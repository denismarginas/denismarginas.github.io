<?php

class post_items_render extends post_items_item
{
    private const FILTER_SETTINGS = ['max_items'];

    public static function render(array $data = []): string
    {
        $postTypes = self::resolve_post_types($data['post_type'] ?? null);

        $posts = [];
        foreach ($postTypes as $postType) {
            $typePosts = PlatformDataService::get_all_posts_from_file($postType);
            if (empty($typePosts)) continue;
            foreach ($typePosts as $post) {
                $post['_post_type'] = $postType;
                $posts[] = $post;
            }
        }
        if (empty($posts)) return '';

        $template = $data['template'] ?? null;
        $excludeBy = $data['exclude_by'] ?? [];
        $filterBy = $data['filter_by'] ?? [];

        $currentId = self::current_id($data);

        $posts = array_filter($posts, fn (array $post): bool =>
            ($post['settings']['render'] ?? true) !== false
            && !($currentId !== '' && self::post_id($post) === $currentId)
            && !self::is_excluded($post, $excludeBy)
            && self::passes_filter($post, $filterBy)
        );
        $posts = self::sort_posts($posts, $data['sort'] ?? null);
        $posts = self::limit($posts, $filterBy);

        $items = '';
        foreach ($posts as $post) {
            if (is_array($template)) {
                $items .= self::render_via_template($post, $template);
            } else {
                $postType = (string) ($post['_post_type'] ?? 'project');
                $items .= self::render_item($post, self::build_context($data, $postType));
            }
        }

        if ($items === '') return '';

        return PlatformTemplateRenderer::render([
            'id_attr' => self::id_attr($data['id'] ?? ''),
            'extra_class' => self::extra_class($data['class'] ?? ''),
            'items' => $items,
        ]);
    }

    public static function id_attr(mixed $id): string
    {
        $id = is_scalar($id) ? trim((string) $id) : '';
        return $id !== '' ? ' id="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '"' : '';
    }

    public static function extra_class(mixed $class): string
    {
        $class = is_scalar($class) ? trim(preg_replace('/[^a-zA-Z0-9_\- ]/', '', (string) $class)) : '';
        return $class !== '' ? ' ' . preg_replace('/\s+/', ' ', $class) : '';
    }

    public static function sort_posts(array $posts, mixed $sort): array
    {
        $posts = array_values($posts);
        if (empty($sort)) return $posts;

        $sorted = PlatformComponentRenderer::value('utility_sort', ['items' => $posts, 'rules' => $sort]);
        return is_array($sorted) ? $sorted : $posts;
    }

    public static function current_id(array $data): string
    {
        if (($data['exclude_current'] ?? false) !== true) return '';
        $current = is_array($data['post_current_data'] ?? null) ? $data['post_current_data'] : [];
        return self::post_id($current);
    }

    public static function post_id(array $post): string
    {
        return (string) ($post['post_id'] ?? $post['_id'] ?? '');
    }

    public static function is_excluded(array $post, mixed $excludeBy): bool
    {
        $entries = is_array($excludeBy) ? $excludeBy : [$excludeBy];
        if (empty($entries)) return false;

        $context = [
            'post_id' => self::post_id($post),
            'data' => $post['data'] ?? [],
            'settings' => $post['settings'] ?? [],
            'img_base' => '',
        ];

        foreach ($entries as $entry) {
            if (is_string($entry)) {
                $path = $entry;
                $expected = null;
            } elseif (is_array($entry)) {
                $path = (string) ($entry['path'] ?? $entry['ref'] ?? '');
                $expected = $entry['value'] ?? null;
            } else {
                continue;
            }

            if ($path === '') continue;
            $resolved = self::resolve_token(self::path_token($path), $context);

            if ($expected !== null) {
                if (self::matches($resolved, $expected)) return true;
            } elseif (self::is_truthy($resolved)) {
                return true;
            }
        }

        return false;
    }

    public static function passes_filter(array $post, mixed $filterBy): bool
    {
        $entries = is_array($filterBy) ? $filterBy : [];
        if (empty($entries)) return true;

        $context = [
            'post_id' => self::post_id($post),
            'data' => $post['data'] ?? [],
            'settings' => $post['settings'] ?? [],
            'img_base' => '',
        ];

        foreach ($entries as $path => $expected) {
            if (!is_string($path) || in_array($path, self::FILTER_SETTINGS, true)) continue;

            if (!self::matches(self::resolve_token(self::path_token($path), $context), $expected)) return false;
        }

        return true;
    }

