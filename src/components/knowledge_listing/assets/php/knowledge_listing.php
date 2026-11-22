<?php

class knowledge_listing
{
    private const ALLOWED_TAGS = '<br><b><strong><i><em>';

    public static function render(array $data = []): string
    {
        $title = self::str($data['title'] ?? '');
        $html = [
            'title' => $title !== '' ? '<h2 class="title-card title-divider font-secondary' . (self::str($data['title_class'] ?? '') !== '' ? ' ' . self::e(self::str($data['title_class'])) : '') . '">' . self::e($title) . '</h2>' : '',
            'text' => self::text($data['text'] ?? ''),
            'groups' => self::groups($data['groups'] ?? []),
            'icons' => ($data['icons'] ?? true) === false ? '' : self::icons($data),
            'buttons' => self::buttons($data['buttons'] ?? []),
        ];
        if (trim(implode('', $html)) === '') return '';

        $class = self::str($data['class'] ?? '');
        $id = self::str($data['id'] ?? '');

        return PlatformTemplateRenderer::render(__DIR__ . '/../html/template.html', $html + [
            'extra_class' => $class !== '' ? ' ' . self::e($class) : '',
            'id_attr' => $id !== '' ? ' id="' . self::e($id) . '"' : '',
        ]);
    }

    private static function text(mixed $text): string
    {
        $html = '';
        foreach ((array) $text as $p) {
            $p = is_scalar($p) ? trim(strip_tags((string) $p, self::ALLOWED_TAGS)) : '';
            if ($p !== '') $html .= '<p class="knowledge-listing-text">' . $p . '</p>';
        }
        return $html;
    }

    private static function groups(mixed $groups): string
    {
        if (!is_array($groups)) return '';
        $html = '';
        foreach ($groups as $group) {
            if (!is_array($group)) continue;
            $items = '';
            foreach ((array) ($group['list'] ?? $group['text_list'] ?? []) as $item) {
                if (is_scalar($item) && trim((string) $item) !== '') $items .= '<li>' . self::item(trim((string) $item)) . '</li>';
            }
            $title = self::str($group['title'] ?? '');
            if ($items === '' && $title === '') continue;
            $html .= '<div class="knowledge-group">'
                . ($title !== '' ? '<p class="knowledge-group-title">' . self::group_icon($group) . '<span>' . self::e($title) . '</span></p>' : '')
                . ($items !== '' ? '<ul>' . $items . '</ul>' : '') . '</div>';
        }
        return $html === '' ? '' : '<div class="knowledge-groups">' . $html . '</div>';
    }

    private static function item(string $item): string
    {
        $pos = strpos($item, ': ');
        if ($pos === false || $pos > 40) return self::e($item);
        return '<span class="knowledge-item-label">' . self::e(substr($item, 0, $pos + 1)) . '</span> ' . self::e(substr($item, $pos + 2));
    }

    private static function group_icon(array $group): string
    {
        $icon = self::str($group['svg'] ?? '');
        if ($icon === '') return '';
        $svg = (string) PlatformComponentRenderer::render('svg', ['icon' => $icon]);
        return trim($svg) === '' ? '' : '<span class="knowledge-group-icon" aria-hidden="true">' . $svg . '</span>';
    }

    private static function icons(array $data): string
    {
        $type = self::str($data['icons_type'] ?? '') ?: 'knowledge_item';
        $items = PlatformDataService::get_collection($type) ?? [];
        if (!is_array($items)) return '';
        $items = self::sort_icons($items);

        $html = '';
        foreach ($items as $item) {
            if (!is_array($item) || ($item['settings']['render'] ?? true) === false) continue;
            $icon = self::str($item['svg'] ?? '');
            if ($icon === '') continue;
            $text = self::str($item['text'] ?? '');
            $svg = (string) PlatformComponentRenderer::render('svg', ['icon' => $icon, 'class' => 'knowledge-icon']);
            if (trim($svg) === '') continue;

            $url = self::str($item['external_url'] ?? '');
            $label = $text !== '' ? ' title="' . self::e($text) . '" aria-label="' . self::e($text) . '"' : '';
            $html .= $url !== ''
                ? '<li><a href="' . self::e($url) . '" target="_blank" rel="noopener"' . $label . '>' . $svg . '</a></li>'
                : '<li' . $label . '>' . $svg . '</li>';
        }
        if ($html === '') return '';

        $iconsTitle = self::str($data['icons_title'] ?? '');
        return ($iconsTitle !== '' ? '<p class="knowledge-group-title">' . self::e($iconsTitle) . '</p>' : '')
            . '<ul class="knowledge-icons">' . $html . '</ul>';
    }

    private static function sort_icons(array $items): array
    {
        $i = 0;
        foreach ($items as &$item) $item = ['pos' => $i++, 'item' => $item];
        unset($item);
        usort($items, function ($a, $b) {
            $oa = is_array($a['item']) && is_numeric($a['item']['settings']['order'] ?? null) ? (float) $a['item']['settings']['order'] : INF;
            $ob = is_array($b['item']) && is_numeric($b['item']['settings']['order'] ?? null) ? (float) $b['item']['settings']['order'] : INF;
            return $oa <=> $ob ?: $a['pos'] <=> $b['pos'];
        });
        return array_column($items, 'item');
    }

    private static function buttons(mixed $buttons): string
    {
        if (!is_array($buttons)) return '';
        $html = '';
        foreach ($buttons as $btn) {
            if (!is_array($btn)) continue;
            $id = self::str($btn['_id'] ?? '');
            $html .= (string) PlatformComponentRenderer::render('button', [
                'text' => self::str($btn['text'] ?? ''),
                'link' => $id !== '' ? ['post_id' => $id] : ($btn['link'] ?? ''),
                'svg' => self::str($btn['svg'] ?? ''),
                'class' => self::str($btn['class'] ?? '') ?: 'btn btn-primary',
            ]);
        }
        return $html === '' ? '' : '<div class="knowledge-listing-buttons">' . $html . '</div>';
    }

    private static function str(mixed $value): string
    {
        return is_scalar($value) ? trim((string) $value) : '';
    }

    private static function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
