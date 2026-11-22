<?php

class resume_view
{
    public static function render(array $data = []): string
    {
        $img = trim((string) ($data['image'] ?? ''));
        if ($img === '') return '';

        $title = trim((string) ($data['title'] ?? ''));
        $description = trim((string) ($data['description'] ?? ''));
        $lang = trim((string) ($data['lang'] ?? ''));
        $date = trim((string) ($data['date'] ?? ''));
        $dateText = trim((string) ($data['date_text'] ?? '')) ?: $date;
        $pdf = trim((string) ($data['pdf'] ?? ''));

        $imageHtml = (string) PlatformComponentRenderer::render('image', [
            'src' => $img,
            'alt' => $title !== '' ? $title : 'Resume',
            'class' => 'resume-view-image',
        ]);
        if ($imageHtml === '') return '';

        $meta = ($lang !== '' ? '<span class="resume-view-lang">' . self::e($lang) . '</span>' : '')
            . ($dateText !== '' ? '<time class="resume-view-date"' . ($date !== '' ? ' datetime="' . self::e($date) . '"' : '') . '>' . self::e($dateText) . '</time>' : '');

        $buttons = '';
        if ($pdf !== '') {
            $buttons .= '<a class="btn btn-primary resume-view-download" href="' . self::e($pdf) . '" download>'
                . '<span class="btn-text">' . self::e((string) ($data['download_label'] ?? 'Download PDF')) . '</span>'
                . (string) PlatformComponentRenderer::render('svg', ['icon' => 'download', 'class' => 'btn-icon'])
                . '</a>';
            $buttons .= '<a class="btn btn-outline resume-view-open" href="' . self::e($pdf) . '" target="_blank" rel="noopener">'
                . '<span class="btn-text">' . self::e((string) ($data['open_text'] ?? 'Open PDF')) . '</span>'
                . (string) PlatformComponentRenderer::render('svg', ['icon' => 'new-tab', 'class' => 'btn-icon'])
                . '</a>';
        }

        return '<div class="resume-view bg-radial-primary">'
            . '<div class="resume-view-media">' . $imageHtml . '</div>'
            . '<div class="resume-view-info">'
            . ($title !== '' ? '<h3 class="resume-view-title title-card font-secondary">' . self::e($title) . '</h3>' : '')
            . ($description !== '' ? '<p class="resume-view-description">' . self::e($description) . '</p>' : '')
            . ($meta !== '' ? '<div class="resume-view-meta">' . $meta . '</div>' : '')
            . ($buttons !== '' ? '<div class="resume-view-actions">' . $buttons . '</div>' : '')
            . '</div>'
            . '</div>';
    }

    private static function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
