<?php

class video_archive
{
    private const HTML_DIR = __DIR__ . '/../html/';
    private const PLACEHOLDER = 'thumbnails/video-thumbnail-placeholder.webp';
    private const SORT_FIELDS = [
        ['label' => 'Date', 'by' => 'date', 'type' => 'date', 'order' => 'desc'],
        ['label' => 'Title', 'by' => 'title', 'type' => 'text', 'order' => 'asc'],
    ];

    public static function render(array $data = []): string
    {
        $data = listing_toolbar::data($data, 'video-archive');
        $id = self::slug((string) ($data['id'] ?? '')) ?: 'video-archive';

        $videos = self::videos($data);
        if (empty($videos)) return '';

        $placeholder = (string) ($data['placeholder'] ?? self::PLACEHOLDER);
        $tags = self::tags($videos);

        $items = '';
        $index = [];
        foreach ($videos as $video) {
            $entry = self::entry($video, $placeholder, $data);
            if ($entry === null) continue;
            $items .= $entry['html'];
            $index[$entry['id']] = $entry['index'];
        }
        if ($items === '') return '';

        $count = count($index);

        return PlatformTemplateRenderer::render(self::HTML_DIR . 'template.html', [
            'id' => self::e($id),
            'form_id' => self::e($id . '-filters'),
            'toolbar' => self::toolbar($data, $id, $tags, $count),
            'index' => self::json($index),
            'count' => (string) $count,
            'items' => $items,
            'empty_text' => self::e(self::text($data, 'empty_text', 'No videos match your search.')),
        ]);
    }

    private static function videos(array $data): array
    {
        $videos = array_values(array_filter(
            PlatformDataService::get_collection('video') ?? [],
            fn ($v) => is_array($v) && ($v['settings']['render'] ?? true) !== false && self::source($v) !== ''
        ));

        $filterTags = $data['filter']['taxonomy']['tag'] ?? null;
        if (is_array($filterTags) && $filterTags !== []) {
            $videos = array_values(array_filter($videos, fn ($v) => array_intersect($filterTags, self::video_tags($v)) !== []));
        }

        $filterIds = $data['filter']['_id'] ?? null;
        if (is_array($filterIds) && $filterIds !== []) {
            $videos = array_values(array_filter($videos, fn ($v) => in_array((string) ($v['_id'] ?? ''), array_map('strval', $filterIds), true)));
        }

        usort($videos, fn ($a, $b) => self::timestamp($b) <=> self::timestamp($a));
        return $videos;
    }

    private static function entry(array $video, string $placeholder, array $data): ?array
    {
        $id = (string) ($video['_id'] ?? '');
        if ($id === '') return null;

        $d = $video['data'] ?? [];
        $info = $d['media']['video']['video_data'] ?? [];
        $title = trim((string) ($info['title'] ?? $d['seo']['title'] ?? $id));
        $description = (string) ($info['description'] ?? '');
        $date = trim((string) ($d['date']['publish'] ?? ''));
        $tags = self::video_tags($video);
        $thumb = self::thumbnail((string) ($d['media']['video']['video_thumbnail'] ?? ''), $placeholder);
        $color = (string) ($video['settings']['appearance']['colors']['primary'] ?? '');

        $meta = self::meta($date, $tags);
        $card = PlatformTemplateRenderer::render(self::HTML_DIR . 'parts/card.html', [
            'aria_label' => self::e(PlatformTextService::ui('video_archive_play', 'Play video') . ': ' . $title),
            'image' => $thumb === '' ? '' : (string) PlatformComponentRenderer::render('image', [
                'src' => $thumb,
                'alt' => PlatformTextService::ui('video_archive_thumbnail', 'Thumbnail') . ': ' . $title,
                'class' => 'video-archive-image',
                'lazy' => true,
            ]),
            'meta' => $meta,
            'title' => self::e($title),
        ]);

        $content = self::content($video, $thumb, $meta, $title, $description, $color);

        $popup = (string) PlatformComponentRenderer::render('popup', [
            'trigger' => $card,
            'content' => $content,
            'panel' => true,
            'width' => (string) ($data['popup_width'] ?? 'min(1100px, 100%)'),
            'hash' => $id,
            'class' => 'video-archive-trigger',
        ]);

        $html = PlatformTemplateRenderer::render(self::HTML_DIR . 'parts/item.html', [
            'item_id' => self::e($id),
            'colors' => (string) PlatformComponentRenderer::value('utility_appearance_colors', [
                'post_current_data' => $video,
                'output' => 'attribute',
            ]),
            'popup' => $popup,
        ]);

        return [
            'id' => $id,
            'html' => $html,
            'index' => [
                'keywords' => self::keywords([$id, $title, strip_tags(str_replace(['<br>', '</li>', '</p>'], ' ', $description)), implode(' ', $tags), $date]),
                'tag' => $tags,
                '_sort' => ['date' => self::timestamp($video), 'title' => mb_strtolower($title)],
            ],
        ];
    }

