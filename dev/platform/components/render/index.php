<?php

function platform_render_render_fragment(string $tab = ''): string
{
    platform_asset('css', 'components/render/assets/css/render.css');
    platform_asset('js', 'components/render/assets/js/render.js');

    $tab = in_array($tab, ['compile', 'preview', 'render-html', 'seo'], true) ? $tab : 'compile';

    $icon = static function (string $name) {
        return PlatformSvg::render(['name' => $name, 'size' => 16]);
    };

    $cards = [];
    foreach (platform_menu()['render']['children'] as $childKey => $child) {
        $cards[] = [
            'title'  => $child['label'],
            'desc'   => $child['desc'] ?? '',
            'icon'   => $child['icon'],
            'url'    => $child['url'],
            'active' => $tab === $childKey,
        ];
    }

    $compileVisible = $tab === 'compile' ? '' : ' style="display:none"';
    $previewVisible = $tab === 'preview' ? '' : ' style="display:none"';
    $htmlVisible    = $tab === 'render-html' ? '' : ' style="display:none"';
    $seoVisible     = $tab === 'seo' ? '' : ' style="display:none"';

    $previewDefaults = [];
    foreach (platform_card_vars('live_preview') as $var) {
        $previewDefaults[$var['name']] = $var['value'];
    }
    $previewDefaultsJson = htmlspecialchars(json_encode($previewDefaults), ENT_QUOTES, 'UTF-8');

    ob_start();
?>
    <div class="platform-admin-page" data-render-root data-render-tab="<?php echo htmlspecialchars($tab, ENT_QUOTES, 'UTF-8'); ?>" data-render-preview-defaults="<?php echo $previewDefaultsJson; ?>">
        <div class="platform-page-heading">
            <h2 class="platform-title">Render</h2>
            <p class="platform-description">Compile stylesheets, preview rendered posts and build static HTML.</p>
        </div>

        <?php echo platform_render_cards($cards); ?>

        <section class="platform-render-panel" data-render-panel-compile<?php echo $compileVisible; ?>>
            <div class="platform-card">
                <div class="platform-card-header">
                    <h2 class="platform-card-title">Compile SCSS</h2>
                </div>
                <div class="platform-render-toolbar">
                    <button class="platform-button" type="button" data-render-action="compile-scss"><?php echo $icon('compile'); ?>Run compile</button>
                    <span class="platform-status" data-render-status>Idle</span>
                </div>
                <div class="platform-render-results" data-render-scss-results></div>
                <?php echo platform_render_vars('compile_scss', 'compile-scss'); ?>
            </div>
        </section>

        <section class="platform-render-panel" data-render-panel-render-html<?php echo $htmlVisible; ?>>
            <div class="platform-card">
                <div class="platform-card-header">
                    <h2 class="platform-card-title">HTML Bulk Render</h2>
                </div>
                <div class="platform-render-toolbar">
                    <button class="platform-button" type="button" data-render-action="compile-html"><?php echo $icon('html'); ?>Run render</button>
                    <span class="platform-status" data-render-html-status>Idle</span>
                </div>
                <div class="platform-render-results" data-render-html-results></div>
                <?php echo platform_render_vars('render', 'compile-html'); ?>
            </div>
        </section>

        <section class="platform-render-panel" data-render-panel-seo<?php echo $seoVisible; ?>>
            <div class="platform-card">
                <div class="platform-card-header">
                    <h2 class="platform-card-title">SEO Files</h2>
                </div>
                <p class="platform-description">sitemap.xml and robots.txt in dist/. Lists the pages the HTML bulk render writes, without the ones whose seo.index is false (unless include_noindex).</p>
                <div class="platform-render-toolbar">
                    <button class="platform-button" type="button" data-render-action="seo-files"><?php echo $icon('generate'); ?>Build files</button>
                    <span class="platform-status" data-render-seo-status>Idle</span>
                </div>
                <div class="platform-render-results" data-render-seo-results></div>
                <div class="platform-render-seo-files" data-render-seo-files></div>
                <?php echo platform_render_vars('seo_files', 'seo-files'); ?>
            </div>
        </section>

        <section class="platform-render-panel" data-render-panel-preview<?php echo $previewVisible; ?>>
            <div class="platform-card">
                <div class="platform-card-header">
                    <h2 class="platform-card-title">Live Preview</h2>
                </div>
                <div class="platform-render-toolbar">
                    <select class="platform-input" data-render-post-type style="max-width:260px"></select>
                    <select class="platform-input" data-render-post style="max-width:260px"></select>
                    <a class="platform-button" data-render-open href="preview/?_id=home" target="_blank"><?php echo $icon('view'); ?>Open preview</a>
                </div>
                <iframe class="platform-render-preview-iframe" data-render-preview src="preview/?_id=home" title="Live preview"></iframe>
                <?php echo platform_render_vars('live_preview', 'preview'); ?>
            </div>
        </section>
    </div>
<?php
    return ob_get_clean();
}
