<?php

class PlatformSeoFilesBuilder
{
    public const DEFAULTS = [
        'sitemap' => true,
        'robots' => true,
        'site_url' => '',
        'include_noindex' => false,
        'front_page_as_root' => true,
        'lastmod' => true,
        'robots_disallow' => '',
        'build_on_html_render' => true,
    ];

    public static function params(): array
    {
        $params = self::DEFAULTS;
        foreach (PlatformWorkflowService::section('seo_files') as $key => $value) {
            if (!array_key_exists($key, $params)) continue;
            $params[$key] = is_bool(self::DEFAULTS[$key])
                ? ($value === true || (string) $value === 'true' || (string) $value === '1')
                : trim((string) $value);
        }
        return $params;
    }

    public static function build(?array $params = null): array
    {
        $params = $params ?? self::params();
        $distDir = ENGINE_PROJECT_ROOT . '/dist';
        if (!is_dir($distDir)) mkdir($distDir, 0777, true);

        $siteUrl = self::site_url($params);
        $entries = self::entries($params, $siteUrl, $distDir);
        $results = [];

        if ($params['sitemap']) {
            $xml = self::sitemap_xml($entries, $params);
            $ok = file_put_contents($distDir . '/sitemap.xml', $xml) !== false;
            $results[] = ['file' => 'dist/sitemap.xml', 'success' => $ok, 'bytes' => strlen($xml), 'count' => count($entries), 'content' => $xml];
        }
        if ($params['robots']) {
            $txt = self::robots_txt($params, $siteUrl);
            $ok = file_put_contents($distDir . '/robots.txt', $txt) !== false;
            $results[] = ['file' => 'dist/robots.txt', 'success' => $ok, 'bytes' => strlen($txt), 'content' => $txt];
        }

        $notes = [];
        if ($siteUrl === '') {
            $notes[] = 'No site url: set seo_files.site_url or settings_routing "url" (sitemap urls must be absolute).';
        } elseif (trim((string) parse_url($siteUrl, PHP_URL_PATH), '/') !== '' && $params['robots']) {
            $notes[] = 'The site lives in a sub folder (' . parse_url($siteUrl, PHP_URL_PATH) . '): crawlers only read robots.txt at the domain root, so submit sitemap.xml in Google Search Console instead.';
        }

        return ['ok' => true, 'site_url' => $siteUrl, 'count' => count($entries), 'results' => $results, 'urls' => array_column($entries, 'loc'), 'notes' => $notes];
    }

    public static function maybe_build_after_render(): ?array
    {
        $params = self::params();
        if (!$params['build_on_html_render'] || (!$params['sitemap'] && !$params['robots'])) return null;
        return self::build($params);
    }

    private static function site_url(array $params): string
    {
        $url = $params['site_url'];
        if ($url === '') {
            $routing = PlatformDataService::get_data('settings_routing') ?? [];
            $url = trim((string) ($routing['url'] ?? ''));
        }
        return $url === '' ? '' : rtrim($url, '/') . '/';
    }

