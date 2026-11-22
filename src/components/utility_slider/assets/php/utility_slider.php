<?php

class utility_slider
{
    private const OPTIONS = ['arrows' => true, 'counter' => true, 'dots' => false];

    public static function value(array $params = []): string
    {
        $attrs = ' data-utility-slider';
        foreach (self::OPTIONS as $key => $default) {
            $attrs .= ' data-slider-' . $key . '="' . (self::flag($params, $key, $default) ? 'true' : 'false') . '"';
        }
        return $attrs;
    }

    private static function flag(array $data, string $key, bool $default): bool
    {
        if (!array_key_exists($key, $data)) return $default;
        $v = $data[$key];
        if (is_bool($v)) return $v;
        return !in_array(strtolower(trim((string) $v)), ['false', '0', 'no', 'off', ''], true);
    }
}
