<?php

class card_meta extends card_button
{
    public static function render_meta(array $data, array $postData): string
    {
        $dateHtml = self::render_date($data, $postData);
        $taxonomyHtml = self::render_taxonomy($data, $postData);

        $metaContent = $dateHtml . $taxonomyHtml;
        if ($metaContent === '') return '';

        return PlatformTemplateRenderer::render(__DIR__ . '/../../html/parts/meta.html', [
            'meta_content' => $metaContent,
        ]);
    }

    public static function render_date(array $data, array $postData): string
    {
        $raw = $data['date'] ?? null;

        if (is_array($raw)) {
            $display = (string) ($raw['display'] ?? '');
            $datetime = (string) ($raw['datetime'] ?? $display);
            if ($display === '') return '';
            return self::render_date_html($datetime, $display, $data);
        }

        $date = is_string($raw) ? $raw : '';
        if ($date !== '' && str_starts_with($date, '@')) {
            $date = PlatformDataService::resolve_path_string($postData, substr($date, 1)) ?? '';
        }
        if ($date === '') return '';

        $display = PlatformTextService::format_date($date, (string) ($data['date_format'] ?? 'd M y'));

        return self::render_date_html($date, $display, $data);
    }

    public static function render_date_html(string $datetime, string $display, array $data): string
    {
        $time = PlatformTemplateRenderer::render(__DIR__ . '/../../html/parts/date.html', [
            'datetime' => htmlspecialchars($datetime, ENT_QUOTES, 'UTF-8'),
            'display' => htmlspecialchars($display, ENT_QUOTES, 'UTF-8'),
        ]);

        $icon = (string) ($data['date_icon'] ?? 'clock');
        $svg = '';
        if ($icon !== '') {
            $svg = PlatformComponentRenderer::render('svg', [
                'icon' => $icon,
                'class' => 'card-date-icon',
            ]);
        }

        if ($svg === '') return $time;

        return PlatformTemplateRenderer::render(__DIR__ . '/../../html/parts/date_container.html', [
            'icon' => $svg,
            'time' => $time,
        ]);
    }

    public static function render_taxonomy(array $data, array $postData): string
    {
        $raw = $data['taxonomy'] ?? null;
        if (!is_array($raw)) return '';

        $html = '';
        foreach (['category', 'tag'] as $type) {
            $items = $raw[$type] ?? null;
            if (!is_array($items) || empty($items)) continue;

            $inner = '';
            foreach ($items as $item) {
                if (is_array($item)) {
                    $label = (string) ($item['name'] ?? $item['text'] ?? '');
                    $url = (string) ($item['url'] ?? $item['link'] ?? '');
                } else {
                    $label = (string) $item;
                    $url = '';
                }
                if ($label === '') continue;

                $labelEscaped = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
                if ($url !== '') {
                    $inner .= PlatformTemplateRenderer::render(__DIR__ . '/../../html/parts/taxonomy_item_link.html', [
                        'link' => htmlspecialchars($url, ENT_QUOTES, 'UTF-8'),
                        'label' => $labelEscaped,
                    ]);
                } else {
                    $inner .= PlatformTemplateRenderer::render(__DIR__ . '/../../html/parts/taxonomy_item_span.html', [
                        'label' => $labelEscaped,
                    ]);
                }
            }
            if ($inner === '') continue;

            $html .= PlatformTemplateRenderer::render(__DIR__ . '/../../html/parts/taxonomy_group.html', [
                'type' => $type,
                'items' => $inner,
            ]);
        }

        return $html;
    }
}

