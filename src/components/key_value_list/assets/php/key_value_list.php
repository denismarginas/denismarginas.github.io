<?php

class key_value_list
{
    public static function render(array $data = []): string
    {
        $rows = '';
        foreach ((array) ($data['items'] ?? []) as $item) {
            if (!is_array($item)) continue;
            $label = self::str($item['label'] ?? '');
            $value = self::value($item);
            if ($label === '' || $value === '') continue;
            $rows .= '<div class="key-value-list-row"><dt class="text-label">' . self::e($label) . '</dt><dd>' . $value . '</dd></div>';
        }
        if ($rows === '') return '';

        $variant = ($data['variant'] ?? 'grid') === 'inline' ? 'inline' : 'grid';
        $columns = (int) ($data['columns'] ?? 2) === 1 ? 1 : 2;
        $class = self::str($data['class'] ?? '');

        return PlatformTemplateRenderer::render([
            'variant' => $variant,
            'columns' => (string) $columns,
            'extra_class' => $class !== '' ? ' ' . self::e($class) : '',
            'rows' => $rows,
        ]);
    }

    private static function value(array $item): string
    {
        $html = is_string($item['html'] ?? null) ? trim($item['html']) : '';
        if ($html !== '') return $html;

        $value = self::str($item['value'] ?? '');
        if ($value === '') return '';
        $link = self::str($item['link'] ?? '');
        return $link !== '' ? '<a href="' . self::e($link) . '">' . self::e($value) . '</a>' : self::e($value);
    }

    private static function str(mixed $value): string
    {
        return is_scalar($value) ? trim((string) $value) : '';
    }

    private static function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
