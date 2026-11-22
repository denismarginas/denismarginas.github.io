<?php

class video_listing
{
    public static function render(array $data = []): string
    {
        $template = $data['template'] ?? [];
        $descCollapse = !in_array($data['description_collapse'] ?? true, [false, 'false', 0, '0'], true);
        $descMax = (int) ($data['description_max'] ?? 300);
        $comp = str_replace('-', '_', (string) ($template['component'] ?? 'video'));
        $filterTags = $data['filter']['taxonomy']['tag'] ?? null;
        $filterIds = $data['filter']['_id'] ?? null;

        $videos = PlatformDataService::get_collection('video') ?? [];
        $videos = array_values(array_filter($videos, fn($v) => ($v['settings']['render'] ?? true) !== false));

        if (is_array($filterTags) && !empty($filterTags)) {
            $videos = array_values(array_filter($videos, function ($v) use ($filterTags) {
                $tags = $v['taxonomy']['tag'] ?? $v['data']['taxonomy']['tag'] ?? [];
                return !empty(array_intersect($filterTags, (array) $tags));
            }));
        }

        if (is_array($filterIds) && !empty($filterIds)) {
            $byId = [];
            foreach ($videos as $v) $byId[(string) ($v['_id'] ?? '')] = $v;
            $videos = [];
            foreach ($filterIds as $id) {
                if (isset($byId[(string) $id])) $videos[] = $byId[(string) $id];
            }
        }

        $itemsHtml = '';
        foreach ($videos as $idx => $video) {
            $d = $video['data'] ?? [];
            $media = $d['media']['video'] ?? [];
            $videoSrc = $media['video'] ?? '';
            $thumb = self::thumbnail((string) ($media['video_thumbnail'] ?? ''));
            $appearance = $video['settings']['appearance'] ?? $d['appearance'] ?? [];
            $color = $appearance['colors']['primary'] ?? '#fff';

            $videoHtml = PlatformComponentRenderer::render($comp, [
                'src' => $videoSrc,
                'thumbnail' => $thumb,
                'appearance' => ['colors' => ['primary' => $color]],
            ]);

            $videoData = $media['video_data'] ?? [];
            $title = $videoData['title'] ?? $d['seo']['title'] ?? '';
            $desc = $videoData['description'] ?? '';
            $timeline = $videoData['timeline'] ?? null;
            $buttons = $d['media']['buttons'] ?? [];

            $dateHtml = ($data['show_date'] ?? true) !== false ? self::date((string) ($d['date']['publish'] ?? ''), (string) ($data['date_format'] ?? PlatformTextService::ui('date_format', 'M j, Y'))) : '';

            $textHtml = '<div class="video-text">';
            $textHtml .= PlatformComponentRenderer::render('text_block', ['elements' => [
                ['subtitle' => $title],
            ]]);
            $textHtml .= $dateHtml;
            if ($descCollapse) {
                $textHtml .= PlatformComponentRenderer::render('text_collapse', [
                    'text' => $desc,
                    'max_chars' => $descMax,
                    'class' => 'video-description',
                ]);
            } else {
                $textHtml .= PlatformComponentRenderer::render('text_block', ['elements' => [
                    ['paragraph' => $desc, 'class' => 'video-description', 'escape' => false],
                ]]);
            }
            if (is_array($timeline) && !empty($timeline['list'])) {
                $textHtml .= '<div class="video-timeline"><span>' . htmlspecialchars($timeline['text'] ?? '', ENT_QUOTES, 'UTF-8') . '</span><ul>';
                foreach ($timeline['list'] as $tl) $textHtml .= '<li>' . htmlspecialchars((string) $tl, ENT_QUOTES, 'UTF-8') . '</li>';
                $textHtml .= '</ul></div>';
            }
            if (!empty($buttons) && is_array($buttons)) {
                $buttonItems = array_map(fn($b) => [
                    'text' => $b['text'] ?? '',
                    'link' => $b['url'] ?? '',
                    'svg' => $b['svg'] ?? '',
                ], $buttons);
                $textHtml .= PlatformComponentRenderer::render('text_block', ['elements' => [
                    ['buttons' => $buttonItems, 'class' => 'content-wrapper'],
                ]]);
            }
            $textHtml .= '</div>';

            $isReverse = $idx % 2 === 1;
            $itemsHtml .= '<div class="video-listing-item' . ($isReverse ? ' reverse' : '') . '"><div class="video-wrapper">' . $videoHtml . '</div>' . $textHtml . '</div>';
        }

        return PlatformTemplateRenderer::render(['items' => $itemsHtml]);
    }

    private static function date(string $publish, string $format): string
    {
        $publish = trim($publish);
        if ($publish === '') return '';
        $time = strtotime($publish);
        $text = (string) PlatformTextService::format_date($publish, $format);
        if ($text === '') return '';
        return '<time class="video-date"' . ($time ? ' datetime="' . date('Y-m-d', $time) . '"' : '') . '>' . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . '</time>';
    }

    private static function thumbnail(string $path): string
    {
        $path = trim(str_replace('\\', '/', $path));
        if ($path === '' || PlatformUrlService::is_external_url($path)) return $path;

        $path = str_starts_with(ltrim($path, '/'), 'src/') ? ltrim($path, '/') : 'src/content/img/' . ltrim($path, '/');
        $root = defined('ENGINE_PROJECT_ROOT') ? rtrim(ENGINE_PROJECT_ROOT, '/\\') . '/' : '';
        if (!is_file($root . $path)) return '';

        return rtrim(PlatformPathService::asset_relative_prefix(), '/') . '/' . $path;
    }
}
