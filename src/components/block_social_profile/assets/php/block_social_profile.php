<?php

class block_social_profile
{
    public static function render(array $data = []): string
    {
        $post = is_array($data['post_current_data'] ?? null) ? $data['post_current_data'] : [];
        $title = self::field($data['title'] ?? '', $post);
        $description = self::field($data['description'] ?? '', $post);
        $wallpaper = self::str($data['wallpaper'] ?? '');
        $avatar = self::str($data['avatar'] ?? '');
        if ($title === '' && $wallpaper === '') return '';

        $class = trim((self::str($data['layout'] ?? '') === 'stacked' ? 'block-social-profile-stacked ' : '') . self::str($data['class'] ?? ''));
        $id = self::str($data['id'] ?? '');

        return PlatformTemplateRenderer::render([
            'extra_class' => $class !== '' ? ' ' . self::e($class) : '',
            'id_attr' => $id !== '' ? ' id="' . self::e($id) . '"' : '',
            'wallpaper' => $wallpaper !== '' ? (string) PlatformComponentRenderer::render('image', ['src' => $wallpaper, 'alt' => '', 'class' => 'block-social-profile-wallpaper-img']) : '',
            'avatar' => $avatar !== '' ? (string) PlatformComponentRenderer::render('image', ['src' => $avatar, 'alt' => $title, 'class' => 'block-social-profile-avatar-img']) : '',
            'tag' => self::str($data['title_tag'] ?? '') === 'h2' ? 'h2' : 'h1',
            'title' => self::e($title),
            'description' => $description !== '' ? '<p class="block-social-profile-description">' . self::e($description) . '</p>' : '',
        ]);
    }

    private static function field(mixed $raw, array $post): string
    {
        $raw = self::str($raw);
        if (!str_starts_with($raw, '@')) return $raw;
        $path = substr($raw, 1);
        $value = PlatformDataService::resolve_path_string($post, $path);
        if ($value === null && str_starts_with($path, 'data.')) $value = PlatformDataService::resolve_path_string($post, substr($path, 5));
        return self::str($value);
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
