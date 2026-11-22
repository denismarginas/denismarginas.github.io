<?php

class header_logo extends header_menu
{
    public static function render_logo(array $settings = []): string
    {
        return PlatformComponentRenderer::render('logo', [
            'variant' => 'header',
            'shape' => 'circle',
        ]);
    }

    public static function resolve_logo_img(string $logoImg): string
    {
        PlatformComponentRenderer::load_component_class('logo');
        return class_exists('logo', false) ? logo::resolve_logo_img($logoImg) : '';
    }
}
