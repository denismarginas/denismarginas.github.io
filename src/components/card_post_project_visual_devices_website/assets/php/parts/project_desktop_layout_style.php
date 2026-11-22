<?php

class project_desktop_layout_style
{
    public static function resolve(string $type, string $screenPath): array
    {
        $position = 'top';
        $bg = '';
        $aspectRatio = null;

        $size = self::image_size($screenPath);
        if ($size !== null) {
            [$width, $height] = $size;
            $aspectRatio = $width / $height;

            if ($type === 'phone' && $width >= $height) {
                $position = 'center';
            } elseif ($type === 'desktop' && ($height > $width || ($aspectRatio >= 1 && $aspectRatio <= 1.8))) {
                $position = 'top';
            } else {
                $position = $aspectRatio >= 0.7 ? 'center' : 'top';
            }

            $bg = $aspectRatio <= 0.7 ? 'bg-primary fade-under' : 'bg-primary';
        }

        return [
            'position' => $position,
            'bg' => $bg,
            'aspect_ratio' => $aspectRatio !== null ? round($aspectRatio, 2) : null,
            'classes' => trim($position . ' ' . $bg),
        ];
    }

    private static function image_size(string $screenPath): ?array
    {
        if ($screenPath === '') {
            return null;
        }

        $raw = ltrim($screenPath, '/');
        $absolute = defined('ENGINE_PROJECT_ROOT') ? ENGINE_PROJECT_ROOT . '/' . $raw : $raw;
        if (!file_exists($absolute)) {
            return null;
        }

        $info = @getimagesize($absolute);
        return ($info !== false && $info[1] > 0) ? [$info[0], $info[1]] : null;
    }
}
