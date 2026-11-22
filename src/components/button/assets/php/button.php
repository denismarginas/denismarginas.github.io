<?php

class button
{
    public static function render(array $data = []): string
    {
        $text = (string) ($data['text'] ?? '');
        $link = self::resolve_link($data);
        $icon = (string) ($data['svg'] ?? '');
        if (($text === '' && $icon === '') || $link === '') return '';

        $class = (string) ($data['class'] ?? 'btn btn-primary');

        $svg = '';
        if ($icon !== '') {
            $svg = PlatformComponentRenderer::render('svg', [
                'icon' => $icon,
                'class' => 'btn-icon',
            ]);
        }

        $attrs = '';
        $target = (string) ($data['target'] ?? '');
        if ($target !== '') {
            $attrs .= ' target="' . htmlspecialchars($target, ENT_QUOTES, 'UTF-8') . '"';
        }
        $rel = (string) ($data['rel'] ?? '');
        if ($rel !== '') {
            $attrs .= ' rel="' . htmlspecialchars($rel, ENT_QUOTES, 'UTF-8') . '"';
        }

        $textHtml = $text !== '' ? '<span class="btn-text">' . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . '</span>' : '';

        if (($data['svg_position'] ?? '') === 'end') {
            $textHtml .= $svg;
            $svg = '';
        }

        return PlatformTemplateRenderer::render([
            'class' => htmlspecialchars($class, ENT_QUOTES, 'UTF-8'),
            'href' => htmlspecialchars($link, ENT_QUOTES, 'UTF-8'),
            'text_html' => $textHtml,
            'svg' => $svg,
            'attrs' => $attrs,
        ]);
    }

    public static function resolve_link(array $data): string
    {
        $link = $data['link'] ?? '';

        if (is_string($link) && $link !== '') {
            return $link;
        }

        if (is_array($link)) {
            $postId = (string) ($link['post_id'] ?? $link['_id'] ?? $link['slug'] ?? '');
            if ($postId !== '') {
                return PlatformPathService::post_link($postId);
            }
        }

        return '';
    }
}
