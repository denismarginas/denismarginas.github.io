<?php

require_once __DIR__ . '/parts/project_device_render_conditions.php';
require_once __DIR__ . '/parts/project_desktop_layout_style.php';
require_once __DIR__ . '/parts/project_device_models.php';
require_once __DIR__ . '/parts/project_device_blocks.php';
require_once __DIR__ . '/../../../utility_project_web_screens/assets/php/utility_project_web_screens.php';

class card_post_project_visual_website
{
    private const DEFAULT_SCREEN_PATH = 'web/website/';

    public static function render(array $data = []): string
    {
        $post = is_array($data['post_current_data'] ?? null) ? $data['post_current_data'] : [];
        if (empty($post)) {
            return '';
        }

        $postData = is_array($post['data'] ?? null) ? $post['data'] : [];
        $primaryColor = (string) ($post['settings']['appearance']['colors']['primary'] ?? '');
        $color = htmlspecialchars($primaryColor, ENT_QUOTES, 'UTF-8');

        $models = project_device_models::resolve($data, $postData);
        $frames = project_device_models::frames($models);
        $screens = self::resolve_screens($data, (string) ($postData['media']['path'] ?? ''));
        $screenStyle = $color !== '' ? ' style="background-color: ' . $color . ';"' : '';

        $desktopBlock = project_device_blocks::desktop($screens['desktop'], $frames['desktop'], $models['desktop'], $screenStyle, self::flag($data, 'desktop_layout_style'));
        $phoneBlock = project_device_blocks::phone($screens['phone'], $frames['phone'], $models['phone'], $screenStyle, self::flag($data, 'phone_layout_style'));

        $link = PlatformPathService::post_link((string) ($post['_id'] ?? '')) . '#webdevelopment';

        return PlatformTemplateRenderer::render(__DIR__ . '/../html/template.html', [
            'both_devices_check' => ($desktopBlock !== '' && $phoneBlock !== '') ? ' desktop-and-phone-devices' : '',
            'link' => htmlspecialchars($link, ENT_QUOTES, 'UTF-8'),
            'color_style' => $color !== '' ? ' style="--primary-color-post: ' . $color . ';"' : '',
            'desktop_block' => $desktopBlock,
            'phone_block' => $phoneBlock,
        ]);
    }

    private static function flag(array $data, string $key): bool
    {
        return !array_key_exists($key, $data) || (bool) $data[$key];
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
