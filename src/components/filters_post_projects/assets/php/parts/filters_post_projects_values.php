<?php

class filters_post_projects_values
{
    private const ONGOING = ['present', 'in progress', 'ongoing', 'now', 'current'];

    public static function filters(array $data, array $posts): array
    {
        $defs = listing_toolbar::filters($data);
        $include = (array) ($data['filters_include'] ?? []);
        $exclude = (array) ($data['filters_exclude'] ?? []);
        $hideSingle = !array_key_exists('hide_single_option', $data) || (bool) $data['hide_single_option'];

        $filters = [];
        foreach ($defs as $def) {
            $id = (string) ($def['_id'] ?? '');
            if ($id === '' || empty($def['json-path'])) continue;
            if ($include && !in_array($id, $include, true)) continue;
            if (in_array($id, $exclude, true)) continue;

            $def['options'] = self::options($def, $posts);
            if (count($def['options']) < ($hideSingle ? 2 : 1)) continue;
            $filters[] = $def;
        }
        return $filters;
    }

    public static function options(array $def, array $posts): array
    {
        $values = [];
        foreach ($posts as $post) {
            foreach (self::of($post, $def) as $value) {
                $values[$value] = true;
            }
        }
        $values = array_map('strval', array_keys($values));

        if (($def['json-type'] ?? '') === 'date') {
            rsort($values, SORT_NATURAL);
        }
        return $values;
    }

    public static function of(array $post, array $def): array
    {
        $value = self::path($post, (string) $def['json-path']);
        $list = is_array($value) && array_is_list($value) ? $value : [$value];
        $key = (string) ($def['json-array-key'] ?? '');
        $isDate = ($def['json-type'] ?? '') === 'date';

        $out = [];
        foreach ($list as $item) {
            if (is_array($item)) $item = $key !== '' ? ($item[$key] ?? null) : null;
            if (!is_scalar($item) || trim((string) $item) === '') continue;
            $item = trim((string) $item);
            if ($isDate) $item = self::year($item);
            if ($item !== '') $out[] = $item;
        }
        return array_values(array_unique($out));
    }

    private static function path(array $post, string $path): mixed
    {
        preg_match_all("/\\[['\"]?([^'\"\\]]+)['\"]?\\]/", $path, $m);
        $value = $post;
        foreach ($m[1] as $key) {
            if (!is_array($value) || !array_key_exists($key, $value)) return null;
            $value = $value[$key];
        }
        return $value;
    }

    private static function year(string $date): string
    {
        if (in_array(strtolower($date), self::ONGOING, true)) return date('Y');
        return preg_match('/\d{4}/', $date, $m) ? $m[0] : '';
    }
}
