<?php

class filters_post_projects_posts
{
    public static function find(array $data): array
    {
        $posts = [];
        foreach (post_items_render::resolve_post_types($data['post_type'] ?? null) as $postType) {
            foreach (PlatformDataService::get_all_posts_from_file($postType) ?? [] as $post) {
                $post['_post_type'] = $postType;
                $posts[] = $post;
            }
        }

        $excludeBy = $data['exclude_by'] ?? [];
        $filterBy = $data['filter_by'] ?? [];
        $posts = array_filter($posts, fn (array $post): bool =>
            ($post['settings']['render'] ?? true) !== false
            && !post_items_render::is_excluded($post, $excludeBy)
            && post_items_render::passes_filter($post, $filterBy)
        );

        $posts = post_items_render::sort_posts($posts, $data['sort'] ?? null);

        return post_items_render::limit($posts, $filterBy);
    }

    public static function id(array $post): string
    {
        return (string) ($post['post_id'] ?? $post['_id'] ?? '');
    }
}
