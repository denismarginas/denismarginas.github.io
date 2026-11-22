<?php

if (!defined('ENGINE_PROJECT_ROOT')) {
    define('ENGINE_PROJECT_ROOT', dirname(__DIR__, 3));
}

function platform_get_content_types(): ?array
{
    $typesPath = ENGINE_PROJECT_ROOT . '/src/content/json/data/settings/data_settings_types.json';
    if (!file_exists($typesPath)) {
        return null;
    }
    return json_decode(file_get_contents($typesPath), true);
}

function platform_get_post_types(): array
{
    $types = platform_get_content_types();
    if (!$types || empty($types['post'])) {
        return [];
    }
    $result = [];
    foreach ($types['post'] as $typeKey => $config) {
        $result[] = [
            'post_type' => $typeKey,
            'file' => $config['file'],
            'title' => $config['label'],
            'root' => $config['root'] ?? '',
            'structure' => $config['structure'] ?? '',
            'seo' => $config['seo'] ?? '',
            'routable' => $config['routable'] ?? true,
            'global_content_path' => $config['global_content_path'] ?? '',
            'global_img_path' => $config['global_img_path'] ?? '',
            'global_vid_path' => $config['global_vid_path'] ?? '',
        ];
    }
    return $result;
}

function platform_get_taxonomy_types(): array
{
    $types = platform_get_content_types();
    if (!$types || empty($types['taxonomy'])) {
        return [];
    }
    $result = [];
    foreach ($types['taxonomy'] as $typeKey => $config) {
        $result[] = [
            'taxonomy' => $typeKey,
            'file' => $config['file'],
            'title' => $config['label'],
            'root' => $config['root'] ?? '',
            'routable' => $config['routable'] ?? true,
            'post_types' => $config['post_types'] ?? [],
        ];
    }
    return $result;
}

function platform_get_collection_types(): array
{
    $types = platform_get_content_types();
    if (!$types || empty($types['collection'])) {
        return [];
    }
    $result = [];
    foreach ($types['collection'] as $typeKey => $config) {
        $result[] = [
            'collection_type' => $typeKey,
            'title_path' => $config['title_path'] ?? '',
            'file' => $config['file'],
            'title' => $config['label'],
            'routable' => ($config['routable'] ?? false) === true,
            'root' => $config['root'] ?? '',
            'structure' => $config['structure'] ?? '',
            'seo' => $config['seo'] ?? '',
            'global_content_path' => $config['global_content_path'] ?? '',
            'global_img_path' => $config['global_img_path'] ?? '',
            'global_vid_path' => $config['global_vid_path'] ?? '',
        ];
    }
    return $result;
}

function platform_get_routable_collection_types(): array
{
    $result = [];
    foreach (platform_get_collection_types() as $t) {
        if (!$t['routable']) continue;
        $result[] = [
            'post_type' => $t['collection_type'],
            'kind' => 'collection',
            'file' => $t['file'],
            'title' => $t['title'],
            'root' => $t['root'],
            'structure' => $t['structure'],
            'seo' => $t['seo'],
            'routable' => true,
            'global_content_path' => $t['global_content_path'],
            'global_img_path' => $t['global_img_path'],
            'global_vid_path' => $t['global_vid_path'],
        ];
    }
    return $result;
}

function platform_content_kinds(): array
{
    return [
        'post'       => 'edit-posts',
        'taxonomy'   => 'edit-taxonomies',
        'collection' => 'edit-collections',
    ];
}

function platform_content_list_url(string $kind, string $type = ''): string
{
    return '?page=content&kind=' . rawurlencode($kind) . ($type !== '' ? '&type=' . rawurlencode($type) : '');
}

function platform_content_edit_url(string $kind, string $type, string $id = ''): string
{
    $page = platform_content_kinds()[$kind] ?? 'edit-posts';
    return '?page=' . $page . '&type=' . rawurlencode($type) . ($id !== '' ? '&_id=' . rawurlencode($id) : '&new=1');
}

