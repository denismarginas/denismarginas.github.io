<?php

class utility_sort_text
{
    public static function compare(mixed $a, mixed $b, array $rule): int
    {
        $ka = self::key($a);
        $kb = self::key($b);

        return utility_sort_path::empty_last($ka === '', $kb === '')
            ?? utility_sort_path::direction($rule) * strnatcasecmp($ka, $kb);
    }

    private static function key(mixed $value): string
    {
        if (is_array($value)) $value = reset($value);
        return is_scalar($value) ? trim((string) $value) : '';
    }
}
