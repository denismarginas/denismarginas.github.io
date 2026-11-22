<?php

require_once __DIR__ . '/../../../utility_project_media/assets/php/utility_project_media.php';
require_once __DIR__ . '/parts/project_page_transitions.php';

class project_page
{
    private const PASSTHROUGH = ['post_current_data', 'global_content_path', 'global_img_path', 'global_vid_path'];

    private const SECTIONS = [
        'overview' => ['project_overview', 'overview', 'nav_overview', 'Overview'],
        'screens' => ['project_screens', 'screens', 'nav_screens', 'Screens'],
        'brand' => ['project_brand', 'brand', 'nav_brand', 'Brand'],
        'gallery' => ['project_gallery', 'gallery', 'nav_gallery', 'Gallery'],
        'videos' => ['project_videos', 'videos', 'nav_videos', 'Videos'],
    ];
    private const DEFAULT_ORDER = ['overview', 'screens', 'brand', 'gallery', 'videos', 'children'];

    private const SECTION_COMPONENTS = [
        'project_overview', 'project_screens', 'project_brand', 'project_gallery', 'project_videos',
        'project_related', 'section', 'section_layout_aside', 'project_taxonomy_marquee', 'project_hero',
    ];

    public static function render(array $data = []): string
    {
        $post = is_array($data['post_current_data'] ?? null) ? $data['post_current_data'] : [];
        if (empty($post)) return '';
        $shared = array_intersect_key($data, array_flip(self::PASSTHROUGH));

        $hero = self::part($data, 'hero', 'project_hero', $shared);
        $marquee = self::part($data, 'marquee', 'project_taxonomy_marquee', $shared);

        $chunks = [];
        $navItems = [];
        $order = is_array($data['sections'] ?? null) ? $data['sections'] : self::DEFAULT_ORDER;
        if (!in_array('children', $order, true)) $order[] = 'children';
        foreach ($order as $key) {
            if ($key === 'children') {
                [$html, $items] = self::children($data, $shared);
                $chunks = array_merge($chunks, $html);
                $navItems = array_merge($navItems, $items);
                continue;
            }
            if (!isset(self::SECTIONS[$key]) || ($data[$key] ?? []) === false) continue;
            [$component, $id, $labelKey, $label] = self::SECTIONS[$key];
            $params = array_merge(['id' => $id], is_array($data[$key] ?? null) ? $data[$key] : [], $shared);
            $html = self::section($component, $params);
            if (trim($html) === '') continue;
            $chunks[] = $html;
            $navItems[] = ['id' => (string) $params['id'], 'text' => utility_project_media::text($labelKey, $label)];
        }

        $nav = '';
        if (($data['nav'] ?? []) !== false && count($navItems) > 1) {
            $navParams = is_array($data['nav'] ?? null) ? $data['nav'] : [];
            $nav = (string) PlatformComponentRenderer::render('section_nav', array_merge(['items' => $navItems], $navParams, $shared));
        }

        return PlatformTemplateRenderer::render([
            'kind' => utility_project_media::screens($post, 'desktop') !== [] ? 'website' : 'media',
            'style' => (string) PlatformComponentRenderer::value('utility_appearance_colors', ['post_current_data' => $post, 'output' => 'attribute']),
            'hero' => $hero,
            'marquee' => $marquee,
            'nav' => $nav,
            'sections' => implode('', self::alternate($chunks)),
        ]);
    }

    private static function part(array $data, string $key, string $component, array $shared): string
    {
        if (($data[$key] ?? []) === false) return '';
        $params = is_array($data[$key] ?? null) ? $data[$key] : [];
        return (string) PlatformComponentRenderer::render($component, array_merge($params, $shared));
    }

    private static function section(string $component, array $params): string
    {
        if (PlatformComponentRenderer::get_component_config($component) === null) return '';
        if (!array_key_exists('transition', $params) && ($rule = project_page_transitions::rule($component)) !== null) {
            $params['transition'] = $rule;
        }
        return (string) PlatformComponentRenderer::render($component, $params);
    }

    private static function children(array $data, array $shared): array
    {
        $children = $data['children'] ?? [];
        if (!is_array($children)) return [[], []];

        $caseLayout = ($data['case_study_layout'] ?? true) !== false;
        $html = [];
        $nav = [];
        $cases = 0;
        $related = 0;

        foreach ($children as $child) {
            if (!is_array($child)) continue;
            $name = str_replace('-', '_', (string) ($child['component'] ?? ''));
            if ($name === '') continue;
            $params = array_merge(is_array($child['data'] ?? null) ? $child['data'] : [], $shared, [
                'children' => $child['children'] ?? $child['data']['children'] ?? [],
            ]);

            if ($name === 'block_highlight' && ($params['layout'] ?? ($caseLayout ? 'case_study' : '')) === 'case_study') {
                $out = self::case_study($params, $cases);
                if ($out === '') continue;
                if ($cases === 0) $nav[] = self::nav_item($out, 'nav_case_studies', 'Case studies');
                $cases++;
            } elseif (in_array($name, self::SECTION_COMPONENTS, true)) {
                if ($name === 'project_related' && $related > 0 && in_array((string) ($params['id'] ?? 'related'), ['', 'related'], true)) {
                    $params['id'] = 'related-' . ($related + 1);
                }
                $out = trim(self::section($name, $params));
                if ($out === '') continue;
                if ($name === 'project_related' && $related++ === 0) $nav[] = self::nav_item($out, 'nav_related', 'Related');
            } else {
                $out = self::block($name, $params);
                if ($out === '') continue;
            }
            $html[] = $out;
        }

        return [$html, array_values(array_filter($nav))];
    }

    private static function case_study(array $params, int $index): string
    {
        $params['layout'] = 'case_study';
        $params['variant'] = $params['variant'] ?? ($index % 2 === 1 ? 'panel' : '');
        return trim(self::section('block_highlight', $params));
    }

    private static function alternate(array $chunks): array
    {
        $right = null;
        foreach ($chunks as $i => $html) {
            if (!preg_match('/class="(block-highlight-case(?: [^"]*)?)"/', $html, $m, PREG_OFFSET_CAPTURE)
                || str_contains($m[1][0], 'block-highlight-case-no-media')) {
                $right = null;
                continue;
            }
            $flip = $right === false;
            $classes = array_values(array_diff(explode(' ', $m[1][0]), ['block-highlight-case-flip']));
            if ($flip) $classes[] = 'block-highlight-case-flip';
            $chunks[$i] = substr_replace($html, implode(' ', $classes), $m[1][1], strlen($m[1][0]));
            $right = $flip;
        }
        return $chunks;
    }

    private static function block(string $name, array $params): string
    {
        if (!array_key_exists('transition', $params) && ($rule = PlatformComponentRenderer::transition_rule($name)) !== null) {
            $params['transition'] = $rule;
        }
        $out = (string) PlatformComponentRenderer::render($name, $params);
        return trim($out) !== ''
            ? (string) PlatformComponentRenderer::render('section', ['class' => 'project-section project-section-block', 'children' => $out])
            : '';
    }

    private static function nav_item(string $html, string $labelKey, string $label): ?array
    {
        return preg_match('/\sid="([^"]+)"/', $html, $m)
            ? ['id' => html_entity_decode($m[1], ENT_QUOTES, 'UTF-8'), 'text' => utility_project_media::text($labelKey, $label)]
            : null;
    }
}
