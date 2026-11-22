<?php

require_once __DIR__ . '/parts/utility_sort_path.php';
require_once __DIR__ . '/parts/utility_sort_date.php';
require_once __DIR__ . '/parts/utility_sort_group.php';
require_once __DIR__ . '/parts/utility_sort_text.php';

class utility_sort
{
    public static function value(array $params = []): array
    {
        $items = is_array($params['items'] ?? null) ? array_values($params['items']) : [];
        $rules = self::rules($params['rules'] ?? $params['sort'] ?? null);
        if (count($items) < 2 || empty($rules)) {
            return $items;
        }

        usort($items, function (array $a, array $b) use ($rules): int {
            foreach ($rules as $rule) {
                $cmp = self::compare($a, $b, $rule);
                if ($cmp !== 0) return $cmp;
            }
            return 0;
        });

        return $items;
    }

    private static function compare(array $a, array $b, array $rule): int
    {
        $va = utility_sort_path::get($a, $rule['by']);
        $vb = utility_sort_path::get($b, $rule['by']);

        return match (self::type($rule)) {
            'list' => self::compare_list($va, $vb, $rule),
            'group' => utility_sort_group::compare($va, $vb, $rule),
            'date' => utility_sort_date::compare($va, $vb, $rule),
            default => utility_sort_text::compare($va, $vb, $rule),
        };
    }

    private static function compare_list(mixed $a, mixed $b, array $rule): int
    {
        $order = array_flip(array_map('strval', array_values($rule['values'])));
        $last = count($order);
        $ia = is_scalar($a) ? ($order[(string) $a] ?? $last) : $last;
        $ib = is_scalar($b) ? ($order[(string) $b] ?? $last) : $last;
        return $ia <=> $ib;
    }

    private static function type(array $rule): string
    {
        if (is_array($rule['values'] ?? null)) return 'list';
        if (array_key_exists('value', $rule)) return 'group';
        $type = strtolower((string) ($rule['type'] ?? ''));
        if ($type !== '') return $type;
        return str_starts_with(ltrim($rule['by'], '@'), 'date.') || str_contains($rule['by'], '.date.') ? 'date' : 'text';
    }

    private static function rules(mixed $rules): array
    {
        if (!is_array($rules)) return [];
        $list = array_is_list($rules) ? $rules : [$rules];

        return array_values(array_filter($list, fn ($rule) =>
            is_array($rule) && is_string($rule['by'] ?? null) && $rule['by'] !== ''
        ));
    }
}
