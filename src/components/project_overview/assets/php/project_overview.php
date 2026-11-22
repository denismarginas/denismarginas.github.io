<?php

require_once __DIR__ . '/../../../utility_project_media/assets/php/utility_project_media.php';
require_once __DIR__ . '/../../../section_head/assets/php/section_head.php';

class project_overview
{
    public static function render(array $data = []): string
    {
        $post = is_array($data['post_current_data'] ?? null) ? $data['post_current_data'] : [];
        if (empty($post)) return '';
        $d = is_array($post['data'] ?? null) ? $post['data'] : [];

        $text = self::paragraphs(trim((string) ($data['text'] ?? '')) ?: trim((string) ($d['seo']['description'] ?? '')));
        $meta = (string) PlatformComponentRenderer::render('key_value_list', [
            'items' => is_array($data['meta'] ?? null) ? $data['meta'] : self::meta($d, is_array($data['meta_exclude'] ?? null) ? $data['meta_exclude'] : []),
            'columns' => 2,
            'class' => 'project-overview-meta',
        ]);
        $links = ($data['links'] ?? true) !== false
            ? (string) PlatformComponentRenderer::render('project_links', ['post_current_data' => $post, 'class' => 'project-overview-links'])
            : '';
        if ($text === '' && trim($meta) === '' && trim($links) === '') return '';

        $kicker = array_key_exists('kicker', $data) ? trim((string) $data['kicker']) : utility_project_media::text('overview_kicker', 'Overview');
        $title = section_head::text(array_key_exists('title', $data) ? (string) $data['title'] : utility_project_media::text('overview_title', 'The <em>project</em>.'));
        $subtitle = section_head::text(array_key_exists('subtitle', $data) ? (string) $data['subtitle'] : self::subtitle($d));
        $quote = section_head::text((string) ($data['quote'] ?? ''));

        $html = PlatformTemplateRenderer::render([
            'kicker' => $kicker !== '' ? '<span class="project-overview-kicker text-label">' . self::e($kicker) . '</span>' : '',
            'title' => $title !== '' ? '<h2 class="project-overview-title title-secondary title-divider">' . $title . '</h2>' : '',
            'subtitle' => $subtitle !== '' ? '<p class="project-overview-subtitle">' . $subtitle . '</p>' : '',
            'quote' => $quote !== '' ? '<blockquote class="project-overview-quote font-secondary">' . $quote . '</blockquote>' : '',
            'cover' => ($data['cover'] ?? true) !== false ? self::cover($post) : '',
            'text' => $text,
            'meta' => $meta,
            'links' => $links,
        ]);

        return (string) PlatformComponentRenderer::render('section', [
            'id' => array_key_exists('id', $data) ? trim((string) $data['id']) : 'overview',
            'class' => 'project-section project-overview-section',
            'variant' => $data['variant'] ?? '',
            'children' => $html,
        ]);
    }

    private static function subtitle(array $d): string
    {
        $types = array_map(
            fn ($t) => utility_project_media::type($t),
            array_filter(array_map(fn ($t) => is_scalar($t) ? trim((string) $t) : '', (array) ($d['project']['types'] ?? [])))
        );
        return implode(' · ', array_filter([implode(', ', array_slice($types, 0, 3)), self::timeline($d['date'] ?? [])]));
    }

    private static function cover(array $post): string
    {
        $title = (string) ($post['data']['seo']['title'] ?? '');
        $logo = utility_project_media::logo($post);
        $logoHtml = $logo['img'] !== ''
            ? (string) PlatformComponentRenderer::render('image', ['src' => $logo['img'], 'alt' => PlatformTextService::ui('logo_alt', 'Logo') . ' - ' . $title, 'lazy' => true])
            : ($logo['svg'] !== '' ? (string) PlatformComponentRenderer::render('svg', ['icon' => $logo['svg']]) : '');
        $thumb = utility_project_media::thumbnail($post);
        $thumbHtml = $thumb !== '' ? (string) PlatformComponentRenderer::render('image', ['src' => $thumb, 'alt' => $title, 'lazy' => true]) : '';

        $html = (trim($logoHtml) !== '' ? '<figure class="project-overview-tile project-overview-logo">' . $logoHtml . '</figure>' : '')
            . (trim($thumbHtml) !== '' ? '<figure class="project-overview-tile project-overview-thumb">' . $thumbHtml . '</figure>' : '');
        return $html !== '' ? '<div class="project-overview-cover">' . $html . '</div>' : '';
    }

