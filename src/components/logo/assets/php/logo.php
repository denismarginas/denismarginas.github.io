<?php

class logo
{
    private const VARIANTS = ['header', 'footer'];
    private const SHAPES = ['circle', 'square'];

    public static function render(array $data = []): string
    {
        $settings = PlatformDataService::get_data('settings_site') ?? [];
        $siteTitle = trim((string) ($settings['site_title'] ?? ''));

        $parts = array_values(array_intersect((array) ($data['parts'] ?? ['visual', 'text']), ['visual', 'text']));
        $visual = in_array('visual', $parts, true) ? self::visual((string) ($settings['logo']['site_logo_img'] ?? ''), $siteTitle) : '';
        $text = in_array('text', $parts, true) ? self::text($siteTitle) : '';
        if ($visual === '' && $text === '') return '';

        $variant = strtolower((string) ($data['variant'] ?? 'header'));
        $shape = strtolower((string) ($data['shape'] ?? 'circle'));
        $class = trim((string) ($data['class'] ?? ''));

        return PlatformTemplateRenderer::render(__DIR__ . '/../html/template.html', [
            'logo_url' => self::e(self::url($data['link'] ?? null)),
            'shape' => in_array($shape, self::SHAPES, true) ? $shape : 'circle',
            'extra_class' => $class !== '' ? ' ' . self::e($class) : '',
            'variant' => in_array($variant, self::VARIANTS, true) ? $variant : 'header',
            'aria_label' => $text === '' && $siteTitle !== '' ? ' aria-label="' . self::e($siteTitle) . '"' : '',
            'visual_html' => $visual,
            'text_html' => $text,
        ]);
    }

    private static function visual(string $logoImg, string $siteTitle): string
    {
        $src = self::resolve_logo_img($logoImg);
        if ($src === '') return '';

        $img = (string) PlatformComponentRenderer::render('image', [
            'src' => $src,
            'alt' => $siteTitle !== '' ? $siteTitle . ' Logo' : '',
            'class' => 'logo-img',
            'width' => '50',
            'height' => '50',
        ]);
        return $img === '' ? '' : '<div class="logo-container-img">' . $img . '</div>';
    }

    private static function text(string $siteTitle): string
    {
        if ($siteTitle === '') return '';

        $parts = preg_split('/\s+/', $siteTitle, 2);
        $html = '<span class="primary">' . self::e($parts[0]) . '</span>';
        if (isset($parts[1]) && $parts[1] !== '') {
            $html .= '<span class="secondary">' . self::e($parts[1]) . '</span>';
        }
        return '<div class="site-title">' . $html . '</div>';
    }

    private static function url(mixed $link): string
    {
        if (is_array($link)) {
            $id = trim((string) ($link['_id'] ?? ''));
            if ($id !== '') return PlatformPathService::post_link($id);
            $url = trim((string) ($link['url'] ?? ''));
            if ($url !== '') return $url;
        }
        return PlatformPathService::front_page_link();
    }

    public static function resolve_logo_img(string $logoImg): string
    {
        if ($logoImg === '') return '';

        $raw = ltrim($logoImg, '/');
        if (PlatformUrlService::is_external_url($raw)) return $logoImg;

        $candidates = [];
        if (strpos($raw, 'src/content/') !== 0) {
            $candidates[] = 'src/content/img/' . $raw;
        }
        $candidates[] = $raw;

        foreach ($candidates as $candidate) {
            $absolute = defined('ENGINE_PROJECT_ROOT') ? ENGINE_PROJECT_ROOT . '/' . $candidate : $candidate;
            if (file_exists($absolute)) return $candidate;
        }
        return '';
    }

    private static function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
