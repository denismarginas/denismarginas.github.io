<?php

require_once __DIR__ . '/../../../utility_project_media/assets/php/utility_project_media.php';

class project_videos
{
    private const HTML_DIR = __DIR__ . '/../html/';

    public static function render(array $data = []): string
    {
        $post = is_array($data['post_current_data'] ?? null) ? $data['post_current_data'] : [];
        if (empty($post)) return '';

        $entries = self::entries(self::videos($data, $post), $post, ($data['details'] ?? true) !== false);
        if ($entries === []) return '';
        $single = count($entries) === 1 && ($data['single'] ?? 'playlist') === 'player';

        $format = (string) ($data['date_format'] ?? PlatformTextService::ui('date_format', 'M j, Y'));
        foreach ($entries as $i => $entry) $entries[$i]['meta'] = self::meta($entry['video'], $format);

        $items = '';
        $templates = '';
        foreach ($entries as $i => $entry) {
            $items .= self::item($entry, $i);
            $templates .= '<template data-video-player="' . $i . '">' . $entry['player'] . '</template>';
            if ($entry['details'] !== '') $templates .= '<template data-video-details="' . $i . '">' . $entry['details'] . '</template>';
        }

        $first = $entries[0];
        $playlist = PlatformTemplateRenderer::render(self::HTML_DIR . 'template.html', [
            'player' => $first['player'],
            'title' => self::e($first['video']['title']),
            'meta' => self::e($first['meta']),
            'details_text' => self::e(utility_project_media::text('videos_details', 'More details')),
            'details_icon' => (string) PlatformComponentRenderer::render('svg', ['icon' => 'video-details', 'class' => 'project-videos-details-icon']),
            'details_hidden' => $first['details'] !== '' ? '' : ' hidden',
            'class' => $single ? ' project-videos-single' : '',
            'list' => $single ? '' : '<ol class="project-videos-list" data-video-list>' . $items . '</ol>',
            'templates' => $templates,
        ]);

        return (string) PlatformComponentRenderer::render('section', [
            'id' => array_key_exists('id', $data) ? trim((string) $data['id']) : 'videos',
            'class' => 'project-section project-videos-section',
            'variant' => $data['variant'] ?? 'glow',
            'children' => self::head($data, count($entries)) . $playlist,
        ]);
    }

    private static function entries(array $videos, array $post, bool $details): array
    {
        $postColor = trim((string) ($post['settings']['appearance']['colors']['primary'] ?? ''));
        $entries = [];
        foreach ($videos as $video) {
            $player = (string) PlatformComponentRenderer::render('project_block_video', [
                'post_current_data' => $post,
                'src' => $video['src'],
                'video_primary_color' => $video['color'] ?: $postColor,
            ]);
            if (trim($player) === '') continue;
            $entries[] = [
                'video' => $video,
                'player' => $player,
                'details' => $details && $video['id'] !== '' ? self::details($video['id']) : '',
            ];
        }
        return $entries;
    }

    private static function item(array $entry, int $index): string
    {
        $video = $entry['video'];
        $thumb = $video['thumbnail'] !== ''
            ? (string) PlatformComponentRenderer::render('image', ['src' => $video['thumbnail'], 'alt' => '', 'lazy' => true])
            : '<span class="project-videos-thumb-empty">' . PlatformComponentRenderer::render('svg', ['icon' => 'play', 'class' => 'project-videos-thumb-play']) . '</span>';

        return trim(PlatformTemplateRenderer::render(self::HTML_DIR . 'parts/item.html', [
            'index' => (string) $index,
            'title' => self::e($video['title']),
            'year' => self::e($video['year']),
            'meta' => self::e($entry['meta'] ?? $video['year']),
            'has_details' => $entry['details'] !== '' ? 'true' : 'false',
            'current' => $index === 0 ? ' aria-current="true"' : '',
            'num' => str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
            'thumb' => $thumb,
            'year_html' => $video['year'] !== '' ? '<span class="text-label">' . self::e($video['year']) . '</span>' : '',
        ]));
    }

    private static function meta(array $video, string $format): string
    {
        $publish = (string) ($video['publish'] ?? '');
        $date = $publish !== '' ? (string) PlatformTextService::format_date($publish, $format) : '';
        return $date !== '' ? $date : (string) ($video['year'] ?? '');
    }

    private static function head(array $data, int $total): string
    {
        $vars = ['{total}' => str_pad((string) $total, 2, '0', STR_PAD_LEFT)];
        return (string) PlatformComponentRenderer::render('section_head', [
            'kicker' => strtr(self::opt($data, 'kicker', utility_project_media::text('videos_kicker', 'Video content')), $vars),
            'title' => strtr(self::opt($data, 'title', utility_project_media::text('videos_title', 'Video <em>editing</em>.')), $vars),
            'text' => strtr(self::opt($data, 'text', $total === 1
                ? utility_project_media::text('videos_text_single', 'Play it here; "More details" has the credits and the tools used.')
                : utility_project_media::text('videos_text', 'Pick a film from the playlist to load it in the player.')), $vars),
            'count' => strtr(self::opt($data, 'count', $total === 1
                ? utility_project_media::text('videos_count_single', '{total} video')
                : utility_project_media::text('videos_count', '{total} videos')), $vars),
        ]);
    }

    private static function videos(array $data, array $post): array
    {
        $list = [];
        if (is_array($data['videos'] ?? null) && $data['videos'] !== []) {
            foreach ($data['videos'] as $ref) {
                $ref = trim((string) $ref);
                if ($ref === '') continue;
                if (preg_match('/\.(webm|mp4|ogg|ogv|m4v|mov)$/i', $ref)) {
                    $src = str_starts_with($ref, 'src/') ? $ref : 'src/content/vid/' . ltrim($ref, '/');
                    if (is_file(utility_project_media::absolute($src))) $list[] = utility_project_media::video($src);
                } elseif (($video = utility_project_media::video_by_id($ref)) !== null) {
                    $list[] = $video;
                }
            }
        } else {
            $list = utility_project_media::videos($post);
        }

        $exclude = array_map(fn ($e) => strtolower(trim((string) $e, ' /')), is_array($data['exclude'] ?? null) ? $data['exclude'] : []);
        if ($exclude === []) return $list;
        return array_values(array_filter($list, function (array $v) use ($exclude): bool {
            $src = strtolower($v['src']);
            foreach ($exclude as $e) {
                if ($e !== '' && ($e === strtolower($v['id']) || $e === basename($src) || str_ends_with($src, '/' . $e) || str_contains($src, '/' . $e . '/'))) return false;
            }
            return true;
        }));
    }

    private static function details(string $id): string
    {
        $html = class_exists('video_archive') ? video_archive::popup($id) : '';
        return trim($html) !== '' ? '<div class="popup-panel">' . $html . '</div>' : '';
    }

    private static function opt(array $data, string $key, string $default): string
    {
        return array_key_exists($key, $data) ? trim((string) $data[$key]) : $default;
    }

    private static function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
