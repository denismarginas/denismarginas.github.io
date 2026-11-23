<?php

class post_short_info_content_helpers
{
    private const DEFAULT_LABELS = [
        'web_platform' => 'Platform: ',
        'web_technology' => 'Technology: ',
        'web_plugins' => 'Modules: ',
        'project_collaboration' => 'Project Collaboration: ',
        'project_school' => 'School: ',
        'web_project_status' => 'Web Project Status:',
        'web_project_status_done' => 'Done',
        'web_project_status_undone' => 'Undone',
        'web_project_status_in_progress' => 'In progress',
        'media_platforms' => 'Media Platforms: ',
        'employer_type' => 'Worked as:',
        'employer_location' => 'Worked at:',
        'date' => 'Date:',
        'date_present' => 'Present',
    ];

    private static ?array $labels = null;

    protected static function label(string $key): string
    {
        if (self::$labels === null) {
            $content = PlatformDataService::get_data('content_post_projects') ?? [];
            $fields = is_array($content['fields'] ?? null) ? $content['fields'] : [];
            self::$labels = array_merge(self::DEFAULT_LABELS, array_filter($fields, 'is_string'));
        }
        return (string) (self::$labels[$key] ?? '');
    }

    protected static function esc(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }

    protected static function esc_text(string $text): string
    {
        $parts = preg_split('/<br\s*\/?>/i', $text);
        return implode('<br>', array_map(fn($p) => self::esc($p), $parts));
    }

    protected static function svg(string $icon, string $class = ''): string
    {
        if ($icon === '') return '';
        return (string) PlatformComponentRenderer::render('svg', ['icon' => $icon, 'class' => $class !== '' ? $class : 'svg-icon']);
    }

    protected static function strings(mixed $value): array
    {
        if (is_string($value)) $value = [$value];
        if (!is_array($value)) return [];
        return array_values(array_filter(array_map(fn($v) => is_scalar($v) ? trim((string) $v) : '', $value), fn($v) => $v !== ''));
    }

    protected static function items(mixed $value): array
    {
        if (!is_array($value)) return [];
        return array_values(array_filter($value, fn($i) => is_array($i) && (trim((string) ($i['text'] ?? '')) !== '' || trim((string) ($i['svg'] ?? '')) !== '')));
    }

    protected static function field(string $class, string $label, string $valueHtml): string
    {
        if (trim($valueHtml) === '') return '';
        $labelHtml = $label !== '' ? '<span class="post-short-info-field-label">' . self::esc($label) . '</span> ' : '';
        return '<p class="' . trim('post-short-info-field ' . $class) . '">' . $labelHtml . $valueHtml . '</p>';
    }

    protected static function pills(string $class, array $items): string
    {
        if (empty($items)) return '';
        $html = '';
        foreach ($items as [$text, $link]) {
            $html .= $link !== ''
                ? '<a class="post-short-info-pill" href="' . self::esc($link) . '">' . self::esc($text) . '</a>'
                : '<span class="post-short-info-pill">' . self::esc($text) . '</span>';
        }
        return '<div class="post-short-info-pills ' . $class . '">' . $html . '</div>';
    }

    protected static function taxonomy_term(string $taxonomy, string $value): array
    {
        $needle = self::slugify($value);
        foreach ((PlatformDataService::get_data('taxonomy_' . $taxonomy) ?? []) as $term) {
            if (!is_array($term)) continue;
            $termData = is_array($term['data'] ?? null) ? $term['data'] : $term;
            if (($termData['settings']['render'] ?? true) === false) continue;

            $candidates = [
                (string) ($term['_id'] ?? ''),
                (string) ($termData['seo']['slug'] ?? ''),
                (string) ($termData['seo']['title'] ?? ''),
            ];
            foreach ($candidates as $candidate) {
                if ($candidate !== '' && self::slugify($candidate) === $needle) {
                    $title = trim((string) ($termData['seo']['title'] ?? $termData['name'] ?? $term['name'] ?? ''));
                    return [$title !== '' ? $title : $value, PlatformPathService::post_link((string) ($term['_id'] ?? ''))];
                }
            }
        }
        return [$value, ''];
    }

    protected static function slugify(string $text): string
    {
        $text = strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? $text;
        return trim($text, '-');
    }
}
