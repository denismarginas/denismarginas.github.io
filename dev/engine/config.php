<?php

class PlatformConfig
{
    private static array $config = [];
    private static bool $initialized = false;

    public static function init(): void
    {
        if (self::$initialized) return;

        $root_dir = defined('ENGINE_PROJECT_ROOT') ? ENGINE_PROJECT_ROOT : dirname(__DIR__, 2);

        $themeConf = ['root' => 'src/components/theme', 'assets' => 'assets', 'compiled_assets' => 'assets_compiled', 'default_mode' => 'light', 'svg_root' => 'src/content/svg/'];

        $routingPath = $root_dir . '/src/content/json/data/settings/data_settings_routing.json';
        if (file_exists($routingPath)) {
            $routing = json_decode(file_get_contents($routingPath), true);
            if (is_array($routing) && isset($routing['theme']) && is_array($routing['theme'])) {
                $themeConf = array_merge($themeConf, $routing['theme']);
            }
        }

        $themeRoot = $root_dir . '/' . trim($themeConf['root'], '/');
        $themeAssets = trim($themeConf['assets'], '/');
        $themeCompiled = trim($themeConf['compiled_assets'], '/');

        self::$config = [
            'root_dir'            => $root_dir,
            'src_dir'             => $root_dir . '/src',
            'dist_dir'            => $root_dir . '/dist',
            'dev_dir'             => $root_dir . '/dev',
            'engine_dir'          => $root_dir . '/dev/engine',
            'theme_dir'           => $themeRoot,
            'theme_assets_dir'    => $themeRoot . '/' . $themeAssets,
            'theme_compiled_dir'  => $themeRoot . '/' . $themeCompiled,
            'theme_compiled_path' => trim($themeConf['root'], '/') . '/' . $themeCompiled,
            'components_dir'      => $root_dir . '/src/components',
            'data_dir'            => $root_dir . '/src/content/json/data',
            'index_dir'           => $root_dir . '/src/content/json/index',
            'theme_scss_dir'      => $themeRoot . '/' . $themeAssets . '/scss',
            'theme_css_dir'       => $themeRoot . '/' . $themeAssets . '/css',
            'theme_js_dir'        => $themeRoot . '/' . $themeAssets . '/js',
            'theme_default_mode'  => $themeConf['default_mode'] ?? 'light',
            'theme_auto_extract_fonts' => $themeConf['auto_extract_fonts'] ?? true,
            'svg_dir'             => $root_dir . '/' . trim($themeConf['svg_root'] ?? 'src/content/svg/', '/'),
            'img_dir'             => $root_dir . '/src/content/img',
            'vid_dir'             => $root_dir . '/src/content/vid',
            'style_css_output'    => 'style',
            'components_css_output' => 'components',
        ];

        self::$initialized = true;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        self::init();
        return self::$config[$key] ?? $default;
    }

    public static function all(): array
    {
        self::init();
        return self::$config;
    }

    public static function getUrlPath(): string
    {
        return $GLOBALS['url_path'] ?? '';
    }

    public static function getPageSlugExtension(): string
    {
        $global = PlatformDataService::get_data('settings_routing');
        return $global['routing']['extension'] ?? $global['page_slug_extension'] ?? '.html';
    }

    public static function getRoutingMappings(): array
    {
        $global = PlatformDataService::get_data('settings_routing');
        return $global['routing']['mappings'] ?? $global['post-mappings'] ?? [];
    }
}
