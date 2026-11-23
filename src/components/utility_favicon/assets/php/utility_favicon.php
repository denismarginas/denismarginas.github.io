<?php

class utility_favicon
{
    private const TYPES = [
        'ico' => 'image/x-icon',
        'png' => 'image/png',
        'svg' => 'image/svg+xml',
        'webp' => 'image/webp',
        'gif' => 'image/gif',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
    ];

    public static function value(array $data = []): string
    {
        $path = trim((string) ($data['img_path'] ?? ''));
        if ($path === '') {
            $site = PlatformDataService::get_data('settings_site') ?? [];
            $path = trim((string) ($site['site_logo_favicon'] ?? $site['favicon'] ?? ''));
        }
        if ($path === '') return '';

        return preg_match('#^(https?:)?//#i', $path) || str_starts_with($path, 'data:')
            ? $path
            : PlatformPathService::asset_relative_prefix() . ltrim($path, '/');
    }

    public static function render(array $data = []): string
    {
        $url = self::value($data);
        if ($url === '') return '';

        $type = trim((string) ($data['type'] ?? ''));
        if ($type === '') {
            $ext = strtolower(pathinfo((string) parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
            $type = self::TYPES[$ext] ?? '';
        }

        $typeAttr = $type !== '' ? ' type="' . htmlspecialchars($type, ENT_QUOTES, 'UTF-8') . '"' : '';
        return '<link rel="icon"' . $typeAttr . ' href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '">';
    }
}
