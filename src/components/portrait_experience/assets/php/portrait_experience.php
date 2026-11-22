<?php

class portrait_experience
{
    private const STYLES = ['circle', 'arch'];
    private const MAX_BADGES = 3;

    public static function render(array $data = []): string
    {
        $src = is_scalar($data['img'] ?? null) ? trim((string) $data['img']) : '';
        if ($src === '') return '';

        $image = (string) PlatformComponentRenderer::render('image', [
            'src' => $src,
            'alt' => is_scalar($data['alt'] ?? null) ? (string) $data['alt'] : 'Portrait',
            'class' => 'portrait-experience-img',
            'lazy' => true,
        ]);
        if (trim($image) === '') return '';

        $style = strtolower(is_scalar($data['style'] ?? null) ? trim((string) $data['style']) : 'circle');
        if (!in_array($style, self::STYLES, true)) $style = 'circle';

        $defaultIcon = $style === 'circle' ? 'dots-graphic' : '';
        $icon = array_key_exists('graphic', $data) ? (is_scalar($data['graphic']) ? trim((string) $data['graphic']) : '') : $defaultIcon;
        $graphic = $icon !== ''
            ? '<div class="portrait-experience-graphic" aria-hidden="true">' . PlatformComponentRenderer::render('svg', ['icon' => $icon, 'class' => 'portrait-experience-dots']) . '</div>'
            : '';

        $class = 'portrait-experience--' . $style;
        $extra = is_scalar($data['class'] ?? null) ? trim((string) $data['class']) : '';
        if ($extra !== '') $class .= ' ' . $extra;

        $id = is_scalar($data['id'] ?? null) ? trim((string) $data['id']) : '';
        $maxWidth = is_scalar($data['max_width'] ?? null) ? trim((string) $data['max_width']) : '';
        $styleAttr = $maxWidth !== '' && !preg_match('/[;{}<>"\']/', $maxWidth) ? ' style="max-width: ' . htmlspecialchars($maxWidth, ENT_QUOTES, 'UTF-8') . ';"' : '';

        return PlatformTemplateRenderer::render(__DIR__ . '/../html/template.html', [
            'extra_class' => htmlspecialchars($class, ENT_QUOTES, 'UTF-8'),
            'id_attr' => ($id !== '' ? ' id="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '"' : '') . $styleAttr,
            'image' => $image,
            'graphic' => $graphic,
            'badges' => self::badges($data['badges'] ?? null),
        ]);
    }

    private static function badges(mixed $badges): string
    {
        if (!is_array($badges)) return '';

        $html = '';
        $n = 0;
        foreach ($badges as $badge) {
            if (!is_scalar($badge) || trim((string) $badge) === '') continue;
            if (++$n > self::MAX_BADGES) break;
            $html .= '<span class="portrait-experience-badge portrait-experience-badge-' . $n . '"><i></i>'
                . htmlspecialchars(trim((string) $badge), ENT_QUOTES, 'UTF-8') . '</span>';
        }

        return $html !== '' ? '<div class="portrait-experience-badges" aria-hidden="true">' . $html . '</div>' : '';
    }
}
