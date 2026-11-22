<?php

class resume_listing
{
    private const HTML_DIR = __DIR__ . '/../html/';

    public static function render(array $data = []): string
    {
        $items = self::items($data);
        $id = trim((string) ($data['id'] ?? '')) ?: 'resumes';

        $itemsHtml = '';
        foreach ($items as $item) {
            $itemsHtml .= self::item($item, $data);
        }

        if ($itemsHtml === '') {
            $empty = trim((string) ($data['empty_text'] ?? ''));
            return $empty === '' ? '' : '<p class="resume-listing-empty">' . self::e($empty) . '</p>';
        }

        return PlatformTemplateRenderer::render(self::HTML_DIR . 'template.html', [
            'id' => self::e($id),
            'count' => (string) count($items),
            'variant_class' => self::is_compact($data) ? ' resume-listing--compact' : '',
            'columns_attr' => ($cols = (int) ($data['columns'] ?? 0)) > 0 ? ' data-columns="' . min($cols, 6) . '" style="--resume-columns: ' . min($cols, 6) . ';"' : '',
            'items' => $itemsHtml,
        ]);
    }

    public static function items(array $data): array
    {
        $type = trim((string) ($data['type'] ?? '')) ?: 'resume';
        $items = PlatformDataService::get_collection($type) ?? [];
        if (!is_array($items)) return [];

        $langs = array_filter(array_map('strtolower', (array) ($data['lang'] ?? [])));
        if (!empty($langs)) {
            $items = array_filter($items, fn ($item): bool =>
                is_array($item) && in_array(self::lang($item), $langs, true)
            );
        }

        $items = PlatformComponentRenderer::value('utility_filter', [
            'items' => $items,
            'filter_by' => $data['filter_by'] ?? [],
            'exclude_by' => $data['exclude_by'] ?? [],
            'sort' => $data['sort'] ?? [['by' => 'date.publish', 'order' => 'desc']],
            'max_items' => $data['max_items'] ?? 0,
        ]);
        return is_array($items) ? $items : [];
    }

