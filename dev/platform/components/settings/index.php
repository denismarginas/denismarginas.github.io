<?php

function platform_render_settings_fragment(string $tab = ''): string
{
    platform_asset('css', 'components/settings/assets/css/settings.css');
    platform_asset('css', 'components/json-editor/assets/css/json-editor.css');

    platform_asset('js', 'components/json-editor/assets/js/json-editor.js');
    platform_asset('js', 'components/json-editor/assets/js/parts/json-editor-object.js');
    platform_asset('js', 'components/json-editor/assets/js/parts/json-editor-key-value.js');
    platform_asset('js', 'components/json-editor/assets/js/parts/json-editor-array.js');
    platform_asset('js', 'components/json-editor/assets/js/parts/json-editor-primitive.js');
    platform_asset('js', 'components/json-editor/assets/js/parts/json-editor-path.js');
    platform_asset('js', 'components/settings/assets/js/settings.js');
    platform_asset('js', 'components/settings/assets/js/settings-tools.js');

    $tools = PlatformSettings::tools();

    ob_start();
    ?>
    <div class="platform-admin-page platform-settings-root" data-settings-root>
        <div class="platform-page-heading">
            <h2 class="platform-title">Settings</h2>
            <p class="platform-description">Edit global settings and content data files as JSON.</p>
        </div>

        <section class="platform-card platform-settings-tools" data-settings-tools data-settings-api="api/platform-settings.php">
            <div class="platform-settings-tools-title">
                <?php echo PlatformSvg::render(['name' => 'settings', 'size' => 14]); ?>
                <span>Platform tools</span>
            </div>
            <label class="platform-checkbox-label">
                <input class="platform-checkbox" type="checkbox" data-settings-tool="performance_monitor"<?php echo $tools['performance_monitor'] ? ' checked' : ''; ?>>
                <span>Performance monitor</span>
            </label>
            <span class="platform-hint">FPS, frame times, long tasks and layout shifts on every preview page, with a score. Previews only, never in compiled pages.</span>
            <span class="platform-status" data-settings-tools-status></span>
        </section>

        <div class="platform-settings-layout">
            <aside class="platform-settings-sidebar">
                <div class="platform-card">
                    <div class="platform-card-header">
                        <h2 class="platform-card-title">Files</h2>
                    </div>
                    <div class="platform-form-row">
                        <input class="platform-input" type="text" data-settings-search placeholder="Filter files..." style="width:100%">
                    </div>
                    <div class="platform-settings-file-list" data-settings-file-list></div>
                </div>
            </aside>

            <main class="platform-settings-main">
                <div class="platform-card">
                    <div class="platform-card-header">
                        <h2 class="platform-card-title" data-settings-file-title>Select a file</h2>
                        <div class="platform-topbar-actions">
                            <span class="platform-status" data-settings-status></span>
                            <button class="platform-button platform-button-sm" type="button" data-settings-save disabled>Save</button>
                        </div>
                    </div>
                    <div class="platform-card-body">
                        <div data-settings-json-editor>
                            <p class="platform-empty">Choose a JSON file from the list.</p>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
