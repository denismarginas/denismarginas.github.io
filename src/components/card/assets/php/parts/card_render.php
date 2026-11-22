<?php

class card_render extends card_media
{
    public static function render(array $data = []): string
    {
        $variant = (string) ($data['variant'] ?? $data['template'] ?? 'post');
        return self::render_variant($variant, $data);
    }

    public static function render_variant(string $variant, array $data): string
    {
        if (str_starts_with($variant, 'card_')) {
            $variant = substr($variant, 5);
        }
        $method = 'render_' . str_replace('-', '_', $variant);
        if (method_exists(self::class, $method)) {
            return self::$method($data);
        }
        return self::render_post($data);
    }

    public static function render_post(array $data): string
    {
        $postData = $data['post_current_data'] ?? [];

        $title = self::resolve_text_field($data, $postData, 'title');
        $description = self::resolve_description($data, $postData);
        $link = self::resolve_link($data);

        $imageHtml = self::render_media($data, $postData, $title, $link);
        $titleHtml = self::render_title($title, $link);
        $descriptionHtml = self::render_description($description);
        $metaHtml = self::render_meta($data, $postData);
        $buttonHtml = self::render_button($data, $postData, $link);

        $isMinimal = ((string) ($data['variant'] ?? $data['template'] ?? 'post')) === 'minimal';
        $template = $isMinimal
            ? __DIR__ . '/../../html/template_card_minimal.html'
            : __DIR__ . '/../../html/template_card_post.html';

        return PlatformTemplateRenderer::render($template, [
            'image_html' => $imageHtml,
            'title_html' => $titleHtml,
            'description_html' => $descriptionHtml,
            'meta_html' => $metaHtml,
            'button_html' => $buttonHtml,
        ]);
    }

    public static function render_minimal(array $data): string
    {
        return self::render_post($data);
    }

    public static function render_description(string $description): string
    {
        if ($description === '') return '';
        return PlatformTemplateRenderer::render(__DIR__ . '/../../html/parts/description.html', [
            'description' => htmlspecialchars($description, ENT_QUOTES, 'UTF-8'),
        ]);
    }
}


