<?php

class PlatformSettings
{
    private const MODES = ['pagination', 'scroll'];

    private static ?array $settings = null;

    public static function file(): string
    {
        return __DIR__ . '/../data/settings.json';
    }

    public static function defaults(): array
    {
        return [
            'listing' => [
                'show_all'  => false,
                'limit_max' => 20,
                'mode'      => 'pagination',
            ],
            'tools' => [
                'performance_monitor' => false,
            ],
        ];
    }

    public static function all(): array
    {
        if (self::$settings !== null) return self::$settings;

        $stored = [];
        if (file_exists(self::file())) {
            $decoded = json_decode((string) file_get_contents(self::file()), true);
            if (is_array($decoded)) $stored = $decoded;
        }

        $settings = self::defaults();
        foreach ($stored as $section => $values) {
            $settings[$section] = is_array($values) && is_array($settings[$section] ?? null)
                ? array_merge($settings[$section], $values)
                : $values;
        }
        $settings['listing'] = self::clean_listing($settings['listing']);
        $settings['tools'] = self::clean_tools(is_array($settings['tools'] ?? null) ? $settings['tools'] : []);

        return self::$settings = $settings;
    }

    public static function listing(): array
    {
        return self::all()['listing'];
    }

    public static function tools(): array
    {
        return self::all()['tools'];
    }

    public static function save_listing(array $values): ?array
    {
        $settings = self::all();
        $settings['listing'] = self::clean_listing(array_merge($settings['listing'], $values));
        return self::write($settings) ? $settings['listing'] : null;
    }

    public static function save_tools(array $values): ?array
    {
        $settings = self::all();
        $settings['tools'] = self::clean_tools(array_merge($settings['tools'], $values));
        return self::write($settings) ? $settings['tools'] : null;
    }

    private static function write(array $settings): bool
    {
        $json = json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($json === false || file_put_contents(self::file(), $json . "\n", LOCK_EX) === false) return false;

        self::$settings = $settings;
        return true;
    }

    private static function clean_tools(array $tools): array
    {
        return [
            'performance_monitor' => filter_var($tools['performance_monitor'] ?? false, FILTER_VALIDATE_BOOLEAN),
        ];
    }

    private static function clean_listing(array $listing): array
    {
        $limit = (int) ($listing['limit_max'] ?? 20);
        $mode = (string) ($listing['mode'] ?? 'pagination');

        return [
            'show_all'  => filter_var($listing['show_all'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'limit_max' => max(1, min(500, $limit > 0 ? $limit : 20)),
            'mode'      => in_array($mode, self::MODES, true) ? $mode : 'pagination',
        ];
    }
}
