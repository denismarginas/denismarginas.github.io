<?php

class page_not_found
{
    private const ALLOWED_TAGS = '<br><b><strong><i><em>';

    private const DEFAULT_BUTTONS = [
        ['text' => 'Back to Home', 'svg' => 'chevron-left', 'link' => ['post_id' => 'home'], 'class' => 'btn btn-primary'],
        ['text' => 'Search', 'svg' => 'search', 'link' => ['post_id' => 'search']],
    ];

    public static function render(array $data = []): string
    {
        $code = trim((string) ($data['code'] ?? '404'));
        $title = trim((string) ($data['title'] ?? 'Page not found'));
        $text = $data['text'] ?? 'The page you are looking for does not exist, was moved or the link is broken.';

        $paragraphs = '';
        foreach ((array) $text as $p) {
            $p = trim(strip_tags((string) $p, self::ALLOWED_TAGS));
            if ($p !== '') $paragraphs .= '<p class="page-not-found-text">' . $p . '</p>';
        }

        $notes = '';
        foreach ((array) ($data['note'] ?? []) as $n) {
            $n = trim(strip_tags((string) $n, self::ALLOWED_TAGS));
            if ($n !== '') $notes .= '<p class="page-not-found-note">' . $n . '</p>';
        }

        $path = '';
        if (($data['path'] ?? true) !== false) {
            $requested = trim((string) ($GLOBALS['page_not_found_request'] ?? ''));
            $path = '<p class="page-not-found-path" data-page-not-found-path' . ($requested === '' ? ' hidden' : '') . '>'
                . '<span class="page-not-found-path-label">' . self::e((string) ($data['path_label'] ?? 'Requested address:')) . '</span> '
                . '<code class="page-not-found-path-value">' . self::e($requested) . '</code></p>';
        }

        $buttons = '';
        $list = is_array($data['buttons'] ?? null) ? $data['buttons'] : self::DEFAULT_BUTTONS;
        foreach ($list as $button) {
            if (!is_array($button)) continue;
            $button['class'] = trim('btn ' . (string) ($button['class'] ?? 'btn btn-outline') . ' page-not-found-button');
            $button['class'] = preg_replace('/\bbtn\s+btn\b/', 'btn', $button['class']);
            $buttons .= (string) PlatformComponentRenderer::render('button', $button);
        }

        $svg = trim((string) ($data['svg'] ?? 'broken-link'));
        $icon = $svg !== '' ? (string) PlatformComponentRenderer::render('svg', ['icon' => $svg, 'class' => 'page-not-found-icon']) : '';

        $overlays = section::render_children(array_merge($data, ['content' => '', 'body_content' => '', 'children_html' => '']));

        $class = trim((string) ($data['class'] ?? ''));
        $id = trim((string) ($data['id'] ?? ''));

        return PlatformTemplateRenderer::render(__DIR__ . '/../html/template.html', [
            'extra_class' => $class !== '' ? ' ' . self::e($class) : '',
            'id_attr' => $id !== '' ? ' id="' . self::e($id) . '"' : '',
            'overlays' => trim($overlays) !== '' ? '<div class="page-not-found-overlays" aria-hidden="true">' . $overlays . '</div>' : '',
            'code' => self::e($code),
            'title' => self::e($title),
            'icon' => trim($icon) !== '' ? '<span class="page-not-found-badge" aria-hidden="true">' . $icon . '</span>' : '',
            'text' => $paragraphs,
            'path' => $path,
            'note' => $notes,
            'buttons' => $buttons !== '' ? '<div class="page-not-found-actions">' . $buttons . '</div>' : '',
        ]);
    }

    private static function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
