<?php

require_once __DIR__ . '/../../../post_short_info_visual/assets/php/post_short_info_post.php';
require_once __DIR__ . '/parts/post_short_info_content_helpers.php';
require_once __DIR__ . '/parts/post_short_info_content_blocks.php';

class post_short_info_content extends post_short_info_content_blocks
{
    public static function render(array $data = []): string
    {
        $post = post_short_info_post::resolve($data);
        if (empty($post)) return '';

        $postData = post_short_info_post::post_data($post);

        $html = '';
        foreach (self::resolve_fields($data) as $field) {
            $method = 'block_' . $field;
            if (method_exists(static::class, $method)) {
                $html .= static::$method($postData, $post);
            }
        }
        if (trim($html) === '') return '';

        $extra = trim((string) ($data['class'] ?? ''));

        return PlatformTemplateRenderer::render(__DIR__ . '/../html/template.html', [
            'extra_class' => $extra !== '' ? ' ' . htmlspecialchars($extra, ENT_QUOTES, 'UTF-8') : '',
            'blocks' => $html,
        ]);
    }

    private static function resolve_fields(array $data): array
    {
        $fields = self::list_option($data['fields'] ?? null);
        if (empty($fields)) $fields = self::DEFAULT_FIELDS;

        $exclude = self::list_option($data['exclude'] ?? null);

        return array_values(array_filter($fields, fn($f) => !in_array($f, $exclude, true)));
    }

    private static function list_option(mixed $value): array
    {
        if (is_string($value)) $value = explode(',', $value);
        if (!is_array($value)) return [];
        $out = [];
        foreach ($value as $v) {
            $v = strtolower(trim((string) $v));
            if ($v !== '' && preg_match('/^[a-z_]+$/', $v)) $out[] = $v;
        }
        return $out;
    }
}
