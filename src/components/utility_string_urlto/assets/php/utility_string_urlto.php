<?php

class utility_string_urlto
{
    public static function value(array $params = []): string
    {
        $input = (string) ($params['input'] ?? '');
        if ($input === '') return '';

        $action = (string) ($params['action'] ?? 'synthesize');
        if ($action === 'transform') {
            return self::transform($input);
        }
        return self::synthesize($input);
    }

    public static function synthesize(string $url): string
    {
        return PlatformUrlService::remove_https($url);
    }

    public static function transform(string $url): string
    {
        return PlatformUrlService::add_https($url);
    }
}