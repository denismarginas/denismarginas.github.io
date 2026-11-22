<?php


class card_post_project
{
    private const CATEGORY_WEBSITE = 'Web Projects';
    private const CATEGORY_MEDIA = 'Media Projects';
    private const CARD_CLASS = 'card-post-project';
    private const HTML_DIR = __DIR__ . '/../html/';

    public static function render(array $data = []): string
    {
        $post = is_array($data['post_current_data'] ?? null) ? $data['post_current_data'] : [];
        if (empty($post)) {
            return '';
        }

        $postData = is_array($post['data'] ?? null) ? $post['data'] : [];
        $seo = is_array($postData['seo'] ?? null) ? $postData['seo'] : [];
        $categories = self::resolve_categories($postData);

        $visualComponent = self::resolve_visual_component(
            $data['visual_component'] ?? null,
            $categories
        );

        $visualParams = is_array($data['visual_params'] ?? null) ? $data['visual_params'] : [];
        $visualParams['post_current_data'] = $post;
        $visual = PlatformComponentRenderer::render($visualComponent, $visualParams);

        $link = PlatformPathService::post_link((string) ($post['_id'] ?? ''));
        $title = self::truncate((string) ($seo['title'] ?? ''), self::length($data, 'title_length', 20));
        $descriptionHtml = self::render_description($data, $seo);

        $visualHtml = PlatformTemplateRenderer::render(self::HTML_DIR . 'parts/visual.html', ['visual' => $visual]);
        $bodyHtml = PlatformTemplateRenderer::render(self::HTML_DIR . 'parts/body.html', [
            'link' => htmlspecialchars($link, ENT_QUOTES, 'UTF-8'),
            'title' => htmlspecialchars($title, ENT_QUOTES, 'UTF-8'),
            'description' => $descriptionHtml,
        ]);

        $cardClass = self::card_class($data);
        $columns = is_array($data['columns'] ?? null) ? $data['columns'] : null;
        if ($columns !== null) {
            $cardClass .= ' card-post-project-columns';
            $inner = self::columns($columns, $visualHtml, $bodyHtml);
        } else {
            $inner = $visualHtml . $bodyHtml;
        }

        return PlatformTemplateRenderer::render(self::HTML_DIR . 'template.html', [
            'card_class' => htmlspecialchars($cardClass, ENT_QUOTES, 'UTF-8'),
            'inner' => $inner,
            'visual_type' => htmlspecialchars($visualComponent, ENT_QUOTES, 'UTF-8'),
            'style_attr' => (string) PlatformComponentRenderer::value('utility_appearance_colors', [
                'post_current_data' => $post,
                'output' => 'attribute',
            ]),
        ]);
    }

    private static function columns(array $params, string $visualHtml, string $bodyHtml): string
    {
        $params = array_merge(['gap' => 'sm', 'align' => 'start'], $params);
        $params['children'] = [
            ['html' => $visualHtml, 'class' => 'card-post-project-column-visual'],
            ['html' => $bodyHtml, 'class' => 'card-post-project-column-body'],
        ];
        return (string) PlatformComponentRenderer::render('columns', $params);
    }

    private static function length(array $data, string $key, int $default): int
    {
        $value = $data[$key] ?? $default;
        return is_numeric($value) ? max(0, (int) $value) : $default;
    }

    private static function card_class(array $data): string
    {
        $class = is_scalar($data['card_class'] ?? null) ? (string) $data['card_class'] : '';
        $class = trim(preg_replace('/\s+/', ' ', preg_replace('/[^a-zA-Z0-9_\- ]/', '', $class)));
        return $class !== '' ? $class : self::CARD_CLASS;
    }

    private static function render_description(array $data, array $seo): string
    {
        $show = (bool) ($data['description'] ?? false);
        if (!$show) {
            return '';
        }

        $excerpt = self::truncate((string) ($seo['description'] ?? ''), self::length($data, 'description_length', 25));
        if ($excerpt === '') {
            return '';
        }

        return '<p class="card-description">' . htmlspecialchars($excerpt, ENT_QUOTES, 'UTF-8') . '</p>';
    }

    private static function resolve_categories(array $postData): array
    {
        $categories = $postData['taxonomy']['category'] ?? [];
        return is_array($categories) ? $categories : [];
    }

    private static function resolve_visual_component(?string $override, array $categories): string
    {
        if (is_string($override) && $override !== '') {
            $override = str_replace('-', '_', $override);
            return $override === 'card_post_project_visual_website' ? 'card_post_project_visual_devices_website' : $override;
        }
        if (in_array(self::CATEGORY_WEBSITE, $categories, true)) {
            return 'card_post_project_visual_devices_website';
        }
        if (in_array(self::CATEGORY_MEDIA, $categories, true)) {
            return 'card_post_project_visual_media';
        }
        return 'card_post_project_visual_default';
    }

    public static function truncate(string $text, int $maxLength): string
    {
        $text = trim($text);
        if ($maxLength === 0) {
            return $text;
        }
        if ($maxLength <= 3) {
            return mb_substr($text, 0, $maxLength);
        }
        if (mb_strlen($text) <= $maxLength) {
            return $text;
        }
        return rtrim(mb_substr($text, 0, $maxLength - 3)) . '...';
    }
}
