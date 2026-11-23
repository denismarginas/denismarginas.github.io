<?php

require_once __DIR__ . '/../../../utility_project_media/assets/php/utility_project_media.php';

class project_hero
{
    private const HTML_DIR = __DIR__ . '/../html/';
    private const VARIANTS = ['website', 'media', 'simple'];

    public static function render(array $data = []): string
    {
        $post = is_array($data['post_current_data'] ?? null) ? $data['post_current_data'] : [];
        if (empty($post)) return '';
        $d = is_array($post['data'] ?? null) ? $post['data'] : [];

        $title = self::str($data['title'] ?? '') ?: self::str($d['seo']['title'] ?? '');
        if ($title === '') return '';

        $targets = array_merge(['screens' => 'screens', 'gallery' => 'gallery', 'overview' => 'overview'], is_array($data['targets'] ?? null) ? $data['targets'] : []);
        $variant = self::variant($data, $post);

        $lead = self::str($data['lead'] ?? '') ?: self::str($d['seo']['description'] ?? '');
        $id = array_key_exists('id', $data) ? self::str($data['id']) : 'top';

        return PlatformTemplateRenderer::render([
            'variant' => $variant,
            'id_attr' => $id !== '' ? ' id="' . self::e($id) . '"' : '',
            'eyebrow' => self::eyebrow($data, $d),
            'title' => self::title($title),
            'lead' => $lead !== '' ? '<p class="project-hero-lead">' . self::e(strip_tags($lead)) . '</p>' : '',
            'actions' => self::actions($data, $d, $variant, $targets),
            'meta' => $variant === 'media' ? self::stats($post) : self::meta($data, $d),
            'stage' => self::stage($variant, $post, $title, $targets),
        ]);
    }

    private static function variant(array $data, array $post): string
    {
        $variant = strtolower(self::str($data['variant'] ?? 'auto'));
        if (in_array($variant, self::VARIANTS, true)) return $variant;
        if (utility_project_media::screens($post, 'desktop') !== []) return 'website';
        if (utility_project_media::images($post, 'media') !== [] || utility_project_media::videos($post) !== []) return 'media';
        return 'simple';
    }

    private static function eyebrow(array $data, array $d): string
    {
        $parts = [];
        if (($custom = self::str($data['eyebrow'] ?? '')) !== '') {
            $parts[] = $custom;
        } else {
            $type = self::strings($d['project']['types'] ?? [])[0] ?? '';
            if ($type !== '') $parts[] = utility_project_media::type($type);
            $date = self::date(self::str($d['date']['end'] ?? '')) ?: self::date(self::str($d['date']['start'] ?? ''));
            if ($date !== '') $parts[] = $date;
        }

        $html = '<span class="project-hero-dot" aria-hidden="true"></span>';
        foreach ($parts as $part) $html .= '<span>' . self::e($part) . '</span>';
        return $html;
    }

    private static function title(string $title): string
    {
        $words = preg_split('/\s+/u', trim($title)) ?: [];
        if (count($words) === 1) return '<span class="project-hero-title-main"><em>' . self::e($words[0]) . '</em></span>';

        $first = array_shift($words);
        $last = array_pop($words);
        $rest = ($words !== [] ? self::e(implode(' ', $words)) . ' ' : '') . '<em>' . self::e((string) $last) . '</em>';
        return '<span class="project-hero-title-main">' . self::e($first) . '</span> <span class="project-hero-title-rest">' . $rest . '</span>';
    }

    private static function actions(array $data, array $d, string $variant, array $targets): string
    {
        $actions = is_array($data['actions'] ?? null) ? $data['actions'] : null;
        if ($actions === null) {
            $actions = [];
            if ($variant === 'website') {
                $actions[] = ['text' => utility_project_media::text('hero_see_screens', 'View the showcase'), 'link' => '#' . $targets['screens'], 'style' => 'accent', 'svg' => 'showcase-screens'];
            } elseif ($variant === 'media') {
                $actions[] = ['text' => utility_project_media::text('hero_browse_gallery', 'Browse the gallery'), 'link' => '#' . $targets['gallery'], 'style' => 'accent', 'svg' => 'showcase-gallery'];
            } else {
                $actions[] = ['text' => utility_project_media::text('hero_details', 'Project details'), 'link' => '#' . $targets['overview'], 'style' => 'accent', 'svg' => 'project-details'];
            }
            $url = self::str(($d['web']['urls'][0]['url'] ?? '') ?: '');
            if ($url !== '') {
                $actions[] = [
                    'text' => utility_project_media::text('hero_visit_website', 'Visit website'),
                    'link' => (string) PlatformComponentRenderer::value('utility_string_urlto', ['input' => $url, 'action' => 'transform']),
                    'style' => 'ghost',
                    'svg' => 'new-tab',
                    'target' => '_blank',
                ];
            }
        }

        $html = '';
        foreach ($actions as $action) {
            if (!is_array($action)) continue;
            $style = self::str($action['style'] ?? 'accent') === 'ghost' ? 'btn-ghost' : 'btn-accent';
            $target = self::str($action['target'] ?? '');
            $html .= (string) PlatformComponentRenderer::render('button', [
                'text' => self::str($action['text'] ?? ''),
                'link' => $action['link'] ?? '',
                'svg' => self::str($action['svg'] ?? ''),
                'class' => 'btn ' . $style . ' project-hero-btn',
                'target' => $target,
                'rel' => $target === '_blank' ? 'noopener' : '',
            ]);
        }
        return $html !== '' ? '<div class="project-hero-actions">' . $html . '</div>' : '';
    }

