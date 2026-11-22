<?php

class taxonomy_listing
{
    public static function render(array $data = []): string
    {
        $postType = (string) ($data['post_type'] ?? '');
        $taxonomy = (string) ($data['taxonomy'] ?? 'category');
        $titleRender = ($data['title_render'] ?? true) !== false;
        $templateCfg = is_array($data['template'] ?? null) ? $data['template'] : [];
        $cardComponent = str_replace('-', '_', (string) ($templateCfg['component'] ?? 'card_taxonomy'));
        $cardData = $templateCfg['data'] ?? [];

        $types = PlatformDataService::get_data('settings_types');
        $taxConfig = self::resolve_taxonomy_config($types, $taxonomy, $postType);

        $terms = PlatformDataService::get_data('taxonomy_' . $taxonomy);
        if (!is_array($terms) && isset($taxConfig['file']) && preg_match('/^(?:data_)?(taxonomy_[a-z0-9_]+)\.json$/', (string) $taxConfig['file'], $m)) {
            $terms = PlatformDataService::get_data($m[1]);
        }
        if (!is_array($terms)) $terms = [];
        $terms = array_values(array_filter($terms, function ($t) use ($postType, $taxConfig) {
            if (($t['data']['settings']['listing'] ?? true) === false) return false;
            $termTypes = $t['post_types'] ?? $taxConfig['post_types'] ?? [];
            return $postType === '' || empty($termTypes) || in_array($postType, $termTypes, true);
        }));
        if (empty($terms)) return '';

        $labelsAttr = (string) PlatformComponentRenderer::value('utility_grid_listing', ['count' => count($terms)]);

        $cards = '';
        foreach ($terms as $term) {
            $params = array_merge($cardData, ['term' => $term]);
            if (class_exists($cardComponent) && method_exists($cardComponent, 'render')) {
                $html = $cardComponent::render($params);
            } else {
                $html = PlatformComponentRenderer::render($cardComponent, $params);
            }
            if ($html !== '') $cards .= '<li class="taxonomy-item">' . $html . '</li>';
        }
        if ($cards === '') return '';


        $titleHtml = $titleRender ? (string) PlatformComponentRenderer::render('title', title::normalize($data['title'] ?? null, $data)) : '';

        return PlatformTemplateRenderer::render([
            'title_html' => $titleHtml,
            'labels_attr' => $labelsAttr,
            'items' => $cards
        ]);
    }

    private static function resolve_taxonomy_config(array $types, string $taxonomy, string $postType): array
    {
        $all = $types['taxonomy'] ?? [];
        if (isset($all[$taxonomy])) return $all[$taxonomy];
        foreach ($all as $key => $cfg) {
            if (str_starts_with($key, rtrim($taxonomy, 'y'))) return $cfg;
        }
        if ($postType !== '') {
            foreach ($all as $cfg) {
                if (in_array($postType, $cfg['post_types'] ?? [], true)) return $cfg;
            }
        }
        return [];
    }
}