    private static function item(array $item, array $data): string
    {
        $d = is_array($item['data'] ?? null) ? $item['data'] : [];
        $title = trim((string) ($d['seo']['title'] ?? ''));
        $description = trim((string) ($d['seo']['description'] ?? ''));
        $publish = trim((string) ($d['date']['publish'] ?? ''));
        $lang = self::lang($item);
        $media = is_array($d['media'] ?? null) ? $d['media'] : [];
        $image = $media['image'] ?? '';
        $pdf = self::asset_url((string) ($media['pdf'] ?? ''));
        $img = is_array($image) ? (string) ($image['img'] ?? '') : (string) $image;
        $thumb = (string) ($media['thumbnail'] ?? (is_array($image) ? ($image['thumbnail'] ?? '') : '')) ?: $img;

        if ($title === '' && $thumb === '' && $pdf === '') return '';

        if (self::is_compact($data)) return self::compact_item($item, $data, $title, $description, $publish, $lang, $img !== '' ? $img : $thumb, $pdf);

        $titleHtml = $pdf !== ''
            ? '<a class="resume-card-title" href="' . self::e($pdf) . '" target="_blank" rel="noopener">' . self::e($title) . '</a>'
            : '<span class="resume-card-title">' . self::e($title) . '</span>';

        $dateHtml = '';
        $dateText = '';
        if ($publish !== '') {
            $format = (string) ($data['date_format'] ?? 'M j, Y');
            $dateText = (string) PlatformTextService::format_date($publish, $format);
            $dateHtml = '<time class="resume-card-date" datetime="' . self::e($publish) . '">' . self::e($dateText) . '</time>';
        }

        $langHtml = '';
        $langLabel = '';
        if ($lang !== '') {
            $labels = is_array($data['lang_labels'] ?? null) ? $data['lang_labels'] : [];
            $langLabel = (string) ($labels[$lang] ?? strtoupper($lang));
            $langHtml = '<span class="resume-card-lang">' . self::e($langLabel) . '</span>';
        }

        $downloadHtml = '';
        if ($pdf !== '') {
            $label = self::e((string) ($data['download_text'] ?? 'Download') . ($title !== '' ? ' ' . $title : ''));
            $downloadHtml = '<a class="btn btn-primary-small resume-card-download" href="' . self::e($pdf) . '" download'
                . ' aria-label="' . $label . '" title="' . $label . '">'
                . (string) PlatformComponentRenderer::render('svg', ['icon' => 'download', 'class' => 'resume-card-download-icon'])
                . '</a>';
        }

        $viewHtml = '';
        if (($data['preview_popup'] ?? true) !== false) {
            $viewHtml = (string) PlatformComponentRenderer::render('resume_view', [
                'image' => $img !== '' ? $img : $thumb,
                'title' => $title,
                'description' => $description,
                'lang' => $langLabel,
                'date' => $publish,
                'date_text' => $dateText,
                'pdf' => $pdf,
                'download_label' => $data['download_label'] ?? 'Download PDF',
                'open_text' => $data['open_text'] ?? 'Open PDF',
            ]);
        }

        $viewButtonHtml = '';
        if ($viewHtml !== '') {
            $viewLabel = self::e((string) ($data['view_text'] ?? 'View') . ($title !== '' ? ' ' . $title : ''));
            $viewButtonHtml = (string) PlatformComponentRenderer::render('popup', [
                'trigger' => '<button type="button" class="btn btn-primary-small resume-card-view" aria-label="' . $viewLabel . '" title="' . $viewLabel . '">'
                    . (string) PlatformComponentRenderer::render('svg', ['icon' => 'eye', 'class' => 'resume-card-view-icon'])
                    . '</button>',
                'content' => $viewHtml,
                'class' => 'resume-view-trigger',
                'hash' => trim((string) ($item['_id'] ?? '')),
            ]);
        }

        $actionsHtml = ($viewButtonHtml . $downloadHtml) !== ''
            ? '<div class="resume-card-actions">' . $viewButtonHtml . $downloadHtml . '</div>'
            : '';

        return PlatformTemplateRenderer::render(self::HTML_DIR . 'parts/item.html', [
            'item_id' => self::e(trim((string) ($item['_id'] ?? ''))),
            'id_attr' => ($itemId = trim((string) ($item['_id'] ?? ''))) !== '' ? ' id="' . self::e($itemId) . '"' : '',
            'model' => self::e(trim((string) ($d['model'] ?? ''))),
            'lang' => self::e($lang),
            'preview' => self::preview($thumb, $img, $title, $viewHtml),
            'title_html' => $titleHtml,
            'description_html' => $description !== '' ? '<span class="resume-card-description">' . self::e($description) . '</span>' : '',
            'lang_html' => $langHtml,
            'date_html' => $dateHtml,
            'actions_html' => $actionsHtml,
        ]);
    }

    private static function is_compact(array $data): bool
    {
        return strtolower(trim((string) ($data['variant'] ?? ''))) === 'compact';
    }

