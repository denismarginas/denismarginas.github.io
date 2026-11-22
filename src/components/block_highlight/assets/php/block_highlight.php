<?php

class block_highlight
{
    private const HTML_DIR = __DIR__ . '/../html/';
    private const TAGS = ['h2', 'h3', 'h4', 'div'];
    private const ALLOWED_TAGS = ['br', 'b', 'strong', 'i', 'em', 'u'];
    private const PASSTHROUGH = ['post_current_data', 'global_content_path', 'global_img_path', 'global_vid_path'];
    private const SPLIT_LENGTH = 600;
    private const SPLIT_KEEP = 0.45;
    private const COLUMNS_DEFAULTS = [
        'layout' => ['base' => '100', 'md' => '50-50'],
        'gap' => 'sm',
        'align' => 'start',
        'reverse_stacked' => false,
    ];

    public static function render(array $data = []): string
    {
        $title = trim((string) ($data['title'] ?? ''));
        if ($title === '') return '';

        $shared = array_intersect_key($data, array_flip(self::PASSTHROUGH));
        if (($data['layout'] ?? '') === 'case_study') return self::case_study($data, $title, $shared);

        $mediaItems = self::media($data, $shared);
        $text = self::text((string) ($data['text'] ?? ''));
        $textFull = self::text((string) ($data['text_full'] ?? ''));
        $tags = self::tags($data);
        $children = self::children($data, $shared);
        if (!$mediaItems && $text === '' && $textFull === '' && $tags === '' && $children === '') return '';

        $media = $mediaItems
            ? PlatformTemplateRenderer::render(self::HTML_DIR . 'parts/media.html', ['items' => implode('', $mediaItems)])
            : '';
        $content = $text !== ''
            ? PlatformTemplateRenderer::render(self::HTML_DIR . 'parts/content.html', ['text' => $text])
            : '';
        $contentFull = $textFull !== ''
            ? PlatformTemplateRenderer::render(self::HTML_DIR . 'parts/content_full.html', ['text' => $textFull])
            : '';

        $classes = [];
        if ($media === '') $classes[] = 'block-highlight-no-media';
        if ($content === '') $classes[] = 'block-highlight-no-text';
        $extra = trim((string) ($data['class'] ?? ''));
        if ($extra !== '') $classes[] = $extra;

        $tag = strtolower(trim((string) ($data['tag'] ?? 'h3')));
        if (!in_array($tag, self::TAGS, true)) $tag = 'h3';

        $id = array_key_exists('id', $data) ? trim((string) $data['id']) : preg_replace('/[^a-z0-9]/', '', strtolower($title));
        $label = trim((string) ($data['label'] ?? ''));

        return PlatformTemplateRenderer::render([
            'extra_class' => $classes ? ' ' . self::esc(implode(' ', $classes)) : '',
            'id_attr' => $id !== '' ? ' id="' . self::esc($id) . '"' : '',
            'tag' => $tag,
            'title' => htmlspecialchars($title, ENT_QUOTES, 'UTF-8', false),
            'label' => $label !== '' ? '<span class="block-highlight-label">' . self::esc($label) . '</span>' : '',
            'body' => self::body($content, $media, $data) . $contentFull . $children,
            'tags' => $tags,
        ]);
    }

    private static function body(string $content, string $media, array $data): string
    {
        if ($content === '' || $media === '') return $content . $media;

        $params = is_array($data['columns'] ?? null) ? $data['columns'] : [];
        $mediaFirst = ($params['media_first'] ?? true) !== false;
        unset($params['media_first'], $params['children']);

        $columns = array_merge(self::COLUMNS_DEFAULTS, $params);
        $columns['class'] = trim('block-highlight-body ' . (is_scalar($params['class'] ?? null) ? (string) $params['class'] : ''));

        $text = ['html' => $content, 'class' => 'block-highlight-column-text'];
        $visual = ['html' => $media, 'class' => 'block-highlight-column-media'];
        $columns['children'] = $mediaFirst ? [$visual, $text] : [$text, $visual];

        return (string) PlatformComponentRenderer::render('columns', $columns);
    }

    private static function children(array $data, array $shared): string
    {
        $children = $data['children'] ?? [];
        if (!is_array($children) || $children === []) return '';

        $html = (string) section::render_children(array_merge($shared, ['children' => $children]));
        return trim($html) === '' ? '' : '<div class="block-highlight-children">' . $html . '</div>';
    }

