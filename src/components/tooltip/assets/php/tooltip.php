<?php

class tooltip
{
    private const STATUSES = ['info', 'success', 'warning', 'error'];
    private const ICONS = ['info' => 'i', 'success' => '✓', 'warning' => '!', 'error' => '!'];
    private const ALLOWED_TAGS = '<br><b><strong><i><em>';

    private static int $count = 0;

    public static function render(array $data = []): string
    {
        $text = trim(strip_tags((string) ($data['text'] ?? ''), self::ALLOWED_TAGS));
        if ($text === '') return (string) ($data['trigger'] ?? '');

        $status = strtolower(trim((string) ($data['status'] ?? 'info')));
        if (!in_array($status, self::STATUSES, true)) $status = 'info';

        $position = strtolower((string) ($data['position'] ?? 'bottom')) === 'top' ? 'top' : 'bottom';
        $showIcon = ($data['icon'] ?? true) !== false;
        $class = trim((string) ($data['class'] ?? ''));

        self::$count++;

        return PlatformTemplateRenderer::render(__DIR__ . '/../html/template.html', [
            'extra_class' => $class !== '' ? ' ' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') : '',
            'status' => $status,
            'position' => $position,
            'tooltip_id' => 'tooltip-' . self::$count,
            'trigger' => (string) ($data['trigger'] ?? ''),
            'icon' => $showIcon ? '<span class="tooltip-icon" aria-hidden="true">' . self::ICONS[$status] . '</span>' : '',
            'text' => $text,
        ]);
    }
}
