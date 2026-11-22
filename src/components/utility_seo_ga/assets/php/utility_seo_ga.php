<?php

class utility_seo_ga
{
    public static function settings(): ?array
    {
        $seo = PlatformDataService::get_data('settings_seo') ?? [];
        $ga = is_array($seo['google_analytics'] ?? null) ? $seo['google_analytics'] : [];
        $id = trim((string) ($ga['id'] ?? ''));
        if ($id === '' || !preg_match('/^[A-Z]{1,3}-[A-Z0-9-]+$/i', $id)) return null;

        return [
            'id' => $id,
            'require_consent' => filter_var($ga['require_consent'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'default_value' => filter_var($ga['default_value'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'track_localhost' => filter_var($ga['track_localhost'] ?? false, FILTER_VALIDATE_BOOLEAN),
        ];
    }

    public static function render(array $data = []): string
    {
        $settings = self::settings();
        if ($settings === null) return '';

        $json = json_encode($settings, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP);
        return '<script type="application/json" data-seo-ga>' . $json . '</script>';
    }
}
