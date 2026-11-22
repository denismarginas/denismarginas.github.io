<?php

class utility_post_get_img
{
    public static function value(array $params = []): string
    {
        $root = (string) ($params['root'] ?? '');
        if ($root === '') return '';

        $root = self::normalize_path($root);
        $cases = $params['cases'] ?? $params['try'] ?? [];
        if (is_string($cases)) {
            $cases = [$cases];
        }
        if (!is_array($cases)) {
            $cases = [];
        }

        foreach ($cases as $case) {
            if (is_string($case)) {
                $case = ['img' => $case];
            }
            if (!is_array($case)) continue;

            $path = self::normalize_path((string) ($case['path'] ?? ''));
            $img = (string) ($case['img'] ?? '');

            if ($img === 'any' || $img === '*') {
                $found = self::find_any($root . $path, $case);
                if ($found !== '') {
                    return $found;
                }
                continue;
            }

            if ($img === '') continue;

            $candidate = $root . $path . $img;
            if (self::file_exists($candidate)) {
                return $candidate;
            }
        }

        return '';
    }

    public static function find_any(string $baseDir, array $case): string
    {
        $excludePath = (string) ($case['exclude_path_with_string'] ?? '');
        $excludeImg = (string) ($case['exclude_img_with_string'] ?? '');
        $extensions = ['webp', 'jpg', 'jpeg', 'png', 'gif', 'avif'];

        $abs = self::absolute($baseDir);
        if (!is_dir($abs)) return '';

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($abs, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if (!$file->isFile()) continue;

            $relPath = self::normalize_path(substr($file->getPathname(), strlen($abs)));
            $relPath = ltrim($relPath, '/');

            $ext = strtolower($file->getExtension());
            if (!in_array($ext, $extensions, true)) continue;

            if ($excludePath !== '' && str_contains(strtolower($relPath), strtolower($excludePath))) continue;
            if ($excludeImg !== '' && str_contains(strtolower($file->getFilename()), strtolower($excludeImg))) continue;

            return $baseDir . $relPath;
        }

        return '';
    }

    public static function file_exists(string $projectRelativePath): bool
    {
        return is_file(self::absolute($projectRelativePath));
    }

    public static function absolute(string $projectRelativePath): string
    {
        $root = defined('ENGINE_PROJECT_ROOT') ? ENGINE_PROJECT_ROOT : '';
        return rtrim($root, '/\\') . '/' . ltrim($projectRelativePath, '/\\');
    }

    public static function normalize_path(string $path): string
    {
        return str_replace('\\', '/', $path);
    }
}
