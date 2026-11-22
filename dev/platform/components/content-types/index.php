<?php

require_once __DIR__ . '/../content-overview/index.php';

function platform_render_content_types_fragment(string $tab = ''): string
{
    $get = static function (string $path, string $default = ''): string {
        return PlatformData::getString($path, $default);
    };

    ob_start();
    ?>
    <div class="platform-admin-page" data-content-types-root>
        <div class="platform-page-heading">
            <h2 class="platform-title"><?php echo htmlspecialchars($get('contentTypes.label', 'Content Types'), ENT_QUOTES, 'UTF-8'); ?></h2>
            <p class="platform-description"><?php echo htmlspecialchars($get('contentTypes.description', 'Posts, taxonomies and collections of data_settings_types.json.'), ENT_QUOTES, 'UTF-8'); ?></p>
        </div>

        <?php echo platform_render_content_overview(false, true); ?>
    </div>
    <?php
    return ob_get_clean();
}
