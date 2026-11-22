<?php

require_once __DIR__ . '/../../../../utility_project_media/assets/php/utility_project_media.php';

class filters_post_projects_render
{
    private const HTML_DIR = __DIR__ . '/../../html/';

    private const DEFAULT_SORT_FIELDS = [
        ['label' => 'Name', 'by' => 'seo.title', 'type' => 'text', 'order' => 'asc'],
        ['label' => 'Publish Date', 'by' => 'date.publish', 'type' => 'date', 'order' => 'desc'],
    ];

    public static function form(array $data, string $target, array $filters, array $posts): string
    {
        $filtersHtml = '';
        foreach ($filters as $filter) {
            $filtersHtml .= self::filter($target, $filter);
        }

        $sortData = self::sort_data($data, $target);
        $sortHtml = (string) filters_sort::render($sortData);
        $count = count($posts);

        $toolbar = filters_toolbar::render($data, [
            'target' => $target,
            'input' => [
                'class' => 'post-search',
                'name' => 'keywords',
                'placeholder' => (string) ($data['search_placeholder'] ?? PlatformTextService::ui('search_placeholder', 'Search keywords...')),
                'aria-label' => (string) ($data['search_placeholder'] ?? PlatformTextService::ui('search_placeholder', 'Search keywords...')),
                'autocomplete' => 'off',
            ],
            'results_text' => filters_toolbar::results_text((string) ($data['results_text'] ?? PlatformTextService::ui('results_text', 'Showing {n} results.')), $count),
            'count' => $count,
            'filters' => $filtersHtml,
            'sort' => $sortHtml,
            'view' => self::preview_button($data, $target),
        ]);

        return PlatformTemplateRenderer::render(self::HTML_DIR . 'template.html', [
            'target' => self::e($target),
            'form_id' => self::e($target . '-filters'),
            'filters_number' => (string) count($filters),
            'toolbar' => $toolbar,
            'index' => self::index($filters, $posts, $sortHtml === '' ? null : $sortData),
        ]);
    }

    private static function filter(string $target, array $filter): string
    {
        $placeholder = filters_toolbar::placeholder($filter['placeholder'] ?? '');
        $style = isset($filter['style']) ? ' style="' . self::e((string) $filter['style']) . '"' : '';

        $options = '<option value="">' . self::e($placeholder) . '</option>';
        foreach ($filter['options'] as $value) {
            $options .= '<option value="' . self::e($value) . '"' . $style . '>' . self::e(self::option_label($filter, $value)) . '</option>';
        }

        return PlatformTemplateRenderer::render(self::HTML_DIR . 'parts/filter.html', [
            'select_id' => self::e($target . '-' . $filter['_id']),
            'filter_id' => self::e((string) $filter['_id']),
            'name' => self::e((string) ($filter['name'] ?? $filter['_id'])),
            'label' => self::e(filters_toolbar::label((string) ($filter['label'] ?? ''))),
            'type' => self::e((string) ($filter['json-type'] ?? 'string')),
            'mode' => self::e(listing_toolbar::mode($filter)),
            'style' => $style,
            'options' => $options,
        ]);
    }

    private static function sort_data(array $data, string $target): array
    {
        return array_merge($data, [
            'target' => $target,
            'sort_fields' => $data['sort_fields'] ?? self::DEFAULT_SORT_FIELDS,
            'sort_default' => $data['sort_default'] ?? PlatformTextService::ui('default', 'Default'),
        ], filters_toolbar::sort_labels($data));
    }

    private static function index(array $filters, array $posts, ?array $sortData = null): string
    {
        $index = [];
        foreach ($posts as $post) {
            $id = filters_post_projects_posts::id($post);
            if ($id === '') continue;

            $entry = ['keywords' => self::keywords($post)];
            foreach ($filters as $filter) {
                $entry[$filter['_id']] = filters_post_projects_values::of($post, $filter);
            }
            if ($sortData !== null) {
                $entry['_sort'] = filters_sort::values($post, $sortData);
            }
            $index[$id] = $entry;
        }

        $json = json_encode($index, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP);
        return $json === false ? '{}' : $json;
    }

    private static function keywords(array $post): string
    {
        $seo = $post['data']['seo'] ?? [];
        $parts = [filters_post_projects_posts::id($post), $seo['title'] ?? '', $seo['description'] ?? '', $seo['keywords'] ?? ''];
        $data = is_array($post['data'] ?? null) ? $post['data'] : [];
        array_walk_recursive($data, function ($v, $k) use (&$parts) {
            if (is_string($v) && in_array($k, ['text', 'employer'], true)) $parts[] = $v;
        });
        foreach (['types', 'companies', 'contractors', 'collaboration'] as $key) {
            foreach ((array) ($data['project'][$key] ?? []) as $v) {
                if (is_string($v)) $parts[] = $v;
            }
        }
        foreach ((array) ($data['taxonomy'] ?? []) as $terms) {
            foreach ((array) $terms as $v) {
                if (is_string($v)) $parts[] = $v;
            }
        }
        return mb_strtolower(trim(preg_replace('/\s+/', ' ', implode(' ', array_filter($parts, 'is_string')))));
    }

    private static function preview_button(array $data, string $target): string
    {
        if (($data['preview_toggle'] ?? false) !== true) {
            return '';
        }
        return filters_toolbar::segment('preview', $target, 'eye', (string) ($data['preview_label'] ?? PlatformTextService::ui('preview_label', 'Card image')), [
            'logo' => (string) ($data['preview_off'] ?? PlatformTextService::ui('preview_off', 'Logo')),
            'preview' => (string) ($data['preview_on'] ?? PlatformTextService::ui('preview_on', 'Preview')),
        ], ($data['preview_active'] ?? true) !== false ? 'preview' : 'logo');
    }

    private static function option_label(array $filter, string $value): string
    {
        $path = (string) ($filter['json-path'] ?? '');
        if (str_contains($path, "['types']")) return utility_project_media::type($value);
        if (str_contains($path, "['tag']")) return utility_project_media::tag($value);
        if (str_contains($path, "['category']")) return utility_project_media::category($value);
        return $value;
    }

    private static function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
