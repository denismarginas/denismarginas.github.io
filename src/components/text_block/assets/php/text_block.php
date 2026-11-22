<?php

class text_block
{
    public static function render(array $data = []): string
    {
        $elements = $data['elements'] ?? [];
        if (!is_array($elements) || empty($elements)) return '';

        $vars = self::resolve_vars($data['vars'] ?? []);
        if (!empty($vars)) $elements = self::fill_vars($elements, $vars);

        $html = '';
        foreach ($elements as $element) {
            if (is_array($element)) $html .= self::render_element($element);
        }
        return $html;
    }

    private static function resolve_vars(mixed $vars): array
    {
        if (!is_array($vars)) return [];
        $out = [];
        foreach ($vars as $name => $var) {
            $name = trim((string) $name);
            if ($name === '') continue;

            if (is_array($var) && isset($var['component'])) {
                $component = str_replace('-', '_', trim((string) $var['component']));
                $value = $component !== ''
                    ? PlatformComponentRenderer::value($component, is_array($var['data'] ?? null) ? $var['data'] : [])
                    : null;
            } else {
                $value = $var;
            }

            if (is_scalar($value)) $out[$name] = (string) $value;
        }
        return $out;
    }

    private static function fill_vars(mixed $value, array $vars): mixed
    {
        if (is_array($value)) {
            foreach ($value as $k => $v) $value[$k] = self::fill_vars($v, $vars);
            return $value;
        }
        if (!is_string($value) || !str_contains($value, '{{')) return $value;

        return preg_replace_callback(
            '/\{\{\s*([A-Za-z0-9_\-]+)\s*\}\}/',
            fn ($m) => array_key_exists($m[1], $vars) ? $vars[$m[1]] : $m[0],
            $value
        ) ?? $value;
    }

    private static function render_element(array $element): string
    {
        $html = '';

        if (isset($element['title'])) {
            $html .= self::render_heading($element['title'], [
                'tag' => $element['title_tag'] ?? 'h2',
                'class' => $element['title_class'] ?? null,
            ]);
        }

        if (isset($element['subtitle'])) {
            $html .= self::render_heading($element['subtitle'], [
                'tag' => $element['subtitle_tag'] ?? 'h3',
                'class' => $element['subtitle_class'] ?? (string) ($element['class'] ?? ''),
            ]);
        }

        $text = $element['paragraphs'] ?? $element['paragraph'] ?? $element['texts'] ?? $element['text'] ?? null;
        if ($text !== null) {
            $escape = !array_key_exists('escape', $element) || $element['escape'] !== false;
            $html .= self::render_paragraphs($text, (string) ($element['class'] ?? ''), $escape);
        }

        if (isset($element['list']) && is_array($element['list'])) {
            $html .= self::render_list($element['list'], (string) ($element['class'] ?? ''));
        }

        $buttons = $element['buttons'] ?? $element['btns'] ?? null;
        if (is_array($buttons)) {
            $html .= self::render_buttons($buttons, true, (string) ($element['class'] ?? ''));
        }

        $button = $element['button'] ?? $element['btn'] ?? null;
        if (is_array($button)) {
            $html .= self::render_buttons([$button], false);
        }

        return $html;
    }

    private static function render_heading(mixed $value, array $defaults): string
    {
        $data = title::normalize($value);
        if (!array_key_exists('tag', $data)) $data['tag'] = $defaults['tag'] ?? 'h2';
        if (!array_key_exists('class', $data)) $data['class'] = $defaults['class'] ?? null;
        return (string) PlatformComponentRenderer::render('title', $data);
    }

    private static function render_paragraphs(mixed $text, string $class, bool $escape): string
    {
        $classAttr = $class !== '' ? ' class="' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '"' : '';

        if (is_array($text)) {
            $html = '';
            foreach ($text as $p) {
                $p = trim((string) $p);
                if ($p === '') continue;
                $html .= '<p' . $classAttr . '>' . ($escape ? htmlspecialchars($p, ENT_QUOTES, 'UTF-8') : $p) . '</p>';
            }
            return $html;
        }

        $text = trim((string) $text);
        if ($text === '') return '';
        return '<p' . $classAttr . '>' . ($escape ? htmlspecialchars($text, ENT_QUOTES, 'UTF-8') : $text) . '</p>';
    }

    private static function render_list(array $list, string $class): string
    {
        if (empty($list)) return '';
        foreach ($list as $item) {
            if (is_array($item)) return '';
        }

        $classAttr = $class !== '' ? ' class="' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '"' : '';
        $html = '<ul' . $classAttr . '>';
        foreach ($list as $item) {
            $html .= '<li>' . htmlspecialchars((string) $item, ENT_QUOTES, 'UTF-8') . '</li>';
        }
        $html .= '</ul>';
        return $html;
    }

    private static function render_buttons(array $list, bool $wrap, string $class = ''): string
    {
        $html = '';
        foreach ($list as $btn) {
            if (!is_array($btn)) continue;
            $html .= PlatformComponentRenderer::render('button', [
                'text' => (string) ($btn['text'] ?? ''),
                'link' => $btn['link'] ?? $btn['_id'] ?? '',
                'svg' => (string) ($btn['svg'] ?? ''),
                'svg_position' => (string) ($btn['svg_position'] ?? ''),
                'class' => (string) ($btn['class'] ?? 'btn btn-primary'),
                'target' => (string) ($btn['target'] ?? ''),
                'rel' => (string) ($btn['rel'] ?? ''),
            ]);
        }
        if ($html === '') return '';
        if (!$wrap) return $html;

        $wrapClass = $class !== '' ? $class : 'actions';
        return '<div class="' . htmlspecialchars($wrapClass, ENT_QUOTES, 'UTF-8') . '">' . $html . '</div>';
    }
}
