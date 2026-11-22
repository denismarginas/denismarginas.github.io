<?php

class popup
{
    public static function render(array $data = []): string
    {
        $contentHtml = (string) ($data['content'] ?? $data['content_html'] ?? '');
        if ($contentHtml === '' && is_array($data['content_component'] ?? null)) {
            $contentHtml = self::component($data['content_component']);
        }
        $triggerHtml = (string) ($data['trigger'] ?? $data['trigger_html'] ?? '');
        if ($contentHtml === '' && $triggerHtml === '') return '';

        $gallery = is_array($data['items'] ?? null) ? $data['items'] : [];
        $inner = !empty($gallery) ? self::gallery($gallery) : self::panel($contentHtml, $data);

        $class = trim((string) ($data['class'] ?? ''));
        if (($data['inline'] ?? false) === true) $class = trim('popup-trigger-inline ' . $class);

        $hash = trim((string) ($data['hash'] ?? ''), " #");

        return PlatformTemplateRenderer::render(__DIR__ . '/../html/parts/trigger.html', [
            'trigger' => $triggerHtml,
            'content' => $inner,
            'class' => self::e($class),
            'attrs' => $hash !== '' ? ' data-popup-hash="' . self::e($hash) . '"' : '',
        ]);
    }

    public static function open_attrs(array $options): string
    {
        $hash = trim((string) ($options['hash'] ?? ''), " #");
        if ($hash === '') return '';

        $attrs = ' data-popup-hash="' . self::e($hash) . '" data-popup-move="' . self::e(trim((string) ($options['move'] ?? ''))) . '"';
        if (($options['panel'] ?? true) !== false) $attrs .= ' data-popup-panel';

        $width = trim((string) ($options['width'] ?? ''));
        if ($width !== '' && preg_match('/^[a-z0-9.%()+\-\s,]+$/i', $width)) $attrs .= ' data-popup-width="' . self::e($width) . '"';

        return $attrs;
    }

    private static function component(array $block): string
    {
        $name = trim((string) ($block['component'] ?? ''));
        if ($name === '') return '';
        return (string) PlatformComponentRenderer::render($name, is_array($block['data'] ?? null) ? $block['data'] : []);
    }

    private static function gallery(array $items): string
    {
        $slides = '';
        foreach ($items as $item) {
            $slides .= '<div class="popup-slide">' . (is_array($item)
                ? PlatformComponentRenderer::render('image', ['src' => $item['src'] ?? '', 'alt' => $item['alt'] ?? ''])
                : (string) $item) . '</div>';
        }
        return '<div class="popup-gallery" data-popup-gallery>' . $slides . '</div>';
    }

    private static function panel(string $html, array $data): string
    {
        if (($data['panel'] ?? false) !== true || $html === '') return $html;

        $width = trim((string) ($data['width'] ?? ''));
        $style = $width !== '' && preg_match('/^[a-z0-9.%()+\-\s,]+$/i', $width)
            ? ' style="--popup-panel-width: ' . self::e($width) . ';"'
            : '';

        return '<div class="popup-panel"' . $style . '>' . $html . '</div>';
    }

    private static function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
