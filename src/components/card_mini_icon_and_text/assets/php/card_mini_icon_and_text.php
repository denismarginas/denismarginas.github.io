<?php

class card_mini_icon_and_text
{
    private const VARIANTS = ['row', 'stack'];
    private const CARD_FIELDS = ['svg', 'text', 'link', 'target', 'img', 'variant', 'noise'];

    public static function render(array $data = []): string
    {
        if (is_array($data['items'] ?? null)) return self::render_list($data);
        return self::render_card($data);
    }

    public static function render_card(array $card): string
    {
        $text = self::str($card['text'] ?? $card['title'] ?? '');
        $icon = self::str($card['svg'] ?? $card['icon'] ?? '');

        $iconHtml = $icon !== ''
            ? trim((string) PlatformComponentRenderer::render('svg', ['icon' => $icon, 'class' => 'card-mini-svg']))
            : '';
        if ($text === '' && $iconHtml === '') return '';

        $img = self::str($card['img'] ?? $card['feature_image'] ?? '');
        $imageHtml = $img !== ''
            ? trim((string) PlatformComponentRenderer::render('image', ['src' => $img, 'alt' => '', 'class' => 'card-mini-bg', 'lazy' => true]))
            : '';

        $variant = strtolower(self::str($card['variant'] ?? ''));
        if (!in_array($variant, self::VARIANTS, true)) $variant = 'row';

        $link = self::link($card['link'] ?? '');
        $attrs = '';
        if ($link !== '') {
            $attrs .= ' href="' . self::e($link) . '"';
            $target = self::str($card['target'] ?? self::link_target($card['link'] ?? null));
            if ($target !== '') {
                $attrs .= ' target="' . self::e($target) . '"' . ($target === '_blank' ? ' rel="noopener"' : '');
            }
        }

        $class = self::str($card['class'] ?? '');

        $noise = self::flag($card['noise'] ?? ($variant === 'row'))
            ? (string) PlatformComponentRenderer::render('overlay_noise', [])
            : '';

        $arrow = $link !== '' && $variant === 'row'
            ? '<span class="card-mini-arrow">' . trim((string) PlatformComponentRenderer::render('svg', ['icon' => 'chevron-right', 'class' => 'card-mini-arrow-svg'])) . '</span>'
            : '';

        return PlatformTemplateRenderer::render(__DIR__ . '/../html/template.html', [
            'tag' => $link !== '' ? 'a' : 'div',
            'variant' => $variant,
            'extra_class' => ($imageHtml !== '' ? ' card-mini-has-image' : '') . ($class !== '' ? ' ' . self::e($class) : ''),
            'attrs' => $attrs,
            'image' => $imageHtml . $noise,
            'icon' => $iconHtml !== '' ? '<span class="card-mini-icon">' . $iconHtml . '</span>' : '',
            'text' => ($text !== '' ? '<span class="card-mini-text">' . self::e($text) . '</span>' : '') . $arrow,
        ]);
    }

    private static function render_list(array $data): string
    {
        $defaults = array_intersect_key($data, array_flip(self::CARD_FIELDS));
        unset($defaults['link'], $defaults['text'], $defaults['svg']);

        $cards = [];
        foreach ($data['items'] as $item) {
            if (!is_array($item)) continue;
            $html = self::render_card(array_merge($defaults, $item));
            if ($html !== '') $cards[] = '<li>' . $html . '</li>';
        }
        if (empty($cards)) return '';

        $columns = (int) ($data['columns'] ?? 0);
        $gridAttrs = '';
        $style = '';
        if ($columns > 0) {
            $gridAttrs = (string) PlatformComponentRenderer::value('utility_grid_listing', ['count' => count($cards), 'max' => $columns]);
        } else {
            $minWidth = self::str($data['min_width'] ?? '');
            if ($minWidth !== '' && !preg_match('/[;{}<>"\']/', $minWidth)) {
                $style = ' style="--card-mini-min-width: ' . self::e($minWidth) . ';"';
            }
        }

        $class = 'card-mini-listing' . ($columns > 0 ? '' : ' card-mini-listing-auto');
        $listClass = self::str($data['list_class'] ?? $data['class_list'] ?? '');
        if ($listClass !== '') $class .= ' ' . self::e($listClass);
        $id = self::str($data['id'] ?? '');

        return '<ul class="' . $class . '"' . ($id !== '' ? ' id="' . self::e($id) . '"' : '') . $gridAttrs . $style . '>'
            . implode('', $cards) . '</ul>';
    }

    private static function link(mixed $link): string
    {
        if (is_string($link)) return trim($link);
        if (!is_array($link)) return '';
        $url = self::str($link['url'] ?? '');
        $url = $url !== '' ? $url : button::resolve_link(['link' => $link]);
        return $url === '' ? '' : self::with_params($url, $link['params'] ?? [], self::str($link['anchor'] ?? ''));
    }

    private static function with_params(string $url, mixed $params, string $anchor): string
    {
        $hash = '';
        if (($pos = strpos($url, '#')) !== false) {
            $hash = substr($url, $pos);
            $url = substr($url, 0, $pos);
        }
        $query = [];
        foreach (is_array($params) ? $params : [] as $key => $value) {
            $value = self::str($value);
            if (is_string($key) && $key !== '' && $value !== '') $query[$key] = $value;
        }
        if ($query) $url .= (str_contains($url, '?') ? '&' : '?') . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        $anchor = ltrim($anchor, '#');
        return $url . ($anchor !== '' ? '#' . rawurlencode($anchor) : $hash);
    }

    private static function link_target(mixed $link): string
    {
        if (!is_array($link)) return '';
        return self::str($link['target'] ?? (self::str($link['url'] ?? '') !== '' ? '_blank' : ''));
    }

    private static function flag(mixed $value): bool
    {
        if (is_bool($value)) return $value;
        if (is_int($value) || is_float($value)) return $value != 0;
        return !in_array(strtolower(trim((string) $value)), ['false', '0', 'no', 'off', ''], true);
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
