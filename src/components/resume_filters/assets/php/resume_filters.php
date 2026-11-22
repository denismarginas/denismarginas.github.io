<?php

class resume_filters
{
    private const DEFAULT_SORT_FIELDS = [
        ['label' => 'Publish Date', 'by' => 'date.publish', 'type' => 'date', 'order' => 'desc'],
        ['label' => 'Title', 'by' => 'seo.title', 'type' => 'text', 'order' => 'asc'],
    ];

    public static function render(array $data = []): string
    {
        $target = trim((string) ($data['id'] ?? '')) ?: 'resumes';
        $items = array_values(array_filter(resume_listing::items(array_merge($data, ['max_items' => 0])), 'is_array'));
        if (empty($items)) return '';

        $filters = self::filters($data, $items);
        $filtersHtml = '';
        foreach ($filters as $filter) {
            $filtersHtml .= self::select($target, $filter);
        }

        $count = count($items);
        $placeholder = self::text($data, 'search_placeholder', 'Search resumes...');
        $toolbar = filters_toolbar::render($data, [
            'target' => $target,
            'input' => [
                'class' => 'item-search',
                'name' => 'keywords',
                'placeholder' => $placeholder,
                'aria-label' => $placeholder,
                'autocomplete' => 'off',
            ],
            'results_text' => filters_toolbar::results_text(self::text($data, 'results_text', 'Showing {n} resumes.'), $count),
            'count' => $count,
            'filters' => $filtersHtml,
            'sort' => (string) filters_sort::render(array_merge($data, [
                'target' => $target,
                'sort_fields' => self::sort_fields($data),
            ], filters_toolbar::sort_labels($data))),
        ]);

        return PlatformTemplateRenderer::render(__DIR__ . '/../html/template.html', [
            'form_id' => self::e($target . '-filters'),
            'target' => self::e($target),
            'toolbar' => $toolbar,
            'empty_text' => self::e(self::text($data, 'empty_text', 'No resumes match the filters.')),
            'index' => self::index($data, $items, $filters),
        ]);
    }

    private static function filters(array $data, array $items): array
    {
        $keys = array_values(array_filter(array_map('strval', (array) ($data['filters'] ?? ['lang', 'model']))));
        $out = [];
        foreach ($keys as $key) {
            $options = [];
            foreach ($items as $item) {
                $value = self::value($item, $key);
                if ($value === '' || isset($options[$value])) continue;
                $options[$value] = self::option_label($key, $value, $data);
            }
            if (count($options) < 2) continue;
            if ($key === 'model') krsort($options, SORT_NATURAL);
            $out[] = [
                'key' => $key,
                'label' => $key === 'lang' ? self::text($data, 'lang_label', 'Language') : self::text($data, 'model_label_filter', 'Model'),
                'placeholder' => (string) ($data[$key . '_placeholder'] ?? ''),
                'options' => $options,
            ];
        }
        return $out;
    }

    private static function value(array $item, string $key): string
    {
        $d = is_array($item['data'] ?? null) ? $item['data'] : [];
        if ($key === 'lang') return strtolower(trim((string) ($d['lang'] ?? $item['settings']['lang'] ?? '')));
        $value = $d[$key] ?? '';
        return is_scalar($value) ? trim((string) $value) : '';
    }

    private static function option_label(string $key, string $value, array $data): string
    {
        if ($key === 'lang') {
            $labels = is_array($data['lang_labels'] ?? null) ? $data['lang_labels'] : [];
            return (string) ($labels[$value] ?? strtoupper($value));
        }
        if ($key === 'model') {
            return str_replace('{n}', $value, self::text($data, 'model_label', 'Model {n}'));
        }
        return $value;
    }

    private static function select(string $target, array $filter): string
    {
        $id = $target . '-' . $filter['key'];
        $options = '<option value="">' . self::e(filters_toolbar::placeholder($filter['placeholder'])) . '</option>';
        foreach ($filter['options'] as $value => $label) {
            $options .= '<option value="' . self::e((string) $value) . '">' . self::e($label) . '</option>';
        }
        return '<div class="filter">'
            . '<label for="' . self::e($id) . '">' . self::e(filters_toolbar::label($filter['label'])) . '</label>'
            . '<select id="' . self::e($id) . '" name="' . self::e($filter['key']) . '" data-filter="' . self::e($filter['key']) . '">' . $options . '</select>'
            . '</div>';
    }

    private static function sort_fields(array $data): array
    {
        $fields = $data['sort_fields'] ?? null;
        if (is_array($fields) && $fields !== []) return $fields;
        return array_map(fn (array $f): array => array_merge($f, [
            'label' => PlatformTextService::ui('sort_' . str_replace('.', '_', $f['by']), $f['label']),
        ]), self::DEFAULT_SORT_FIELDS);
    }

    private static function index(array $data, array $items, array $filters): string
    {
        $sortData = ['sort_fields' => self::sort_fields($data)];
        $index = [];
        foreach ($items as $item) {
            $id = trim((string) ($item['_id'] ?? ''));
            if ($id === '') continue;
            $d = is_array($item['data'] ?? null) ? $item['data'] : [];
            $entry = ['keywords' => mb_strtolower(trim(preg_replace('/\s+/', ' ', implode(' ', [
                $id,
                (string) ($d['seo']['title'] ?? ''),
                (string) ($d['seo']['description'] ?? ''),
                self::option_label('lang', self::value($item, 'lang'), $data),
                self::option_label('model', self::value($item, 'model'), $data),
            ]))))];
            foreach ($filters as $filter) {
                $entry[$filter['key']] = self::value($item, $filter['key']);
            }
            $entry['_sort'] = filters_sort::values($item, $sortData);
            $index[$id] = $entry;
        }
        $json = json_encode($index, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP);
        return $json === false ? '{}' : $json;
    }

    private static function text(array $data, string $key, string $default): string
    {
        $value = $data[$key] ?? null;
        return is_scalar($value) && trim((string) $value) !== '' ? (string) $value : PlatformTextService::ui('resume_' . $key, $default);
    }

    private static function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
