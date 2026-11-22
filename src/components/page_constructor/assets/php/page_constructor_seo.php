<?php

require_once __DIR__ . '/../../../utility_post_thumbnail/assets/php/utility_post_thumbnail.php';

class page_constructor_seo
{
    public static function implicit_seo_fields(): array
    {
        $fields = [];
        $global = PlatformDataService::get_global_settings();

        $site_name = $global['name'] ?? '';
        $description = $global['site_description'] ?? '';
        $favicon = $global['favicon'] ?? '';

        if (!empty($favicon)) {
            if (str_starts_with($favicon, 'http')) {
                $favicon_url = $favicon;
            } else {
                $favicon_url = PlatformPathService::asset_relative_prefix() . ltrim($favicon, '/');
            }
            $fields[] = '<link rel="icon" type="image/x-icon" href="' . htmlspecialchars($favicon_url) . '">';
        }
        if (!empty($site_name)) {
            $fields[] = '<meta name="application-name" content="' . htmlspecialchars($site_name) . '">';
        }
        if (!empty($description)) {
            $fields[] = '<meta name="description" content="' . htmlspecialchars($description) . '">';
        }
        $fields[] = '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
        $fields[] = '<meta charset="UTF-8">';

        $verification = $global['google_site_verification'] ?? '';
        if (!empty($verification)) {
            $fields[] = '<meta name="google-site-verification" content="' . htmlspecialchars($verification) . '">';
        }

        return $fields;
    }

    public static function share_image_fields(mixed $post): string
    {
        if (!is_array($post) || empty($post)) return '';
        $path = utility_post_thumbnail::get($post, 'default');
        if ($path === '') return '';

        $global = PlatformDataService::get_global_settings() ?? [];
        $siteUrl = trim((string) ($global['site_url'] ?? ''));
        $url = $siteUrl !== ''
            ? rtrim($siteUrl, '/') . '/' . ltrim($path, '/')
            : PlatformPathService::asset_relative_prefix() . ltrim($path, '/');
        $url = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');

        return '<meta property="og:image" content="' . $url . '">'
            . '<meta name="twitter:card" content="summary_large_image">'
            . '<meta name="twitter:image" content="' . $url . '">';
    }

    public static function add_seo_to_html(array $seo_data, string $existing_html = ''): string
    {
        $html = $existing_html;

        if (!empty($seo_data['title'])) {
            $html .= '<title>' . htmlspecialchars($seo_data['title']) . '</title>';
        }

        foreach (['description' => 'description', 'keywords' => 'keywords'] as $key => $meta) {
            if (!empty($seo_data[$key])) {
                $html .= '<meta name="' . htmlspecialchars($meta) . '" content="' . htmlspecialchars($seo_data[$key]) . '">';
            }
        }

        $slug = $seo_data['slug'] ?? '';
        if (!empty($slug)) {
            $ext = PlatformConfig::getPageSlugExtension();
            $html .= '<link rel="canonical" href="' . htmlspecialchars($slug . $ext) . '">';
            $index = filter_var($seo_data['index'] ?? true, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true;
            $html .= $index
                ? '<meta name="robots" content="index, follow">'
                : '<meta name="robots" content="noindex, nofollow">';
        }

        return $html;
    }
}

