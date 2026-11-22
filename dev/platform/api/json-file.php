<?php

header('Content-Type: application/json; charset=UTF-8');

$rootDir = __DIR__ . '/../../../src/content/json/data';
$settingsDir = $rootDir . '/settings';

$default = 'en';
if (file_exists($settingsDir . '/data_settings_languages.json')) {
    $settings = json_decode(file_get_contents($settingsDir . '/data_settings_languages.json'), true);
    $default = $settings['default'] ?? 'en';
}
$dataDir = $rootDir . '/' . $default;

function platform_send_json(array $data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}

function platform_resolve_json_file(string $rawPath, string $settingsDir, string $dataDir): ?string
{
    $rawPath = str_replace('\\', '/', ltrim($rawPath, '/\\'));
    if ($rawPath === '' || str_contains($rawPath, '../') || str_contains($rawPath, '..\\')) {
        return null;
    }
    if (str_starts_with($rawPath, 'settings/')) {
        $candidate = $settingsDir . '/' . substr($rawPath, strlen('settings/'));
        return is_file($candidate) ? $candidate : null;
    }
    $candidate = $dataDir . '/' . $rawPath;
    if (is_file($candidate)) {
        return $candidate;
    }
    foreach (glob($dataDir . '/*', GLOB_ONLYDIR) as $subDir) {
        $nested = $subDir . '/' . $rawPath;
        if (is_file($nested)) {
            return $nested;
        }
    }
    return null;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $rawPath = $_GET['path'] ?? '';
    $full = platform_resolve_json_file($rawPath, $settingsDir, $dataDir);
    if (!$full) {
        platform_send_json(['ok' => false, 'message' => 'File not found: ' . $rawPath], 404);
        exit;
    }
    $content = file_get_contents($full);
    if (strncmp($content, "\xEF\xBB\xBF", 3) === 0) {
        $content = substr($content, 3);
    }
    $decoded = json_decode($content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        platform_send_json(['ok' => false, 'message' => 'Invalid JSON: ' . json_last_error_msg()], 400);
        exit;
    }
    platform_send_json(['ok' => true, 'path' => $rawPath, 'content' => $decoded]);
    exit;
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input') ?: '', true);
    $rawPath = $input['path'] ?? '';
    $content = $input['content'] ?? null;
    if (!is_array($input) || $rawPath === '' || $content === null) {
        platform_send_json(['ok' => false, 'message' => 'Missing path or content'], 400);
        exit;
    }
    $full = platform_resolve_json_file($rawPath, $settingsDir, $dataDir);
    if (!$full) {
        platform_send_json(['ok' => false, 'message' => 'File not found: ' . $rawPath], 404);
        exit;
    }
    $json = json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        platform_send_json(['ok' => false, 'message' => 'Invalid JSON content'], 400);
        exit;
    }
    if (file_put_contents($full, $json, LOCK_EX) === false) {
        platform_send_json(['ok' => false, 'message' => 'Write failed'], 500);
        exit;
    }
    platform_send_json(['ok' => true, 'path' => $rawPath]);
    exit;
}

platform_send_json(['ok' => false, 'message' => 'Method not allowed'], 405);
