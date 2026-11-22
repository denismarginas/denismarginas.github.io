<?php

PlatformPathService::load_php_dir(__DIR__ . '/../renderers/');

class tab_items_listing_render
{
    public static function render(array $data = []): string
    {
        $type = (string) ($data['type'] ?? $data['collection_type'] ?? '');
        if ($type === '' && !empty($data['items'])) {
            $type = (string) ($data['type'] ?? $data['collection_type'] ?? '');
        }

        $items = $data['items'] ?? PlatformDataService::get_collection($type);
        if (!is_array($items) || empty($items)) return '';

        $items = PlatformComponentRenderer::value('utility_sort', [
            'items' => $items,
            'rules' => $data['sort'] ?? self::default_sort($data),
        ]);
        if (!is_array($items) || empty($items)) return '';

        $labels = '';
        $itemsHtml = '';
        $labelCount = 0;

        foreach ($items as $item) {
            if (($item['settings']['render'] ?? true) === false) continue;

            $itemId = (string) ($item['_id'] ?? $item['item_id'] ?? $item['post_id'] ?? '');
            if ($itemId === '') continue;
            $slug = trim((string) ($item['data']['seo']['slug'] ?? ''));
            if ($slug !== '') $itemId = $slug;

            $labels .= tab_items_listing_label::render_label($itemId, $item, $data);
            $itemsHtml .= tab_items_listing_content::render_content($item, $data, $itemId);
            $labelCount++;
        }

        if ($itemsHtml === '') return '';

        $layout = (string) ($data['layout'] ?? '');
        $labelsAttr = '';
        if ($layout === 'labels_top') {
            $labelsAttr = (string) PlatformComponentRenderer::value('utility_grid_listing', [
                'count' => $labelCount,
            ]);
        }
        $existNote = self::render_exist_note($data, $items);
        $additionalContent = '';
        if (!empty($data['children']) && is_array($data['children'])) {
            foreach ($data['children'] as $child) {
                $comp = (string) ($child['component'] ?? '');
                if ($comp === '') continue;
                $cData = $child['data'] ?? [];
                $html = PlatformComponentRenderer::render(str_replace('-', '_', $comp), $cData);
                if ($html !== '') $additionalContent .= '<li class="tab-additional-content">' . $html . '</li>';
            }
        }
        return PlatformTemplateRenderer::render([
            'labels' => $labels,
            'labels_attr' => $labelsAttr,
            'items' => $itemsHtml,
            'exist_note' => $existNote,
            'additional_content' => $additionalContent,
            'layout_attr' => $layout !== '' ? ' layout="' . htmlspecialchars($layout, ENT_QUOTES, 'UTF-8') . '"' : '',
            'type' => htmlspecialchars($type, ENT_QUOTES, 'UTF-8'),
        ]);
    }

    public static function default_sort(array $data): array
    {
        return [
            'by' => 'date.' . (string) ($data['date_sort_key'] ?? 'publish'),
            'order' => strtolower((string) ($data['date_sort_order'] ?? 'desc')),
        ];
    }

    public static function render_exist_note(array $data, array $items): string
    {
        $cfg = $data['exist_item_render_false'] ?? null;
        if (!is_array($cfg) || ($cfg['condition'] ?? false) !== true) return '';
        $content = $cfg['content'] ?? null;
        if (!is_array($content) || empty($content['component'])) return '';
        $hasFalse = false;
        foreach ($items as $it) {
            if (($it['settings']['render'] ?? true) === false) { $hasFalse = true; break; }
        }
        if (!$hasFalse) return '';
        $component = (string) $content['component'];
        $params = $content['data'] ?? array_diff_key($content, ['component' => true]);
        $html = PlatformComponentRenderer::render($component, $params);
        if ($html === '') return '';
        return '<li class="dm-tab-items-listing__exist-note">' . $html . '</li>';
    }
}


