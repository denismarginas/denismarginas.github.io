<?php

header('Content-Type: application/json; charset=UTF-8');

if (!defined('ENGINE_PROJECT_ROOT')) {
    define('ENGINE_PROJECT_ROOT', dirname(__DIR__, 3));
}

require_once ENGINE_PROJECT_ROOT . '/dev/engine/bootstrap.php';

ini_set('display_errors', '0');

@set_time_limit(120);

function platform_resumes_send_json(array $data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $input = json_decode(file_get_contents('php://input') ?: '', true);
    if (!is_array($input) || trim((string) ($input['_id'] ?? '')) === '') {
        platform_resumes_send_json(['ok' => false, 'message' => '_id is required'], 400);
        exit;
    }

    $overwrite = array_key_exists('overwrite', $input) && $input['overwrite'] !== null ? (bool) $input['overwrite'] : null;
    $result = PlatformResumeImageBuilder::save(
        (string) $input['_id'],
        (string) ($input['image'] ?? ''),
        (string) ($input['thumbnail'] ?? ''),
        $overwrite
    );
    platform_resumes_send_json(['ok' => true] + $result);
    exit;
}

platform_resumes_send_json([
    'ok'         => true,
    'settings'   => PlatformResumeImageBuilder::settings(),
    'collection_file' => str_replace('\\', '/', substr(PlatformResumeImageBuilder::collection_file(), strlen(ENGINE_PROJECT_ROOT) + 1)),
    'resumes'    => PlatformResumeImageBuilder::resumes(),
]);
