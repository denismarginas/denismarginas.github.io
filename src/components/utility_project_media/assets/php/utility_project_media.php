<?php

require_once __DIR__ . '/../../../project_media_gallery/assets/php/parts/project_media_gallery_sources.php';
require_once __DIR__ . '/../../../utility_project_web_screens/assets/php/utility_project_web_screens.php';
require_once __DIR__ . '/../../../utility_post_thumbnail/assets/php/utility_post_thumbnail.php';

class utility_project_media
{
    public const IMG_DIR = 'src/content/img/projects/';
    public const VID_DIR = 'src/content/vid/projects/';
    private const IMAGE_EXTENSIONS = ['webp', 'jpg', 'jpeg', 'png', 'gif', 'avif'];
    private const VIDEO_EXTENSIONS = ['webm', 'mp4', 'ogg', 'ogv', 'm4v', 'mov'];

    public static function render(array $data = []): string
    {
        return '';
    }

    public static function value(array $data = []): array
    {
        $post = is_array($data['post_current_data'] ?? null) ? $data['post_current_data'] : [];
        return [
            'galleries' => self::galleries($post, (string) ($data['dir'] ?? 'media')),
            'videos' => self::videos($post),
        ];
    }

    public static function path(array $post): string
    {
        $path = trim(str_replace('\\', '/', (string) ($post['data']['media']['path'] ?? '')), '/');
        return ($path === '' || str_contains($path, '..')) ? '' : $path;
    }

    public static function screens(array $post, string $device = 'desktop'): array
    {
        return utility_project_web_screens::all(self::path($post), $device);
    }

    public static function galleries(array $post, string $dir = 'media', array $exclude = []): array
    {
        $path = self::path($post);
        if ($path === '') return [];

        $dir = trim(str_replace('\\', '/', $dir), '/');
        $base = self::IMG_DIR . $path . ($dir !== '' ? '/' . $dir : '');
        $sources = project_media_gallery_sources::find($base, 'images', 'desktop', 'phone', true, $exclude);

        $galleries = [];
        foreach ($sources as $i => $source) {
            $images = array_values(array_filter($source['images'], fn ($img) => in_array(strtolower(pathinfo($img, PATHINFO_EXTENSION)), self::IMAGE_EXTENSIONS, true)));
            if ($images === []) continue;
            $name = (string) $source['name'];
            $galleries[] = [
                'key' => self::key($name) ?: 'gallery-' . ($i + 1),
                'name' => $name,
                'label' => self::label($name),
                'images' => $images,
            ];
        }
        return $galleries;
    }

    public static function images(array $post, string $dir = 'media'): array
    {
        $images = [];
        foreach (self::galleries($post, $dir) as $gallery) $images = array_merge($images, $gallery['images']);
        return $images;
    }

    public static function videos(array $post): array
    {
        $path = self::path($post);
        if ($path === '') return [];

        $files = [];
        self::collect(self::VID_DIR . $path, self::VIDEO_EXTENSIONS, $files);

        $videos = [];
        foreach ($files as $src) $videos[] = self::video($src);
        return $videos;
    }

    public static function video(string $src): array
    {
        $item = class_exists('block_video') ? block_video::find($src) : null;
        $d = is_array($item['data'] ?? null) ? $item['data'] : [];
        $media = is_array($d['media']['video'] ?? null) ? $d['media']['video'] : [];

        $title = trim((string) ($media['video_data']['title'] ?? ''));
        if ($title === '') $title = self::label(pathinfo($src, PATHINFO_FILENAME));

        return [
            'src' => $src,
            'item' => $item,
            'id' => (string) ($item['_id'] ?? ''),
            'title' => $title,
            'thumbnail' => self::existing_img((string) ($media['video_thumbnail'] ?? '')),
            'year' => self::year((string) ($d['date']['publish'] ?? '')),
            'publish' => trim((string) ($d['date']['publish'] ?? '')),
            'color' => trim((string) ($item['settings']['appearance']['colors']['primary'] ?? '')),
        ];
    }

    public static function video_by_id(string $id): ?array
    {
        foreach ((array) (PlatformDataService::get_collection('video') ?? []) as $item) {
            if (!is_array($item) || (string) ($item['_id'] ?? '') !== $id) continue;
            $src = trim((string) ($item['data']['media']['video']['video'] ?? ''));
            if ($src === '') return null;
            $src = str_starts_with($src, 'src/') ? $src : 'src/content/vid/' . ltrim($src, '/');
            return is_file(self::absolute($src)) ? self::video($src) : null;
        }
        return null;
    }

    public static function logo(array $post): array
    {
        $logo = is_array($post['data']['media']['logo'] ?? null) ? $post['data']['media']['logo'] : [];
        $img = trim((string) ($logo['img'] ?? ''));
        if ($img !== '') {
            $img = str_starts_with($img, 'src/') ? $img : self::IMG_DIR . ltrim($img, '/');
            if (!is_file(self::absolute($img))) $img = '';
        }
        return ['img' => $img, 'svg' => trim((string) ($logo['svg'] ?? ''))];
    }

