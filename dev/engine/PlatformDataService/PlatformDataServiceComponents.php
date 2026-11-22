<?php

trait PlatformDataServiceComponents
{
    public static function get_component_config(string $component_name): ?array
    {
        $components_dir = PlatformConfig::get('components_dir');
        $config_path = $components_dir . '/' . $component_name . '/component.json';

        if (!file_exists($config_path)) {
            return null;
        }

        $content = file_get_contents($config_path);
        if (strncmp($content, "\xEF\xBB\xBF", 3) === 0) {
            $content = substr($content, 3);
        }
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        return $data;
    }

    public static function get_all_component_names(): array
    {
        $components_dir = PlatformConfig::get('components_dir');
        $items = scandir($components_dir);
        $components = [];

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $config_path = $components_dir . '/' . $item . '/component.json';
            if (is_dir($components_dir . '/' . $item) && file_exists($config_path)) {
                $components[] = $item;
            }
        }

        sort($components);
        return $components;
    }
}
