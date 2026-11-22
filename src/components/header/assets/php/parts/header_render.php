<?php

class header_render extends header_logo
{
    public static function render(array $data = []): string
    {
        $settings = PlatformDataService::get_data('settings_site') ?? [];

        return PlatformTemplateRenderer::render([
            'logo_html' => self::render_logo($settings),
            'menu_html' => self::render_menu($data),
            'page_heading' => self::render_page_heading($data),
        ]);
    }
}




