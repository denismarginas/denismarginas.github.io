<?php

class tab_items_listing_label
{
    public static function render_label(string $itemId, array $item, array $data): string
    {
        $itemData = is_array($item['data'] ?? null) ? $item['data'] : $item;
        $education = $itemData['education'] ?? [];
        $media = $itemData['media'] ?? [];
        $logo = $media['logo'] ?? [];

        $labelText = (string) ($education['type'] ?? '');
        if ($labelText === '') {
            $labelText = tab_items_listing_utility::item_title($item);
        }

        $badgeImg = '';
        $src = (string) ($logo['img_min'] ?? $logo['img'] ?? '');
        if ($src !== '') {
            $badgeImg = PlatformComponentRenderer::render('image', [
                'src' => $src,
                'alt' => $labelText,
                'class' => 'dm-tab-items-listing__badge-img',
            ]);
        }

        $badgeLayout = (string) ($logo['bg'] ?? 'light');

        return PlatformTemplateRenderer::render(__DIR__ . '/../../../html/parts/label.html', [
            'item_id' => htmlspecialchars($itemId, ENT_QUOTES, 'UTF-8'),
            'badge_img' => $badgeImg,
            'badge_layout' => htmlspecialchars($badgeLayout, ENT_QUOTES, 'UTF-8'),
            'label_text' => htmlspecialchars($labelText, ENT_QUOTES, 'UTF-8'),
        ]);
    }
}
