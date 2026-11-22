<?php

class carousels_projects_items
{
    public static function render(array $data = []): string
    {
        $children = '';
        foreach ((array) ($data['children'] ?? []) as $child) {
            $component = str_replace('-', '_', (string) ($child['component'] ?? ''));
            if ($component === '') continue;
            $children .= PlatformComponentRenderer::render($component, (array) ($child['data'] ?? []));
        }
        if ($children === '') {
            return '';
        }

        $id = trim((string) ($data['id'] ?? ''));
        $class = trim((string) ($data['class'] ?? ''));

        return PlatformTemplateRenderer::render(__DIR__ . '/../html/template.html', [
            'id_attr' => $id !== '' ? ' id="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '"' : '',
            'extra_class' => $class !== '' ? ' ' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') : '',
            'children' => $children,
        ]);
    }
}
