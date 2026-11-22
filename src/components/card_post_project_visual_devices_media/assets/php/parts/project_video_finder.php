<?php

class project_video_finder
{
    private const PROJECTS_DIR = 'src/content/vid/projects/';
    private const EXTENSIONS = ['webm', 'mp4', 'ogg', 'mov'];

    public static function video(array $postData): string
    {
        $mediaPath = trim((string) ($postData['media']['path'] ?? ''), '/');
        if ($mediaPath === '') {
            return '';
        }

        $dir = self::PROJECTS_DIR . $mediaPath . '/';
        $video = self::first_video($dir);
        if ($video !== '') {
            return $video;
        }

        foreach (self::sorted(self::absolute($dir), 'is_dir') as $folder) {
            $video = self::first_video($dir . $folder . '/');
            if ($video !== '') {
                return $video;
            }
        }

        return '';
    }

    public static function videos(array $postData): array
    {
        $mediaPath = trim((string) ($postData['media']['path'] ?? ''), '/');
        if ($mediaPath === '') {
            return [];
        }

        $dir = self::PROJECTS_DIR . $mediaPath . '/';
        $videos = self::all_videos($dir);
        foreach (self::sorted(self::absolute($dir), 'is_dir') as $folder) {
            $videos = array_merge($videos, self::all_videos($dir . $folder . '/'));
        }

        return $videos;
    }

    private static function all_videos(string $dir): array
    {
        $out = [];
        foreach (self::sorted(self::absolute($dir), 'is_file') as $file) {
            if (in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), self::EXTENSIONS, true)) {
                $out[] = $dir . $file;
            }
        }
        return $out;
    }

    private static function first_video(string $dir): string
    {
        foreach (self::sorted(self::absolute($dir), 'is_file') as $file) {
            if (in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), self::EXTENSIONS, true)) {
                return $dir . $file;
            }
        }
        return '';
    }

    private static function sorted(string $absDir, callable $check): array
    {
        if (!is_dir($absDir)) {
            return [];
        }

        $names = array_values(array_filter(
            scandir($absDir) ?: [],
            fn ($name) => $name[0] !== '.' && $check($absDir . '/' . $name)
        ));
        natcasesort($names);

        return array_values($names);
    }

    private static function absolute(string $path): string
    {
        $root = defined('ENGINE_PROJECT_ROOT') ? rtrim(ENGINE_PROJECT_ROOT, '/\\') . '/' : '';
        return $root . ltrim($path, '/');
    }
}
