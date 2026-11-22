<?php

class simplelink_constructor
{
    public static function render(array $data = []): string
    {
        $link = $data['link'] ?? '';
        if ($link === '') {
            return '';
        }

        $url = self::resolve_url($link);

        $text = $data['text'] ?? '';
        if ($text === '') {
            return $url;
        }

        $attrs = '';
        if (!empty($data['class'])) {
            $attrs .= ' class="' . htmlspecialchars($data['class'], ENT_QUOTES, 'UTF-8') . '"';
        }
        if (!empty($data['target'])) {
            $attrs .= ' target="' . htmlspecialchars($data['target'], ENT_QUOTES, 'UTF-8') . '"';
        }

        return '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '"' . $attrs . '>'
             . htmlspecialchars($text)
             . '</a>';
    }

    public static function resolve_url(string $link): string
    {
        if (preg_match('#^https?://#i', $link)) {
            return $link;
        }

        return PlatformPathService::post_link($link);
    }
}

