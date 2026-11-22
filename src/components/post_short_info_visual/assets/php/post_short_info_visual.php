<?php

require_once __DIR__ . '/post_short_info_post.php';

class post_short_info_visual
{
    private const PROJECTS_IMG_DIR = 'src/content/img/projects/';
    private const FALLBACK_SVG = 'projects';

    public static function render(array $data = []): string
    {
        $post = post_short_info_post::resolve($data);
        if (empty($post)) return '';

        $postData = post_short_info_post::post_data($post);
        $title = (string) ($postData['seo']['title'] ?? '');

        $logo = self::logo($postData['media']['logo'] ?? [], $title);

        $classes = [$logo['type'] === 'svg' ? 'post-short-info-visual-svg' : 'post-short-info-visual-img'];
        $extra = trim((string) ($data['class'] ?? ''));
        if ($extra !== '') $classes[] = $extra;

        return PlatformTemplateRenderer::render(__DIR__ . '/../html/template.html', [
            'extra_class' => ' ' . htmlspecialchars(implode(' ', $classes), ENT_QUOTES, 'UTF-8'),
            'logo' => $logo['html'],
        ]);
    }

    private static function logo(mixed $logo, string $title): array
    {
        $logo = is_array($logo) ? $logo : [];

        $img = trim((string) ($logo['img'] ?? ''));
        if ($img !== '') {
            $src = str_starts_with($img, 'src/') ? $img : self::PROJECTS_IMG_DIR . ltrim($img, '/');
            $html = (string) PlatformComponentRenderer::render('image', [
                'src' => $src,
                'alt' => 'Post Logo - ' . $title,
                'class' => 'post-short-info-visual-logo',
            ]);
            if ($html !== '') return ['type' => 'img', 'html' => $html];
        }

        $svg = trim((string) ($logo['svg'] ?? ''));
        $html = $svg !== '' ? self::svg($svg) : '';
        if ($html === '') $html = self::svg(self::FALLBACK_SVG);

        return ['type' => 'svg', 'html' => $html];
    }

    private static function svg(string $icon): string
    {
        return (string) PlatformComponentRenderer::render('svg', ['icon' => $icon, 'class' => 'post-short-info-visual-logo']);
    }
}
