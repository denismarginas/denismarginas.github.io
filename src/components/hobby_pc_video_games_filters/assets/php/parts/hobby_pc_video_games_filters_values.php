<?php

class hobby_pc_video_games_filters_values
{
    public static function filters(array $data, array $items): array
    {
        $defs = listing_toolbar::filters($data);
        $include = (array) ($data['filters_include'] ?? []);
        $exclude = (array) ($data['filters_exclude'] ?? []);
        $hideSingle = !array_key_exists('hide_single_option', $data) || (bool) $data['hide_single_option'];

        $filters = [];
        foreach ($defs as $def) {
            if (!is_array($def)) continue;
            $id = (string) ($def['_id'] ?? '');
            if ($id === '') continue;
            if ($include && !in_array($id, $include, true)) continue;
            if (in_array($id, $exclude, true)) continue;

            if (self::is_display($def)) {
                $def['options'] = self::display_options($def);
                if (self::has_hidden($items)) $filters[] = $def;
                continue;
            }
            if (empty($def['json-path'])) continue;

            $def['options'] = self::options($def, $items);
            if (count($def['options']) < ($hideSingle ? 2 : 1)) continue;
            $filters[] = $def;
        }
        return $filters;
    }

    public static function options(array $def, array $items): array
    {
        $type = self::type($def);

        if (is_array($def['options'] ?? null) && !empty($def['options'])) {
            $values = array_values(array_filter(array_map('strval', $def['options']), fn ($v) =>
                trim($v) !== '' && self::any_match($def, $items, $v)
            ));
        } else {
            $found = [];
            foreach ($items as $item) {
                foreach (self::of($item, $def) as $value) {
                    $found[$value] = true;
                }
            }
            $values = array_map('strval', array_keys($found));
        }

        $order = strtolower((string) ($def['options-order'] ?? (in_array($type, ['number', 'date'], true) ? 'desc' : 'asc')));
        if ($order === 'asc' || $order === 'desc') {
            in_array($type, ['number', 'date'], true)
                ? usort($values, fn ($a, $b) => (float) $a <=> (float) $b)
                : usort($values, 'strnatcasecmp');
            if ($order === 'desc') $values = array_reverse($values);
        }
        return $values;
    }

    public static function of(array $item, array $def): array
    {
        $value = self::path($item, (string) $def['json-path']);
        $type = self::type($def);

        if ($type === 'list' && is_string($value)) {
            $value = explode((string) ($def['json-separator'] ?? ','), $value);
        }

        $list = is_array($value) && array_is_list($value) ? $value : [$value];
        $key = (string) ($def['json-array-key'] ?? '');

        $out = [];
        foreach ($list as $entry) {
            if (is_array($entry)) $entry = $key !== '' ? ($entry[$key] ?? null) : null;
            if (!is_scalar($entry) || trim((string) $entry) === '') continue;
            $entry = trim((string) $entry);

            if ($type === 'number') {
                $number = hobby_pc_video_games_listing_items::number($entry);
                $entry = $number === null ? '' : self::number_text($number);
            } elseif ($type === 'date') {
                $entry = preg_match('/\d{4}/', $entry, $m) ? $m[0] : '';
            }
            if ($entry !== '') $out[] = $entry;
        }
        return array_values(array_unique($out));
    }

    public static function matches(array $values, string $selected, array $def): bool
    {
        $type = self::type($def);
        if (!in_array($type, ['number', 'date'], true)) {
            return in_array($selected, $values, true);
        }

        $mode = listing_toolbar::mode($def, '=');
        $wanted = (float) $selected;
        foreach ($values as $v) {
            $v = (float) $v;
            if (($mode === '>=' && $v >= $wanted) || ($mode === '<=' && $v <= $wanted) || ($mode === '=' && $v == $wanted)) {
                return true;
            }
        }
        return false;
    }

    public static function is_display(array $def): bool
    {
        return strtolower((string) ($def['type'] ?? '')) === 'display' || strtolower((string) ($def['json-type'] ?? '')) === 'display';
    }

    public static function display_options(array $def): array
    {
        $options = $def['options'] ?? null;
        if (!is_array($options) || empty($options)) {
            $options = [['value' => 'default', 'label' => PlatformTextService::ui('default', 'Default')], ['value' => 'all', 'label' => PlatformTextService::ui('all', 'All')]];
        }
        $out = [];
        foreach ($options as $option) {
            $value = is_array($option) ? (string) ($option['value'] ?? '') : (string) $option;
            if (trim($value) === '') continue;
            $label = is_array($option) ? (string) ($option['label'] ?? ucfirst($value)) : ucfirst($value);
            $out[] = ['value' => $value, 'label' => $label];
        }
        return $out;
    }

    private static function has_hidden(array $items): bool
    {
        foreach ($items as $item) {
            if (hobby_pc_video_games_listing_items::is_hidden($item)) return true;
        }
        return false;
    }

    public static function type(array $def): string
    {
        return strtolower((string) ($def['json-type'] ?? 'string'));
    }

    public static function mode(array $def): string
    {
        return listing_toolbar::mode($def);
    }

    private static function any_match(array $def, array $items, string $option): bool
    {
        foreach ($items as $item) {
            if (self::matches(self::of($item, $def), $option, $def)) return true;
        }
        return false;
    }

    private static function number_text(float $value): string
    {
        return floor($value) === $value ? (string) (int) $value : (string) $value;
    }

    private static function path(array $item, string $path): mixed
    {
        preg_match_all("/\\[['\"]?([^'\"\\]]+)['\"]?\\]/", $path, $m);
        foreach ([$item, $item['data'] ?? null] as $source) {
            $value = $source;
            foreach ($m[1] as $key) {
                if (!is_array($value) || !array_key_exists($key, $value)) { $value = null; break; }
                $value = $value[$key];
            }
            if ($value !== null) return $value;
        }
        return null;
    }
}
