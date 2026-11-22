<?php

class Svg
{
    public function render(array $data = []): string
    {
        $icon = $data['icon'] ?? '';
        $class = $data['class'] ?? 'svg-icon';

        if ($icon === '') {
            return '';
        }

        $svgMarkup = $this->load_svg($icon);

        if ($svgMarkup === '') {
            return '';
        }

        if ($class !== '') {
            $classAttr = htmlspecialchars($class, ENT_QUOTES, 'UTF-8');
            $svgMarkup = preg_replace('/<svg(\s[^>]*)?>/', '<svg$1 class="' . $classAttr . '">', $svgMarkup, 1);
        }

        return $svgMarkup;
    }

    public function load_svg(string $icon): string
    {
        $svgDir = PlatformConfig::get('svg_dir', '');
        if ($svgDir === '') {
            return '';
        }

        $svgFile = $svgDir . '/' . basename($icon) . '.svg';
        if (!is_file($svgFile)) {
            return '';
        }

        $markup = file_get_contents($svgFile);
        return is_string($markup) ? trim($markup) : '';
    }
}

