<?php

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../core/autoload.php';
require_once __DIR__ . '/posts_helpers.php';

function platform_content_list_send(array $data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}

$kind = (string) ($_GET['kind'] ?? '');
$type = (string) ($_GET['type'] ?? '');
$types = platform_get_content_types() ?? [];
$config = $types[$kind][$type] ?? null;

if (!is_array($config) || !isset(platform_content_kinds()[$kind])) {
    platform_content_list_send(['ok' => false, 'message' => 'Unknown content type ' . $kind . ' / ' . $type], 404);
    exit;
}

$entries = platform_read_data_file((string) ($config['file'] ?? '')) ?? [];
$routable = $kind === 'collection' ? ($config['routable'] ?? false) === true : ($config['routable'] ?? true) !== false;

$rows = [];
foreach ($entries as $entry) {
    if (!is_array($entry)) continue;
    $rows[] = platform_content_row($entry, $kind, $type, $routable, $config['title_path'] ?? null);
}

$labels = platform_content_type_labels($kind, $type);

platform_content_list_send([
    'ok'       => true,
    'kind'     => $kind,
    'type'     => $type,
    'label'    => $labels['plural'] !== '' ? $labels['plural'] : (string) ($config['label'] ?? $type),
    'singular' => $labels['singular'] !== '' ? $labels['singular'] : (string) ($config['label'] ?? $type),
    'file'     => (string) ($config['file'] ?? ''),
    'routable' => $routable,
    'rows'     => $rows,
]);
