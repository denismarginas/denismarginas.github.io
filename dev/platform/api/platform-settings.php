<?php

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../core/autoload.php';

function platform_settings_send(array $data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    platform_settings_send(['ok' => true, 'settings' => PlatformSettings::all()]);
    exit;
}

if ($method === 'POST') {
    $input = json_decode((string) file_get_contents('php://input'), true);
    if (is_array($input) && is_array($input['tools'] ?? null)) {
        $tools = PlatformSettings::save_tools($input['tools']);
        if ($tools === null) {
            platform_settings_send(['ok' => false, 'message' => 'Could not write data/settings.json'], 500);
            exit;
        }
        platform_settings_send(['ok' => true, 'tools' => $tools]);
        exit;
    }
    if (!is_array($input) || !is_array($input['listing'] ?? null)) {
        platform_settings_send(['ok' => false, 'message' => 'Expected { "listing": { ... } } or { "tools": { ... } }'], 400);
        exit;
    }
    $listing = PlatformSettings::save_listing($input['listing']);
    if ($listing === null) {
        platform_settings_send(['ok' => false, 'message' => 'Could not write data/settings.json'], 500);
        exit;
    }
    platform_settings_send(['ok' => true, 'listing' => $listing]);
    exit;
}

platform_settings_send(['ok' => false, 'message' => 'Method not allowed'], 405);
