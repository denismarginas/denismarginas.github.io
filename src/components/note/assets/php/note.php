<?php

class note
{
    public static function render(array $data = []): string
    {
        $text = trim((string) ($data['text'] ?? $data['content'] ?? ''));
        if ($text === '') return '';

        $status = strtolower(trim((string) ($data['status'] ?? 'info')));
        if (!in_array($status, ['info', 'success', 'warning', 'error'], true)) {
            $status = 'info';
        }

        return PlatformTemplateRenderer::render([
            'text' => $text,
            'status' => htmlspecialchars($status, ENT_QUOTES, 'UTF-8'),
        ]);
    }
}

