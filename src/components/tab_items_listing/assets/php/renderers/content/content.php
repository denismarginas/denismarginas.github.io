<?php

class tab_items_listing_content
{
    public static function render_content(array $item, array $data, string $itemId = ''): string
    {
        $type = (string) ($data['type'] ?? $data['collection_type'] ?? '');
        if ($type === 'education') {
            return tab_items_listing_education_content::render_content_education($item, $data, $itemId);
        }
        if ($type === 'job') {
            return tab_items_listing_job_content::render_content_jobs($item, $data, $itemId);
        }

        $contentTemplate = $data['content'] ?? $data['tab_item_content_template'] ?? null;
        if (is_array($contentTemplate)) {
            $html = tab_items_listing_resolver::render_component_spec($contentTemplate, $item);
            if ($html !== '') return $html;
        }

        return '';
    }
}
