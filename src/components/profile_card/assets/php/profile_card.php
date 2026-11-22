<?php

class profile_card
{
    private const ALLOWED_TAGS = '<br><b><strong><i><em>';

    public static function render(array $data = []): string
    {
        $titleData = title::normalize($data['title'] ?? null, $data, ['base_class' => 'profile-card-title']);
        $title = is_scalar($titleData['text'] ?? $titleData['content'] ?? null) ? trim((string) ($titleData['text'] ?? $titleData['content'])) : '';
        $text = self::text($data['text'] ?? '');
        $details = self::details($data['details'] ?? []);
        $media = self::media($data, $title, self::style($data));

        if ($title === '' && $text === '' && $details === '' && $media === '') return '';

        $position = strtolower((string) ($data['image_position'] ?? 'left'));
        if (!in_array($position, ['left', 'right', 'top'], true)) $position = 'left';
        $style = self::style($data);

        $class = trim((string) ($data['class'] ?? ''));
        $id = trim((string) ($data['id'] ?? ''));

        return PlatformTemplateRenderer::render(__DIR__ . '/../html/template.html', [
            'extra_class' => $class !== '' ? ' ' . self::e($class) : '',
            'id_attr' => $id !== '' ? ' id="' . self::e($id) . '"' : '',
            'image_position' => $position,
            'image_style' => $style,
            'media' => $media,
            'title' => (string) PlatformComponentRenderer::render('title', $titleData),
            'text' => $text,
            'details' => $details,
        ]);
    }

    private static function style(array $data): string
    {
        $style = strtolower((string) ($data['image_style'] ?? 'rounded'));
        return in_array($style, ['circle', 'plain'], true) ? $style : 'rounded';
    }

    private static function media(array $data, string $title, string $style): string
    {
        $src = trim((string) ($data['img'] ?? ''));
        if ($src === '') return '';

        $img = (string) PlatformComponentRenderer::render('image', [
            'src' => $src,
            'alt' => (string) ($data['img_alt'] ?? $title),
            'class' => 'profile-card-image',
            'lazy' => true,
        ]);
        if (trim($img) === '') return '';

        if ($style === 'circle') {
            $noise = self::flag($data['noise'] ?? false) ? (string) PlatformComponentRenderer::render('overlay_noise', []) : '';
            $img = '<span class="profile-card-circle"><span class="profile-card-circle-background">' . $noise . '</span>'
                . '<span class="profile-card-circle-clip">' . $img . '</span></span>';
        }
        return '<div class="profile-card-media">' . $img . '</div>';
    }

    private static function text(mixed $text): string
    {
        $html = '';
        foreach ((array) $text as $paragraph) {
            $paragraph = trim(strip_tags((string) $paragraph, self::ALLOWED_TAGS));
            if ($paragraph !== '') $html .= '<p class="profile-card-text">' . $paragraph . '</p>';
        }
        return $html;
    }

    private static function details(mixed $details): string
    {
        if (!is_array($details) || empty($details)) return '';

        $html = '';
        foreach ($details as $detail) {
            if (!is_array($detail)) continue;
            $label = trim((string) ($detail['label'] ?? ''));
            $value = trim((string) ($detail['value'] ?? ''));
            if ($label === '' && $value === '') continue;

            $link = trim((string) ($detail['link'] ?? ''));
            $valueHtml = $link !== ''
                ? '<a class="profile-card-detail-value" href="' . self::e($link) . '"' . (preg_match('#^https?://#', $link) ? ' target="_blank" rel="noopener"' : '') . '>' . self::e($value) . '</a>'
                : '<span class="profile-card-detail-value">' . self::e($value) . '</span>';

            $tooltip = $detail['tooltip'] ?? $detail['note'] ?? null;
            if (is_string($tooltip)) $tooltip = ['text' => $tooltip, 'status' => 'warning'];
            if (is_array($tooltip) && isset($tooltip['data']) && is_array($tooltip['data'])) $tooltip = $tooltip['data'];
            if (is_array($tooltip) && trim((string) ($tooltip['text'] ?? '')) !== '') {
                $valueHtml = (string) PlatformComponentRenderer::render('tooltip', array_merge(
                    ['status' => 'warning', 'class' => 'profile-card-detail-tooltip'],
                    $tooltip,
                    ['trigger' => $valueHtml]
                ));
            }

            $icon = (string) PlatformComponentRenderer::render('svg', [
                'icon' => (string) ($detail['svg'] ?? 'chevron-right'),
                'class' => 'profile-card-detail-icon',
            ]);

            $html .= '<li class="profile-card-detail">'
                . '<span class="profile-card-detail-badge">' . $icon . '</span>'
                . '<span class="profile-card-detail-body">'
                . ($label !== '' ? '<b class="profile-card-detail-label">' . self::e($label) . '</b>' : '')
                . $valueHtml . '</span></li>';
        }

        return $html === '' ? '' : '<ul class="profile-card-details">' . $html . '</ul>';
    }

    private static function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    private static function flag(mixed $value): bool
    {
        if (is_bool($value)) return $value;
        if (is_int($value) || is_float($value)) return $value != 0;
        return !in_array(strtolower(trim((string) $value)), ['false', '0', 'no', 'off', ''], true);
    }
}
