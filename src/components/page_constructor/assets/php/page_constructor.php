<?php

require_once __DIR__ . '/page_constructor_seo.php';

class page_constructor extends page_constructor_seo
{
    public static function render(array $data = []): string
    {
        $bodyContent = $data['body_content'] ?? $data['content'] ?? $data['children_html'] ?? '';
        $seo = $data['seo'] ?? [];

        $langIso = PlatformDataService::get_language();

        $defaultMode = $data['mode'] ?? PlatformConfig::get('theme_default_mode', 'light');

        $seoFields = '';
        foreach (self::implicit_seo_fields() as $field) {
            $seoFields .= $field;
        }
        if (!empty($seo)) {
            $seoFields = self::add_seo_to_html($seo, $seoFields);
        }
        $seoFields .= self::share_image_fields($data['post_current_data'] ?? null);
        $seoFields .= self::alternate_links($data['post_current_data'] ?? null);

        $seoFields .= PlatformComponentRenderer::render('utility_seo_ga');

        $headerContent = PlatformComponentRenderer::render('header', $data);

        $mainContent = PlatformComponentRenderer::render('page_content', [
            'body_content' => $bodyContent,
            'post_current_data' => $data['post_current_data'] ?? null,
        ]);

        $footerContent = PlatformComponentRenderer::render('footer');

        $cookieNotice = PlatformComponentRenderer::render('cookie_notice');

        $performanceMonitor = PlatformComponentRenderer::render('utility_performance_monitor');

        PlatformComponentRenderer::mark_used('overlay_noise');
        PlatformComponentRenderer::mark_used('animation_grid_background');
        $overlayNoise = PlatformComponentRenderer::render('overlay_noise');
        $animationGridBackground = PlatformComponentRenderer::render('animation_grid_background');

        PlatformComponentRenderer::mark_used('cursor');

        $themeModeInit = '';
        $themeModeInitPath = __DIR__ . '/../js/theme_mode_init.js';
        if (file_exists($themeModeInitPath)) {
            $themeModeInit = file_get_contents($themeModeInitPath);
        }

        $preloaderHead = (string) PlatformComponentRenderer::value('preloader', ['part' => 'head']);
        $preloader = PlatformComponentRenderer::render('preloader');
        $i18nFrontend = PlatformPathService::frontend_languages() ? PlatformComponentRenderer::render('i18n_frontend', $data) : '';

        $assetTags = self::asset_tags((bool) ($data['compile_assets'] ?? false));

        $html = PlatformTemplateRenderer::render([
            'lang_iso' => $langIso,
            'body_mode' => $defaultMode,
            'seo_fields' => $seoFields,
            'component_asset_tags' => $assetTags,
            'overlay_noise' => $overlayNoise,
            'animation_grid_background' => $animationGridBackground,
            'theme_mode_init' => $themeModeInit,
            'preloader_head' => $preloaderHead,
            'preloader' => $preloader,
            'i18n_frontend' => $i18nFrontend,
            'header_content' => $headerContent,
            'main_content' => $mainContent,
            'footer_content' => $footerContent,
            'cookie_notice' => $cookieNotice,
            'performance_monitor' => $performanceMonitor,
        ]);

        return class_exists('PlatformHeadHoist') ? PlatformHeadHoist::maybe_apply($html) : $html;
    }

    private static function alternate_links(mixed $post): string
    {
        $id = is_array($post) ? trim((string) ($post['_id'] ?? '')) : '';
        $languages = PlatformPathService::static_languages();
        if ($id === '' || count($languages) < 2) return '';

        $html = '';
        foreach ($languages as $lang) {
            $url = PlatformPathService::post_url($id, $lang);
            if ($url === '') return '';
            $html .= '<link rel="alternate" hreflang="' . htmlspecialchars($lang) . '" href="' . htmlspecialchars($url) . '">';
        }
        $default = PlatformPathService::post_url($id, PlatformDataService::default_language());
        return $html . '<link rel="alternate" hreflang="x-default" href="' . htmlspecialchars($default) . '">';
    }

    private static function asset_tags(bool $compiled): string
    {
        if ($compiled) {
            $urlPath = PlatformPathService::asset_relative_prefix();
            $compiledPath = PlatformConfig::get('theme_compiled_path');
            return '<link rel="stylesheet" href="' . $urlPath . $compiledPath . '/css/bundle.css">'
                . '<script src="' . $urlPath . $compiledPath . '/js/bundle.js"></script>';
        }

        PlatformComponentRenderer::mark_used('theme');
        return PlatformComponentRenderer::get_component_asset_tags();
    }
}
