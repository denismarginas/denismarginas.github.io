<?php

trait PlatformComponentRendererAssets
{
    public static function get_component_asset_tags(): string
    {
        $html = '';
        $components_dir = PlatformConfig::get('components_dir');
        $url_path = PlatformPathService::asset_relative_prefix();

        $names = !empty(self::$used_components) ? self::$used_components : PlatformDataService::get_all_component_names();

        $names = array_values($names);
        $themeIndex = array_search('theme', $names, true);
        if ($themeIndex !== false && $themeIndex !== 0) {
            array_splice($names, $themeIndex, 1);
            array_unshift($names, 'theme');
        }

        foreach ($names as $name) {
            $config = self::get_component_config($name);
            if ($config === null) continue;

            $assets = $config['assets'] ?? [];
            $component_dir = $components_dir . '/' . $name;

            foreach (PlatformAssetResolver::resolve($assets['css'] ?? [], $component_dir) as $css_file) {
                if (str_ends_with($css_file, '.scss')) continue;
                $file_path = $component_dir . '/' . $css_file;
                if (file_exists($file_path)) {
                    $html .= '<link rel="stylesheet" href="' . $url_path . 'src/components/' . $name . '/' . $css_file . '">';
                }
            }

            foreach (PlatformAssetResolver::resolve($assets['js'] ?? [], $component_dir) as $js_file) {
                $file_path = $component_dir . '/' . $js_file;
                if (file_exists($file_path)) {
                    $html .= '<script src="' . $url_path . 'src/components/' . $name . '/' . $js_file . '"></script>';
                }
            }
        }

        return $html;
    }

    public static function mark_used(string $component_name): void
    {
        if (!in_array($component_name, self::$used_components, true)) {
            self::$used_components[] = $component_name;
        }
        foreach (self::get_dependency_chain($component_name) as $dep) {
            if (!in_array($dep, self::$used_components, true)) {
                self::$used_components[] = $dep;
            }
        }
    }

    public static function get_dependency_chain(string $component_name, array &$visited = []): array
    {
        $deps = [];
        if (isset($visited[$component_name])) return $deps;
        $visited[$component_name] = true;

        $config = self::get_component_config($component_name);
        if ($config === null) return $deps;

        $direct_deps = $config['dependencies'] ?? [];
        foreach ($direct_deps as $dep) {
            $child_deps = self::get_dependency_chain($dep, $visited);
            $deps = array_merge($deps, $child_deps);
            if (!in_array($dep, $deps)) {
                $deps[] = $dep;
            }
        }

        return $deps;
    }
}
