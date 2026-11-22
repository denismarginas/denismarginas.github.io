<?php

require_once __DIR__ . '/../../../utility_project_media/assets/php/utility_project_media.php';

class project_related
{
    private const PASSTHROUGH = ['post_current_data', 'global_content_path', 'global_img_path', 'global_vid_path'];

    private const TEMPLATE = [
        'component' => 'card_post_project',
        'params' => [
            'card_class' => 'card-post-project',
            'visual_component' => 'card_post_project_visual_default',
            'title_length' => 0,
            'description' => true,
            'description_length' => 0,
            'columns' => [
                'layout' => ['base' => '100', 'xs' => '40-60', 'sm' => '30-70'],
                'gap' => 'sm',
                'align' => 'center',
            ],
        ],
    ];

    public static function render(array $data = []): string
    {
        $count = self::count($data);
        if ($count === 0) return '';

        $items = (string) PlatformComponentRenderer::render('post_items', array_merge(
            array_intersect_key($data, array_flip(self::PASSTHROUGH)),
            [
                'class' => 'gap-md show-preview project-related-listing' . ($count === 1 ? ' project-related-listing-single' : ''),
                'post_type' => trim((string) ($data['post_type'] ?? 'project')) ?: 'project',
                'filter_by' => is_array($data['filter_by'] ?? null) ? $data['filter_by'] : [],
                'exclude_by' => $data['exclude_by'] ?? [],
                'exclude_current' => ($data['exclude_current'] ?? true) !== false,
                'sort' => $data['sort'] ?? [],
                'template' => is_array($data['template'] ?? null) ? $data['template'] : self::template($count),
            ]
        ));
        if (trim($items) === '') return '';

        $title = trim((string) ($data['title'] ?? ''));
        if ($title === '' || in_array(strtolower($title), ['related project', 'related projects'], true)) {
            $title = $count === 1
                ? utility_project_media::text('related_title_one', 'Related <em>project</em>')
                : utility_project_media::text('related_title', 'Related <em>projects</em>');
        }
        $head = (string) PlatformComponentRenderer::render('section_head', [
            'kicker' => array_key_exists('kicker', $data) ? trim((string) $data['kicker']) : utility_project_media::text('related_kicker', 'Related'),
            'title' => $title,
            'text' => trim((string) ($data['text'] ?? '')),
        ]);

        return (string) PlatformComponentRenderer::render('section', [
            'id' => array_key_exists('id', $data) ? trim((string) $data['id']) : 'related',
            'class' => 'project-section project-related-section',
            'variant' => $data['variant'] ?? 'panel',
            'children' => $head . '<div class="project-related">' . $items . '</div>',
        ]);
    }

    private static function template(int $count): array
    {
        $template = self::TEMPLATE;
        if ($count === 1) $template['params']['columns']['layout']['lg'] = '20-80';
        return $template;
    }

    private static function count(array $data): int
    {
        $posts = PlatformDataService::get_all_posts_from_file(trim((string) ($data['post_type'] ?? 'project')) ?: 'project') ?? [];
        if (!is_array($posts)) return 0;
        $current = (string) ($data['post_current_data']['_id'] ?? '');
        if (($data['exclude_current'] ?? true) !== false && $current !== '') {
            $posts = array_values(array_filter($posts, fn ($p) => is_array($p) && (string) ($p['_id'] ?? '') !== $current));
        }
        return count((array) PlatformComponentRenderer::value('utility_filter', [
            'items' => $posts,
            'filter_by' => is_array($data['filter_by'] ?? null) ? $data['filter_by'] : [],
            'exclude_by' => $data['exclude_by'] ?? [],
        ]));
    }
}
