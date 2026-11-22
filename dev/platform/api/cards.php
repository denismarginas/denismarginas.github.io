<?php

header('Content-Type: application/json; charset=UTF-8');

$dataFile = __DIR__ . '/../data/cards.json';

if (!file_exists(dirname($dataFile))) {
    mkdir(dirname($dataFile), 0777, true);
}

if (!file_exists($dataFile)) {
    file_put_contents(
        $dataFile,
        json_encode([
            'cards' => [],
            'links' => [],
            'variables' => [],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
    );
}

function platform_read_state(string $dataFile): array
{
    $raw = file_get_contents($dataFile);
    $decoded = json_decode($raw ?: '', true);

    if (!is_array($decoded)) {
        return [
            'cards' => [],
            'links' => [],
            'variables' => [],
        ];
    }

    return [
        'cards' => array_values($decoded['cards'] ?? []),
        'links' => array_values($decoded['links'] ?? []),
        'variables' => array_values($decoded['variables'] ?? []),
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $state = json_decode($input ?: '', true);

    if (!is_array($state)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'message' => 'Invalid JSON payload']);
        exit;
    }

    $payload = [
        'cards' => array_values($state['cards'] ?? []),
        'links' => array_values($state['links'] ?? []),
        'variables' => array_values($state['variables'] ?? []),
    ];

    file_put_contents($dataFile, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);

    echo json_encode(['ok' => true]);
    exit;
}

echo json_encode(platform_read_state($dataFile), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