function platform_content_type_labels(string $kind, string $type): array
{
    static $labels = null;
    if ($labels === null) {
        $path = platform_default_data_dir() . '/data_types.json';
        $decoded = file_exists($path) ? json_decode((string) file_get_contents($path), true) : null;
        $labels = is_array($decoded) ? $decoded : [];
    }
    $entry = $labels[$kind]['types'][$type] ?? [];
    return [
        'singular' => (string) ($entry['singular'] ?? ''),
        'plural'   => (string) ($entry['plural'] ?? ''),
    ];
}

function platform_content_title(array $entry, $titlePath = null): string
{
    $paths = is_array($titlePath) ? $titlePath : (is_string($titlePath) && $titlePath !== '' ? [$titlePath] : []);
    $paths = array_merge($paths, ['data.seo.title', 'data.title', 'data.name', 'title', 'name', 'text']);

    foreach ($paths as $path) {
        $value = $entry;
        foreach (explode('.', (string) $path) as $segment) {
            $value = is_array($value) && array_key_exists($segment, $value) ? $value[$segment] : null;
        }
        if (!is_scalar($value) || trim((string) $value) === '') continue;

        $text = trim((string) preg_replace('/\s+/u', ' ', (string) $value));
        if (mb_strlen($text) > 80) $text = rtrim(mb_substr($text, 0, 77)) . '…';
        return $text;
    }

    $id = trim((string) ($entry['_id'] ?? $entry['post_id'] ?? ''));
    return $id === '' ? '' : ucfirst(trim(preg_replace('/[-_]+/', ' ', $id)));
}

function platform_content_row(array $entry, string $kind, string $type, bool $routable, $titlePath = null): array
{
    $id = (string) ($entry['_id'] ?? $entry['post_id'] ?? '');
    $data = is_array($entry['data'] ?? null) ? $entry['data'] : [];
    $settings = is_array($entry['settings'] ?? null) ? $entry['settings'] : (is_array($data['settings'] ?? null) ? $data['settings'] : []);

    $title = platform_content_title($entry, $titlePath);

    $subtype = '';
    foreach ([$data['project']['types'] ?? null, $data['experience']['type'] ?? null, $data['education']['type'] ?? null, $settings['type'] ?? null] as $candidate) {
        $value = is_array($candidate) ? implode(', ', array_filter(array_map('strval', $candidate))) : (is_string($candidate) ? $candidate : '');
        if ($value !== '' && $value !== $type && $value !== $kind) { $subtype = $value; break; }
    }

    $taxonomies = [];
    $source = is_array($data['taxonomy'] ?? null) ? $data['taxonomy'] : (is_array($entry['taxonomy'] ?? null) ? $entry['taxonomy'] : []);
    foreach ($source as $taxonomy => $terms) {
        $terms = array_values(array_filter(array_map('strval', is_array($terms) ? $terms : [$terms]), fn ($t) => trim($t) !== ''));
        if ($terms !== []) $taxonomies[(string) $taxonomy] = $terms;
    }
    if ($taxonomies === [] && is_array($data['tags'] ?? null)) {
        $taxonomies['tag'] = array_values(array_map('strval', $data['tags']));
    }
    if (is_string($settings['parent'] ?? null) && $settings['parent'] !== '') {
        $taxonomies['parent'] = [$settings['parent']];
    }

    $publish = '';
    foreach ([$data['date']['publish'] ?? null, $entry['date']['publish'] ?? null, $data['publish_date'] ?? null, $entry['publish_date'] ?? null] as $candidate) {
        if (is_scalar($candidate) && trim((string) $candidate) !== '') { $publish = trim((string) $candidate); break; }
    }
    $timestamp = $publish !== '' ? strtotime($publish) : false;

    return [
        '_id'        => $id,
        'title'      => $title,
        'type'       => $type,
        'subtype'    => $subtype,
        'taxonomies' => $taxonomies,
        'publish'    => $publish,
        'publish_ts' => $timestamp !== false ? $timestamp : null,
        'hidden'     => ($settings['render'] ?? true) === false,
        'edit_url'   => platform_content_edit_url($kind, $type, $id),
        'view_url'   => $routable && $id !== '' ? 'preview/?_id=' . rawurlencode($id) : '',
    ];
}

