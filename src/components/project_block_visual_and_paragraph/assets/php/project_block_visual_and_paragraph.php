<?php

require_once __DIR__ . '/../../../utility_post_thumbnail/assets/php/utility_post_thumbnail.php';

class project_block_visual_and_paragraph
{
    private const HTML_DIR = __DIR__ . '/../html/';
    private const PROJECTS_DIR = 'src/content/img/projects/';
    private const ALLOWED_TAGS = ['br', 'b', 'strong', 'i', 'em', 'u'];
    private const PASSTHROUGH = ['post_current_data', 'post_id', 'global_content_path', 'global_img_path', 'global_vid_path'];
    private const COLUMNS_DEFAULTS = [
        'layout' => ['base' => '100', 'sm' => '40-60', 'md' => '30-70', 'lg' => '40-60'],
        'gap' => 'sm',
        'align' => 'start',
    ];

    public static function render(array $data = []): string
    {
        $shared = array_intersect_key($data, array_flip(self::PASSTHROUGH));

        $wrap = strtolower(trim((string) ($data['mode'] ?? 'wrap'))) !== 'columns';
        $right = strtolower(trim((string) ($data['visual_position'] ?? 'left'))) === 'right';

        $visual = post_short_info_post::flag($data, 'show_visual', true) ? self::visual($data, $shared, !$wrap) : '';
        $content = self::content($data, $shared, $wrap ? $visual : '');
        $extraText = self::text((string) ($data['text_extra'] ?? ''));
        if (trim($visual . $content) === '' && $extraText === '') return '';

        $classes = [$wrap ? 'project-block-visual-and-paragraph-mode-wrap' : 'project-block-visual-and-paragraph-mode-columns'];
        if ($right) $classes[] = 'project-block-visual-and-paragraph-visual-right';
        if (trim($visual) === '') $classes[] = 'project-block-visual-and-paragraph-no-visual';
        if (trim($content) === '') $classes[] = 'project-block-visual-and-paragraph-no-content';
        $extra = trim((string) ($data['class'] ?? ''));
        if ($extra !== '') $classes[] = $extra;
        $id = trim((string) ($data['id'] ?? ''));
        $style = self::sizes($data);

        return PlatformTemplateRenderer::render([
            'extra_class' => $classes ? ' ' . self::esc(implode(' ', $classes)) : '',
            'id_attr' => $id !== '' ? ' id="' . self::esc($id) . '"' : '',
            'style_attr' => $style !== '' ? ' style="' . self::esc($style) . '"' : '',
            'body' => $wrap ? $content : self::body($visual, $content, $data),
            'extra' => $extraText !== ''
                ? '<p class="project-block-visual-and-paragraph-text project-block-visual-and-paragraph-text-extra">' . $extraText . '</p>'
                : '',
        ]);
    }

    private static function sizes(array $data): string
    {
        $vars = [];
        foreach (['image_height' => '--pvp-image-height', 'image_height_stacked' => '--pvp-image-height-stacked'] as $key => $var) {
            $v = is_scalar($data[$key] ?? null) ? strtolower(trim((string) $data[$key])) : '';
            if ($v !== '' && preg_match('/^\d+(\.\d+)?(px|rem|em|vh)$/', $v)) $vars[] = $var . ': ' . $v;
        }
        return implode('; ', $vars);
    }

    private static function body(string $visual, string $content, array $data): string
    {
        if (trim($visual) === '' || trim($content) === '') return $visual . $content;

        $params = self::object($data['columns'] ?? []);
        $visualFirst = ($params['media_first'] ?? true) !== false
            && strtolower(trim((string) ($data['visual_position'] ?? 'left'))) !== 'right';
        unset($params['media_first'], $params['children']);

        $defaults = self::COLUMNS_DEFAULTS;
        if (!$visualFirst) $defaults['layout'] = ['base' => '100', 'sm' => '60-40', 'md' => '70-30', 'lg' => '60-40'];
        $columns = array_merge($defaults, $params);
        $columns['class'] = trim('project-block-visual-and-paragraph-columns ' . (is_scalar($params['class'] ?? null) ? (string) $params['class'] : ''));

        $v = ['html' => $visual, 'class' => 'project-block-visual-and-paragraph-column-visual'];
        $c = ['html' => $content, 'class' => 'project-block-visual-and-paragraph-column-content'];
        $columns['children'] = $visualFirst ? [$v, $c] : [$c, $v];

        return (string) PlatformComponentRenderer::render('columns', $columns);
    }

