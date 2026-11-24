<?php

class website_info
{
    public static function render(array $data = []): string
    {
        $source = trim((string) ($data['source'] ?? '')) ?: 'content_project_info';
        $file = PlatformDataService::get_data($source) ?? [];
        $cfg = array_merge(is_array($file) ? $file : [], array_diff_key($data, array_flip(['children', 'post_current_data'])));

        $datesHtml = self::dates_button($cfg, $source);

        $cookie = $cfg['cookie_settings'] ?? false;
        $cookieHtml = $cookie === false || $cookie === null || $cookie === '' || $cookie === 'false' ? ''
            : (string) PlatformComponentRenderer::render('cookie_settings', is_array($cookie) ? $cookie : []);

        $startHtml = $cookieHtml . $datesHtml;
        if ($startHtml !== '') $startHtml = '<div class="website-info-start">' . $startHtml . '</div>';

        $copyrightHtml = self::copyright($cfg['copyright'] ?? '');

        if ($startHtml === '' && $copyrightHtml === '') return '';

        $class = trim((string) ($cfg['class'] ?? ''));
        $id = trim((string) ($cfg['id'] ?? ''));

        return PlatformTemplateRenderer::render(__DIR__ . '/../html/template.html', [
            'extra_class' => $class !== '' ? ' ' . self::e($class) : '',
            'id_attr' => $id !== '' ? ' id="' . self::e($id) . '"' : '',
            'dates' => $startHtml,
            'copyright' => $copyrightHtml,
        ]);
    }

    private static function dates_button(array $cfg, string $source): string
    {
        $dates = array_filter((array) ($cfg['dates'] ?? []), fn ($d) => is_array($d) && (trim((string) ($d['date'] ?? '')) !== '' || trim((string) ($d['field'] ?? '')) !== ''));
        $project = trim((string) ($cfg['project'] ?? ''));
        if ($dates === [] && $project === '') return '';

        $label = trim((string) ($cfg['dates_label'] ?? '')) ?: PlatformTextService::ui('website_info_dates', 'Website info');
        $icon = array_key_exists('dates_svg', $cfg) ? trim((string) $cfg['dates_svg']) : 'project-details';
        $svg = $icon !== '' ? PlatformComponentRenderer::render('svg', ['icon' => $icon, 'class' => 'website-info-dates-icon']) : '';

        $prefix = PlatformPathService::asset_relative_prefix();
        $name = preg_replace('/^data_/', '', $source);
        $folder = strtok($name, '_') ?: 'content';
        $projectType = trim((string) ($cfg['project_type'] ?? '')) ?: 'project';

        $attrs = ' data-src="' . self::e($prefix . self::relative('data_dir', 'src/content/json/data') . '/') . '"'
            . ' data-file="' . self::e($folder . '/data_' . $name . '.json') . '"'
            . ' data-svg="' . self::e($prefix . self::relative('svg_dir', 'src/content/svg') . '/') . '"'
            . ' data-default-lang="' . self::e(PlatformDataService::default_language()) . '"';
        if ($project !== '') {
            $attrs .= ' data-project="' . self::e($project) . '" data-project-file="' . self::e('post/data_post_' . $projectType . '.json') . '"';
        }

        $popoverId = 'website-info-dates-' . substr(md5($source), 0, 6);

        return '<span class="website-info-dates-wrap" data-website-info-dates' . $attrs . '>'
            . '<button class="website-info-dates-button" type="button" aria-expanded="false" aria-controls="' . $popoverId . '"'
            . ' aria-label="' . self::e($label) . '" title="' . self::e($label) . '">' . $svg . '</button>'
            . '<span class="website-info-dates-popover" id="' . $popoverId . '" role="dialog" aria-label="' . self::e($label) . '" hidden></span>'
            . '</span>';
    }

    private static function relative(string $key, string $fallback): string
    {
        $dir = str_replace('\\', '/', (string) PlatformConfig::get($key));
        $root = str_replace('\\', '/', (string) PlatformConfig::get('root_dir'));
        return trim($root !== '' && str_starts_with($dir, $root) ? substr($dir, strlen($root)) : $fallback, '/');
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

    private static function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
