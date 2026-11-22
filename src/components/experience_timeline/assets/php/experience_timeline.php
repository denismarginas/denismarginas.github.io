<?php

class experience_timeline
{
    private const LABELS = [
        'job' => 'Work',
        'education' => 'Education',
        'details' => 'Details',
    ];

    private const LINKS = [
        'job' => 'employee-experience',
        'education' => 'education-experience',
    ];

    public static function render(array $data = []): string
    {
        $defaults = [];
        foreach (self::LABELS as $key => $label) $defaults[$key] = PlatformTextService::ui('timeline_' . $key, $label);
        $labels = array_merge($defaults, is_array($data['labels'] ?? null) ? $data['labels'] : []);
        $links = ($data['links'] ?? null) === false
            ? []
            : array_merge(self::LINKS, is_array($data['links'] ?? null) ? $data['links'] : []);

        $entries = self::entries($data['types'] ?? ['job', 'education'], $data);
        if (empty($entries)) return '';

        $items = '';
        $prevYear = null;
        foreach ($entries as $i => $entry) {
            $items .= self::item($entry, $i, $prevYear, $labels, $links, is_array($data['tag_labels'] ?? null) ? $data['tag_labels'] : []);
            $prevYear = $entry['start']->format('Y');
        }

        $class = self::str($data['class'] ?? '');
        $id = self::str($data['id'] ?? '');

        return PlatformTemplateRenderer::render(__DIR__ . '/../html/template.html', [
            'extra_class' => $class !== '' ? ' ' . self::e($class) : '',
            'id_attr' => $id !== '' ? ' id="' . self::e($id) . '"' : '',
            'items' => $items,
        ]);
    }

    private static function entries(mixed $types, array $data = []): array
    {
        $now = new DateTime('today');
        $titles = is_array($data['titles'] ?? null) ? $data['titles'] : [];
        $maxTags = max(0, (int) ($data['max_tags'] ?? 6));
        $entries = [];

        foreach ((array) $types as $type) {
            $type = self::str($type);
            if ($type === '') continue;
            $items = PlatformDataService::get_collection($type) ?? [];
            if (!is_array($items)) continue;

            if (!empty($data['filter_by']) || !empty($data['exclude_by'])) {
                $filtered = PlatformComponentRenderer::value('utility_filter', [
                    'items' => $items,
                    'filter_by' => is_array($data['filter_by'] ?? null) ? $data['filter_by'] : [],
                    'exclude_by' => $data['exclude_by'] ?? [],
                ]);
                $items = is_array($filtered) ? $filtered : $items;
            }

            foreach ($items as $item) {
                if (!is_array($item) || ($item['settings']['render'] ?? true) === false) continue;
                $d = $item['data'] ?? [];
                $start = self::date($d['date']['start'] ?? '');
                if (!$start) continue;

                $endRaw = strtolower(self::str($d['date']['end'] ?? ''));
                $current = $endRaw === '' || $endRaw === 'present';
                $end = $current ? $now : (self::date($endRaw) ?: $now);
                if ($end > $now) $current = true;

                $kind = ($item['settings']['collection_type'] ?? $type) === 'education' || $type === 'education' ? 'education' : 'job';

                if ($kind === 'education') {
                    $edu = $d['education'] ?? [];
                    $role = implode(' · ', array_filter(array_map([self::class, 'str'], (array) ($edu['profession'] ?? []))));
                    $meta = array_filter([self::str($edu['type'] ?? '')]);
                } else {
                    $exp = $d['experience'] ?? [];
                    $role = implode(' · ', array_filter(array_map([self::class, 'str'], (array) ($exp['functions'] ?? []))));
                    $meta = array_filter(array_map([self::class, 'str'], (array) ($exp['location_types'] ?? [])));
                }

                $anchor = self::str($d['seo']['slug'] ?? '') ?: self::str($item['_id'] ?? '');

                $entries[] = [
                    'kind' => $kind,
                    'anchor' => $anchor,
                    'title' => self::str($titles[$item['_id'] ?? ''] ?? '') ?: self::str($d['seo']['title'] ?? $item['_id'] ?? ''),
                    'role' => $role,
                    'meta' => array_values($meta),
                    'tags' => array_slice(array_values(array_filter(array_map([self::class, 'str'], (array) ($d['taxonomy']['tag'] ?? [])))), 0, $maxTags),
                    'logo' => is_array($d['media']['logo'] ?? null) ? $d['media']['logo'] : [],
                    'color' => self::brand_color($item['settings']['appearance']['colors'] ?? []),
                    'start' => $start,
                    'end' => $end,
                    'current' => $current,
                ];
            }
        }

        usort($entries, function ($a, $b) {
            return [$b['start'], $b['end']] <=> [$a['start'], $a['end']];
        });

        return $entries;
    }

