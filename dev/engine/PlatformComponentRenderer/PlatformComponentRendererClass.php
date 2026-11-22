<?php

trait PlatformComponentRendererClass
{
    public static function load_component_class(string $component_name): bool
    {
        $class_name = self::component_name_to_class($component_name);
        if (isset(self::$loaded_classes[$class_name])) {
            return true;
        }

        $config = self::get_component_config($component_name);
        if ($config === null) {
            return false;
        }

        $dependencies = $config['dependencies'] ?? [];
        foreach ($dependencies as $dep) {
            self::load_component_class($dep);
        }

        $php_files = $config['assets']['php'] ?? [];
        foreach ($php_files as $php_file) {
            $file_path = self::get_component_file_path($component_name, $php_file);
            if ($file_path && file_exists($file_path)) {
                require_once $file_path;
            }
        }

        self::$loaded_classes[$class_name] = true;
        return true;
    }

    protected static function get_component_file_path(string $component_name, string $relative_path): string
    {
        $components_dir = PlatformConfig::get('components_dir');
        return $components_dir . '/' . $component_name . '/' . $relative_path;
    }
}
