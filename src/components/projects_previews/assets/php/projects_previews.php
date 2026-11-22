<?php

class projects_previews
{
    private const MAX_CARDS = 6;

    public static function render(array $data = []): string
    {
        $cards = self::cards($data['cards'] ?? []);
        $screen = self::screen($data['screen'] ?? []);
        if ($cards === '' && $screen === '') return '';

        $class = self::str($data['class'] ?? '');
        $id = self::str($data['id'] ?? '');

        return PlatformTemplateRenderer::render(__DIR__ . '/../html/template.html', [
            'extra_class' => $class !== '' ? ' ' . self::e($class) : '',
            'id_attr' => $id !== '' ? ' id="' . self::e($id) . '"' : '',
            'cards' => $cards === '' ? '' : '<div class="projects-previews-cards">'
                . $cards
                . (($data['glow'] ?? true) !== false && substr_count($cards, '<img') > 1 ? '<span class="projects-previews-glow"></span>' : '')
                . '</div>',
            'screen' => $screen,
        ]);
    }

    private static function cards(mixed $cards): string
    {
        if (!is_array($cards)) return '';
        $html = '';
        $nr = 0;
        foreach ($cards as $card) {
            if ($nr >= self::MAX_CARDS) break;
            $img = self::img($card, 'projects-previews-card card-' . ($nr + 1));
            if ($img === '') continue;
            $html .= $img;
            $nr++;
        }
        return $html;
    }

    private static function screen(mixed $screen): string
    {
        $img = self::img($screen, 'projects-previews-screen-img');
        return $img === '' ? '' : '<div class="projects-previews-screen">' . $img . '</div>';
    }

    private static function img(mixed $item, string $class): string
    {
        if (is_string($item)) $item = ['img' => $item];
        if (!is_array($item)) return '';
        $src = self::str($item['img'] ?? $item['src'] ?? '');
        if ($src === '') return '';

        return trim((string) PlatformComponentRenderer::render('image', [
            'src' => $src,
            'alt' => self::str($item['alt'] ?? ''),
            'class' => $class,
            'lazy' => true,
        ]));
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
