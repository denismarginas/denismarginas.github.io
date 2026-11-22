<?php

class PlatformPathService
{
    public static function dist_relative_prefix(): string
    {
        if (($GLOBALS['render_target'] ?? '') !== 'dist') {
            return PlatformConfig::getUrlPath();
        }
        $current = $GLOBALS['dist_rel_path'] ?? '';
        $dir = $current !== '' ? dirname($current) : '.';
        if ($dir === '.' || $dir === '') {
            return '';
        }
        return str_repeat('../', substr_count($dir, '/') + 1);
    }

    public static function asset_relative_prefix(): string
    {
        if (($GLOBALS['render_target'] ?? '') !== 'dist') {
            return PlatformConfig::getUrlPath();
        }
        $current = $GLOBALS['dist_rel_path'] ?? '';
        $dir = $current !== '' ? dirname($current) : '.';
        $depth = ($dir === '.' || $dir === '') ? 0 : substr_count($dir, '/') + 1;
        return str_repeat('../', $depth + 1);
    }

    public static function languages(): array
    {
        $data = PlatformDataService::get_data('settings_languages') ?? [];
        $list = [];
        foreach (($data['list'] ?? []) as $item) {
            $iso = strtolower(trim((string) ($item['iso'] ?? '')));
            if ($iso === '' || !preg_match('/^[a-z]{2,3}(-[a-z0-9]+)?$/', $iso)) continue;
            $list[$iso] = trim((string) ($item['text'] ?? '')) ?: strtoupper($iso);
        }
        $default = PlatformDataService::default_language();
        if (!isset($list[$default])) $list = [$default => strtoupper($default)] + $list;
        return $list;
    }

    public static function language_modes(): array
    {
        $data = PlatformDataService::get_data('settings_languages') ?? [];
        $default = PlatformDataService::default_language();
        $modes = [];
        foreach (($data['list'] ?? []) as $item) {
            $iso = strtolower(trim((string) ($item['iso'] ?? '')));
            if ($iso === '') continue;
            $modes[$iso] = ($iso !== $default && ($item['mode'] ?? '') === 'frontend') ? 'frontend' : 'static';
        }
        $modes[$default] = 'static';
        return $modes;
    }

    public static function language_mode(?string $lang = null): string
    {
        $lang = $lang ?? PlatformDataService::get_language();
        return self::language_modes()[$lang] ?? 'static';
    }

    public static function is_frontend_language(?string $lang = null): bool
    {
        return self::language_mode($lang) === 'frontend';
    }

    public static function frontend_languages(): array
    {
        return array_values(array_intersect(array_keys(self::languages()), array_keys(array_filter(self::language_modes(), fn($m) => $m === 'frontend'))));
    }

    public static function static_languages(): array
    {
        return array_values(array_diff(array_keys(self::languages()), self::frontend_languages()));
    }

    public static function lang_prefix(?string $lang = null): string
    {
        $lang = $lang ?? PlatformDataService::get_language();
        return ($lang === '' || $lang === PlatformDataService::default_language()) ? '' : $lang . '/';
    }

    public static function dist_lang_prefix(?string $lang = null): string
    {
        $lang = $lang ?? PlatformDataService::get_language();
        return self::is_frontend_language($lang) ? '' : self::lang_prefix($lang);
    }

    public static function post_path(string $slug, ?string $lang = null): string
    {
        $folder = trim(self::get_dist_folder($slug), '/');
        return self::dist_lang_prefix($lang) . ($folder !== '' ? $folder . '/' : '') . ltrim($slug, '/') . self::extension();
    }

    public static function post_url(string $slug, ?string $lang = null): string
    {
        $base = rtrim((string) (PlatformDataService::get_data('settings_routing')['url'] ?? ''), '/');
        return $base === '' ? '' : $base . '/' . self::post_path($slug, $lang);
    }

    public static function post_link(string $slug, ?string $lang = null): string
    {
        $lang = $lang ?? PlatformDataService::get_language();

        if (($GLOBALS['render_target'] ?? '') === 'dist') {
            return self::dist_relative_prefix() . self::post_path($slug, $lang);
        }

        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        if (str_contains($requestUri, '/dev/platform/preview/')) {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? '127.0.0.1';
            $langQuery = self::lang_prefix($lang) !== '' ? '&lang=' . urlencode($lang) : '';
            return $scheme . '://' . $host . '/dev/platform/preview/?_id=' . urlencode($slug) . $langQuery;
        }

        $globalData = PlatformDataService::get_data('settings_routing');
        $baseUrl = rtrim($globalData['url'] ?? '', '/');
        return $baseUrl . '/' . self::dist_lang_prefix($lang) . ltrim($slug, '/') . self::extension();
    }

    private static function extension(): string
    {
        $globalData = PlatformDataService::get_data('settings_routing');
        return $globalData['routing']['extension'] ?? $globalData['page_slug_extension'] ?? '.html';
    }

    private static function get_dist_folder(string $slug): string
    {
        $types = self::get_content_types();
        foreach (($types['post'] ?? []) as $type => $config) {
            $posts = PlatformDataService::get_all_posts_from_file($type);
            if ($posts) {
                foreach ($posts as $post) {
                    if (($post['_id'] ?? '') === $slug) {
                        return $config['root'] ?? '';
                    }
                }
            }
        }
        foreach (($types['collection'] ?? []) as $type => $config) {
            if (($config['routable'] ?? false) !== true) continue;
            $entries = PlatformDataService::get_collection($type);
            if ($entries) {
                foreach ($entries as $entry) {
                    if (($entry['_id'] ?? '') === $slug) {
                        return $config['root'] ?? '';
                    }
                }
            }
        }
        foreach (($types['taxonomy'] ?? []) as $type => $config) {
            $terms = PlatformDataService::get_data('taxonomy_' . $type);
            if ($terms) {
                foreach ($terms as $term) {
                    if (($term['_id'] ?? '') === $slug) {
                        return $config['root'] ?? '';
                    }
                }
            }
        }
        return '';
    }

    private static function get_content_types(): array
    {
        $data = PlatformDataService::get_data('settings_types');
        return $data ?? [];
    }

    public static function front_page_link(): string
    {
        $mappings = PlatformConfig::getRoutingMappings();
        $frontId = $mappings['front_post_id'] ?? 'home';
        return self::post_link($frontId);
    }

    public static function load_php_dir(string $dir): void
    {
        $dir = rtrim($dir, '/\\');
        foreach (glob($dir . '/*.php') as $filename) {
            require_once $filename;
        }
        foreach (glob($dir . '/*', GLOB_ONLYDIR) as $subdir) {
            self::load_php_dir($subdir);
        }
    }
}
