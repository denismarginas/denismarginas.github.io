<?php

header('Content-Type: application/json; charset=UTF-8');

if (!defined('ENGINE_PROJECT_ROOT')) {
    define('ENGINE_PROJECT_ROOT', dirname(__DIR__, 3));
}

require_once ENGINE_PROJECT_ROOT . '/dev/engine/bootstrap.php';

function platform_workflow_send_json(array $data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}

$allowedSections = ['render', 'compile_scss', 'live_preview', 'project_structure', 'page_structure', 'seo_project', 'seo_page', 'translation', 'thumbnails', 'resumes', 'seo_files'];

$section = (string) ($_GET['section'] ?? $_GET['card_type'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input') ?: '', true);
    $section = (string) ($input['section'] ?? $input['card_type'] ?? '');
    $variables = $input['variables'] ?? null;

    if ($section === '' || !is_array($variables)) {
        platform_workflow_send_json(['ok' => false, 'message' => 'section and variables are required'], 400);
        exit;
    }

    $sectionData = [];
    foreach ($variables as $var) {
        $name = (string) ($var['name'] ?? '');
        if ($name === '') continue;
        $raw = (string) ($var['value'] ?? '');
        $sectionData[$name] = $raw === 'true' ? true : ($raw === 'false' ? false : $raw);
    }

    $saved = PlatformWorkflowService::save_section($section, $sectionData);
    platform_workflow_send_json(['ok' => $saved, 'section' => $section]);
    exit;
}

if ($section === '') {
    platform_workflow_send_json(['ok' => true, 'sections' => array_values($allowedSections), 'workflow' => PlatformWorkflowService::read()]);
    exit;
}

if (!in_array($section, $allowedSections, true)) {
    platform_workflow_send_json(['ok' => false, 'message' => 'unknown section: ' . $section], 404);
    exit;
}

platform_workflow_send_json(['ok' => true, 'section' => $section, 'variables' => PlatformWorkflowService::vars($section)]);