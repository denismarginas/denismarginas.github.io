<?php

class search
{
    private const HTML_DIR = __DIR__ . '/../html/';
    private const DEFAULT_SORT_FIELDS = [
        ['label' => 'Name', 'by' => 'title', 'type' => 'text', 'order' => 'asc'],
    ];

    public static function render(array $data = []): string
    {
        $data = listing_toolbar::data($data, 'search');
        $target = trim((string) ($data['id'] ?? '')) ?: 'search-results';
        $text = static fn(string $key, string $default): string => self::e(
            is_scalar($data[$key] ?? null) && trim((string) $data[$key]) !== '' ? (string) $data[$key] : PlatformTextService::ui($key, $default)
        );

        $entries = self::entries();
        $filters = self::filters($data, $entries);
        $descMax = max(10, (int) ($data['description_max'] ?? 100));

        $filtersHtml = '';
        foreach ($filters as $filter) $filtersHtml .= self::filter($target, $filter);

        $sortData = array_merge($data, [
            'target' => $target,
            'sort_fields' => $data['sort_fields'] ?? self::DEFAULT_SORT_FIELDS,
        ], filters_toolbar::sort_labels($data));
        $sortHtml = (string) filters_sort::render($sortData);

        $items = '';
        $index = [];
        foreach ($entries as $entry) {
            $items .= self::item($entry, $descMax);
            $row = ['keywords' => $entry['keywords'], '_sort' => ['title' => $entry['title']]];
            foreach ($filters as $filter) $row[$filter['_id']] = [(string) ($entry[$filter['field']] ?? '')];
            $index[$entry['id']] = $row;
        }

        $showAll = $text('results_show_all', 'Show all');
        $toolbar = filters_toolbar::render($data, [
            'target' => $target,
            'input' => [
                'id' => $target . '-input',
                'class' => 'item-search',
                'name' => 'q',
                'placeholder' => html_entity_decode($text('search_placeholder', 'Search keywords..'), ENT_QUOTES, 'UTF-8'),
                'aria-label' => html_entity_decode($text('search_label', 'Search'), ENT_QUOTES, 'UTF-8'),
                'autocomplete' => 'off',
                'spellcheck' => 'false',
                'enterkeyhint' => 'search',
            ],
            'view' => filters_toolbar::segment('all', $target, 'eye', html_entity_decode($showAll, ENT_QUOTES, 'UTF-8'), [
                'yes' => html_entity_decode($text('view_yes', 'Yes'), ENT_QUOTES, 'UTF-8'),
                'no' => html_entity_decode($text('view_no', 'No'), ENT_QUOTES, 'UTF-8'),
            ], ($data['results_show_all_active'] ?? false) === true ? 'yes' : 'no'),
            'results_text' => filters_toolbar::results_text(html_entity_decode($text('results_text', 'Showing {n} results.'), ENT_QUOTES, 'UTF-8'), 0),
            'count' => 0,
            'filters' => $filtersHtml,
            'sort' => $sortHtml,
        ]);

        return PlatformTemplateRenderer::render(self::HTML_DIR . 'template.html', [
            'target' => self::e($target),
            'form_id' => self::e($target . '-filters'),
            'min_chars' => (string) max(1, (int) ($data['search_min_chars'] ?? $data['min_chars'] ?? 2)),
            'hidden' => self::preview_hidden(),
            'filters_number' => (string) count($filters),
            'toolbar' => $toolbar,
            'hint' => $text('results_hint', 'Type at least 2 characters to search, or turn on Show all in Filters.'),
            'no_results' => $text('results_no_results', 'No results for "{query}".'),
            'index' => self::json($index),
            'items' => $items,
        ]);
    }

    private static function entries(): array
    {
        $labels = self::type_labels();
        $out = [];
        foreach (PlatformSearchIndexBuilder::entries() as [$title, $description, $url, $type]) {
            $id = (string) preg_replace('/\.[a-z0-9]+$/i', '', basename((string) $url));
            if ($id === '') continue;
            $typeLabel = $labels[$type] ?? ucwords(str_replace(['_', '-'], ' ', (string) $type));
            $out[] = [
                'id' => $id,
                'title' => (string) $title,
                'description' => (string) $description,
                'url' => PlatformPathService::post_link($id),
                'type' => (string) $type,
                'type_label' => $typeLabel,
                'keywords' => self::fold($title . ' ' . $description),
            ];
        }
        return $out;
    }

