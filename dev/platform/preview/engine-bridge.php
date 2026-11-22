<?php

error_reporting(E_ALL & ~E_DEPRECATED);

define('ENGINE_PROJECT_ROOT', dirname(__DIR__, 3));

$engineBootstrap = ENGINE_PROJECT_ROOT . '/dev/engine/bootstrap.php';

if (!file_exists($engineBootstrap)) {
    http_response_code(500);
    echo json_encode(['error' => 'Engine not found at ' . $engineBootstrap]);
    exit;
}

require_once $engineBootstrap;

$GLOBALS['url_path'] = '/';
$GLOBALS['urlPath'] = '/';
