<?php

class icon_list
{
    private const DEFAULT_SVG = 'chevron-right';

    public static function render(array $data = []): string
    {
        $svg = array_key_exists('svg', $data) ? self::str($data['svg']) : self::DEFAULT_SVG;
        $icons = [];

        $items = '';
        $count = 0;
        foreach ((array) ($data['items'] ?? []) as $item) {
            $text = self::str(is_array($item) ? ($item['text'] ?? '') : $item);
            if ($text === '') continue;
            $name = is_array($item) && array_key_exists('svg', $item) ? self::str($item['svg']) : $svg;
            if ($name !== '' && !isset($icons[$name])) {
                $icons[$name] = (string) PlatformComponentRenderer::render('svg', ['icon' => $name, 'class' => 'icon-list-icon']);
            }
            $count++;
            $items .= '<li class="icon-list-item">' . ($name !== '' ? $icons[$name] : '')
                . '<span class="icon-list-text">' . self::e($text) . '</span></li>';
        }
        if ($items === '') return '';

        $classes = [];
        if (self::str($data['layout'] ?? '') === 'inline') $classes[] = 'icon-list-inline';
        if (self::str($data['icon_style'] ?? '') === 'plain') $classes[] = 'icon-list-plain';
        if (in_array($size = self::str($data['size'] ?? ''), ['sm', 'base'], true)) $classes[] = 'icon-list-' . $size;
        $columns = self::str($data['columns'] ?? '');
        if ($columns !== '') {
            $max = $columns === 'auto' ? 3 : max(1, (int) $columns);
            $classes[] = 'icon-list-columns icon-list-columns-' . min($max, $count);
        }
        if (($class = self::str($data['class'] ?? '')) !== '') $classes[] = $class;
        $id = self::str($data['id'] ?? '');

        return PlatformTemplateRenderer::render([
            'extra_class' => $classes !== [] ? ' ' . self::e(implode(' ', $classes)) : '',
            'id_attr' => $id !== '' ? ' id="' . self::e($id) . '"' : '',
            'items' => $items,
        ]);
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
