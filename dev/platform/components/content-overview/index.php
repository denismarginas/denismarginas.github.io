<?php

function platform_render_content_overview(bool $withHeading = true, bool $withSearch = false, bool $withGroups = true): string
{
    platform_asset('css', 'components/content-overview/assets/css/content-overview.css');

    $get = static function (string $path, string $default = ''): string {
        return PlatformData::getString($path, $default);
    };
    $e = static fn (string $v): string => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');

    $types = platform_get_content_types() ?? [];
    $groups = [
        'post'       => ['label' => $get('contentTypes.posts', 'Posts'), 'icon' => 'file-text', 'desc' => $get('contentTypes.postsDesc', 'Pages with their own URL.')],
        'taxonomy'   => ['label' => $get('contentTypes.taxonomies', 'Taxonomies'), 'icon' => 'tag', 'desc' => $get('contentTypes.taxonomiesDesc', 'Terms that group posts.')],
        'collection' => ['label' => $get('contentTypes.collections', 'Collections'), 'icon' => 'database', 'desc' => $get('contentTypes.collectionsDesc', 'Data used inside pages.')],
    ];
    $newLabel = $get('contentOverview.new', 'New');
    $entriesLabel = $get('contentOverview.entries', 'entries');
    $routableLabel = $get('contentOverview.routable', 'own pages');

    $html = '<section class="platform-co" aria-label="' . $e($get('contentOverview.title', 'Content')) . '">';
    if ($withHeading) {
        $html .= '<div class="platform-co-heading">'
            . '<h2 class="platform-card-title">' . $e($get('contentOverview.title', 'Content')) . '</h2>'
            . '<a class="platform-co-heading-link" href="?page=content-types">' . $e($get('contentTypes.label', 'Content Types')) . '</a>'
            . '</div>';
    }
    if ($withSearch) {
        require_once __DIR__ . '/../content-search/index.php';
        $html .= platform_render_content_search();
    }
    if (!$withGroups) {
        return $html . '</section>';
    }
    $html .= '<div class="platform-co-groups">';

    foreach ($groups as $kind => $group) {
        $rows = '';
        $total = 0;
        foreach (($types[$kind] ?? []) as $type => $config) {
            $type = (string) $type;
            $entries = platform_read_data_file((string) ($config['file'] ?? ''));
            $count = is_array($entries) ? count($entries) : 0;
            $total += $count;
            $labels = platform_content_type_labels($kind, $type);
            $label = $labels['plural'] !== '' ? $labels['plural'] : (string) ($config['label'] ?? $type);
            $routable = $kind === 'collection' ? ($config['routable'] ?? false) === true : ($config['routable'] ?? true) !== false;

            $rows .= '<li class="platform-co-type">'
                . '<a class="platform-co-type-link" href="' . $e(platform_content_list_url($kind, $type)) . '">'
                . '<span class="platform-co-type-name">' . $e($label) . '</span>'
                . '<span class="platform-co-type-meta">' . $e((string) ($config['file'] ?? '')) . ($routable ? ' · ' . $e($routableLabel) : '') . '</span>'
                . '</a>'
                . '<span class="platform-co-type-count" title="' . $count . ' ' . $e($entriesLabel) . '">' . $count . '</span>'
                . '<a class="platform-co-type-new" href="' . $e(platform_content_edit_url($kind, $type)) . '" title="' . $e($newLabel . ' ' . ($labels['singular'] ?: $label)) . '">'
                . PlatformSvg::render(['name' => 'add', 'size' => 12]) . '<span>' . $e($newLabel) . '</span></a>'
                . '</li>';
        }
        if ($rows === '') {
            $rows = '<li class="platform-co-empty">—</li>';
        }

        $html .= '<div class="platform-card platform-co-group">'
            . '<div class="platform-co-group-head">'
            . '<span class="platform-co-group-icon">' . PlatformSvg::render(['name' => $group['icon'], 'size' => 16]) . '</span>'
            . '<span class="platform-co-group-copy">'
            . '<span class="platform-co-group-title">' . $e($group['label']) . '</span>'
            . '<span class="platform-co-group-desc">' . $e($group['desc']) . '</span>'
            . '</span>'
            . '<span class="platform-co-group-total">' . $total . ' ' . $e($entriesLabel) . '</span>'
            . '</div>'
            . '<ul class="platform-co-types">' . $rows . '</ul>'
            . '</div>';
    }

    return $html . '</div></section>';
}
