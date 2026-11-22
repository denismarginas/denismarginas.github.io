<?php

class block_tags
{
    private const HTML_DIR = __DIR__ . '/../html/';
    private const PREFIXES = ['hash', 'chevron', 'none'];
    private const CHEVRON = '<svg class="block-tags-chevron" viewBox="0 0 320 512" aria-hidden="true"><path fill="currentColor" d="M278.6 233.4c12.5 12.5 12.5 32.8 0 45.3l-160 160c-12.5 12.5-32.8 12.5-45.3 0s-12.5-32.8 0-45.3L210.7 256 73.4 118.6c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0l160 160z"/></svg>';

    public static function render(array $data = []): string
    {
        $prefix = strtolower(trim((string) ($data['prefix'] ?? 'hash')));
        if (!in_array($prefix, self::PREFIXES, true)) $prefix = 'hash';

        $html = '';
        foreach (self::list($data['tags'] ?? []) as $tag) {
            $text = ltrim(trim(is_array($tag) ? (string) ($tag['text'] ?? '') : (string) $tag), '#');
            if ($text === '') continue;

            $link = is_array($tag) ? trim((string) ($tag['link'] ?? $tag['url'] ?? '')) : '';
            if (preg_match('/^\s*(javascript|data|vbscript):/i', $link)) $link = '';

            $html .= PlatformTemplateRenderer::render(self::HTML_DIR . 'parts/' . ($link !== '' ? 'tag_link.html' : 'tag.html'), [
                'before' => $prefix === 'hash' ? '#' : '',
                'text' => self::esc($text),
                'after' => $prefix === 'chevron' ? self::CHEVRON : '',
                'link' => self::esc($link),
            ]);
        }
        if ($html === '') return '';

        $extra = trim((string) ($data['class'] ?? ''));
        $id = trim((string) ($data['id'] ?? ''));

        return PlatformTemplateRenderer::render(self::HTML_DIR . 'template.html', [
            'extra_class' => $extra !== '' ? ' ' . self::esc($extra) : '',
            'id_attr' => $id !== '' ? ' id="' . self::esc($id) . '"' : '',
            'tags' => $html,
        ]);
    }

    private static function list(mixed $tags): array
    {
        if (is_string($tags)) {
            $decoded = json_decode($tags, true);
            $tags = is_array($decoded) ? $decoded : explode(',', $tags);
        }
        return is_array($tags) ? $tags : [];
    }

    private static function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
