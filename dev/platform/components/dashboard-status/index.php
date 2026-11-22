<?php

function platform_render_dashboard_status(): string
{
    platform_asset('css', 'components/dashboard-status/assets/css/dashboard-status.css');
    platform_asset('js', 'components/dashboard-status/assets/js/dashboard-status.js');

    $get = static fn (string $path, string $default = ''): string => PlatformData::getString($path, $default);
    $e = static fn (string $v): string => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');

    $strings = PlatformData::getStrings()['dashboardStatus'] ?? [];
    $cards = [
        'translations' => [
            'icon'  => 'translate',
            'title' => $get('dashboardStatus.translations', 'Translations'),
            'links' => [['label' => $get('dashboardStatus.openTranslate', 'Open Translate'), 'url' => '?page=generate&tab=translate']],
            'run'   => '',
        ],
        'recent' => [
            'icon'  => 'clock',
            'title' => $get('dashboardStatus.recent', 'Recently edited'),
            'links' => [['label' => $get('dashboardStatus.openSettings', 'All data files'), 'url' => '?page=settings']],
            'run'   => '',
        ],
        'html' => [
            'icon'  => 'html',
            'title' => $get('dashboardStatus.html', 'HTML Render'),
            'links' => [['label' => $get('dashboardStatus.openRender', 'Open HTML Render'), 'url' => '?page=render&tab=render-html']],
            'run'   => $get('dashboardStatus.runRender', 'Render all pages'),
        ],
        'projects' => [
            'icon'  => 'web',
            'title' => $get('dashboardStatus.projects', 'Projects'),
            'links' => [
                ['label' => $get('dashboardStatus.openThumbnails', 'Generate Thumbnails'), 'url' => '?page=generate&tab=thumbnails'],
                ['label' => $get('dashboardStatus.openProjects', 'All projects'), 'url' => platform_content_list_url('post', 'project')],
            ],
            'run'   => '',
        ],
    ];

    $icons = [];
    foreach (['ok' => 'check', 'warn' => 'warning', 'error' => 'close', 'clock' => 'clock', 'pages' => 'file-text', 'outdated' => 'update', 'projects' => 'layers', 'rendered' => 'html', 'hidden' => 'eye-off', 'thumbnail' => 'image', 'logo' => 'web'] as $key => $name) {
        $icons[$key] = PlatformSvg::render(['name' => $name, 'size' => $key === 'ok' || $key === 'warn' || $key === 'error' ? 16 : 22]);
    }

    $html = '<section class="platform-ds" data-ds-root data-api="api/dashboard-status.php" data-render-api="api/compile-html.php" data-strings="' . $e((string) json_encode($strings, JSON_UNESCAPED_UNICODE)) . '" data-icons="' . $e((string) json_encode($icons, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) . '">'
        . '<div class="platform-ds-grid">';

    foreach ($cards as $key => $card) {
        if ($key === 'translations') $html .= '<div class="platform-ds-stack">';
        $html .= '<article class="platform-ds-card" data-ds-card="' . $e($key) . '" aria-busy="true">'
            . '<header class="platform-ds-head">'
            . '<span class="platform-ds-icon">' . PlatformSvg::render(['name' => $card['icon'], 'size' => 20]) . '</span>'
            . '<h3 class="platform-ds-title">' . $e($card['title']) . '</h3>'
            . '<span class="platform-ds-badge" data-ds-badge></span>'
            . '<button class="platform-ds-check" type="button" data-ds-refresh title="' . $e($get('dashboardStatus.check', 'Check again')) . '" aria-label="' . $e($get('dashboardStatus.check', 'Check again')) . '">' . PlatformSvg::render(['name' => 'update', 'size' => 16]) . '</button>'
            . '</header>'
            . '<div class="platform-ds-body" data-ds-body><p class="platform-ds-muted">' . $e($get('dashboardStatus.loading', 'Checking...')) . '</p></div>'
            . '<footer class="platform-ds-foot">'
            . '<span class="platform-ds-time" data-ds-time></span>';
        if ($card['run'] !== '') {
            $html .= '<button class="platform-ds-action is-primary" type="button" data-ds-run="' . $e($key) . '">' . PlatformSvg::render(['name' => 'html', 'size' => 13]) . '<span>' . $e($card['run']) . '</span></button>';
        }
        foreach ($card['links'] as $link) {
            $html .= '<a class="platform-ds-action" href="' . $e($link['url']) . '">' . $e($link['label']) . PlatformSvg::render(['name' => 'chevron-right', 'size' => 11]) . '</a>';
        }
        $html .= '</footer></article>';
        if ($key === 'recent') $html .= '</div>';
    }

    return $html . '</div></section>';
}
