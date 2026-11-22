<?php

class block_workstation_hero
{
    public static function render(array $data = []): string
    {
        $item = is_array($data['post_current_data'] ?? null) ? $data['post_current_data'] : [];
        $seo = is_array($item['data']['seo'] ?? null) ? $item['data']['seo'] : [];

        $title = self::str($data['title'] ?? $seo['title'] ?? '');
        if ($title === '') return '';
        $description = self::str($data['description'] ?? $seo['description'] ?? '');

        $tag = strtolower(self::str($data['tag'] ?? 'h2'));
        if (!in_array($tag, ['h1', 'h2', 'h3'], true)) $tag = 'h2';

        $src = self::image_path(self::str($data['image'] ?? ''), $item, (string) ($data['global_img_path'] ?? ''));
        $image = $src !== '' ? (string) PlatformComponentRenderer::render('image', ['src' => $src, 'alt' => $title, 'class' => 'block-workstation-hero-img']) : '';
        if (trim($image) === '') {
            $image = (string) PlatformComponentRenderer::render('svg', ['icon' => 'workstation', 'class' => 'block-workstation-hero-svg']);
        }

        $class = self::str($data['class'] ?? '');
        $id = self::str($data['id'] ?? '');

        return PlatformTemplateRenderer::render([
            'extra_class' => $class !== '' ? ' ' . self::e($class) : '',
            'id_attr' => $id !== '' ? ' id="' . self::e($id) . '"' : '',
            'tag' => $tag,
            'title' => self::e($title),
            'description' => $description !== '' ? '<p class="block-workstation-hero-description">' . self::e($description) . '</p>' : '',
            'image' => $image,
            'status' => (string) PlatformComponentRenderer::render('block_workstation_status', ['item' => $item]),
        ]);
    }

    private static function image_path(string $image, array $item, string $globalImg): string
    {
        if ($image !== '' && !str_contains($image, '..')) {
            if (str_starts_with($image, 'src/')) return $image;
            $media = trim(str_replace('\\', '/', (string) ($item['data']['media']['path'] ?? '')), '/');
            $globalImg = trim(str_replace('\\', '/', $globalImg), '/');
            if ($globalImg !== '') return $globalImg . '/' . ($media !== '' ? $media . '/' : '') . ltrim($image, '/');
        }
        return utility_post_thumbnail::get($item, 'default');
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
