<?php

class preloader
{
    private const DEFAULTS = ['enabled' => true, 'wait' => 'load', 'max_ms' => 4000];

    public static function settings(): array
    {
        $site = PlatformDataService::get_data('settings_site') ?? [];
        $raw = $site['preloader'] ?? [];
        if ($raw === false) return ['enabled' => false] + self::DEFAULTS;
        $settings = array_merge(self::DEFAULTS, is_array($raw) ? $raw : []);
        $settings['enabled'] = $settings['enabled'] === true || (string) $settings['enabled'] === 'true' || (string) $settings['enabled'] === '1';
        $settings['wait'] = $settings['wait'] === 'dom' ? 'dom' : 'load';
        $settings['max_ms'] = max(500, (int) $settings['max_ms']);
        return $settings;
    }

    public static function value(array $data = []): string
    {
        if (($data['part'] ?? '') !== 'head' || !self::settings()['enabled']) return '';
        $path = __DIR__ . '/../js/preloader_init.js';
        return is_file($path) ? '<script>' . trim((string) file_get_contents($path)) . '</script>' : '';
    }

    public static function render(array $data = []): string
    {
        $settings = self::settings();
        if (!$settings['enabled']) return '';

        return PlatformTemplateRenderer::render([
            'wait' => htmlspecialchars($settings['wait'], ENT_QUOTES, 'UTF-8'),
            'max_ms' => (string) $settings['max_ms'],
        ]);
    }
}
