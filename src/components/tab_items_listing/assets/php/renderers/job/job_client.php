<?php

class tab_items_listing_job_client
{
    public static function render_job_clients(array $workingFor, array $data): string
    {
        if ($workingFor === []) return '';
        if (array_is_list($workingFor)) $workingFor = ['items' => $workingFor];

        $label = trim((string) ($workingFor['label'] ?? ''));
        $labelHtml = $label !== '' ? '<span class="tab-items-listing-client-label">' . self::e($label) . '</span>' : '';
        $ids = tab_items_listing_utility::as_list($workingFor['items'] ?? $workingFor['jobs'] ?? null);
        if ($ids === []) return '';

        $pool = is_array($data['items'] ?? null)
            ? $data['items']
            : PlatformDataService::get_collection((string) ($data['type'] ?? $data['collection_type'] ?? 'job'));
        $byId = [];
        foreach ((array) $pool as $entry) {
            if (is_array($entry) && isset($entry['_id'])) $byId[(string) $entry['_id']] = $entry;
        }

        $chips = '';
        foreach ($ids as $id) {
            $entry = $byId[$id] ?? null;
            if ($entry === null) {
                $chips .= '<span class="tab-items-listing-worktime-type tab-items-listing-client">' . $labelHtml . '<span class="tab-items-listing-client-name">' . self::e($id) . '</span></span>';
                continue;
            }
            $entryData = is_array($entry['data'] ?? null) ? $entry['data'] : [];
            $name = (string) ($entryData['seo']['title'] ?? $id);
            $anchor = trim((string) ($entryData['seo']['slug'] ?? '')) ?: $id;
            $logo = is_array($entryData['media']['logo'] ?? null) ? $entryData['media']['logo'] : [];
            $logoHtml = '';
            if (!empty($logo['img'])) {
                $image = (string) PlatformComponentRenderer::render('image', ['src' => (string) $logo['img'], 'alt' => $name, 'class' => 'tab-items-listing-client-logo-img']);
                if ($image !== '') {
                    $logoHtml = '<span class="tab-items-listing-client-logo" layout-style="' . self::e((string) ($logo['bg'] ?? 'light')) . '">' . $image . '</span>';
                }
            }
            $hidden = ($entry['settings']['render'] ?? true) === false;
            $inner = $labelHtml . ($logoHtml !== '' ? $logoHtml : '<span class="tab-items-listing-client-name">' . self::e($name) . '</span>');
            $chips .= $hidden
                ? '<span class="tab-items-listing-worktime-type tab-items-listing-client" title="' . self::e($name) . '">' . $inner . '</span>'
                : '<a class="tab-items-listing-worktime-type tab-items-listing-client" href="#' . self::e($anchor) . '" title="' . self::e($name) . '">' . $inner . '</a>';
        }

        return $chips;
    }

    private static function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
