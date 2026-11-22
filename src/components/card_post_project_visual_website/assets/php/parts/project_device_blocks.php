<?php

class project_device_blocks
{
    private const HTML_DIR = __DIR__ . '/../../html/parts/';

    public static function desktop(string $screen, string $frame, string $model, string $screenStyle, bool $layoutStyle): string
    {
        $image = self::image($screen, 'Website preview', 'web-desktop-image');
        if ($image === '') {
            return '';
        }

        [$screenClass, $screenAttrs] = self::screen_layout('desktop', $screen, $layoutStyle);

        return PlatformTemplateRenderer::render(self::HTML_DIR . 'desktop_block.html', [
            'device_class' => self::model_class('desktop', $model),
            'screen_class' => $screenClass,
            'screen_style' => $screenStyle,
            'screen_attrs' => $screenAttrs,
            'desktop_image' => $image,
            'laptop_frame' => self::image($frame, 'Laptop frame', 'laptop'),
        ]);
    }

    public static function phone(string $screen, string $frame, string $model, string $screenStyle, bool $layoutStyle): string
    {
        $image = self::image($screen, 'Website preview mobile', 'web-phone-image');
        if ($image === '') {
            return '';
        }

        [$screenClass, $screenAttrs] = self::screen_layout('phone', $screen, $layoutStyle);

        return PlatformTemplateRenderer::render(self::HTML_DIR . 'phone_block.html', [
            'device_class' => self::model_class('phone', $model),
            'screen_class' => $screenClass,
            'screen_style' => $screenStyle,
            'screen_attrs' => $screenAttrs,
            'phone_image' => $image,
            'phone_frame' => self::image($frame, 'Phone frame', 'phone'),
        ]);
    }

    private static function screen_layout(string $type, string $screen, bool $enabled): array
    {
        $class = 'screen';
        $attrs = '';
        if ($enabled) {
            $layout = project_desktop_layout_style::resolve($type, $screen);
            if ($layout['classes'] !== '') {
                $class .= ' ' . $layout['classes'];
            }
            if ($layout['aspect_ratio'] !== null) {
                $attrs = ' data-aspect-ratio="' . htmlspecialchars((string) $layout['aspect_ratio'], ENT_QUOTES, 'UTF-8') . '"';
            }
        }

        return [htmlspecialchars($class, ENT_QUOTES, 'UTF-8'), $attrs];
    }

    private static function image(string $src, string $alt, string $class): string
    {
        return $src !== ''
            ? PlatformComponentRenderer::render('image', ['src' => $src, 'alt' => $alt, 'class' => $class])
            : '';
    }

    private static function model_class(string $type, string $model): string
    {
        return $model !== '' ? htmlspecialchars(' ' . $type . '-' . $model, ENT_QUOTES, 'UTF-8') : '';
    }
}
