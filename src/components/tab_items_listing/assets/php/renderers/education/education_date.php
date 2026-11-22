<?php

class tab_items_listing_education_date
{
    public static function education_date_range(array $date): string
    {
        $start = (string) ($date['start'] ?? '');
        $end = (string) ($date['end'] ?? '');

        $startLabel = $start !== '' ? PlatformTextService::format_date($start, 'M Y', 'yyyy-mm-dd') : '';
        $endLabel = $end !== '' && !self::is_current_date($end)
            ? PlatformTextService::format_date($end, 'M Y', 'yyyy-mm-dd')
            : ($end !== '' ? self::current_date_label($end) : '');

        if ($startLabel !== '' && $endLabel !== '' && $startLabel === $endLabel) {
            return $startLabel;
        }
        $parts = array_values(array_filter([$startLabel, $endLabel], fn ($v) => $v !== ''));
        return implode(' - ', $parts);
    }

    public static function is_current_date(string $value): bool
    {
        $key = strtolower(trim($value));
        $key = str_replace(['-', ' '], '_', $key);
        if ($key === 'working') $key = 'in_progress';
        $data = PlatformDataService::get_data('dates');
        $labels = $data['current_date'] ?? null;
        if (is_array($labels) && !empty($labels)) {
            return isset($labels[$key]);
        }
        return in_array($key, ['present', 'in_progress', 'currently', 'current_date'], true);
    }

    public static function current_date_label(string $value): string
    {
        $key = strtolower(trim($value));
        $key = str_replace(['-', ' '], '_', $key);
        if ($key === 'working') $key = 'in_progress';
        $data = PlatformDataService::get_data('dates');
        $labels = $data['current_date'] ?? [];
        if (isset($labels[$key]) && is_string($labels[$key]) && $labels[$key] !== '') {
            return $labels[$key];
        }

        return $labels['present'] ?? 'Present';
    }
}
