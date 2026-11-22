<?php

require_once __DIR__ . '/../../../utility_project_media/assets/php/utility_project_media.php';

class project_hero_stage_media
{
    private const HTML_DIR = __DIR__ . '/../html/';

    public static function render(array $data = []): string
    {
        $post = is_array($data['post_current_data'] ?? null) ? $data['post_current_data'] : [];
        $title = trim((string) ($post['data']['seo']['title'] ?? ''));

        $first = utility_project_media::galleries($post, 'media')[0]['images'] ?? [];
        $main = ($first[1] ?? $first[0] ?? '') ?: utility_project_media::thumbnail($post);
        $mainHtml = $main !== '' ? (string) PlatformComponentRenderer::render('image', ['src' => $main, 'alt' => $title]) : '';
        if (trim($mainHtml) === '') return '';

        return PlatformTemplateRenderer::render(self::HTML_DIR . 'template.html', [
            'href' => self::e(trim((string) ($data['href'] ?? '')) ?: '#gallery'),
            'label' => self::e(utility_project_media::text('hero_browse_gallery', 'Browse the gallery')),
            'bar_title' => self::e(trim((string) ($data['bar_title'] ?? '')) ?: $title),
            'main' => $mainHtml,
            'square' => self::square($post, $main, $title),
            'video' => self::video_card($post),
            'tag' => self::e(self::tag($post) ?: $title),
        ]);
    }

    private static function square(array $post, string $main, string $title): string
    {
        $square = self::pick(array_values(array_diff(utility_project_media::images($post, 'media'), [$main])), 0.85, 1.2);
        if ($square !== '') {
            $html = (string) PlatformComponentRenderer::render('image', ['src' => $square, 'alt' => $title, 'lazy' => true]);
        } else {
            $logo = utility_project_media::logo($post);
            $html = $logo['img'] !== ''
                ? (string) PlatformComponentRenderer::render('image', ['src' => $logo['img'], 'alt' => PlatformTextService::ui('logo_alt', 'Logo') . ' - ' . $title, 'class' => 'project-hero-square-logo'])
                : '';
        }
        return trim($html) !== '' ? '<span class="project-hero-square">' . $html . '</span>' : '';
    }

    private static function video_card(array $post): string
    {
        $video = utility_project_media::videos($post)[0] ?? null;
        if ($video === null) return '';

        $player = (string) PlatformComponentRenderer::render('project_block_video', [
            'post_current_data' => $post,
            'src' => $video['src'],
            'video_primary_color' => $video['color'] ?: trim((string) ($post['settings']['appearance']['colors']['primary'] ?? '')),
        ]);
        if (trim($player) === '') return '';

        $thumb = $video['thumbnail'] !== ''
            ? (string) PlatformComponentRenderer::render('image', ['src' => $video['thumbnail'], 'alt' => '', 'lazy' => true])
            : '';
        $trigger = trim(PlatformTemplateRenderer::render(self::HTML_DIR . 'parts/video_card.html', [
            'no_thumb' => $thumb === '' ? ' project-hero-video-no-thumb' : '',
            'label' => self::e(utility_project_media::text('play', 'Play') . ': ' . $video['title']),
            'thumb' => $thumb,
            'title' => self::e($video['title']),
        ]));

        return (string) PlatformComponentRenderer::render('popup', [
            'trigger' => $trigger,
            'content' => '<div class="project-hero-video-popup">' . $player . '</div>',
            'class' => 'project-hero-video-trigger',
        ]);
    }

    private static function tag(array $post): string
    {
        $tags = array_filter(array_map(fn ($t) => is_scalar($t) ? trim((string) $t) : '', (array) ($post['data']['taxonomy']['tag'] ?? [])));
        return implode(' · ', array_map(fn ($t) => utility_project_media::label($t), array_slice(array_values($tags), 0, 3)));
    }

    private static function pick(array $images, float $min, float $max): string
    {
        foreach ($images as $image) {
            $size = utility_project_media::size($image);
            if ($size === null) continue;
            $ratio = $size[0] / $size[1];
            if ($ratio >= $min && $ratio <= $max) return $image;
        }
        return '';
    }

    private static function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
