<?php

class block_workstation_listing
{
    public static function render(array $data = []): string
    {
        $type = self::str($data['type'] ?? '') ?: 'workstation';
        $items = PlatformDataService::get_all_posts_from_file($type);
        if (!is_array($items) || $items === []) return '';
        $items = array_values(array_filter($items, fn ($i) => is_array($i) && ($i['settings']['render'] ?? true) !== false));

        $ids = is_array($data['ids'] ?? null) ? array_values(array_filter(array_map([self::class, 'str'], $data['ids']))) : [];
        if ($ids !== []) {
            $byId = [];
            foreach ($items as $item) $byId[self::str($item['_id'] ?? '')] = $item;
            $items = array_values(array_filter(array_map(fn ($id) => $byId[$id] ?? null, $ids)));
        }

        $cards = [];
        foreach ($items as $item) {
            $card = (string) PlatformComponentRenderer::render('block_workstation_card', ['item' => $item]);
            if (trim($card) !== '') $cards[] = '<li>' . $card . '</li>';
        }
        if ($cards === []) return '';

        $columns = (int) ($data['columns'] ?? 2);
        $class = self::str($data['class'] ?? '');
        $id = self::str($data['id'] ?? '');

        return PlatformTemplateRenderer::render([
            'extra_class' => $class !== '' ? ' ' . self::e($class) : '',
            'id_attr' => $id !== '' ? ' id="' . self::e($id) . '"' : '',
            'grid_attrs' => (string) PlatformComponentRenderer::value('utility_grid_listing', ['count' => count($cards), 'max' => $columns > 0 ? $columns : 2]),
            'items' => implode('', $cards),
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
