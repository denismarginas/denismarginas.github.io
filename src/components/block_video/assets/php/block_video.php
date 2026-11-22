<?php

class block_video
{
    private const IMG_DIR = 'src/content/img/';

    private static ?array $items = null;

    public static function render(array $data = []): string
    {
        $src = trim(str_replace('\\', '/', (string) ($data['src'] ?? $data['video'] ?? '')));
        if ($src === '') return '';

        $item = self::find($src);

        $thumbnail = self::item_thumbnail($src);
        if ($thumbnail === '') $thumbnail = trim((string) ($data['thumbnail_bg'] ?? ''));

        $color = trim((string) ($data['video_primary_color'] ?? ''));
        if ($color === '' && $item) $color = trim((string) ($item['settings']['appearance']['colors']['primary'] ?? ''));

        return (string) PlatformComponentRenderer::render('video', array_filter([
            'src' => $src,
            'thumbnail_bg' => $thumbnail,
            'video_primary_color' => $color,
            'thumbnail_content' => (string) ($data['thumbnail_content'] ?? ''),
        ], fn ($v) => $v !== ''));
    }

    public static function item_thumbnail(string $src): string
    {
        $item = self::find($src);
        return $item ? self::thumbnail((string) ($item['data']['media']['video']['video_thumbnail'] ?? '')) : '';
    }

    public static function item_thumbnail_path(string $src): string
    {
        $item = self::find($src);
        $path = $item ? self::thumbnail_path((string) ($item['data']['media']['video']['video_thumbnail'] ?? '')) : '';
        return PlatformUrlService::is_external_url($path) ? '' : $path;
    }

    public static function find(string $src): ?array
    {
        $src = strtolower(ltrim($src, '/'));
        $name = basename($src);
        $byName = null;

        foreach (self::items() as $item) {
            $video = strtolower(ltrim(str_replace('\\', '/', (string) ($item['data']['media']['video']['video'] ?? '')), '/'));
            if ($video === '') continue;
            if ($src === $video || str_ends_with($src, '/' . $video)) return $item;
            if ($byName === null && basename($video) === $name) $byName = $item;
        }

        return $byName;
    }

    private static function items(): array
    {
        if (self::$items === null) {
            $items = PlatformDataService::get_collection('video') ?? [];
            self::$items = array_values(array_filter($items, fn ($i) => is_array($i) && ($i['settings']['render'] ?? true) !== false));
        }
        return self::$items;
    }

    private static function thumbnail(string $path): string
    {
        $path = self::thumbnail_path($path);
        if ($path === '' || PlatformUrlService::is_external_url($path)) return $path;

        return rtrim(PlatformPathService::asset_relative_prefix(), '/') . '/' . $path;
    }

    private static function thumbnail_path(string $path): string
    {
        $path = trim(str_replace('\\', '/', $path));
        if ($path === '') return '';
        if (PlatformUrlService::is_external_url($path)) return $path;

        $path = str_starts_with(ltrim($path, '/'), 'src/') ? ltrim($path, '/') : self::IMG_DIR . ltrim($path, '/');
        $root = defined('ENGINE_PROJECT_ROOT') ? rtrim(ENGINE_PROJECT_ROOT, '/\\') . '/' : '';

        return is_file($root . $path) ? $path : '';
    }
}
