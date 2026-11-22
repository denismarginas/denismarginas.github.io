<?php

class text_collapse
{
    public static function render(array $data = []): string
    {
        $raw = is_scalar($data['text'] ?? null) ? trim((string) $data['text']) : '';
        if ($raw === '') return '';

        $escape = ($data['escape'] ?? false) === true;
        $full = $escape ? nl2br(self::e($raw)) : $raw;
        $max = max(10, (int) ($data['max_chars'] ?? 300));
        $class = is_scalar($data['class'] ?? null) ? trim((string) $data['class']) : '';
        $extra = $class !== '' ? ' ' . self::e($class) : '';

        $plain = self::plain($raw, !$escape);
        if (mb_strlen($plain) <= $max) {
            return '<div class="text-collapse text-collapse-static' . $extra . '">' . $full . '</div>';
        }

        $short = rtrim(mb_substr($plain, 0, $max - 3)) . '...';

        return PlatformTemplateRenderer::render([
            'extra_class' => $extra,
            'short' => nl2br(self::e($short)),
            'full' => $full,
            'icon' => self::icon($data),
            'more' => self::e(is_scalar($data['more_label'] ?? null) ? (string) $data['more_label'] : PlatformTextService::ui('show_more', 'Show More')),
            'less' => self::e(is_scalar($data['less_label'] ?? null) ? (string) $data['less_label'] : PlatformTextService::ui('show_less', 'Show Less')),
        ]);
    }

    private static function plain(string $text, bool $isHtml): string
    {
        if ($isHtml) {
            $text = preg_replace('#<\s*/?\s*br\s*/?\s*>#i', "\n", $text);
            $text = preg_replace('#</\s*(p|div|li|ul|ol|h[1-6])\s*>#i', "\n", $text);
            $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        $text = preg_replace('/[ \t]+/u', ' ', str_replace(["\r\n", "\r"], "\n", $text));
        return trim(preg_replace("/\n{3,}/", "\n\n", $text));
    }

    private static function icon(array $data): string
    {
        $name = array_key_exists('icon', $data) ? (is_scalar($data['icon']) ? trim((string) $data['icon']) : '') : 'chevron-down';
        return $name !== '' ? (string) PlatformComponentRenderer::render('svg', ['icon' => $name, 'class' => 'text-collapse-toggle-icon']) : '';
    }

    private static function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
