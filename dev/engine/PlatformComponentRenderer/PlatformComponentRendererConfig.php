<?php

trait PlatformComponentRendererConfig
{
    public static function get_component_config(string $component_name): ?array
    {
        if (isset(self::$component_cache[$component_name])) {
            return self::$component_cache[$component_name];
        }

        $config = PlatformDataService::get_component_config($component_name);
        if ($config === null) {
            return null;
        }

        self::$component_cache[$component_name] = $config;
        return $config;
    }

    public static function component_name_to_class(string $name): string
    {
        return str_replace('-', '_', $name);
    }
}
