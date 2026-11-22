<?php

class header_theme_toggle extends header_heading
{
    public static function render_theme_toggle(): string
    {
        $toggleMode = PlatformConfig::get('theme_default_mode', 'light');

        return PlatformTemplateRenderer::render(__DIR__ . '/../../html/template_theme_toggle.html', [
            'label_theme' => htmlspecialchars(PlatformTextService::ui('header_theme_toggle', 'Toggle color theme'), ENT_QUOTES, 'UTF-8'),
            'toggle_mode' => $toggleMode,
            'sun_active_attr' => $toggleMode === 'light' ? 'active="true"' : 'active="false"',
            'moon_active_attr' => $toggleMode === 'dark' ? 'active="true"' : 'active="false"',
            'sun_icon_html' => PlatformComponentRenderer::render('svg', ['icon' => 'sun']),
            'moon_icon_html' => PlatformComponentRenderer::render('svg', ['icon' => 'moon']),
        ]);
    }
}


