<?php

class hero
{
    public static function render(array $data = []): string
    {
        $postData = $data['post_current_data'] ?? [];

        $layout = (string) ($data['layout'] ?? $data['variant'] ?? 'standard');
        $imgPath = (string) ($data['img'] ?? $data['background'] ?? '');
        $title = self::resolve_text_field($data, $postData, 'title');
        $description = self::resolve_text_field($data, $postData, 'description');

        $bgHtml = '';
        $resolvedImg = self::resolve_image_src($imgPath, $data);

        if (is_array($data['video'] ?? null)) {
            $videoData = array_merge(['overlay' => 0.6], $data['video']);
            if (!isset($videoData['poster']) && $resolvedImg !== '' && !PlatformUrlService::is_external_url($resolvedImg)) {
                $videoData['poster'] = $resolvedImg;
            }
            $bgHtml = (string) PlatformComponentRenderer::render('video_background', $videoData);
        }

        if ($bgHtml === '' && $resolvedImg !== '') {
            $bgHtml = PlatformComponentRenderer::render('image', [
                'src' => $resolvedImg,
                'alt' => $title !== '' ? $title : '',
                'class' => 'hero-bg',
            ]);
        }

        $textContentHtml = '';
        if ($title !== '' || $description !== '') {
            $textContentHtml = PlatformTemplateRenderer::render(__DIR__ . '/../html/text_content.html', [
                'title' => htmlspecialchars($title, ENT_QUOTES, 'UTF-8'),
                'description' => htmlspecialchars($description, ENT_QUOTES, 'UTF-8'),
            ]);
        }

        $childrenHtml = '';
        $hasNoiseChild = false;
        $children = $data['children'] ?? [];
        if (!is_array($children)) {
            $children = [];
        }
        foreach ($children as $child) {
            $childComponent = (string) ($child['component'] ?? '');
            if ($childComponent === '') {
                continue;
            }
            $childData = $child['data'] ?? [];
            if (!is_array($childData)) {
                $childData = [];
            }
            if (str_replace('-', '_', $childComponent) === 'overlay_noise') {
                $hasNoiseChild = true;
            }
            $childrenHtml .= PlatformComponentRenderer::render(str_replace('-', '_', $childComponent), $childData);
        }

        if (($data['noise'] ?? true) !== false && empty($hasNoiseChild)) {
            $childrenHtml .= (string) PlatformComponentRenderer::render('overlay_noise', []);
        }

        return PlatformTemplateRenderer::render([
            'layout_attr' => htmlspecialchars($layout, ENT_QUOTES, 'UTF-8'),
            'bg_html' => $bgHtml,
            'text_content_html' => $textContentHtml,
            'children_html' => $childrenHtml,
        ]);
    }

    public static function resolve_image_src(string $imgPath, array $data): string
    {
        if ($imgPath === '') {
            return '';
        }
        if (PlatformUrlService::is_external_url($imgPath)) {
            return $imgPath;
        }

        $globalImg = rtrim((string) ($data['global_img_path'] ?? ''), '/');
        $raw = ltrim($imgPath, '/');
        $candidates = [];
        if ($globalImg !== '' && strpos($raw, $globalImg) !== 0) {
            $candidates[] = $globalImg . '/' . $raw;
        }
        if (strpos($raw, 'src/content/') !== 0) {
            $candidates[] = 'src/content/img/' . $raw;
        }
        $candidates[] = $raw;

        foreach ($candidates as $candidate) {
            $absolute = defined('ENGINE_PROJECT_ROOT') ? ENGINE_PROJECT_ROOT . '/' . $candidate : $candidate;
            if (file_exists($absolute)) {
                return $candidate;
            }
        }

        return '';
    }

    public static function resolve_text_field(array $data, array $postData, string $key): string
    {
        $raw = $data[$key] ?? null;
        if (!is_string($raw) || $raw === '') {
            return '';
        }
        if (str_starts_with($raw, '@')) {
            $path = substr($raw, 1);
            $value = PlatformDataService::resolve_path_string($postData, $path);
            if ($value === null && str_starts_with($path, 'data.')) {
                $value = PlatformDataService::resolve_path_string($postData, substr($path, 5));
            }
            return $value ?? '';
        }
        return $raw;
    }
}



