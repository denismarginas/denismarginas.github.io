<?php

class theme_get_font_list
{
    private const FONT_EXTS = ['ttf', 'otf', 'woff', 'woff2', 'eot'];
    private const FONT_WEIGHTS = ['black', 'bold', 'extrabold', 'extralight', 'light', 'medium', 'regular', 'semibold', 'thin'];
    private const FILE_ORDER = ['ttf', 'otf', 'woff2', 'woff', 'eot'];

    public static function generate(?string $fontsDir = null, ?string $outputFile = null, ?bool $theme_auto_extract_fonts = null): array
    {
        if ($theme_auto_extract_fonts === null) {
            $theme_auto_extract_fonts = true;
        }

        $fontsDir = $fontsDir ?? (PlatformConfig::get('theme_assets_dir') . '/fonts');
        $outputFile = $outputFile ?? (PlatformConfig::get('theme_scss_dir') . '/variables/_font-faces.scss');

        if (!$theme_auto_extract_fonts) {
            return ['ok' => false, 'skipped' => true, 'message' => 'theme_auto_extract_fonts is disabled', 'fonts' => []];
        }

        if (!is_dir($fontsDir)) {
            return ['ok' => false, 'message' => 'fonts dir not found: ' . $fontsDir, 'fonts' => []];
        }

        $dirs = array_values(array_filter(scandir($fontsDir), function ($entry) use ($fontsDir) {
            return $entry !== '.' && $entry !== '..' && is_dir($fontsDir . '/' . $entry);
        }));
        sort($dirs);

        $weights = array_filter(self::FONT_WEIGHTS, fn() => true);
        usort($weights, fn($a, $b) => strlen($b) <=> strlen($a));

        $roots = [];
        $variants = [];

        foreach ($dirs as $dir) {
            $groups = self::collect_groups($fontsDir . '/' . $dir);
            if (empty($groups)) continue;

            foreach ($groups as $base => $files) {
                $match = self::match_weight_suffix($base, $weights);
                if ($match !== null) {
                    $variants[] = ['name' => $base, 'src' => $dir, 'files' => $files];
                } else {
                    $roots[] = ['name' => $dir, 'src' => $dir, 'files' => $files];
                }
            }
        }

        $entries = array_merge($roots, $variants);

        if (empty($entries)) {
            return ['ok' => false, 'message' => 'no font files found in ' . $fontsDir, 'fonts' => []];
        }

        $content = self::render_scss($entries);

        $outputDir = dirname($outputFile);
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0777, true);
        }

        $changed = !file_exists($outputFile) || file_get_contents($outputFile) !== $content;
        if ($changed) {
            file_put_contents($outputFile, $content);
        }

        return [
            'ok' => true,
            'output' => $outputFile,
            'changed' => $changed,
            'fonts' => array_map(fn($f) => $f['name'], $entries),
            'count' => count($entries),
        ];
    }

    private static function collect_groups(string $dir): array
    {
        $groups = [];
        foreach (scandir($dir) as $entry) {
            if ($entry === '.' || $entry === '..') continue;
            $path = $dir . '/' . $entry;
            if (!is_file($path)) continue;

            $ext = strtolower(pathinfo($entry, PATHINFO_EXTENSION));
            if (!in_array($ext, self::FONT_EXTS, true)) continue;

            $base = pathinfo($entry, PATHINFO_FILENAME);
            $groups[$base][$ext] = $entry;
        }

        foreach ($groups as $base => $files) {
            $ordered = [];
            foreach (self::FILE_ORDER as $ext) {
                if (isset($files[$ext])) $ordered[$ext] = $files[$ext];
            }
            foreach ($files as $ext => $file) {
                if (!isset($ordered[$ext])) $ordered[$ext] = $file;
            }
            $groups[$base] = $ordered;
        }

        return $groups;
    }

    private static function match_weight_suffix(string $base, array $weights): ?array
    {
        $lower = strtolower($base);
        foreach ($weights as $weight) {
            foreach (['-', '_', ' '] as $sep) {
                $needle = $sep . $weight;
                if (str_ends_with($lower, $needle)) {
                    return [
                        'suffix' => $weight,
                        'separator' => $sep,
                        'root' => substr($base, 0, -strlen($needle)),
                    ];
                }
            }
        }
        return null;
    }

    private static function render_scss(array $entries): string
    {
        $lines = ['$dm-fonts: ('];
        foreach ($entries as $i => $entry) {
            $lines[] = '  (';
            $lines[] = '    name: "' . $entry['name'] . '",';
            $lines[] = '    root: (';
            $lines[] = '      src: "' . $entry['src'] . '",';
            $lines[] = '      files: (';
            $fileKeys = array_keys($entry['files']);
            $fileCount = count($entry['files']);
            $idx = 0;
            foreach ($entry['files'] as $file) {
                $key = $fileKeys[$idx];
                $comma = ($idx < $fileCount - 1) ? ',' : '';
                $lines[] = '        ' . $key . ': "' . $file . '"' . $comma;
                $idx++;
            }
            $lines[] = '      )';
            $lines[] = '    )';
            $comma = ($i < count($entries) - 1) ? ',' : '';
            $lines[] = '  )' . $comma;
        }
        $lines[] = ');';

        return implode("\n", $lines) . "\n";
    }
}

if (php_sapi_name() === 'cli' && !defined('IN_THEME_GET_FONT_LIST')) {
    require_once __DIR__ . '/../../../../../dev/engine/bootstrap.php';
    $result = theme_get_font_list::generate();
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
}
