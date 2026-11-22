<?php

require_once __DIR__ . '/preview_load.php';
require_once __DIR__ . '/preview_post.php';
require_once __DIR__ . '/preview_render.php';

header('Content-Type: text/html; charset=UTF-8');

$postId = isset($_GET['_id']) ? trim($_GET['_id']) : (isset($_GET['post_id']) ? trim($_GET['post_id']) : '');

if (!$postId) {
    http_response_code(400);
    echo '<h1>Missing _id parameter</h1>';
    exit;
}

$state = platform_load_preview_state();

$scssFlags = $state['compile_scss_flags'];
if (!empty($scssFlags['compile_scss_everytime'])) {
    PlatformScssBuilder::run($scssFlags);
}

$found = platform_find_preview_post($postId);
$notFound = false;
if (!$found['post']) {
    $notFoundId = (string) (PlatformConfig::getRoutingMappings()['404_post_id'] ?? '404');
    $found = $notFoundId !== '' ? platform_find_preview_post($notFoundId) : $found;
    http_response_code(404);
    if (!$found['post']) {
        echo '<h1>Post not found: ' . htmlspecialchars($postId) . '</h1>';
        exit;
    }
    $GLOBALS['page_not_found_request'] = $postId;
    $notFound = true;
}

$postData = platform_normalize_preview_post($found['post']);
$postType = $found['type'];
$postConfig = $found['config'] ?? [];

$structureKey = $postConfig['structure'] ?? ($postType === 'project' ? 'project_structure' : 'page_structure');
$seoKey = $postConfig['seo'] ?? ($postType === 'project' ? 'seo_project' : 'seo_page');

$headerComponent = $state['structure_vars'][$structureKey]['header'] ?? null;
$footerComponent = $state['structure_vars'][$structureKey]['footer'] ?? null;
$pageStructureComponent = $state['structure_vars'][$structureKey]['page_structure'] ?? null;
$bodyWrapper = $state['structure_vars'][$structureKey]['body_wrapper'] ?? null;
$seoConfig = $state['seo_vars'][$seoKey] ?? [];

if ($postType === 'project') {
    $postData = platform_generate_project_seo($postData, $seoConfig, $postId);
}

$postGlobalPaths = $state['global_paths'][$postType] ?? [];
$globalContentPath = $postGlobalPaths['global_content_path'] ?? '';
$globalImgPath = $postGlobalPaths['global_img_path'] ?? '';
$globalVidPath = $postGlobalPaths['global_vid_path'] ?? '';

$structureData = [
    'header_component' => $headerComponent,
    'footer_component' => $footerComponent,
    'global_content_path' => $globalContentPath,
    'global_img_path' => $globalImgPath,
    'global_vid_path' => $globalVidPath,
    'compile_assets' => $state['compile_assets'],
];
$pageStructureName = $pageStructureComponent ?: 'page_constructor';
$seo = $postData['seo'] ?? [];

if ($state['debug_mode']) {
    platform_render_debug_page($pageStructureName, $structureData, $postData, $globalContentPath, $globalImgPath, $globalVidPath);
    exit;
}

$bodyHtml = platform_render_body_sections($postData, $globalContentPath, $globalImgPath, $globalVidPath);
$bodyHtml = platform_wrap_body_if_needed($bodyHtml, $postData, $bodyWrapper);

$pageData = array_merge($structureData, [
    'body_content' => $bodyHtml,
    'global_content_path' => $globalContentPath,
    'global_img_path' => $globalImgPath,
    'global_vid_path' => $globalVidPath,
    'seo' => $seo,
    'post_current_data' => $postData,
]);

$GLOBALS['render_target'] = 'preview';
echo PlatformComponentRenderer::render($pageStructureName, $pageData);

if ($state['html_compile'] && !$notFound) {
    platform_write_dist_html($pageData, $pageStructureName, $postId, $postType, $state['html_compile_folder'], $postConfig['root'] ?? '');
}
