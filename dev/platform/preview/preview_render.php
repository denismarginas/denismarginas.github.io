<?php

function platform_render_debug_page(string $pageStructureName, array $structureData, array $postData, string $globalContentPath, string $globalImgPath, string $globalVidPath): void
{
    $debugHtml = '<pre style="padding:20px;background:#141414;color:#47cbe3;overflow:auto;white-space:pre-wrap;font-size:12px;">'
               . htmlspecialchars(json_encode($postData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE))
               . '</pre>';
    echo PlatformComponentRenderer::render($pageStructureName, array_merge($structureData, [
        'body_content' => $debugHtml,
        'global_content_path' => $globalContentPath,
        'global_img_path' => $globalImgPath,
        'global_vid_path' => $globalVidPath,
        'seo' => $postData['seo'] ?? [],
        'post_current_data' => $postData,
    ]));
}

function platform_render_body_sections(array $postData, string $globalContentPath, string $globalImgPath, string $globalVidPath): string
{
    $bodyHtml = '';
    $contentItems = $postData['data']['content'] ?? $postData['content'] ?? [];

    foreach ($contentItems as $item) {
        $componentName = $item['component'] ?? '';
        if (!$componentName) continue;

        $componentData = array_merge($item['data'] ?? [], [
            'post_current_data' => $postData,
            'global_content_path' => $globalContentPath,
            'global_img_path' => $globalImgPath,
            'global_vid_path' => $globalVidPath,
            'children' => $item['children'] ?? $item['data']['children'] ?? [],
        ]);

        if (!array_key_exists('transition', $componentData)) {
            $rule = PlatformComponentRenderer::transition_rule($componentName);
            if ($rule !== null) $componentData['transition'] = $rule;
        }

        $bodyHtml .= PlatformComponentRenderer::render(str_replace('-', '_', $componentName), $componentData);
    }

    return $bodyHtml;
}

function platform_wrap_body_if_needed(string $bodyHtml, array $postData, ?string $bodyWrapper): string
{
    if ($bodyWrapper && $bodyHtml !== '' && isset($postData['data'])) {
        $wrapperData = array_merge($postData['data'], [
            'postContent' => $bodyHtml,
            'postCurrentData' => $postData['data'],
            'post_current_data' => $postData['data'],
        ]);
        return PlatformComponentRenderer::render($bodyWrapper, $wrapperData);
    }
    return $bodyHtml;
}

function platform_dist_rel_path(string $postId, string $postType, bool $htmlCompileFolder, string $root = ''): string
{
    $globalSettings = PlatformDataService::get_data('settings_routing');
    $extension = $globalSettings['routing']['extension'] ?? $globalSettings['page_slug_extension'] ?? '.html';

    $folder = $root !== '' ? trim($root, '/') : '';
    return PlatformPathService::dist_lang_prefix() . ($htmlCompileFolder && $folder !== '' && $postType !== 'page' ? $folder . '/' : '') . $postId . $extension;
}

function platform_render_dist_html(array $pageData, string $pageStructureName, string $postId, string $postType, bool $htmlCompileFolder, string $root = ''): array
{
    $relPath = platform_dist_rel_path($postId, $postType, $htmlCompileFolder, $root);

    $GLOBALS['render_target'] = 'dist';
    $GLOBALS['dist_rel_path'] = $relPath;

    mt_srand(crc32($relPath));

    $postData = $pageData['post_current_data'] ?? [];
    if (!empty($postData)) {
        $pageData['body_content'] = platform_render_body_sections(
            $postData,
            $pageData['global_content_path'] ?? '',
            $pageData['global_img_path'] ?? '',
            $pageData['global_vid_path'] ?? ''
        );
    }

    $html = PlatformComponentRenderer::render($pageStructureName, $pageData);
    mt_srand();

    return ['rel_path' => $relPath, 'html' => $html];
}

function platform_write_dist_html(array $pageData, string $pageStructureName, string $postId, string $postType, bool $htmlCompileFolder, string $root = ''): string
{
    if (PlatformPathService::is_frontend_language()) return '';

    $page = platform_render_dist_html($pageData, $pageStructureName, $postId, $postType, $htmlCompileFolder, $root);

    $distAbsPath = ENGINE_PROJECT_ROOT . '/dist/' . $page['rel_path'];
    if (!is_dir(dirname($distAbsPath))) {
        mkdir(dirname($distAbsPath), 0777, true);
    }
    file_put_contents($distAbsPath, $page['html']);
    return $page['rel_path'];
}
