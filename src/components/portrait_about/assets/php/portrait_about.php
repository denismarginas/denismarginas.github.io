<?php

class portrait_about
{
    public static function render(array $data = []): string
    {
        $src = self::str($data['img'] ?? '');
        if ($src === '') return '';

        $image = trim((string) PlatformComponentRenderer::render('image', [
            'src' => $src,
            'alt' => self::str($data['alt'] ?? '') ?: 'Portrait',
            'class' => 'portrait-about-img',
            'lazy' => true,
        ]));
        if ($image === '') return '';

        $class = self::str($data['class'] ?? '');
        $id = self::str($data['id'] ?? '');
        $maxWidth = self::str($data['max_width'] ?? '');
        $style = $maxWidth !== '' && !preg_match('/[;{}<>"\']/', $maxWidth) ? ' style="--portrait-about-max-width: ' . self::e($maxWidth) . ';"' : '';

        return PlatformTemplateRenderer::render(__DIR__ . '/../html/template.html', [
            'extra_class' => $class !== '' ? ' ' . self::e($class) : '',
            'id_attr' => ($id !== '' ? ' id="' . self::e($id) . '"' : '') . $style,
            'image' => $image,
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
