<?php

require_once __DIR__ . '/../../../utility_project_media/assets/php/utility_project_media.php';

class project_gallery
{
    private const HTML_DIR = __DIR__ . '/../html/';

    private static int $instance = 0;

    public static function render(array $data = []): string
    {
        $post = is_array($data['post_current_data'] ?? null) ? $data['post_current_data'] : [];
        if (empty($post)) return '';

        $galleries = self::galleries($post, $data);
        if ($galleries === []) return '';
        $tiles = self::order($galleries, $data);
        $total = count($tiles);
        if ($total === 1 && ($data['single'] ?? 'case_study') !== 'gallery') return self::single($data, $tiles[0], $post);

        $pageSize = max(0, (int) ($data['page_size'] ?? 16));
        $featured = ($data['featured'] ?? true) !== false;
        $postKey = preg_replace('/[^a-z0-9\-]/', '', strtolower((string) ($post['_id'] ?? 'post')));
        $group = 'project-gallery-' . $postKey . '-' . (++self::$instance);

        $tilesHtml = self::tiles($tiles, (string) ($post['data']['seo']['title'] ?? ''), $group, $pageSize, $featured);
        if ($tilesHtml === '') return '';

        $gallery = PlatformTemplateRenderer::render(self::HTML_DIR . 'template.html', [
            'page_size' => (string) $pageSize,
            'featured_attr' => $featured ? '' : ' data-featured="false"',
            'group' => self::e($group),
            'status_text' => self::e(utility_project_media::text('gallery_status', 'Showing {shown} of {total} images')),
            'filters' => ($data['filters'] ?? true) !== false && count($galleries) > 1 ? self::filters($galleries, $total) : '',
            'tiles' => $tilesHtml,
            'foot' => self::foot($pageSize > 0 ? min($pageSize, $total) : $total, $total),
        ]);

        return (string) PlatformComponentRenderer::render('section', [
            'id' => array_key_exists('id', $data) ? trim((string) $data['id']) : 'gallery',
            'class' => trim('project-section project-gallery-section ' . self::str($data['section_class'] ?? '')),
            'variant' => $data['variant'] ?? 'panel',
            'children' => self::head($data, $total, count($galleries)) . trim($gallery),
        ]);
    }

    private static function single(array $data, array $tile, array $post): string
    {
        $project = trim((string) ($post['data']['seo']['title'] ?? ''));
        $dir = trim((string) ($data['dir'] ?? 'media'), '/');
        $folder = $tile['key'] === utility_project_media::key(basename($dir)) ? $project : (string) $tile['label'];
        $vars = ['{project}' => $project, '{folder}' => $folder];

        $params = [
            'layout' => 'case_study',
            'post_current_data' => $post,
            'id' => array_key_exists('id', $data) ? trim((string) $data['id']) : 'gallery',
            'title' => strtr(self::opt($data, 'single_title', $folder !== '' ? $folder : $project), $vars),
            'text' => strtr(self::opt($data, 'single_text', utility_project_media::text('gallery_single_image_text', 'Edited for {project}. Open it to see it full size.')), $vars),
            'image' => (string) $tile['src'],
            'label' => strtr(self::opt($data, 'single_label', utility_project_media::text('gallery_single_label', 'Photo editing')), $vars),
            'variant' => $data['variant'] ?? 'panel',
            'class' => trim('project-gallery-single ' . self::str($data['section_class'] ?? '')),
        ];
        if (class_exists('project_page_transitions') && ($rule = project_page_transitions::rule('block_highlight')) !== null) {
            $params['transition'] = $rule;
        }
        return (string) PlatformComponentRenderer::render('block_highlight', $params);
    }

    private static function galleries(array $post, array $data): array
    {
        $labels = is_array($data['labels'] ?? null) ? $data['labels'] : [];
        $found = utility_project_media::galleries($post, trim((string) ($data['dir'] ?? 'media'), '/'), is_array($data['exclude'] ?? null) ? $data['exclude'] : []);
        return array_map(fn ($g) => [
            'key' => $g['key'],
            'label' => trim((string) ($labels[$g['name']] ?? '')) ?: $g['label'],
            'images' => $g['images'],
        ], $found);
    }

