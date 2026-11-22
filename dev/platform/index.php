<?php

require __DIR__ . '/core/autoload.php';
require __DIR__ . '/core/shell.php';

$pages = platform_pages();
$menu = platform_menu();

$page = $_GET['page'] ?? 'home';
$tab = $_GET['tab'] ?? $_GET['type'] ?? '';

$pageUnknown = !isset($pages[$page]);
if ($pageUnknown) {
    http_response_code(404);
    $page = 'home';
    $tab = '';
}

platform_asset('css', 'assets/css/style.css');
platform_asset('css', 'components/svg/assets/css/svg.css');
platform_asset('css', 'components/topbar/assets/css/topbar.css');
platform_asset('css', 'assets/css/platform-admin.css');

$footer = platform_render_footer();

platform_asset('js', 'assets/js/platform.js');
platform_asset('js', 'assets/js/parts/platform-utils.js');
platform_asset('js', 'assets/js/parts/platform-escape.js');
platform_asset('js', 'assets/js/parts/platform-svg.js');
platform_asset('js', 'assets/js/parts/platform-data.js');
platform_asset('js', 'assets/js/parts/platform-menu.js');
platform_asset('js', 'assets/js/parts/platform-entry.js');
platform_asset('js', 'components/topbar/assets/js/topbar.js');

$renderFn = $pages[$page]['render'];
require_once __DIR__ . '/' . $pages[$page]['file'];
$content = $renderFn($tab);

if ($pageUnknown) {
    $content = '<div class="platform-admin-page">'
        . '<div class="platform-card">'
        . '<h2 class="platform-card-title">Page not found</h2>'
        . '<p class="platform-empty">The requested page does not exist.</p>'
        . '</div>'
        . '</div>';
}

$cssLinks = '';
foreach ($GLOBALS['_platform_assets']['css'] as $href) {
    $cssLinks .= '<link rel="stylesheet" href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '">' . "\n    ";
}

$jsScripts = '';
foreach ($GLOBALS['_platform_assets']['js'] as $src) {
    $jsScripts .= '<script src="' . htmlspecialchars($src, ENT_QUOTES, 'UTF-8') . '" defer></script>' . "\n    ";
}

$pageTitle = ($GLOBALS['_platform_page_title'] ?? $pages[$page]['title']) . ' ' . PlatformData::getString('pageTitleSuffix', '| Portfolio CMS');
$metaDesc = PlatformData::getString('meta_description');
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="assets/img/favicon/favicon.ico">
    <meta name="description" content="<?php echo htmlspecialchars($metaDesc, ENT_QUOTES, 'UTF-8'); ?>">
    <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
    <?php echo $cssLinks; ?>
</head>
<body class="platform-body">
    <div class="platform-admin">
        <header class="platform-admin-header">
            <?php echo platform_render_topbar(); ?>
        </header>

        <div class="platform-admin-body">
            <aside class="platform-admin-menu">
                <?php echo platform_render_menu($menu, $page, $tab, platform_active_content_key($page)); ?>
            </aside>

            <main class="platform-admin-main">
                <?php echo $content; ?>
            </main>
        </div>

        <footer class="platform-admin-footer">
            <?php echo $footer; ?>
        </footer>
    </div>

    <?php echo $jsScripts; ?>
</body>
</html>
