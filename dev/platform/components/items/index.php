<?php

function platform_render_items_fragment(string $tab = ''): string
{
    platform_asset('js', 'components/items/assets/js/items.js');

    $get = static function (string $path, string $default = ''): string {
        return PlatformData::getString($path, $default);
    };

    $types = platform_get_content_types();
    $itemTypes = $types['item'] ?? [];

    $activeType = $tab !== '' ? $tab : (array_key_first($itemTypes) ?: 'jobs');
    if (!isset($itemTypes[$activeType])) {
        $activeType = array_key_first($itemTypes) ?: 'jobs';
    }

    $config = $itemTypes[$activeType] ?? [];
    $items = platform_read_data_file($config['file'] ?? 'data_items_' . $activeType . '.json') ?? [];

    ob_start();
    ?>
    <div class="platform-admin-page" data-items-root data-active-type="<?php echo htmlspecialchars($activeType, ENT_QUOTES, 'UTF-8'); ?>">
        <div class="platform-page-heading">
            <h2 class="platform-title"><?php echo htmlspecialchars($get('structureTypes.items', 'Items'), ENT_QUOTES, 'UTF-8'); ?></h2>
            <p class="platform-description"><?php echo htmlspecialchars($get('structureTypes.itemsDesc', 'Manage data items.'), ENT_QUOTES, 'UTF-8'); ?></p>
        </div>

        <div class="platform-tabs" data-item-tabs>
            <?php foreach ($itemTypes as $key => $cfg): ?>
                <a class="platform-tab<?php echo $key === $activeType ? ' is-active' : ''; ?>" href="?page=items&type=<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>">
                    <?php echo PlatformSvg::render(['name' => 'database', 'size' => 16]); ?>
                    <?php echo htmlspecialchars($cfg['label'], ENT_QUOTES, 'UTF-8'); ?>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="platform-cards-grid" data-items-list>
            <?php foreach ($items as $item): ?>
                <article class="platform-card platform-item-entry">
                    <div class="platform-card-header">
                        <h3 class="platform-card-title"><?php echo htmlspecialchars($item['name'] ?? $item['_id'] ?? '', ENT_QUOTES, 'UTF-8'); ?></h3>
                        <span class="platform-card-badge"><?php echo htmlspecialchars($activeType, ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    <div class="platform-card-body">
                        <?php if (!empty($item['description'])): ?>
                            <p class="platform-card-desc"><?php echo htmlspecialchars($item['description'], ENT_QUOTES, 'UTF-8'); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($item['tags'])): ?>
                            <div class="platform-card-tags">
                                <?php foreach (explode(',', $item['tags']) as $tag): ?>
                                    <span class="platform-tag"><?php echo htmlspecialchars(trim($tag), ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="platform-card-footer">
                        <a class="platform-button platform-button-sm platform-button-ghost" href="?page=edit-items&type=<?php echo htmlspecialchars($activeType, ENT_QUOTES, 'UTF-8'); ?>&_id=<?php echo htmlspecialchars($item['_id'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo PlatformSvg::render(['name' => 'edit', 'size' => 12]); ?>
                            Edit
                        </a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}