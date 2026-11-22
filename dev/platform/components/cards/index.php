<?php

function platform_render_cards(array $cards, string $variant = ''): string
{
    if ($cards === []) return '';

    platform_asset('css', 'components/cards/assets/css/cards.css');

    $e = static fn (string $v): string => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
    $tiles = $variant === 'tiles';

    $html = '<div class="platform-cards-grid' . ($tiles ? ' platform-cards-grid--tiles' : '') . '">';

    foreach ($cards as $card) {
        $title  = (string) ($card['title'] ?? '');
        $desc   = (string) ($card['desc'] ?? '');
        $icon   = (string) ($card['icon'] ?? '');
        $url    = (string) ($card['url'] ?? '');
        $action = (string) ($card['action'] ?? '');
        $class  = (string) ($card['class'] ?? '');
        $active = !empty($card['active']);

        if ($tiles) {
            $inner = '<span class="platform-cards-item-icon">' . PlatformSvg::render(['name' => $icon, 'size' => 22, 'class' => 'platform-cards-item-icon-svg']) . '</span>'
                . '<span class="platform-cards-item-title">' . $e($title) . '</span>';
        } else {
            $inner = '<span class="platform-cards-item-icon">' . PlatformSvg::render(['name' => $icon, 'size' => 18, 'class' => 'platform-cards-item-icon-svg']) . '</span>'
                . '<span class="platform-cards-item-copy">'
                . '<span class="platform-cards-item-title">' . $e($title) . '</span>'
                . ($desc !== '' ? '<span class="platform-cards-item-desc">' . $e($desc) . '</span>' : '')
                . '</span>';
        }

        $cls = 'platform-cards-item' . ($active ? ' is-active' : '') . ($class !== '' ? ' ' . $e($class) : '');
        $tip = $tiles && $desc !== '' ? ' title="' . $e($desc) . '"' : '';

        if ($action !== '') {
            $html .= '<button class="' . $cls . '" type="button" data-platform-card-action="' . $e($action) . '"' . $tip . '>' . $inner . '</button>';
        } else {
            $html .= '<a class="' . $cls . '" href="' . $e($url) . '"' . $tip . '>' . $inner . '</a>';
        }
    }

    $html .= '</div>';
    return $html;
}
