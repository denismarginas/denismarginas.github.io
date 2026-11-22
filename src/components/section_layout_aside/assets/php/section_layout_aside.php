<?php

class section_layout_aside
{
    private const SLOT_COMPONENTS = [
        'layout_content' => 'content',
        'layout_aside' => 'aside',
    ];

    public static function render(array $data = []): string
    {
        $slots = self::resolve_slots($data['children'] ?? []);

        $passthrough = array_intersect_key($data, array_flip([
            'post_current_data',
            'global_content_path',
            'global_img_path',
            'global_vid_path',
        ]));

        $contentHtml = section::render_children(array_merge($passthrough, ['children' => $slots['content']]));
        $asideHtml = section::render_children(array_merge($passthrough, ['children' => $slots['aside']]));

        $position = strtolower(trim((string) ($data['aside_position'] ?? 'right')));
        if (!in_array($position, ['left', 'right'], true)) $position = 'right';

        $mobile = strtolower(trim((string) ($data['aside_mobile'] ?? 'bottom')));
        if (!in_array($mobile, ['top', 'bottom'], true)) $mobile = 'bottom';

        $classes = [];
        if (trim($asideHtml) === '') $classes[] = 'section-layout-aside-no-aside';
        if (self::flag($data, 'aside_sticky', true)) $classes[] = 'section-layout-aside-sticky';
        $extra = trim((string) ($data['class'] ?? ''));
        if ($extra !== '') $classes[] = $extra;
        $extraClass = $classes ? ' ' . htmlspecialchars(implode(' ', $classes), ENT_QUOTES, 'UTF-8') : '';

        $id = trim((string) ($data['id'] ?? ''));
        $idAttr = $id !== '' ? ' id="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '"' : '';

        $styleAttr = self::style_attr($data);

        $asideBlock = trim($asideHtml) === ''
            ? ''
            : '<aside class="section-layout-aside-aside">' . $asideHtml . '</aside>';

        return PlatformTemplateRenderer::render([
            'extra_class' => $extraClass,
            'id_attr' => $idAttr,
            'aside_position' => $position,
            'aside_mobile' => $mobile,
            'style_attr' => $styleAttr,
            'content' => $contentHtml,
            'aside' => $asideBlock,
        ]);
    }

    private static function resolve_slots($children): array
    {
        $slots = ['content' => [], 'aside' => []];
        if (!is_array($children) || empty($children)) return $slots;

        if (!array_is_list($children)) {
            foreach (array_keys($slots) as $slot) {
                $block = $children[$slot] ?? [];
                $slots[$slot] = is_array($block) ? self::list_of($block['children'] ?? $block) : [];
            }
            return $slots;
        }

        foreach ($children as $item) {
            if (!is_array($item)) continue;
            $name = str_replace('-', '_', (string) ($item['component'] ?? ''));
            $slot = self::SLOT_COMPONENTS[$name] ?? null;

            if ($slot === null) {
                $slots['content'][] = $item;
                continue;
            }

            $slotChildren = $item['children'] ?? $item['data']['children'] ?? [];
            $slots[$slot] = array_merge($slots[$slot], self::list_of($slotChildren));
        }

        return $slots;
    }

    private static function style_attr(array $data): string
    {
        $declarations = [];

        if (self::flag($data, 'appearance_colors', true)) {
            $colors = trim((string) PlatformComponentRenderer::value('utility_appearance_colors', [
                'post_current_data' => $data['post_current_data'] ?? [],
                'output' => 'declarations',
            ]));
            if ($colors !== '') $declarations[] = rtrim($colors, '; ');
        }

        $width = trim((string) ($data['aside_width'] ?? ''));
        if ($width !== '' && !preg_match('/[;{}<>"\']/', $width)) {
            $declarations[] = '--section-layout-aside-width: ' . $width;
        }

        return $declarations
            ? ' style="' . htmlspecialchars(implode('; ', $declarations) . ';', ENT_QUOTES, 'UTF-8') . '"'
            : '';
    }

    private static function list_of($value): array
    {
        return is_array($value) && array_is_list($value) ? $value : [];
    }

    private static function flag(array $data, string $key, bool $default): bool
    {
        if (!array_key_exists($key, $data)) return $default;
        $v = $data[$key];
        if (is_bool($v)) return $v;
        return !in_array(strtolower(trim((string) $v)), ['false', '0', 'no', 'off', ''], true);
    }
}
