<?php

require_once __DIR__ . '/../core/autoload.php';

if (!defined('ENGINE_PROJECT_ROOT')) {
    define('ENGINE_PROJECT_ROOT', dirname(__DIR__, 3));
}

$type = $_GET['type'] ?? 'img';
$path = $_GET['path'] ?? '';

if (!$path) {
    http_response_code(400);
    exit;
}

$baseDir = $type === 'vid'
    ? (ENGINE_PROJECT_ROOT . '/src/content/vid/')
    : (ENGINE_PROJECT_ROOT . '/src/content/img/');

$clean = str_replace(['..', '\\'], '', $path);
$fullPath = realpath($baseDir . $clean);

if ($fullPath === false || strpos($fullPath, realpath($baseDir)) !== 0 || !file_exists($fullPath)) {
    http_response_code(404);
    header('Content-Type: text/plain');
    echo 'IMG Not Found';
    exit;
}

$ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
$mimeMap = [
    'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
    'gif' => 'image/gif', 'webp' => 'image/webp', 'avif' => 'image/avif',
    'svg' => 'image/svg+xml', 'ico' => 'image/x-icon',
    'mp4' => 'video/mp4', 'webm' => 'video/webm',
];
$mime = $mimeMap[$ext] ?? 'application/octet-stream';

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($fullPath));
header('Cache-Control: public, max-age=86400');
readfile($fullPath);
