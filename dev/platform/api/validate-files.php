<?php

header('Content-Type: application/json; charset=UTF-8');

$baseDir = dirname(__DIR__, 3);
$rawPath = isset($_GET['path']) ? ltrim($_GET['path'], '/\\') : '';
while (strpos($rawPath, '../') === 0) {
    $rawPath = substr($rawPath, 3);
}
$fullPath = realpath($baseDir . '/' . $rawPath);

if (!$fullPath || !is_dir($fullPath)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid or non-existent directory path']);
    exit;
}

$prefixLength = strlen($baseDir) + 1;
$resolved = substr($fullPath, $prefixLength);
$resolved = str_replace('\\', '/', $resolved);

$files = [];
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($fullPath, FilesystemIterator::SKIP_DOTS));

foreach ($iterator as $item) {
    if (!$item->isFile() || strtolower($item->getExtension()) !== 'json') continue;
    $content = file_get_contents($item->getPathname());
    $valid = true;
    $error = '';

    if ($content === false || trim($content) === '') {
        $valid = false;
        $error = 'Empty or unreadable';
    } else {
        if (strncmp($content, "\xEF\xBB\xBF", 3) === 0) {
            $content = substr($content, 3);
        }
        json_decode($content);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $valid = false;
            $error = json_last_error_msg();
        }
    }

    $relative = str_replace('\\', '/', substr($item->getPathname(), strlen($fullPath) + 1));
    $files[] = [
        'name' => $relative,
        'path' => $resolved . '/' . $relative,
        'valid' => $valid,
        'error' => $valid ? '' : $error,
    ];
}

usort($files, function ($a, $b) {
    return strcasecmp($a['name'], $b['name']);
});

echo json_encode(['path' => $resolved, 'files' => $files], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