    private static function filters(array $data, array $entries): array
    {
        $defs = listing_toolbar::filters($data);
        $include = (array) ($data['filters_include'] ?? []);
        $exclude = (array) ($data['filters_exclude'] ?? []);
        $hideSingle = !array_key_exists('hide_single_option', $data) || (bool) $data['hide_single_option'];
        $typeLabels = self::type_labels();

        $filters = [];
        foreach ($defs as $def) {
            if (!is_array($def)) continue;
            $id = (string) ($def['_id'] ?? '');
            if ($id === '' || ($include && !in_array($id, $include, true)) || in_array($id, $exclude, true)) continue;

            $field = preg_match("/^\[\s*['\"]([a-z_]+)['\"]\s*\]$/i", trim((string) ($def['json-path'] ?? '')), $m) ? $m[1] : '';
            if (!in_array($field, ['type', 'type_label', 'title'], true)) continue;

            $found = [];
            foreach ($entries as $entry) {
                $value = (string) ($entry[$field] ?? '');
                if ($value !== '') $found[$value] = true;
            }
            $values = is_array($def['options'] ?? null) && $def['options']
                ? array_values(array_filter(array_map('strval', $def['options']), fn($v) => isset($found[$v])))
                : array_keys($found);
            if (!isset($def['options'])) usort($values, 'strnatcasecmp');
            if (count($values) < ($hideSingle ? 2 : 1)) continue;

            $optionLabels = is_array($def['option-labels'] ?? null) ? $def['option-labels'] : ($field === 'type' ? $typeLabels : []);
            $options = [];
            foreach ($values as $value) $options[$value] = (string) ($optionLabels[$value] ?? $value);

            $def['field'] = $field;
            $def['options'] = $options;
            $filters[] = $def;
        }
        return $filters;
    }

    private static function filter(string $target, array $filter): string
    {
        $options = '<option value="">' . self::e(filters_toolbar::placeholder($filter['placeholder'] ?? '')) . '</option>';
        foreach ($filter['options'] as $value => $label) {
            $options .= '<option value="' . self::e((string) $value) . '">' . self::e($label) . '</option>';
        }
        return PlatformTemplateRenderer::render(self::HTML_DIR . 'parts/filter.html', [
            'select_id' => self::e($target . '-' . $filter['_id']),
            'filter_id' => self::e((string) $filter['_id']),
            'name' => self::e((string) ($filter['name'] ?? $filter['_id'])),
            'label' => self::e(filters_toolbar::label((string) ($filter['label'] ?? ''))),
            'options' => $options,
        ]);
    }

    private static function item(array $entry, int $descMax): string
    {
        $desc = $entry['description'];
        if (mb_strlen($desc) > $descMax) $desc = rtrim(mb_substr($desc, 0, $descMax - 3)) . '...';
        return PlatformTemplateRenderer::render(self::HTML_DIR . 'parts/item.html', [
            'id' => self::e($entry['id']),
            'type' => self::e($entry['type']),
            'url' => self::e($entry['url']),
            'title' => self::e($entry['title']),
            'description' => $desc !== '' ? "\n    " . '<p class="card-text search-card-text">' . self::e($desc) . '</p>' : '',
        ]);
    }

    private static function type_labels(): array
    {
        $labels = [];
        foreach ((PlatformDataService::get_data('types') ?? []) as $group) {
            foreach ((array) ($group['types'] ?? []) as $key => $type) {
                if (is_array($type) && isset($type['singular'])) $labels[$key] = (string) $type['singular'];
            }
        }
        return $labels;
    }

    private static function preview_hidden(): string
    {
        if (($GLOBALS['render_target'] ?? '') === 'dist') return '';
        if (!str_contains((string) ($_SERVER['REQUEST_URI'] ?? ''), '/dev/platform/preview/')) return '';
        $id = (string) ($_GET['_id'] ?? '');
        return $id === '' ? '' : '<input type="hidden" name="_id" value="' . self::e($id) . '">';
    }

    private static function fold(string $text): string
    {
        $text = mb_strtolower(html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if (class_exists('Normalizer')) {
            $text = (string) preg_replace('/\p{Mn}+/u', '', (string) Normalizer::normalize($text, Normalizer::FORM_D));
        } else {
            $text = strtr($text, [
                'ă' => 'a', 'â' => 'a', 'à' => 'a', 'á' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a',
                'î' => 'i', 'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'ș' => 's', 'ş' => 's', 'ț' => 't', 'ţ' => 't',
                'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e', 'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
                'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ç' => 'c', 'ñ' => 'n',
            ]);
        }
        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }

    private static function json(array $data): string
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP);
        return $json === false ? '{}' : $json;
    }

    private static function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
