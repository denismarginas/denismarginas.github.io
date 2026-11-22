<?php

class card_media extends card_meta
{
    public static function render_media(array $data, array $postData, string $title, string $link): string
    {
        $src = self::resolve_text_field($data, $postData, 'image');
        if ($src === '') return '';

        $imageHtml = PlatformComponentRenderer::render('image', [
            'src' => $src,
            'alt' => $title,
            'class' => 'card-media-img',
        ]);
        if ($imageHtml === '') return '';

        if ($link !== '') {
            return PlatformTemplateRenderer::render(__DIR__ . '/../../html/parts/media_link.html', [
                'link' => htmlspecialchars($link, ENT_QUOTES, 'UTF-8'),
                'image' => $imageHtml,
            ]);
        }
        return PlatformTemplateRenderer::render(__DIR__ . '/../../html/parts/media_plain.html', [
            'image' => $imageHtml,
        ]);
    }

    public static function render_title(string $title, string $link): string
    {
        if ($title === '') return '';
        $escaped = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        if ($link !== '') {
            return PlatformTemplateRenderer::render(__DIR__ . '/../../html/parts/title_link.html', [
                'link' => htmlspecialchars($link, ENT_QUOTES, 'UTF-8'),
                'title' => $escaped,
            ]);
        }
        return PlatformTemplateRenderer::render(__DIR__ . '/../../html/parts/title_plain.html', [
            'title' => $escaped,
        ]);
    }
}

