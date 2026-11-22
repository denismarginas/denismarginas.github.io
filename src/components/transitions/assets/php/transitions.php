<?php

class transitions
{
    private const EFFECTS = [
        'fade', 'slide-left', 'slide-right', 'slide-top', 'slide-bottom',
        'rotate', 'rotate-left', 'rotate-right', 'zoom', 'zoom-in', 'zoom-out', 'blur',
    ];

    private const OPTIONS = [
        'duration' => 'duration', 'delay' => 'delay', 'easing' => 'easing', 'distance' => 'distance',
        'angle' => 'angle', 'scale' => 'scale', 'blur' => 'blur', 'threshold' => 'threshold',
        'target' => 'target', 'stagger' => 'stagger',
    ];

    public static function render(array $data = []): string
    {
        $children = section::render_children($data);
        if (trim($children) === '') return '';

        $effects = self::effects($data['effect'] ?? $data['effects'] ?? 'fade');
        $attrs = $effects !== '' ? self::attr('data-transition', $effects) : '';
        foreach (self::OPTIONS as $key => $name) {
            if (!isset($data[$key]) || !is_scalar($data[$key]) || trim((string) $data[$key]) === '') continue;
            $attrs .= self::attr('data-transition-' . $name, (string) $data[$key]);
        }
        if (($data['once'] ?? true) === false) $attrs .= self::attr('data-transition-once', 'false');
        if (($data['each'] ?? false) === true) $attrs .= self::attr('data-transition-each', 'true');

        $tag = strtolower(trim((string) ($data['tag'] ?? 'div')));
        if (!preg_match('/^[a-z][a-z0-9-]*$/', $tag)) $tag = 'div';

        $class = trim((string) ($data['class'] ?? ''));
        if (($data['contents'] ?? false) === true) $class = trim('transitions-contents ' . $class);
        $id = trim((string) ($data['id'] ?? ''));

        return PlatformTemplateRenderer::render(__DIR__ . '/../html/template.html', [
            'tag' => $tag,
            'extra_class' => $class !== '' ? ' ' . self::e($class) : '',
            'id_attr' => $id !== '' ? self::attr('id', $id) : '',
            'attrs' => $attrs,
            'children' => $children,
        ]);
    }

    private static function effects(mixed $value): string
    {
        $list = is_array($value) ? $value : preg_split('/[\s,]+/', (string) $value);
        $out = [];
        foreach ($list as $effect) {
            $effect = strtolower(trim((string) $effect));
            if (in_array($effect, self::EFFECTS, true) && !in_array($effect, $out, true)) $out[] = $effect;
        }
        return implode(' ', $out);
    }

    private static function attr(string $name, string $value): string
    {
        return ' ' . $name . '="' . self::e($value) . '"';
    }

    private static function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
