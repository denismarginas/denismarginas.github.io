<?php

class post_short_info
{
    private const PASSTHROUGH = ['post_current_data', 'post_id', 'global_content_path', 'global_img_path', 'global_vid_path'];

    public static function render(array $data = []): string
    {
        $shared = array_intersect_key($data, array_flip(self::PASSTHROUGH));

        $visual = '';
        if (post_short_info_post::flag($data, 'show_visual', true)) {
            $visual = PlatformComponentRenderer::render('post_short_info_visual', array_merge(self::sub($data, 'visual'), $shared));
        }

        $content = '';
        if (post_short_info_post::flag($data, 'show_content', true)) {
            $content = PlatformComponentRenderer::render('post_short_info_content', array_merge(self::sub($data, 'content'), $shared));
        }

        $children = section::render_children(array_merge($shared, ['children' => $data['children'] ?? []]));

        if (trim($visual . $content . $children) === '') return '';

        return PlatformTemplateRenderer::render([
            'visual' => $visual,
            'content' => $content,
            'children' => $children,
        ]);
    }

    private static function sub(array $data, string $key): array
    {
        $value = $data[$key] ?? [];
        if (is_string($value)) $value = json_decode($value, true);
        return is_array($value) ? $value : [];
    }
}
