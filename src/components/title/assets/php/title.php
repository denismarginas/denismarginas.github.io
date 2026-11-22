<?php

class title
{
    public const DEFAULT_CLASS = 'title-divider title-primary';
    private const TAGS = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'];

    public static function render(array $data = []): string
    {
        $raw = $data['text'] ?? $data['content'] ?? null;
        $text = is_scalar($raw) ? trim((string) $raw) : '';
        if ($text === '') return '';

        $tag = is_scalar($data['tag'] ?? null) ? strtolower(trim((string) $data['tag'])) : '';
        if (!in_array($tag, self::TAGS, true)) $tag = 'h2';

        $class = array_key_exists('class', $data) && $data['class'] !== null
            ? (is_scalar($data['class']) ? trim((string) $data['class']) : self::DEFAULT_CLASS)
            : self::DEFAULT_CLASS;
        $base = is_scalar($data['base_class'] ?? null) ? trim((string) $data['base_class']) : '';
        $classes = trim($base . ' ' . $class);

        $id = is_scalar($data['id'] ?? null) ? trim((string) $data['id']) : '';

        return '<' . $tag
            . ($classes !== '' ? ' class="' . self::e(preg_replace('/\s+/', ' ', $classes)) . '"' : '')
            . ($id !== '' ? ' id="' . self::e($id) . '"' : '')
            . '>' . self::e($text) . '</' . $tag . '>';
    }

    public static function normalize(mixed $title, array $legacy = [], array $extra = []): array
    {
        $out = is_array($title)
            ? array_intersect_key($title, array_flip(['text', 'content', 'tag', 'class', 'id']))
            : ['text' => is_scalar($title) ? (string) $title : ''];

        if (!array_key_exists('tag', $out) && isset($legacy['title_tag'])) $out['tag'] = $legacy['title_tag'];
        if (!array_key_exists('class', $out) && array_key_exists('title_class', $legacy)) $out['class'] = $legacy['title_class'];

        return array_merge($out, $extra);
    }

    private static function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
