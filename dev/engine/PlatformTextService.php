<?php

class PlatformTextService
{
    private static array $ui = [];

    public static function ui(string $key, string $default = ''): string
    {
        $lang = PlatformDataService::get_language();
        if (!isset(self::$ui[$lang])) {
            $data = PlatformDataService::get_data('content_ui');
            self::$ui[$lang] = is_array($data) ? array_filter($data, fn ($v) => is_string($v) && trim($v) !== '') : [];
        }
        return self::$ui[$lang][$key] ?? $default;
    }

    public static function excerpt(string $text, int $max = 45, string $suffix = '...'): string
    {
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text);
        $text = trim($text);
        if ($text === '') return '';

        if (mb_strlen($text) <= $max) return $text;
        return mb_substr($text, 0, $max) . $suffix;
    }

    public static function truncate(string $text, int $maxVisible = 25, string $suffix = '..'): string
    {
        $text = trim(strip_tags($text));
        $len = mb_strlen($text);
        if ($len <= $maxVisible) return $text;

        $cut = $maxVisible - mb_strlen($suffix);
        if ($cut < 1) return $suffix;
        return mb_substr($text, 0, $cut) . $suffix;
    }

    public static function strip_html(string $text): string
    {
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = strip_tags($text);
        return trim($text);
    }

    public static function format_date(string $date, string $format = 'd M y', ?string $inputFormat = null): string
    {
        $timestamp = self::parse_date($date, $inputFormat);
        if ($timestamp === null) return $date;

        $locale = self::date_locale();
        $monthNumber = (int) date('n', $timestamp);
        $dayNumber = (int) date('w', $timestamp);

        $tokens = [
            'yyyy' => date('Y', $timestamp),
            'yy' => date('y', $timestamp),
            'Y' => date('Y', $timestamp),
            'y' => date('y', $timestamp),
            'd' => date('d', $timestamp),
            'j' => date('j', $timestamp),
            'm' => date('m', $timestamp),
            'n' => date('n', $timestamp),
            'F' => $locale['months']['full'][$monthNumber - 1] ?? date('F', $timestamp),
            'M' => $locale['months']['short'][$monthNumber - 1] ?? date('M', $timestamp),
            'l' => $locale['days']['full'][$dayNumber] ?? date('l', $timestamp),
            'D' => $locale['days']['short'][$dayNumber] ?? date('D', $timestamp),
        ];

        return preg_replace_callback('/yyyy|yy|F|M|l|D|Y|y|d|j|m|n/', static function (array $m) use ($tokens): string {
            return $tokens[$m[0]] ?? $m[0];
        }, $format) ?? $format;
    }

    protected static function parse_date(string $date, ?string $inputFormat = null): ?int
    {
        if ($inputFormat !== null && $inputFormat !== '') {
            $phpFormat = self::to_php_date_format($inputFormat);
            $parsed = DateTime::createFromFormat($phpFormat, $date);
            if ($parsed !== false && $parsed->format($phpFormat) === $date) {
                return $parsed->getTimestamp();
            }
        }

        $formats = ['d-m-Y', 'd/m/Y', 'd.m.Y', 'Y-m-d', 'Y/m/d'];
        foreach ($formats as $format) {
            $parsed = DateTime::createFromFormat($format, $date);
            if ($parsed !== false && $parsed->format($format) === $date) {
                return $parsed->getTimestamp();
            }
        }

        $timestamp = strtotime($date);
        return $timestamp === false ? null : $timestamp;
    }

    protected static function to_php_date_format(string $format): string
    {
        $replacements = [
            'dd' => 'd',
            'd' => 'j',
            'mm' => 'm',
            'm' => 'n',
            'yyyy' => 'Y',
            'yy' => 'y',
        ];

        return preg_replace_callback('/dd|d|mm|m|yyyy|yy/', static function (array $m) use ($replacements): string {
            return $replacements[$m[0]] ?? $m[0];
        }, $format) ?? $format;
    }

    protected static function date_locale(): array
    {
        $data = PlatformDataService::get_data('dates') ?? [];
        return [
            'months' => $data['months'] ?? [],
            'days' => $data['days'] ?? [],
        ];
    }
}
