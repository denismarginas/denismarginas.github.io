<?php

class PlatformUrlService
{
    public static function get_url_path(): string
    {
        return $GLOBALS['url_path'] ?? '';
    }

    public static function get_full_url(string $path = ''): string
    {
        $base = self::get_url_path();
        if (!empty($path)) {
            return rtrim($base, '/') . '/' . ltrim($path, '/');
        }
        return $base;
    }

    public static function get_image_path(string $relative_path): string
    {
        return self::get_full_url('content/img/' . ltrim($relative_path, '/'));
    }

    public static function get_video_path(string $relative_path): string
    {
        return self::get_full_url('content/vid/' . ltrim($relative_path, '/'));
    }

    public static function is_external_url(string $url): bool
    {
        return str_starts_with($url, 'http://')
            || str_starts_with($url, 'https://')
            || str_starts_with($url, '//')
            || str_starts_with($url, '/');
    }

    public static function add_https(string $url): string
    {
        $url = trim($url);
        if (empty($url)) return '';
        if (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://') && !str_starts_with($url, '//')) {
            return 'https://' . $url;
        }
        if (str_starts_with($url, '//')) {
            return 'https:' . $url;
        }
        return $url;
    }

    public static function remove_https(string $url): string
    {
        $url = trim($url);
        $url = preg_replace('/^https?:\/\//i', '', $url);
        return rtrim($url, '/');
    }
}
