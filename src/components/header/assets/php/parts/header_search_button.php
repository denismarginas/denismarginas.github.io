<?php

class header_search_button extends header_theme_toggle
{
    public static function render_search_button(array $data = []): string
    {
        $mappings = PlatformConfig::getRoutingMappings();
        $searchId = $mappings['search_post_id'] ?? 'search';
        $searchUrl = PlatformPathService::post_link($searchId);

        $currentPostId = (string) ($data['post_current_data']['_id'] ?? $data['post_current_data']['post_id'] ?? '');
        $isActive = $currentPostId !== '' && $searchId === $currentPostId;

        return PlatformTemplateRenderer::render(__DIR__ . '/../../html/template_search_button.html', [
            'label_search' => htmlspecialchars(PlatformTextService::ui('search_label', 'Search'), ENT_QUOTES, 'UTF-8'),
            'active_class' => $isActive ? 'active' : '',
            'aria_current' => $isActive ? 'aria-current="page"' : '',
            'search_url' => htmlspecialchars($searchUrl, ENT_QUOTES, 'UTF-8'),
            'search_icon_html' => PlatformComponentRenderer::render('svg', [
                'icon' => 'search',
                'class' => 'header-search-button-icon',
            ]),
        ]);
    }
}


