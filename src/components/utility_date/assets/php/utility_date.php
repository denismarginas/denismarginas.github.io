<?php

class utility_date
{
    public static function value(array $params = []): array
    {
        $input = (string) ($params['input'] ?? '');
        if ($input === '') return [];

        $inputFormat = (string) ($params['format'] ?? '');
        if ($inputFormat === '') {
            $inputFormat = null;
        }

        $outputs = (array) ($params['outputs'] ?? []);
        $result = [];

        foreach ($outputs as $key => $format) {
            if (is_string($format) && $format !== '') {
                $result[(string) $key] = PlatformTextService::format_date($input, $format, $inputFormat);
            }
        }

        return $result;
    }
}