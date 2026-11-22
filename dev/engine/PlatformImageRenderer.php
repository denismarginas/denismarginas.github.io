<?php

class PlatformImageRenderer
{
    public static function render(array $data = []): string
    {
        $src = $data['src'] ?? '';
        $alt = $data['alt'] ?? '';
        $class = $data['class'] ?? 'responsive-image';
        $title = $data['title'] ?? '';
        $lazy = !empty($data['lazy']) ? 'loading="lazy"' : '';
        $additional_attributes = $data['additionalAttributes'] ?? [];

        if (empty($src)) {
            return '';
        }

        $url_path = $GLOBALS['url_path'] ?? '';

        $output_src = $src;
        if ($url_path !== '' && !str_starts_with($src, 'http') && !str_starts_with($src, '//') && !str_starts_with($src, '/') && !str_starts_with($src, $url_path)) {
            $output_src = $url_path . $src;
        }

        if (empty($alt)) {
            $image_path_parts = pathinfo($src);
            $alt = isset($image_path_parts['filename']) ? 'Image: ' . $image_path_parts['filename'] : '';
        }

        $attributes_portion = ' ' . $lazy;
        foreach ($additional_attributes as $attr => $value) {
            $attributes_portion .= ' ' . htmlspecialchars($attr, ENT_QUOTES, 'UTF-8') . '="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"';
        }

        $class_attr = htmlspecialchars($class, ENT_QUOTES, 'UTF-8');
        $src_attr = htmlspecialchars($output_src, ENT_QUOTES, 'UTF-8');
        $alt_attr = htmlspecialchars($alt, ENT_QUOTES, 'UTF-8');
        $title_attr = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');

        return '<img class="' . $class_attr . '" src="' . $src_attr . '" alt="' . $alt_attr . '" title="' . $title_attr . '"' . $attributes_portion . '>';
    }
}