    private static function visual(array $data, array $shared, bool $overlay): string
    {
        $image = self::image_source($data);
        if ($image !== '') {
            $img = (string) PlatformComponentRenderer::render('image', [
                'src' => $image,
                'alt' => (string) ($data['post_current_data']['data']['seo']['title'] ?? ''),
                'class' => 'project-block-visual-and-paragraph-img',
                'lazy' => true,
            ]);
            if (trim($img) !== '') {
                return PlatformTemplateRenderer::render(self::HTML_DIR . 'parts/image.html', [
                    'image' => $img,
                    'overlay' => $overlay ? (string) PlatformComponentRenderer::render('overlay_noise', []) : '',
                ]);
            }
        }

        return (string) PlatformComponentRenderer::render('post_short_info_visual', array_merge(
            self::object($data['visual'] ?? []),
            $shared,
            ['class' => 'project-block-visual-and-paragraph-visual']
        ));
    }

    private static function content(array $data, array $shared, string $visual = ''): string
    {
        $text = self::text((string) ($data['text'] ?? ''));
        $prefix = trim((string) ($data['tags_prefix'] ?? 'chevron'));
        $tags = (string) PlatformComponentRenderer::render('block_tags', array_merge($shared, [
            'tags' => $data['tags'] ?? [],
            'prefix' => $prefix,
            'class' => 'project-block-visual-and-paragraph-tags',
        ]));
        if ($text === '' && trim($tags) === '' && trim($visual) === '') return '';

        return PlatformTemplateRenderer::render(self::HTML_DIR . 'parts/content.html', [
            'text' => $visual . ($text !== '' ? '<p class="project-block-visual-and-paragraph-text">' . $text . '</p>' : ''),
            'tags' => $tags,
        ]);
    }

    private static function image_source(array $data): string
    {
        $post = is_array($data['post_current_data'] ?? null) ? $data['post_current_data'] : [];
        $raw = trim((string) ($data['image'] ?? ''));

        if ($raw === '') return utility_post_thumbnail::get($post);
        if (utility_post_thumbnail::is_reference($raw)) return utility_post_thumbnail::reference($raw, $post);
        return self::image_path($raw, $post);
    }

    private static function image_path(string $path, mixed $post): string
    {
        $path = trim(str_replace('\\', '/', $path), '/');
        if ($path === '' || str_contains($path, '..')) return '';

        $candidates = [];
        if (str_starts_with($path, 'src/')) {
            $candidates[] = $path;
        } else {
            $media = is_array($post) ? trim(str_replace('\\', '/', (string) ($post['data']['media']['path'] ?? '')), '/') : '';
            if ($media !== '') $candidates[] = self::PROJECTS_DIR . $media . '/' . $path;
            $candidates[] = self::PROJECTS_DIR . $path;
        }

        $root = defined('ENGINE_PROJECT_ROOT') ? rtrim(ENGINE_PROJECT_ROOT, '/\\') . '/' : '';
        foreach ($candidates as $candidate) {
            if (is_file($root . $candidate)) return $candidate;
        }
        return '';
    }

    private static function text(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '') return '';

        $html = htmlspecialchars($raw, ENT_QUOTES, 'UTF-8', false);
        $tags = implode('|', self::ALLOWED_TAGS);
        $html = preg_replace_callback(
            '#&lt;\s*(/?)\s*(' . $tags . ')\s*/?\s*&gt;#i',
            fn ($m) => strtolower($m[2]) === 'br' ? '<br>' : '<' . $m[1] . strtolower($m[2]) . '>',
            $html
        );

        return trim(preg_replace('#^(\s*<br>)+|(<br>\s*)+$#', '', $html));
    }

    private static function object(mixed $value): array
    {
        if (is_string($value)) $value = json_decode($value, true);
        return is_array($value) ? $value : [];
    }

    private static function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
