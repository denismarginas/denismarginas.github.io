<?php

if (!isset($GLOBALS['_platform_assets'])) {
    $GLOBALS['_platform_assets'] = ['css' => [], 'js' => []];
}

function platform_asset(string $type, string ...$paths): void
{
    foreach ($paths as $path) {
        if (!in_array($path, $GLOBALS['_platform_assets'][$type], true)) {
            $GLOBALS['_platform_assets'][$type][] = $path;
        }
    }
}

function platform_menu(): array
{
    $get = static function (string $path, string $default = ''): string {
        return PlatformData::getString($path, $default);
    };

    return [
        'home'            => ['label' => $get('home.title', 'Dashboard'), 'icon' => 'home', 'url' => '?page=home', 'desc' => $get('home.description', 'Build, render and publish the portfolio.')],
        'content-types' => [
            'label'    => $get('contentTypes.label', 'Content Types'),
            'icon'     => 'layers',
            'url'      => '?page=content-types',
            'desc'     => $get('contentTypes.label', 'Content Types'),
            'children' => platform_content_menu_children($get),
        ],
        'render'          => [
            'label'    => $get('menu.render', 'Render'),
            'icon'     => 'render',
            'url'      => '?page=render&tab=compile',
            'desc'     => 'Compile stylesheets and preview rendered posts.',
            'children' => [
                'compile'      => ['label' => $get('menu.compileScss', 'Compile SCSS'), 'icon' => 'code', 'url' => '?page=render&tab=compile', 'desc' => 'Build CSS from component stylesheets.'],
                'preview'      => ['label' => $get('menu.livePreview', 'Live Preview'), 'icon' => 'preview', 'url' => '?page=render&tab=preview', 'desc' => 'Preview any post rendered by the page constructor.'],
                'render-html'  => ['label' => $get('actions.renderHtml', 'HTML Bulk Render'), 'icon' => 'html', 'url' => '?page=render&tab=render-html', 'desc' => 'Render every post to static HTML files.'],
                'seo'          => ['label' => $get('menu.seoFiles', 'SEO Files'), 'icon' => 'seo', 'url' => '?page=render&tab=seo', 'desc' => 'Build sitemap.xml and robots.txt in dist/.'],
            ],
        ],
        'generate'        => [
            'label'    => $get('actions.generate', 'Generate'),
            'icon'     => 'generate',
            'url'      => '?page=generate&tab=translate',
            'desc'     => 'Generate site artifacts from content data.',
            'children' => [
                'translate'  => ['label' => $get('menu.translate', 'Generate Translations'), 'icon' => 'translate', 'url' => '?page=generate&tab=translate', 'desc' => 'Synchronise content data across languages.'],
                'thumbnails' => ['label' => $get('menu.thumbnails', 'Generate Thumbnails'), 'icon' => 'image', 'url' => '?page=generate&tab=thumbnails', 'desc' => 'Build project thumbnails from the screen template.'],
                'resumes'    => ['label' => $get('menu.resumes', 'Generate Resume Images'), 'icon' => 'resume', 'url' => '?page=generate&tab=resumes', 'desc' => 'Render the resume PDFs to WebP images and thumbnails.'],
            ],
        ],
        'settings'        => ['label' => $get('home.settings', 'Settings'), 'icon' => 'settings', 'url' => '?page=settings', 'desc' => $get('home.settingsDesc', 'Site, routing, languages and SEO configuration.')],
    ];
}

function platform_content_menu_children(callable $get): array
{
    $types = platform_get_content_types() ?? [];
    $groups = [
        'post'       => ['key' => 'posts',       'label' => $get('contentTypes.posts', 'Posts'),             'icon' => 'file-text'],
        'taxonomy'   => ['key' => 'taxonomies',  'label' => $get('contentTypes.taxonomies', 'Taxonomies'),   'icon' => 'tag'],
        'collection' => ['key' => 'collections', 'label' => $get('contentTypes.collections', 'Collections'), 'icon' => 'database'],
    ];

    $children = [];
    foreach ($groups as $kind => $group) {
        $items = [];
        foreach (($types[$kind] ?? []) as $type => $config) {
            $labels = platform_content_type_labels($kind, (string) $type);
            $items[$kind . ':' . $type] = [
                'label' => $labels['plural'] !== '' ? $labels['plural'] : (string) ($config['label'] ?? $type),
                'url'   => platform_content_list_url($kind, (string) $type),
            ];
        }
        $children[$group['key']] = [
            'label'    => $group['label'],
            'icon'     => $group['icon'],
            'url'      => platform_content_list_url($kind),
            'children' => $items,
        ];
    }
    return $children;
}

function platform_active_content_key(string $page): string
{
    $kind = '';
    if ($page === 'content') {
        $kind = (string) ($_GET['kind'] ?? 'post');
    } else {
        $kind = (string) (array_search($page, platform_content_kinds(), true) ?: '');
    }
    if ($kind === '') return '';

    $type = (string) ($_GET['type'] ?? '');
    if ($type === '') {
        $types = platform_get_content_types() ?? [];
        $type = (string) (array_key_first($types[$kind] ?? []) ?? '');
    }
    return $kind . ':' . $type;
}

