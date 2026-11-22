<?php

require_once __DIR__ . '/parts/hobby_pc_video_games_filters_values.php';
require_once __DIR__ . '/parts/hobby_pc_video_games_filters_render.php';

class hobby_pc_video_games_filters
{
    public static function render(array $data = []): string
    {
        $data = listing_toolbar::data($data, 'pc-video-games');
        $target = trim((string) ($data['id'] ?? '')) ?: 'hobby-pc-video-games';

        $renderHidden = ($data['render_hidden'] ?? $data['show_hidden'] ?? true) !== false;
        $items = hobby_pc_video_games_listing_items::find(array_merge($data, ['sort' => [], 'show_hidden' => $renderHidden]));
        if (empty($items)) {
            return '';
        }

        $filters = hobby_pc_video_games_filters_values::filters($data, $items);

        return hobby_pc_video_games_filters_render::form($data, $target, $filters, $items);
    }
}
