<?php

function platform_render_edit_posts_fragment(string $tab = ''): string
{
    platform_asset('css', 'components/edit-posts/assets/css/edit-posts.css');
    platform_asset('css', 'components/json-editor/assets/css/json-editor.css');
    platform_asset('css', 'components/image/assets/css/image.css');

    platform_asset('js', 'components/json-editor/assets/js/json-editor.js');
    platform_asset('js', 'components/json-editor/assets/js/parts/json-editor-object.js');
    platform_asset('js', 'components/json-editor/assets/js/parts/json-editor-key-value.js');
    platform_asset('js', 'components/json-editor/assets/js/parts/json-editor-array.js');
    platform_asset('js', 'components/json-editor/assets/js/parts/json-editor-primitive.js');
    platform_asset('js', 'components/json-editor/assets/js/parts/json-editor-path.js');
    platform_asset('js', 'components/edit-posts/assets/js/edit-posts-state.js');
    platform_asset('js', 'components/edit-posts/assets/js/edit-posts-utils.js');
    platform_asset('js', 'components/edit-posts/assets/js/edit-posts-core.js');
    platform_asset('js', 'components/edit-posts/assets/js/parts/edit-posts-persistence.js');
    platform_asset('js', 'components/edit-posts/assets/js/edit-posts-editor.js');
    platform_asset('js', 'components/edit-posts/assets/js/parts/edit-posts-fields.js');
    platform_asset('js', 'components/edit-posts/assets/js/parts/edit-posts-field-row.js');
    platform_asset('js', 'components/edit-posts/assets/js/parts/edit-posts-duplicate.js');
    platform_asset('js', 'components/edit-posts/assets/js/edit-posts-content.js');
    platform_asset('js', 'components/edit-posts/assets/js/parts/edit-posts-content-header.js');
    platform_asset('js', 'components/edit-posts/assets/js/parts/edit-posts-content-add-field.js');
    platform_asset('js', 'components/edit-posts/assets/js/parts/edit-posts-content-add-child.js');
    platform_asset('js', 'components/edit-posts/assets/js/parts/edit-posts-content-drag.js');
    platform_asset('js', 'components/edit-posts/assets/js/parts/edit-posts-palette.js');
    platform_asset('js', 'components/edit-posts/assets/js/parts/edit-posts-entry.js');
    platform_asset('js', 'components/edit-posts/assets/js/edit-posts-init.js');
    platform_asset('js', 'components/edit-posts/assets/js/img_preview_from_json.js');

    $entry = platform_entry_context('post');

    ob_start();
    ?>
    <div class="platform-admin-page platform-entry-single" data-ep-root data-entry-list-url="<?php echo htmlspecialchars($entry['list_url'], ENT_QUOTES, 'UTF-8'); ?>">
        <div class="platform-entry-bar"><?php echo platform_entry_back_link($entry); ?></div>
        <div class="platform-ep-layout">
            <?php require __DIR__ . '/parts/sidebar.php'; ?>
            <?php require __DIR__ . '/parts/editor.php'; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
