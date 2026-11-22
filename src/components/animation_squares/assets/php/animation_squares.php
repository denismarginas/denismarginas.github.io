<?php

class animation_squares
{
    public static function render(array $data): string
    {
        $extraClass = (string) ($data['class'] ?? '');
        return PlatformTemplateRenderer::render([
            'extra_class' => htmlspecialchars($extraClass, ENT_QUOTES, 'UTF-8'),
        ]);
    }
}