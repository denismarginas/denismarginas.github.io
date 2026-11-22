<?php

class utility_grid_column_balancing
{
    private const GROUPS = [13, 7, 6, 5, 4, 3, 2];

    public static function value(array $params = []): string
    {
        $count = (int) ($params['count'] ?? 0);
        if ($count <= 0) return '';

        return sprintf(' data-grid-listing="%d" data-grid-column-balancing="%s"', $count, self::design($count));
    }

    public static function design(int $count): string
    {
        if ($count === 1) return 'items-exactly-1';

        foreach (self::GROUPS as $group) {
            if ($count % $group === 0) return 'items-divisible-by-' . $group;
        }

        return 'items-rest-' . ($count % 4);
    }
}
