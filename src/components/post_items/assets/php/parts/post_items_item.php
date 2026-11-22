<?php

class post_items_item extends post_items_image
{
    public static function render_item(array $post, array $context): string
    {
        $postId = (string)($post['post_id'] ?? $post['_id'] ?? '');
        if ($postId === '') return '';

        $titleParam = $context['title_param'];
        $title = is_string($titleParam) ? $titleParam : (self::value_at($post, $titleParam) ?? $postId);
        $link = PlatformPathService::post_link($postId);

        $item = PlatformTemplateRenderer::render(__DIR__ . '/../../html/template_item_default.html', [
            'image' => self::render_image_link($post, $context, $title, $link),
            'url' => htmlspecialchars($link),
            'title' => htmlspecialchars($title),
        ]);

        return PlatformTemplateRenderer::render(__DIR__ . '/../../html/template_item.html', [
            'post_id' => htmlspecialchars($postId, ENT_QUOTES, 'UTF-8'),
            'item' => $item,
        ]);
    }
}


