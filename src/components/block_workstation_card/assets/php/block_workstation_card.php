<?php

class block_workstation_card
{
    public static function render(array $data = []): string
    {
        $item = is_array($data['item'] ?? null) ? $data['item'] : (is_array($data['post_current_data'] ?? null) ? $data['post_current_data'] : []);
        $id = self::str($item['_id'] ?? '');
        if ($id === '') return '';

        $title = self::str($item['data']['seo']['title'] ?? $id);
        $src = self::str($data['image'] ?? '') ?: utility_post_thumbnail::get($item, 'default');
        $image = $src !== '' ? (string) PlatformComponentRenderer::render('image', ['src' => $src, 'alt' => $title, 'class' => 'block-workstation-card-img', 'lazy' => true]) : '';
        if (trim($image) === '') {
            $image = (string) PlatformComponentRenderer::render('svg', ['icon' => 'workstation', 'class' => 'block-workstation-card-svg']);
        }

        $class = self::str($data['class'] ?? '');

        return PlatformTemplateRenderer::render([
            'extra_class' => $class !== '' ? ' ' . self::e($class) : '',
            'style_attr' => (string) PlatformComponentRenderer::value('utility_appearance_colors', ['post_current_data' => $item]),
            'link' => self::e(PlatformPathService::post_link($id)),
            'title' => self::e($title),
            'image' => $image,
            'status' => (string) PlatformComponentRenderer::render('block_workstation_status', ['item' => $item]),
        ]);
    }

    private static function str(mixed $value): string
    {
        return is_scalar($value) ? trim((string) $value) : '';
    }

    private static function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
