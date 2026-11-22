<?php

class video_background
{
    public static function render(array $data = []): string
    {
        $item = self::item((string) ($data['video_id'] ?? ''));
        $video = $item['data']['media']['video'] ?? [];

        $src = trim((string) ($data['src'] ?? $video['video'] ?? ''));
        $src = self::asset($src, ['src/content/vid/', 'src/content/']);
        if ($src === '') return '';

        $poster = trim((string) ($data['poster'] ?? $video['video_thumbnail'] ?? ''));
        $poster = $poster !== '' ? self::asset($poster, ['src/content/img/', 'src/content/']) : '';

        $start = max(0, (float) ($data['start'] ?? 0));
        $endRaw = $data['end'] ?? 'auto';
        if (is_numeric($data['duration'] ?? null) && (float) $data['duration'] > 0) $endRaw = $start + (float) $data['duration'];
        $end = is_numeric($endRaw) && (float) $endRaw > $start ? (float) $endRaw : null;
        $loop = ($data['loop'] ?? true) !== false;
        $audio = ($data['audio'] ?? false) === true;
        $overlay = is_numeric($data['overlay'] ?? null) ? max(0, min(1, (float) $data['overlay'])) : 0.55;
        $position = preg_replace('/[^a-z0-9% .-]/i', '', (string) ($data['position'] ?? 'center')) ?: 'center';

        $attrs = ' data-start="' . self::num($start) . '"'
            . ($end !== null ? ' data-end="' . self::num($end) . '"' : '')
            . ' data-loop="' . ($loop ? 'true' : 'false') . '"'
            . ' data-audio="' . ($audio ? 'true' : 'false') . '"'
            . ' style="--video-bg-overlay: ' . self::num($overlay) . '; --video-bg-position: ' . self::e($position) . ';'
            . ($poster !== '' ? ' --video-bg-poster: url(\'' . self::e($poster) . '\');' : '') . '"';

        $class = trim((string) ($data['class'] ?? ''));
        $id = trim((string) ($data['id'] ?? ''));

        $soundButton = '';
        if ($audio) {
            $soundButton = '<button class="video-background-sound" type="button" data-video-background-sound aria-pressed="false" aria-label="Turn sound on" title="Sound">'
                . '<svg class="video-background-sound-off" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 9v6h4l5 4V5L8 9H4z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M17 9l4 6M21 9l-4 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>'
                . '<svg class="video-background-sound-on" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 9v6h4l5 4V5L8 9H4z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M16.5 8.5a5 5 0 0 1 0 7M19 6a8.5 8.5 0 0 1 0 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>'
                . '</button>';
        }

        return PlatformTemplateRenderer::render(__DIR__ . '/../html/template.html', [
            'extra_class' => $class !== '' ? ' ' . self::e($class) : '',
            'id_attr' => $id !== '' ? ' id="' . self::e($id) . '"' : '',
            'attrs' => $attrs,
            'poster_attr' => $poster !== '' ? ' poster="' . self::e($poster) . '"' : '',
            'src' => self::e($src),
            'type' => str_ends_with(strtolower(parse_url($src, PHP_URL_PATH) ?? ''), '.mp4') ? 'video/mp4' : 'video/webm',
            'overlay' => $overlay > 0 ? '<span class="video-background-overlay"></span>' : '',
            'sound_button' => $soundButton,
        ]);
    }

    private static function item(string $id): array
    {
        $id = trim($id);
        if ($id === '') return [];
        foreach (PlatformDataService::get_collection('video') ?? [] as $item) {
            if (is_array($item) && ($item['_id'] ?? '') === $id) return $item;
        }
        return [];
    }

    private static function asset(string $path, array $bases): string
    {
        if ($path === '') return '';
        if (PlatformUrlService::is_external_url($path)) return $path;
        $raw = ltrim($path, '/');
        $candidates = [$raw];
        if (!str_starts_with($raw, 'src/')) {
            foreach ($bases as $base) $candidates[] = $base . $raw;
        }
        foreach ($candidates as $cand) {
            $abs = defined('ENGINE_PROJECT_ROOT') ? ENGINE_PROJECT_ROOT . '/' . $cand : $cand;
            if (file_exists($abs)) {
                return rtrim(PlatformPathService::asset_relative_prefix(), '/') . '/' . ltrim($cand, '/');
            }
        }
        return '';
    }

    private static function num(float $n): string
    {
        return rtrim(rtrim(number_format($n, 3, '.', ''), '0'), '.') ?: '0';
    }

    private static function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
