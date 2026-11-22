<?php

class footer_render extends footer_copyrights
{
    public static function render(array $data = []): string
    {
        $content = PlatformDataService::get_data('content_footer') ?? [];

        $columns = '';
        foreach ((array) ($content['columns'] ?? []) as $block) {
            if (!is_array($block)) continue;
            $group = !isset($block['component']) && is_array($block['children'] ?? null);
            $html = self::blocks($group ? $block['children'] : [$block]);
            if (trim($html) === '') continue;

            $class = $group ? trim(preg_replace('/[^a-zA-Z0-9_\- ]/', '', (string) ($block['class'] ?? ''))) : '';
            $columns .= '<div class="footer-column' . ($class !== '' ? ' ' . $class : '') . '">' . $html . '</div>';
        }

        $bottom = self::blocks((array) ($content['bottom'] ?? []));
        if ($columns === '' && trim($bottom) === '') {
            $site = PlatformDataService::get_data('settings_site') ?? [];
            $bottom = self::render_copyrights((string) ($site['site_title'] ?? $site['name'] ?? ''));
        }

        return PlatformTemplateRenderer::render([
            'columns_html' => $columns !== '' ? '<div class="footer-columns">' . $columns . '</div>' : '',
            'bottom_html' => trim($bottom) !== '' ? '<div class="footer-bottom">' . $bottom . '</div>' : '',
        ]);
    }

    private static function blocks(array $blocks): string
    {
        $blocks = array_values(array_filter($blocks, 'is_array'));
        return empty($blocks) ? '' : section::render_children(['children' => $blocks]);
    }
}