    private static function order(array $galleries, array $data): array
    {
        $tiles = [];
        $add = function (array $g, int $n) use (&$tiles): void {
            $src = $g['images'][$n];
            $tiles[] = [
                'key' => $g['key'], 'label' => $g['label'], 'src' => $src, 'n' => $n + 1, 'of' => count($g['images']),
                'logo' => stripos($g['key'] . '/' . basename($src), 'logo') !== false,
            ];
        };

        if (($data['interleave'] ?? true) !== false) {
            $rounds = max(array_map(fn ($g) => count($g['images']), $galleries));
            for ($n = 0; $n < $rounds; $n++) {
                foreach ($galleries as $g) if (isset($g['images'][$n])) $add($g, $n);
            }
        } else {
            foreach ($galleries as $g) foreach (array_keys($g['images']) as $n) $add($g, $n);
        }

        if (($data['logo_first'] ?? false) === true) usort($tiles, fn ($a, $b) => (int) $b['logo'] <=> (int) $a['logo']);
        return $tiles;
    }

    private static function tiles(array $tiles, string $title, string $group, int $pageSize, bool $featured): string
    {
        $html = '';
        foreach ($tiles as $i => $tile) {
            $img = (string) PlatformComponentRenderer::render('image', [
                'src' => $tile['src'],
                'alt' => trim($title . ' - ' . $tile['label'] . ' ' . $tile['n']),
                'lazy' => true,
            ]);
            if (trim($img) === '') continue;
            $hidden = $pageSize > 0 && $i >= $pageSize;
            $html .= trim(PlatformTemplateRenderer::render(self::HTML_DIR . 'parts/tile.html', [
                'classes' => ($featured && $i === 0 ? ' is-featured' : '') . ($tile['logo'] ? ' is-logo' : ''),
                'key' => self::e($tile['key']),
                'group' => self::e($group . ($hidden ? '-hidden' : '')),
                'hidden' => $hidden ? ' hidden' : '',
                'image' => $img,
                'label' => self::e($tile['label']),
                'n' => str_pad((string) $tile['n'], 2, '0', STR_PAD_LEFT),
                'of' => str_pad((string) $tile['of'], 2, '0', STR_PAD_LEFT),
            ]));
        }
        return $html;
    }

    private static function filters(array $galleries, int $total): string
    {
        $chips = self::chip('all', utility_project_media::text('gallery_all', 'All'), $total, true);
        foreach ($galleries as $g) $chips .= self::chip($g['key'], $g['label'], count($g['images']), false);
        return trim(PlatformTemplateRenderer::render(self::HTML_DIR . 'parts/filters.html', [
            'label' => self::e(utility_project_media::text('gallery_filters', 'Filter the gallery')),
            'chips' => $chips,
        ]));
    }

    private static function chip(string $key, string $label, int $count, bool $active): string
    {
        return trim(PlatformTemplateRenderer::render(self::HTML_DIR . 'parts/chip.html', [
            'key' => self::e($key),
            'pressed' => $active ? 'true' : 'false',
            'label' => self::e($label),
            'count' => (string) $count,
        ]));
    }

    private static function foot(int $shown, int $total): string
    {
        return trim(PlatformTemplateRenderer::render(self::HTML_DIR . 'parts/foot.html', [
            'hidden' => $shown >= $total ? ' hidden' : '',
            'status' => self::e(strtr(utility_project_media::text('gallery_status', 'Showing {shown} of {total} images'), ['{shown}' => (string) $shown, '{total}' => (string) $total])),
            'more_icon' => self::icon('load-more'),
            'more' => self::e(utility_project_media::text('gallery_more', 'Load more')),
            'all_icon' => self::icon('grid'),
            'all' => self::e(utility_project_media::text('gallery_show_all', 'Show all')),
        ]));
    }

    private static function head(array $data, int $total, int $galleries): string
    {
        $vars = ['{total}' => (string) $total, '{galleries}' => (string) $galleries];
        $text = $galleries > 1
            ? utility_project_media::text('gallery_text', '{total} images in {galleries} collections. Filter the archive, then open any frame.')
            : utility_project_media::text('gallery_text_single', '{total} images. Open any frame to see it full size.');
        return (string) PlatformComponentRenderer::render('section_head', [
            'kicker' => strtr(self::opt($data, 'kicker', utility_project_media::text('gallery_kicker', 'Photo content')), $vars),
            'title' => strtr(self::opt($data, 'title', utility_project_media::text('gallery_title', 'Photo <em>editing</em>.')), $vars),
            'text' => strtr(self::opt($data, 'text', $text), $vars),
            'count' => strtr(self::opt($data, 'count', utility_project_media::text('gallery_count', '{total} images')), $vars),
        ]);
    }

    private static function icon(string $name): string
    {
        return trim((string) PlatformComponentRenderer::render('svg', ['icon' => $name, 'class' => 'project-gallery-icon']));
    }

    private static function opt(array $data, string $key, string $default): string
    {
        return array_key_exists($key, $data) ? trim((string) $data[$key]) : $default;
    }

    private static function str(mixed $value): string
    {
        return is_scalar($value) ? trim((string) $value) : '';
    }

    private static function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
