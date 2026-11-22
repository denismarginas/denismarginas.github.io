<?php

class section
{
    private const VARIANTS = ['panel', 'dark', 'glow'];

    public static function render(array $data = []): string
    {
        $class = trim((string) ($data['class'] ?? ''));
        $variant = strtolower(trim((string) ($data['variant'] ?? '')));
        if (in_array($variant, self::VARIANTS, true)) $class = trim('content-section-' . $variant . ' ' . $class);
        $extraClass = $class !== '' ? ' ' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') : '';

        $id = trim((string) ($data['id'] ?? ''));
        $idAttr = $id !== '' ? ' id="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '"' : '';

        return PlatformTemplateRenderer::render([
            'extra_class' => $extraClass,
            'id_attr' => $idAttr,
            'children' => self::render_children($data),
        ]);
    }

    public static function render_children(array $data): string
    {
        $children = $data['children'] ?? [];

        if (is_string($children)) return $children;

        if (!is_array($children) || empty($children)) {
            return (string) ($data['content'] ?? $data['body_content'] ?? $data['children_html'] ?? '');
        }

        $passthrough = array_intersect_key($data, array_flip([
            'post_current_data',
            'global_content_path',
            'global_img_path',
            'global_vid_path',
        ]));

        $useRules = ($data['transition_rules'] ?? true) !== false;

        $html = '';
        foreach ($children as $item) {
            $componentName = (string) ($item['component'] ?? '');
            if ($componentName === '') continue;

            $componentData = array_merge($item['data'] ?? [], $passthrough, [
                'children' => $item['children'] ?? $item['data']['children'] ?? [],
            ]);

            if ($useRules && !array_key_exists('transition', $componentData)) {
                $rule = PlatformComponentRenderer::transition_rule($componentName);
                if ($rule !== null) $componentData['transition'] = $rule;
            }

            $html .= PlatformComponentRenderer::render(str_replace('-', '_', $componentName), $componentData);
        }

        return $html;
    }
}
