<?php

class block_paragraph
{
    private const ALLOWED_TAGS = ['br', 'b', 'strong', 'i', 'em', 'u'];

    private const PASSTHROUGH = ['post_current_data', 'global_content_path', 'global_img_path', 'global_vid_path'];

    public static function render(array $data = []): string
    {
        $text = self::text((string) ($data['text'] ?? ''));

        $children = '';
        if (!empty($data['children']) && is_array($data['children'])) {
            $children = (string) section::render_children(array_merge(
                array_intersect_key($data, array_flip(self::PASSTHROUGH)),
                ['children' => $data['children']]
            ));
        }

        if ($text === '' && trim($children) === '') return '';

        $classes = [];
        if ($text === '') $classes[] = 'block-paragraph-no-text';
        if (trim($children) !== '') $classes[] = 'block-paragraph-has-children';
        $extra = trim((string) ($data['class'] ?? ''));
        if ($extra !== '') $classes[] = $extra;

        $id = trim((string) ($data['id'] ?? ''));
        $style = trim((string) ($data['style'] ?? ''));
        if (preg_match('/[{}<>"]/', $style)) $style = '';

        return PlatformTemplateRenderer::render([
            'extra_class' => $classes ? ' ' . htmlspecialchars(implode(' ', $classes), ENT_QUOTES, 'UTF-8') : '',
            'id_attr' => $id !== '' ? ' id="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '"' : '',
            'style_attr' => $style !== '' ? ' style="' . htmlspecialchars($style, ENT_QUOTES, 'UTF-8') . '"' : '',
            'text' => $text !== '' ? PlatformTemplateRenderer::render(__DIR__ . '/../html/parts/text.html', ['text' => $text]) : '',
            'children' => $children,
        ]);
    }

    private static function text(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '') return '';

        $html = htmlspecialchars($raw, ENT_QUOTES, 'UTF-8', false);
        $tags = implode('|', self::ALLOWED_TAGS);
        $html = preg_replace_callback(
            '#&lt;\s*(/?)\s*(' . $tags . ')\s*/?\s*&gt;#i',
            fn ($m) => strtolower($m[2]) === 'br' ? '<br>' : '<' . $m[1] . strtolower($m[2]) . '>',
            $html
        );

        return trim(preg_replace('#^(\s*<br>)+|(<br>\s*)+$#', '', $html));
    }
}