    private static function compact_item(array $item, array $data, string $title, string $description, string $publish, string $lang, string $image, string $pdf): string
    {
        $itemId = trim((string) ($item['_id'] ?? ''));
        [$dateHtml, $dateText] = self::date_parts($publish, $data);
        $langLabel = self::lang_label($lang, $data);
        $viewText = (string) ($data['view_text'] ?? 'View resume');
        $viewLabel = self::e($viewText . ($title !== '' ? ' - ' . $title : ''));
        $buttonInner = '<span class="btn-text">' . self::e($viewText) . '</span>'
            . (string) PlatformComponentRenderer::render('svg', ['icon' => 'eye', 'class' => 'btn-icon resume-card-view-icon']);

        $action = '';
        $page = trim((string) ($data['view_page'] ?? ''));
        if ($page !== '' && $itemId !== '') {
            $href = PlatformPathService::post_link($page) . '#' . rawurlencode($itemId);
            $action = '<a class="btn btn-primary-small resume-card-open" href="' . self::e($href) . '" aria-label="' . $viewLabel . '">' . $buttonInner . '</a>';
        } elseif ($image !== '') {
            $view = (string) PlatformComponentRenderer::render('resume_view', [
                'image' => $image,
                'title' => $title,
                'description' => $description,
                'lang' => $langLabel,
                'date' => $publish,
                'date_text' => $dateText,
                'pdf' => $pdf,
                'download_label' => $data['download_label'] ?? 'Download PDF',
                'open_text' => $data['open_text'] ?? 'Open PDF',
            ]);
            if ($view !== '') {
                $action = (string) PlatformComponentRenderer::render('popup', [
                    'trigger' => '<button type="button" class="btn btn-primary-small resume-card-open" aria-label="' . $viewLabel . '">' . $buttonInner . '</button>',
                    'content' => $view,
                    'class' => 'resume-view-trigger',
                    'hash' => $itemId,
                ]);
            }
        }

        return PlatformTemplateRenderer::render(self::HTML_DIR . 'parts/item_compact.html', [
            'item_id' => self::e($itemId),
            'id_attr' => $itemId !== '' ? ' id="' . self::e($itemId) . '"' : '',
            'model' => self::e(trim((string) ($item['data']['model'] ?? ''))),
            'lang' => self::e($lang),
            'preview' => '<span class="resume-card-badge" aria-hidden="true">' . (string) PlatformComponentRenderer::render('svg', ['icon' => 'resume', 'class' => 'resume-card-badge-icon']) . '</span>',
            'title_html' => '<span class="resume-card-title">' . self::e($title) . '</span>',
            'description_html' => '',
            'lang_html' => $langLabel !== '' ? '<span class="resume-card-lang">' . self::e($langLabel) . '</span>' : '',
            'date_html' => $dateHtml,
            'actions_html' => $action !== '' ? '<div class="resume-card-actions">' . $action . '</div>' : '',
        ]);
    }

    private static function date_parts(string $publish, array $data): array
    {
        if ($publish === '') return ['', ''];
        $text = (string) PlatformTextService::format_date($publish, (string) ($data['date_format'] ?? 'M j, Y'));
        return ['<time class="resume-card-date" datetime="' . self::e($publish) . '">' . self::e($text) . '</time>', $text];
    }

    private static function lang_label(string $lang, array $data): string
    {
        if ($lang === '') return '';
        $labels = is_array($data['lang_labels'] ?? null) ? $data['lang_labels'] : [];
        return (string) ($labels[$lang] ?? strtoupper($lang));
    }

    private static function lang(array $item): string
    {
        return strtolower(trim((string) ($item['data']['lang'] ?? $item['settings']['lang'] ?? '')));
    }

    private static function preview(string $thumb, string $img, string $title, string $viewHtml): string
    {
        if ($thumb === '') return '';

        $thumbHtml = (string) PlatformComponentRenderer::render('image', [
            'src' => $thumb,
            'alt' => $title !== '' ? $title . ' - preview' : 'Resume preview',
            'class' => 'resume-card-thumbnail',
            'lazy' => true,
        ]);
        if ($thumbHtml === '') return '';

        $thumbHtml .= (string) PlatformComponentRenderer::render('svg', ['icon' => 'resume', 'class' => 'resume-card-icon']);

        $content = $viewHtml;
        if ($content === '' && $img !== '') {
            $content = (string) PlatformComponentRenderer::render('image', [
                'src' => $img,
                'alt' => $title,
                'class' => 'resume-card-full',
            ]);
        }

        if ($content === '') {
            return '<div class="resume-card-preview">' . $thumbHtml . '</div>';
        }

        return (string) PlatformComponentRenderer::render('popup', [
            'trigger' => $thumbHtml,
            'content' => $content,
            'class' => 'resume-card-preview',
        ]);
    }

    private static function asset_url(string $path): string
    {
        $path = trim($path);
        if ($path === '') return '';
        if (PlatformUrlService::is_external_url($path)) return $path;
        return rtrim(PlatformPathService::asset_relative_prefix(), '/') . '/' . ltrim($path, '/');
    }

    private static function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
