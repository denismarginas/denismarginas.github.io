<?php

header('Content-Type: application/json; charset=UTF-8');

$rootDir = __DIR__ . '/../../../src/content/json/data';
$default = 'en';
$settingsPath = $rootDir . '/settings/data_settings_languages.json';
if (file_exists($settingsPath)) {
    $settings = json_decode(file_get_contents($settingsPath), true);
    $default = $settings['default'] ?? 'en';
}
$dataDir = $rootDir . '/' . $default;

if (!is_dir($dataDir)) {
    echo json_encode(['files' => []]);
    exit;
}

$files = [];
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dataDir, FilesystemIterator::SKIP_DOTS));

foreach ($iterator as $item) {
    if ($item->isFile() && strtolower($item->getExtension()) === 'json') {
        $files[] = str_replace('\\', '/', substr($item->getPathname(), strlen($dataDir) + 1));
    }
}

$settingsDir = $rootDir . '/settings';
if (is_dir($settingsDir)) {
    $settingsIterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($settingsDir, FilesystemIterator::SKIP_DOTS));
    foreach ($settingsIterator as $item) {
        if ($item->isFile() && strtolower($item->getExtension()) === 'json') {
            $files[] = 'settings/' . str_replace('\\', '/', substr($item->getPathname(), strlen($settingsDir) + 1));
        }
    }
}

sort($files);

echo json_encode(['files' => $files], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
