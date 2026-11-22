<?php
trait PlatformDataServiceCore
{
    public static function init(): void
    {
        self::$data_dir = PlatformConfig::get('data_dir');
    }

    public static function get_data(string $name, string $sub_dir = 'data'): ?array
    {
        $lang = self::get_language();
        $cache_key = $sub_dir . '/' . $lang . '/' . $name;

        if (isset(self::$cache[$cache_key])) {
            return self::$cache[$cache_key];
        }

        $base_dir = PlatformConfig::get('data_dir');
        if ($sub_dir !== 'data') {
            $base_dir = dirname($base_dir) . '/' . $sub_dir;
        }

        $file_path = self::resolve_data_file($base_dir, $name, $sub_dir, $lang);
        if (!file_exists($file_path)) {
            return null;
        }

        $content = file_get_contents($file_path);
        if (strncmp($content, "\xEF\xBB\xBF", 3) === 0) {
            $content = substr($content, 3);
        }
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        self::$cache[$cache_key] = $data;
        return $data;
    }

    private static function resolve_data_file(string $base_dir, string $name, string $sub_dir, string $lang): string
    {
        $candidates = ['data_' . $name . '.json', $name . '.json', 'data-' . $name . '.json'];

        if ($sub_dir === 'data' && $lang !== '') {
            foreach (array_unique([$lang, self::default_language()]) as $langDir) {
                $found = self::find_candidate($base_dir . '/' . $langDir, $candidates);
                if ($found !== '') return $found;
            }
        }

        $found = self::find_candidate($base_dir, $candidates);
        return $found !== '' ? $found : $base_dir . '/data_' . $name . '.json';
    }

    private static function find_candidate(string $dir, array $candidates): string
    {
        foreach ($candidates as $candidate) {
            if (file_exists($dir . '/' . $candidate)) return $dir . '/' . $candidate;
        }
        if (!is_dir($dir)) return '';
        foreach (glob($dir . '/*', GLOB_ONLYDIR) as $sub_dir) {
            $found = self::find_candidate($sub_dir, $candidates);
            if ($found !== '') return $found;
        }
        return '';
    }

    public static function default_language(): string
    {
        $path = PlatformConfig::get('data_dir') . '/settings/data_settings_languages.json';
        if (!file_exists($path)) return 'en';
        $data = json_decode(file_get_contents($path), true);
        return $data['default'] ?? 'en';
    }

    public static function get_language(): string
    {
        if (self::$language === null) self::$language = self::default_language();
        return self::$language;
    }

    public static function set_language(?string $lang): void
    {
        self::$language = $lang;
        self::clear_cache();
    }

    public static function get_index_data(string $name): ?array { return self::get_data($name, 'index'); }

    public static function get_global_settings(): ?array
    {
        return array_merge(
            self::get_data('settings_site') ?? [],
            self::get_data('settings_routing') ?? [],
            self::get_data('settings_languages') ?? [],
            self::get_data('settings_seo') ?? []
        );
    }

    public static function get_personal_data(): ?array { return self::get_data('content'); }

    public static function get_url_path(): string { return $GLOBALS['url_path'] ?? ''; }

    public static function value_at(array $data, array $segments): ?string
    {
        $value = $data;
        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return null;
            }
            $value = $value[$segment];
        }
        return is_string($value) ? $value : null;
    }

    public static function resolve_path_string(array $data, string $path): ?string
    {
        if ($path === '') return null;
        return self::value_at($data, explode('.', $path));
    }

    public static function clear_cache(): void { self::$cache = []; }
}
