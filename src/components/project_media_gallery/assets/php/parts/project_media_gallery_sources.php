<?php

require_once __DIR__ . '/../../../../utility_project_web_screens/assets/php/utility_project_web_screens.php';

class project_media_gallery_sources
{
    public const PROJECTS_DIR = 'src/content/img/projects/';
    public const VIDEOS_DIR = 'src/content/vid/projects/';
    private const EXTENSIONS = ['webp', 'jpg', 'jpeg', 'png', 'gif', 'avif', 'svg'];
    private const VIDEO_EXTENSIONS = ['webm', 'mp4', 'ogg', 'ogv', 'm4v', 'mov'];

    public static function find(string $baseDir, string $type, string $webDir, string $phoneDir, bool $autoSearch, array $exclude = []): array
    {
        $baseDir = rtrim($baseDir, '/') . '/';
        $exclude = array_map(fn ($e) => strtolower(trim((string) $e, " /")), $exclude);
        $folders = [['name' => basename(rtrim($baseDir, '/')), 'rel' => '', 'dir' => $baseDir]];

        if ($autoSearch) {
            foreach (self::entries($baseDir, 'is_dir') as $sub) {
                if ($type === 'devices' && in_array($sub, [$webDir, $phoneDir], true)) continue;
                if (in_array(strtolower($sub), $exclude, true)) continue;
                $folders[] = ['name' => $sub, 'rel' => $sub . '/', 'dir' => $baseDir . $sub . '/'];
            }
        }

        $sources = [];
        foreach ($folders as $folder) {
            $source = ['name' => $folder['name'], 'desktop' => [], 'phone' => [], 'images' => [], 'videos' => []];
            if ($type === 'devices') {
                $source['desktop'] = self::images($folder['dir'] . trim($webDir, '/') . '/');
                $source['phone'] = self::images($folder['dir'] . trim($phoneDir, '/') . '/');
            } elseif ($type === 'videos') {
                $source['videos'] = self::files($folder['dir'], self::VIDEO_EXTENSIONS, $exclude, $folder['rel']);
            } else {
                $source['images'] = self::files($folder['dir'], self::EXTENSIONS, $exclude, $folder['rel']);
            }
            if ($source['desktop'] || $source['phone'] || $source['images'] || $source['videos']) {
                $sources[] = $source;
            }
        }

        return $sources;
    }

    public static function images(string $dir): array
    {
        return self::files($dir, self::EXTENSIONS);
    }

    private static function files(string $dir, array $extensions, array $exclude = [], string $rel = ''): array
    {
        $out = [];
        foreach (utility_project_web_screens::sort(self::entries($dir, 'is_file')) as $file) {
            if (!in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), $extensions, true)) continue;
            if ($exclude && (in_array(strtolower($file), $exclude, true) || in_array(strtolower($rel . $file), $exclude, true))) continue;
            $out[] = $dir . $file;
        }
        return $out;
    }

    private static function entries(string $dir, callable $check): array
    {
        $abs = self::absolute($dir);
        if (!is_dir($abs)) return [];

        $names = array_values(array_filter(
            scandir($abs) ?: [],
            fn ($name) => $name[0] !== '.' && $check($abs . '/' . $name)
        ));
        natcasesort($names);
        return array_values($names);
    }

    public static function absolute(string $path): string
    {
        $root = defined('ENGINE_PROJECT_ROOT') ? rtrim(ENGINE_PROJECT_ROOT, '/\\') . '/' : '';
        return rtrim($root . ltrim($path, '/'), '/');
    }
}
