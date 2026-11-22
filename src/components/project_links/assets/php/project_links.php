<?php

require_once __DIR__ . '/../../../utility_project_media/assets/php/utility_project_media.php';

class project_links
{
    public static function render(array $data = []): string
    {
        $post = is_array($data['post_current_data'] ?? null) ? $data['post_current_data'] : [];
        $d = is_array($post['data'] ?? null) ? $post['data'] : [];

        $urls = [];
        foreach ((is_array($data['sources'] ?? null) ? $data['sources'] : ['web', 'media']) as $source) {
            if (in_array($source, ['web', 'media'], true) && is_array($d[$source]['urls'] ?? null)) $urls = array_merge($urls, $d[$source]['urls']);
        }
        if (is_array($data['items'] ?? null)) $urls = array_merge($urls, $data['items']);

        $html = '';
        $seen = [];
        foreach ($urls as $item) {
            $item = is_array($item) ? $item : ['url' => $item];
            $url = trim((string) ($item['url'] ?? ''));
            if ($url === '') continue;
            $href = (string) PlatformComponentRenderer::value('utility_string_urlto', ['input' => $url, 'action' => 'transform']);
            if (isset($seen[$href])) continue;
            $seen[$href] = true;

            $text = trim((string) ($item['text'] ?? '')) ?: (string) PlatformComponentRenderer::value('utility_string_urlto', ['input' => $url, 'action' => 'synthesize']);
            $icon = trim((string) ($item['svg'] ?? ''));
            $svg = $icon !== '' ? (string) PlatformComponentRenderer::render('svg', ['icon' => $icon, 'class' => 'project-links-icon']) : '';

            $html .= '<a class="project-links-link" href="' . self::e($href) . '" target="_blank" rel="noopener">'
                . $svg . '<span>' . self::e(rtrim($text, '/')) . '</span>'
                . (string) PlatformComponentRenderer::render('svg', ['icon' => 'new-tab', 'class' => 'project-links-external'])
                . '</a>';
        }
        if ($html === '') return '';

        $label = array_key_exists('label', $data) ? trim((string) $data['label']) : utility_project_media::text('links_label', 'Project links');
        $class = trim((string) ($data['class'] ?? ''));
        $id = trim((string) ($data['id'] ?? ''));

        return '<div class="project-links' . ($class !== '' ? ' ' . self::e($class) : '') . '"' . ($id !== '' ? ' id="' . self::e($id) . '"' : '') . '>'
            . ($label !== '' ? '<span class="project-links-label text-label">' . self::e($label) . '</span>' : '')
            . '<div class="project-links-list">' . $html . '</div></div>';
    }

    private static function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
