<?php

require_once __DIR__ . '/../../../utility_project_media/assets/php/utility_project_media.php';
require_once __DIR__ . '/../../../card_post_project_visual_devices_website/assets/php/parts/project_desktop_layout_style.php';

class project_hero_stage_website
{
    private const TEMPLATE = __DIR__ . '/../html/template.html';

    public static function render(array $data = []): string
    {
        $post = is_array($data['post_current_data'] ?? null) ? $data['post_current_data'] : [];
        $desktop = utility_project_media::screens($post, 'desktop')[0] ?? '';
        if ($desktop === '') return '';

        $title = trim((string) ($post['data']['seo']['title'] ?? ''));
        $desktopHtml = (string) PlatformComponentRenderer::render('image', ['src' => $desktop, 'alt' => $title . ' - desktop']);
        if (trim($desktopHtml) === '') return '';

        $phone = utility_project_media::screens($post, 'phone')[0] ?? '';
        $phoneImg = $phone !== '' ? (string) PlatformComponentRenderer::render('image', ['src' => $phone, 'alt' => $title . ' - mobile']) : '';
        $phoneHtml = trim($phoneImg) !== ''
            ? '<span class="project-hero-phone"><span class="project-hero-phone-screen ' . self::screen_classes('phone', $phone) . '">' . $phoneImg . '</span></span>'
            : '';

        return PlatformTemplateRenderer::render(self::TEMPLATE, [
            'href' => self::e(trim((string) ($data['href'] ?? '')) ?: '#screens'),
            'label' => self::e(utility_project_media::text('hero_see_screens', 'View the showcase')),
            'bar_title' => self::e(trim((string) ($data['bar_title'] ?? '')) ?: $title),
            'desktop' => $desktopHtml,
            'desktop_classes' => self::screen_classes('desktop', $desktop),
            'phone' => $phoneHtml,
            'tag' => self::e($phoneHtml !== ''
                ? utility_project_media::text('hero_tag_website', 'Desktop + mobile')
                : utility_project_media::text('hero_tag_desktop', 'Desktop')),
        ]);
    }

    private static function screen_classes(string $type, string $src): string
    {
        return self::e('screen ' . project_desktop_layout_style::resolve($type, $src)['classes']);
    }

    private static function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
