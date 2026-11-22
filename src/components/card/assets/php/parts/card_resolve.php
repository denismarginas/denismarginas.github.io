<?php

class card_resolve
{
    public static function resolve_description(array $data, array $postData): string
    {
        $raw = self::resolve_text_field($data, $postData, 'description');
        if ($raw === '') return '';

        $max = (int) ($data['excerpt_length'] ?? 0);
        if ($max <= 0) {
            return PlatformTextService::strip_html($raw);
        }

        return PlatformTextService::excerpt($raw, $max);
    }

    public static function resolve_link(array $data): string
    {
        $link = (string) ($data['link'] ?? $data['url'] ?? '');
        if ($link !== '') return $link;

        $postId = (string) ($data['post_id'] ?? '');
        if ($postId === '') return '';

        return PlatformPathService::post_link($postId);
    }

    public static function resolve_text_field(array $data, array $postData, string $key): string
    {
        $raw = $data[$key] ?? null;
        if (!is_string($raw) || $raw === '') {
            return '';
        }
        if (str_starts_with($raw, '@')) {
            return PlatformDataService::resolve_path_string($postData, substr($raw, 1)) ?? '';
        }
        return $raw;
    }
}