    public static function max_items(mixed $filterBy): ?int
    {
        $max = is_array($filterBy) ? ($filterBy['max_items'] ?? null) : null;
        return is_numeric($max) && (int) $max > 0 ? (int) $max : null;
    }

    public static function limit(array $posts, mixed $filterBy): array
    {
        $max = self::max_items($filterBy);
        return $max === null ? array_values($posts) : array_slice(array_values($posts), 0, $max);
    }

    public static function path_token(string $path): string
    {
        if (str_starts_with($path, '@')) return $path;
        if ($path === '_id' || $path === 'post_id') return '@_id';
        return (str_starts_with($path, 'data.') || str_starts_with($path, 'settings.'))
            ? '@' . $path
            : '@data.' . $path;
    }

    public static function matches(mixed $resolved, mixed $expected): bool
    {
        if (is_array($expected) && array_is_list($expected)) {
            foreach ($expected as $one) {
                if (self::matches($resolved, $one)) return true;
            }
            return false;
        }
        if (is_array($resolved)) {
            return in_array((string) $expected, array_map('strval', $resolved), true);
        }
        return self::values_equal($resolved, $expected);
    }

    public static function values_equal(mixed $a, mixed $b): bool
    {
        if (is_bool($b)) {
            return (bool) $a === $b;
        }
        if (is_string($b) || is_int($b) || is_float($b)) {
            return (string) $a === (string) $b;
        }
        return $a === $b;
    }

    public static function is_truthy(mixed $value): bool
    {
        if ($value === null || $value === '' || $value === false || $value === 0 || $value === '0' || $value === 'false') {
            return false;
        }
        if (is_array($value)) {
            return !empty($value);
        }
        return true;
    }

    public static function resolve_post_types(mixed $postType): array
    {
        if ($postType === null || $postType === '' || $postType === false || $postType === []) {
            $valid = PlatformDataService::get_valid_post_files();
            return array_values(array_map(static fn ($v) => $v['name'], $valid));
        }

        if (is_string($postType)) {
            return array_values(array_filter(array_map('trim', explode(',', $postType)), static fn ($v) => $v !== ''));
        }

        if (is_array($postType)) {
            return array_values($postType);
        }

        return ['project'];
    }

    public static function render_via_template(array $post, array $template): string
    {
        $component = (string) ($template['component'] ?? 'card');
        $rawParams = (array) ($template['params'] ?? $template['data'] ?? []);

        $postType = (string) ($post['_post_type'] ?? 'project');
        $contentBase = rtrim((string) ($template['global_content_path'] ?? ''), '/');
        $imgBase = $contentBase !== ''
            ? $contentBase . '/img/' . $postType
            : self::post_type_img_base($postType);
        $imgBase = rtrim($imgBase, '/');

        $params = array_merge($rawParams, [
            'post_id' => (string) ($post['post_id'] ?? $post['_id'] ?? ''),
        ]);

        $context = [
            'post_id' => (string) ($post['post_id'] ?? $post['_id'] ?? ''),
            'data' => $post['data'] ?? [],
            'img_base' => $imgBase,
        ];
        $params = self::resolve_template_value($params, $context);
        if (!is_array($params)) {
            $params = [];
        }
        $params['post_current_data'] = $post;

        $item = PlatformComponentRenderer::render($component, $params);

        return PlatformTemplateRenderer::render(__DIR__ . '/../../html/template_item.html', [
            'post_id' => htmlspecialchars($context['post_id'], ENT_QUOTES, 'UTF-8'),
            'item' => $item,
        ]);
    }

    public static function build_context(array $data, string $postType): array
    {
        $contentBase = rtrim((string)($data['global_content_path'] ?? ''), '/');
        $globalImgPath = $contentBase !== '' ? $contentBase . '/img/' . $postType : self::post_type_img_base($postType);

        return [
            'img_path' => isset($data['feature_image_path']) ? rtrim((string)$data['feature_image_path'], '/') . '/' : '',
            'img_filename' => (string)($data['feature_image_filename'] ?? ''),
            'title_param' => $data['title'] ?? ['data', 'seo', 'title'],
            'media_path_param' => $data['media_path'] ?? ['data', 'media', 'path'],
            'global_img_path' => $globalImgPath,
        ];
    }

    public static function post_type_img_base(string $postType): string
    {
        $types = PlatformDataService::get_data('settings_types');
        $path = (string)($types['post'][$postType]['global_img_path'] ?? '');
        if ($path !== '') return rtrim($path, '/');
        return 'src/content/img/' . $postType;
    }
}




