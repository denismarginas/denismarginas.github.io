<?php

require_once __DIR__ . '/parts/project_device_render_conditions.php';
require_once __DIR__ . '/parts/project_desktop_layout_style.php';
require_once __DIR__ . '/parts/project_device_models.php';
require_once __DIR__ . '/parts/project_device_blocks.php';
require_once __DIR__ . '/../../../utility_project_web_screens/assets/php/utility_project_web_screens.php';

class card_post_project_visual_devices_website
{
    private const DEFAULT_SCREEN_PATH = 'web/website/';

    public static function render(array $data = []): string
    {
        $post = is_array($data['post_current_data'] ?? null) ? $data['post_current_data'] : [];
        if (empty($post)) {
            return '';
        }

        $postData = is_array($post['data'] ?? null) ? $post['data'] : [];

        $models = project_device_models::resolve($data, $postData);
        $frames = project_device_models::frames($models);

        $mediaImage = trim((string) ($data['render_media_image'] ?? ''));
        if ($mediaImage === '') {
            $screens = self::resolve_screens($data, (string) ($postData['media']['path'] ?? ''));
        } else {
            $type = strtolower(trim((string) ($data['render_media_device_type'] ?? ''))) === 'phone' ? 'phone' : 'desktop';
            $path = str_starts_with($mediaImage, 'src/') ? $mediaImage : 'src/content/img/projects/' . ltrim($mediaImage, '/');
            $screens = ['desktop' => $type === 'desktop' ? $path : '', 'phone' => $type === 'phone' ? $path : ''];
        }

        $desktopBlock = project_device_blocks::desktop($screens['desktop'], $frames['desktop'], $models['desktop'], self::flag($data, 'desktop_layout_style'));
        $phoneBlock = project_device_blocks::phone($screens['phone'], $frames['phone'], $models['phone'], self::flag($data, 'phone_layout_style'));

        if ($desktopBlock === '' && $phoneBlock === '') {
            return '';
        }

        $asLink = self::flag($data, 'link');
        $link = PlatformPathService::post_link((string) ($post['_id'] ?? ''));

        return PlatformTemplateRenderer::render(__DIR__ . '/../html/template.html', [
            'tag' => $asLink ? 'a' : 'div',
            'href_attr' => $asLink ? ' href="' . htmlspecialchars($link, ENT_QUOTES, 'UTF-8') . '"' : '',
            'both_devices_check' => ($desktopBlock !== '' && $phoneBlock !== '') ? ' desktop-and-phone-devices' : '',
            'desktop_block' => $desktopBlock,
            'phone_block' => $phoneBlock,
        ]);
    }

    private static function flag(array $data, string $key): bool
    {
        if (!array_key_exists($key, $data)) return true;
        $v = $data[$key];
        if (is_bool($v)) return $v;
        return !in_array(strtolower(trim((string) $v)), ['false', '0', 'no', 'off', ''], true);
    }

    private static function resolve_screens(array $data, string $mediaPath): array
    {
        if ($mediaPath === '') {
            return ['desktop' => '', 'phone' => ''];
        }

        $screenPath = rtrim((string) ($data['screen_path'] ?? self::DEFAULT_SCREEN_PATH), '/') . '/';
        $base = utility_project_web_screens::PROJECTS_DIR . $mediaPath . '/' . $screenPath;

        $screens = [];
        foreach (utility_project_web_screens::DEVICES as $device) {
            $fixed = trim((string) ($data['screen_' . $device] ?? ''));
            $screens[$device] = $fixed !== ''
                ? $base . ltrim($fixed, '/')
                : utility_project_web_screens::first($mediaPath, $device);
        }
        return $screens;
    }
}
