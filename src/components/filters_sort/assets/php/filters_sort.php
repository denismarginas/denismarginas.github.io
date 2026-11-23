<?php

class filters_sort
{
    private const FIELDS_COUNT = 2;

    private const GRID_MAX = 4;

    private const ORDER_LABELS = [
        'text' => ['asc' => 'A-Z', 'desc' => 'Z-A'],
        'number' => ['asc' => 'Low-High', 'desc' => 'High-Low'],
        'date' => ['asc' => 'Oldest', 'desc' => 'Newest'],
    ];

    private static function order_labels(string $type): array
    {
        $labels = self::ORDER_LABELS[$type] ?? self::ORDER_LABELS['text'];
        return [
            'asc' => PlatformTextService::ui('sort_' . $type . '_asc', $labels['asc']),
            'desc' => PlatformTextService::ui('sort_' . $type . '_desc', $labels['desc']),
        ];
    }

    public static function render(array $data = []): string
    {
        $target = trim((string) ($data['target'] ?? ''));
        $fields = self::fields($data);
        if ($target === '' || empty($fields)) return '';

        $default = trim((string) ($data['sort_default'] ?? ''));
        $options = $default !== '' ? '<option value="" data-type="" data-order="">' . self::e($default) . '</option>' : '';
        foreach ($fields as $field) {
            $options .= '<option value="' . self::e($field['by']) . '"'
                . ' data-type="' . self::e($field['type']) . '"'
                . ' data-order="' . self::e($field['order']) . '"'
                . ' data-label-asc="' . self::e($field['labels']['asc']) . '"'
                . ' data-label-desc="' . self::e($field['labels']['desc']) . '">'
                . self::e($field['label']) . '</option>';
        }

        $first = $default !== '' ? null : $fields[0];
        $labels = $first['labels'] ?? self::order_labels('text');

        return PlatformTemplateRenderer::render(__DIR__ . '/../html/template.html', [
            'target' => self::e($target),
            'grid_attrs' => self::grid_attrs(self::FIELDS_COUNT, $data, 'sort'),
            'by_id' => self::e($target . '-sort-by'),
            'order_id' => self::e($target . '-sort-order'),
            'by_label' => self::e((string) ($data['sort_by_label'] ?? 'Sort by:')),
            'order_label' => self::e((string) ($data['sort_order_label'] ?? 'Order:')),
            'by_options' => $options,
            'by_attrs' => select_box_search::attrs(),
            'order_attrs' => select_box_search::attrs(['search' => false]),
            'asc_label' => self::e($labels['asc']),
            'desc_label' => self::e($labels['desc']),
            'desc_selected' => ($first['order'] ?? 'asc') === 'desc' ? ' selected' : '',
            'order_disabled' => $first === null ? ' disabled' : '',
        ]);
    }

    public static function grid_attrs(int $count, array $data, string $prefix): string
    {
        if ($count <= 0) return '';

        $balanced = $data[$prefix . '_column_balancing'] ?? !array_key_exists($prefix . '_grid_max', $data);
        if ($balanced === true) {
            return (string) PlatformComponentRenderer::value('utility_grid_column_balancing', ['count' => $count]);
        }

        $max = (int) ($data[$prefix . '_grid_max'] ?? self::GRID_MAX);
        if ($max < 1) return '';

        return (string) PlatformComponentRenderer::value('utility_grid_listing', ['count' => $count, 'max' => $max]);
    }

    public static function toggle_button(array $data = []): string
    {
        if (empty(self::fields($data))) return '';
        $expanded = ($data['sort_expanded'] ?? false) === true ? 'true' : 'false';
        $icon = (string) PlatformComponentRenderer::render('svg', ['icon' => 'sort', 'class' => 'filters-icon']);
        return '<button type="button" class="icon toggle-sort" aria-expanded="' . $expanded . '" aria-label="Sort">' . $icon . '</button>';
    }

    public static function hidden_attr(array $data = []): string
    {
        return ($data['sort_expanded'] ?? false) === true ? '' : ' hidden';
    }

    public static function values(array $item, array $data): array
    {
        $out = [];
        foreach (self::fields($data) as $field) {
            $value = self::path($item, $field['by']);
            if (is_array($value)) $value = reset($value);
            if (!is_scalar($value) || trim((string) $value) === '') {
                $out[$field['by']] = null;
                continue;
            }
            $value = trim((string) $value);
            if ($field['type'] === 'number') {
                $out[$field['by']] = preg_match('/-?\d+(?:[.,]\d+)?/', $value, $m) ? (float) str_replace(',', '.', $m[0]) : null;
            } elseif ($field['type'] === 'date') {
                $time = strtotime($value);
                $out[$field['by']] = $time === false ? null : $time;
            } else {
                $out[$field['by']] = $value;
            }
        }
        return $out;
    }

    public static function fields(array $data): array
    {
        $raw = $data['sort_fields'] ?? [];
        if (!is_array($raw)) return [];

        $fields = [];
        foreach ($raw as $field) {
            if (!is_array($field)) continue;
            $by = trim((string) ($field['by'] ?? ''));
            if ($by === '') continue;
            $type = strtolower((string) ($field['type'] ?? 'text'));
            if (!isset(self::ORDER_LABELS[$type])) $type = 'text';
            $labels = array_merge(self::order_labels($type), array_map('strval', (array) ($field['order_labels'] ?? [])));
            $fields[] = [
                'by' => $by,
                'label' => (string) ($field['label'] ?? ucfirst($by)),
                'type' => $type,
                'order' => strtolower((string) ($field['order'] ?? ($type === 'text' ? 'asc' : 'desc'))) === 'desc' ? 'desc' : 'asc',
                'labels' => $labels,
            ];
        }
        return $fields;
    }

    private static function path(array $item, string $path): mixed
    {
        foreach ([$item, $item['data'] ?? null] as $source) {
            $value = $source;
            foreach (explode('.', ltrim($path, '@')) as $key) {
                if (!is_array($value) || !array_key_exists($key, $value)) { $value = null; break; }
                $value = $value[$key];
            }
            if ($value !== null) return $value;
        }
        return null;
    }

    private static function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
