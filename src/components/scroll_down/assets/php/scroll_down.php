<?php

class scroll_down
{
    public static function render(array $data = []): string
    {
        $target = is_scalar($data['target'] ?? null) ? trim((string) $data['target']) : '';
        $label = is_scalar($data['label'] ?? null) && trim((string) $data['label']) !== '' ? trim((string) $data['label']) : PlatformTextService::ui('scroll_down', 'Scroll down');
        $position = strtolower((string) ($data['position'] ?? 'bottom')) === 'inline' ? 'inline' : 'bottom';
        $hideMobile = ($data['hide_mobile'] ?? true) !== false;
        $class = is_scalar($data['class'] ?? null) ? trim((string) $data['class']) : '';
        $id = is_scalar($data['id'] ?? null) ? trim((string) $data['id']) : '';

        $attrs = ' aria-label="' . self::e($label) . '"'
            . ' data-position="' . $position . '"'
            . ($hideMobile ? ' data-hide-mobile' : '')
            . ($target !== '' ? ' data-scroll-down-target="' . self::e($target) . '"' : '')
            . ($id !== '' ? ' id="' . self::e($id) . '"' : '');

        return PlatformTemplateRenderer::render(__DIR__ . '/../html/template.html', [
            'extra_class' => $class !== '' ? ' ' . self::e($class) : '',
            'attrs' => $attrs,
        ]);
    }

    private static function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