    public static function popup(string $id, string $placeholder = self::PLACEHOLDER): string
    {
        $id = trim($id);
        if ($id === '') return '';
        foreach (PlatformDataService::get_collection('video') ?? [] as $video) {
            if (!is_array($video) || (string) ($video['_id'] ?? '') !== $id || self::source($video) === '') continue;
            $d = $video['data'] ?? [];
            $info = $d['media']['video']['video_data'] ?? [];
            return self::content(
                $video,
                self::thumbnail((string) ($d['media']['video']['video_thumbnail'] ?? ''), $placeholder),
                self::meta(trim((string) ($d['date']['publish'] ?? '')), self::video_tags($video)),
                trim((string) ($info['title'] ?? $d['seo']['title'] ?? $id)),
                (string) ($info['description'] ?? ''),
                (string) ($video['settings']['appearance']['colors']['primary'] ?? '')
            );
        }
        return '';
    }

    private static function content(array $video, string $thumb, string $meta, string $title, string $description, string $color): string
    {
        return PlatformTemplateRenderer::render(self::HTML_DIR . 'parts/popup.html', [
            'video' => (string) PlatformComponentRenderer::render('video', [
                'src' => self::source($video),
                'thumbnail' => $thumb === '' ? '' : self::url($thumb),
                'appearance' => ['colors' => ['primary' => $color !== '' ? $color : '#fff']],
            ]),
            'meta' => $meta,
            'title' => self::e($title),
            'description' => $description,
            'buttons' => self::buttons($video['data']['media']['buttons'] ?? []),
            'style' => preg_match('/^#[0-9a-f]{3,8}$|^(rgb|hsl)a?\([^;{}]*\)$/i', trim($color)) ? ' style="--video-color: ' . self::e(trim($color)) . ';"' : '',
        ]);
    }

    private static function toolbar(array $data, string $id, array $tags, int $count): string
    {
        $placeholder = self::text($data, 'search_placeholder', 'Search videos...');

        $filters = '';
        if (count($tags) > 1) {
            $options = '<option value="">' . self::e(filters_toolbar::placeholder($data['type_placeholder'] ?? '')) . '</option>';
            foreach ($tags as $tag) {
                $options .= '<option value="' . self::e($tag) . '">' . self::e(self::label($tag)) . '</option>';
            }
            $label = filters_toolbar::label(self::text($data, 'type_label', 'Type'));
            $filters = '<div class="filter"><label for="' . self::e($id . '-tag') . '">' . self::e($label) . '</label>'
                . '<select id="' . self::e($id . '-tag') . '" name="type" data-filter="tag">' . $options . '</select></div>';
        }

        $sort = (string) filters_sort::render(array_merge($data, [
            'target' => $id,
            'sort_fields' => $data['sort_fields'] ?? array_map(fn ($f) => array_merge($f, ['label' => PlatformTextService::ui('video_archive_sort_' . $f['by'], $f['label'])]), self::SORT_FIELDS),
        ], filters_toolbar::sort_labels($data)));

        return filters_toolbar::render($data, [
            'target' => $id,
            'input' => [
                'class' => 'item-search',
                'name' => 'keywords',
                'placeholder' => $placeholder,
                'aria-label' => $placeholder,
                'autocomplete' => 'off',
            ],
            'results_text' => filters_toolbar::results_text(self::text($data, 'results_text', 'Showing {n} videos.'), $count),
            'count' => $count,
            'filters' => $filters,
            'sort' => $sort,
        ]);
    }

