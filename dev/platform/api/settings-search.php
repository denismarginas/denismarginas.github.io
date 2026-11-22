<?php

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../core/autoload.php';
require_once __DIR__ . '/posts_helpers.php';
require_once __DIR__ . '/../core/shell.php';

function platform_settings_search_send(array $data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
}

function platform_settings_search_value($value): string
{
    if (is_bool($value)) return $value ? 'true' : 'false';
    if ($value === null) return 'null';
    $text = trim((string) preg_replace('/\s+/u', ' ', strip_tags((string) $value)));
    return $text;
}

function platform_settings_search_label(string $file): string
{
    $name = preg_replace('~\.json$~i', '', basename($file));
    $name = preg_replace('~^data[_-](settings[_-]|content[_-])?~i', '', (string) $name);
    return ucfirst(str_replace(['_', '-'], ' ', (string) $name));
}

function platform_settings_search_walk($value, array $path, array &$leaves, int $max): void
{
    if (count($leaves) >= $max) return;
    if (is_array($value)) {
        if ($value === []) {
            $leaves[] = [$path, $value === [] ? '[]' : ''];
            return;
        }
        foreach ($value as $key => $child) {
            platform_settings_search_walk($child, array_merge($path, [(string) $key]), $leaves, $max);
        }
        return;
    }
    $leaves[] = [$path, platform_settings_search_value($value)];
}

$get = static fn (string $path, string $default = ''): string => PlatformData::getString($path, $default);
$labels = [
    'setting' => $get('settingsSearch.kindSettings', 'Settings'),
    'data'    => $get('settingsSearch.kindData', 'Site data'),
    'tool'    => $get('settingsSearch.kindTools', 'Tools'),
];

$rootDir = ENGINE_PROJECT_ROOT . '/src/content/json/data';
$settingsDir = $rootDir . '/settings';
$languages = is_file($settingsDir . '/data_settings_languages.json') ? json_decode((string) file_get_contents($settingsDir . '/data_settings_languages.json'), true) : [];
$dataDir = $rootDir . '/' . (is_array($languages) && !empty($languages['default']) ? $languages['default'] : 'en');

$contentFiles = [];
foreach ((platform_get_content_types() ?? []) as $kind => $types) {
    foreach ((array) $types as $config) {
        if (is_array($config) && !empty($config['file'])) $contentFiles[strtolower((string) $config['file'])] = true;
    }
}

$files = [];
foreach ([[$settingsDir, 'settings/', 'setting'], [$dataDir, '', 'data']] as [$dir, $prefix, $kind]) {
    if (!is_dir($dir)) continue;
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $item) {
        if (!$item->isFile() || strtolower($item->getExtension()) !== 'json') continue;
        $name = $item->getFilename();
        $relative = str_replace('\\', '/', substr($item->getPathname(), strlen($dir) + 1));
        if ($kind === 'data') {
            if (isset($contentFiles[strtolower($name)]) || isset($contentFiles[strtolower(preg_replace('~\.json$~i', '', $name))])) continue;
            if (preg_match('~^(post|taxonomy|collection)/~i', $relative) || preg_match('~^data_(post|taxonomy|collection)_~i', $name)) continue;
        }
        $files[] = ['path' => $prefix . $relative, 'full' => $item->getPathname(), 'kind' => $kind];
    }
}
usort($files, static fn ($a, $b) => strcmp($a['path'], $b['path']));

$rows = [];
foreach ($files as $file) {
    $content = (string) file_get_contents($file['full']);
    if (strncmp($content, "\xEF\xBB\xBF", 3) === 0) $content = substr($content, 3);
    $data = json_decode($content, true);
    $typeLabel = platform_settings_search_label($file['path']);
    $baseUrl = '?page=settings&file=' . rawurlencode($file['path']);

    $rows[] = [
        '_id'         => $file['path'],
        'title'       => $typeLabel,
        'kind'        => $file['kind'],
        'kind_label'  => $labels[$file['kind']],
        'type_label'  => $get('settingsSearch.file', 'File'),
        'description' => is_array($data) ? count($data) . ' ' . $get('settingsSearch.keys', 'keys') : $get('settingsSearch.invalid', 'Invalid JSON'),
        'text'        => '',
        'edit_url'    => $baseUrl,
        'view_url'    => '',
    ];
    if (!is_array($data)) continue;

    $leaves = [];
    platform_settings_search_walk($data, [], $leaves, 1500);
    foreach ($leaves as [$path, $value]) {
        if ($path === []) continue;
        $dot = implode('.', $path);
        $rows[] = [
            '_id'         => $file['path'],
            'title'       => implode(' › ', $path),
            'kind'        => $file['kind'],
            'kind_label'  => $labels[$file['kind']],
            'type_label'  => $typeLabel,
            'description' => mb_strlen($value) > 160 ? rtrim(mb_substr($value, 0, 159)) . '…' : ($value === '' ? '""' : $value),
            'text'        => mb_strlen($value) > 160 ? mb_substr($value, 0, 1000) : '',
            'edit_url'    => $baseUrl . '&path=' . rawurlencode($dot),
            'view_url'    => '',
        ];
    }
}

foreach (platform_menu() as $key => $item) {
    $entries = [$key => $item];
    if (!empty($item['children']) && $key !== 'content-types') $entries = array_merge($entries, $item['children']);
    if ($key === 'content-types') continue;
    foreach ($entries as $entry) {
        $rows[] = [
            '_id'         => (string) $entry['url'],
            'title'       => (string) $entry['label'],
            'kind'        => 'tool',
            'kind_label'  => $labels['tool'],
            'type_label'  => (string) ($item['label'] ?? ''),
            'description' => (string) ($entry['desc'] ?? ''),
            'text'        => '',
            'edit_url'    => (string) $entry['url'],
            'view_url'    => '',
        ];
    }
}
$rows[] = [
    '_id'         => '?page=settings',
    'title'       => $get('settingsSearch.performanceMonitor', 'Performance monitor'),
    'kind'        => 'tool',
    'kind_label'  => $labels['tool'],
    'type_label'  => $get('home.settings', 'Settings'),
    'description' => $get('settingsSearch.performanceMonitorDesc', 'FPS, frame times, long tasks and layout shifts on preview pages.'),
    'text'        => 'platform tools fps performance',
    'edit_url'    => '?page=settings',
    'view_url'    => '',
];

platform_settings_search_send(['ok' => true, 'rows' => $rows]);
