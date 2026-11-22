<?php

class image_render extends image_sizes
{
    public static function render(array $data = []): string
    {
        $src = (string)($data['src'] ?? $data['path'] ?? '');
        if ($src === '') return '';

        $width = '';
        $height = '';
        if (!PlatformUrlService::is_external_url($src)) {
            $candidates = [];
            $raw = ltrim($src, '/');
            if (!str_starts_with($raw, 'src/content/')) {
                $candidates[] = 'src/content/img/' . $raw;
                $candidates[] = 'src/content/' . $raw;
            }
            $candidates[] = $raw;
            $found = null;
            $absolute = '';
            foreach ($candidates as $cand) {
                $abs = defined('ENGINE_PROJECT_ROOT') ? ENGINE_PROJECT_ROOT . '/' . $cand : $cand;
                if (file_exists($abs)) { $found = $cand; $absolute = $abs; break; }
            }
            if ($found === null) return '';
            $src = rtrim(PlatformPathService::asset_relative_prefix(), '/') . '/' . ltrim($found, '/');
            $size = self::get_sizes($absolute);
            if ($size && !isset($data['width']) && !isset($data['height'])) {
                $width = ' width="' . $size[0] . '"';
                $height = ' height="' . $size[1] . '"';
            }
        }
        if (isset($data['width'])) {
            $width = ' width="' . htmlspecialchars((string)$data['width'], ENT_QUOTES, 'UTF-8') . '"';
        }
        if (isset($data['height'])) {
            $height = ' height="' . htmlspecialchars((string)$data['height'], ENT_QUOTES, 'UTF-8') . '"';
        }

        $lazy = !empty($data['lazy']) ? ' loading="lazy"' : '';

        $title = (string)($data['title'] ?? '');
        $titleAttr = $title !== '' ? ' title="' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '"' : '';

        $attrs = '';
        if (is_array($data['attributes'] ?? null)) {
            foreach ($data['attributes'] as $attrKey => $attrValue) {
                $key = preg_replace('/[^a-zA-Z0-9:_\-]/', '', (string) $attrKey);
                if ($key === '') continue;
                if ($attrValue === true || $attrValue === '') { $attrs .= ' ' . $key; continue; }
                $attrs .= ' ' . $key . '="' . htmlspecialchars((string) $attrValue, ENT_QUOTES, 'UTF-8') . '"';
            }
        }

        return PlatformTemplateRenderer::render([
            'class' => htmlspecialchars((string)($data['class'] ?? 'responsive-image')),
            'src' => htmlspecialchars($src),
            'alt' => htmlspecialchars((string)($data['alt'] ?? '')),
            'title_attr' => $titleAttr,
            'lazy' => $lazy,
            'width' => $width,
            'height' => $height,
            'attrs' => $attrs,
        ]);
    }
}




