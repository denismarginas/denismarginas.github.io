<?php

function platform_render_footer(): string
{
    platform_asset('css', 'components/footer/assets/css/footer.css');
    platform_asset('js', 'components/footer/assets/js/footer.js');

    $get = static function (string $path, string $default = ''): string {
        return PlatformData::getString($path, $default);
    };
    $e = static fn (string $v): string => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');

    $current = '?' . (string) ($_SERVER['QUERY_STRING'] ?? '');
    $link = static function (string $label, string $url, array $options = []) use ($e, $current): string {
        $external = !empty($options['external']);
        $active = !$external && $url === $current;
        return '<li><a class="platform-footer-link" href="' . $e($url) . '"'
            . ($external ? ' target="_blank" rel="noopener"' : '')
            . ($active ? ' aria-current="page"' : '')
            . (!empty($options['title']) ? ' title="' . $e($options['title']) . '"' : '') . '>'
            . '<span>' . $e($label) . '</span>'
            . ($external ? PlatformSvg::render(['name' => 'view', 'size' => 11, 'class' => 'platform-footer-link-icon']) : '')
            . '</a></li>';
    };
    $column = static function (string $title, string $links): string {
        return $links === '' ? '' : '<h3 class="platform-footer-heading">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h3><ul class="platform-footer-links">' . $links . '</ul>';
    };

    $menu = platform_menu();
    $content = $menu['content-types']['children'] ?? [];
    $typeLinks = static function (array $group) use ($link): string {
        $html = '';
        foreach (($group['children'] ?? []) as $item) {
            $html .= $link((string) $item['label'], (string) $item['url']);
        }
        return $html;
    };

    $tools = '';
    foreach (['render', 'generate'] as $key) {
        foreach (($menu[$key]['children'] ?? []) as $item) {
            $tools .= $link((string) $item['label'], (string) $item['url'], ['title' => (string) ($item['desc'] ?? '')]);
        }
    }
    $tools .= $link((string) ($menu['settings']['label'] ?? 'Settings'), (string) ($menu['settings']['url'] ?? '?page=settings'));

    $root = defined('ENGINE_PROJECT_ROOT') ? ENGINE_PROJECT_ROOT : dirname(__DIR__, 4);
    $readJson = static function (string $path): array {
        $decoded = file_exists($path) ? json_decode((string) file_get_contents($path), true) : null;
        return is_array($decoded) ? $decoded : [];
    };
    $site = $readJson($root . '/src/content/json/data/settings/data_settings_site.json');
    $routing = $readJson($root . '/src/content/json/data/settings/data_settings_routing.json');
    $liveUrl = trim((string) ($site['site_url'] ?? $routing['url'] ?? ''));
    $frontId = trim((string) ($routing['routing']['mappings']['front_post_id'] ?? ''));

    $siteLinks = '';
    if ($frontId !== '') {
        $siteLinks .= $link($get('footer.frontPage', 'Front page preview'), 'preview/?_id=' . rawurlencode($frontId), ['external' => true]);
    }
    if (is_dir($root . '/dist')) {
        $siteLinks .= $link($get('footer.builtSite', 'Built site (dist)'), '../../dist/', ['external' => true]);
        if (file_exists($root . '/dist/sitemap.xml')) {
            $siteLinks .= $link($get('footer.sitemap', 'sitemap.xml'), '../../dist/sitemap.xml', ['external' => true]);
        }
    }
    if ($liveUrl !== '' && preg_match('~^https?://~i', $liveUrl)) {
        $siteLinks .= $link($get('footer.liveSite', 'Live site'), $liveUrl, ['external' => true, 'title' => $liveUrl]);
    }

    $strings = PlatformData::getStrings();
    $topbar = $strings['topbar'] ?? [];
    $title = (string) ($topbar['title'] ?? 'Portfolio CMS');
    $subtitle = (string) ($topbar['subtitle'] ?? '');
    $logo = is_array($topbar['logo'] ?? null) ? (string) ($topbar['logo']['svg'] ?? 'cms') : 'cms';

    $meta = [];
    $meta[] = 'PHP ' . PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION . '.' . PHP_RELEASE_VERSION;
    if (!empty($_SERVER['HTTP_HOST'])) $meta[] = (string) $_SERVER['HTTP_HOST'];

    $searchLabel = $get('footer.search', 'Search content');

    return '<div class="platform-footer">'
        . '<div class="platform-footer-grid">'
        . '<div class="platform-footer-brand">'
        . '<a class="platform-footer-brand-head" href="./">'
        . '<span class="platform-topbar-logo">' . PlatformSvg::render(['name' => $logo, 'size' => 20, 'class' => 'platform-topbar-logo-icon']) . '</span>'
        . '<span class="platform-footer-brand-copy"><span class="platform-footer-title">' . $e($title) . '</span>'
        . ($subtitle !== '' ? '<span class="platform-footer-tagline">' . $e($subtitle) . '</span>' : '')
        . '</span></a>'
        . '<p class="platform-footer-desc">' . $e($get('footer.description', 'File-based CMS and static site generator for the portfolio.')) . '</p>'
        . '<a class="platform-footer-search" href="?page=home&amp;focus=search" data-footer-search>'
        . PlatformSvg::render(['name' => 'search', 'size' => 13])
        . '<span>' . $e($searchLabel) . '</span><kbd>Ctrl K</kbd></a>'
        . '</div>'
        . '<nav class="platform-footer-col" aria-label="' . $e($content['posts']['label'] ?? 'Posts') . '">'
        . $column((string) ($content['posts']['label'] ?? 'Posts'), $typeLinks($content['posts'] ?? []))
        . $column((string) ($content['taxonomies']['label'] ?? 'Taxonomies'), $typeLinks($content['taxonomies'] ?? []))
        . '</nav>'
        . '<nav class="platform-footer-col" aria-label="' . $e($content['collections']['label'] ?? 'Collections') . '">'
        . $column((string) ($content['collections']['label'] ?? 'Collections'), $typeLinks($content['collections'] ?? []))
        . '</nav>'
        . '<nav class="platform-footer-col" aria-label="' . $e($get('footer.tools', 'Tools')) . '">'
        . $column($get('footer.tools', 'Tools'), $tools)
        . '</nav>'
        . '<nav class="platform-footer-col" aria-label="' . $e($get('footer.site', 'Site')) . '">'
        . $column($get('footer.site', 'Site'), $siteLinks)
        . '</nav>'
        . '</div>'
        . '<div class="platform-footer-bottom">'
        . '<span>' . $e($get('footer.text', 'Platform — Portfolio CMS')) . '</span>'
        . '<span class="platform-footer-meta">' . $e(implode(' · ', $meta)) . '</span>'
        . '</div>'
        . '</div>';
}
