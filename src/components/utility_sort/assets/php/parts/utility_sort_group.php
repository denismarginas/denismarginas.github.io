<?php

class utility_sort_group
{
    public static function compare(mixed $a, mixed $b, array $rule): int
    {
        $expected = is_array($rule['value']) ? $rule['value'] : [$rule['value']];
        $ma = self::matches($a, $expected);
        $mb = self::matches($b, $expected);
        if ($ma === $mb) return 0;

        $first = strtolower((string) ($rule['position'] ?? 'last')) === 'first';
        return ($ma === $first) ? -1 : 1;
    }

    private static function matches(mixed $value, array $expected): bool
    {
        $values = is_array($value) ? $value : [$value];
        foreach ($values as $item) {
            if (!is_scalar($item) && $item !== null) continue;
            foreach ($expected as $want) {
                if (is_bool($want) ? (bool) $item === $want : (string) $item === (string) $want) return true;
            }
        }
        return false;
    }
}
