<?php

function platform_render_structure_types_fragment(string $tab = ''): string
{
    platform_asset('js', 'components/structure-types/assets/js/structure-types.js');

    $get = static function (string $path, string $default = ''): string {
        return PlatformData::getString($path, $default);
    };

    $types = platform_get_content_types();
    $postTypes = $types['post'] ?? [];
    $taxonomyTypes = $types['taxonomy'] ?? [];
    $itemTypes = $types['item'] ?? [];

    $cards = [];
    foreach ($postTypes as $key => $config) {
        $cards[] = [
            'title'  => $config['label'],
            'desc'   => $get('structureTypes.postsDesc', 'Manage pages, projects and workstations.'),
            'icon'   => 'web',
            'url'    => '?page=edit-posts&type=' . $key,
        ];
    }
    foreach ($taxonomyTypes as $key => $config) {
        $cards[] = [
            'title'  => $config['label'],
            'desc'   => $get('structureTypes.taxonomiesDesc', 'Manage categories and taxonomies.'),
            'icon'   => 'tag',
            'url'    => '?page=taxonomies&type=' . $key,
        ];
    }
    foreach ($itemTypes as $key => $config) {
        $cards[] = [
            'title'  => $config['label'],
            'desc'   => $get('structureTypes.itemsDesc', 'Manage data items.'),
            'icon'   => 'database',
            'url'    => '?page=items&type=' . $key,
        ];
    }

    ob_start();
    ?>
    <div class="platform-admin-page" data-structure-types-root>
        <div class="platform-page-heading">
            <h2 class="platform-title"><?php echo htmlspecialchars($get('structureTypes.label', 'Structure Types'), ENT_QUOTES, 'UTF-8'); ?></h2>
            <p class="platform-description"><?php echo htmlspecialchars($get('structureTypes.label', 'Structure Types') . ' overview', ENT_QUOTES, 'UTF-8'); ?></p>
        </div>

        <?php echo platform_render_cards($cards); ?>
    </div>
    <?php
    return ob_get_clean();
}