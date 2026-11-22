<?php

class utility_filter
{
    public static function value(array $params = []): array
    {
        $items = is_array($params['items'] ?? null) ? array_values(array_filter($params['items'], 'is_array')) : [];
        $filterBy = is_array($params['filter_by'] ?? null) ? $params['filter_by'] : [];
        $excludeBy = $params['exclude_by'] ?? [];
        $includeHidden = ($params['include_hidden'] ?? false) === true;

        $items = array_values(array_filter($items, fn (array $item): bool =>
            ($includeHidden || self::value_at($item, 'settings.render') !== false)
            && self::passes_filter($item, $filterBy)
            && !self::is_excluded($item, $excludeBy)
        ));

        $sort = $params['sort'] ?? null;
        if (!empty($sort) && count($items) > 1) {
            $sorted = PlatformComponentRenderer::value('utility_sort', ['items' => $items, 'rules' => $sort]);
            if (is_array($sorted)) $items = array_values($sorted);
        }

        $max = (int) ($filterBy['max_items'] ?? $params['max_items'] ?? 0);
        return $max > 0 ? array_slice($items, 0, $max) : $items;
    }

    public static function passes_filter(array $item, array $filterBy): bool
    {
        foreach ($filterBy as $path => $expected) {
            if (!is_string($path) || $path === 'max_items') continue;
            if (!self::matches(self::value_at($item, $path), $expected)) return false;
        }
        return true;
    }

    public static function is_excluded(array $item, mixed $excludeBy): bool
    {
        $entries = is_array($excludeBy) ? $excludeBy : [$excludeBy];
        foreach ($entries as $entry) {
            $path = is_string($entry) ? $entry : (is_array($entry) ? (string) ($entry['path'] ?? $entry['ref'] ?? '') : '');
            if ($path === '') continue;

            $value = self::value_at($item, $path);
            if (is_array($entry) && array_key_exists('value', $entry)) {
                if (self::matches($value, $entry['value'])) return true;
            } elseif ($value !== null && $value !== '' && $value !== false && $value !== []) {
                return true;
            }
        }
        return false;
    }

    public static function value_at(array $item, string $path): mixed
    {
        $path = ltrim(trim($path), '@');
        if ($path === '') return null;
        if ($path === '_id' || $path === 'post_id') return $item['_id'] ?? null;

        $segments = explode('.', $path);
        if ($segments[0] === 'settings') {
            $value = self::walk($item, $segments);
            return $value ?? self::walk($item['data'] ?? [], $segments);
        }
        if ($segments[0] !== 'data') array_unshift($segments, 'data');

        return self::walk($item, $segments);
    }

    public static function matches(mixed $value, mixed $expected): bool
    {
        if (is_array($expected)) {
            foreach ($expected as $one) {
                if (self::matches($value, $one)) return true;
            }
            return false;
        }
        if (is_array($value)) {
            return in_array((string) $expected, array_map('strval', $value), true);
        }
        if (is_bool($expected)) return $value !== null && (bool) $value === $expected;
        return $value !== null && (string) $value === (string) $expected;
    }

    private static function walk(mixed $value, array $segments): mixed
    {
        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) return null;
            $value = $value[$segment];
        }
        return $value;
    }
}
