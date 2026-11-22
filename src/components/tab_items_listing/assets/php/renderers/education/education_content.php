<?php

class tab_items_listing_education_content
{
    public static function render_content_education(array $item, array $data, string $itemId = ''): string
    {
        $itemData = is_array($item['data'] ?? null) ? $item['data'] : $item;
        $seo = $itemData['seo'] ?? [];
        $date = $itemData['date'] ?? [];
        $education = $itemData['education'] ?? [];
        $media = $itemData['media'] ?? [];
        $logo = $media['logo'] ?? [];

        $name = (string) ($seo['title'] ?? '');
        $type = (string) ($education['type'] ?? '');
        $location = (string) ($education['location'] ?? '');
        $profession = tab_items_listing_utility::as_list($education['profession'] ?? null);
        $heading = (string) ($education['heading'] ?? '');
        $disciplinesData = $education['disciplines'] ?? null;
        $disciplines = [];
        $disciplinesSvg = '';
        if (is_array($disciplinesData)) {
            if (isset($disciplinesData['list']) && is_array($disciplinesData['list'])) {
                $disciplines = array_values(array_map('strval', $disciplinesData['list']));
                $disciplinesSvg = (string) ($disciplinesData['svg'] ?? '');
            } else {
                $disciplines = tab_items_listing_utility::as_list($disciplinesData);
            }
        }
        $projects = is_array($education['projects'] ?? null) ? $education['projects'] : [];
        $links = is_array($itemData['external_links'] ?? null) ? $itemData['external_links'] : [];

        $dateLabel = tab_items_listing_education_date::education_date_range($date);
        $logoHtml = tab_items_listing_education::render_education_logo($logo, $name);
        $typeTagHtml = '';
        if ($type !== '') {
            $typeIconHtml = PlatformComponentRenderer::render('svg', [
                'icon' => 'education',
                'class' => 'tab-items-listing-type-icon',
            ]);
            $typeTagHtml = tab_items_listing_loader::load_education_partial('type.html', [
                'type' => htmlspecialchars($type, ENT_QUOTES, 'UTF-8'),
                'type_svg' => $typeIconHtml,
            ]);
        }

        $dateIconHtml = $dateLabel !== '' ? PlatformComponentRenderer::render('svg', [
            'icon' => 'time',
            'class' => 'tab-items-listing-date-icon',
        ]) : '';
        $locationIconHtml = $location !== '' ? PlatformComponentRenderer::render('svg', [
            'icon' => 'location',
            'class' => 'tab-items-listing-location-icon',
        ]) : '';

        $metaHtml = tab_items_listing_loader::load_education_partial('meta.html', [
            'type_tag' => $typeTagHtml,
            'date_icon' => $dateIconHtml,
            'date' => htmlspecialchars($dateLabel, ENT_QUOTES, 'UTF-8'),
            'location_icon' => $locationIconHtml,
            'location' => htmlspecialchars($location, ENT_QUOTES, 'UTF-8'),
        ]);

        $professionHtml = !empty($profession)
            ? tab_items_listing_loader::load_education_partial('profession.html', [
                'profession' => htmlspecialchars(implode(', ', $profession), ENT_QUOTES, 'UTF-8'),
            ])
            : '';

        $disciplinesHtml = '';
        if (!empty($disciplines)) {
            $disciplinesHtml = '<div class="tab-items-listing-heading">' . htmlspecialchars($heading, ENT_QUOTES, 'UTF-8') . '</div>'
                . PlatformComponentRenderer::render('icon_list', [
                    'items' => $disciplines,
                    'svg' => $disciplinesSvg,
                    'layout' => 'inline',
                    'size' => 'base',
                    'class' => 'tab-items-listing-list',
                ]);
        }

        $linksHtml = tab_items_listing_education::render_education_links($links, $data);
        $projectsHtml = tab_items_listing_education::render_education_projects($projects, $data);

        $linksContainerHtml = '';
        if ($linksHtml !== '' || $projectsHtml !== '') {
            $linksContainerHtml = tab_items_listing_loader::load_education_partial('links_container.html', [
                'links_html' => $linksHtml,
                'projects_html' => $projectsHtml,
            ]);
        }

        $display = (string) ($data['display'] ?? 'detailed');

        $patternSvg = PlatformComponentRenderer::render('svg', [
            'icon' => 'dotted-signal-pattern',
            'class' => 'tab-items-listing-pattern-svg',
        ]);

        return tab_items_listing_loader::load_education_partial('template.html', [
            'item_id' => htmlspecialchars($itemId, ENT_QUOTES, 'UTF-8'),
            'display' => htmlspecialchars($display, ENT_QUOTES, 'UTF-8'),
            'logo_html' => $logoHtml,
            'name' => htmlspecialchars($name, ENT_QUOTES, 'UTF-8'),
            'meta_html' => $metaHtml,
            'profession_html' => $professionHtml,
            'list_html' => $disciplinesHtml,
            'duration_html' => '',
            'links_container_html' => $linksContainerHtml,
            'pattern_svg' => $patternSvg,
        ]);
    }
}
