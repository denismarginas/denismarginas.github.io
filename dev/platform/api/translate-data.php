<?php

header('Content-Type: application/json; charset=UTF-8');

if (!defined('ENGINE_PROJECT_ROOT')) {
    define('ENGINE_PROJECT_ROOT', dirname(__DIR__, 3));
}

require_once ENGINE_PROJECT_ROOT . '/dev/engine/bootstrap.php';

function platform_translate_send(array $data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}

function platform_translate_language_modes(): array
{
    $modes = PlatformPathService::language_modes();
    $default = PlatformDataService::default_language();
    $rows = [];
    foreach (PlatformPathService::languages() as $iso => $name) {
        if ($iso === $default) continue;
        $rows[] = ['iso' => $iso, 'name' => $name, 'mode' => $modes[$iso] ?? 'static'];
    }
    return ['default' => $default, 'list' => $rows];
}

function platform_translate_save_modes(array $modes): array
{
    $path = PlatformConfig::get('data_dir') . '/settings/data_settings_languages.json';
    $data = is_file($path) ? json_decode((string) file_get_contents($path), true) : null;
    if (!is_array($data) || !is_array($data['list'] ?? null)) {
        throw new RuntimeException('data_settings_languages.json has no "list".');
    }
    $default = $data['default'] ?? 'en';
    foreach ($data['list'] as &$item) {
        $iso = strtolower(trim((string) ($item['iso'] ?? '')));
        if ($iso === '' || $iso === $default || !array_key_exists($iso, $modes)) continue;
        $item['mode'] = $modes[$iso] === 'frontend' ? 'frontend' : 'static';
    }
    unset($item);
    file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n");
    PlatformDataService::clear_cache();
    return platform_translate_language_modes();
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    if (($_GET['view'] ?? '') === 'modes') {
        platform_translate_send(['ok' => true, 'modes' => platform_translate_language_modes()]);
        exit;
    }
    if (($_GET['view'] ?? '') === 'rules') {
        platform_translate_send(['ok' => true, 'rules' => PlatformTranslationBuilder::rules(), 'keys' => PlatformTranslationBuilder::key_report()]);
        exit;
    }
    platform_translate_send(['ok' => true] + PlatformTranslationBuilder::status());
    exit;
}

$input = json_decode(file_get_contents('php://input') ?: '', true);
if (!is_array($input)) $input = [];

if (($input['action'] ?? '') === 'modes') {
    try {
        platform_translate_send(['ok' => true, 'modes' => platform_translate_save_modes(is_array($input['modes'] ?? null) ? $input['modes'] : [])]);
    } catch (Throwable $e) {
        platform_translate_send(['ok' => false, 'message' => $e->getMessage()], 422);
    }
    exit;
}

if (($input['action'] ?? '') === 'rules') {
    $rules = PlatformTranslationBuilder::save_rules(is_array($input['rules'] ?? null) ? $input['rules'] : []);
    platform_translate_send(['ok' => true, 'rules' => $rules]);
    exit;
}
$lang = trim((string) ($input['lang'] ?? ''));
$file = trim((string) ($input['file'] ?? ''));
$budget = max(5.0, min(120.0, (float) ($input['budget'] ?? 20)));

if ($lang === '' || $file === '') {
    platform_translate_send(['ok' => false, 'message' => 'lang and file are required'], 400);
    exit;
}

$result = PlatformTranslationBuilder::run_file($lang, $file, $budget, !empty($input['overwrite']), (string) ($input['run'] ?? ''));
platform_translate_send($result, $result['ok'] ? 200 : 422);