    public static function thumbnail(array $post): string
    {
        return utility_post_thumbnail::get(is_array($post['data'] ?? null) ? $post['data'] : []);
    }

    public static function text(string $key, string $default): string
    {
        static $cache = [];
        $lang = PlatformDataService::get_language();
        $texts = $cache[$lang] ?? null;
        if ($texts === null) {
            $content = PlatformDataService::get_data('content_post_projects') ?? [];
            $texts = is_array($content['project_page'] ?? null) ? array_filter($content['project_page'], 'is_string') : [];
            $fields = is_array($content['fields'] ?? null) ? $content['fields'] : [];
            foreach (['web_project_status_done' => 'status_done', 'web_project_status_undone' => 'status_undone'] as $from => $to) {
                if (!isset($texts[$to]) && is_string($fields[$from] ?? null)) $texts[$to] = $fields[$from];
            }
            $cache[$lang] = $texts;
        }
        $value = trim((string) ($texts[$key] ?? ''));
        return $value !== '' ? $value : PlatformTextService::ui($key, $default);
    }

    public static function size(string $path): ?array
    {
        $size = @getimagesize(self::absolute($path));
        return ($size && $size[0] > 0 && $size[1] > 0) ? [(int) $size[0], (int) $size[1]] : null;
    }

    public static function type(string $type): string
    {
        $map = self::map('types');
        $key = strtolower(trim($type));
        return trim((string) ($map[$key] ?? '')) !== '' ? (string) $map[$key] : self::label($type);
    }

    public static function tag(string $tag): string
    {
        $map = self::map('tags');
        $key = strtolower(trim($tag));
        return trim((string) ($map[$key] ?? '')) !== '' ? (string) $map[$key] : self::label($tag);
    }

    public static function category(string $name): string
    {
        static $cache = [];
        $lang = PlatformDataService::get_language();
        if (!isset($cache[$lang])) {
            $cache[$lang] = [];
            foreach ((array) (PlatformDataService::get_data('taxonomy_category') ?? []) as $term) {
                $title = is_array($term) ? trim((string) ($term['data']['seo']['title'] ?? '')) : '';
                if ($title !== '') $cache[$lang][(string) ($term['_id'] ?? '')] = $title;
            }
        }
        return $cache[$lang][self::key($name)] ?? $name;
    }

    private static function map(string $name): array
    {
        static $cache = [];
        $lang = PlatformDataService::get_language();
        if (!isset($cache[$lang])) {
            $content = PlatformDataService::get_data('content_post_projects') ?? [];
            $cache[$lang] = [];
            foreach (['types', 'tags'] as $key) {
                $cache[$lang][$key] = is_array($content[$key] ?? null) ? array_change_key_case(array_filter($content[$key], 'is_string')) : [];
            }
        }
        return $cache[$lang][$name] ?? [];
    }

    public static function label(string $name): string
    {
        $text = trim(preg_replace('/\s+/', ' ', str_replace(['_', '-'], ' ', $name)));
        return $text === '' ? '' : mb_strtoupper(mb_substr($text, 0, 1)) . mb_substr($text, 1);
    }

    public static function key(string $name): string
    {
        return trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($name)), '-');
    }

    public static function url(string $path): string
    {
        if ($path === '' || PlatformUrlService::is_external_url($path)) return $path;
        return rtrim(PlatformPathService::asset_relative_prefix(), '/') . '/' . ltrim($path, '/');
    }

    public static function absolute(string $path): string
    {
        $root = defined('ENGINE_PROJECT_ROOT') ? rtrim(ENGINE_PROJECT_ROOT, '/\\') . '/' : '';
        return rtrim($root . ltrim($path, '/'), '/');
    }

    public static function year(string $date): string
    {
        return preg_match('/\b(19|20)\d{2}\b/', $date, $m) ? $m[0] : '';
    }

    private static function existing_img(string $path): string
    {
        $path = trim(str_replace('\\', '/', $path));
        if ($path === '') return '';
        if (PlatformUrlService::is_external_url($path)) return $path;
        $path = str_starts_with(ltrim($path, '/'), 'src/') ? ltrim($path, '/') : 'src/content/img/' . ltrim($path, '/');
        return is_file(self::absolute($path)) ? $path : '';
    }

    private static function collect(string $dir, array $extensions, array &$out): void
    {
        $abs = self::absolute($dir);
        if (!is_dir($abs)) return;

        $names = array_values(array_filter(scandir($abs) ?: [], fn ($n) => $n !== '' && $n[0] !== '.'));
        natcasesort($names);
        $folders = [];
        foreach (utility_project_web_screens::sort($names) as $name) {
            if (is_dir($abs . '/' . $name)) { $folders[] = $name; continue; }
            if (in_array(strtolower(pathinfo($name, PATHINFO_EXTENSION)), $extensions, true)) $out[] = $dir . '/' . $name;
        }
        natcasesort($folders);
        foreach ($folders as $folder) self::collect($dir . '/' . $folder, $extensions, $out);
    }
}
