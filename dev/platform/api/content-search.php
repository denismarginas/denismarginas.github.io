<?php

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../core/autoload.php';
require_once __DIR__ . '/posts_helpers.php';

function platform_content_search_send(array $data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
}

function platform_content_search_clean(string $value): string
{
    $value = html_entity_decode(strip_tags(str_replace(['<br', '</p>', '</li>'], [' <br', ' </p>', ' </li>'], $value)), ENT_QUOTES, 'UTF-8');
    return trim((string) preg_replace('/\s+/u', ' ', $value));
}

function platform_content_search_cut(string $value, int $max): string
{
    return mb_strlen($value) > $max ? rtrim(mb_substr($value, 0, $max - 1)) . '…' : $value;
}

function platform_content_search_strings($value, array &$out, int &$length, int $max): void
{
    if ($length >= $max) return;
    if (is_array($value)) {
        foreach ($value as $key => $child) {
            if (is_string($key) && in_array($key, ['component', 'variant', 'class', 'classes', 'icon', 'svg', 'img', 'image', 'video', 'url', 'link', 'href', 'path', 'color', 'colors'], true) && !is_array($child)) continue;
            platform_content_search_strings($child, $out, $length, $max);
        }
        return;
    }
    if (!is_string($value)) return;
    $text = trim($value);
    if ($text === '' || preg_match('~^(https?:|mailto:|tel:|/|\.\.?/|#[0-9a-f]{3,8}$|[\w./-]+\.(webp|png|jpe?g|gif|svg|ico|mp4|webm|pdf|json|css|js|html)$)~i', $text)) return;
    $text = platform_content_search_clean($text);
    if ($text === '') return;
    $out[] = $text;
    $length += mb_strlen($text) + 1;
}

$get = static fn (string $path, string $default = ''): string => PlatformData::getString($path, $default);
$kindLabels = [
    'post'       => $get('contentTypes.posts', 'Posts'),
    'taxonomy'   => $get('contentTypes.taxonomies', 'Taxonomies'),
    'collection' => $get('contentTypes.collections', 'Collections'),
];

$types = platform_get_content_types() ?? [];
$rows = [];
$counts = ['post' => 0, 'taxonomy' => 0, 'collection' => 0];

foreach (platform_content_kinds() as $kind => $page) {
    foreach (($types[$kind] ?? []) as $type => $config) {
        if (!is_array($config)) continue;
        $type = (string) $type;
        $entries = platform_read_data_file((string) ($config['file'] ?? '')) ?? [];
        $routable = $kind === 'collection' ? ($config['routable'] ?? false) === true : ($config['routable'] ?? true) !== false;
        $labels = platform_content_type_labels($kind, $type);
        $typeLabel = $labels['singular'] !== '' ? $labels['singular'] : (string) ($config['label'] ?? $type);

        foreach ($entries as $entry) {
            if (!is_array($entry)) continue;
            $row = platform_content_row($entry, $kind, $type, $routable, $config['title_path'] ?? null);
            $data = is_array($entry['data'] ?? null) ? $entry['data'] : [];

            $description = '';
            foreach ([$data['seo']['description'] ?? null, $entry['seo']['description'] ?? null, $data['description'] ?? null, $data['excerpt'] ?? null, $data['summary'] ?? null] as $candidate) {
                if (is_scalar($candidate) && trim((string) $candidate) !== '') { $description = platform_content_search_cut(platform_content_search_clean((string) $candidate), 160); break; }
            }

            $parts = [];
            $length = 0;
            platform_content_search_strings([$entry['data'] ?? [], $entry['content'] ?? []], $parts, $length, 3000);

            $row['kind'] = $kind;
            $row['kind_label'] = $kindLabels[$kind];
            $row['type_label'] = $typeLabel;
            $row['description'] = $description;
            $row['text'] = platform_content_search_cut(implode(' ', $parts), 3000);
            $rows[] = $row;
            $counts[$kind]++;
        }
    }
}

platform_content_search_send(['ok' => true, 'counts' => $counts, 'rows' => $rows]);
