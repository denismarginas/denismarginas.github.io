<?php

function platform_render_search_box(array $options): string
{
    platform_asset('css', 'components/content-search/assets/css/content-search.css');
    platform_asset('js', 'components/content-search/assets/js/content-search.js');

    $get = static function (string $path, string $default = ''): string {
        return PlatformData::getString($path, $default);
    };
    $e = static fn (string $v): string => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');

    $id = (string) ($options['id'] ?? 'search');
    $placeholder = (string) ($options['placeholder'] ?? '');
    $shortcut = (string) ($options['shortcut'] ?? '');
    $kinds = (array) ($options['kinds'] ?? []);

    $strings = [];
    foreach (['result', 'results', 'more', 'none', 'loading', 'view', 'hidden', 'error'] as $key) {
        $strings[$key] = $get('contentSearch.' . $key, '');
    }

    $html = '<section class="platform-card platform-cs" data-cs-root data-cs-id="' . $e($id) . '" data-api="' . $e((string) $options['api']) . '"'
        . ($shortcut !== '' ? ' data-cs-shortcut="' . $e($shortcut) . '"' : '')
        . ' data-strings="' . $e((string) json_encode($strings, JSON_UNESCAPED_UNICODE)) . '">'
        . (!empty($options['heading']) ? '<div class="platform-cs-head">'
            . '<h2 class="platform-card-title">' . $e((string) $options['heading']) . '</h2>'
            . (!empty($options['link']) ? '<a class="platform-cs-head-link" href="' . $e((string) $options['link']['url']) . '">' . $e((string) $options['link']['label']) . PlatformSvg::render(['name' => 'chevron-right', 'size' => 11]) . '</a>' : '')
            . '</div>' : '')
        . '<div class="platform-cs-bar">'
        . '<span class="platform-cs-icon" aria-hidden="true">' . PlatformSvg::render(['name' => (string) ($options['icon'] ?? 'search'), 'size' => 16]) . '</span>'
        . '<input class="platform-input platform-cs-input" type="search" autocomplete="off" spellcheck="false" data-cs-input'
        . ' placeholder="' . $e($placeholder) . '" aria-label="' . $e($placeholder) . '" role="combobox" aria-expanded="false" aria-controls="platform-cs-results-' . $e($id) . '" aria-autocomplete="list">'
        . ($shortcut !== '' ? '<kbd class="platform-cs-kbd" title="' . $e((string) ($options['shortcut_hint'] ?? '')) . '">Ctrl ' . $e(strtoupper($shortcut)) . '</kbd>' : '')
        . '</div>'
        . '<div class="platform-cs-filters" role="group" aria-label="' . $e($get('contentSearch.filter', 'Filter by kind')) . '">';
    $first = true;
    foreach (['' => $get('contentSearch.all', 'All')] + $kinds as $kind => $label) {
        $html .= '<button class="platform-cs-kind' . ($first ? ' is-active' : '') . '" type="button" data-cs-kind="' . $e((string) $kind) . '" aria-pressed="' . ($first ? 'true' : 'false') . '">'
            . '<span>' . $e((string) $label) . '</span><span class="platform-cs-kind-count" data-cs-count="' . $e((string) $kind) . '"></span></button>';
        $first = false;
    }
    $html .= '<span class="platform-cs-summary" data-cs-summary aria-live="polite"></span>'
        . '</div>'
        . '<ul class="platform-cs-results" id="platform-cs-results-' . $e($id) . '" role="listbox" data-cs-results hidden></ul>'
        . '</section>';

    return $html;
}

function platform_render_content_search(array $extra = []): string
{
    $get = static fn (string $path, string $default = ''): string => PlatformData::getString($path, $default);

    return platform_render_search_box($extra + [
        'id'            => 'search',
        'api'           => 'api/content-search.php',
        'placeholder'   => $get('contentSearch.placeholder', 'Search posts, taxonomies and collections...'),
        'shortcut'      => 'k',
        'shortcut_hint' => $get('contentSearch.shortcutHint', 'Press Ctrl+K or / to search'),
        'kinds'         => [
            'post'       => $get('contentTypes.posts', 'Posts'),
            'taxonomy'   => $get('contentTypes.taxonomies', 'Taxonomies'),
            'collection' => $get('contentTypes.collections', 'Collections'),
        ],
    ]);
}

function platform_render_settings_search(array $extra = []): string
{
    $get = static fn (string $path, string $default = ''): string => PlatformData::getString($path, $default);

    return platform_render_search_box($extra + [
        'id'            => 'settings',
        'api'           => 'api/settings-search.php',
        'icon'          => 'settings',
        'placeholder'   => $get('settingsSearch.placeholder', 'Search settings, site data and tools...'),
        'shortcut'      => 'j',
        'shortcut_hint' => $get('settingsSearch.shortcutHint', 'Press Ctrl+J to search settings'),
        'kinds'         => [
            'setting' => $get('settingsSearch.kindSettings', 'Settings'),
            'data'    => $get('settingsSearch.kindData', 'Site data'),
            'tool'    => $get('settingsSearch.kindTools', 'Tools'),
        ],
    ]);
}

function platform_render_search_panels(): string
{
    $get = static fn (string $path, string $default = ''): string => PlatformData::getString($path, $default);

    return '<section class="platform-cs-panels">'
        . platform_render_content_search([
            'heading' => $get('contentOverview.title', 'Content'),
            'link'    => ['label' => $get('contentTypes.label', 'Content Types'), 'url' => '?page=content-types'],
        ])
        . platform_render_settings_search([
            'heading' => $get('settingsSearch.title', 'Settings'),
            'link'    => ['label' => $get('settingsSearch.open', 'All settings'), 'url' => '?page=settings'],
        ])
        . '</section>';
}
