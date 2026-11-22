<?php

require_once __DIR__ . '/../../../utility_project_media/assets/php/utility_project_media.php';
require_once __DIR__ . '/../../../section_head/assets/php/section_head.php';

class screens_viewer
{
    private static int $instance = 0;

    public static function render(array $data = []): string
    {
        $post = is_array($data['post_current_data'] ?? null) ? $data['post_current_data'] : [];
        if (empty($post)) return '';

        $groups = self::groups($post, ($data['content_website'] ?? true) !== false);
        $items = self::items($groups);
        if ($items === []) return '';

        $deviceParams = is_array($data['device_params'] ?? null) ? $data['device_params'] : [];
        $webDir = utility_project_media::IMG_DIR . utility_project_media::path($post) . '/web/';
        $rel = fn (string $src): string => str_starts_with($src, $webDir) ? substr($src, strlen($webDir)) : '';
        $pairs = '';
        $first = '';
        foreach ($items as $i => $item) {
            $card = (string) PlatformComponentRenderer::render('card_post_project_visual_devices_website', array_merge($deviceParams, [
                'post_current_data' => $post,
                'link' => false,
                'screen_path' => 'web',
                'screen_desktop' => $rel($item['pair_desktop']),
                'screen_phone' => $rel($item['pair_phone']),
            ]));
            if ($i === 0) $first = $card;
            $pairs .= '<template data-screens-pair="' . $i . '">' . $card . '</template>';
        }

        $postKey = preg_replace('/[^a-z0-9\-]/', '', strtolower((string) ($post['_id'] ?? 'post')));
        $group = 'screens-viewer-' . $postKey . '-' . (++self::$instance);

        $thumbs = '';
        $source = '';
        $total = count($items);
        $overlay = (string) PlatformComponentRenderer::render('overlay_noise', []);
        foreach ($items as $i => $item) {
            $device = (string) PlatformComponentRenderer::render('card_post_project_visual_devices_website', array_merge($deviceParams, [
                'post_current_data' => $post,
                'render_media_image' => $item['src'],
                'render_media_device_type' => $item['device'],
                'link' => false,
            ]));
            $device = str_replace('<img ', '<img loading="lazy" ', $device);
            $thumbs .= '<button type="button" class="screens-viewer-thumb screens-viewer-thumb-' . $item['device'] . '" role="listitem"'
                . ' data-screens-index="' . $i . '"'
                . ' data-title="' . self::e($item['title']) . '" data-sub="' . self::e($item['sub']) . '"'
                . ' aria-label="' . self::e($item['title']) . '"'
                . ($i === 0 ? ' aria-current="true"' : '') . '>'
                . '<span class="screens-viewer-thumb-shot">' . $overlay . $device . '</span>'
                . '<span class="screens-viewer-thumb-label text-label"><b>' . str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) . '</b> ' . self::e($item['short']) . '</span>'
                . '</button>';
            $source .= '<span class="screens-viewer-source-item" data-popup="true" data-popup-group="' . self::e($group) . '">'
                . PlatformComponentRenderer::render('image', ['src' => $item['src'], 'alt' => $item['title'], 'lazy' => true])
                . '</span>';
        }

        $desktopCount = count(array_filter($items, fn ($i) => $i['device'] === 'desktop'));
        $phoneCount = $total - $desktopCount;
        $vars = ['{total}' => (string) $total, '{desktop}' => (string) $desktopCount, '{phone}' => (string) $phoneCount];

        $head = (string) PlatformComponentRenderer::render('section_head', [
            'kicker' => self::opt($data, 'kicker', utility_project_media::text('screens_kicker', 'Screens')),
            'title' => self::opt($data, 'title', utility_project_media::text('screens_title', 'Website <em>screens</em>')),
            'text' => strtr(self::opt($data, 'text', $phoneCount > 0
                ? utility_project_media::text('screens_text', '{desktop} desktop and {phone} mobile views, shown in pairs. Pick a screen from the rail; open the stage for the full capture.')
                : utility_project_media::text('screens_text_desktop', '{desktop} desktop views. Pick a screen from the rail; open the stage for the full capture.')), $vars),
            'count' => strtr(self::opt($data, 'count', utility_project_media::text('screens_count', '{total} views')), $vars),
        ]);

