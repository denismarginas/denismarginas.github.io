<?php

class project_visual_media_gallery_video
{
    public const DEFAULT_TITLE = 'Video Media Content';

    private const PASSTHROUGH = ['post_current_data', 'global_content_path', 'global_img_path', 'global_vid_path'];

    public static function render(array $data = []): string
    {
        $post = is_array($data['post_current_data'] ?? null) ? $data['post_current_data'] : [];
        if (empty($post)) return '';

        $gridMax = (int) ($data['grid_max'] ?? 2);
        $default = [[
            'source_gallery_type' => 'videos',
            'source_gallery_dir_devices' => trim((string) ($data['dir'] ?? ''), '/'),
            'auto_search_galleries_dir' => true,
            'multiple_galleries' => true,
            'exclude' => project_media_gallery::strings($data, 'exclude'),
            'grid_max' => $gridMax,
            'id' => 'video',
        ]];

        return project_visual_web_gallery::part(
            (string) ($data['title'] ?? self::DEFAULT_TITLE),
            project_visual_web_gallery::galleries($data, 'galleries', $default),
            $post,
            array_intersect_key($data, array_flip(self::PASSTHROUGH)),
            $gridMax
        );
    }
}
