<?php

require_once __DIR__ . '/../../../../../dev/engine/bootstrap.php';

class theme_compile_css
{
    public static function compile(?string $filterComponent = null): array
    {
        $themeDir = PlatformConfig::get('theme_dir');
        $themeAssetsDir = PlatformConfig::get('theme_assets_dir');
        $scssDir = PlatformConfig::get('theme_scss_dir');
        $cssDir = PlatformConfig::get('theme_css_dir');
        $componentsDir = PlatformConfig::get('components_dir');

        $configPath = $themeDir . '/component.json';
        $themeConfig = json_decode(file_get_contents($configPath), true);
        $compileOrder = $themeConfig['compile_order'] ?? [];
        $outputFile = $themeConfig['output']['css'] ?? 'style.css';
        $minify = $themeConfig['minify'] ?? false;

        $loadPaths = [];
        foreach ($compileOrder as $relPath) {
            $absPath = $themeAssetsDir . '/' . $relPath;
            if (is_dir($absPath)) {
                $loadPaths[] = $absPath;
            }
        }
        if (!in_array($scssDir, $loadPaths, true)) {
            array_unshift($loadPaths, $scssDir);
        }

        $cssOutput = '';

        $styleFile = $scssDir . '/style.scss';
        if (file_exists($styleFile)) {
            try {
                $cssOutput .= PlatformScssService::compile_file($styleFile, $loadPaths) . "\n";
            } catch (\Throwable $e) {
                $cssOutput .= '/* Error compiling theme: ' . $e->getMessage() . ' */' . "\n";
            }
        }

        $components = PlatformDataService::get_all_component_names();
        foreach ($components as $componentName) {
            if ($componentName === 'theme') continue;
            if ($filterComponent !== null && $filterComponent !== $componentName) continue;

            $scssFile = $componentsDir . '/' . $componentName . '/assets/scss/style.scss';
            if (!file_exists($scssFile)) continue;

            $fileLoadPaths = $loadPaths;
            $fileLoadPaths[] = dirname($scssFile);

            try {
                $cssOutput .= PlatformScssService::compile_file($scssFile, $fileLoadPaths) . "\n";
            } catch (\Throwable $e) {
                $cssOutput .= '/* Error compiling ' . $componentName . ': ' . $e->getMessage() . ' */' . "\n";
            }
        }

        if (!is_dir($cssDir)) {
            mkdir($cssDir, 0755, true);
        }
        $outputPath = $cssDir . '/' . $outputFile;
        file_put_contents($outputPath, $cssOutput);

        $result = ['output' => $outputPath, 'size' => strlen($cssOutput), 'status' => 'compiled'];

        if ($minify) {
            $minified = PlatformScssService::minify($cssOutput);
            $minPath = $cssDir . '/' . preg_replace('/\.css$/', '.min.css', $outputFile);
            file_put_contents($minPath, $minified);
            $result['min_size'] = strlen($minified);
            $result['min_output'] = $minPath;
        }

        return $result;
    }
}

if (php_sapi_name() === 'cli' && !defined('IN_THEME_COMPILE_CSS')) {
    $filter = $argv[1] ?? null;
    $result = theme_compile_css::compile($filter);
    echo json_encode($result, JSON_PRETTY_PRINT) . "\n";
}
