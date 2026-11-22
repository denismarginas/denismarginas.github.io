<?php

class cookie_settings
{
    public static function render(array $data = []): string
    {
        $notice = PlatformDataService::get_data('content_cookie_notice') ?? [];
        if (!filter_var($notice['display'] ?? true, FILTER_VALIDATE_BOOLEAN)) return '';

        $text = self::str($data['text'] ?? '');
        $label = self::str($data['label'] ?? '') ?: (self::str($notice['settings']['button_label'] ?? '') ?: PlatformTextService::ui('cookie_settings', 'Cookie settings'));
        $class = self::str($data['class'] ?? '') ?: 'cookie-settings-button';
        $icon = array_key_exists('svg', $data) ? self::str($data['svg']) : 'settings';

        $svg = $icon !== '' ? PlatformComponentRenderer::render('svg', ['icon' => $icon, 'class' => 'cookie-settings-icon']) : '';
        $textHtml = $text !== '' ? '<span class="btn-text">' . self::e($text) . '</span>' : '';
        $a11y = $text === '' ? ' aria-label="' . self::e($label) . '" title="' . self::e($label) . '"' : '';

        $status = '';
        if (filter_var($data['status'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            $status = '<span class="cookie-settings-status" data-cookie-status aria-live="polite"'
                . ' data-on="' . self::e(self::str($data['status_on'] ?? '') ?: PlatformTextService::ui('cookie_analytics_on', 'Analytics cookies: allowed')) . '"'
                . ' data-off="' . self::e(self::str($data['status_off'] ?? '') ?: PlatformTextService::ui('cookie_analytics_off', 'Analytics cookies: off')) . '"></span>';
        }

        return PlatformTemplateRenderer::render([
            'class' => self::e($class),
            'a11y' => $a11y,
            'content' => $svg . $textHtml,
            'status' => $status,
            'wrap_class' => $status !== '' ? ' cookie-settings-with-status' : '',
        ]);
    }

    private static function str(mixed $value): string
    {
        return is_scalar($value) ? trim((string) $value) : '';
    }

    private static function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
