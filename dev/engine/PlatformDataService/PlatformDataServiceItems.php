<?php

trait PlatformDataServiceItems
{
    public static function get_valid_item_files(): array
    {
        $data_dir = PlatformConfig::get('data_dir');
        $langDir = $data_dir . '/' . self::get_language();
        $files = self::glob_recursive_items($langDir, 'data_items_*.json');
        if (!$files) {
            $files = self::glob_recursive_items($data_dir, 'data_items_*.json');
        }
        $valid = [];

        foreach ($files as $file_path) {
            $filename = basename($file_path);
            if (preg_match('/^data_items_([a-z0-9]+)\.json$/', $filename, $matches)) {
                $valid[] = [
                    'name'     => $matches[1],
                    'path'     => $file_path,
                    'filename' => $filename,
                ];
            }
        }

        return $valid;
    }

    public static function get_all_items_from_file(string $item_name): ?array
    {
        return self::get_data('items_' . $item_name, 'data');
    }

    public static function get_item_by_id(string $item_name, string $id): ?array
    {
        $items = self::get_all_items_from_file($item_name);
        if (empty($items)) return null;

        foreach ($items as $item) {
            if (($item['_id'] ?? '') === $id) {
                return $item;
            }
        }

        return null;
    }

    public static function get_item_by_slug(string $item_name, string $slug): ?array
    {
        $items = self::get_all_items_from_file($item_name);
        if (empty($items)) return null;

        foreach ($items as $item) {
            $seo = $item['data']['seo'] ?? [];
            if (($seo['slug'] ?? '') === $slug) {
                return $item;
            }
        }

        return null;
    }

    private static function glob_recursive_items(string $dir, string $pattern): array
    {
        if (!is_dir($dir)) return [];
        $files = glob($dir . '/' . $pattern);
        foreach (glob($dir . '/*', GLOB_ONLYDIR) as $subDir) {
            $files = array_merge($files, self::glob_recursive_items($subDir, $pattern));
        }
        return $files ?: [];
    }
}