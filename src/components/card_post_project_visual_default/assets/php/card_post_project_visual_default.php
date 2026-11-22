<?php

require_once __DIR__ . '/parts/project_default_preview.php';

class card_post_project_visual_default
{
    private const FALLBACK_SVG = 'projects';

    public static function render(array $data = []): string
    {
        $post = is_array($data['post_current_data'] ?? null) ? $data['post_current_data'] : [];
        if (empty($post)) {
            return '';
        }

        $postData = is_array($post['data'] ?? null) ? $post['data'] : [];
        $bgColor = (string) ($post['settings']['appearance']['colors']['canvas_primary'] ?? '');
        $primaryColor = (string) ($post['settings']['appearance']['colors']['primary'] ?? '');
        $preview = project_default_preview::find($postData);

        return PlatformTemplateRenderer::render(__DIR__ . '/../html/template.html', [
            'link' => htmlspecialchars(PlatformPathService::post_link((string) ($post['_id'] ?? '')), ENT_QUOTES, 'UTF-8'),
            'bg_style' => self::style($bgColor, $primaryColor),
            'visual_image' => self::logo($postData['media']['logo'] ?? []),
            'preview_image' => $preview !== '' ? self::image($preview, 'default-visual-preview') : '',
        ]);
    }

    private static function style(string $bgColor, string $primaryColor): string
    {
        $rules = [];
        if ($bgColor !== '') $rules[] = 'background-color: ' . $bgColor . ';';
        if ($primaryColor !== '') $rules[] = '--primary-color-post: ' . $primaryColor . ';';
        return $rules ? ' style="' . htmlspecialchars(implode(' ', $rules), ENT_QUOTES, 'UTF-8') . '"' : '';
    }

    private static function logo(mixed $logo): string
    {
        $logo = is_array($logo) ? $logo : [];

        $img = (string) ($logo['img'] ?? '');
        $html = $img !== '' ? self::image('src/content/img/projects/' . ltrim($img, '/'), 'default-visual-image default-visual-logo') : '';

        $svg = (string) ($logo['svg'] ?? '');
        if ($html === '' && $svg !== '') {
            $html = self::svg($svg);
        }

        return $html !== '' ? $html : self::svg(self::FALLBACK_SVG);
    }

    private static function svg(string $icon): string
    {
        return (string) PlatformComponentRenderer::render('svg', ['icon' => $icon, 'class' => 'default-visual-svg default-visual-logo']);
    }

    private static function image(string $src, string $class): string
    {
        return (string) PlatformComponentRenderer::render('image', [
            'src' => $src,
            'alt' => PlatformTextService::ui('image_alt', 'Image') . ': ' . pathinfo($src, PATHINFO_FILENAME),
            'class' => $class,
            'lazy' => true,
        ]);
    }
}
