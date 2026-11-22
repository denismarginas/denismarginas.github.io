<?php

class PlatformAssetResolver
{
    public static function resolve(array $patterns, string $componentDir): array
    {
        $resolved = [];
        foreach ($patterns as $pattern) {
            if (strpos($pattern, '*') === false) {
                $resolved[] = $pattern;
                continue;
            }
            foreach (self::expand($componentDir, $pattern) as $match) {
                $resolved[] = $match;
            }
        }
        return array_values(array_unique($resolved));
    }

    private static function expand(string $componentDir, string $pattern): array
    {
        if ($pattern === '**' || str_ends_with($pattern, '/**')) {
            $relDir = $pattern === '**' ? '' : substr($pattern, 0, -3);
            $absDir = rtrim($componentDir . '/' . $relDir, '/');
            if (!is_dir($absDir)) return [];

            $files = [];
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($absDir, FilesystemIterator::SKIP_DOTS));
            foreach ($iterator as $file) {
                if ($file->isDir()) continue;
                if (str_starts_with($file->getFilename(), '_')) continue;
                $files[] = self::relative_path($componentDir, $file->getPathname());
            }
            sort($files);
            return $files;
        }

        $matches = glob($componentDir . '/' . $pattern);
        if ($matches === false) return [];

        $files = [];
        foreach ($matches as $match) {
            if (is_dir($match)) continue;
            if (str_starts_with(basename($match), '_')) continue;
            $files[] = self::relative_path($componentDir, $match);
        }
        sort($files);
        return $files;
    }

    private static function relative_path(string $componentDir, string $absPath): string
    {
        $rel = substr($absPath, strlen($componentDir));
        return ltrim(str_replace('\\', '/', $rel), '/');
    }
}
