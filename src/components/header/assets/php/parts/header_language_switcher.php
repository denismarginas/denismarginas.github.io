<?php

class header_language_switcher
{
    public static function render_language_switcher(array $data = []): string
    {
        $languages = PlatformPathService::languages();
        if (count($languages) < 2) return '';

        $current = PlatformDataService::get_language();
        $postId = trim((string) ($data['post_current_data']['_id'] ?? $data['post_current_data']['post_id'] ?? ''));
        if ($postId === '') $postId = (string) (PlatformConfig::getRoutingMappings()['front_post_id'] ?? 'home');

        $frontend = PlatformPathService::frontend_languages();
        $dist = ($GLOBALS['render_target'] ?? '') === 'dist';

        $items = '';
        foreach ($languages as $iso => $name) {
            $active = $iso === $current;
            $href = PlatformPathService::post_link($postId, $iso);
            if ($dist && in_array($iso, $frontend, true)) $href .= '?lang=' . rawurlencode($iso);
            $items .= '<li><a class="header-language-dropdown-link' . ($active ? ' active' : '') . '"'
                . ' href="' . self::e($href) . '"'
                . ' data-i18n-lang="' . self::e($iso) . '"'
                . (in_array($iso, $frontend, true) ? ' rel="nofollow"' : ' hreflang="' . self::e($iso) . '"')
                . ' lang="' . self::e($iso) . '"'
                . ($active ? ' aria-current="true"' : '')
                . ' title="' . self::e($name) . '"'
                . '>' . self::flag($iso) . '<span class="header-language-dropdown-code">' . self::e(strtoupper($iso)) . '</span></a></li>';
        }

        return PlatformTemplateRenderer::render(__DIR__ . '/../../html/template_language_switcher.html', [
            'current_code' => self::e(strtoupper($current)),
            'current_name' => self::e($languages[$current] ?? strtoupper($current)),
            'chevron' => (string) PlatformComponentRenderer::render('svg', ['icon' => 'chevron-down', 'class' => 'header-language-dropdown-chevron']),
            'items' => $items,
        ]);
    }

    private static function flag(string $iso): string
    {
        return (string) PlatformComponentRenderer::render('svg', ['icon' => 'flag_' . $iso, 'class' => 'header-language-flag']);
    }

    private static function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
