<?php

class utility_appearance_colors
{
    public const FALLBACKS = [
        'primary' => 'var(--dm-color-primary-600)',
        'secondary' => 'var(--dm-color-primary-50)',
        'canvas_primary' => 'var(--dm-color-primary-800)',
        'canvas_secondary' => 'var(--dm-color-primary-50)',
    ];

    public static function value(array $params = []): string
    {
        $colors = $params['colors'] ?? ($params['post_current_data']['settings']['appearance']['colors'] ?? []);
        $colors = is_array($colors) ? $colors : [];

        $declarations = self::declarations($colors);
        $output = strtolower(trim((string) ($params['output'] ?? 'attribute')));

        if ($output === 'declarations') {
            return $declarations;
        }

        if ($output === 'style_tag') {
            $selector = trim((string) ($params['selector'] ?? ':root'));
            if ($selector === '' || preg_match('/[{}<>]/', $selector)) $selector = ':root';
            return '<style>' . $selector . '{' . $declarations . '}</style>';
        }

        return ' style="' . htmlspecialchars($declarations, ENT_QUOTES, 'UTF-8') . '"';
    }

    public const EDGE_TOLERANCE = 0.08;

    public const EDGE_ADJUST = [
        'on-dark' => ['black', 'var(--dm-color-gray-light)'],
        'on-light' => ['white', 'var(--dm-color-gray-dark)'],
    ];

    public static function declarations(array $colors): string
    {
        $values = [];
        foreach (self::FALLBACKS as $key => $fallback) {
            $values[$key] = self::clean($colors[$key] ?? '') ?: $fallback;
        }
        foreach ($colors as $key => $value) {
            $key = (string) $key;
            if (!isset($values[$key]) && ($clean = self::clean($value)) !== '') {
                $values[$key] = $clean;
            }
        }

        $out = [];
        foreach ($values as $key => $value) {
            $name = preg_replace('/[^a-z0-9\-]/', '', str_replace('_', '-', strtolower($key)));
            if ($name === '') continue;
            $out[] = '--post-color-' . $name . ': ' . $value . ';';
            $edge = self::edge($value);
            foreach (self::EDGE_ADJUST as $suffix => [$tone, $replacement]) {
                if ($edge === $tone) $out[] = '--post-color-' . $name . '-' . $suffix . ': ' . $replacement . ';';
            }
        }
        return implode(' ', $out);
    }

    public static function edge(string $value): string
    {
        $rgb = self::rgb($value);
        if ($rgb === null) return '';
        $limit = 255 * self::EDGE_TOLERANCE;
        if (max($rgb) <= $limit) return 'black';
        if (min($rgb) >= 255 - $limit) return 'white';
        return '';
    }

    private static function rgb(string $value): ?array
    {
        $value = strtolower(trim($value));
        if (preg_match('/^#([0-9a-f]{3,8})$/', $value, $m)) {
            $hex = $m[1];
            if (strlen($hex) === 3 || strlen($hex) === 4) $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
            if (strlen($hex) < 6) return null;
            return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
        }
        if (preg_match('/^rgba?\(\s*(\d+)[\s,]+(\d+)[\s,]+(\d+)/', $value, $m)) {
            return [(int) $m[1], (int) $m[2], (int) $m[3]];
        }
        if ($value === 'black') return [0, 0, 0];
        if ($value === 'white') return [255, 255, 255];
        return null;
    }

    private static function clean(mixed $value): string
    {
        $value = is_scalar($value) ? trim((string) $value) : '';
        return ($value === '' || preg_match('/[;{}<>"\']/', $value)) ? '' : $value;
    }
}
