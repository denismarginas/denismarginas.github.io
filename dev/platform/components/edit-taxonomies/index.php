<?php

function platform_render_edit_taxonomies_fragment(string $tab = ''): string
{
    platform_asset('css', 'components/edit-taxonomies/assets/css/edit-taxonomies.css');
    platform_asset('css', 'components/json-editor/assets/css/json-editor.css');
    platform_asset('js', 'components/json-editor/assets/js/json-editor.js');
    platform_asset('js', 'components/json-editor/assets/js/parts/json-editor-object.js');
    platform_asset('js', 'components/json-editor/assets/js/parts/json-editor-key-value.js');
    platform_asset('js', 'components/json-editor/assets/js/parts/json-editor-array.js');
    platform_asset('js', 'components/json-editor/assets/js/parts/json-editor-primitive.js');
    platform_asset('js', 'components/json-editor/assets/js/parts/json-editor-path.js');
    platform_asset('js', 'components/edit-taxonomies/assets/js/edit-taxonomies.js');

    $get = static function (string $path, string $default = ''): string {
        return PlatformData::getString($path, $default);
    };

    $entry = platform_entry_context('taxonomy');

    ob_start();
    ?>
    <div class="platform-admin-page platform-entry-single" data-et-root>
        <div class="platform-entry-bar"><?php echo platform_entry_back_link($entry); ?></div>
        <div class="platform-ep-layout">
            <aside class="platform-ep-sidebar">
                <div class="platform-card">
                    <div class="platform-card-header">
                        <h2 class="platform-card-title" data-platform-i18n="editTaxonomies.types">Taxonomy Types</h2>
                    </div>
                    <div class="platform-list" data-et-types><div class="platform-list-item" style="color:var(--platform-color-text-secondary)">Loading taxonomy types...</div></div>
                    <div class="platform-form-row platform-ep-routable-row" data-et-routable-wrap style="display:none">
                        <label class="platform-checkbox-label">
                            <input class="platform-checkbox" type="checkbox" data-et-routable>
                            <span data-platform-i18n="editPosts.routable">Routable</span>
                        </label>
                    </div>
                </div>
                <div class="platform-card" data-et-term-list-wrap style="display:none;max-height: 300px;overflow: auto;">
                    <div class="platform-card-header">
                        <h2 class="platform-card-title" data-platform-i18n="editTaxonomies.terms">Terms</h2>
                    </div>
                    <div class="platform-form-row">
                        <input class="platform-ep-input" type="text" data-et-search data-platform-placeholder="editTaxonomies.searchTerms" placeholder="Search terms..." style="width:100%">
                    </div>
                    <div class="platform-list" data-et-list></div>
                </div>
            </aside>

            <main class="platform-ep-main" data-et-editor style="display:none">
                <section class="platform-card">
                    <div class="platform-card-header">
                        <h2 class="platform-card-title" data-et-title>Taxonomy Term</h2>
                        <div class="platform-topbar-actions">
                            <button class="platform-button platform-button-sm platform-button-ghost" type="button" data-et-action="new"><?php echo PlatformSvg::render(['name' => 'add', 'size' => 14]); ?><span data-platform-i18n="editTaxonomies.new">New</span></button>
                            <button class="platform-button platform-button-sm platform-button-ghost" type="button" data-et-action="delete" style="color:var(--platform-danger)"><?php echo PlatformSvg::render(['name' => 'trash', 'size' => 14]); ?><span data-platform-i18n="editTaxonomies.delete">Delete</span></button>
                            <button class="platform-button platform-button-sm" type="button" data-et-action="save"><?php echo PlatformSvg::render(['name' => 'save', 'size' => 14]); ?><span data-platform-i18n="editTaxonomies.save">Save</span></button>
                        </div>
                    </div>
                    <div class="platform-card-body platform-bg-grid">
                        <div data-et-json-editor></div>
                    </div>
                </section>
            </main>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
