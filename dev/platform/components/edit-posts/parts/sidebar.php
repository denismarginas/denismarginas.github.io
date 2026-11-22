            <aside class="platform-ep-sidebar">
                <div class="platform-card">
                    <div class="platform-card-header">
                        <h2 class="platform-card-title" data-ep-i18n="editPosts.postTypes">Post Types</h2>
                    </div>
                    <div class="platform-list" data-ep-post-types><div class="platform-list-item" style="color:var(--platform-color-text-secondary)" data-ep-i18n="editPosts.loadingPostTypes">Loading post types...</div></div>
                    <div class="platform-form-row platform-ep-routable-row" data-ep-routable-wrap style="display:none">
                        <label class="platform-checkbox-label">
                            <input class="platform-checkbox" type="checkbox" data-ep-routable>
                            <span data-ep-i18n="editPosts.routable">Routable</span>
                        </label>
                    </div>
                </div>
                <div class="platform-card" data-ep-post-list-wrap style="display:none;max-height: 300px;overflow: auto;">
                    <div class="platform-card-header">
                        <h2 class="platform-card-title" data-ep-i18n="editPosts.posts">Posts</h2>
                    </div>
                    <div class="platform-form-row">
                        <input class="platform-ep-input" type="text" data-ep-search data-ep-placeholder="editPosts.searchPosts" placeholder="Search posts..." style="width:100%">
                    </div>
                    <div class="platform-list" data-ep-post-list></div>
                </div>
            </aside>
