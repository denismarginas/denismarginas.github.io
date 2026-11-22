<?php

trait PlatformDataServicePosts
{
    public static function get_valid_post_files(): array
    {
        $data_dir = PlatformConfig::get('data_dir');
        $langDir = $data_dir . '/' . self::get_language();
        $files = self::glob_recursive($langDir, 'data_post_*.json');
        if (!$files) {
            $files = self::glob_recursive($data_dir, 'data_post_*.json');
        }
        $valid = [];

        foreach ($files as $file_path) {
            $filename = basename($file_path);
            if (preg_match('/^data_post_([a-z0-9]+)\.json$/', $filename, $matches)) {
                $valid[] = [
                    'name'     => $matches[1],
                    'path'     => $file_path,
                    'filename' => $filename,
                ];
            }
        }

        return $valid;
    }

    private static function glob_recursive(string $dir, string $pattern): array
    {
        if (!is_dir($dir)) return [];
        $files = glob($dir . '/' . $pattern);
        foreach (glob($dir . '/*', GLOB_ONLYDIR) as $subDir) {
            $files = array_merge($files, self::glob_recursive($subDir, $pattern));
        }
        return $files ?: [];
    }

    public static function get_all_posts_from_file(string $post_name): ?array
    {
        return self::get_data('post_' . $post_name);
    }

    public static function get_post_by_id(string $post_name, string $id): ?array
    {
        $posts = self::get_all_posts_from_file($post_name);
        if (empty($posts)) return null;

        foreach ($posts as $post) {
            if (($post['_id'] ?? '') === $id) {
                return $post;
            }
        }

        return null;
    }

    public static function get_post_by_slug(string $post_name, string $slug): ?array
    {
        $posts = self::get_all_posts_from_file($post_name);
        if (empty($posts)) return null;

        foreach ($posts as $post) {
            $seo = $post['seo'] ?? [];
            if (($seo['slug'] ?? '') === $slug) {
                return $post;
            }
        }

        return null;
    }
}
