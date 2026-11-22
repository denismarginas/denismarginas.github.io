<?php

header('Content-Type: application/json; charset=UTF-8');

if (!defined('ENGINE_PROJECT_ROOT')) {
    define('ENGINE_PROJECT_ROOT', dirname(__DIR__, 3));
}

$componentsDir = ENGINE_PROJECT_ROOT . '/src/components';
$dirs = glob($componentsDir . '/*', GLOB_ONLYDIR);
$components = [];

foreach ($dirs as $dir) {
    $name = basename($dir);
    $componentJson = $dir . '/component.json';
    $hasPhp = false;
    $hasHtml = false;
    $description = '';

    if (file_exists($componentJson)) {
        $content = file_get_contents($componentJson);
        if (strncmp($content, "\xEF\xBB\xBF", 3) === 0) {
            $content = substr($content, 3);
        }
        $cfg = json_decode($content, true);
        if ($cfg) {
            $description = $cfg['description'] ?? '';
            foreach ($cfg['assets']['php'] ?? [] as $f) {
                if (file_exists($dir . '/' . $f)) $hasPhp = true;
            }
            foreach ($cfg['assets']['html'] ?? [] as $f) {
                if (file_exists($dir . '/' . $f)) $hasHtml = true;
            }
        }
    }

    $components[] = [
        'name' => $name,
        'description' => $description,
        'has_php' => $hasPhp,
        'has_html' => $hasHtml,
        'dir' => $name,
    ];
}

usort($components, fn($a, $b) => strcmp($a['name'], $b['name']));

echo json_encode(['ok' => true, 'components' => $components], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
