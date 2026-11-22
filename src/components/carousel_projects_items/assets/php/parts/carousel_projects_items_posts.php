<?php

class carousel_projects_items_posts
{
    public static function find(array $data): array
    {
        $posts = [];
        foreach (post_items_render::resolve_post_types($data['post_type'] ?? 'project') as $postType) {
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

        return array_slice($posts, max(0, (int) ($data['offset'] ?? 0)));
    }
}
