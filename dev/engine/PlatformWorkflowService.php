<?php

class PlatformWorkflowService
{
    public static function path(): string
    {
        return PlatformConfig::get('data_dir') . '/settings/data_settings_workflow.json';
    }

    public static function read(): array
    {
        $path = self::path();
        if (!file_exists($path)) return [];
        $decoded = json_decode(file_get_contents($path) ?: '', true);
        return is_array($decoded) ? $decoded : [];
    }

    public static function write(array $data): bool
    {
        $path = self::path();
        if (!is_dir(dirname($path))) mkdir(dirname($path), 0777, true);
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return $json !== false && file_put_contents($path, $json, LOCK_EX) !== false;
    }

    public static function section(string $name): array
    {
        $data = self::read();
        return is_array($data[$name] ?? null) ? $data[$name] : [];
    }

    public static function get(string $section, string $key, mixed $default = null): mixed
    {
        $data = self::section($section);
        return array_key_exists($key, $data) ? $data[$key] : $default;
    }

    public static function bool(string $section, string $key, bool $default = false): bool
    {
        $value = self::get($section, $key, $default);
        if (is_bool($value)) return $value;
        return ((string) $value) === 'true' || ((string) $value) === '1';
    }

    public static function save_section(string $section, array $vars): bool
    {
        $data = self::read();
        $data[$section] = $vars;
        return self::write($data);
    }

    public static function vars(string $section): array
    {
        $out = [];
        foreach (self::section($section) as $name => $value) {
            if (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            } elseif ($value === null) {
                $value = '';
            }
            $out[] = ['name' => (string) $name, 'value' => (string) $value];
        }
        return $out;
    }
}