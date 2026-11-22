<?php

class social_links
{
    private const LISTS = ['visual', 'text'];
    private const TITLE_TAGS = ['h2', 'h3', 'h4', 'h5'];

    public static function render(array $data = []): string
    {
        $type = trim((string) ($data['type'] ?? '')) ?: 'social_link';
        $items = PlatformDataService::get_collection($type) ?? [];
        if (!is_array($items)) return '';
        $items = PlatformComponentRenderer::value('utility_filter', [
            'items' => $items,
            'filter_by' => $data['filter_by'] ?? [],
            'exclude_by' => $data['exclude_by'] ?? [],
            'sort' => $data['sort'] ?? null,
        ]);
        if (!is_array($items)) return '';

        $lists = array_values(array_intersect((array) ($data['lists'] ?? self::LISTS), self::LISTS));

        $visual = in_array('visual', $lists, true) ? self::visual($items) : '';
        $text = in_array('text', $lists, true) ? self::text($items) : '';
        if ($visual === '' && $text === '') return '';

        if ($lists === ['text', 'visual']) [$visual, $text] = [$text, $visual];

        $title = trim((string) ($data['title'] ?? ''));
        $tag = strtolower((string) ($data['title_tag'] ?? 'h3'));
        if (!in_array($tag, self::TITLE_TAGS, true)) $tag = 'h3';

        $class = trim((string) ($data['class'] ?? ''));
        $id = trim((string) ($data['id'] ?? ''));
        $variant = strtolower((string) ($data['variant'] ?? 'card')) === 'plain' ? 'plain' : 'card';

        return PlatformTemplateRenderer::render(__DIR__ . '/../html/template.html', [
            'extra_class' => $class !== '' ? ' ' . self::e($class) : '',
            'id_attr' => $id !== '' ? ' id="' . self::e($id) . '"' : '',
            'variant' => $variant,
            'variant_class' => $variant === 'card' ? ' bg-radial-primary' : '',
            'title' => $title !== '' ? '<' . $tag . ' class="social-links-title' . (trim((string) ($data['title_class'] ?? '')) !== '' ? ' ' . self::e(trim((string) $data['title_class'])) : '') . '">' . self::e($title) . '</' . $tag . '>' : '',
            'visual' => $visual,
            'text' => $text,
        ]);
    }

    private static function visual(array $items): string
    {
        $html = '';
        foreach ($items as $item) {
            $d = $item['data'] ?? [];
            if (($d['visual_list']['render'] ?? false) !== true) continue;
            $link = trim((string) ($d['external_link'] ?? ''));
            if ($link === '') continue;

            $title = trim((string) ($d['seo']['title'] ?? ''));
            $icon = (string) PlatformComponentRenderer::render('svg', [
                'icon' => (string) ($d['svg'] ?? ''),
                'class' => 'social-links-icon',
            ]);

            $html .= '<li><a class="social-links-visual-link" href="' . self::e($link) . '" target="_blank" rel="noopener" title="' . self::e($title) . '" aria-label="' . self::e($title) . '">'
                . ($icon !== '' ? $icon : '<span>' . self::e($title) . '</span>') . '</a></li>';
        }
        return $html === '' ? '' : '<ul class="social-links-visual">' . $html . '</ul>';
    }

    private static function text(array $items): string
    {
        $pos = 0;
        $keyed = array_map(function ($item) use (&$pos) {
            $order = $item['data']['text_list']['order'] ?? null;
            return [is_numeric($order) ? (float) $order : PHP_FLOAT_MAX, $pos++, $item];
        }, array_values($items));
        usort($keyed, fn ($a, $b) => [$a[0], $a[1]] <=> [$b[0], $b[1]]);
        $items = array_column($keyed, 2);

        $html = '';
        foreach ($items as $item) {
            $d = $item['data'] ?? [];
            $t = is_array($d['text_list'] ?? null) ? $d['text_list'] : [];
            if (($t['render'] ?? false) !== true) continue;

            $link = trim((string) ($t['external_link'] ?? $d['external_link'] ?? ''));
            if ($link === '') continue;
            $title = trim((string) ($t['title'] ?? $d['seo']['title'] ?? ''));
            $text = trim((string) ($t['text'] ?? preg_replace('#^https?://(www\.)?#', '', rtrim($link, '/'))));

            $icon = trim((string) ($d['svg'] ?? '')) !== '' ? (string) PlatformComponentRenderer::render('svg', [
                'icon' => (string) $d['svg'],
                'class' => 'social-links-icon',
            ]) : '';
            $arrow = (string) PlatformComponentRenderer::render('svg', ['icon' => 'new-tab', 'class' => 'social-links-arrow']);

            $html .= '<li><a class="social-links-text-link" href="' . self::e($link) . '" target="_blank" rel="noopener">'
                . ($icon !== '' ? '<span class="social-links-badge" aria-hidden="true">' . $icon . '</span>' : '<span class="social-links-dot" aria-hidden="true"></span>')
                . '<span class="social-links-text-body">'
                . '<b>' . self::e(rtrim($title, ': ')) . '</b>'
                . '<span class="social-links-text-value">' . self::e($text) . '</span>'
                . '</span>' . $arrow . '</a></li>';
        }
        return $html === '' ? '' : '<ul class="social-links-text">' . $html . '</ul>';
    }

    private static function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
