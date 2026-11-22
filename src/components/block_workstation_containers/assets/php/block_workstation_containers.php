<?php

class block_workstation_containers
{
    private const PASSTHROUGH = ['post_current_data', 'global_content_path', 'global_img_path', 'global_vid_path'];

    public static function render(array $data = []): string
    {
        $children = (string) section::render_children(array_merge(
            array_intersect_key($data, array_flip(self::PASSTHROUGH)),
            ['children' => is_array($data['children'] ?? null) ? $data['children'] : []]
        ));
        if (trim($children) === '') return '';

        $item = is_array($data['post_current_data'] ?? null) ? $data['post_current_data'] : [];
        $style = trim((string) PlatformComponentRenderer::value('utility_appearance_colors', ['post_current_data' => $item, 'output' => 'declarations']));

        $class = is_scalar($data['class'] ?? null) ? trim((string) $data['class']) : '';
        $id = is_scalar($data['id'] ?? null) ? trim((string) $data['id']) : '';

        return PlatformTemplateRenderer::render([
            'extra_class' => $class !== '' ? ' ' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') : '',
            'id_attr' => $id !== '' ? ' id="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '"' : '',
            'style' => htmlspecialchars($style, ENT_QUOTES, 'UTF-8'),
            'children' => $children,
        ]);
    }
}
