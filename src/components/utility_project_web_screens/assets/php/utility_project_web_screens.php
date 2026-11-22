<?php

class utility_project_web_screens
{
    public const PROJECTS_DIR = 'src/content/img/projects/';
    public const WEBSITE_DIR = 'web/website';
    public const DEVICES = ['desktop', 'phone'];
    private const EXTENSIONS = ['webp', 'jpg', 'jpeg', 'png', 'gif', 'avif'];

    public static function value(array $data = []): string
    {
        $post = is_array($data['post_current_data'] ?? null) ? $data['post_current_data'] : [];
        return self::first((string) ($post['data']['media']['path'] ?? ''), (string) ($data['device'] ?? 'desktop'));
    }

    public static function first(string $mediaPath, string $device = 'desktop', string $baseDir = self::PROJECTS_DIR): string
    {
        return self::all($mediaPath, $device, $baseDir)[0] ?? '';
    }

    public static function all(string $mediaPath, string $device = 'desktop', string $baseDir = self::PROJECTS_DIR): array
    {
        $mediaPath = trim(str_replace('\\', '/', $mediaPath), '/');
        if ($mediaPath === '' || str_contains($mediaPath, '..')) return [];

        $device = $device === 'phone' ? 'phone' : 'desktop';
        $base = rtrim($baseDir, '/') . '/' . $mediaPath . '/';

        return self::images($base . self::WEBSITE_DIR . '/' . $device . '/');
    }

    public static function images(string $dir): array
    {
        $dir = rtrim($dir, '/') . '/';
        $abs = self::absolute($dir);
        if (!is_dir($abs)) return [];

        $names = [];
        foreach (scandir($abs) ?: [] as $name) {
            if ($name === '' || $name[0] === '.') continue;
            if (!in_array(strtolower(pathinfo($name, PATHINFO_EXTENSION)), self::EXTENSIONS, true)) continue;
            if (!is_file($abs . '/' . $name)) continue;
            $names[] = $name;
        }

        return array_map(fn ($name) => $dir . $name, self::sort($names));
    }

    public static function sort(array $names): array
    {
        $names = array_values($names);
        usort($names, static function ($a, $b): int {
            $firstA = self::is_first((string) $a);
            $firstB = self::is_first((string) $b);
            if ($firstA !== $firstB) return $firstA ? -1 : 1;
            return strnatcasecmp(basename((string) $a), basename((string) $b));
        });
        return $names;
    }

    public static function is_first(string $name): bool
    {
        return (bool) preg_match('/(^|[_\-])0+$/', pathinfo(basename($name), PATHINFO_FILENAME));
    }

    private static function absolute(string $path): string
    {
        $root = defined('ENGINE_PROJECT_ROOT') ? rtrim(ENGINE_PROJECT_ROOT, '/\\') . '/' : '';
        return rtrim($root . ltrim($path, '/'), '/');
    }
}
