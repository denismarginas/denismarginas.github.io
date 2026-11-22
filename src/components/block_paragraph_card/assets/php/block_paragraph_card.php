<?php

class block_paragraph_card
{
    private const ALLOWED_TAGS = '<br><b><strong><i><em>';

    public static function render(array $data = []): string
    {
        $text = '';
        foreach ((array) ($data['text'] ?? []) as $p) {
            $p = is_scalar($p) ? trim(strip_tags((string) $p, self::ALLOWED_TAGS)) : '';
            if ($p !== '') $text .= '<p>' . $p . '</p>';
        }
        $title = is_scalar($data['title'] ?? null) ? trim((string) $data['title']) : '';
        if ($text === '' && $title === '') return '';

        $classes = [];
        if (($data['fill'] ?? true) !== false) $classes[] = 'block-paragraph-card-fill';
        $extra = is_scalar($data['class'] ?? null) ? trim((string) $data['class']) : '';
        if ($extra !== '') $classes[] = $extra;
        $id = is_scalar($data['id'] ?? null) ? trim((string) $data['id']) : '';

        return PlatformTemplateRenderer::render(__DIR__ . '/../html/template.html', [
            'extra_class' => $classes ? ' ' . htmlspecialchars(implode(' ', $classes), ENT_QUOTES, 'UTF-8') : '',
            'id_attr' => $id !== '' ? ' id="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '"' : '',
            'title' => $title !== '' ? '<h3 class="block-paragraph-card-title">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h3>' : '',
            'text' => $text,
        ]);
    }
}
