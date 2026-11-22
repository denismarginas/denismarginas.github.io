<?php

class utility_post_thumbnail
{
    private const PROJECTS_DIR = 'src/content/img/projects/';
    private const REFERENCE = '@thumbnail';

    public static function get(array $postData, string $key = 'default'): string
    {
        $media = self::media($postData);
        $path = self::existing(self::raw($media));
        if ($path === '') $path = self::existing(self::generated($media));
        return $path;
    }

    public static function is_reference(string $value): bool
    {
        return str_starts_with(strtolower(trim($value)), self::REFERENCE);
    }

    public static function reference(string $value, array $postData): string
    {
        return self::is_reference($value) ? self::get($postData) : '';
    }

    private static function raw(array $media): string
    {
        $thumb = $media['thumbnail'] ?? null;
        if (is_array($thumb) && is_string($thumb['default'] ?? null) && trim($thumb['default']) !== '') {
            return trim($thumb['default']);
        }
        if (is_string($thumb) && trim($thumb) !== '') return trim($thumb);
        if (is_string($media['thumbnail_img'] ?? null)) return trim($media['thumbnail_img']);
        return '';
    }

    private static function generated(array $media): string
    {
        $dir = trim(str_replace('\\', '/', (string) ($media['path'] ?? '')), '/');
        return $dir === '' ? '' : $dir . '/thumbnail/thumbnail.webp';
    }

    private static function existing(string $relative): string
    {
        $relative = trim(str_replace('\\', '/', $relative), '/');
        if ($relative === '' || str_contains($relative, '..')) return '';

        $path = str_starts_with($relative, 'src/') ? $relative : self::PROJECTS_DIR . $relative;
        $root = defined('ENGINE_PROJECT_ROOT') ? rtrim(ENGINE_PROJECT_ROOT, '/\\') . '/' : '';
        return is_file($root . $path) ? $path : '';
    }

    private static function media(array $postData): array
    {
        if (isset($postData['data']) && is_array($postData['data'])) $postData = $postData['data'];
        return is_array($postData['media'] ?? null) ? $postData['media'] : [];
    }
}
