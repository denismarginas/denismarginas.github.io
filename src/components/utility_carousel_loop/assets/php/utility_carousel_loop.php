<?php

class utility_carousel_loop
{
    public const SPEEDS = ['slow' => '80s', 'normal' => '50s', 'fast' => '30s'];

    public static function render(array $data = []): string
    {
        $listTag = ($data['list_tag'] ?? 'ul') === 'div' ? 'div' : 'ul';
        $itemTag = $listTag === 'ul' ? 'li' : 'div';
        $itemClass = self::classes($data['item_class'] ?? '');

        $items = '';
        foreach ((array) ($data['items'] ?? []) as $item) {
            $html = is_array($item) ? (string) ($item['html'] ?? '') : (string) $item;
            if (trim($html) === '') continue;
            $attrs = is_array($item) ? (string) ($item['attrs'] ?? '') : '';
            $class = 'carousel-loop-item' . $itemClass . (is_array($item) ? self::classes($item['class'] ?? '') : '');
            $items .= '<' . $itemTag . ' class="' . $class . '"' . $attrs . '>' . $html . '</' . $itemTag . '>';
        }
        if ($items === '') return '';

        return PlatformTemplateRenderer::render([
            'extra_class' => self::classes($data['class'] ?? ''),
            'list_class' => self::classes($data['list_class'] ?? ''),
            'list_tag' => $listTag,
            'direction' => ($data['direction'] ?? 'left') === 'right' ? 'right' : 'left',
            'pause_on_hover' => self::flag($data['pause_on_hover'] ?? true) ? 'true' : 'false',
            'attrs' => (string) ($data['attrs'] ?? ''),
            'style' => self::style($data),
            'items' => $items,
        ]);
    }

    public static function duration(mixed $speed): string
    {
        $speed = is_scalar($speed) ? strtolower(trim((string) $speed)) : '';
        if (isset(self::SPEEDS[$speed])) return self::SPEEDS[$speed];
        return preg_match('/^\d+(\.\d+)?m?s$/', $speed) ? $speed : self::SPEEDS['slow'];
    }

    public static function gap(mixed $value): string
    {
        if (is_int($value) || is_float($value) || (is_string($value) && is_numeric(trim($value)))) {
            return ((float) $value) . 'px';
        }
        if (!is_string($value)) return '';
        $value = trim($value);
        if (preg_match('/^--[a-z0-9-]+$/i', $value)) return 'var(' . $value . ')';
        if (preg_match('/^var\(--[a-z0-9-]+\)$/i', $value)) return $value;
        return preg_match('/^\d+(\.\d+)?(px|rem|em|vw|vh|%)$/i', $value) ? strtolower($value) : '';
    }

    private static function style(array $data): string
    {
        $css = '--carousel-duration: ' . self::duration($data['speed'] ?? 'slow') . ';';
        foreach (['gap' => '--carousel-gap', 'gap_phone' => '--carousel-gap-phone'] as $key => $var) {
            $gap = self::gap($data[$key] ?? null);
            if ($gap !== '') $css .= ' ' . $var . ': ' . $gap . ';';
        }
        $extra = trim((string) ($data['style'] ?? ''));
        if ($extra !== '' && !preg_match('/[<>"{}]/', $extra)) $css .= ' ' . $extra;

        return ' style="' . htmlspecialchars($css, ENT_QUOTES, 'UTF-8') . '"';
    }

    private static function classes(mixed $value): string
    {
        $value = is_scalar($value) ? trim((string) $value) : '';
        return $value !== '' ? ' ' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') : '';
    }

    private static function flag(mixed $value): bool
    {
        if (is_bool($value)) return $value;
        if (is_int($value) || is_float($value)) return $value != 0;
        return !in_array(strtolower(trim((string) $value)), ['false', '0', 'no', 'off', ''], true);
    }
}
