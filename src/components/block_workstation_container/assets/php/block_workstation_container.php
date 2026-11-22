<?php

class block_workstation_container
{
    private const PASSTHROUGH = ['post_current_data', 'global_content_path', 'global_img_path', 'global_vid_path'];

    public static function render(array $data = []): string
    {
        $children = (string) section::render_children(array_merge(
            array_intersect_key($data, array_flip(self::PASSTHROUGH)),
            ['children' => is_array($data['children'] ?? null) ? $data['children'] : []]
        ));
        if (trim($children) === '') return '';

        $title = self::str($data['title'] ?? '');
        $tag = self::str($data['title_tag'] ?? '') === 'h3' ? 'h3' : 'h2';
        $class = self::str($data['class'] ?? '');
        $id = self::str($data['id'] ?? '');

        return PlatformTemplateRenderer::render([
            'extra_class' => $class !== '' ? ' ' . self::e($class) : '',
            'id_attr' => $id !== '' ? ' id="' . self::e($id) . '"' : '',
            'title' => $title !== '' ? '<' . $tag . ' class="title-divider title-tertiary font-primary">' . self::e($title) . '</' . $tag . '>' : '',
            'children' => $children,
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