    private static function item(array $entry, int $index, ?string $prevYear, array $labels, array $links, array $tagLabels = []): string
    {
        $year = $entry['start']->format('Y');
        $side = $index % 2 === 0 ? 'left' : 'right';
        $kindLabel = $labels[$entry['kind']] ?? ucfirst($entry['kind']);

        $logo = '';
        $src = self::str($entry['logo']['img'] ?? '');
        if ($src !== '') {
            $img = (string) PlatformComponentRenderer::render('image', ['src' => $src, 'alt' => $entry['title'], 'class' => 'experience-timeline-logo-img']);
            if (trim($img) !== '') {
                $bg = self::str($entry['logo']['bg'] ?? 'light') === 'dark' ? 'dark' : 'light';
                $logo = '<span class="experience-timeline-logo" data-bg="' . $bg . '">' . $img . '</span>';
            }
        }

        $meta = '';
        foreach ($entry['meta'] as $m) $meta .= '<li class="is-meta">' . self::e($m) . '</li>';

        $tags = '';
        $seen = array_map(fn ($m) => strtolower($m), $entry['meta']);
        foreach ($entry['tags'] as $slug) {
            $t = self::str($tagLabels[$slug] ?? '') ?: str_replace('-', ' ', $slug);
            if (in_array(strtolower($t), $seen, true)) continue;
            $seen[] = strtolower($t);
            $tags .= '<li>' . self::e($t) . '</li>';
        }

        $details = '';
        $page = self::str($links[$entry['kind']] ?? '');
        if ($page !== '') {
            $details = '<a class="experience-timeline-link" href="' . self::e(PlatformPathService::post_link($page) . ($entry['anchor'] !== '' ? '#' . rawurlencode($entry['anchor']) : '')) . '">'
                . '<span>' . self::e($labels['details']) . '</span>' . self::chevron() . '</a>';
        }

        $style = $entry['color'] !== '' ? ' style="--tl-brand: ' . $entry['color'] . '"' : '';
        $classes = 'experience-timeline-item is-' . $side . ' is-' . $entry['kind'] . ($entry['current'] ? ' is-current' : '');
        $marker = $year !== $prevYear
            ? '<span class="experience-timeline-year">' . self::e($year) . '</span>'
            : '<span class="experience-timeline-dot"></span>';

        return '<li class="' . $classes . '"' . $style . '>'
            . '<div class="experience-timeline-marker" aria-hidden="true">' . $marker . '</div>'
            . '<article class="experience-timeline-card">'
            . '<div class="experience-timeline-card-head">'
            . $logo
            . '<div class="experience-timeline-card-heading">'
            . '<span class="experience-timeline-kind">' . self::icon($entry['kind']) . self::e($kindLabel) . '</span>'
            . '<h3 class="experience-timeline-card-title">' . self::e($entry['title']) . '</h3>'
            . '</div></div>'
            . ($entry['role'] !== '' ? '<p class="experience-timeline-role">' . self::e($entry['role']) . '</p>' : '')
            . ($meta !== '' || $tags !== '' || $details !== ''
                ? '<div class="experience-timeline-card-foot">'
                    . ($meta !== '' || $tags !== '' ? '<ul class="experience-timeline-tags">' . $meta . $tags . '</ul>' : '')
                    . $details . '</div>'
                : '')
            . '</article></li>';
    }

    private static function brand_color(mixed $colors): string
    {
        if (!is_array($colors)) return '';
        foreach (['primary', 'secondary', 'canvas_secondary', 'canvas_primary'] as $key) {
            $hex = self::str($colors[$key] ?? '');
            if (!preg_match('/^#([0-9a-f]{3}|[0-9a-f]{6})$/i', $hex)) continue;
            $h = ltrim($hex, '#');
            if (strlen($h) === 3) $h = $h[0] . $h[0] . $h[1] . $h[1] . $h[2] . $h[2];
            [$r, $g, $b] = array_map('hexdec', str_split($h, 2));
            $lum = (0.2126 * $r + 0.7152 * $g + 0.0722 * $b) / 255;
            if ($lum > 0.12 && $lum < 0.9) return '#' . strtolower($h);
        }
        return '';
    }

    private static function chevron(): string
    {
        $svg = (string) PlatformComponentRenderer::render('svg', ['icon' => 'chevron-right', 'class' => 'experience-timeline-link-icon']);
        if (trim($svg) !== '') return $svg;
        return '<svg class="experience-timeline-link-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg>';
    }

    private static function icon(string $kind): string
    {
        $path = $kind === 'education'
            ? '<path d="M12 4 1.5 9 12 14l8.5-4.05V16h1.5V9L12 4Z"/><path d="M5.5 12.2V16c0 1.66 2.91 3 6.5 3s6.5-1.34 6.5-3v-3.8L12 15.3l-6.5-3.1Z"/>'
            : '<path d="M9 4h6a2 2 0 0 1 2 2v1h3a2 2 0 0 1 2 2v3H2V9a2 2 0 0 1 2-2h3V6a2 2 0 0 1 2-2Zm0 3h6V6H9v1Z"/><path d="M2 13.5h8V15h4v-1.5h8V18a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-4.5Z"/>';
        return '<svg class="experience-timeline-kind-icon" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">' . $path . '</svg>';
    }

    private static function date(mixed $value): ?DateTime
    {
        $value = self::str($value);
        if ($value === '' || strtolower($value) === 'present') return null;
        try {
            return new DateTime($value);
        } catch (Exception $e) {
            return null;
        }
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
