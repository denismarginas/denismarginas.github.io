<?php

class block_image
{
    private const PROJECTS_DIR = 'src/content/img/projects/';

    public static function render(array $data = []): string
    {
        $post = is_array($data['post_current_data'] ?? null) ? $data['post_current_data'] : [];

        $src = self::source($data, $post);
        if ($src === '') return '';

        $alt = trim((string) ($data['alt'] ?? ($post['data']['project']['title'] ?? $post['data']['seo']['title'] ?? '')));

        $image = (string) PlatformComponentRenderer::render('image', [
            'src' => $src,
            'alt' => $alt,
            'class' => 'block-image-img',
            'lazy' => true,
        ]);
        if (trim($image) === '') return '';

        $video = self::video($data, $src);

        $caption = trim((string) ($data['caption'] ?? ''));
        [$sizeClasses, $style] = self::sizes($data);
        if ($video !== '') $sizeClasses = trim($sizeClasses . ' block-image-has-video');
        $card = ($data['card'] ?? true) !== false;
        $popup = ($data['popup'] ?? true) !== false;
        $group = trim((string) ($data['popup_group'] ?? ''));
        $extra = trim((string) ($data['class'] ?? ''));
        $id = trim((string) ($data['id'] ?? ''));

        return PlatformTemplateRenderer::render([
            'card_class' => $card ? ' card-outline' : ' block-image-plain',
            'extra_class' => ($sizeClasses !== '' ? ' ' . $sizeClasses : '') . ($extra !== '' ? ' ' . htmlspecialchars($extra, ENT_QUOTES, 'UTF-8') : ''),
            'popup_attr' => $popup ? ' data-popup="true"' : '',
            'style_attr' => $style !== '' ? ' style="' . htmlspecialchars($style, ENT_QUOTES, 'UTF-8') . '"' : '',
            'id_attr' => $id !== '' ? ' id="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '"' : '',
            'group_attr' => $group !== '' ? ' data-popup-group="' . htmlspecialchars($group, ENT_QUOTES, 'UTF-8') . '"' : '',
            'image' => $image . $video,
            'caption' => $caption !== ''
                ? PlatformTemplateRenderer::render(__DIR__ . '/../html/parts/caption.html', [
                    'caption' => htmlspecialchars($caption, ENT_QUOTES, 'UTF-8', false),
                ])
                : '',
        ]);
    }

    private static function video(array $data, string $poster): string
    {
        $video = is_scalar($data['video'] ?? null) ? trim(str_replace('\\', '/', (string) $data['video']), '/') : '';
        if ($video === '' || str_contains($video, '..')) return '';
        $video = str_starts_with($video, 'src/') ? $video : 'src/content/vid/' . $video;
        if (!self::exists($video)) return '';

        return (string) PlatformComponentRenderer::render('video_background', [
            'src' => $video,
            'poster' => $poster,
            'overlay' => 0,
            'loop' => ($data['video_loop'] ?? true) !== false,
            'class' => 'block-image-video',
        ]);
    }

    private static function sizes(array $data): array
    {
        $classes = [];
        $vars = [];
        $map = [
            'width' => '--block-image-width',
            'height' => '--block-image-height',
            'height_lg' => '--block-image-height-lg',
            'height_md' => '--block-image-height-md',
            'height_sm' => '--block-image-height-sm',
            'height_xs' => '--block-image-height-xs',
        ];
        foreach ($map as $key => $var) {
            $value = is_scalar($data[$key] ?? null) ? strtolower(trim((string) $data[$key])) : '';
            if ($value === '' || !preg_match('/^(auto|\d+(\.\d+)?(px|%|rem|em|vh|vw))$/', $value)) continue;
            $vars[] = $var . ': ' . $value;
        }
        if ($vars) $classes[] = 'block-image-sized';
        if (($data['cover'] ?? false) === true) $classes[] = 'block-image-cover';
        if (is_scalar($data['height'] ?? null) && trim((string) $data['height']) === '100%') $classes[] = 'block-image-fill';

        return [implode(' ', $classes), $vars ? implode('; ', $vars) . ';' : ''];
    }

    private static function source(array $data, array $post): string
    {
        $img = is_scalar($data['img'] ?? null) ? trim(str_replace('\\', '/', (string) $data['img']), '/') : '';
        if ($img !== '' && !str_contains($img, '..')) {
            $candidate = str_starts_with($img, 'src/') ? $img : 'src/content/img/' . $img;
            return self::exists($candidate) ? $candidate : '';
        }

        $dir = trim(str_replace('\\', '/', (string) ($data['image_path'] ?? '')), '/');
        $file = trim(str_replace('\\', '/', (string) ($data['image_file_name'] ?? '')), '/');
        $relative = trim($dir . '/' . $file, '/');
        if ($file === '' || str_contains($relative, '..')) return '';

        if (str_starts_with($relative, 'src/')) {
            return self::exists($relative) ? $relative : '';
        }

        $media = trim(str_replace('\\', '/', (string) ($post['data']['media']['path'] ?? '')), '/');
        $candidates = [];
        if ($media !== '') $candidates[] = self::PROJECTS_DIR . $media . '/' . $relative;
        $candidates[] = self::PROJECTS_DIR . $relative;

        foreach ($candidates as $candidate) {
            if (self::exists($candidate)) return $candidate;
        }
        return '';
    }

    private static function exists(string $path): bool
    {
        $root = defined('ENGINE_PROJECT_ROOT') ? rtrim(ENGINE_PROJECT_ROOT, '/\\') . '/' : '';
        return is_file($root . $path);
    }
}
