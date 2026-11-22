<?php

class PlatformScssBuilder
{
    public static function read_flags(): array
    {
        $flags = [
            'compile_scss_platform_components' => true,
            'compile_scss_platform_assets' => true,
            'compile_scss_src_components' => true,
            'compile_scss_assets' => false,
            'compile_scss_everytime' => true,
        ];
        $section = PlatformWorkflowService::section('compile_scss');
        foreach ($section as $name => $value) {
            if (array_key_exists($name, $flags)) $flags[$name] = $value === true || (string) $value === 'true';
        }
        $useBundle = $section['compile_assets'] ?? PlatformWorkflowService::get('render', 'compile_assets', false);
        if ($useBundle === true || (string) $useBundle === 'true') $flags['compile_scss_assets'] = true;
        return $flags;
    }

    public static function compile_component(string $componentDir, bool $clean = false): array
    {
        return self::compile_dir($componentDir . '/assets/scss', $componentDir . '/assets/css', $clean);
    }

    public static function compile_dir(string $scssDir, string $cssDir, bool $clean = false): array
    {
        if (!is_dir($scssDir)) return [];
        if ($clean) self::clean_css_dir($cssDir);
        $results = [];
        $themeScss = PlatformConfig::get('theme_scss_dir');
        foreach (self::find_scss_files($scssDir) as $scssFile) {
            $rel = substr($scssFile, strlen($scssDir) + 1);
            $cssFile = $cssDir . '/' . preg_replace('/\.scss$/', '.css', $rel);
            $entry = ['file' => $rel, 'output' => $rel];
            try {
                $css = PlatformScssService::compile_file($scssFile, [$themeScss, dirname($scssFile)], true);
                if (!is_dir(dirname($cssFile))) mkdir(dirname($cssFile), 0777, true);
                file_put_contents($cssFile, $css);
                $results[] = $entry + ['success' => true, 'bytes' => strlen($css)];
            } catch (\Throwable $e) {
                $results[] = $entry + ['success' => false, 'message' => $e->getMessage()];
            }
        }
        return $results;
    }

    public static function compile_src(bool $clean = false): array
    {
        $results = [];
        foreach (glob(PlatformConfig::get('components_dir') . '/*', GLOB_ONLYDIR) as $dir) {
            $results = array_merge($results, self::compile_component($dir, $clean));
        }
        return $results;
    }

    public static function compile_platform_components(bool $clean = false): array
    {
        $dev = PlatformConfig::get('dev_dir');
        $results = [];
        foreach (glob($dev . '/platform/components/*', GLOB_ONLYDIR) as $dir) {
            $results = array_merge($results, self::compile_component($dir, $clean));
        }
        return $results;
    }

    public static function compile_platform_assets(bool $clean = false): array
    {
        $dev = PlatformConfig::get('dev_dir');
        return self::compile_dir($dev . '/platform/assets/scss', $dev . '/platform/assets/css', $clean);
    }

    public static function compile_assets(): array
    {
        return PlatformBundleBuilder::build();
    }

    public static function run(array $flags = []): array
    {
        if (!$flags) $flags = self::read_flags();
        self::refresh_font_list();
        $clean = !empty($flags['compile_scss_everytime']);
        $results = [];
        if (!empty($flags['compile_scss_src_components'])) $results = array_merge($results, self::compile_src($clean));
        if (!empty($flags['compile_scss_platform_components'])) $results = array_merge($results, self::compile_platform_components($clean));
        if (!empty($flags['compile_scss_platform_assets'])) $results = array_merge($results, self::compile_platform_assets($clean));
        if (!empty($flags['compile_scss_assets'])) $results = array_merge($results, self::compile_assets());
        $success = 0;
        foreach ($results as $r) if (!empty($r['success'])) $success++;
        return ['total' => count($results), 'success_count' => $success, 'error_count' => count($results) - $success, 'results' => $results];
    }

    private static function clean_css_dir(string $cssDir): void
    {
        if (!is_dir($cssDir)) return;
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($cssDir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $file) {
            $path = $file->getPathname();
            if ($file->isDir()) {
                @rmdir($path);
                continue;
            }
            if (str_ends_with($path, '.css')) {
                @unlink($path);
            }
        }
    }

    private static function refresh_font_list(): void
    {
        $file = PlatformConfig::get('theme_assets_dir') . '/php/get_font_list.php';
        if (!is_file($file)) return;
        if (!class_exists('theme_get_font_list')) {
            define('IN_THEME_GET_FONT_LIST', true);
            require_once $file;
        }
        theme_get_font_list::generate(null, null, PlatformConfig::get('theme_auto_extract_fonts', true));
    }

    private static function find_scss_files(string $dir): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if ($file->isDir()) continue;
            $path = $file->getPathname();
            if (!str_ends_with($path, '.scss')) continue;
            if (str_starts_with(basename($path), '_')) continue;
            $files[] = $path;
        }
        sort($files);
        return $files;
    }
}
