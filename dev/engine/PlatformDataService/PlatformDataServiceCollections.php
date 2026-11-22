<?php

trait PlatformDataServiceCollections
{
    public static function get_valid_collection_files(): array
    {
        $data_dir = PlatformConfig::get('data_dir');
        $langDir = $data_dir . '/' . self::get_language();
        $files = self::glob_recursive_collections($langDir, 'data_collection_*.json');
        if (!$files) {
            $files = self::glob_recursive_collections($data_dir, 'data_collection_*.json');
        }
        $valid = [];

        foreach ($files as $file_path) {
            $filename = basename($file_path);
            if (preg_match('/^data_collection_([a-z0-9_]+)\.json$/', $filename, $matches)) {
                $valid[] = [
                    'name'     => $matches[1],
                    'path'     => $file_path,
                    'filename' => $filename,
                ];
            }
        }

        return $valid;
    }

    public static function get_collection(string $collection): ?array
    {
        return self::get_data('collection_' . $collection, 'data');
    }

    public static function get_collection_entry_by_id(string $collection, string $id): ?array
    {
        $entries = self::get_collection($collection);
        if (empty($entries)) return null;

        foreach ($entries as $entry) {
            if (($entry['_id'] ?? '') === $id) {
                return $entry;
            }
        }

        return null;
    }

    public static function get_collection_entry_by_slug(string $collection, string $slug): ?array
    {
        $entries = self::get_collection($collection);
        if (empty($entries)) return null;

        foreach ($entries as $entry) {
            $seo = $entry['data']['seo'] ?? [];
            if (($seo['slug'] ?? '') === $slug) {
                return $entry;
            }
        }

        return null;
    }

    private static function glob_recursive_collections(string $dir, string $pattern): array
    {
        if (!is_dir($dir)) return [];
        $files = glob($dir . '/' . $pattern);
        foreach (glob($dir . '/*', GLOB_ONLYDIR) as $subDir) {
            $files = array_merge($files, self::glob_recursive_collections($subDir, $pattern));
        }
        return $files ?: [];
    }
}