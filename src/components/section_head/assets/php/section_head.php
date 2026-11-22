<?php

class section_head
{
    private const TAGS = ['h1', 'h2', 'h3', 'div'];
    private const SIZES = ['primary', 'secondary', 'tertiary'];
    private const ALLOWED_TAGS = ['br', 'b', 'strong', 'i', 'em'];

    public static function render(array $data = []): string
    {
        $title = self::text((string) ($data['title'] ?? ''));
        if ($title === '') return '';

        $tag = strtolower(self::str($data['tag'] ?? 'h2'));
        if (!in_array($tag, self::TAGS, true)) $tag = 'h2';

        $size = strtolower(self::str($data['size'] ?? 'secondary'));
        if (!in_array($size, self::SIZES, true)) $size = 'secondary';

        $kicker = self::str($data['kicker'] ?? '');
        $text = self::text((string) ($data['text'] ?? ''));
        $count = self::str($data['count'] ?? '');
        $class = self::str($data['class'] ?? '');
        $id = self::str($data['id'] ?? '');

        return PlatformTemplateRenderer::render([
            'extra_class' => $class !== '' ? ' ' . self::e($class) : '',
            'id_attr' => $id !== '' ? ' id="' . self::e($id) . '"' : '',
            'kicker' => $kicker !== '' ? '<span class="section-head-kicker text-label">' . self::e($kicker) . '</span>' : '',
            'tag' => $tag,
            'size' => $size,
            'title' => $title,
            'text' => $text !== '' ? '<p class="section-head-text">' . $text . '</p>' : '',
            'count' => $count !== '' ? '<span class="section-head-count text-label">' . self::e($count) . '</span>' : '',
        ]);
    }

    public static function text(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '') return '';
        $html = htmlspecialchars($raw, ENT_QUOTES, 'UTF-8', false);
        return preg_replace_callback(
            '#&lt;\s*(/?)\s*(' . implode('|', self::ALLOWED_TAGS) . ')\s*/?\s*&gt;#i',
            fn ($m) => strtolower($m[2]) === 'br' ? '<br>' : '<' . $m[1] . strtolower($m[2]) . '>',
            $html
        );
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
