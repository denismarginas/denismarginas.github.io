<?php

class PlatformSearchIndexBuilder
{
    private const DESCRIPTION_MAX = 300;

    private static array $entries = [];

    public static function entries(): array
    {
        $lang = PlatformDataService::get_language();
        if (isset(self::$entries[$lang])) return self::$entries[$lang];

        $types = PlatformDataService::get_data('settings_types') ?? [];
        $routing = PlatformDataService::get_data('settings_routing') ?? [];
        $extension = $routing['routing']['extension'] ?? $routing['page_slug_extension'] ?? '.html';
        $mappings = PlatformConfig::getRoutingMappings();
        $skip = array_filter([$mappings['404_post_id'] ?? '404', $mappings['search_post_id'] ?? 'search']);
        $render = PlatformWorkflowService::section('render');
        $folders = ($render['html_compile_folder'] ?? false) === true || (string) ($render['html_compile_folder'] ?? '') === 'true';

        $rows = [];
        foreach (($types['post'] ?? []) as $type => $config) {
            if (($config['routable'] ?? true) === false) continue;
            $folder = ($folders && $type !== 'page') ? self::folder($config) : '';
            foreach (PlatformDataService::get_all_posts_from_file($type) ?? [] as $post) {
                self::add($rows, $post, $type, $folder, $extension, $skip);
            }
        }
        foreach (($types['collection'] ?? []) as $type => $config) {
            if (($config['routable'] ?? false) !== true) continue;
            $folder = $folders ? self::folder($config) : '';
            foreach (PlatformDataService::get_collection($type) ?? [] as $entry) {
                self::add($rows, $entry, $type, $folder, $extension, $skip);
            }
        }
        foreach (($types['taxonomy'] ?? []) as $type => $config) {
            if (($config['routable'] ?? true) === false) continue;
            $folder = $folders ? self::folder($config) : '';
            foreach (PlatformDataService::get_data('taxonomy_' . $type) ?? [] as $term) {
                self::add($rows, $term, $type, $folder, $extension, $skip);
            }
        }

        return self::$entries[$lang] = $rows;
    }

    private static function add(array &$rows, mixed $post, string $type, string $folder, string $extension, array $skip): void
    {
        if (!is_array($post)) return;
        $id = trim((string) ($post['_id'] ?? ''));
        if ($id === '' || in_array($id, $skip, true)) return;

        $settings = is_array($post['settings'] ?? null) ? $post['settings'] : [];
        if (($settings['render'] ?? true) === false || ($settings['search'] ?? true) === false) return;

        $content = $post['content'] ?? $post['data']['content'] ?? [];
        if (is_array($content) && ($content[0]['component'] ?? '') === 'utility_duplicate_content') return;

        $data = is_array($post['data'] ?? null) ? $post['data'] : $post;
        $seo = is_array($data['seo'] ?? null) ? $data['seo'] : (is_array($post['seo'] ?? null) ? $post['seo'] : []);

        $title = self::plain((string) ($seo['title'] ?? $data['title'] ?? ''));
        if ($title === '') $title = $id;
        $description = self::plain((string) ($seo['description'] ?? $data['description'] ?? ''));
        if (mb_strlen($description) > self::DESCRIPTION_MAX) {
            $description = rtrim(mb_substr($description, 0, self::DESCRIPTION_MAX - 3)) . '...';
        }

        $rows[] = [$title, $description, ($folder !== '' ? $folder . '/' : '') . $id . $extension, $type];
    }

    private static function folder(array $config): string
    {
        return trim((string) ($config['root'] ?? ''), '/');
    }

    private static function plain(string $text): string
    {
        $text = preg_replace('#<\s*br\s*/?\s*>#i', ' ', $text);
        $text = html_entity_decode(strip_tags((string) $text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }
}
