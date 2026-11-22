<?php

require_once __DIR__ . '/../../../card_post_project_visual_media/assets/php/parts/project_media_finder.php';
require_once __DIR__ . '/parts/project_video_finder.php';

class card_post_project_visual_devices_media
{
    private const HTML_DIR = __DIR__ . '/../html/';

    public static function render(array $data = []): string
    {
        $post = is_array($data['post_current_data'] ?? null) ? $data['post_current_data'] : [];
        if (empty($post)) {
            return '';
        }

        $postData = is_array($post['data'] ?? null) ? $post['data'] : [];
        $show = strtolower(trim((string) ($data['show'] ?? 'both')));
        if (!in_array($show, ['both', 'photo', 'video'], true)) $show = 'both';

        $color = htmlspecialchars((string) ($post['settings']['appearance']['colors']['primary'] ?? ''), ENT_QUOTES, 'UTF-8');
        $screenStyle = $color !== '' ? ' style="background-color: ' . $color . ';"' : '';
        $frames = self::frames();
        $lazy = !array_key_exists('lazy', $data) || (bool) $data['lazy'];

        $photoBlock = $show !== 'video' ? self::photo_block($postData, $frames['tablet'], $screenStyle, $lazy) : '';
        $videoBlock = $show !== 'photo' ? self::video_block($postData, $frames['tablet-landscape'], $screenStyle) : '';
        if ($photoBlock === '' && $videoBlock === '') {
            return '';
        }

        $asLink = !array_key_exists('link', $data) || (bool) $data['link'];
        $link = PlatformPathService::post_link((string) ($post['_id'] ?? '')) . '#visualmedia';

        return PlatformTemplateRenderer::render(self::HTML_DIR . 'template.html', [
            'tag' => $asLink ? 'a' : 'div',
            'href_attr' => $asLink ? ' href="' . htmlspecialchars($link, ENT_QUOTES, 'UTF-8') . '"' : '',
            'both_devices_check' => ($photoBlock !== '' && $videoBlock !== '') ? ' tablet-and-landscape-devices' : '',
            'color_style' => $color !== '' ? ' style="--primary-color-post: ' . $color . ';"' : '',
            'photo_block' => $photoBlock,
            'video_block' => $videoBlock,
        ]);
    }

    private static function photo_block(array $postData, string $frame, string $screenStyle, bool $lazy): string
    {
        $src = project_media_finder::photo($postData);
        $photo = $src !== '' ? self::image($src, 'Media preview', 'media-photo-image', $lazy) : '';
        if ($photo === '') {
            return '';
        }

        return PlatformTemplateRenderer::render(self::HTML_DIR . 'parts/tablet_block.html', [
            'screen_style' => $screenStyle,
            'photo' => $photo,
            'frame' => self::image($frame, 'Tablet frame', 'tablet', $lazy),
        ]);
    }

    private static function video_block(array $postData, string $frame, string $screenStyle): string
    {
        $videos = project_video_finder::videos($postData);
        if (empty($videos)) {
            return '';
        }

        $media = '';
        foreach ($videos as $src) {
            $thumbnail = block_video::item_thumbnail_path($src);
            if ($thumbnail !== '') {
                $media = self::image($thumbnail, 'Video preview', 'media-video-thumbnail', true);
                if ($media !== '') break;
            }
        }
        if ($media === '') {
            $url = rtrim(PlatformPathService::asset_relative_prefix(), '/') . '/' . ltrim($videos[0], '/');
            $media = PlatformTemplateRenderer::render(self::HTML_DIR . 'parts/video.html', [
                'video_src' => htmlspecialchars($url . '#t=0.1', ENT_QUOTES, 'UTF-8'),
            ]);
        }

        return PlatformTemplateRenderer::render(self::HTML_DIR . 'parts/tablet_landscape_block.html', [
            'screen_style' => $screenStyle,
            'media' => $media,
            'play_icon' => (string) PlatformComponentRenderer::render('svg', ['icon' => 'play', 'class' => 'media-play-icon']),
            'frame' => self::image($frame, 'Tablet landscape frame', 'tablet-landscape', false),
        ]);
    }

    private static function frames(): array
    {
        $config = PlatformDataService::get_data('content_post_projects');
        $devices = is_array($config['img']['devices'] ?? null) ? $config['img']['devices'] : [];

        return [
            'tablet' => (string) ($devices['tablet'] ?? ''),
            'tablet-landscape' => (string) ($devices['tablet-landscape'] ?? ''),
        ];
    }

    private static function image(string $src, string $alt, string $class, bool $lazy): string
    {
        return $src !== ''
            ? (string) PlatformComponentRenderer::render('image', ['src' => $src, 'alt' => $alt, 'class' => $class, 'lazy' => $lazy])
            : '';
    }
}
