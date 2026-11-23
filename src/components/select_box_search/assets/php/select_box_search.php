<?php

class select_box_search
{
    public static function render(array $data = []): string
    {
        $id = trim((string) ($data['id'] ?? ''));
        $options = self::options($data['options'] ?? []);
        if ($id === '' || empty($options)) return '';

        $selected = array_map('strval', (array) ($data['selected'] ?? []));
        $html = self::placeholder_option($data);
        foreach ($options as $value => $label) {
            $html .= '<option value="' . self::e($value) . '"' . (in_array($value, $selected, true) ? ' selected' : '') . '>' . self::e($label) . '</option>';
        }

        return PlatformTemplateRenderer::render([
            'id' => self::e($id),
            'name' => self::e((string) ($data['name'] ?? $id)),
            'label' => self::e((string) ($data['label'] ?? '')),
            'attrs' => self::attrs($data),
            'options' => $html,
        ]);
    }

    public static function attrs(array $def = []): string
    {
        $search = $def['search'] ?? 'auto';
        $mode = $search === true || $search === 'on' ? 'on' : ($search === false || $search === 'off' ? 'off' : 'auto');

        $html = ' data-select-box-search="' . $mode . '"'
            . ' data-label-search="' . self::e(PlatformTextService::ui('select_search_placeholder', 'Search...')) . '"'
            . ' data-label-empty="' . self::e(PlatformTextService::ui('select_no_matches', 'No matches')) . '"';
        if (isset($def['search_min'])) {
            $html .= ' data-search-min="' . max(0, (int) $def['search_min']) . '"';
        }
        if (self::multiple($def)) {
            $html .= ' multiple data-placeholder="' . self::e(self::placeholder($def['placeholder'] ?? '')) . '"';
        }
        return $html;
    }

    public static function multiple(array $def): bool
    {
        return filter_var($def['multiple'] ?? false, FILTER_VALIDATE_BOOLEAN);
    }

    public static function placeholder_option(array $def): string
    {
        if (self::multiple($def)) return '';
        return '<option value="">' . self::e(self::placeholder($def['placeholder'] ?? '')) . '</option>';
    }

    public static function placeholder(mixed $placeholder): string
    {
        $placeholder = is_scalar($placeholder) ? trim((string) $placeholder) : '';
        return ($placeholder === '' || preg_match('/^-*\s*select\s*-*$/i', $placeholder)) ? PlatformTextService::ui('all', 'All') : $placeholder;
    }

    private static function options(mixed $options): array
    {
        if (!is_array($options)) return [];

        $out = [];
        foreach ($options as $key => $option) {
            if (is_array($option)) {
                $value = (string) ($option['value'] ?? '');
                if ($value !== '') $out[$value] = (string) ($option['label'] ?? $value);
            } elseif (is_scalar($option)) {
                $value = array_is_list($options) ? (string) $option : (string) $key;
                if ($value !== '') $out[$value] = (string) $option;
            }
        }
        return $out;
    }

    private static function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
