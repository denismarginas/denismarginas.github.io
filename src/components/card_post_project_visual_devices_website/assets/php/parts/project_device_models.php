<?php

class project_device_models
{
    private const FORCED_MODEL = 'model-03';

    public static function resolve(array $data, array $postData): array
    {
        $raw = $data['devices_conditions_render'] ?? true;
        $enabled = is_bool($raw) ? $raw : !in_array(strtolower(trim((string) $raw)), ['false', '0', 'no', 'off', ''], true);

        $auto = $enabled
            ? project_device_render_conditions::resolve($postData, self::devices())
            : ['desktop' => self::FORCED_MODEL, 'phone' => self::FORCED_MODEL];

        return [
            'desktop' => self::override($data, 'desktop_device') ?? $auto['desktop'],
            'phone' => self::override($data, 'phone_device') ?? $auto['phone'],
        ];
    }

    public static function frames(array $models): array
    {
        $devices = self::devices();

        return [
            'desktop' => (string) ($devices['desktop'][$models['desktop']] ?? ''),
            'phone' => (string) ($devices['phone'][$models['phone']] ?? ''),
        ];
    }

    private static function devices(): array
    {
        $config = PlatformDataService::get_data('content_post_projects');
        $devices = is_array($config['img']['devices'] ?? null) ? $config['img']['devices'] : [];

        return [
            'desktop' => is_array($devices['desktop'] ?? null) ? $devices['desktop'] : [],
            'phone' => is_array($devices['phone'] ?? null) ? $devices['phone'] : [],
        ];
    }

    private static function override(array $data, string $key): ?string
    {
        return isset($data[$key]) && $data[$key] !== '' ? (string) $data[$key] : null;
    }
}
