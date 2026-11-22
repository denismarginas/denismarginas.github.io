<?php

class PlatformErrorRenderer
{
    private const STYLE = 'color: #fff;border:1px solid #b53836;background:rgba(181, 56, 54, 0.3);padding:14px 8px;border-radius:4px;font-size:12px;margin:10px;text-align:center;';

    private const FALLBACKS = [
        'component_not_found' => 'Component not found: {name}',
        'no_php_files'        => 'No PHP files for component: {name}',
        'class_not_found'     => 'Component class not found: {class}',
        'class_not_loaded'    => 'Component class not loaded: {class}',
        'no_render_method'    => 'Component has no render method: {class}',
    ];

    public static function render(string $key, array $params = []): string
    {
        $message = PlatformData::getString('errors.' . $key, self::FALLBACKS[$key] ?? 'Error: ' . $key);

        foreach ($params as $name => $value) {
            $message = str_replace('{' . $name . '}', htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'), $message);
        }

        return '<div style="' . self::STYLE . '">' . $message . '</div>';
    }
}
