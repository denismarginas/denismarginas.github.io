<?php

class PlatformTemplateRenderer
{
    public static function render(string|array $templatePath = '', array $data = []): string
    {
        if (is_array($templatePath)) {
            $data = $templatePath;
            $templatePath = '';
        }
        if ($templatePath === '') {
            $caller = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0]['file'] ?? '';
            if ($caller !== '') {
                $candidate = dirname($caller) . '/../html/template.html';
                if (!file_exists($candidate)) {
                    $candidate = dirname($caller) . '/../../html/template.html';
                }
                $templatePath = $candidate;
            }
        }
        if (!file_exists($templatePath) || $templatePath === '') {
            return '<!-- Component Render was not found: ' . htmlspecialchars($templatePath, ENT_QUOTES, 'UTF-8') . ' -->';
        }

        $html = file_get_contents($templatePath);
        foreach ($data as $key => $value) {
            $html = str_replace('{{ ' . $key . ' }}', $value, $html);
        }

        return $html;
    }
}
