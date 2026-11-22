<?php

function platform_render_edit_items_fragment(string $tab = ''): string
{
    platform_asset('css', 'components/edit-items/assets/css/edit-items.css');
    platform_asset('css', 'components/json-editor/assets/css/json-editor.css');
    platform_asset('js', 'components/json-editor/assets/js/json-editor.js');
    platform_asset('js', 'components/json-editor/assets/js/parts/json-editor-object.js');
    platform_asset('js', 'components/json-editor/assets/js/parts/json-editor-key-value.js');
    platform_asset('js', 'components/json-editor/assets/js/parts/json-editor-array.js');
    platform_asset('js', 'components/json-editor/assets/js/parts/json-editor-primitive.js');
    platform_asset('js', 'components/json-editor/assets/js/parts/json-editor-path.js');
    platform_asset('js', 'components/edit-items/assets/js/edit-items.js');

    ob_start();
    ?>
    <div class="platform-admin-page" data-ei-root>
        <div class="platform-ep-layout">
            <aside class="platform-ep-sidebar">
                <div class="platform-card">
                    <div class="platform-card-header">
                        <h2 class="platform-card-title" data-platform-i18n="editItems.types">Item Types</h2>
                    </div>
                    <div class="platform-list" data-ei-types><div class="platform-list-item" style="color:var(--platform-color-text-secondary)">Loading item types...</div></div>
                    <div class="platform-form-row platform-ep-routable-row" data-ei-routable-wrap style="display:none">
                        <label class="platform-checkbox-label">
                            <input class="platform-checkbox" type="checkbox" data-ei-routable>
                            <span data-platform-i18n="editPosts.routable">Routable</span>
                        </label>
                    </div>
                </div>
                <div class="platform-card" data-ei-item-list-wrap style="display:none;max-height: 300px;overflow: auto;">
                    <div class="platform-card-header">
                        <h2 class="platform-card-title" data-platform-i18n="editItems.items">Items</h2>
                    </div>
                    <div class="platform-form-row">
                        <input class="platform-ep-input" type="text" data-ei-search data-platform-placeholder="editItems.searchItems" placeholder="Search items..." style="width:100%">
                    </div>
                    <div class="platform-list" data-ei-list></div>
                </div>
            </aside>

            <main class="platform-ep-main" data-ei-editor style="display:none">
                <section class="platform-card">
                    <div class="platform-card-header">
                        <h2 class="platform-card-title" data-ei-title>Item</h2>
                        <div class="platform-topbar-actions">
                            <button class="platform-button platform-button-sm platform-button-ghost" type="button" data-ei-action="new"><?php echo PlatformSvg::render(['name' => 'add', 'size' => 14]); ?><span data-platform-i18n="editItems.new">New</span></button>
                            <button class="platform-button platform-button-sm platform-button-ghost" type="button" data-ei-action="delete" style="color:var(--platform-danger)"><?php echo PlatformSvg::render(['name' => 'trash', 'size' => 14]); ?><span data-platform-i18n="editItems.delete">Delete</span></button>
                            <button class="platform-button platform-button-sm" type="button" data-ei-action="save"><?php echo PlatformSvg::render(['name' => 'save', 'size' => 14]); ?><span data-platform-i18n="editItems.save">Save</span></button>
                        </div>
                    </div>
                    <div class="platform-card-body platform-bg-grid">
                        <div data-ei-json-editor></div>
                    </div>
                </section>
            </main>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
