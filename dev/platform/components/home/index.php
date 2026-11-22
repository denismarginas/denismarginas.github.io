<?php

function platform_render_home_fragment(string $tab = ''): string
{
    platform_asset('js', 'components/home/assets/js/home.js');
    require_once __DIR__ . '/../content-search/index.php';
    require_once __DIR__ . '/../dashboard-status/index.php';

    $get = static function (string $path, string $default = ''): string {
        return PlatformData::getString($path, $default);
    };

    $cards = [];
    foreach (platform_menu() as $key => $item) {
        if ($key === 'home' || $key === 'content-types') continue;
        $cards[] = [
            'title' => $item['label'],
            'desc'  => $item['desc'] ?? '',
            'icon'  => $item['icon'],
            'url'   => $item['url'],
        ];
    }
    $cards[] = [
        'title'  => $get('home.compileHtml', 'Compile HTML'),
        'desc'   => $get('home.compileHtmlDesc', 'Write static HTML for every post to the dist folder.'),
        'icon'   => 'compile',
        'action' => 'compile-html',
    ];

    ob_start();
    ?>
    <div class="platform-admin-page" data-home-root>
        <div class="platform-page-heading">
            <h2 class="platform-title"><?php echo htmlspecialchars($get('home.title', 'Dashboard'), ENT_QUOTES, 'UTF-8'); ?></h2>
            <p class="platform-description"><?php echo htmlspecialchars($get('home.description', 'Build, render and publish the portfolio.'), ENT_QUOTES, 'UTF-8'); ?></p>
        </div>

        <?php echo platform_render_cards($cards, 'tiles'); ?>

        <?php echo platform_render_search_panels(); ?>

        <?php echo platform_render_dashboard_status(); ?>
    </div>
    <?php
    return ob_get_clean();
}