    private static function meta(array $d, array $exclude): array
    {
        $rows = [
            'client' => [utility_project_media::text('meta_client', 'Client'), self::join($d['project']['companies'] ?? [])],
            'role' => [utility_project_media::text('meta_role', 'Collaboration'), self::join($d['project']['collaboration'] ?? [])],
            'school' => [utility_project_media::text('meta_school', 'School'), self::join($d['project']['school'] ?? [])],
            'platform' => [utility_project_media::text('meta_platform', 'Platform'), self::texts($d['web']['platform'] ?? [])],
            'technology' => [utility_project_media::text('meta_technology', 'Technology'), self::texts($d['web']['technology'] ?? [])],
            'modules' => [utility_project_media::text('meta_modules', 'Modules'), self::texts($d['web']['plugins'] ?? [])],
            'tools' => [utility_project_media::text('meta_tools', 'Tools'), self::texts($d['media']['platforms'] ?? [])],
            'timeline' => [utility_project_media::text('meta_timeline', 'Timeline'), self::timeline($d['date'] ?? [])],
        ];

        $items = [];
        foreach ($rows as $key => [$label, $value]) {
            if (!in_array($key, $exclude, true)) $items[] = ['label' => $label, 'value' => $value];
        }
        if (!in_array('employer', $exclude, true) && ($employer = self::employer($d)) !== null) $items[] = $employer;
        return $items;
    }

    private static function timeline(mixed $date): string
    {
        if (!is_array($date)) return '';
        $start = self::month((string) ($date['start'] ?? ''));
        $endRaw = trim((string) ($date['end'] ?? ''));
        $end = self::month($endRaw);
        if ($end === '' && $start !== '') {
            $dates = PlatformDataService::get_data('dates') ?? [];
            $end = (string) ($dates['current_date']['present'] ?? 'Present');
        }
        if ($start === '' || $start === $end) return $start ?: $end;
        return $start . ' — ' . $end;
    }

    private static function employer(array $d): ?array
    {
        $employer = trim((string) ($d['project']['employer'] ?? ''));
        if ($employer === '' || strtolower($employer) === 'unspecified') return null;

        if (strtolower($employer) === 'freelancer') {
            return ['label' => utility_project_media::text('meta_worked_as', 'Worked as'), 'value' => $employer];
        }

        $link = '';
        $needle = self::slug($employer);
        foreach ((PlatformDataService::get_data('collection_job') ?? []) as $job) {
            if (!is_array($job) || ($job['settings']['render'] ?? true) === false) continue;
            if (self::slug((string) ($job['data']['seo']['title'] ?? '')) !== $needle) continue;
            $link = PlatformPathService::post_link('employee-experience') . '#' . ((string) ($job['data']['seo']['slug'] ?? '') ?: $needle);
            break;
        }
        return ['label' => utility_project_media::text('meta_worked_at', 'Worked at'), 'value' => $employer, 'link' => $link];
    }

    private static function paragraphs(string $text): string
    {
        if ($text === '') return '';
        $parts = preg_split('/(?:\r?\n\s*\r?\n|(?:<br\s*\/?>\s*){2,})/i', $text) ?: [$text];
        $html = '';
        foreach ($parts as $part) {
            $part = section_head::text(str_replace(["\r\n", "\n"], '<br>', trim($part)));
            if ($part !== '') $html .= '<p class="project-overview-text">' . $part . '</p>';
        }
        return $html;
    }

    private static function month(string $value): string
    {
        $value = trim($value);
        if (!preg_match('/^\d{4}-\d{1,2}(-\d{1,2})?$/', $value)) return '';
        if (substr_count($value, '-') === 1) $value .= '-01';
        return PlatformTextService::format_date($value, 'M Y');
    }

    private static function join(mixed $value): string
    {
        if (is_string($value)) $value = [$value];
        if (!is_array($value)) return '';
        return implode(', ', array_filter(array_map(fn ($v) => is_scalar($v) ? trim((string) $v) : '', $value), 'strlen'));
    }

    private static function texts(mixed $items): string
    {
        if (!is_array($items)) return '';
        return implode(', ', array_filter(array_map(fn ($i) => is_array($i) ? trim((string) ($i['text'] ?? '')) : '', $items), 'strlen'));
    }

    private static function slug(string $text): string
    {
        return trim(preg_replace('/[^a-z0-9]+/', '-', strtolower(trim($text))) ?? '', '-');
    }

    private static function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
