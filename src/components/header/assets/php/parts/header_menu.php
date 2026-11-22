<?php

class header_menu extends header_search_button
{
    public static function render_menu(array $data = []): string
    {
        $jsonContent = PlatformDataService::get_data('content_header_menu');
        $menuList = $jsonContent['menu_list'] ?? [];

        $currentPostId = $data['post_current_data']['_id'] ?? '';

        $items = '';

        foreach ($menuList as $item) {
            $postId = $item['_id'] ?? '';
            $text = $item['text'] ?? '';
            if ($postId === '' || $text === '') continue;

            $submenuHtml = '';
            $childActive = false;
            foreach (($item['submenu'] ?? []) as $subItem) {
                $subId = $subItem['_id'] ?? '';
                $subText = $subItem['text'] ?? '';
                if ($subId === '' || $subText === '') continue;
                if ($subId === $currentPostId) $childActive = true;
                $submenuHtml .= PlatformTemplateRenderer::render(__DIR__ . '/../../html/template_menu_item.html', [
                    'active' => self::menu_active_attr($subId, $currentPostId),
                    'url' => PlatformPathService::post_link($subId),
                    'name' => htmlspecialchars($subText),
                    'submenu' => '',
                ]);
            }

            if ($submenuHtml !== '') {
                $items .= self::render_parent_item($item, $postId, $currentPostId, $submenuHtml, $childActive);
                continue;
            }

            $items .= PlatformTemplateRenderer::render(__DIR__ . '/../../html/template_menu_item.html', [
                'active' => self::menu_active_attr($postId, $currentPostId),
                'url' => PlatformPathService::post_link($postId),
                'name' => htmlspecialchars($text),
                'submenu' => $submenuHtml,
            ]);
        }

        if ($items === '') {
            return '';
        }

        return PlatformTemplateRenderer::render(__DIR__ . '/../../html/template_menu.html', [
            'items' => $items,
            'search_button_html' => self::render_search_button($data),
            'language_switcher_html' => header_language_switcher::render_language_switcher($data),
            'theme_toggle_html' => self::render_theme_toggle(),
            'menu_toggle_html' => self::render_menu_toggle(),
        ]);
    }

    private static function render_parent_item(array $item, string $postId, string $currentPostId, string $submenuItems, bool $childActive): string
    {
        static $count = 0;
        $count++;
        $text = htmlspecialchars((string) $item['text']);
        $submenuId = 'header-submenu-' . $count;
        $chevron = PlatformComponentRenderer::render('svg', ['icon' => 'chevron-down', 'class' => 'header-submenu-toggle-icon']);
        $active = self::menu_active_attr($postId, $currentPostId);

        return '<li class="has-submenu' . ($childActive ? ' is-current-parent' : '') . '">'
            . '<span class="header-menu-row">'
            . '<a ' . $active . ' href="' . PlatformPathService::post_link($postId) . '">' . $text . '</a>'
            . '<button type="button" class="header-submenu-toggle" aria-expanded="false" aria-controls="' . $submenuId . '" aria-label="' . $text . ' submenu">' . $chevron . '</button>'
            . '</span>'
            . '<ul class="header-submenu" id="' . $submenuId . '">' . $submenuItems . '</ul>'
            . '</li>';
    }

    public static function menu_active_attr(string $postId, string $currentPostId): string
    {
        if ($currentPostId !== '' && $postId === $currentPostId) {
            return 'class="active" aria-current="page"';
        }
        return '';
    }

    public static function render_menu_toggle(): string
    {
        return PlatformTemplateRenderer::render(__DIR__ . '/../../html/template_menu_navbar_toggle.html', [
            'label_menu' => htmlspecialchars(PlatformTextService::ui('header_menu_toggle', 'Toggle menu'), ENT_QUOTES, 'UTF-8'),
            'menu_icon_html' => PlatformComponentRenderer::render('svg', [
                'icon' => 'menu',
                'class' => 'header-menu-navbar-toggle-icon',
            ]),
        ]);
    }
}


