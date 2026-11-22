<?php

class cookie_notice
{
    private const DATA_FILE = 'content_cookie_notice';

    public static function render(array $data = []): string
    {
        $file = PlatformDataService::get_data(self::DATA_FILE) ?? [];
        $data = array_diff_key($data, array_flip(['children', 'post_current_data']));
        $cfg = array_replace_recursive(is_array($file) ? $file : [], $data);

        if (!filter_var($cfg['display'] ?? true, FILTER_VALIDATE_BOOLEAN)) return '';

        $description = self::str($cfg['description_text'] ?? '');
        if ($description === '') return '';

        $settings = is_array($cfg['settings'] ?? null) ? $cfg['settings'] : [];
        $settingsLabel = self::str($settings['button_label'] ?? '') ?: 'Cookie settings';

        $seo = PlatformDataService::get_data('settings_seo') ?? [];
        $hasAnalytics = self::str($seo['google_analytics']['id'] ?? '') !== '';
        $defaults = ['analytics' => $hasAnalytics && filter_var($seo['google_analytics']['default_value'] ?? false, FILTER_VALIDATE_BOOLEAN)];

        return PlatformTemplateRenderer::render([
            'text_html' => self::e($description) . self::page_sentence($cfg),
            'button_text' => self::e(self::str($cfg['button_text'] ?? '') ?: 'Accept'),
            'settings_label' => self::e($settingsLabel),
            'settings_icon' => PlatformComponentRenderer::render('svg', ['icon' => 'settings', 'class' => 'cookie-notice-icon']),
            'settings_title' => self::e(self::str($settings['title'] ?? '') ?: $settingsLabel),
            'options' => self::option('necessary', $settings['necessary'] ?? [], 'Necessary', 'Remembers your cookie choice. Always on.', true)
                . ($hasAnalytics ? self::option('analytics', $settings['analytics'] ?? [], 'Analytics', 'Google Analytics: anonymous statistics about visited pages.', false) : ''),
            'save_text' => self::e(self::str($settings['save_text'] ?? '') ?: 'Save preferences'),
            'version' => self::e(self::str($cfg['version'] ?? '1') ?: '1'),
            'days' => (string) max(1, (int) ($cfg['expires_days'] ?? 365)),
            'label' => self::e(self::str($cfg['label'] ?? '') ?: PlatformTextService::ui('cookie_notice', 'Cookie notice')),
            'close_label' => self::e(self::str($cfg['close_label'] ?? '') ?: PlatformTextService::ui('results_close', 'Close')),
            'close_icon' => PlatformComponentRenderer::render('svg', ['icon' => 'close', 'class' => 'cookie-notice-icon']),
            'defaults' => self::e((string) json_encode($defaults)),
        ]);
    }

    private static function page_sentence(array $cfg): string
    {
        $page = is_array($cfg['page'] ?? null) ? $cfg['page'] : [];
        $pageId = self::str($page['_id'] ?? '') ?: self::str(PlatformConfig::getRoutingMappings()['privacy_post_id'] ?? '');
        $pageLabel = self::str($page['button_text'] ?? '');
        if ($pageId === '' || $pageLabel === '') return '';

        $link = '<a class="cookie-notice-link" href="' . self::e(PlatformPathService::post_link($pageId)) . '">' . self::e($pageLabel) . '</a>';
        $parts = explode('%s', self::str($cfg['page_text'] ?? '') ?: '%s', 2);
        return ' ' . self::e($parts[0]) . $link . self::e($parts[1] ?? '');
    }

    private static function option(string $key, mixed $option, string $title, string $text, bool $locked): string
    {
        $option = is_array($option) ? $option : [];
        $title = self::str($option['title'] ?? '') ?: $title;
        $text = self::str($option['text'] ?? '') ?: $text;
        $id = 'cookie-notice-' . $key;

        return '<div class="cookie-notice-option' . ($locked ? ' is-locked' : '') . '">'
            . '<input class="cookie-notice-switch" type="checkbox" id="' . $id . '" '
            . ($locked ? 'checked disabled' : 'data-cookie-notice-category="' . self::e($key) . '"')
            . ' aria-describedby="' . $id . '-text">'
            . '<label class="cookie-notice-option-copy" for="' . $id . '">'
            . '<span class="cookie-notice-option-title">' . self::e($title) . '</span>'
            . '<span class="cookie-notice-option-text" id="' . $id . '-text">' . self::e($text) . '</span>'
            . '</label>'
            . '</div>';
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
