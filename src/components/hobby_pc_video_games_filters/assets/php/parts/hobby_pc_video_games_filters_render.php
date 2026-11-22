<?php

class hobby_pc_video_games_filters_render
{
    private const HTML_DIR = __DIR__ . '/../../html/';

    private const DEFAULT_SORT_FIELDS = [
        ['label' => 'Name', 'by' => 'name', 'type' => 'text', 'order' => 'asc'],
        ['label' => 'Rank', 'by' => 'rank', 'type' => 'number', 'order' => 'desc'],
        ['label' => 'Playtime', 'by' => 'playtime', 'type' => 'number', 'order' => 'desc'],
    ];

    public static function form(array $data, string $target, array $filters, array $items): string
    {
        $filtersHtml = '';
        foreach ($filters as $filter) {
            $filtersHtml .= self::filter($target, $filter);
        }

        $sortData = self::sort_data($data, $target);
        $sortHtml = (string) filters_sort::render($sortData);
        $count = count(array_filter($items, fn ($i) => !hobby_pc_video_games_listing_items::is_hidden($i)));
        $placeholder = (string) ($data['search_placeholder'] ?? PlatformTextService::ui('search_games_placeholder', 'Search games...'));

        $toolbar = filters_toolbar::render($data, [
            'target' => $target,
            'input' => [
                'class' => 'item-search',
                'name' => 'keywords',
                'placeholder' => $placeholder,
                'aria-label' => $placeholder,
                'autocomplete' => 'off',
            ],
            'results_text' => filters_toolbar::results_text((string) ($data['results_text'] ?? PlatformTextService::ui('results_text_games', 'Showing {n} games.')), $count),
            'count' => $count,
            'filters' => $filtersHtml,
            'sort' => $sortHtml,
            'view' => self::details_toggle($data, $target),
        ]);

        return PlatformTemplateRenderer::render(self::HTML_DIR . 'template.html', [
            'target' => self::e($target),
            'form_id' => self::e($target . '-filters'),
            'filters_number' => (string) count($filters),
            'toolbar' => $toolbar,
            'index' => self::index($filters, $items),
        ]);
    }

    private static function details_toggle(array $data, string $target): string
    {
        $text = static fn (string $key, string $default): string =>
            is_scalar($data[$key] ?? null) && trim((string) $data[$key]) !== '' ? (string) $data[$key] : PlatformTextService::ui($key, $default);
        return filters_toolbar::segment('details', $target, 'eye', $text('view_details_label', 'Detailed cards'), [
            'yes' => $text('view_yes', 'Yes'),
            'no' => $text('view_no', 'No'),
        ], ($data['view_details'] ?? false) === true ? 'yes' : 'no');
    }

    private static function filter(string $target, array $filter): string
    {
        $placeholder = filters_toolbar::placeholder($filter['placeholder'] ?? '');
        $style = isset($filter['style']) ? ' style="' . self::e((string) $filter['style']) . '"' : '';
        $prefix = (string) ($filter['option-prefix'] ?? '');
        $suffix = (string) ($filter['option-suffix'] ?? '');

        $isDisplay = hobby_pc_video_games_filters_values::is_display($filter);

        $options = '<option value="">' . self::e($placeholder) . '</option>';
        foreach ($filter['options'] as $option) {
            $value = is_array($option) ? (string) $option['value'] : (string) $option;
            $label = is_array($option) ? (string) $option['label'] : $prefix . $value . $suffix;
            $options .= '<option value="' . self::e($value) . '">' . self::e($label) . '</option>';
        }

        return PlatformTemplateRenderer::render(self::HTML_DIR . 'parts/filter.html', [
            'select_id' => self::e($target . '-' . $filter['_id']),
            'filter_id' => self::e((string) $filter['_id']),
            'name' => self::e((string) ($filter['name'] ?? $filter['_id'])),
            'label' => self::e(filters_toolbar::label((string) ($filter['label'] ?? ''))),
            'type' => self::e($isDisplay ? 'display' : hobby_pc_video_games_filters_values::type($filter)),
            'mode' => self::e(hobby_pc_video_games_filters_values::mode($filter)),
            'style' => $style,
            'options' => $options,
        ]);
    }

    private static function sort_data(array $data, string $target): array
    {
        return array_merge($data, [
            'target' => $target,
            'sort_fields' => $data['sort_fields'] ?? self::DEFAULT_SORT_FIELDS,
        ], filters_toolbar::sort_labels($data));
    }

    private static function index(array $filters, array $items): string
    {
        $index = [];
        foreach ($items as $item) {
            $id = hobby_pc_video_games_listing_items::id($item);
            if ($id === '') continue;

            $entry = ['keywords' => self::keywords($item)];
            foreach ($filters as $filter) {
                if (hobby_pc_video_games_filters_values::is_display($filter)) continue;
                $entry[$filter['_id']] = hobby_pc_video_games_filters_values::of($item, $filter);
            }
            $index[$id] = $entry;
        }

        $json = json_encode($index, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP);
        return $json === false ? '{}' : $json;
    }

    private static function keywords(array $item): string
    {
        $parts = [
            hobby_pc_video_games_listing_items::id($item),
            (string) (hobby_pc_video_games_listing_items::value($item, 'name') ?? ''),
            implode(' ', hobby_pc_video_games_listing_items::tags($item)),
            (string) (hobby_pc_video_games_listing_items::value($item, 'release_year') ?? ''),
        ];
        return mb_strtolower(trim(preg_replace('/\s+/', ' ', implode(' ', $parts))));
    }

    private static function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
