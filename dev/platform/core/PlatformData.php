<?php

class PlatformData
{
    private static ?array $strings = null;
    private static ?array $cardsVariables = null;

    public static function getStrings(): array
    {
        if (self::$strings === null) {
            $path = __DIR__ . '/../data/strings.json';
            if (file_exists($path)) {
                $decoded = json_decode(file_get_contents($path) ?: '', true);
                self::$strings = is_array($decoded) ? $decoded : [];
            } else {
                self::$strings = [];
            }
        }
        return self::$strings;
    }

    public static function getString(string $path, string $default = 'Err'): string
    {
        $data = self::getStrings();
        $value = $data;
        foreach (explode('.', $path) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }
        return is_string($value) ? $value : $default;
    }

    public static function loadCardsVariables(): array
    {
        if (self::$cardsVariables === null) {
            $path = __DIR__ . '/../data/cards.json';
            if (file_exists($path)) {
                $graph = json_decode(file_get_contents($path) ?: '', true);
                $vars = [];
                foreach ($graph['variables'] ?? [] as $v) {
                    $vars[$v['name']] = $v['value'];
                }
                self::$cardsVariables = $vars;
            } else {
                self::$cardsVariables = [];
            }
        }
        return self::$cardsVariables;
    }

    public static function resolveToken(string $value): string
    {
        $vars = self::loadCardsVariables();
        $globalPath = $vars['global_path'] ?? '';
        return str_replace('{global_path}', $globalPath, $value);
    }

    public static function resolveCardVariables(array $variables): array
    {
        return array_map(function ($var) {
            $var['value'] = self::resolveToken($var['value']);
            return $var;
        }, $variables);
    }
}
