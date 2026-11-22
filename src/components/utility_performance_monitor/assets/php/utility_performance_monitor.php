<?php

class utility_performance_monitor
{
    public static function enabled(): bool
    {
        if (($GLOBALS['render_target'] ?? '') !== 'preview') return false;
        if (!class_exists('PlatformSettings')) return false;

        if (isset($_GET['perf'])) return filter_var($_GET['perf'], FILTER_VALIDATE_BOOLEAN);

        $tools = PlatformSettings::tools();
        return !empty($tools['performance_monitor']);
    }

    public static function render(array $data = []): string
    {
        if (!self::enabled()) return '';

        $dir = __DIR__ . '/..';
        $css = is_file($dir . '/css/style.css') ? (string) file_get_contents($dir . '/css/style.css') : '';
        $js = is_file($dir . '/js/utility_performance_monitor.js') ? (string) file_get_contents($dir . '/js/utility_performance_monitor.js') : '';

        return '<performance-monitor class="performance-monitor" data-performance-monitor hidden></performance-monitor>'
            . '<style data-performance-monitor-style>' . $css . '</style>'
            . '<script data-performance-monitor-script>' . $js . '</script>';
    }
}