    private static function buttons(mixed $buttons): string
    {
        if (!is_array($buttons) || $buttons === []) return '';
        $items = [];
        foreach ($buttons as $b) {
            if (!is_array($b) || trim((string) ($b['url'] ?? '')) === '') continue;
            $items[] = ['text' => $b['text'] ?? '', 'link' => $b['url'], 'svg' => $b['svg'] ?? '', 'class' => 'btn btn-primary-small'];
        }
        if ($items === []) return '';
        return (string) PlatformComponentRenderer::render('text_block', ['elements' => [
            ['buttons' => $items, 'class' => 'video-archive-popup-buttons'],
        ]]);
    }

    private static function meta(string $date, array $tags): string
    {
        $parts = [];
        if ($date !== '') $parts[] = '<time>' . self::e(($t = strtotime($date)) !== false ? PlatformTextService::format_date(date('Y-m-d', $t), 'M j, Y') : $date) . '</time>';
        foreach ($tags as $tag) $parts[] = '<span>' . self::e(self::label($tag)) . '</span>';
        return implode('', $parts);
    }

    private static function tags(array $videos): array
    {
        $all = [];
        foreach ($videos as $video) {
            foreach (self::video_tags($video) as $tag) $all[$tag] = true;
        }
        $all = array_keys($all);
        sort($all, SORT_NATURAL | SORT_FLAG_CASE);
        return $all;
    }

    private static function video_tags(array $video): array
    {
        $tags = $video['data']['taxonomy']['tag'] ?? $video['taxonomy']['tag'] ?? [];
        return array_values(array_filter(array_map(fn ($t) => trim((string) $t), (array) $tags), fn ($t) => $t !== ''));
    }

    private static function source(array $video): string
    {
        return trim((string) ($video['data']['media']['video']['video'] ?? ''));
    }

    private static function timestamp(array $video): int
    {
        $time = strtotime((string) ($video['data']['date']['publish'] ?? ''));
        return $time === false ? 0 : $time;
    }

    private static function thumbnail(string $path, string $placeholder): string
    {
        foreach ([$path, $placeholder] as $candidate) {
            $candidate = ltrim(trim(str_replace('\\', '/', $candidate)), '/');
            if ($candidate === '') continue;
            if (PlatformUrlService::is_external_url($candidate)) return $candidate;
            $full = str_starts_with($candidate, 'src/') ? $candidate : 'src/content/img/' . $candidate;
            if (is_file(self::root() . $full)) return $full;
        }
        return '';
    }

    private static function url(string $path): string
    {
        if (PlatformUrlService::is_external_url($path)) return $path;
        return rtrim(PlatformPathService::asset_relative_prefix(), '/') . '/' . ltrim($path, '/');
    }

    private static function root(): string
    {
        return defined('ENGINE_PROJECT_ROOT') ? rtrim(ENGINE_PROJECT_ROOT, '/\\') . '/' : '';
    }

    private static function keywords(array $parts): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/', ' ', html_entity_decode(implode(' ', $parts), ENT_QUOTES, 'UTF-8'))));
    }

    private static function label(string $tag): string
    {
        return PlatformTextService::ui('video_tag_' . self::slug(mb_strtolower($tag)), mb_convert_case($tag, MB_CASE_TITLE, 'UTF-8'));
    }

    private static function slug(string $value): string
    {
        return trim(preg_replace('/[^a-z0-9_-]+/i', '-', $value), '-');
    }

    private static function text(array $data, string $key, string $default): string
    {
        $value = $data[$key] ?? null;
        return is_scalar($value) && trim((string) $value) !== '' ? (string) $value : PlatformTextService::ui('video_archive_' . $key, $default);
    }

    private static function json(array $value): string
    {
        $json = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP);
        return $json === false ? '{}' : $json;
    }

    private static function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