    private static function media(array $data, array $shared): array
    {
        $items = [];

        $image = trim(str_replace('\\', '/', (string) ($data['image'] ?? '')), '/');
        if ($image !== '') {
            $dir = str_contains($image, '/') ? substr($image, 0, strrpos($image, '/')) . '/' : '';
            $items[] = (string) PlatformComponentRenderer::render('block_image', array_merge($shared, [
                'image_path' => $dir,
                'image_file_name' => basename($image),
                'alt' => trim((string) ($data['title'] ?? '')),
                'card' => false,
                'class' => 'block-highlight-image',
            ]));
        }

        $slider = trim(str_replace('\\', '/', (string) ($data['slider'] ?? '')));
        $videos = self::videos($data['video'] ?? []);
        $videoInSlider = $slider !== '' && $videos && ($data['video_in_slider'] ?? true) !== false;

        if ($slider !== '') {
            $items[] = (string) PlatformComponentRenderer::render('block_slider_images', array_merge($shared, [
                'img_array_path_dir' => $slider,
                'videos' => $videoInSlider ? $videos : [],
                'class' => 'block-highlight-slider',
            ], self::slider_options($data)));
        }

        if (!$videoInSlider) {
            foreach ($videos as $video) {
                $items[] = (string) PlatformComponentRenderer::render('project_block_video', array_merge($shared, [
                    'src' => $video,
                    'class' => 'block-highlight-video',
                ]));
            }
        }

        return array_values(array_filter($items, fn ($html) => trim($html) !== ''));
    }

    private static function slider_options(array $data): array
    {
        return array_intersect_key($data, array_flip(['show_arrows', 'show_numbers', 'show_dots']));
    }

    private static function videos(mixed $value): array
    {
        $list = is_array($value) ? $value : [$value];
        return array_values(array_filter(array_map(
            fn ($v) => is_scalar($v) ? trim(str_replace('\\', '/', (string) $v)) : '',
            $list
        ), fn ($v) => $v !== ''));
    }

    private static function tags(array $data): string
    {
        $list = $data['tags'] ?? [];
        if (is_string($list)) {
            $decoded = json_decode($list, true);
            $list = is_array($decoded) ? $decoded : explode(',', $list);
        }
        if (!is_array($list)) return '';

        $html = '';
        foreach ($list as $tag) {
            $text = trim(is_array($tag) ? (string) ($tag['text'] ?? '') : (string) $tag);
            if ($text === '') continue;
            $html .= PlatformTemplateRenderer::render(self::HTML_DIR . 'parts/tag.html', ['text' => self::esc($text)]);
        }
        if ($html === '') return '';

        $title = trim((string) ($data['tags_title'] ?? ''));

        return PlatformTemplateRenderer::render(self::HTML_DIR . 'parts/tags.html', [
            'tags_title' => $title !== '' ? '<span class="block-highlight-tags-title text-label">' . self::esc($title) . '</span>' : '',
            'tags' => $html,
        ]);
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

    private static function split_text(string $text): array
    {
        $paragraphs = preg_split('#(?:<br>\s*){2,}#', $text) ?: [];
        $total = mb_strlen(strip_tags($text));
        if (count($paragraphs) < 2 || $total <= self::SPLIT_LENGTH) return [$text, ''];

        $keep = [];
        $length = 0;
        while ($paragraphs !== [] && ($keep === [] || $length < $total * self::SPLIT_KEEP)) {
            $paragraph = array_shift($paragraphs);
            $keep[] = $paragraph;
            $length += mb_strlen(strip_tags($paragraph));
        }
        return [implode('<br><br>', $keep), implode('<br><br>', $paragraphs)];
    }

    private static function case_study(array $data, string $title, array $shared): string
    {
        $post = is_array($data['post_current_data'] ?? null) ? $data['post_current_data'] : [];
        [$media, $mediaCount, $hasVideo] = self::case_media($data, $post, $shared, $title);
        $text = self::text((string) ($data['text'] ?? ''));
        $textFull = self::text((string) ($data['text_full'] ?? ''));
        $tags = self::tags($data);
        $children = self::children($data, $shared);
        if ($media === '' && $text === '' && $textFull === '' && $tags === '' && $children === '') return '';

        $tag = strtolower(trim((string) ($data['tag'] ?? 'h3')));
        if (!in_array($tag, self::TAGS, true)) $tag = 'h3';
        $id = array_key_exists('id', $data) ? trim((string) $data['id']) : preg_replace('/[^a-z0-9]/', '', strtolower($title));
        $label = array_key_exists('label', $data) ? trim((string) $data['label']) : ($hasVideo ? 'Showreel' : 'Case study');
        if (class_exists('utility_project_media') && !array_key_exists('label', $data)) {
            $label = $hasVideo
                ? utility_project_media::text('case_study_label_video', 'Showreel')
                : utility_project_media::text('case_study_label', 'Case study');
        }

        if ($mediaCount === 1 && trim($media) !== '' && $textFull === '') [$text, $textFull] = self::split_text($text);
        $textUnderMedia = $mediaCount === 1 && trim($media) !== '' && $textFull !== '';

        $titleClass = in_array($data['title_class'] ?? '', ['title-primary', 'title-secondary', 'title-tertiary'], true) ? $data['title_class'] : 'title-secondary';

        $classes = ['block-highlight-case'];
        if ($media === '') $classes[] = 'block-highlight-case-no-media';
        if ($media === '' && mb_strlen(strip_tags($text . $textFull)) > self::SPLIT_LENGTH) $classes[] = 'block-highlight-case-text-columns';
        if (($data['flip'] ?? false) === true) $classes[] = 'block-highlight-case-flip';

        $body = '<div class="block-highlight-case-body block-paragraph-card block-paragraph-card-fill">'
            . ($label !== '' ? '<span class="block-highlight-case-label text-label">' . self::esc($label) . '</span>' : '')
            . '<' . $tag . ' class="block-highlight-case-title ' . self::esc($titleClass) . ' title-divider">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8', false) . '</' . $tag . '>'
            . ($text !== '' ? '<p class="block-highlight-case-text">' . $text . '</p>' : '')
            . ($textFull !== '' && !$textUnderMedia ? '<p class="block-highlight-case-text">' . $textFull . '</p>' : '')
            . $tags
            . '</div>';
        if ($textUnderMedia) $media .= '<p class="block-highlight-case-media-text">' . $textFull . '</p>';

        $html = '<div class="' . implode(' ', $classes) . '">'
            . ($media !== '' ? '<div class="block-highlight-case-media">' . $media . '</div>' : '')
            . $body
            . '</div>'
            . ($children !== '' ? '<div class="block-highlight-case-children">' . $children . '</div>' : '');

        $extra = trim((string) ($data['class'] ?? ''));
        return (string) PlatformComponentRenderer::render('section', [
            'id' => $id,
            'class' => trim('project-section block-highlight-case-section ' . $extra),
            'variant' => $data['variant'] ?? '',
            'children' => $html,
        ]);
    }

