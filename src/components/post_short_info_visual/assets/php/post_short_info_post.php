<?php

class post_short_info_post
{
    public static function resolve(array $data): array
    {
        $postId = trim((string) ($data['post_id'] ?? ''));
        if ($postId !== '') {
            $found = self::find_post($postId);
            if (!empty($found)) return $found;
        }

        $post = $data['post_current_data'] ?? [];
        return is_array($post) ? $post : [];
    }

    public static function flag(array $data, string $key, bool $default): bool
    {
        if (!array_key_exists($key, $data)) return $default;
        $v = $data[$key];
        if (is_bool($v)) return $v;
        return !in_array(strtolower(trim((string) $v)), ['false', '0', 'no', 'off', ''], true);
    }

    public static function post_data(array $post): array
    {
        return is_array($post['data'] ?? null) ? $post['data'] : [];
    }

    private static function find_post(string $postId): array
    {
        $types = PlatformDataService::get_data('settings_types') ?? [];
        foreach (array_keys($types['post'] ?? []) as $type) {
            foreach ((PlatformDataService::get_all_posts_from_file($type) ?? []) as $post) {
                if (($post['_id'] ?? '') === $postId) return $post;
            }
        }
        return [];
    }
}