    private static function entries(array $params, string $siteUrl, string $distDir): array
    {
        $types = PlatformDataService::get_data('settings_types') ?? [];
        $routing = PlatformDataService::get_data('settings_routing') ?? [];
        $extension = $routing['routing']['extension'] ?? $routing['page_slug_extension'] ?? '.html';
        $mappings = PlatformConfig::getRoutingMappings();
        $frontId = (string) ($mappings['front_post_id'] ?? 'home');
        $skip = array_filter([$mappings['404_post_id'] ?? '404', $mappings['search_post_id'] ?? 'search']);
        $folders = PlatformWorkflowService::bool('render', 'html_compile_folder', false);

        $rows = [];
        $seen = [];
        $current = PlatformDataService::get_language();
        foreach (PlatformPathService::static_languages() as $lang) {
            PlatformDataService::set_language($lang);
            $langPrefix = PlatformPathService::lang_prefix($lang);

            $groups = [];
            foreach (($types['post'] ?? []) as $type => $config) {
                if (($config['routable'] ?? true) === false) continue;
                $groups[] = [PlatformDataService::get_all_posts_from_file($type) ?? [], ($folders && $type !== 'page') ? self::folder($config) : ''];
            }
            foreach (($types['taxonomy'] ?? []) as $type => $config) {
                if (($config['routable'] ?? true) === false) continue;
                $groups[] = [PlatformDataService::get_data('taxonomy_' . $type) ?? [], $folders ? self::folder($config) : ''];
            }
            foreach (($types['collection'] ?? []) as $type => $config) {
                if (($config['routable'] ?? false) !== true) continue;
                $groups[] = [PlatformDataService::get_collection($type) ?? [], $folders ? self::folder($config) : ''];
            }

            foreach ($groups as [$posts, $folder]) {
                foreach ($posts as $post) {
                    if (!is_array($post)) continue;
                    $id = trim((string) ($post['_id'] ?? ''));
                    if ($id === '' || in_array($id, $skip, true)) continue;

                    $data = is_array($post['data'] ?? null) ? $post['data'] : $post;
                    $settings = is_array($post['settings'] ?? null) ? $post['settings'] : (is_array($data['settings'] ?? null) ? $data['settings'] : []);
                    if (($settings['render'] ?? true) === false) continue;

                    $content = $post['content'] ?? $data['content'] ?? [];
                    if (is_array($content) && ($content[0]['component'] ?? '') === 'utility_duplicate_content') continue;

                    $seo = is_array($data['seo'] ?? null) ? $data['seo'] : (is_array($post['seo'] ?? null) ? $post['seo'] : []);
                    $index = filter_var($seo['index'] ?? true, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true;
                    if (!$index && !$params['include_noindex']) continue;

                    $rel = $langPrefix . ($folder !== '' ? $folder . '/' : '') . $id . $extension;
                    $isFront = $params['front_page_as_root'] && $id === $frontId;
                    $href = $langPrefix . ($folder !== '' ? $folder . '/' : '') . $id . PlatformPathService::link_extension();
                    $loc = $isFront ? $siteUrl . $langPrefix : $siteUrl . self::encode_path($href);
                    if (isset($seen[$loc])) continue;
                    $seen[$loc] = true;

                    $rows[] = [
                        'loc' => $loc,
                        'lastmod' => $params['lastmod'] ? self::lastmod($data, $distDir . '/' . $rel) : '',
                        'front' => $isFront,
                    ];
                }
            }
        }
        PlatformDataService::set_language($current);

        usort($rows, fn($a, $b) => (int) $b['front'] <=> (int) $a['front']);
        return $rows;
    }

    private static function sitemap_xml(array $entries, array $params): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($entries as $e) {
            $xml .= "  <url>\n    <loc>" . htmlspecialchars($e['loc'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</loc>\n";
            if ($e['lastmod'] !== '') $xml .= '    <lastmod>' . $e['lastmod'] . "</lastmod>\n";
            $xml .= "  </url>\n";
        }
        return $xml . "</urlset>\n";
    }

    private static function robots_txt(array $params, string $siteUrl): string
    {
        $basePath = '/' . trim((string) parse_url($siteUrl, PHP_URL_PATH), '/');
        $basePath = $basePath === '/' ? '' : $basePath;

        $lines = ['User-agent: *'];
        $disallow = array_filter(array_map('trim', explode(',', $params['robots_disallow'])));
        foreach ($disallow as $path) {
            $lines[] = 'Disallow: ' . $basePath . '/' . self::encode_path(ltrim($path, '/'));
        }
        if (!$disallow) $lines[] = 'Allow: /';
        if ($params['sitemap'] && $siteUrl !== '') {
            $lines[] = '';
            $lines[] = 'Sitemap: ' . $siteUrl . 'sitemap.xml';
        }
        return implode("\n", $lines) . "\n";
    }

    private static function lastmod(array $data, string $distFile): string
    {
        $date = is_array($data['date'] ?? null) ? $data['date'] : [];
        foreach (['update', 'updated', 'modified', 'publish'] as $key) {
            $value = trim((string) ($date[$key] ?? ''));
            if ($value !== '' && ($ts = strtotime($value)) !== false) return date('Y-m-d', $ts);
        }
        return is_file($distFile) ? date('Y-m-d', (int) filemtime($distFile)) : '';
    }

    private static function folder(array $config): string
    {
        return trim((string) ($config['root'] ?? ''), '/');
    }

    private static function encode_path(string $path): string
    {
        return implode('/', array_map('rawurlencode', explode('/', $path)));
    }
}
