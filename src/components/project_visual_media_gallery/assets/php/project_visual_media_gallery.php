<?php

class project_visual_media_gallery
{
    private const PASSTHROUGH = ['post_current_data', 'global_content_path', 'global_img_path', 'global_vid_path'];

    public static function render(array $data = []): string
    {
        $post = is_array($data['post_current_data'] ?? null) ? $data['post_current_data'] : [];
        if (empty($post)) return '';

        $shared = array_intersect_key($data, array_flip(self::PASSTHROUGH));

        $primary = section::render_children(array_merge($shared, [
            'children' => project_visual_web_gallery::galleries($data, 'content_section_primary', []),
        ]));
        $photos = self::part($data, 'photo', 'title_section_secondary', project_visual_media_gallery_photo::DEFAULT_TITLE, $shared);
        $videos = self::part($data, 'video', 'title_section_tertiary', project_visual_media_gallery_video::DEFAULT_TITLE, $shared);

        if (trim($primary . $photos . $videos) === '') return '';

        $title = trim((string) ($data['title_section_primary'] ?? 'Visual Media'));

        return ($title !== '' ? project_visual_web_gallery::title($title, $post) : '') . $primary . $photos . $videos;
    }

    private static function part(array $data, string $key, string $titleKey, string $defaultTitle, array $shared): string
    {
        if (!project_media_gallery::flag($data, 'show_' . $key, true)) return '';

        return (string) PlatformComponentRenderer::render('project_visual_media_gallery_' . $key, array_merge(
            project_media_gallery::params($data, $key),
            ['title' => (string) ($data[$titleKey] ?? $defaultTitle)],
            $shared
        ));
    }
}
