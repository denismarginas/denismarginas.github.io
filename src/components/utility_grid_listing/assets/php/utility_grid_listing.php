<?php

class utility_grid_listing
{
    public static function value(array $params = []): string
    {
        $count = (int) ($params['count'] ?? 0);
        if ($count <= 0) return '';

        $max = (int) ($params['max'] ?? 0);
        if ($max < 1 || $max > 9) {
            return sprintf(
                ' data-grid-listing="%d" data-grid-list-design="items-divisible-by-%d"',
                $count,
                self::divisor_of($count, 9)
            );
        }

        $columns = $count <= $max ? $count : self::divisor_of($count, $max);
        if ($columns < 2 && $count > $max) {
            $columns = $max;
        }

        return sprintf(
            ' data-grid-listing="%d" data-grid-list-design="items-divisible-by-%d" data-grid-listing-max="%d"',
            $count,
            $columns,
            $max
        );
    }

    private static function divisor_of(int $count, int $limit): int
    {
        for ($d = min($limit, $count); $d >= 2; $d--) {
            if ($count % $d === 0) {
                return $d;
            }
        }
        return 1;
    }
}
