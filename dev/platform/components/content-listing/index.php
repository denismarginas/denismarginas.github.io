<?php

function platform_render_content_listing_fragment(string $tab = ''): string
{
    platform_asset('css', 'components/content-listing/assets/css/content-listing.css');
    platform_asset('js', 'components/content-listing/assets/js/content-listing.js');

    $get = static function (string $path, string $default = ''): string {
        return PlatformData::getString($path, $default);
    };
    $e = static fn (string $v): string => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');

    $types = platform_get_content_types() ?? [];
    $kinds = platform_content_kinds();
    $kind = (string) ($_GET['kind'] ?? 'post');
    if (!isset($kinds[$kind])) $kind = 'post';
    $type = (string) ($_GET['type'] ?? '');
    if (!isset($types[$kind][$type])) $type = (string) (array_key_first($types[$kind] ?? []) ?? '');

    $kindLabels = [
        'post'       => $get('contentTypes.posts', 'Posts'),
        'taxonomy'   => $get('contentTypes.taxonomies', 'Taxonomies'),
        'collection' => $get('contentTypes.collections', 'Collections'),
    ];

    if ($type === '') {
        return '<div class="platform-admin-page"><div class="platform-card"><h2 class="platform-card-title">' . $e($kindLabels[$kind]) . '</h2>'
            . '<p class="platform-empty">No ' . $e(strtolower($kindLabels[$kind])) . ' types in data_settings_types.json yet.</p></div></div>';
    }

    $config = $types[$kind][$type];
    $labels = platform_content_type_labels($kind, $type);
    $plural = $labels['plural'] !== '' ? $labels['plural'] : (string) ($config['label'] ?? $type);
    $singular = $labels['singular'] !== '' ? $labels['singular'] : $plural;
    $routable = $kind === 'collection' ? ($config['routable'] ?? false) === true : ($config['routable'] ?? true) !== false;
    $prefs = PlatformSettings::listing();
    $GLOBALS['_platform_page_title'] = $plural;

    $strings = [];
    foreach (['edit', 'view', 'hidden', 'untitled', 'loading', 'empty', 'noMatch', 'range', 'rangeAll', 'loadingMore', 'saved', 'saveFailed', 'prev', 'next'] as $key) {
        $strings[$key] = $get('contentListing.' . $key, '');
    }

    $addLabel = str_replace('{singular}', $singular, $get('contentListing.addNew', 'Add New {singular}'));
    $columns = [
        ['key' => '_id', 'label' => $get('contentListing.colId', 'ID'), 'sort' => true],
        ['key' => 'title', 'label' => $get('contentListing.colTitle', 'Title'), 'sort' => true],
        ['key' => 'type', 'label' => $get('contentListing.colType', 'Type'), 'sort' => true],
        ['key' => 'taxonomies', 'label' => $get('contentListing.colTaxonomies', 'Taxonomies'), 'sort' => false],
        ['key' => 'publish', 'label' => $get('contentListing.colPublish', 'Publish date'), 'sort' => true],
        ['key' => 'actions', 'label' => $get('contentListing.colActions', 'Actions'), 'sort' => false],
    ];
    $icons = [
        'edit' => PlatformSvg::render(['name' => 'edit', 'size' => 13]),
        'view' => PlatformSvg::render(['name' => 'view', 'size' => 13]),
    ];

    ob_start();
    ?>
    <div class="platform-admin-page platform-cl" data-cl-root
         data-kind="<?php echo $e($kind); ?>"
         data-type="<?php echo $e($type); ?>"
         data-list-api="api/content-list.php"
         data-settings-api="api/platform-settings.php"
         data-json-api="api/json-file.php"
         data-prefs="<?php echo $e((string) json_encode($prefs)); ?>"
         data-strings="<?php echo $e((string) json_encode($strings, JSON_UNESCAPED_UNICODE)); ?>"
         data-icons="<?php echo $e((string) json_encode($icons, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)); ?>">

        <div class="platform-cl-heading">
            <div class="platform-page-heading">
                <span class="platform-kicker"><?php echo $e($kindLabels[$kind]); ?></span>
                <h2 class="platform-title"><?php echo $e($plural); ?> <span class="platform-cl-count" data-cl-count></span></h2>
            </div>
            <div class="platform-cl-heading-actions">
                <label class="platform-checkbox-label" title="<?php echo $e($kind . ' / ' . $type . ' in data_settings_types.json'); ?>">
                    <input class="platform-checkbox" type="checkbox" data-cl-routable<?php echo $routable ? ' checked' : ''; ?>>
                    <span><?php echo $e($get('contentListing.routable', 'Routable (own pages)')); ?></span>
                </label>
                <a class="platform-button platform-button-sm" href="<?php echo $e(platform_content_edit_url($kind, $type)); ?>">
                    <?php echo PlatformSvg::render(['name' => 'add', 'size' => 14]); ?><span><?php echo $e($addLabel); ?></span>
                </a>
            </div>
        </div>

        <section class="platform-cl-prefs" data-cl-prefs>
            <div class="platform-cl-prefs-title">
                <?php echo PlatformSvg::render(['name' => 'settings', 'size' => 14]); ?>
                <span><?php echo $e($get('contentListing.preferences', 'Listing preferences')); ?></span>
            </div>
            <div class="platform-cl-prefs-fields">
                <label class="platform-checkbox-label">
                    <input class="platform-checkbox" type="checkbox" data-cl-pref="show_all"<?php echo $prefs['show_all'] ? ' checked' : ''; ?>>
                    <span><?php echo $e($get('contentListing.showAll', 'Show all')); ?></span>
                </label>
                <label class="platform-cl-field">
                    <span><?php echo $e($get('contentListing.limitMax', 'Items per page')); ?></span>
                    <input class="platform-input platform-cl-number" type="number" min="1" max="500" step="1" inputmode="numeric" data-cl-pref="limit_max" value="<?php echo (int) $prefs['limit_max']; ?>">
                </label>
                <div class="platform-cl-field">
                    <span><?php echo $e($get('contentListing.mode', 'Loading')); ?></span>
                    <div class="platform-cl-segment" role="radiogroup" aria-label="<?php echo $e($get('contentListing.mode', 'Loading')); ?>">
                        <label><input type="radio" name="platform-cl-mode" value="pagination" data-cl-pref="mode"<?php echo $prefs['mode'] === 'pagination' ? ' checked' : ''; ?>><span><?php echo $e($get('contentListing.modePagination', 'Pagination')); ?></span></label>
                        <label><input type="radio" name="platform-cl-mode" value="scroll" data-cl-pref="mode"<?php echo $prefs['mode'] === 'scroll' ? ' checked' : ''; ?>><span><?php echo $e($get('contentListing.modeScroll', 'Load on scroll')); ?></span></label>
                    </div>
                </div>
                <span class="platform-hint platform-cl-prefs-hint">
                    <?php echo $e($get('contentListing.preferencesHint', 'Saved for every listing in the platform.')); ?>
                    <span class="platform-cl-saved" data-cl-saved aria-live="polite"></span>
                </span>
            </div>
        </section>

        <section class="platform-card platform-cl-table-card">
            <div class="platform-cl-toolbar">
                <input class="platform-input platform-cl-search" type="search" data-cl-search placeholder="<?php echo $e($get('contentListing.search', 'Search by title, ID or taxonomy...')); ?>">
                <span class="platform-cl-summary" data-cl-summary></span>
            </div>
            <div class="platform-cl-table-wrap">
                <table class="platform-cl-table">
                    <thead>
                        <tr>
                            <?php foreach ($columns as $column): ?>
                                <th class="platform-cl-col-<?php echo $e(ltrim($column['key'], '_')); ?>" scope="col">
                                    <?php if ($column['sort']): ?>
                                        <button class="platform-cl-sort" type="button" data-cl-sort="<?php echo $e($column['key']); ?>"><?php echo $e($column['label']); ?><span class="platform-cl-sort-icon" aria-hidden="true"></span></button>
                                    <?php else: ?>
                                        <?php echo $e($column['label']); ?>
                                    <?php endif; ?>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody data-cl-body>
                        <tr><td class="platform-cl-message" colspan="<?php echo count($columns); ?>"><?php echo $e($strings['loading'] ?: 'Loading...'); ?></td></tr>
                    </tbody>
                </table>
            </div>
            <div class="platform-cl-footer">
                <span class="platform-cl-range" data-cl-range></span>
                <nav class="platform-cl-pagination" data-cl-pagination aria-label="Pagination"></nav>
            </div>
            <div class="platform-cl-sentinel" data-cl-sentinel hidden></div>
        </section>
    </div>
    <?php
    return ob_get_clean();
}
