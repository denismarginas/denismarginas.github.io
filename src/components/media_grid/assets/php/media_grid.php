<?php

class media_grid
{
    private static int $instance = 0;

    public static function render(array $data = []): string
    {
        $group = trim((string) ($data['group'] ?? '')) ?: 'media-grid-' . (++self::$instance);
        $max = max(1, min(4, (int) ($data['max'] ?? 4)));
        $imgClass = trim((string) ($data['image_class'] ?? '')) ?: 'media-grid-img';

        $cells = [];
        foreach ((array) ($data['items'] ?? []) as $item) {
            if (is_string($item)) $item = ['type' => 'image', 'src' => $item];
            if (!is_array($item)) continue;
            if (($item['type'] ?? 'image') === 'video') {
                $html = (string) ($item['html'] ?? '');
                if (trim($html) !== '') $cells[] = ['video', $html];
                continue;
            }
            $src = trim((string) ($item['src'] ?? ''));
            if ($src === '') continue;
            $img = (string) PlatformComponentRenderer::render('image', [
                'src' => $src,
                'alt' => (string) ($item['alt'] ?? ''),
                'class' => $imgClass,
                'lazy' => true,
            ]);
            if (trim($img) !== '') $cells[] = ['image', $img];
        }
        if ($cells === []) return '';

        $total = count($cells);
        $shown = min($max, $total);
        $extra = $total - $shown;

        $html = '';
        foreach ($cells as $i => [$type, $content]) {
            $hidden = $i >= $shown;
            if ($type === 'video') {
                if ($hidden) continue;
                $html .= '<div class="media-grid-item media-grid-item-video">' . $content . '</div>';
                continue;
            }
            $more = !$hidden && $extra > 0 && $i === $shown - 1;
            $html .= '<div class="media-grid-item' . ($more ? ' media-grid-item-more' : '') . '" data-popup="true" data-popup-group="' . self::e($group) . '"' . ($hidden ? ' hidden' : '') . '>'
                . $content
                . ($more ? '<span class="media-grid-more" aria-hidden="true">+' . $extra . '</span>' : '')
                . '</div>';
        }

        $class = trim((string) ($data['class'] ?? ''));
        return '<div class="media-grid' . ($class !== '' ? ' ' . self::e($class) : '') . '" data-count="' . $shown . '">' . $html . '</div>';
    }

    private static function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
