<?php

require_once __DIR__ . '/parts/carousel_projects_items_posts.php';
require_once __DIR__ . '/parts/carousel_projects_items_size.php';

class carousel_projects_items
{
    private const DEFAULT_VISUAL = 'card_post_project_visual_default';
    private const VISUALS = [
        'card_post_project_visual_default',
        'card_post_project_visual_media',
        'card_post_project_visual_devices_website',
        'card_post_project_visual_devices_media',
    ];
    private const ALIASES = ['card_post_project_visual_website' => 'card_post_project_visual_devices_website'];

    public static function render(array $data = []): string
    {
        $template = is_array($data['template'] ?? null) ? $data['template'] : [];
        $visual = str_replace('-', '_', (string) ($template['component'] ?? $data['visual_component'] ?? self::DEFAULT_VISUAL));
        $visual = self::ALIASES[$visual] ?? $visual;
        if (!in_array($visual, self::VISUALS, true)) {
            $visual = self::DEFAULT_VISUAL;
        }
        $visualParams = $template['params'] ?? $data['visual_params'] ?? [];
        $visualParams = is_array($visualParams) ? $visualParams : [];

        $max = post_items_render::max_items($data['filter_by'] ?? null) ?? max(1, (int) ($data['max'] ?? 12));
        $items = [];
        foreach (carousel_projects_items_posts::find($data) as $post) {
            if (count($items) >= $max) break;
            $html = PlatformComponentRenderer::render($visual, array_merge($visualParams, ['post_current_data' => $post]));
            if (trim($html) === '') continue;

            $items[] = [
                'html' => $html,
                'attrs' => ' data-post-id="' . htmlspecialchars((string) ($post['_id'] ?? ''), ENT_QUOTES, 'UTF-8') . '"'
                    . (string) PlatformComponentRenderer::value('utility_appearance_colors', [
                        'post_current_data' => $post,
                        'output' => 'attribute',
                    ]),
            ];
        }
        if ($items === []) {
            return '';
        }

        return (string) PlatformComponentRenderer::render('utility_carousel_loop', [
            'items' => $items,
            'class' => trim('carousel-projects-items ' . (string) ($data['class'] ?? '')),
            'list_class' => 'carousel-list',
            'item_class' => 'carousel-item',
            'attrs' => ' data-visual="' . htmlspecialchars($visual, ENT_QUOTES, 'UTF-8') . '"',
            'direction' => ($data['direction'] ?? 'right') === 'left' ? 'left' : 'right',
            'speed' => $data['speed'] ?? 'slow',
            'gap' => $template['gap'] ?? $data['gap'] ?? null,
            'gap_phone' => $template['gap_phone'] ?? $data['gap_phone'] ?? null,
            'pause_on_hover' => self::flag($data['pause_on_hover'] ?? true),
            'style' => carousel_projects_items_size::declarations(array_merge($data, $template)),
        ]);
    }

    private static function flag(mixed $value): bool
    {
        if (is_bool($value)) return $value;
        if (is_int($value) || is_float($value)) return $value != 0;
        return !in_array(strtolower(trim((string) $value)), ['false', '0', 'no', 'off', ''], true);
    }
}