function platform_entry_context(string $kind): array
{
    $types = platform_get_content_types() ?? [];
    $type = (string) ($_GET['type'] ?? '');
    $id = (string) ($_GET['_id'] ?? '');
    $isNew = ($_GET['new'] ?? '') === '1';
    if ($type !== '' && !isset($types[$kind][$type])) $type = '';

    if ($id === '' && !$isNew) {
        header('Location: ' . platform_content_list_url($kind, $type));
        exit;
    }

    $labels = $type !== '' ? platform_content_type_labels($kind, $type) : ['singular' => '', 'plural' => ''];
    $config = $type !== '' ? $types[$kind][$type] : [];
    $plural = $labels['plural'] !== '' ? $labels['plural'] : (string) ($config['label'] ?? '');
    $singular = $labels['singular'] !== '' ? $labels['singular'] : $plural;
    if ($singular !== '') $GLOBALS['_platform_page_title'] = ($isNew ? 'New ' : 'Edit ') . $singular . ($id !== '' ? ' · ' . $id : '');

    return [
        'kind'     => $kind,
        'type'     => $type,
        'id'       => $id,
        'is_new'   => $isNew,
        'list_url' => platform_content_list_url($kind, $type),
        'plural'   => $plural,
    ];
}

function platform_entry_back_link(array $entry): string
{
    $label = $entry['plural'] !== ''
        ? str_replace('{plural}', $entry['plural'], PlatformData::getString('contentListing.backToList', 'All {plural}'))
        : PlatformData::getString('contentListing.pageTitle', 'Content');
    return '<a class="platform-button platform-button-sm platform-button-ghost platform-entry-back" href="' . htmlspecialchars($entry['list_url'], ENT_QUOTES, 'UTF-8') . '">'
        . PlatformSvg::render(['name' => 'chevron-left', 'size' => 14])
        . '<span>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</span></a>';
}

function platform_default_data_dir(): string
{
    $path = ENGINE_PROJECT_ROOT . '/src/content/json/data/settings/data_settings_languages.json';
    $default = 'en';
    if (file_exists($path)) {
        $data = json_decode(file_get_contents($path), true);
        $default = $data['default'] ?? 'en';
    }
    return ENGINE_PROJECT_ROOT . '/src/content/json/data/' . $default;
}

function platform_find_data_file(string $filename): string
{
    $baseDir = platform_default_data_dir();
    $direct = $baseDir . '/' . $filename;
    if (file_exists($direct)) return $direct;
    foreach (glob($baseDir . '/*', GLOB_ONLYDIR) as $subDir) {
        $candidate = $subDir . '/' . $filename;
        if (file_exists($candidate)) return $candidate;
    }
    return $direct;
}

function platform_read_data_file(string $filename): ?array
{
    $path = platform_find_data_file($filename);
    if (!file_exists($path)) return null;
    $content = file_get_contents($path);
    if (strncmp($content, "\xEF\xBB\xBF", 3) === 0) {
        $content = substr($content, 3);
    }
    $data = json_decode($content, true);
    return is_array($data) ? $data : null;
}

function platform_write_data_file(string $filename, array $data): bool
{
    $path = platform_find_data_file($filename);
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    return file_put_contents($path, $json, LOCK_EX) !== false;
}

function platform_find_post_in_file(string $filename, string $id): ?array
{
    $items = platform_read_data_file($filename);
    if (!$items) return null;
    foreach ($items as $i => $item) {
        if (($item['_id'] ?? '') === $id) {
            return ['index' => $i, 'data' => $item, 'file' => $filename];
        }
    }
    return null;
}

function platform_find_taxonomy_term(string $filename, string $id): ?array
{
    $terms = platform_read_data_file($filename);
    if (!$terms) return null;
    foreach ($terms as $i => $term) {
        if (($term['_id'] ?? '') === $id) {
            return ['index' => $i, 'data' => $term, 'file' => $filename];
        }
    }
    return null;
}