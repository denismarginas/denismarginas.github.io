<?php

require_once __DIR__ . '/parts/project_media_gallery_sources.php';

class project_media_gallery
{
    private const HTML_DIR = __DIR__ . '/../html/';
    private const TYPES = ['devices', 'images', 'videos'];

    private static int $instance = 0;

    public static function render(array $data = []): string
    {
        $post = is_array($data['post_current_data'] ?? null) ? $data['post_current_data'] : [];
        $mediaPath = trim((string) ($post['data']['media']['path'] ?? ''), '/');
        if ($mediaPath === '') return '';

        $type = strtolower(trim((string) ($data['source_gallery_type'] ?? 'devices')));
        if (!in_array($type, self::TYPES, true)) $type = 'devices';

        $baseDir = $type === 'videos'
            ? project_media_gallery_sources::VIDEOS_DIR . $mediaPath . '/' . trim((string) ($data['source_gallery_dir_devices'] ?? ''), '/')
            : project_media_gallery_sources::PROJECTS_DIR . $mediaPath . '/' . trim((string) ($data['source_gallery_dir_devices'] ?? 'web'), '/');
        $webDir = trim((string) ($data['source_gallery_dir_web'] ?? 'desktop'), '/');
        $phoneDir = trim((string) ($data['source_gallery_dir_phone'] ?? 'phone'), '/');

        $sources = project_media_gallery_sources::find($baseDir, $type, $webDir, $phoneDir, self::flag($data, 'auto_search_galleries_dir', false), self::strings($data, 'exclude'));
        if (empty($sources)) return '';

        if (!self::flag($data, 'multiple_galleries', false)) {
            $sources = [self::merge($sources)];
        }

        $gridMax = (int) ($data['grid_max'] ?? 4);
        $balancing = self::flag($data, 'column_balancing', false);
        $overlay = self::flag($data, 'overlay_noise', $type === 'devices')
            ? (string) PlatformComponentRenderer::render('overlay_noise', [])
            : '';
        $deviceParams = self::params($data, 'device_params');
        $postKey = preg_replace('/^project-/', '', preg_replace('/[^a-z0-9\-]/', '', strtolower((string) ($post['_id'] ?? 'post'))));
        $group = 'project-media-gallery-' . $postKey . '-' . (++self::$instance);

        $lists = '';
        foreach ($sources as $i => $source) {
            $items = match ($type) {
                'devices' => self::device_items($source, $post, $deviceParams, $group, $overlay),
                'videos' => self::video_items($source, $post, self::video_thumbnail($data, $post), $overlay),
                default => self::image_items($source, $group, $overlay),
            };
            if ($items['count'] === 0) continue;

            $lists .= PlatformTemplateRenderer::render(self::HTML_DIR . 'parts/list.html', [
                'grid_attrs' => $balancing
                    ? (string) PlatformComponentRenderer::value('utility_grid_column_balancing', ['count' => $items['count']])
                    : (string) PlatformComponentRenderer::value('utility_grid_listing', ['count' => $items['count'], 'max' => $gridMax]),
                'source' => htmlspecialchars($source['name'], ENT_QUOTES, 'UTF-8'),
                'items' => $items['html'],
            ]);
        }
        if ($lists === '') return '';

        $id = trim((string) ($data['id'] ?? ''));
        $extra = trim((string) ($data['class'] ?? ''));

        return PlatformTemplateRenderer::render(self::HTML_DIR . 'template.html', [
            'type' => $type,
            'extra_class' => $extra !== '' ? ' ' . htmlspecialchars($extra, ENT_QUOTES, 'UTF-8') : '',
            'id_attr' => $id !== '' ? ' id="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '"' : '',
            'style_attr' => self::color_style($post),
            'lists' => $lists,
        ]);
    }

    public static function color_style(array $post): string
    {
        $colors = $post['settings']['appearance']['colors'] ?? [];
        if (!is_array($colors)) return '';

        $vars = '';
        foreach ($colors as $name => $value) {
            $name = preg_replace('/[^a-z0-9\-]/', '', str_replace('_', '-', strtolower((string) $name)));
            $value = trim((string) $value);
            if ($name === '' || $value === '' || preg_match('/[;{}<>"]/', $value)) continue;
            $vars .= '--post-color-' . $name . ': ' . $value . '; ';
        }

        return $vars === '' ? '' : ' style="' . htmlspecialchars(trim($vars), ENT_QUOTES, 'UTF-8') . '"';
    }