        $viewer = PlatformTemplateRenderer::render([
            'group' => self::e($group),
            'open_label' => self::e(utility_project_media::text('screens_open', 'Open the full capture')),
            'stage' => $first,
            'counter' => '01 / ' . str_pad((string) $total, 2, '0', STR_PAD_LEFT),
            'title' => self::e($items[0]['title']),
            'sub' => self::e($items[0]['sub']),
            'open_text' => self::e(strtr(utility_project_media::text('screens_view_all', 'View all {total} screens'), $vars)) . ' &nearr;',
            'prev_label' => self::e(utility_project_media::text('previous', 'Previous')),
            'next_label' => self::e(utility_project_media::text('next', 'Next')),
            'thumbs' => $thumbs,
            'pairs' => $pairs,
            'source' => $source,
        ]);

        return (string) PlatformComponentRenderer::render('section', [
            'id' => array_key_exists('id', $data) ? trim((string) $data['id']) : 'screens',
            'class' => 'project-section screens-viewer-section',
            'variant' => $data['variant'] ?? 'panel',
            'children' => $head . $viewer,
        ]);
    }

    private static function groups(array $post, bool $contentWebsite): array
    {
        $groups = [[
            'label' => '',
            'desktop' => utility_project_media::screens($post, 'desktop'),
            'phone' => utility_project_media::screens($post, 'phone'),
        ]];
        if ($contentWebsite) {
            $base = utility_project_media::IMG_DIR . utility_project_media::path($post) . '/web/content-website';
            $abs = utility_project_media::absolute($base);
            $folders = is_dir($abs) ? array_values(array_filter(scandir($abs) ?: [], fn ($n) => $n[0] !== '.' && is_dir($abs . '/' . $n))) : [];
            natcasesort($folders);
            foreach ($folders as $folder) {
                $groups[] = [
                    'label' => utility_project_media::label($folder),
                    'desktop' => utility_project_web_screens::images($base . '/' . $folder . '/desktop/'),
                    'phone' => utility_project_web_screens::images($base . '/' . $folder . '/phone/'),
                ];
            }
        }
        return array_values(array_filter($groups, fn ($g) => $g['desktop'] || $g['phone']));
    }

    private static function items(array $groups): array
    {
        $desktopText = utility_project_media::text('screens_desktop', 'Desktop');
        $phoneText = utility_project_media::text('screens_phone', 'Mobile');
        $pairedText = utility_project_media::text('screens_paired', 'paired with');

        $items = [];
        foreach ($groups as $group) {
            $prefix = $group['label'] !== '' ? $group['label'] . ' · ' : '';
            foreach (['desktop', 'phone'] as $device) {
                $other = $device === 'desktop' ? 'phone' : 'desktop';
                foreach ($group[$device] as $i => $src) {
                    $pair = $group[$other] ? $group[$other][$i % count($group[$other])] : '';
                    $pairIndex = $group[$other] ? ($i % count($group[$other])) + 1 : 0;
                    $label = $device === 'desktop' ? $desktopText : $phoneText;
                    $otherLabel = $other === 'desktop' ? $desktopText : $phoneText;
                    $num = str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT);
                    $items[] = [
                        'device' => $device,
                        'src' => $src,
                        'pair_desktop' => $device === 'desktop' ? $src : $pair,
                        'pair_phone' => $device === 'phone' ? $src : $pair,
                        'title' => $prefix . $label . ' ' . $num,
                        'short' => $label,
                        'sub' => $pair !== ''
                            ? $pairedText . ' ' . strtolower($otherLabel) . ' ' . str_pad((string) $pairIndex, 2, '0', STR_PAD_LEFT)
                            : '',
                    ];
                }
            }
        }
        return $items;
    }

    private static function opt(array $data, string $key, string $default): string
    {
        return array_key_exists($key, $data) ? trim((string) $data[$key]) : $default;
    }

    private static function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
