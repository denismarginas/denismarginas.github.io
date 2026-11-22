<?php

class PlatformBundleBuilder
{
    public static function build(): array
    {
        $componentsDir = PlatformConfig::get('components_dir');
        $compiledDir = PlatformConfig::get('theme_compiled_dir');
        $compiledPath = PlatformConfig::get('theme_compiled_path');

        $componentNames = array_map('basename', glob($componentsDir . '/*', GLOB_ONLYDIR));
        sort($componentNames);
        $order = array_merge(['theme'], array_values(array_filter($componentNames, fn($n) => $n !== 'theme')));

        $cssContent = '';
        $jsContent = '';

        foreach ($order as $name) {
            $configPath = $componentsDir . '/' . $name . '/component.json';
            if (!file_exists($configPath)) continue;
            $config = json_decode(file_get_contents($configPath), true);
            if (!$config) continue;

            $assets = $config['assets'] ?? [];
            $componentDir = $componentsDir . '/' . $name;

            foreach (PlatformAssetResolver::resolve($assets['css'] ?? [], $componentDir) as $file) {
                if (str_ends_with($file, '.scss')) continue;
                $fullPath = $componentDir . '/' . $file;
                if (file_exists($fullPath)) {
                    $cssContent .= self::rebase_urls(self::read_source($fullPath), dirname($fullPath), $compiledDir . '/css') . "\n";
                }
            }
            foreach (PlatformAssetResolver::resolve($assets['js'] ?? [], $componentDir) as $file) {
                $fullPath = $componentDir . '/' . $file;
                if (file_exists($fullPath)) {
                    $jsContent .= self::read_source($fullPath) . "\n";
                }
            }
        }

        $cssDir = $compiledDir . '/css';
        $jsDir = $compiledDir . '/js';
        if (!is_dir($cssDir)) mkdir($cssDir, 0755, true);
        if (!is_dir($jsDir)) mkdir($jsDir, 0755, true);

        file_put_contents($cssDir . '/bundle.css', $cssContent);
        file_put_contents($jsDir . '/bundle.js', $jsContent);

        return [
            [
                'file' => $compiledPath . '/css/bundle.css',
                'success' => true,
                'bytes' => strlen($cssContent),
                'message' => 'Written',
            ],
            [
                'file' => $compiledPath . '/js/bundle.js',
                'success' => true,
                'bytes' => strlen($jsContent),
                'message' => 'Written',
            ],
        ];
    }

    private static function read_source(string $path): string
    {
        $content = (string) file_get_contents($path);
        return str_starts_with($content, "\xEF\xBB\xBF") ? substr($content, 3) : $content;
    }

    private static function rebase_urls(string $css, string $fromDir, string $toDir): string
    {
        return preg_replace_callback('/url\(\s*([\'"]?)([^\'")]+)\1\s*\)/i', function ($m) use ($fromDir, $toDir) {
            $url = trim($m[2]);
            if ($url === '' || preg_match('#^([a-z][a-z0-9+.-]*:|//|/|\#)#i', $url)) return $m[0];
            $suffix = '';
            if (preg_match('/^([^?#]*)([?#].*)$/', $url, $parts)) {
                $url = $parts[1];
                $suffix = $parts[2];
            }
            $target = self::normalize($fromDir . '/' . $url);
            return 'url("' . self::relative($toDir, $target) . $suffix . '")';
        }, $css) ?? $css;
    }

    private static function normalize(string $path): string
    {
        $path = str_replace('\\', '/', $path);
        $prefix = preg_match('#^([A-Za-z]:)?/#', $path, $m) ? $m[0] : '';
        $out = [];
        foreach (explode('/', substr($path, strlen($prefix))) as $seg) {
            if ($seg === '' || $seg === '.') continue;
            if ($seg === '..') { array_pop($out); continue; }
            $out[] = $seg;
        }
        return $prefix . implode('/', $out);
    }

    private static function relative(string $fromDir, string $toFile): string
    {
        $from = explode('/', self::normalize($fromDir));
        $to = explode('/', self::normalize($toFile));
        while ($from && $to && strcasecmp($from[0], $to[0]) === 0) {
            array_shift($from);
            array_shift($to);
        }
        return str_repeat('../', count($from)) . implode('/', $to);
    }
}
