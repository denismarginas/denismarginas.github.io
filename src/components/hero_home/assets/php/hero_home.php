<?php

class hero_home
{
    private const ALLOWED_TAGS = '<br><b><strong><i><em>';

    public static function render(array $data = []): string
    {
        $background = is_array($data['video'] ?? null)
            ? (string) PlatformComponentRenderer::render('video_background', array_merge(['overlay' => 0.65], $data['video']))
            : '';

        $overlays = section::render_children(array_merge($data, ['content' => '', 'body_content' => '', 'children_html' => '']));

        $eyebrow = trim((string) ($data['eyebrow'] ?? ''));

        $title = self::e(trim((string) ($data['title'] ?? '')));
        $title = preg_replace('/\*([^*]+)\*/', '<span class="hero-home-highlight">$1</span>', $title);

        $text = '';
        foreach ((array) ($data['text'] ?? []) as $p) {
            $p = trim(strip_tags((string) $p, self::ALLOWED_TAGS));
            if ($p !== '') $text .= '<p class="hero-home-text">' . $p . '</p>';
        }

        $buttons = '';
        foreach ((array) ($data['buttons'] ?? []) as $button) {
            if (!is_array($button)) continue;
            $button['class'] = trim((string) ($button['class'] ?? 'btn btn-outline') . ' hero-home-button');
            $buttons .= (string) PlatformComponentRenderer::render('button', $button);
        }

        $scrollData = $data['scroll'] ?? true;
        $scroll = $scrollData !== false
            ? (string) PlatformComponentRenderer::render('scroll_down', array_merge(
                ['class' => 'hero-home-scroll'],
                is_array($scrollData) ? $scrollData : []
            ))
            : '';

        $align = strtolower((string) ($data['align'] ?? 'center')) === 'left' ? 'left' : 'center';
        $height = in_array($data['height'] ?? '', ['lg', 'md'], true) ? $data['height'] : 'full';
        $class = trim((string) ($data['class'] ?? ''));
        $id = trim((string) ($data['id'] ?? ''));

        return PlatformTemplateRenderer::render(__DIR__ . '/../html/template.html', [
            'extra_class' => $class !== '' ? ' ' . self::e($class) : '',
            'id_attr' => ($id !== '' ? ' id="' . self::e($id) . '"' : '') . ' data-align="' . $align . '" data-height="' . $height . '"',
            'background' => $background,
            'overlays' => trim($overlays) !== '' ? '<div class="hero-home-overlays" aria-hidden="true">' . $overlays . '</div>' : '',
            'eyebrow' => $eyebrow !== '' ? '<p class="hero-home-eyebrow">' . self::e($eyebrow) . '</p>' : '',
            'title' => $title,
            'text' => $text,
            'buttons' => $buttons !== '' ? '<div class="hero-home-actions">' . $buttons . '</div>' : '',
            'scroll' => $scroll,
        ]);
    }

    private static function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
