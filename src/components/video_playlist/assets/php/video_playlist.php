<?php

require_once __DIR__ . '/../../../utility_project_media/assets/php/utility_project_media.php';

class video_playlist
{
    public static function render(array $data = []): string
    {
        $post = is_array($data['post_current_data'] ?? null) ? $data['post_current_data'] : [];
        if (empty($post)) return '';

        $videos = self::videos($data, $post);
        if ($videos === []) return '';

        $postColor = trim((string) ($post['settings']['appearance']['colors']['primary'] ?? ''));
        $detailsOn = ($data['details'] ?? true) !== false;

        $items = '';
        $templates = '';
        $first = '';
        $firstVideo = null;
        foreach ($videos as $i => $video) {
            $player = (string) PlatformComponentRenderer::render('project_block_video', [
                'post_current_data' => $post,
                'src' => $video['src'],
                'video_primary_color' => $video['color'] ?: $postColor,
            ]);
            if (trim($player) === '') continue;
            $index = substr_count($templates, 'data-video-player=');

            $details = $detailsOn && $video['id'] !== '' ? self::details($video['id']) : '';
            if ($index === 0) {
                $first = $player;
                $firstVideo = $video;
            }
            $templates .= '<template data-video-player="' . $index . '">' . $player . '</template>';
            if ($details !== '') $templates .= '<template data-video-details="' . $index . '">' . $details . '</template>';

            $thumb = $video['thumbnail'] !== ''
                ? (string) PlatformComponentRenderer::render('image', ['src' => $video['thumbnail'], 'alt' => '', 'lazy' => true])
                : '<span class="video-playlist-thumb-empty">' . PlatformComponentRenderer::render('svg', ['icon' => 'play', 'class' => 'video-playlist-thumb-play']) . '</span>';

            $items .= '<li><button type="button" class="video-playlist-item" data-video-index="' . $index . '"'
                . ' data-title="' . self::e($video['title']) . '" data-meta="' . self::e($video['year']) . '"'
                . ' data-has-details="' . ($details !== '' ? 'true' : 'false') . '"'
                . ($index === 0 ? ' aria-current="true"' : '') . '>'
                . '<span class="video-playlist-num text-label">' . str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) . '</span>'
                . '<span class="video-playlist-thumb">' . $thumb . '</span>'
                . '<span class="video-playlist-item-text"><b>' . self::e($video['title']) . '</b>'
                . ($video['year'] !== '' ? '<span class="text-label">' . self::e($video['year']) . '</span>' : '') . '</span>'
                . '</button></li>';
        }
        if ($items === '' || $firstVideo === null) return '';

        $total = substr_count($templates, 'data-video-player=');
        $firstHasDetails = str_contains($templates, 'data-video-details="0"');

        $playlist = PlatformTemplateRenderer::render([
            'player' => $first,
            'title' => self::e($firstVideo['title']),
            'meta' => self::e($firstVideo['year']),
            'details_text' => self::e(utility_project_media::text('videos_details', 'More details')),
            'details_icon' => (string) PlatformComponentRenderer::render('svg', ['icon' => 'info', 'class' => 'video-playlist-details-icon']),
            'details_hidden' => $firstHasDetails ? '' : ' hidden',
            'items' => $items,
            'templates' => $templates,
        ]);

        $vars = ['{total}' => str_pad((string) $total, 2, '0', STR_PAD_LEFT)];
        $head = (string) PlatformComponentRenderer::render('section_head', [
            'kicker' => strtr(self::opt($data, 'kicker', utility_project_media::text('videos_kicker', 'Video content')), $vars),
            'title' => strtr(self::opt($data, 'title', utility_project_media::text('videos_title', 'The <em>films</em>.')), $vars),
            'text' => strtr(self::opt($data, 'text', utility_project_media::text('videos_text', 'Pick a film from the playlist to load it in the player.')), $vars),
            'count' => strtr(self::opt($data, 'count', utility_project_media::text('videos_count', '{total} videos')), $vars),
        ]);

        return (string) PlatformComponentRenderer::render('section', [
            'id' => array_key_exists('id', $data) ? trim((string) $data['id']) : 'videos',
            'class' => 'project-section video-playlist-section',
            'variant' => $data['variant'] ?? 'glow',
            'children' => $head . $playlist,
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
        $html = (string) PlatformComponentRenderer::render('video_listing', ['filter' => ['_id' => [$id]]]);
        return trim($html) !== '' ? '<div class="popup-panel video-playlist-details-panel">' . $html . '</div>' : '';
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
