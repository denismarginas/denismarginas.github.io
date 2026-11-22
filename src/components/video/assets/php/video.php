<?php

class video
{
    public static function render(array $data = []): string
    {
        $src = (string) ($data['src'] ?? $data['video'] ?? $data['path'] ?? $data['media']['video']['video'] ?? '');
        if ($src === '') {
            $media = $data['media'] ?? [];
            if (is_array($media['video'] ?? null)) $src = (string) ($media['video']['video'] ?? '');
        }
        if ($src === '') return '';

        if (!PlatformUrlService::is_external_url($src)) {
            $candidates = [ltrim($src, '/')];
            if (!str_starts_with(ltrim($src, '/'), 'src/content/')) {
                $candidates[] = 'src/content/vid/' . ltrim($src, '/');
                $candidates[] = 'src/content/' . ltrim($src, '/');
            }
            $found = null;
            foreach ($candidates as $cand) {
                $abs = defined('ENGINE_PROJECT_ROOT') ? ENGINE_PROJECT_ROOT . '/' . $cand : $cand;
                if (file_exists($abs)) { $found = $cand; break; }
            }
            if ($found === null) return '';
            $src = rtrim(PlatformPathService::asset_relative_prefix(), '/') . '/' . ltrim($found, '/');
        }

        $thumb = trim((string) ($data['thumbnail_bg'] ?? ''));
        if ($thumb === '') {
            $thumb = trim((string) ($data['thumbnail'] ?? $data['video_thumbnail'] ?? $data['media']['video']['video_thumbnail'] ?? ''));
        }
        $thumbStyle = $thumb !== ''
            ? ' style="background-image: url(\'' . htmlspecialchars($thumb, ENT_QUOTES, 'UTF-8') . '\')"'
            : '';

        $color = (string) ($data['appearance']['colors']['primary'] ?? $data['video_primary_color'] ?? $data['primary'] ?? '#fff');
        if ($color === '') $color = '#fff';

        $thumbContent = (string) ($data['thumbnail_content'] ?? '');

        return PlatformTemplateRenderer::render([
            'src' => htmlspecialchars($src, ENT_QUOTES, 'UTF-8'),
            'thumbnail_style' => $thumbStyle,
            'thumbnail_content' => $thumbContent,
            'no_thumbnail_class' => $thumb === '' && trim($thumbContent) === '' ? ' no-thumbnail' : '',
            'style' => '--dm-color-primary:' . htmlspecialchars($color, ENT_QUOTES, 'UTF-8') . ';',
        ]);
    }
}