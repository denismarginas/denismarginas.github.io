<?php

require_once __DIR__ . '/../../../utility_project_media/assets/php/utility_project_media.php';

class media_gallery_filterable
{
    private static int $instance = 0;

    public static function render(array $data = []): string
    {
        $post = is_array($data['post_current_data'] ?? null) ? $data['post_current_data'] : [];
        if (empty($post)) return '';

        $galleries = utility_project_media::galleries($post, trim((string) ($data['dir'] ?? 'media'), '/'), is_array($data['exclude'] ?? null) ? $data['exclude'] : []);
        if ($galleries === []) return '';

        $labels = is_array($data['labels'] ?? null) ? $data['labels'] : [];
        $pageSize = max(0, (int) ($data['page_size'] ?? 16));
        $featured = ($data['featured'] ?? true) !== false;
        $postKey = preg_replace('/[^a-z0-9\-]/', '', strtolower((string) ($post['_id'] ?? 'post')));
        $group = 'media-gallery-' . $postKey . '-' . (++self::$instance);
        $title = (string) ($post['data']['seo']['title'] ?? '');

        $queues = [];
        foreach ($galleries as $g) {
            $label = trim((string) ($labels[$g['name']] ?? '')) ?: $g['label'];
            $queues[] = ['key' => $g['key'], 'label' => $label, 'images' => $g['images']];
        }
        $tiles = [];
        if (($data['interleave'] ?? true) !== false) {
            for ($round = 0, $left = true; $left; $round++) {
                $left = false;
                foreach ($queues as $q) {
                    if (!isset($q['images'][$round])) continue;
                    $left = true;
                    $tiles[] = ['key' => $q['key'], 'label' => $q['label'], 'src' => $q['images'][$round], 'n' => $round + 1, 'of' => count($q['images'])];
                }
            }
        } else {
            foreach ($queues as $q) {
                foreach ($q['images'] as $n => $src) $tiles[] = ['key' => $q['key'], 'label' => $q['label'], 'src' => $src, 'n' => $n + 1, 'of' => count($q['images'])];
            }
        }
        foreach ($tiles as &$tile) $tile['logo'] = stripos($tile['key'] . '/' . basename($tile['src']), 'logo') !== false;
        unset($tile);
        if (($data['logo_first'] ?? false) === true) {
            usort($tiles, fn ($a, $b) => (int) $b['logo'] <=> (int) $a['logo']);
        }
        $total = count($tiles);

        $html = '';
        foreach ($tiles as $i => $tile) {
            $img = (string) PlatformComponentRenderer::render('image', [
                'src' => $tile['src'],
                'alt' => trim($title . ' - ' . $tile['label'] . ' ' . $tile['n']),
                'lazy' => true,
            ]);
            if (trim($img) === '') continue;
            $hidden = $pageSize > 0 && $i >= $pageSize;
            $html .= '<li class="media-gallery-filterable-tile' . ($featured && $i === 0 ? ' is-featured' : '') . ($tile['logo'] ? ' is-logo' : '') . '" data-gallery="' . self::e($tile['key']) . '"'
                . ' data-popup="true" data-popup-group="' . self::e($group . ($hidden ? '-hidden' : '')) . '"' . ($hidden ? ' hidden' : '') . '>'
                . $img
                . '<span class="media-gallery-filterable-veil"><span class="text-label">' . self::e($tile['label']) . '</span>'
                . '<b class="text-label text-label-sm">' . str_pad((string) $tile['n'], 2, '0', STR_PAD_LEFT) . ' / ' . str_pad((string) $tile['of'], 2, '0', STR_PAD_LEFT) . '</b></span>'
                . '</li>';
        }
        if ($html === '') return '';

        $filters = '';
        if (($data['filters'] ?? true) !== false && count($queues) > 1) {
            $filters = '<div class="media-gallery-filterable-filters" role="toolbar" aria-label="' . self::e(utility_project_media::text('gallery_filters', 'Filter the gallery')) . '">'
                . self::chip('all', utility_project_media::text('gallery_all', 'All'), $total, true);
            foreach ($queues as $q) $filters .= self::chip($q['key'], $q['label'], count($q['images']), false);
            $filters .= '</div>';
        }

        $shown = $pageSize > 0 ? min($pageSize, $total) : $total;
        $foot = '<div class="media-gallery-filterable-foot"' . ($shown >= $total ? ' hidden' : '') . '>'
            . '<p class="media-gallery-filterable-status text-label" data-gallery-status>' . self::e(self::status($shown, $total)) . '</p>'
            . '<div class="media-gallery-filterable-actions">'
            . '<button type="button" class="btn btn-ghost btn-sm" data-gallery-more>' . self::icon('chevron-down') . self::e(utility_project_media::text('gallery_more', 'Load more')) . '</button>'
            . '<button type="button" class="btn btn-accent btn-sm" data-gallery-all>' . self::icon('grid') . self::e(utility_project_media::text('gallery_show_all', 'Show all')) . '</button>'
            . '</div></div>';

        $vars = ['{total}' => (string) $total, '{galleries}' => (string) count($queues)];
        $head = (string) PlatformComponentRenderer::render('section_head', [
            'kicker' => strtr(self::opt($data, 'kicker', utility_project_media::text('gallery_kicker', 'Photo content')), $vars),
            'title' => strtr(self::opt($data, 'title', utility_project_media::text('gallery_title', 'The <em>gallery</em>.')), $vars),
            'text' => strtr(self::opt($data, 'text', count($queues) > 1
                ? utility_project_media::text('gallery_text', '{total} images in {galleries} collections. Filter the archive, then open any frame.')
                : utility_project_media::text('gallery_text_single', '{total} images. Open any frame to see it full size.')), $vars),
            'count' => strtr(self::opt($data, 'count', utility_project_media::text('gallery_count', '{total} images')), $vars),
        ]);

        $gallery = '<div class="media-gallery-filterable" data-media-gallery data-page-size="' . $pageSize . '"' . ($featured ? '' : ' data-featured="false"') . ' data-group="' . self::e($group) . '"'
            . ' data-status-text="' . self::e(utility_project_media::text('gallery_status', 'Showing {shown} of {total} images')) . '">'
            . $filters
            . '<ul class="media-gallery-filterable-grid">' . $html . '</ul>'
            . $foot
            . '</div>';

        return (string) PlatformComponentRenderer::render('section', [
            'id' => array_key_exists('id', $data) ? trim((string) $data['id']) : 'gallery',
            'class' => trim('project-section media-gallery-filterable-section ' . self::str($data['section_class'] ?? '')),
            'variant' => $data['variant'] ?? 'panel',
            'children' => $head . $gallery,
        ]);
    }

    private static function chip(string $key, string $label, int $count, bool $active): string
    {
        return '<button type="button" class="media-gallery-filterable-chip" data-gallery-filter="' . self::e($key) . '" aria-pressed="' . ($active ? 'true' : 'false') . '">'
            . self::e($label) . ' <b>' . $count . '</b></button>';
    }

    private static function status(int $shown, int $total): string
    {
        return strtr(utility_project_media::text('gallery_status', 'Showing {shown} of {total} images'), ['{shown}' => (string) $shown, '{total}' => (string) $total]);
    }

    private static function opt(array $data, string $key, string $default): string
    {
        return array_key_exists($key, $data) ? trim((string) $data[$key]) : $default;
    }

    private static function icon(string $name): string
    {
        return (string) PlatformComponentRenderer::render('svg', ['icon' => $name, 'class' => 'media-gallery-filterable-icon']);
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
