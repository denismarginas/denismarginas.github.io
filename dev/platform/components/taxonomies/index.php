<?php

function platform_render_taxonomies_fragment(string $tab = ''): string
{
    platform_asset('js', 'components/taxonomies/assets/js/taxonomies.js');

    $get = static function (string $path, string $default = ''): string {
        return PlatformData::getString($path, $default);
    };

    $types = platform_get_content_types();
    $taxonomyTypes = $types['taxonomy'] ?? [];

    $activeType = $tab !== '' ? $tab : (array_key_first($taxonomyTypes) ?: 'categories');
    if (!isset($taxonomyTypes[$activeType])) {
        $activeType = array_key_first($taxonomyTypes) ?: 'categories';
    }

    $config = $taxonomyTypes[$activeType] ?? [];
    $terms = platform_read_data_file($config['file'] ?? 'data_taxonomy_' . $activeType . '.json') ?? [];

    ob_start();
    ?>
    <div class="platform-admin-page" data-taxonomies-root data-active-type="<?php echo htmlspecialchars($activeType, ENT_QUOTES, 'UTF-8'); ?>">
        <div class="platform-page-heading">
            <h2 class="platform-title"><?php echo htmlspecialchars($get('contentTypes.taxonomies', 'Taxonomies'), ENT_QUOTES, 'UTF-8'); ?></h2>
            <p class="platform-description"><?php echo htmlspecialchars($get('contentTypes.taxonomiesDesc', 'Manage categories and taxonomies.'), ENT_QUOTES, 'UTF-8'); ?></p>
        </div>

        <div class="platform-tabs" data-taxonomy-tabs>
            <?php foreach ($taxonomyTypes as $key => $cfg): ?>
                <a class="platform-tab<?php echo $key === $activeType ? ' is-active' : ''; ?>" href="?page=taxonomies&type=<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>">
                    <?php echo PlatformSvg::render(['name' => $cfg['icon'] ?? 'tag', 'size' => 16]); ?>
                    <?php echo htmlspecialchars($cfg['label'], ENT_QUOTES, 'UTF-8'); ?>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="platform-cards-grid" data-taxonomy-terms>
            <?php foreach ($terms as $term): ?>
                <article class="platform-card platform-taxonomy-term">
                    <div class="platform-card-header">
                        <h3 class="platform-card-title"><?php echo htmlspecialchars($term['name'] ?? $term['_id'] ?? '', ENT_QUOTES, 'UTF-8'); ?></h3>
                        <span class="platform-card-badge"><?php echo htmlspecialchars($term['type'] ?? 'category', ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    <div class="platform-card-body">
                        <p class="platform-card-desc"><?php echo htmlspecialchars($term['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
                        <?php if (!empty($term['post_types'])): ?>
                            <div class="platform-card-meta">
                                <span class="platform-card-meta-item">
                                    <?php echo PlatformSvg::render(['name' => 'link', 'size' => 12]); ?>
                                    <?php echo htmlspecialchars(implode(', ', $term['post_types']), ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="platform-card-footer">
                        <a class="platform-button platform-button-sm platform-button-ghost" href="?page=edit-posts&type=<?php echo htmlspecialchars($activeType, ENT_QUOTES, 'UTF-8'); ?>&_id=<?php echo htmlspecialchars($term['_id'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
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