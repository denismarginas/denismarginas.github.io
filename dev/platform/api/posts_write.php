<?php

require_once __DIR__ . '/posts_helpers.php';

function platform_handle_post_save(): void
{
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || empty($input['_id']) || empty($input['file']) || !isset($input['data'])) {
        platform_send_json(['ok' => false, 'message' => 'Missing _id, file, or data'], 400);
        return;
    }

    $create = ($input['create'] ?? false) === true;
    $items = platform_read_data_file($input['file']);
    if ($items === null && $create && file_exists(platform_find_data_file($input['file']))) {
        $items = [];
    }
    if ($items === null || (!$items && !$create)) {
        platform_send_json(['ok' => false, 'message' => 'Data file not found'], 404);
        return;
    }

    if ($create) {
        foreach ($items as $item) {
            if (($item['_id'] ?? '') === $input['_id']) {
                platform_send_json(['ok' => false, 'message' => 'A post with _id "' . $input['_id'] . '" already exists'], 409);
                return;
            }
        }
        $items[] = $input['data'];
    } else {
        $lookupId = ($input['original__id'] ?? '') !== '' ? $input['original__id'] : $input['_id'];

        $found = false;
        foreach ($items as $i => $item) {
            if (($item['_id'] ?? '') === $lookupId) {
                $items[$i] = $input['data'];
                $found = true;
                break;
            }
        }

        if (!$found) {
            platform_send_json(['ok' => false, 'message' => 'Post not found in file'], 404);
            return;
        }
    }

    if (platform_write_data_file($input['file'], $items)) {
        platform_send_json(['ok' => true, 'message' => 'Post saved']);
    } else {
        platform_send_json(['ok' => false, 'message' => 'Failed to write file'], 500);
    }
}

function platform_handle_post_delete(): void
{
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || empty($input['_id']) || empty($input['file'])) {
        platform_send_json(['ok' => false, 'message' => 'Missing _id or file'], 400);
        return;
    }

    $items = platform_read_data_file($input['file']);
    if (!$items) {
        platform_send_json(['ok' => false, 'message' => 'Data file not found'], 404);
        return;
    }

    $found = false;
    foreach ($items as $i => $item) {
        if (($item['_id'] ?? '') === $input['_id']) {
            array_splice($items, $i, 1);
            $found = true;
            break;
        }
    }

    if (!$found) {
        platform_send_json(['ok' => false, 'message' => 'Post not found'], 404);
        return;
    }

    if (platform_write_data_file($input['file'], $items)) {
        platform_send_json(['ok' => true, 'message' => 'Post deleted']);
    } else {
        platform_send_json(['ok' => false, 'message' => 'Failed to write file'], 500);
    }
}