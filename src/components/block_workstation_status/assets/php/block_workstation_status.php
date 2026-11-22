<?php

class block_workstation_status
{
    public static function render(array $data = []): string
    {
        $item = is_array($data['item'] ?? null) ? $data['item'] : (is_array($data['post_current_data'] ?? null) ? $data['post_current_data'] : []);
        $status = self::str($data['status'] ?? '') ?: self::str($item['data']['workstation']['status'] ?? '');
        if ($status === '') return '';

        $class = self::str($data['class'] ?? '');

        return PlatformTemplateRenderer::render([
            'extra_class' => $class !== '' ? ' ' . self::e($class) : '',
            'key' => self::e(preg_replace('/[^a-z0-9\-]/', '', strtolower($status))),
            'text' => self::e(PlatformTextService::ui('status_' . strtolower($status), ucfirst($status))),
        ]);
    }

    private static function str(mixed $value): string
    {
        return is_scalar($value) ? trim((string) $value) : '';
    }

    private static function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