function platform_pages(): array
{
    $get = static function (string $path, string $default = ''): string {
        return PlatformData::getString($path, $default);
    };

    return [
        'home'           => ['title' => $get('home.title', 'Dashboard'), 'render' => 'platform_render_home_fragment', 'file' => 'components/home/index.php'],
        'content-types'  => ['title' => $get('contentTypes.label', 'Content Types'), 'render' => 'platform_render_content_types_fragment', 'file' => 'components/content-types/index.php'],
        'edit-posts'     => ['title' => $get('editPosts.pageTitle', 'Post Editor'), 'render' => 'platform_render_edit_posts_fragment', 'file' => 'components/edit-posts/index.php'],
        'content'        => ['title' => $get('contentListing.pageTitle', 'Content'), 'render' => 'platform_render_content_listing_fragment', 'file' => 'components/content-listing/index.php'],
        'edit-taxonomies' => ['title' => $get('editTaxonomies.pageTitle', 'Taxonomy Editor'), 'render' => 'platform_render_edit_taxonomies_fragment', 'file' => 'components/edit-taxonomies/index.php'],
        'edit-collections' => ['title' => $get('editCollections.pageTitle', 'Collection Editor'), 'render' => 'platform_render_edit_collections_fragment', 'file' => 'components/edit-collections/index.php'],
        'settings'       => ['title' => $get('home.settings', 'Settings'), 'render' => 'platform_render_settings_fragment', 'file' => 'components/settings/index.php'],
        'render'         => ['title' => $get('actions.compileScss', 'Render'), 'render' => 'platform_render_render_fragment', 'file' => 'components/render/index.php'],
        'generate'       => ['title' => $get('actions.generate', 'Generate'), 'render' => 'platform_render_generate_fragment', 'file' => 'components/generate/index.php'],
    ];
}

function platform_render_menu(array $menu, string $page, string $tab, string $activeContent = ''): string
{
    $e = static fn (string $v): string => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
    $icon = static fn (string $name, int $size): string => PlatformSvg::render(['name' => $name, 'size' => $size, 'class' => 'platform-menu-icon']);

    $html = '<nav class="platform-menu" aria-label="Main menu" data-platform-menu><ul class="platform-menu-list">';

    foreach ($menu as $key => $item) {
        $hasChildren = !empty($item['children']);

        if ($hasChildren) {
            $html .= '<li class="platform-menu-group">';
            $html .= '<a class="platform-menu-link platform-menu-group-link' . ($page === $key ? ' is-active' : '') . '" href="' . $e($item['url']) . '">'
                . $icon($item['icon'], 16)
                . '<span class="platform-menu-label">' . $e($item['label']) . '</span>'
                . '</a>';
            $html .= '<ul class="platform-menu-children">';
            foreach ($item['children'] as $childKey => $child) {
                if (!empty($child['children'])) {
                    $html .= platform_render_menu_dropdown((string) $childKey, $child, $activeContent);
                    continue;
                }
                $isActive = $page === $key && $tab === $childKey;
                $html .= '<li><a class="platform-menu-link platform-menu-child-link' . ($isActive ? ' is-active' : '') . '" href="' . $e($child['url']) . '">'
                    . $icon($child['icon'], 14)
                    . '<span class="platform-menu-label">' . $e($child['label']) . '</span>'
                    . '</a></li>';
            }
            $html .= '</ul>';
            $html .= '</li>';
            continue;
        }

        $isActive = $page === $key;
        $html .= '<li><a class="platform-menu-link' . ($isActive ? ' is-active' : '') . '" href="' . $e($item['url']) . '">'
            . $icon($item['icon'], 16)
            . '<span class="platform-menu-label">' . $e($item['label']) . '</span>'
            . '</a></li>';
    }

    $html .= '</ul></nav>';
    return $html;
}

function platform_render_menu_dropdown(string $key, array $item, string $activeContent): string
{
    $e = static fn (string $v): string => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
    $isOpen = $activeContent !== '' && isset($item['children'][$activeContent]);
    $listId = 'platform-menu-sub-' . preg_replace('/[^a-z0-9_-]/i', '-', $key);

    $links = '';
    foreach ($item['children'] as $childKey => $child) {
        $isActive = $childKey === $activeContent;
        $links .= '<li><a class="platform-menu-link platform-menu-child-link platform-menu-sub-link' . ($isActive ? ' is-active' : '') . '" href="' . $e($child['url']) . '"' . ($isActive ? ' aria-current="page"' : '') . '>'
            . '<span class="platform-menu-label">' . $e($child['label']) . '</span>'
            . '</a></li>';
    }
    if ($links === '') {
        $links = '<li><span class="platform-menu-link platform-menu-child-link platform-menu-sub-empty">—</span></li>';
    }

    return '<li class="platform-menu-sub' . ($isOpen ? ' is-open' : '') . '" data-platform-menu-sub="' . $e($key) . '"' . ($isOpen ? ' data-platform-menu-active' : '') . '>'
        . '<button class="platform-menu-link platform-menu-child-link platform-menu-toggle' . ($isOpen ? ' is-current' : '') . '" type="button" aria-expanded="' . ($isOpen ? 'true' : 'false') . '" aria-controls="' . $e($listId) . '" data-platform-menu-toggle>'
        . PlatformSvg::render(['name' => $item['icon'], 'size' => 14, 'class' => 'platform-menu-icon'])
        . '<span class="platform-menu-label">' . $e($item['label']) . '</span>'
        . PlatformSvg::render(['name' => 'chevron-bottom', 'size' => 12, 'class' => 'platform-menu-caret'])
        . '</button>'
        . '<ul class="platform-menu-children platform-menu-subchildren" id="' . $e($listId) . '">' . $links . '</ul>'
        . '</li>';
}
