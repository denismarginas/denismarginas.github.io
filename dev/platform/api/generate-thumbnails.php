<?php

header('Content-Type: application/json; charset=UTF-8');

if (!defined('ENGINE_PROJECT_ROOT')) {
    define('ENGINE_PROJECT_ROOT', dirname(__DIR__, 3));
}

require_once ENGINE_PROJECT_ROOT . '/dev/engine/bootstrap.php';

ini_set('display_errors', '0');

@set_time_limit(120);

function platform_thumbnails_send_json(array $data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $input = json_decode(file_get_contents('php://input') ?: '', true);
    if (!is_array($input) || trim((string) ($input['path'] ?? '')) === '') {
        platform_thumbnails_send_json(['ok' => false, 'message' => 'path is required'], 400);
        exit;
    }

    $overwrite = array_key_exists('overwrite', $input) && $input['overwrite'] !== null ? (bool) $input['overwrite'] : null;
    $result = PlatformThumbnailBuilder::generate(
        (string) $input['path'],
        (string) ($input['variant'] ?? 'default'),
        !empty($input['preview']),
        $overwrite
    );
    platform_thumbnails_send_json(['ok' => true] + $result);
    exit;
}

platform_thumbnails_send_json([
    'ok'           => true,
    'requirements' => PlatformThumbnailBuilder::requirements(),
    'settings'     => PlatformThumbnailBuilder::settings(),
    'variants'     => PlatformThumbnailBuilder::VARIANTS,
    'projects'     => PlatformThumbnailBuilder::projects(),
]);
