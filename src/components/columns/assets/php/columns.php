<?php

class columns
{
    private const LAYOUTS = ['100', '50-50', '60-40', '40-60', '70-30', '30-70', '75-25', '25-75', '80-20', '20-80', '33-33-33'];
    private const BREAKPOINTS = ['base', 'xs', 'sm', 'md', 'lg', 'xl', '2xl'];
    private const GAPS = ['0', 'xxs', 'xs', 'sm', 'md', 'lg', 'xl', '2xl', '3xl'];
    private const ALIGNS = ['start', 'center', 'end', 'stretch'];
    private const PASSTHROUGH = ['post_current_data', 'global_content_path', 'global_img_path', 'global_vid_path'];

    public static function render(array $data = []): string
    {
        $columnsHtml = self::columns($data);
        if ($columnsHtml === '') return '';

        $layouts = self::responsive($data['layout'] ?? '50-50', self::LAYOUTS, '50-50', $data['stack_below'] ?? 'lg', true);
        $gaps = self::responsive($data['gap'] ?? 'xl', self::GAPS, 'xl', null, false);
        $a = is_scalar($data['align'] ?? null) ? strtolower(trim((string) $data['align'])) : '';
        $align = in_array($a, self::ALIGNS, true) ? $a : 'start';

        $class = is_scalar($data['class'] ?? null) ? trim((string) $data['class']) : '';
        $id = is_scalar($data['id'] ?? null) ? trim((string) $data['id']) : '';

        return PlatformTemplateRenderer::render(__DIR__ . '/../html/template.html', [
            'grid_class' => self::classes('cols', $layouts),
            'gap_class' => self::classes('gap', $gaps),
            'extra_class' => $class !== '' ? ' ' . self::e($class) : '',
            'id_attr' => $id !== '' ? ' id="' . self::e($id) . '"' : '',
            'layout' => self::e(implode(' ', array_map(fn ($bp, $l) => $bp . ':' . $l, array_keys($layouts), $layouts))),
            'align' => $align,
            'reverse_attr' => !empty($data['reverse_stacked']) ? ' data-reverse-stacked="true" data-stack-below="' . self::stack_below($layouts) . '"' : '',
            'columns' => $columnsHtml,
        ]);
    }

    private static function columns(array $data): string
    {
        $children = $data['children'] ?? [];
        if (!is_array($children) || empty($children)) return '';

        $passthrough = array_intersect_key($data, array_flip(self::PASSTHROUGH));

        $html = '';
        $index = 0;
        foreach ($children as $child) {
            if (!is_array($child)) continue;

            if (array_key_exists('html', $child) && !isset($child['component'])) {
                $inner = trim(is_string($child['html']) ? $child['html'] : '');
                if ($inner === '') continue;
                $class = is_scalar($child['class'] ?? null) ? trim((string) $child['class']) : '';
                $html .= '<div class="column' . ($class !== '' ? ' ' . self::e($class) : '') . '">' . $inner . '</div>';
                continue;
            }
            $name = str_replace('-', '_', (string) ($child['component'] ?? ''));
            if ($name === '') continue;

            $explicit = array_key_exists('transition', $child['data'] ?? []) || array_key_exists('transition', $child);
            if ($explicit) {
                $transition = $child['data']['transition'] ?? $child['transition'] ?? null;
            } else {
                $transition = PlatformComponentRenderer::transition_rule($name === 'column' ? 'column' : $name);
                if (is_array($transition) && isset($transition['stagger'])) {
                    $transition['delay'] = (int) ($transition['delay'] ?? 0) + $index * (int) $transition['stagger'];
                    unset($transition['stagger']);
                }
            }
            unset($child['transition']);
            $child['data']['transition'] = false;
            $index++;

            $blocks = $name === 'column'
                ? (array) ($child['children'] ?? $child['data']['children'] ?? [])
                : [$child];

            $animated = $transition !== null && $transition !== false;
            $inner = trim((string) section::render_children(array_merge($passthrough, ['children' => $blocks], $animated ? ['transition_rules' => false] : [])));
            if ($inner === '') continue;

            $class = $name === 'column' && is_scalar($child['data']['class'] ?? null) ? trim((string) $child['data']['class']) : '';
            $attrs = $transition !== null ? PlatformComponentRenderer::transition_attrs($transition) : '';
            $html .= '<div class="column' . ($class !== '' ? ' ' . self::e($class) : '') . '"' . $attrs . '>' . $inner . '</div>';
        }
        return $html;
    }

    private static function responsive(mixed $value, array $allowed, string $default, ?string $stackBelow, bool $isLayout): array
    {
        if (is_array($value)) {
            $out = [];
            foreach (self::BREAKPOINTS as $bp) {
                $raw = $value[$bp] ?? ($bp === 'base' ? ($value['default'] ?? '') : '');
                $v = is_scalar($raw) ? strtolower(trim((string) $raw)) : '';
                if ($v !== '' && in_array($v, $allowed, true)) $out[$bp] = $v;
            }
            if (!isset($out['base'])) $out = ['base' => $isLayout ? '100' : $default] + $out;
            return $out;
        }

        $v = is_scalar($value) ? strtolower(trim((string) $value)) : '';
        if (!in_array($v, $allowed, true)) $v = $default;
        if (!$isLayout || $v === '100') return ['base' => $v];

        $s = is_scalar($stackBelow) ? strtolower(trim((string) $stackBelow)) : '';
        $bp = in_array($s, ['md', 'lg', 'xl'], true) ? $s : 'lg';
        return ['base' => '100', $bp => $v];
    }

    private static function stack_below(array $layouts): string
    {
        foreach ($layouts as $bp => $layout) {
            if ($layout !== '100') return $bp;
        }
        return 'none';
    }

    private static function classes(string $prefix, array $values): string
    {
        $classes = [];
        foreach ($values as $bp => $value) {
            $classes[] = $prefix . '-' . ($bp === 'base' ? '' : $bp . '-') . $value;
        }
        return implode(' ', $classes);
    }

    private static function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
