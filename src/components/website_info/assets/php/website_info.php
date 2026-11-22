<?php

class website_info
{
    private const CURRENT_DATE = ['current-date', 'curent-date', 'current_date', 'today', 'now'];

    public static function render(array $data = []): string
    {
        $source = trim((string) ($data['source'] ?? '')) ?: 'content_project_info';
        $file = PlatformDataService::get_data($source) ?? [];
        $cfg = array_merge(is_array($file) ? $file : [], array_diff_key($data, array_flip(['children', 'post_current_data'])));

        $format = trim((string) ($cfg['date_format'] ?? '')) ?: 'M j, Y';

        $datesHtml = '';
        foreach ((array) ($cfg['dates'] ?? []) as $entry) {
            if (!is_array($entry)) continue;
            $datesHtml .= self::date_line($entry, $format);
        }
        if ($datesHtml !== '') $datesHtml = '<ul class="website-info-dates">' . $datesHtml . '</ul>';

        $cookie = $cfg['cookie_settings'] ?? false;
        $cookieHtml = $cookie === false || $cookie === null || $cookie === '' || $cookie === 'false' ? ''
            : (string) PlatformComponentRenderer::render('cookie_settings', is_array($cookie) ? $cookie : []);
        if ($cookieHtml !== '') $datesHtml = '<div class="website-info-start">' . $cookieHtml . $datesHtml . '</div>';

        $copyrightHtml = self::copyright($cfg['copyright'] ?? '');

        if ($datesHtml === '' && $copyrightHtml === '') return '';

        $class = trim((string) ($cfg['class'] ?? ''));
        $id = trim((string) ($cfg['id'] ?? ''));

        return PlatformTemplateRenderer::render(__DIR__ . '/../html/template.html', [
            'extra_class' => $class !== '' ? ' ' . self::e($class) : '',
            'id_attr' => $id !== '' ? ' id="' . self::e($id) . '"' : '',
            'dates' => $datesHtml,
            'copyright' => $copyrightHtml,
        ]);
    }

    private static function copyright(mixed $copyright): string
    {
        $text = trim((string) (is_array($copyright) ? ($copyright['text'] ?? '') : $copyright));
        $link = is_array($copyright) && is_array($copyright['link'] ?? null) ? $copyright['link'] : [];

        $linkHtml = '';
        $linkText = trim((string) ($link['text'] ?? ''));
        if ($linkText !== '') {
            $id = trim((string) ($link['_id'] ?? ''));
            $url = trim((string) ($link['url'] ?? ''));
            $href = $id !== '' ? PlatformPathService::post_link($id) : ($url !== '' ? $url : PlatformPathService::front_page_link());
            $linkHtml = '<a class="website-info-copyright-link" href="' . self::e($href) . '">' . self::e($linkText) . '</a>';
        }

        if ($text === '' && $linkHtml === '') return '';
        return '<p class="website-info-copyright">' . self::e($text) . ($text !== '' && $linkHtml !== '' ? ' ' : '') . $linkHtml . '</p>';
    }

    private static function date_line(array $entry, string $format): string
    {
        $text = trim((string) ($entry['text'] ?? ''));
        $date = trim((string) ($entry['date'] ?? ''));
        if ($date === '') return '';

        $inputFormat = trim((string) ($entry['input_format'] ?? '')) ?: null;
        if (in_array(strtolower($date), self::CURRENT_DATE, true)) {
            $date = date('Y-m-d');
            $inputFormat = null;
        }

        $display = PlatformTextService::format_date($date, $format, $inputFormat);
        $iso = PlatformTextService::format_date($date, 'Y-m-d', $inputFormat);
        $datetime = preg_match('/^\d{4}-\d{2}-\d{2}$/', $iso) ? ' datetime="' . self::e($iso) . '"' : '';

        return '<li>'
            . ($text !== '' ? '<span class="website-info-label">' . self::e($text) . '</span> ' : '')
            . '<time class="website-info-date"' . $datetime . '>' . self::e($display) . '</time></li>';
    }

    private static function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
