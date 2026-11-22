<?php

class tab_items_listing_education
{
    public static function render_education_logo(array $logo, string $alt): string
    {
        $src = (string) ($logo['img'] ?? '');
        if ($src === '') return '';
        $image = PlatformComponentRenderer::render('image', ['src' => $src, 'alt' => $alt, 'class' => 'tab-items-listing-logo-img']);
        if ($image === '') return '';
        $layout = (string) ($logo['bg'] ?? 'light');
        return tab_items_listing_loader::load_education_partial('logo.html', ['layout' => htmlspecialchars($layout, ENT_QUOTES, 'UTF-8'), 'logo_img' => $image]);
    }

    public static function render_education_links(array $links, array $data = []): string
    {
        if (empty($links)) return '';
        $items = '';
        foreach ($links as $link) {
            $url = self::resolve_education_link_url($link);
            if ($url === '') continue;
            $svg = (string) ($link['svg'] ?? $link['icon'] ?? '');
            $showText = tab_items_listing_text_visibility::should_render_text($svg, $data);
            $text = $showText ? self::synthesize_url_text($url) : '';
            if ($text === '' && $svg === '') continue;
            $items .= self::render_education_button($link, $url, true, $text);
        }
        if ($items === '') return '';
        return tab_items_listing_loader::load_education_partial('links.html', ['items' => $items]);
    }

    public static function render_education_projects(array $projects, array $data = []): string
    {
        if (empty($projects)) return '';
        $items = '';
        foreach ($projects as $project) {
            $text = (string) ($project['text'] ?? '');
            $svg = (string) ($project['svg'] ?? $project['icon'] ?? '');
            if (!tab_items_listing_text_visibility::should_render_text($svg, $data)) $text = '';
            if ($text === '' && $svg === '') continue;
            $url = self::resolve_education_link_url($project);
            if ($url !== '') {
                $items .= self::render_education_button($project, $url, false, $text);
            } else {
                $items .= tab_items_listing_loader::load_education_partial('project_plain.html', ['text' => htmlspecialchars($text, ENT_QUOTES, 'UTF-8')]);
            }
        }
        if ($items === '') return '';
        return tab_items_listing_loader::load_education_partial('projects.html', ['items' => $items]);
    }

    public static function render_education_button(array $item, string $url, bool $external, string $text = ''): string
    {
        if ($text === '') $text = (string) ($item['text'] ?? '');
        $svg = (string) ($item['svg'] ?? $item['icon'] ?? '');
        if ($text === '' && $svg === '') return '';
        return (string) PlatformComponentRenderer::render('button', ['class' => 'btn btn-primary-small', 'text' => $text, 'link' => $url, 'svg' => $svg, 'target' => $external ? '_blank' : '', 'rel' => $external ? 'noopener noreferrer' : '']);
    }

    public static function synthesize_url_text(string $url): string
    {
        return (string) PlatformComponentRenderer::value('utility_string_urlto', ['input' => $url, 'action' => 'synthesize']);
    }

    public static function resolve_education_link_url(array $item): string
    {
        $postId = (string) ($item['post_id'] ?? $item['_id'] ?? '');
        if ($postId !== '') return PlatformPathService::post_link($postId);
        return (string) ($item['link'] ?? '');
    }
}