    private static function case_media(array $data, array $post, array $shared, string $title): array
    {
        $slider = trim(str_replace('\\', '/', (string) ($data['slider'] ?? '')));
        if (($data['case_media'] ?? '') === 'slider' && $slider !== '') {
            $videos = self::videos($data['video'] ?? []);
            $html = (string) PlatformComponentRenderer::render('block_slider_images', array_merge($shared, [
                'img_array_path_dir' => $slider,
                'videos' => $videos,
                'class' => 'block-highlight-case-slider',
            ], self::slider_options($data)));
            if (trim($html) !== '') return [$html, 2, $videos !== []];
        }

        $mediaDir = 'src/content/img/projects/' . trim((string) ($post['data']['media']['path'] ?? ''), '/') . '/';
        $images = [];
        $image = trim(str_replace('\\', '/', (string) ($data['image'] ?? '')), '/');
        if ($image !== '') $images[] = ['type' => 'image', 'src' => str_starts_with($image, 'src/') ? $image : $mediaDir . $image, 'alt' => $title];

        $slider = trim(str_replace('\\', '/', (string) ($data['slider'] ?? '')), '/');
        if ($slider !== '' && class_exists('utility_project_web_screens')) {
            $dir = str_starts_with($slider, 'src/') ? $slider : $mediaDir . $slider;
            foreach (utility_project_web_screens::images($dir . '/') as $src) $images[] = ['type' => 'image', 'src' => $src, 'alt' => $title];
        }

        $videos = '';
        foreach (self::videos($data['video'] ?? []) as $video) {
            $player = (string) PlatformComponentRenderer::render('project_block_video', array_merge($shared, ['src' => $video]));
            if (trim($player) !== '') $videos .= '<div class="block-highlight-case-video">' . $player . '</div>';
        }

        $grid = '';
        $count = count($images) + substr_count($videos, 'class="block-highlight-case-video"');
        if ($images !== []) {
            $id = preg_replace('/[^a-z0-9\-]/', '', strtolower((string) ($post['_id'] ?? 'post') . '-' . preg_replace('/[^a-z0-9]/', '', strtolower($title))));
            $grid = (string) PlatformComponentRenderer::render('media_grid', [
                'items' => $images,
                'group' => 'case-study-' . $id,
                'max' => $videos !== '' ? 2 : 4,
                'class' => 'block-highlight-case-grid',
            ]);
        }

        return [$grid . $videos, $count, $videos !== ''];
    }

    private static function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
