<?php

class utility_text
{
    public static function value(array $params = []): string
    {
        $input = (string) ($params['input'] ?? '');
        if ($input === '') return '';

        $outputs = (array) ($params['outputs'] ?? []);
        $limit = (int) ($outputs['limit_chars'] ?? 0);
        $suffix = (string) ($outputs['suffix'] ?? '...');
        $stripHtml = array_key_exists('strip_html', $outputs)
            ? (bool) $outputs['strip_html']
            : true;

        $text = $input;
        if ($stripHtml) {
            $text = PlatformTextService::strip_html($text);
        }
        if ($limit > 0) {
            return PlatformTextService::excerpt($text, $limit, $suffix);
        }

        return $text;
    }
}