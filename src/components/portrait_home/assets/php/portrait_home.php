<?php

class portrait_home
{
    private const MAX_CHIPS = 6;

    public static function render(array $data = []): string
    {
        $src = is_scalar($data['img'] ?? null) ? trim((string) $data['img']) : '';
        if ($src === '') return '';

        $image = (string) PlatformComponentRenderer::render('image', [
            'src' => $src,
            'alt' => is_scalar($data['alt'] ?? null) ? (string) $data['alt'] : 'Portrait',
            'class' => 'portrait-home-img',
            'lazy' => true,
        ]);
        if (trim($image) === '') return '';

        $chips = '';
        $nr = 0;
        foreach ((array) ($data['chips'] ?? []) as $chip) {
            if ($nr >= self::MAX_CHIPS) break;
            if (is_string($chip)) $chip = ['text' => $chip];
            if (!is_array($chip)) continue;
            $text = trim((string) ($chip['text'] ?? ''));
            $svg = trim((string) ($chip['svg'] ?? ''));
            if ($text === '' && $svg === '') continue;
            $nr++;
            $icon = $svg !== '' ? (string) PlatformComponentRenderer::render('svg', ['icon' => $svg, 'class' => 'portrait-home-chip-icon']) : '';
            $chips .= '<span class="portrait-home-chip chip-' . $nr . '">' . $icon
                . ($text !== '' ? '<span class="portrait-home-chip-text">' . self::e($text) . '</span>' : '') . '</span>';
        }

        $class = is_scalar($data['class'] ?? null) ? trim((string) $data['class']) : '';
        $id = is_scalar($data['id'] ?? null) ? trim((string) $data['id']) : '';
        $maxWidth = is_scalar($data['max_width'] ?? null) ? trim((string) $data['max_width']) : '';
        $style = $maxWidth !== '' && !preg_match('/[;{}<>"\']/', $maxWidth) ? ' style="max-width: ' . self::e($maxWidth) . ';"' : '';

        return PlatformTemplateRenderer::render(__DIR__ . '/../html/template.html', [
            'extra_class' => $class !== '' ? ' ' . self::e($class) : '',
            'parallax_attr' => ($data['parallax'] ?? true) !== false ? ' data-portrait-home-parallax' : '',
            'id_attr' => ($id !== '' ? ' id="' . self::e($id) . '"' : '') . $style,
            'image' => $image,
            'chips' => $chips !== '' ? '<div class="portrait-home-chips" aria-hidden="true">' . $chips . '</div>' : '',
        ]);
    }

    private static function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
