<?php

class section_nav
{
    private static int $instance = 0;

    public static function render(array $data = []): string
    {
        $items = [];
        foreach ((array) ($data['items'] ?? []) as $item) {
            if (!is_array($item)) continue;
            $id = trim(self::str($item['id'] ?? ''), '# ');
            $text = self::str($item['text'] ?? '');
            if ($id !== '' && $text !== '') $items[] = ['id' => $id, 'text' => $text];
        }
        if ($items === []) return '';

        $index = (string) PlatformComponentRenderer::render('anchor_index', [
            'title' => '',
            'items' => $items,
            'min_items' => 1,
            'class' => 'section-nav-index',
        ]);
        if (trim($index) === '') return '';
        $label = self::str($data['label'] ?? '') ?: PlatformTextService::ui('section_nav_label', 'Page sections');
        $linksId = 'section-nav-links-' . (++self::$instance);
        $index = preg_replace('/aria-label="[^"]*"/', 'id="' . $linksId . '" aria-label="' . self::e($label) . '"', $index, 1);

        $progress = ($data['progress'] ?? true) !== false
            ? (string) PlatformComponentRenderer::render('scroll_progress', ['position' => 'bottom', 'class' => 'section-nav-progress'])
            : '';

        $class = self::str($data['class'] ?? '');
        $id = self::str($data['id'] ?? '');

        return PlatformTemplateRenderer::render([
            'extra_class' => $class !== '' ? ' ' . self::e($class) : '',
            'id_attr' => $id !== '' ? ' id="' . self::e($id) . '"' : '',
            'label' => self::e($label),
            'links_id' => $linksId,
            'toggle_icon' => (string) PlatformComponentRenderer::render('svg', ['icon' => 'menu-sections', 'class' => 'section-nav-toggle-icon']),
            'current' => self::e($items[0]['text']),
            'index' => $index,
            'progress' => $progress,
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