    public static function flag(array $data, string $key, bool $default): bool
    {
        if (!array_key_exists($key, $data)) return $default;
        $v = $data[$key];
        if (is_bool($v)) return $v;
        return !in_array(strtolower(trim((string) $v)), ['false', '0', 'no', 'off', ''], true);
    }

    public static function strings(array $data, string $key): array
    {
        $value = $data[$key] ?? [];
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : explode(',', $value);
        }
        if (!is_array($value)) return [];
        return array_values(array_filter(array_map(fn ($v) => is_scalar($v) ? trim((string) $v) : '', $value), 'strlen'));
    }

    public static function params(array $data, string $key): array
    {
        $value = $data[$key] ?? [];
        if (is_string($value)) $value = json_decode($value, true);
        return is_array($value) ? $value : [];
    }

    private static function device_items(array $source, array $post, array $deviceParams, string $group, string $overlay): array
    {
        $html = '';
        $count = 0;
        foreach (['desktop', 'phone'] as $device) {
            foreach ($source[$device] as $image) {
                $visual = (string) PlatformComponentRenderer::render('card_post_project_visual_devices_website', array_merge($deviceParams, [
                    'post_current_data' => $post,
                    'render_media_image' => $image,
                    'render_media_device_type' => $device,
                    'link' => false,
                ]));
                if (trim($visual) === '') continue;

                $html .= self::item($device, $group, $overlay . $visual);
                $count++;
            }
        }
        return ['count' => $count, 'html' => $html];
    }

    private static function image_items(array $source, string $group, string $overlay): array
    {
        $html = '';
        $count = 0;
        foreach ($source['images'] as $image) {
            $img = (string) PlatformComponentRenderer::render('image', [
                'src' => $image,
                'alt' => 'Image: ' . pathinfo($image, PATHINFO_FILENAME),
                'class' => 'project-media-gallery-image' . (self::is_logo($image) ? ' project-media-gallery-image-logo' : ''),
                'lazy' => true,
            ]);
            if ($img === '') continue;

            $html .= self::item('image', $group, $overlay . $img);
            $count++;
        }
        return ['count' => $count, 'html' => $html];
    }

    private static function is_logo(string $image): bool
    {
        $rel = str_starts_with($image, project_media_gallery_sources::PROJECTS_DIR)
            ? substr($image, strlen(project_media_gallery_sources::PROJECTS_DIR))
            : $image;
        return stripos($rel, 'logo') !== false;
    }

    private static function video_items(array $source, array $post, string $thumbnail, string $overlay): array
    {
        $color = trim((string) ($post['settings']['appearance']['colors']['primary'] ?? ''));
        $html = '';
        $count = 0;
        foreach ($source['videos'] as $video) {
            $player = (string) PlatformComponentRenderer::render('project_block_video', array_filter([
                'post_current_data' => $post,
                'src' => $video,
                'thumbnail_bg' => $thumbnail,
                'video_primary_color' => $color,
            ], fn ($v) => $v !== ''));
            if (trim($player) === '') continue;

            $html .= self::item('video', '', $overlay . $player);
            $count++;
        }
        return ['count' => $count, 'html' => $html];
    }

    private static function video_thumbnail(array $data, array $post): string
    {
        $path = trim((string) ($data['video_thumbnail_bg'] ?? ''));
        if ($path === '') return '';

        if (PlatformUrlService::is_external_url($path)) return $path;
        if (!is_file(project_media_gallery_sources::absolute($path))) return '';

        return rtrim(PlatformPathService::asset_relative_prefix(), '/') . '/' . ltrim($path, '/');
    }

    private static function item(string $kind, string $group, string $content): string
    {
        return PlatformTemplateRenderer::render(self::HTML_DIR . 'parts/item.html', [
            'kind' => $kind,
            'item_attrs' => $group !== '' ? ' data-popup="true" data-popup-group="' . htmlspecialchars($group, ENT_QUOTES, 'UTF-8') . '"' : '',
            'content' => $content,
        ]);
    }

    private static function merge(array $sources): array
    {
        $merged = ['name' => $sources[0]['name'], 'desktop' => [], 'phone' => [], 'images' => [], 'videos' => []];
        foreach ($sources as $s) {
            $merged['desktop'] = array_merge($merged['desktop'], $s['desktop']);
            $merged['phone'] = array_merge($merged['phone'], $s['phone']);
            $merged['images'] = array_merge($merged['images'], $s['images']);
            $merged['videos'] = array_merge($merged['videos'], $s['videos']);
        }
        return $merged;
    }
}
