<?php

class PlatformSvg
{
    public static function render(array $data = []): string
    {
        $name = $data['name'] ?? $data['svg'] ?? '';
        if ($name === '') return '';

        $path = __DIR__ . '/../../../../assets/svg/' . $name . '.svg';
        if (!file_exists($path)) return '';

        $svg = file_get_contents($path);
        if ($svg === false) return '';

        $class = $data['class'] ?? '';
        $size = $data['size'] ?? '';

        $spanClass = 'platform-svg';
        if ($class !== '') {
            $spanClass .= ' ' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8');
        }

        $style = '';
        if ($size !== '') {
            $style = ' style="--platform-svg-size:' . htmlspecialchars((string) $size, ENT_QUOTES, 'UTF-8') . 'px"';
        }

        return '<span class="' . $spanClass . '"' . $style . '>' . $svg . '</span>';
    }
}
