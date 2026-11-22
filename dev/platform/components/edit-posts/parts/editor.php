            <main class="platform-ep-main" data-ep-editor style="display:none">
                <div class="platform-status platform-button platform-button-sm platform-button-ghost" data-ep-status></div>

                <section class="platform-card">
                    <div class="platform-card-header">
                        <h2 class="platform-card-title" data-ep-post-title data-ep-i18n="editPosts.post">Post</h2>
                        <div class="platform-topbar-actions">
                            <span class="platform-tag" data-ep-post-id></span>
                            <a class="platform-button platform-button-sm platform-button-ghost" data-ep-action="view" href="#" target="_blank" style="display:none"><?php echo PlatformSvg::render(['name' => 'view', 'size' => 14]); ?><span data-ep-i18n="editPosts.view">View</span></a>
                            <button class="platform-button platform-button-sm platform-button-ghost" type="button" data-ep-action="duplicate"><?php echo PlatformSvg::render(['name' => 'duplicate', 'size' => 14]); ?><span data-ep-i18n="editPosts.duplicate">Duplicate</span></button>
                            <button class="platform-button platform-button-sm platform-button-ghost" type="button" data-ep-action="delete" style="color:var(--platform-danger)"><?php echo PlatformSvg::render(['name' => 'trash', 'size' => 14]); ?><span data-ep-i18n="editPosts.delete">Delete</span></button>
                            <button class="platform-button platform-button-sm" type="button" data-ep-action="save"><?php echo PlatformSvg::render(['name' => 'save', 'size' => 14]); ?><span data-ep-i18n="editPosts.save">Save Post</span></button>
                            <label class="platform-je-toggle-label">
                                <input class="platform-checkbox" type="checkbox" data-ep-toggle-json> <span data-ep-i18n="editPosts.jsonMode">JSON</span>
                            </label>
                        </div>
                    </div>
                    <div class="platform-card-body platform-bg-grid platform-cards-container">
                        <div class="platform-cards-container" data-ep-fields-container></div>
                        <div data-ep-json-editor style="display:none"></div>
                    </div>
                </section>

                <section class="platform-card">
                    <div class="platform-card-header">
                        <h2 class="platform-card-title" data-ep-i18n="editPosts.contentBuilder">Content Builder</h2>
                        <div class="platform-topbar-actions">
                            <button class="platform-button platform-button-sm" type="button" data-ep-action="toggle-components"><?php echo PlatformSvg::render(['name' => 'add', 'size' => 14]); ?><span data-ep-i18n="editPosts.addComponent">Add Component</span></button>
                        </div>
                    </div>
                    <div class="platform-card-body platform-bg-grid">
                        <div class="platform-ep-content-list" data-ep-content-list>
                            <div class="platform-ep-content-empty" data-ep-i18n="editPosts.noContent">No content items. Click "Add Component" to add one.</div>
                        </div>
                    </div>
                </section>

                <div class="platform-ep-component-palette" data-ep-palette style="display:none">
                    <div class="platform-card">
                        <div class="platform-card-header">
                            <h2 class="platform-card-title" data-ep-i18n="editPosts.components">Components</h2>
                            <button class="platform-button platform-button-sm platform-button-ghost" type="button" data-ep-action="close-palette"><?php echo PlatformSvg::render(['name' => 'close', 'size' => 14]); ?></button>
                        </div>
                        <div class="platform-card-body">
                            <div class="platform-form-row">
                                <input class="platform-ep-input" type="text" data-ep-palette-search data-ep-placeholder="editPosts.filterComponents" placeholder="Filter components..." style="width:100%">
                            </div>
                            <div class="platform-ep-palette-grid" data-ep-palette-list></div>
                        </div>
                    </div>
                </div>
            </main>