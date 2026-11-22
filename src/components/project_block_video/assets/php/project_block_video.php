<?php

class project_block_video
{
    private const PROJECTS_IMG_DIR = 'src/content/img/projects/';
    private const OVERLAY = 'src/content/img/thumbnails/workpreview-overlay-thumbnail.webp';

    public static function render(array $data = []): string
    {
        $src = trim(str_replace('\\', '/', (string) ($data['src'] ?? $data['video'] ?? '')));
        if ($src === '') return '';

        $post = is_array($data['post_current_data'] ?? null) ? $data['post_current_data'] : [];
        $params = array_intersect_key($data, array_flip(['src', 'thumbnail_bg', 'video_primary_color']));

        $classes = [];
        if (block_video::item_thumbnail($src) === ''
            && trim((string) ($data['thumbnail_bg'] ?? '')) === ''
            && self::flag($data, 'logo_thumbnail', true)
        ) {
            $logo = self::logo($post);
            if ($logo !== '') {
                $params['thumbnail_bg'] = self::asset(self::OVERLAY);
                $params['thumbnail_content'] = $logo;
                $classes[] = 'project-block-video-logo-thumbnail';
            }
        }

        $video = (string) PlatformComponentRenderer::render('block_video', $params);
        if (trim($video) === '') return '';

        $extra = trim((string) ($data['class'] ?? ''));
        if ($extra !== '') $classes[] = $extra;
        $id = trim((string) ($data['id'] ?? ''));

        return PlatformTemplateRenderer::render([
            'extra_class' => $classes ? ' ' . htmlspecialchars(implode(' ', $classes), ENT_QUOTES, 'UTF-8') : '',
            'id_attr' => $id !== '' ? ' id="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '"' : '',
            'video' => $video,
        ]);
    }

    private static function logo(array $post): string
    {
        $logo = $post['data']['media']['logo'] ?? [];
        if (!is_array($logo)) return '';
        $title = (string) ($post['data']['seo']['title'] ?? '');

        $img = trim((string) ($logo['img'] ?? ''));
        if ($img !== '') {
            $html = (string) PlatformComponentRenderer::render('image', [
                'src' => str_starts_with($img, 'src/') ? $img : self::PROJECTS_IMG_DIR . ltrim($img, '/'),
                'alt' => 'Logo - ' . $title,
                'class' => 'project-block-video-logo',
                'lazy' => true,
            ]);
            if (trim($html) !== '') return $html;
        }

        $svg = trim((string) ($logo['svg'] ?? ''));
        return $svg !== '' ? (string) PlatformComponentRenderer::render('svg', ['icon' => $svg, 'class' => 'project-block-video-logo']) : '';
    }

    private static function asset(string $path): string
    {
        $root = defined('ENGINE_PROJECT_ROOT') ? rtrim(ENGINE_PROJECT_ROOT, '/\\') . '/' : '';
        if (!is_file($root . $path)) return '';
        return rtrim(PlatformPathService::asset_relative_prefix(), '/') . '/' . $path;
    }

    private static function flag(array $data, string $key, bool $default): bool
    {
        if (!array_key_exists($key, $data)) return $default;
        $v = $data[$key];
        if (is_bool($v)) return $v;
        return !in_array(strtolower(trim((string) $v)), ['false', '0', 'no', 'off', ''], true);
    }
}
