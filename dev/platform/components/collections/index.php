<?php

function platform_render_collections_fragment(string $tab = ''): string
{
        $get = static function (string $path, string $default = ''): string {
        return PlatformData::getString($path, $default);
    };

    $types = platform_get_content_types();
    $collectionTypes = $types['collection'] ?? [];

    $activeType = $tab !== '' ? $tab : (array_key_first($collectionTypes) ?: 'jobs');
    if (!isset($collectionTypes[$activeType])) {
        $activeType = array_key_first($collectionTypes) ?: 'jobs';
    }

    $config = $collectionTypes[$activeType] ?? [];
    $entries = platform_read_data_file($config['file'] ?? 'data_collection_' . $activeType . '.json') ?? [];

    ob_start();
    ?>
    <div class="platform-admin-page" data-collections-root data-active-type="<?php echo htmlspecialchars($activeType, ENT_QUOTES, 'UTF-8'); ?>">
        <div class="platform-page-heading">
            <h2 class="platform-title"><?php echo htmlspecialchars($get('contentTypes.collections', 'Collections'), ENT_QUOTES, 'UTF-8'); ?></h2>
            <p class="platform-description"><?php echo htmlspecialchars($get('contentTypes.collectionsDesc', 'Manage data collections.'), ENT_QUOTES, 'UTF-8'); ?></p>
        </div>

        <div class="platform-tabs" data-collection-tabs>
            <?php foreach ($collectionTypes as $key => $cfg): ?>
                <a class="platform-tab<?php echo $key === $activeType ? ' is-active' : ''; ?>" href="?page=collections&type=<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>">
                    <?php echo PlatformSvg::render(['name' => 'database', 'size' => 16]); ?>
                    <?php echo htmlspecialchars($cfg['label'], ENT_QUOTES, 'UTF-8'); ?>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="platform-cards-grid" data-collections-list>
            <?php foreach ($entries as $entry): ?>
                <article class="platform-card platform-collection-entry">
                    <div class="platform-card-header">
                        <h3 class="platform-card-title"><?php echo htmlspecialchars($entry['name'] ?? $entry['_id'] ?? '', ENT_QUOTES, 'UTF-8'); ?></h3>
                        <span class="platform-card-badge"><?php echo htmlspecialchars($activeType, ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    <div class="platform-card-body">
                        <?php if (!empty($entry['description'])): ?>
                            <p class="platform-card-desc"><?php echo htmlspecialchars($entry['description'], ENT_QUOTES, 'UTF-8'); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($entry['tags'])): ?>
                            <div class="platform-card-tags">
                                <?php foreach (explode(',', $entry['tags']) as $tag): ?>
                                    <span class="platform-tag"><?php echo htmlspecialchars(trim($tag), ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="platform-card-footer">
                        <a class="platform-button platform-button-sm platform-button-ghost" href="?page=edit-collections&type=<?php echo htmlspecialchars($activeType, ENT_QUOTES, 'UTF-8'); ?>&_id=<?php echo htmlspecialchars($entry['_id'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
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