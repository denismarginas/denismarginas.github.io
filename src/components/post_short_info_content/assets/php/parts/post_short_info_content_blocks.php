<?php

class post_short_info_content_blocks extends post_short_info_content_helpers
{
    public const DEFAULT_FIELDS = [
        'title',
        'description',
        'categories',
        'website',
        'platform',
        'technology',
        'modules',
        'web_icons',
        'collaboration',
        'status',
        'media_links',
        'media_platforms',
        'types',
        'school',
        'employer',
        'date',
        'tags',
    ];

    protected static function block_title(array $d): string
    {
        $title = trim((string) ($d['seo']['title'] ?? ''));
        return $title !== '' ? '<h2 class="post-short-info-title">' . self::esc($title) . '</h2>' : '';
    }

    protected static function block_description(array $d): string
    {
        $text = trim((string) ($d['seo']['description'] ?? ''));
        return $text !== '' ? '<p class="post-short-info-description">' . self::esc_text($text) . '</p>' : '';
    }

    protected static function block_categories(array $d): string
    {
        $items = [];
        foreach (self::strings($d['taxonomy']['category'] ?? []) as $category) {
            $items[] = self::taxonomy_term('category', $category);
        }
        return self::pills('post-short-info-categories', $items);
    }

    protected static function block_website(array $d): string
    {
        return self::link_pills('post-short-info-website-pills', $d['web']['urls'] ?? []);
    }

    protected static function block_platform(array $d): string
    {
        return self::names_field('post-short-info-platform', 'web_platform', $d['web']['platform'] ?? []);
    }

    protected static function block_technology(array $d): string
    {
        return self::names_field('post-short-info-technology', 'web_technology', $d['web']['technology'] ?? []);
    }

    protected static function block_modules(array $d): string
    {
        return self::names_field('post-short-info-modules', 'web_plugins', $d['web']['plugins'] ?? []);
    }

    protected static function block_web_icons(array $d): string
    {
        return self::icons('post-short-info-web-icons', array_merge(
            self::items($d['web']['platform'] ?? []),
            self::items($d['web']['technology'] ?? []),
            self::items($d['web']['plugins'] ?? [])
        ));
    }

    protected static function block_collaboration(array $d): string
    {
        $values = self::strings($d['project']['collaboration'] ?? []);
        return self::field('post-short-info-collaboration', self::label('project_collaboration'), self::esc(implode(', ', $values)));
    }

    protected static function block_status(array $d): string
    {
        if (!array_key_exists('delivery_status', $d['web'] ?? [])) return '';

        $done = filter_var($d['web']['delivery_status'], FILTER_VALIDATE_BOOLEAN);
        $state = $done ? 'done' : 'undone';
        $label = trim(self::label('web_project_status'));

        return '<div class="post-short-info-status">'
            . ($label !== '' ? '<span class="post-short-info-status-label">' . self::esc($label) . '</span>' : '')
            . '<span class="post-short-info-status-value post-short-info-status-' . $state . '">' . self::esc(self::label('web_project_status_' . $state)) . '</span>'
            . '</div>';
    }

    protected static function block_media_links(array $d): string
    {
        return self::link_pills('post-short-info-media-pills', $d['media']['urls'] ?? []);
    }

    protected static function block_media_platforms(array $d): string
    {
        $items = $d['media']['platforms'] ?? [];
        return self::names_field('post-short-info-media-platforms', 'media_platforms', $items)
            . self::icons('post-short-info-media-icons', self::items($items));
    }

    protected static function block_types(array $d): string
    {
        $items = array_map(fn($t) => [$t, ''], self::strings($d['project']['types'] ?? []));
        return self::pills('post-short-info-types', $items);
    }

    protected static function block_school(array $d): string
    {
        return self::field('post-short-info-school', self::label('project_school'), self::esc(implode(', ', self::strings($d['project']['school'] ?? []))));
    }

    protected static function block_employer(array $d): string
    {
        $employer = trim((string) ($d['project']['employer'] ?? ''));
        if ($employer === '' || strtolower($employer) === 'unspecified') return '';

        if (strtolower($employer) === 'freelancer') {
            return '<div class="post-short-info-employer">' . self::field('', self::label('employer_type'), self::esc($employer)) . '</div>';
        }

        $job = self::find_job($employer);
        $nameHtml = self::esc($employer);
        $logoHtml = '';

        if (!empty($job)) {
            $anchor = (string) ($job['data']['seo']['slug'] ?? self::slugify($employer));
            $link = PlatformPathService::post_link('employee-experience') . '#' . $anchor;
            $nameHtml = '<a href="' . self::esc($link) . '">' . $nameHtml . '</a>';

            $logo = $job['data']['media']['logo'] ?? [];
            $img = trim((string) ($logo['img'] ?? ''));
            if ($img !== '') {
                $imgHtml = (string) PlatformComponentRenderer::render('image', ['src' => $img, 'alt' => $employer, 'class' => 'post-short-info-employer-img', 'lazy' => true]);
                if ($imgHtml !== '') {
                    $bg = in_array(($logo['bg'] ?? ''), ['light', 'dark'], true) ? $logo['bg'] : 'dark';
                    $logoHtml = '<div class="post-short-info-employer-logo post-short-info-employer-logo-' . $bg . '">' . $imgHtml . '</div>';
                }
            }
        }

        return '<div class="post-short-info-employer">' . self::field('', self::label('employer_location'), $nameHtml) . $logoHtml . '</div>';
    }

