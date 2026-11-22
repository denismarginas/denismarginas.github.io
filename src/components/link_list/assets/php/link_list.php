<?php

class link_list
{
    private const TITLE_TAGS = ['h2', 'h3', 'h4', 'h5'];

    public static function render(array $data = []): string
    {
        $items = '';
        foreach ((array) ($data['links'] ?? []) as $link) {
            if (!is_array($link)) continue;
            $items .= is_array($link['source'] ?? null) ? self::query_links($link) : self::link($link, null);
        }
        if ($items === '') return '';

        $title = trim((string) ($data['title'] ?? ''));
        $tag = strtolower((string) ($data['title_tag'] ?? 'h3'));
        if (!in_array($tag, self::TITLE_TAGS, true)) $tag = 'h3';

        $class = trim((string) ($data['class'] ?? ''));
        $id = trim((string) ($data['id'] ?? ''));

        return PlatformTemplateRenderer::render(__DIR__ . '/../html/template.html', [
            'extra_class' => $class !== '' ? ' ' . self::e($class) : '',
            'id_attr' => $id !== '' ? ' id="' . self::e($id) . '"' : '',
            'title' => $title !== '' ? '<' . $tag . ' class="link-list-title">' . self::title_html($title, $data['title_link'] ?? null) . '</' . $tag . '>' : '',
            'items' => $items,
        ]);
    }

    private static function title_html(string $title, mixed $titleLink): string
    {
        $href = is_array($titleLink) ? self::href($titleLink, null) : '';
        return $href !== ''
            ? '<a class="link-list-title-link" href="' . self::e($href) . '">' . self::e($title) . '</a>'
            : self::e($title);
    }

    private static function query_links(array $link): string
    {
        $source = $link['source'];
        $results = PlatformComponentRenderer::value('utility_filter', [
            'items' => self::source_items($source),
            'filter_by' => $source['filter_by'] ?? [],
            'exclude_by' => $source['exclude_by'] ?? [],
            'sort' => $source['sort'] ?? null,
            'max_items' => $source['max_items'] ?? 0,
        ]);
        if (!is_array($results)) return '';

        $html = '';
        foreach ($results as $result) {
            $html .= self::link($link, $result);
        }
        return $html;
    }

    private static function source_items(array $source): array
    {
        if (($name = trim((string) ($source['collection'] ?? ''))) !== '') {
            $items = PlatformDataService::get_collection($name);
        } elseif (($name = trim((string) ($source['post'] ?? ''))) !== '') {
            $items = PlatformDataService::get_all_posts_from_file($name);
        } elseif (($name = trim((string) ($source['taxonomy'] ?? ''))) !== '') {
            $items = PlatformDataService::get_data('taxonomy_' . $name);
        } else {
            $items = null;
        }
        return is_array($items) ? $items : [];
    }

    private static function link(array $link, ?array $result): string
    {
        $text = trim((string) ($link['text'] ?? ''));
        if ($text === '' && $result !== null) {
            $text = trim((string) (utility_filter::value_at($result, 'seo.title') ?? ''));
        }

        $href = self::href($link, $result);
        if ($text === '' || $href === '') return '';

        $attrs = ' href="' . self::e($href) . '"';
        $target = trim((string) ($link['target'] ?? ''));
        if ($target !== '') {
            $attrs .= ' target="' . self::e($target) . '"' . ($target === '_blank' ? ' rel="noopener"' : '');
        }
        if (($link['download'] ?? false) === true) $attrs .= ' download';

        $class = trim((string) ($link['class'] ?? ''));
        $icon = trim((string) ($link['svg'] ?? '')) !== ''
            ? (string) PlatformComponentRenderer::render('svg', ['icon' => (string) $link['svg'], 'class' => 'link-list-icon'])
            : '';

        $html = '<a class="link-list-link' . ($class !== '' ? ' ' . self::e($class) : '') . '"' . $attrs . '>'
            . $icon . '<span class="link-list-text">' . self::e($text) . '</span></a>';

        return '<li>' . self::with_popup($html, $link['popup'] ?? null) . '</li>';
    }

    private static function with_popup(string $linkHtml, mixed $popup): string
    {
        if (!is_array($popup) || trim((string) ($popup['component'] ?? '')) === '') return $linkHtml;

        $html = (string) PlatformComponentRenderer::render('popup', [
            'trigger' => $linkHtml,
            'content_component' => ['component' => $popup['component'], 'data' => $popup['data'] ?? []],
            'hash' => $popup['hash'] ?? '',
            'width' => $popup['width'] ?? '',
            'panel' => true,
            'inline' => true,
        ]);
        return $html !== '' ? $html : $linkHtml;
    }

    private static function href(array $link, ?array $result): string
    {
        $anchor = trim((string) ($link['anchor'] ?? ''), " #");
        $anchor = $anchor !== '' ? '#' . rawurlencode($anchor) : '';

        $path = trim((string) ($link['link'] ?? ''));
        if ($result !== null && $path !== '') {
            $value = utility_filter::value_at($result, $path);
            return is_string($value) && trim($value) !== '' ? self::asset_url($value) . $anchor : '';
        }

        $id = $result !== null ? (string) ($result['_id'] ?? '') : trim((string) ($link['_id'] ?? ''));
        if ($id !== '') return PlatformPathService::post_link($id) . $anchor;

        $url = trim((string) ($link['url'] ?? ''));
        return $url !== '' ? self::asset_url($url) . $anchor : ($anchor !== '' ? $anchor : '');
    }

    private static function asset_url(string $path): string
    {
        $path = trim($path);
        if ($path === '' || PlatformUrlService::is_external_url($path) || preg_match('#^(mailto:|tel:|\#)#i', $path)) return $path;
        return rtrim(PlatformPathService::asset_relative_prefix(), '/') . '/' . ltrim($path, '/');
    }

    private static function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