    private static function meta(array $data, array $d): string
    {
        $items = is_array($data['meta'] ?? null) ? $data['meta'] : null;
        if ($items === null) {
            $platform = $d['web']['platform'][0]['text'] ?? '';
            $type = self::strings($d['project']['types'] ?? [])[0] ?? '';
            $status = '';
            if (array_key_exists('delivery_status', $d['web'] ?? [])) {
                $raw = $d['web']['delivery_status'];
                if (is_string($raw) && in_array(strtolower(str_replace([' ', '-'], '_', trim($raw))), ['in_progress', 'progress'], true)) {
                    $status = utility_project_media::text('status_in_progress', 'In progress');
                } elseif (filter_var($raw, FILTER_VALIDATE_BOOLEAN)) {
                    $status = utility_project_media::text('status_done', 'Delivered');
                } else {
                    $status = utility_project_media::text('status_undone', 'Undone');
                }
            }
            $items = [
                ['label' => utility_project_media::text('meta_platform', 'Platform'), 'value' => self::str($platform)],
                ['label' => utility_project_media::text('meta_scope', 'Scope'), 'value' => $type !== '' ? utility_project_media::type($type) : ''],
                ['label' => utility_project_media::text('meta_status', 'Status'), 'value' => $status],
            ];
        }
        return (string) PlatformComponentRenderer::render('key_value_list', [
            'items' => $items,
            'variant' => 'inline',
            'class' => 'project-hero-meta',
        ]);
    }

    private static function stats(array $post): string
    {
        $galleries = utility_project_media::galleries($post, 'media');
        $photos = array_sum(array_map(fn ($g) => count($g['images']), $galleries));
        $videos = count(utility_project_media::videos($post));

        $stats = [
            [$photos, utility_project_media::text('stat_photos', 'Photos')],
            [$videos, utility_project_media::text('stat_videos', 'Videos')],
            [count($galleries), utility_project_media::text('stat_galleries', 'Galleries')],
        ];
        $html = '';
        foreach ($stats as [$count, $label]) {
            if ($count < 1) continue;
            $html .= '<div class="project-hero-stat"><b>' . str_pad((string) $count, 2, '0', STR_PAD_LEFT) . '</b><span>' . self::e($label) . '</span></div>';
        }
        return $html !== '' ? '<div class="project-hero-stats">' . $html . '</div>' : '';
    }

    private static function stage(string $variant, array $post, string $title, array $targets): string
    {
        $stage = match ($variant) {
            'website' => (string) PlatformComponentRenderer::render('project_hero_stage_website', [
                'post_current_data' => $post,
                'href' => '#' . $targets['screens'],
                'bar_title' => self::short($title),
            ]),
            'media' => (string) PlatformComponentRenderer::render('project_hero_stage_media', [
                'post_current_data' => $post,
                'href' => '#' . $targets['gallery'],
                'bar_title' => self::short($title),
            ]),
            default => '',
        };
        return trim($stage) !== '' ? $stage : self::stage_simple($post, $title);
    }

    private static function stage_simple(array $post, string $title): string
    {
        $thumb = utility_project_media::thumbnail($post);
        if ($thumb === '') return '';
        $img = (string) PlatformComponentRenderer::render('image', ['src' => $thumb, 'alt' => $title]);
        return trim($img) !== '' ? '<div class="project-hero-stage project-hero-stage-simple"><span class="project-hero-thumb">' . $img . '</span></div>' : '';
    }

    private static function short(string $title, int $max = 45): string
    {
        return mb_strlen($title) > $max ? rtrim(mb_substr($title, 0, $max - 1)) . '…' : $title;
    }

    private static function date(string $value): string
    {
        if (!preg_match('/^\d{4}-\d{1,2}(-\d{1,2})?$/', $value)) return '';
        if (substr_count($value, '-') === 1) $value .= '-01';
        return PlatformTextService::format_date($value, 'M Y');
    }

    private static function strings(mixed $value): array
    {
        if (is_string($value)) $value = [$value];
        if (!is_array($value)) return [];
        return array_values(array_filter(array_map(fn ($v) => is_scalar($v) ? trim((string) $v) : '', $value), 'strlen'));
    }

    private static function str(mixed $value): string
    {
        return is_scalar($value) ? trim((string) $value) : '';
    }

    private static function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
