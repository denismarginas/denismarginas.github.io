<?php

class project_media_finder
{
    private const PROJECTS_DIR = 'src/content/img/projects/';
    private const OVERVIEW_DIR = 'overview';
    private const EXCLUDE_DIR = 'logo';
    private const EXTENSIONS = ['webp', 'jpg', 'jpeg', 'png', 'gif', 'avif'];

    public static function photo(array $postData): string
    {
        $overview = (string) ($postData['media']['overview_media'] ?? '');
        if ($overview !== '') {
            $path = self::PROJECTS_DIR . ltrim($overview, '/');
            if (is_file(self::absolute($path))) {
                return $path;
            }
        }

        $mediaPath = trim((string) ($postData['media']['path'] ?? ''), '/');
        if ($mediaPath === '') {
            return '';
        }

        $mediaDir = self::PROJECTS_DIR . $mediaPath . '/media/';
        $image = self::first_image($mediaDir . self::OVERVIEW_DIR . '/');
        if ($image !== '') {
            return $image;
        }

        foreach (self::sorted(self::absolute($mediaDir), 'is_dir') as $folder) {
            if (stripos($folder, self::EXCLUDE_DIR) !== false) {
                continue;
            }
            $image = self::first_image($mediaDir . $folder . '/');
            if ($image !== '') {
                return $image;
            }
        }

        return '';
    }

    private static function first_image(string $dir): string
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
