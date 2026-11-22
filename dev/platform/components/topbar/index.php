<?php

function platform_render_topbar(array $data = []): string
{
    $strings = PlatformData::getStrings();
    $topbar = $strings['topbar'] ?? [];

    $title = $data['title'] ?? $topbar['title'] ?? 'Portfolio CMS';
    $subtitle = $data['subtitle'] ?? $topbar['subtitle'] ?? '';
    $logo = $data['logo'] ?? $topbar['logo'] ?? ['svg' => 'cms'];
    $logoName = is_array($logo) ? ($logo['svg'] ?? $logo['name'] ?? '') : '';

    $logoHtml = '';
    if ($logoName !== '') {
        $logoHtml = PlatformSvg::render(['name' => $logoName, 'size' => 22, 'class' => 'platform-topbar-logo-icon']);
    }

    $actions = $data['actions'] ?? '';

    $showFront = $data['show_frontpage'] ?? true;
    if ($showFront) {
        $actions .= platform_topbar_frontpage_button($topbar['viewFrontpage'] ?? 'View');
    }

    return '<div class="platform-topbar">'
        . '<a class="platform-topbar-brand" href="./" title="' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '">'
        . '<span class="platform-topbar-logo">' . $logoHtml . '</span>'
        . '<span class="platform-topbar-copy">'
        . '<h1 class="platform-title">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h1>'
        . ($subtitle !== '' ? '<span class="platform-subtitle">' . htmlspecialchars($subtitle, ENT_QUOTES, 'UTF-8') . '</span>' : '')
        . '</span>'
        . '</a>'
        . (($data['show_nav'] ?? true) ? platform_topbar_nav() : '')
        . '<div class="platform-topbar-actions">'
        . (($data['show_nav'] ?? true) ? '<button class="platform-topnav-toggle" type="button" aria-expanded="false" aria-controls="platform-topnav" data-topnav-toggle title="' . htmlspecialchars(PlatformData::getString('topbar.menu', 'Menu'), ENT_QUOTES, 'UTF-8') . '">'
            . PlatformSvg::render(['name' => 'menu', 'size' => 18, 'class' => 'platform-topnav-toggle-open'])
            . PlatformSvg::render(['name' => 'close', 'size' => 18, 'class' => 'platform-topnav-toggle-close'])
            . '<span class="platform-topnav-toggle-label">' . htmlspecialchars(PlatformData::getString('topbar.menu', 'Menu'), ENT_QUOTES, 'UTF-8') . '</span></button>' : '')
        . $actions . '</div>'
        . '</div>';
}

function platform_topbar_front_post_id(): string
{
    $root = defined('ENGINE_PROJECT_ROOT') ? ENGINE_PROJECT_ROOT : dirname(__DIR__, 4);
    $path = $root . '/src/content/json/data/settings/data_settings_routing.json';
    if (!file_exists($path)) return '';

    $routing = json_decode((string) file_get_contents($path), true);
    if (!is_array($routing)) return '';

    $mappings = $routing['routing']['mappings'] ?? [];
    return is_array($mappings) ? trim((string) ($mappings['front_post_id'] ?? '')) : '';
}

function platform_topbar_frontpage_button(string $label): string
{
    $frontId = platform_topbar_front_post_id();
    if ($frontId === '') return '';

    $url = 'preview/?_id=' . rawurlencode($frontId);

    $title = PlatformData::getString('topbar.viewFrontpageTitle', 'View the front page') . ' (' . $frontId . ')';

    return '<a class="platform-button platform-topbar-front" href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '"'
        . ' target="_blank" rel="noopener" title="' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '" aria-label="' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '">'
        . PlatformSvg::render(['name' => 'view', 'size' => 14])
        . '</a>';
}

