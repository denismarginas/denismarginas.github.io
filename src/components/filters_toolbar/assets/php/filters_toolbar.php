<?php

class filters_toolbar
{
    public static function render(array $data, array $parts): string
    {
        $target = trim((string) ($parts['target'] ?? ''));
        if ($target === '') return '';

        $filters = (string) ($parts['filters'] ?? '');
        $sort = (string) ($parts['sort'] ?? '');
        $view = self::search_toggle($data, $target)
            . (string) ($parts['view'] ?? '')
            . self::results_toggle($data, $target)
            . self::sticky_toggle($data, $target);
        $count = (int) ($parts['count'] ?? 0);
        $drawerId = $target . '-filters-drawer';
        $text = static fn (string $key, string $default): string => self::text($data, $key, $default);

        $resultsHidden = ($data['results_toggle'] ?? true) !== false && ($data['results_expanded'] ?? true) === false;

        return PlatformTemplateRenderer::render(__DIR__ . '/../html/template.html', [
            'search_id' => self::e($target . '-search'),
            'input_attrs' => self::attrs((array) ($parts['input'] ?? [])),
            'drawer_input_attrs' => self::attrs(self::drawer_input((array) ($parts['input'] ?? []))),
            'drawer_search_hidden' => empty($parts['input']) ? ' hidden' : '',
            'actions' => (string) ($parts['actions'] ?? ''),
            'drawer_id' => self::e($drawerId),
            'drawer_button_hidden' => ($filters === '' && $sort === '' && $view === '') ? ' hidden' : '',
            'results_hidden' => $resultsHidden ? ' hidden' : '',
            'results_text' => (string) ($parts['results_text'] ?? ''),
            'apply_text' => self::results_text($text('results_apply', 'Show {n} results'), $count),
            'filters' => $filters,
            'filters_hidden' => $filters === '' ? ' hidden' : '',
            'sort' => $sort,
            'sort_hidden' => $sort === '' ? ' hidden' : '',
            'view' => $view,
            'view_hidden' => $view === '' ? ' hidden' : '',
            'filters_label' => self::e(self::label($text('filters_label', 'Filters'))),
            'sort_label' => self::e(self::label($text('sort_label', 'Sort'))),
            'view_label' => self::e(self::label($text('view_label', 'View'))),
            'clear_label' => self::e($text('results_clear', 'Clear all')),
            'reset_label' => self::e($text('results_reset', 'Reset')),
            'close_label' => self::e($text('results_close', 'Close')),
            'chips_label' => self::e($text('results_chips', 'Active filters')),
            'remove_label' => self::e($text('results_remove', 'Remove')),
            'icon_search' => self::icon('search'),
            'icon_filter' => self::icon('filter'),
            'icon_close' => self::icon('close'),
        ]);
    }

    public static function segment(string $view, string $target, string $icon, string $label, array $options, string $checked): string
    {
        $label = self::e($label);
        $name = self::e(($target !== '' ? $target : 'listing') . '-view-' . $view);
        $html = '';
        foreach ($options as $value => $text) {
            $html .= '<label class="ft-segment-option"><input type="radio" name="' . $name . '" value="' . self::e((string) $value) . '"'
                . ((string) $value === $checked ? ' checked' : '') . '><span>' . self::e((string) $text) . '</span></label>';
        }

        return '<div class="ft-segment toggle-' . self::e($view) . '" data-view="' . self::e($view) . '" role="radiogroup" aria-label="' . $label . '">'
            . '<span class="ft-segment-label"><span class="ft-segment-icon" aria-hidden="true">' . self::icon($icon) . '</span>' . $label . '</span>'
            . '<span class="ft-segment-options">' . $html . '</span></div>';
    }

    public static function results_toggle(array $data, string $target): string
    {
        if (($data['results_toggle'] ?? true) === false) return '';
        return self::segment('results', $target, 'filter-query', self::text($data, 'results_label', 'Results count'), [
            'show' => self::text($data, 'results_show', 'Show'),
            'hide' => self::text($data, 'results_hide', 'Hide'),
        ], ($data['results_expanded'] ?? true) !== false ? 'show' : 'hide');
    }

    public static function search_toggle(array $data, string $target): string
    {
        return self::segment('search', $target, 'search', self::text($data, 'view_search_label', 'Search bar'), [
            'show' => self::text($data, 'results_show', 'Show'),
            'hide' => self::text($data, 'results_hide', 'Hide'),
        ], ($data['view_search'] ?? true) !== false ? 'show' : 'hide');
    }

    public static function sticky_toggle(array $data, string $target): string
    {
        return self::segment('sticky', $target, 'pin', self::text($data, 'view_sticky_label', 'Sticky toolbar'), [
            'yes' => self::text($data, 'view_yes', 'Yes'),
            'no' => self::text($data, 'view_no', 'No'),
        ], ($data['view_sticky'] ?? false) === true ? 'yes' : 'no');
    }

    public static function results_text(string $text, int $count): string
    {
        $parts = explode('{n}', self::e($text), 2);
        $amount = '<span class="query-amount">' . $count . '</span>';
        return count($parts) === 2 ? $parts[0] . $amount . $parts[1] : $parts[0] . ' ' . $amount;
    }

    public static function sort_labels(array $data): array
    {
        return [
            'sort_by_label' => self::label(self::text($data, 'sort_by_label', 'Sort by')),
            'sort_order_label' => self::label(self::text($data, 'sort_order_label', 'Order')),
        ];
    }

    public static function label(string $label): string
    {
        return rtrim(trim($label), ': ');
    }

    public static function placeholder(mixed $placeholder): string
    {
        $placeholder = is_scalar($placeholder) ? trim((string) $placeholder) : '';
        return ($placeholder === '' || preg_match('/^-*\s*select\s*-*$/i', $placeholder)) ? PlatformTextService::ui('all', 'All') : $placeholder;
    }

    public static function icon(string $name): string
    {
        return (string) PlatformComponentRenderer::render('svg', ['icon' => $name, 'class' => 'filters-icon']);
    }

    private static function text(array $data, string $key, string $default): string
    {
        $value = $data[$key] ?? null;
        return is_scalar($value) && trim((string) $value) !== '' ? (string) $value : PlatformTextService::ui($key, $default);
    }

    private static function drawer_input(array $input): array
    {
        $keep = array_intersect_key($input, array_flip(['placeholder', 'aria-label', 'autocomplete', 'enterkeyhint', 'spellcheck']));
        return $keep + ['autocomplete' => 'off'];
    }

    private static function attrs(array $attrs): string
    {
        $html = '';
        foreach ($attrs as $key => $value) {
            $key = preg_replace('/[^a-zA-Z0-9:_\-]/', '', (string) $key);
            if ($key === '' || $value === false || $value === null) continue;
            $html .= $value === true ? ' ' . $key : ' ' . $key . '="' . self::e((string) $value) . '"';
        }
        return $html;
    }

    private static function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
