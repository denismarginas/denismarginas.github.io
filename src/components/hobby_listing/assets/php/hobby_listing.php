<?php

class hobby_listing
{
    public static function render(array $data = []): string
    {
        $type = self::str($data['type'] ?? '') ?: 'hobby';
        $items = PlatformDataService::get_collection($type);
        if (!is_array($items) || empty($items)) return '';

        $items = array_values(array_filter($items, fn ($item) => is_array($item) && ($item['settings']['render'] ?? true) !== false));

        $ids = is_array($data['ids'] ?? null) ? array_values(array_filter(array_map([self::class, 'str'], $data['ids']))) : [];
        if (!empty($ids)) {
            $byId = [];
            foreach ($items as $item) $byId[self::str($item['_id'] ?? '')] = $item;
            $items = array_values(array_filter(array_map(fn ($id) => $byId[$id] ?? null, $ids)));
        }

        $cards = [];
        foreach ($items as $item) {
            $fields = is_array($item['data'] ?? null) ? $item['data'] : $item;
            $card = [
                'text' => self::str($fields['title'] ?? $fields['name'] ?? ''),
                'svg' => self::str($fields['svg'] ?? ''),
                'img' => self::str($fields['feature_image'] ?? $fields['img'] ?? ''),
            ];
            if (isset($fields['link'])) $card['link'] = $fields['link'];
            if ($card['text'] === '' && $card['svg'] === '') continue;
            $cards[] = $card;
        }
        if (empty($cards)) return '';

        $columns = (int) ($data['columns'] ?? 4);
        $cardsHtml = (string) PlatformComponentRenderer::render('card_mini_icon_and_text', [
            'variant' => self::str($data['variant'] ?? '') ?: 'stack',
            'columns' => $columns > 0 ? $columns : 4,
            'list_class' => 'hobby-listing-items',
            'items' => $cards,
        ]);
        if (trim($cardsHtml) === '') return '';

        $class = self::str($data['class'] ?? '');
        $id = self::str($data['id'] ?? '');

        return PlatformTemplateRenderer::render(__DIR__ . '/../html/template.html', [
            'extra_class' => $class !== '' ? ' ' . self::e($class) : '',
            'id_attr' => $id !== '' ? ' id="' . self::e($id) . '"' : '',
            'title' => (string) PlatformComponentRenderer::render('title', title::normalize($data['title'] ?? null, $data)),
            'cards' => $cardsHtml,
        ]);
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
