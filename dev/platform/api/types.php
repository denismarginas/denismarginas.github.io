<?php

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../core/autoload.php';
require_once __DIR__ . '/posts_helpers.php';

function platform_send_json(array $data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}

$result = ['post' => [], 'taxonomy' => [], 'collection' => []];

foreach (platform_get_post_types() as $t) {
    $items = platform_read_data_file($t['file']);
    $result['post'][] = [
        'type' => $t['post_type'],
        'label' => $t['title'],
        'file' => $t['file'],
        'routable' => $t['routable'] ?? true,
        'count' => $items ? count($items) : 0,
    ];
}

foreach (platform_get_taxonomy_types() as $t) {
    $items = platform_read_data_file($t['file']);
    $result['taxonomy'][] = [
        'type' => $t['taxonomy'],
        'label' => $t['title'],
        'file' => $t['file'],
        'routable' => $t['routable'] ?? true,
        'count' => $items ? count($items) : 0,
    ];
}

foreach (platform_get_collection_types() as $t) {
    $items = platform_read_data_file($t['file']);
    $result['collection'][] = [
        'type' => $t['collection_type'],
        'label' => $t['title'],
        'file' => $t['file'],
        'routable' => $t['routable'] ?? false,
        'title_path' => $t['title_path'] ?? '',
        'count' => $items ? count($items) : 0,
    ];
}

platform_send_json(['ok' => true, 'types' => $result]);
