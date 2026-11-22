<?php

require_once __DIR__ . '/parts/project_media_finder.php';
require_once __DIR__ . '/../../../utility_post_thumbnail/assets/php/utility_post_thumbnail.php';

class card_post_project_visual_media
{
    public static function render(array $data = []): string
    {
        $post = is_array($data['post_current_data'] ?? null) ? $data['post_current_data'] : [];
        if (empty($post)) {
            return '';
        }

        $postData = is_array($post['data'] ?? null) ? $post['data'] : [];
        $lazy = !array_key_exists('lazy', $data) || (bool) $data['lazy'];

        $photoPath = utility_post_thumbnail::get($postData);
        if ($photoPath === '') $photoPath = project_media_finder::photo($postData);
        $photo = self::image($photoPath, 'media-photo', $lazy);
        $logo = self::logo($postData['media']['logo'] ?? [], $lazy);
        if ($photo === '' && $logo === '') {
            return '';
        }

        $colors = $post['settings']['appearance']['colors'] ?? [];
        $color = self::color($colors['primary'] ?? '');
        $logoColor = self::color($colors['canvas_primary'] ?? '');
        $bgStyle = self::bg_style($color);
        $link = PlatformPathService::post_link((string) ($post['_id'] ?? ''));

        return PlatformTemplateRenderer::render(__DIR__ . '/../html/template.html', [
            'link' => htmlspecialchars($link, ENT_QUOTES, 'UTF-8'),
            'color_style' => $color !== '' ? ' style="--primary-color-post: ' . $color . ';"' : '',
            'bg_style' => $bgStyle,
            'photo' => $photo,
            'logo' => $logo !== '' ? '<div class="logo"' . self::bg_style($logoColor) . '>' . $logo . '</div>' : '',
        ]);
    }

    private static function color(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    private static function bg_style(string $color): string
    {
        return $color !== '' ? ' style="background-color: ' . $color . ';"' : '';
    }

    private static function logo(mixed $logo, bool $lazy): string
    {
        if (!is_array($logo)) {
            return '';
        }

        $img = (string) ($logo['img'] ?? '');
        if ($img !== '') {
            return self::image('src/content/img/projects/' . ltrim($img, '/'), 'logo-image', $lazy);
        }

        $svg = (string) ($logo['svg'] ?? '');
        return $svg !== ''
            ? PlatformComponentRenderer::render('svg', ['icon' => $svg, 'class' => 'logo-svg'])
            : '';
    }

    private static function image(string $src, string $class, bool $lazy): string
    {
        if ($src === '') {
            return '';
        }

        return PlatformComponentRenderer::render('image', [
            'src' => $src,
            'alt' => PlatformTextService::ui('image_alt', 'Image') . ': ' . pathinfo($src, PATHINFO_FILENAME),
            'class' => $class,
            'lazy' => $lazy,
        ]);
    }
}
