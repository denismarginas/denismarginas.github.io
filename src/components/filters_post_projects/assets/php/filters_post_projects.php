<?php

require_once __DIR__ . '/parts/filters_post_projects_posts.php';
require_once __DIR__ . '/parts/filters_post_projects_values.php';
require_once __DIR__ . '/parts/filters_post_projects_render.php';

class filters_post_projects
{
    public static function render(array $data = []): string
    {
        $data = listing_toolbar::data($data, 'projects');
        $target = trim((string) ($data['id'] ?? ''));
        if ($target === '') {
            return '';
        }

        $posts = filters_post_projects_posts::find($data);
        if (empty($posts)) {
            return '';
        }

        $filters = filters_post_projects_values::filters($data, $posts);

        return filters_post_projects_render::form($data, $target, $filters, $posts);
    }
}
