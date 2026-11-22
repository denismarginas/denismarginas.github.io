<?php

if (php_sapi_name() !== 'cli' || defined('IN_THEME_COMPILE_BUNDLE')) {
    return;
}

require_once __DIR__ . '/../../../../../dev/engine/bootstrap.php';

$results = PlatformBundleBuilder::build();
$success = count(array_filter($results, fn($r) => $r['success']));

echo json_encode([
    'total' => count($results),
    'success_count' => $success,
    'error_count' => count($results) - $success,
    'results' => $results,
], JSON_PRETTY_PRINT) . "\n";
