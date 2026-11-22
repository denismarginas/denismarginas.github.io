<?php

class listing_toolbar
{
    public const FILE = 'listing_toolbar';
    private const GROUPS = ['search', 'filters', 'sort', 'results', 'view'];
    private const SPECIAL = [
        'filters.items' => 'filter_items',
        'filters.hide_single_option' => 'hide_single_option',
    ];

    public static function data(array $data, string $defaultId): array
    {
        $id = trim((string) ($data['toolbar'] ?? '')) ?: $defaultId;
        return array_merge(self::flatten(self::find($id)), $data);
    }

    public static function find(string $id): array
    {
        foreach (PlatformDataService::get_collection(self::FILE) ?? [] as $item) {
            if (!is_array($item) || (string) ($item['_id'] ?? '') !== $id) continue;
            if (($item['settings']['render'] ?? true) === false) return [];
            return is_array($item['data'] ?? null) ? $item['data'] : [];
        }
        return [];
    }

    public static function filters(array $data): array
    {
        return array_values(array_filter((array) ($data['filter_items'] ?? []), 'is_array'));
    }

    public static function mode(array $def, string $default = ''): string
    {
        return (string) ($def['json-search-mode'] ?? $def['json-date-search-mode'] ?? $default);
    }

    private static function flatten(array $toolbar): array
    {
        $out = [];
        foreach (self::GROUPS as $group) {
            if (!is_array($toolbar[$group] ?? null)) continue;
            foreach ($toolbar[$group] as $key => $value) {
                $out[self::SPECIAL[$group . '.' . $key] ?? $group . '_' . $key] = $value;
            }
        }
        return $out;
    }
}
