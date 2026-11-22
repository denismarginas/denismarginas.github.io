<?php

class header_heading
{
    public static function render_page_heading(array $data): string
    {
        $heading = $data['page_heading'] ?? '';
        if ($heading === '') return '';

        return PlatformTemplateRenderer::render(__DIR__ . '/../../html/template_page_heading.html', [
            'heading' => htmlspecialchars($heading),
        ]);
    }
}