    protected static function block_date(array $d): string
    {
        $start = trim((string) ($d['date']['start'] ?? ''));
        $end = trim((string) ($d['date']['end'] ?? ''));
        if ($start === '' && $end === '') return '';

        $startLabel = $start !== '' ? self::date_label($start) : '';
        $endLabel = self::is_date($end) ? self::date_label($end) : self::present_label();

        $value = ($startLabel !== '' && $startLabel === $endLabel)
            ? $startLabel
            : implode(' - ', array_values(array_filter([$startLabel, $endLabel], fn ($v) => $v !== '')));

        return self::field('post-short-info-date', self::label('date'), self::esc($value));
    }

    private static function date_label(string $date): string
    {
        $out = PlatformComponentRenderer::value('utility_date', [
            'input' => $date,
            'format' => 'yyyy-mm-dd',
            'outputs' => ['label' => 'M Y'],
        ]);
        return is_array($out) ? trim((string) ($out['label'] ?? '')) : '';
    }

    private static function is_date(string $value): bool
    {
        return (bool) preg_match('/^\d{4}-\d{1,2}(-\d{1,2})?$/', $value);
    }

    protected static function block_tags(array $d): string
    {
        $items = [];
        foreach (self::strings($d['taxonomy']['tag'] ?? []) as $tag) {
            [$title] = self::taxonomy_term('tag', $tag);
            $items[] = [$title, ''];
        }
        return self::pills('post-short-info-tags', $items);
    }

    private static function links(mixed $urls): array
    {
        if (!is_array($urls)) return [];

        $links = [];
        foreach ($urls as $item) {
            $item = is_array($item) ? $item : ['url' => $item];
            $url = trim((string) ($item['url'] ?? ''));
            if ($url === '') continue;

            $links[] = [
                'href' => (string) PlatformComponentRenderer::value('utility_string_urlto', ['input' => $url, 'action' => 'transform']),
                'view' => (string) PlatformComponentRenderer::value('utility_string_urlto', ['input' => $url, 'action' => 'synthesize']),
                'text' => trim((string) ($item['text'] ?? '')),
                'svg' => trim((string) ($item['svg'] ?? '')),
            ];
        }
        return $links;
    }

    private static function link_pills(string $class, mixed $urls): string
    {
        $html = '';
        foreach (self::links($urls) as $link) {
            $svg = $link['svg'] !== '' ? self::svg($link['svg'], 'post-short-info-pill-icon') : '';
            $text = $link['text'] !== '' ? $link['text'] : $link['view'];
            $html .= '<a class="post-short-info-pill post-short-info-pill-link" href="' . self::esc($link['href']) . '" target="_blank" rel="noopener">'
                . $svg . '<span>' . self::esc($text) . '</span></a>';
        }
        return $html !== '' ? '<div class="post-short-info-pills ' . $class . '">' . $html . '</div>' : '';
    }

    private static function names_field(string $class, string $labelKey, mixed $items): string
    {
        $names = array_map(fn($i) => trim((string) ($i['text'] ?? '')), self::items($items));
        $names = array_values(array_filter($names, fn($n) => $n !== ''));
        return self::field($class, self::label($labelKey), self::esc(implode(', ', $names)));
    }

    private static function icons(string $class, array $items): string
    {
        $html = '';
        $seen = [];
        foreach ($items as $item) {
            $icon = trim((string) ($item['svg'] ?? ''));
            if ($icon === '' || isset($seen[$icon])) continue;
            $svg = self::svg($icon);
            if ($svg === '') continue;
            $seen[$icon] = true;
            $title = trim((string) ($item['text'] ?? ''));
            $html .= '<li' . ($title !== '' ? ' title="' . self::esc($title) . '"' : '') . '>' . $svg . '</li>';
        }
        return $html !== '' ? '<ul class="post-short-info-icons ' . $class . '">' . $html . '</ul>' : '';
    }

    private static function find_job(string $name): array
    {
        $needle = self::slugify($name);
        foreach ((PlatformDataService::get_data('collection_job') ?? []) as $job) {
            if (($job['settings']['render'] ?? true) === false) continue;
            if (self::slugify((string) ($job['data']['seo']['title'] ?? '')) === $needle) return $job;
        }
        return [];
    }

    private static function present_label(): string
    {
        $dates = PlatformDataService::get_data('dates') ?? [];
        return (string) ($dates['current_date']['present'] ?? self::label('date_present'));
    }
}