function platform_topbar_nav(): string
{
    if (!function_exists('platform_menu')) return '';

    $e = static fn (string $v): string => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
    $page = (string) ($_GET['page'] ?? 'home');
    if (in_array($page, ['content', 'edit-posts', 'edit-taxonomies', 'edit-collections'], true)) $page = 'content-types';
    $current = '?' . (string) ($_SERVER['QUERY_STRING'] ?? '');
    if ($current === '?') $current = '?page=home';

    $subLink = static function (array $item, bool $withIcon = true) use ($e, $current): string {
        $url = (string) ($item['url'] ?? '');
        $desc = (string) ($item['desc'] ?? '');
        return '<li><a class="platform-topnav-sublink" href="' . $e($url) . '"' . ($url === $current ? ' aria-current="page"' : '') . '>'
            . ($withIcon && !empty($item['icon']) ? '<span class="platform-topnav-subicon">' . PlatformSvg::render(['name' => (string) $item['icon'], 'size' => 14]) . '</span>' : '')
            . '<span class="platform-topnav-subcopy"><span class="platform-topnav-subtitle">' . $e((string) ($item['label'] ?? '')) . '</span>'
            . ($desc !== '' && $desc !== ($item['label'] ?? '') ? '<span class="platform-topnav-subdesc">' . $e($desc) . '</span>' : '')
            . '</span></a></li>';
    };

    $html = '<nav class="platform-topnav" id="platform-topnav" aria-label="' . $e(PlatformData::getString('topbar.nav', 'Main menu')) . '" data-topnav><ul class="platform-topnav-list">';
    foreach (platform_menu() as $key => $item) {
        $children = (array) ($item['children'] ?? []);
        $active = $key === $page;
        $label = (string) ($item['label'] ?? $key);
        $url = (string) ($item['url'] ?? '');

        $body = '';
        if ($children !== [] && $key === 'content-types') {
            $body .= '<div class="platform-topnav-groups">';
            foreach ($children as $group) {
                $body .= '<div class="platform-topnav-group">'
                    . '<a class="platform-topnav-group-title" href="' . $e((string) ($group['url'] ?? '')) . '">'
                    . PlatformSvg::render(['name' => (string) ($group['icon'] ?? 'web'), 'size' => 13]) . '<span>' . $e((string) ($group['label'] ?? '')) . '</span></a>'
                    . '<ul class="platform-topnav-sublist">';
                foreach ((array) ($group['children'] ?? []) as $type) {
                    $body .= $subLink($type, false);
                }
                $body .= '</ul></div>';
            }
            $body .= '</div>';
        } elseif ($children !== []) {
            $body .= '<ul class="platform-topnav-sublist">';
            foreach ($children as $child) {
                $body .= $subLink($child);
            }
            $body .= '</ul>';
        }

        $sub = $body === '' ? '' : '<div class="platform-topnav-sub' . ($key === 'content-types' ? ' platform-topnav-sub-wide' : '') . '">'
            . '<div class="platform-topnav-panel">'
            . '<a class="platform-topnav-panel-head" href="' . $e($url) . '">'
            . PlatformSvg::render(['name' => (string) ($item['icon'] ?? 'web'), 'size' => 14])
            . '<span>' . $e($label) . '</span>'
            . PlatformSvg::render(['name' => 'chevron-right', 'size' => 12, 'class' => 'platform-topnav-panel-arrow'])
            . '</a>'
            . $body
            . '</div></div>';

        $html .= '<li class="platform-topnav-item' . ($sub !== '' ? ' has-sub' : '') . ($active ? ' is-active' : '') . '">'
            . '<a class="platform-topnav-link" href="' . $e($url) . '" aria-label="' . $e($label) . '" data-tip="' . $e($label) . '"' . ($active ? ' aria-current="page"' : '') . ($sub !== '' ? ' aria-haspopup="true"' : '') . '>'
            . PlatformSvg::render(['name' => (string) ($item['icon'] ?? 'web'), 'size' => 16, 'class' => 'platform-topnav-icon'])
            . '<span class="platform-topnav-label">' . $e($label) . '</span>'
            . '</a>'
            . $sub
            . '</li>';
    }

    return $html . '</ul></nav>';
}
