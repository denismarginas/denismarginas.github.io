<?php

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../core/autoload.php';
require_once __DIR__ . '/posts_helpers.php';
require_once __DIR__ . '/posts_get.php';
require_once __DIR__ . '/posts_write.php';

if (!defined('ENGINE_PROJECT_ROOT')) {
    define('ENGINE_PROJECT_ROOT', dirname(__DIR__, 3));
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $postId = $_GET['post_id'] ?? '';
    $postType = $_GET['post_type'] ?? '';

    if ($postId) {
        platform_handle_get_post($postId);
    } elseif ($postType) {
        platform_handle_list_posts($postType);
    } else {
        platform_handle_list_types();
    }
    exit;
}

if ($method === 'POST') {
    platform_handle_post_save();
    exit;
}

if ($method === 'DELETE') {
    platform_handle_post_delete();
    exit;
}

platform_send_json(['ok' => false, 'message' => 'Method not allowed'], 405);
