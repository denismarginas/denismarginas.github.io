<?php

class block_title
{
    private const TAGS = ['h2', 'h3', 'h4', 'div'];

    public static function render(array $data = []): string
    {
        $text = trim((string) ($data['text'] ?? ''));
        if ($text === '') return '';

        $tag = strtolower(trim((string) ($data['tag'] ?? 'h2')));
        if (!in_array($tag, self::TAGS, true)) $tag = 'h2';

        $id = array_key_exists('id', $data) ? trim((string) $data['id']) : preg_replace('/[^a-z0-9]/', '', strtolower($text));
        $extra = trim((string) ($data['class'] ?? ''));

        $style = self::flag($data, 'appearance_colors', false)
            ? (string) PlatformComponentRenderer::value('utility_appearance_colors', [
                'post_current_data' => $data['post_current_data'] ?? [],
                'output' => 'attribute',
            ])
            : '';

        return PlatformTemplateRenderer::render([
            'tag' => $tag,
            'extra_class' => $extra !== '' ? ' ' . htmlspecialchars($extra, ENT_QUOTES, 'UTF-8') : '',
            'id_attr' => $id !== '' ? ' id="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '"' : '',
            'style_attr' => $style,
            'overlay' => self::flag($data, 'overlay', true) ? (string) PlatformComponentRenderer::render('overlay_noise', []) : '',
            'text' => htmlspecialchars($text, ENT_QUOTES, 'UTF-8', false),
        ]);
    }

    private static function flag(array $data, string $key, bool $default): bool
    {
        if (!array_key_exists($key, $data)) return $default;
        $v = $data[$key];
        if (is_bool($v)) return $v;
        return !in_array(strtolower(trim((string) $v)), ['false', '0', 'no', 'off', ''], true);
    }
}
