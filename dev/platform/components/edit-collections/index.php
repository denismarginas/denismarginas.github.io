<?php

function platform_render_edit_collections_fragment(string $tab = ''): string
{
    platform_asset('css', 'components/edit-collections/assets/css/edit-collections.css');
    platform_asset('css', 'components/json-editor/assets/css/json-editor.css');
    platform_asset('js', 'components/json-editor/assets/js/json-editor.js');
    platform_asset('js', 'components/json-editor/assets/js/parts/json-editor-object.js');
    platform_asset('js', 'components/json-editor/assets/js/parts/json-editor-key-value.js');
    platform_asset('js', 'components/json-editor/assets/js/parts/json-editor-array.js');
    platform_asset('js', 'components/json-editor/assets/js/parts/json-editor-primitive.js');
    platform_asset('js', 'components/json-editor/assets/js/parts/json-editor-path.js');
    platform_asset('js', 'components/edit-collections/assets/js/edit-collections.js');

    $entry = platform_entry_context('collection');

    ob_start();
    ?>
    <div class="platform-admin-page platform-entry-single" data-ec-root>
        <div class="platform-entry-bar"><?php echo platform_entry_back_link($entry); ?></div>
        <div class="platform-ep-layout">
            <aside class="platform-ep-sidebar">
                <div class="platform-card">
                    <div class="platform-card-header">
                        <h2 class="platform-card-title" data-platform-i18n="editCollections.types">Collection Types</h2>
                    </div>
                    <div class="platform-list" data-ec-types><div class="platform-list-item" style="color:var(--platform-color-text-secondary)">Loading collections...</div></div>
                    <div class="platform-form-row platform-ep-routable-row" data-ec-routable-wrap style="display:none">
                        <label class="platform-checkbox-label">
                            <input class="platform-checkbox" type="checkbox" data-ec-routable>
                            <span data-platform-i18n="editPosts.routable">Routable</span>
                        </label>
                    </div>
                </div>
                <div class="platform-card" data-ec-entry-list-wrap style="display:none;max-height: 300px;overflow: auto;">
                    <div class="platform-card-header">
                        <h2 class="platform-card-title" data-platform-i18n="editCollections.entries">Entries</h2>
                    </div>
                    <div class="platform-form-row">
                        <input class="platform-ep-input" type="text" data-ec-search data-platform-placeholder="editCollections.searchEntries" placeholder="Search entries..." style="width:100%">
                    </div>
                    <div class="platform-list" data-ec-list></div>
                </div>
            </aside>

            <main class="platform-ep-main" data-ec-editor style="display:none">
                <section class="platform-card">
                    <div class="platform-card-header">
                        <h2 class="platform-card-title" data-ec-title>Entry</h2>
                        <div class="platform-topbar-actions">
                            <button class="platform-button platform-button-sm platform-button-ghost" type="button" data-ec-action="new"><?php echo PlatformSvg::render(['name' => 'add', 'size' => 14]); ?><span data-platform-i18n="editCollections.new">New</span></button>
                            <button class="platform-button platform-button-sm platform-button-ghost" type="button" data-ec-action="delete" style="color:var(--platform-danger)"><?php echo PlatformSvg::render(['name' => 'trash', 'size' => 14]); ?><span data-platform-i18n="editCollections.delete">Delete</span></button>
                            <button class="platform-button platform-button-sm" type="button" data-ec-action="save"><?php echo PlatformSvg::render(['name' => 'save', 'size' => 14]); ?><span data-platform-i18n="editCollections.save">Save</span></button>
                        </div>
                    </div>
                    <div class="platform-card-body platform-bg-grid">
                        <div data-ec-json-editor></div>
                    </div>
                </section>
            </main>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
