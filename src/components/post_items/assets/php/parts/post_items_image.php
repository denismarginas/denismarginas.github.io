<?php

require_once __DIR__ . '/../../../../utility_project_web_screens/assets/php/utility_project_web_screens.php';

class post_items_image extends post_items_value
{
    public static function render_image_link(array $post, array $context, string $title, string $link): string
    {
        $mediaPath = (string)self::value_at($post, $context['media_path_param'] ?? ['data', 'media', 'path']);
        if ($mediaPath === '') return '';

        if ($context['img_path'] === '' && $context['img_filename'] === '') {
            $imagePath = utility_project_web_screens::first($mediaPath, 'desktop', $context['global_img_path']);
        } else {
            $dir = $context['global_img_path'] . '/' . $mediaPath . '/' . $context['img_path'];
            $imageFile = self::resolve_image_file($dir, $context['img_filename']);
            $imagePath = $imageFile !== '' ? $dir . $imageFile : '';
        }
        if ($imagePath === '') return '';

        $imageHtml = PlatformComponentRenderer::render('image', [
            'path' => $imagePath,
            'alt' => $title,
        ]);
        if ($imageHtml === '') return '';

        return PlatformTemplateRenderer::render(__DIR__ . '/../../html/template_item_image.html', [
            'url' => htmlspecialchars($link),
            'image' => $imageHtml,
        ]);
    }

    public static function resolve_image_file(string $dir, string $base): string
    {
        $root = defined('ENGINE_PROJECT_ROOT') ? ENGINE_PROJECT_ROOT . '/' : '';
        $exact = $dir . $base . '.webp';
        if (file_exists($root . $exact)) return $base . '.webp';

        $matches = glob($root . $dir . $base . '-*', GLOB_NOSORT);
        if (!$matches) return '';
        $file = basename($matches[0]);
        return $file;
    }
}


