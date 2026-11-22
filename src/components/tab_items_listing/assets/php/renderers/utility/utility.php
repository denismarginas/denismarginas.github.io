<?php

class tab_items_listing_utility
{
    public static function item_title(array $item): string
    {
        $title = $item['data']['seo']['title'] ?? '';
        if (is_string($title) && $title !== '') {
            return $title;
        }
        return (string) ($item['_id'] ?? $item['item_id'] ?? $item['post_id'] ?? '');
    }

    public static function as_list(mixed $value): array
    {
        if (!is_array($value)) return [];
        return array_values(array_map('strval', $value));
    }
}
