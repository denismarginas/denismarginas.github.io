<?php

class anchor_index
{
    public static function render(array $data = []): string
    {
        $format = self::str($data['date_format'] ?? '') ?: 'j F, Y';
        $items = '';
        foreach ((array) ($data['items'] ?? []) as $item) {
            if (!is_array($item)) continue;
            $target = trim(self::str($item['id'] ?? ''), '# ');
            $text = self::str($item['text'] ?? '');
            if ($target === '' || $text === '') continue;
            $date = self::str($item['date'] ?? '');

            $items .= '<li><a class="anchor-index-link" href="#' . self::e($target) . '">'
                . ($date !== '' ? '<span class="anchor-index-date">' . self::e(PlatformTextService::format_date($date, $format)) . '</span>' : '')
                . '<span class="anchor-index-text">' . self::e($text) . '</span></a></li>';
        }
        if ($items === '') return '';

        $title = self::str($data['title'] ?? '');
        $classes = [];
        if (($data['sticky'] ?? false) === true) $classes[] = 'anchor-index-sticky';
        if (($class = self::str($data['class'] ?? '')) !== '') $classes[] = $class;
        $id = self::str($data['id'] ?? '');

        return PlatformTemplateRenderer::render([
            'extra_class' => $classes !== [] ? ' ' . self::e(implode(' ', $classes)) : '',
            'id_attr' => $id !== '' ? ' id="' . self::e($id) . '"' : '',
            'label' => self::e($title !== '' ? $title : 'On this page'),
            'min' => (string) max(1, (int) ($data['min_items'] ?? 2)),
            'title' => $title !== '' ? '<span class="anchor-index-title">' . self::e($title) . '</span>' : '',
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
