<?php

class scroll_progress
{
    private const POSITIONS = ['bottom', 'top', 'fixed'];

    public static function render(array $data = []): string
    {
        $position = strtolower(trim((string) ($data['position'] ?? 'bottom')));
        if (!in_array($position, self::POSITIONS, true)) $position = 'bottom';

        $target = trim((string) ($data['target'] ?? 'page'));
        $class = trim((string) ($data['class'] ?? ''));

        return PlatformTemplateRenderer::render([
            'position' => $position,
            'extra_class' => $class !== '' ? ' ' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') : '',
            'target_attr' => $target !== '' && $target !== 'page'
                ? ' data-scroll-progress-target="' . htmlspecialchars($target, ENT_QUOTES, 'UTF-8') . '"'
                : '',
        ]);
    }
}
