<?php

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../core/autoload.php';
require_once __DIR__ . '/../preview/preview_load.php';
require_once __DIR__ . '/../preview/preview_post.php';
require_once __DIR__ . '/../preview/preview_render.php';
require_once __DIR__ . '/posts_helpers.php';

function platform_send_json(array $data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}

function platform_compile_data_name(string $file): string
{
    return (string) preg_replace(['/^data_/', '/\.json$/'], '', basename($file));
}

function platform_compile_language(array $state, string $lang, callable $emit, array &$results): int
{
    $count = 0;
    $jobs = [];

    foreach (array_merge(platform_get_post_types(), platform_get_routable_collection_types()) as $t) {
        $postType = $t['post_type'];
        $posts = PlatformDataService::get_data(platform_compile_data_name($t['file']));
        if (!$posts) continue;
        foreach ($posts as $post) {
            if (($t['kind'] ?? '') === 'collection' && ($post['settings']['render'] ?? true) === false) continue;
            $jobs[] = [
                'post' => $post,
                'post_type' => $postType,
                'structure' => $t['structure'] ?? ($postType === 'project' ? 'project_structure' : 'page_structure'),
                'seo' => $t['seo'] ?? ($postType === 'project' ? 'seo_project' : 'seo_page'),
                'paths' => $state['global_paths'][$postType] ?? [],
                'root' => $t['root'] ?? '',
            ];
        }
    }

    foreach (platform_get_taxonomy_types() as $t) {
        $terms = PlatformDataService::get_data(platform_compile_data_name($t['file']));
        if (!$terms) continue;
        foreach ($terms as $term) {
            $jobs[] = [
                'post' => $term,
                'post_type' => 'taxonomy:' . $t['taxonomy'],
                'structure' => 'page_structure',
                'seo' => '',
                'paths' => [],
                'root' => $t['root'] ?? '',
            ];
        }
    }

    foreach ($jobs as $job) {
        $id = $job['post']['_id'] ?? '';
        if (!$id) continue;
        $postType = $job['post_type'];
        $structure = $state['structure_vars'][$job['structure']] ?? [];

        try {
            $postData = platform_normalize_preview_post($job['post']);
            if ($postType === 'project') {
                $postData = platform_generate_project_seo($postData, $state['seo_vars'][$job['seo']] ?? [], $id);
            }

            $globalContentPath = $job['paths']['global_content_path'] ?? '';
            $globalImgPath = $job['paths']['global_img_path'] ?? '';
            $globalVidPath = $job['paths']['global_vid_path'] ?? '';

            $bodyHtml = platform_render_body_sections($postData, $globalContentPath, $globalImgPath, $globalVidPath);
            $bodyHtml = platform_wrap_body_if_needed($bodyHtml, $postData, $structure['body_wrapper'] ?? null);

            $pageData = [
                'header_component' => $structure['header'] ?? null,
                'footer_component' => $structure['footer'] ?? null,
                'compile_assets' => $state['compile_assets'],
                'body_content' => $bodyHtml,
                'global_content_path' => $globalContentPath,
                'global_img_path' => $globalImgPath,
                'global_vid_path' => $globalVidPath,
                'seo' => $postData['seo'] ?? [],
                'post_current_data' => $postData,
            ];
            $pageStructureName = ($structure['page_structure'] ?? '') ?: 'page_constructor';

            $row = $emit($pageData, $pageStructureName, $id, $postType, $job['root']);
            $results[] = ['_id' => $id, 'post_type' => $postType, 'lang' => $lang] + $row;
            if ($row['success']) $count++;
        } catch (Throwable $e) {
            $results[] = ['_id' => $id, 'post_type' => $postType, 'lang' => $lang, 'success' => false, 'error' => $e->getMessage()];
        }
    }

    return $count;
}

$state = platform_load_preview_state();

if (!$state['html_compile']) {
    platform_send_json(['ok' => true, 'html_compile' => false, 'message' => 'html_compile flag is off in the Render card.', 'count' => 0, 'results' => []]);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    platform_send_json(['ok' => false, 'message' => 'Use POST'], 405);
    exit;
}

@set_time_limit(0);

$scssFlags = $state['compile_scss_flags'];
if (!empty($scssFlags['compile_scss_everytime'])) {
    PlatformScssBuilder::run($scssFlags);
}

$results = [];
$count = 0;

$languages = array_keys(PlatformPathService::languages());
$defaultLang = PlatformDataService::default_language();
$frontendLangs = PlatformPathService::frontend_languages();
$staticLangs = PlatformPathService::static_languages();
$GLOBALS['i18n_build_id'] = base_convert((string) time(), 10, 36);

foreach ($staticLangs as $lang) {
    PlatformDataService::set_language($lang);
    if ($lang !== $defaultLang) PlatformI18nFrontendBuilder::remove_dir(PlatformI18nFrontendBuilder::dist_dir($lang));

    $count += platform_compile_language($state, $lang, function ($pageData, $pageStructureName, $id, $postType, $root) use ($state) {
        $rel = platform_write_dist_html($pageData, $pageStructureName, $id, $postType, $state['html_compile_folder'], $root);
        return ['success' => $rel !== '', 'file' => $rel];
    }, $results);
}

$i18n = [];
foreach ($frontendLangs as $lang) {
    $old = [
        ENGINE_PROJECT_ROOT . '/dist/' . $lang => 'Removed the static pages of this language (it is translated in the browser now).',
        PlatformI18nFrontendBuilder::dist_dir($lang) => 'Removed the old browser dictionaries of this language (the browser reads src/content/json/data/' . $lang . '/ now).',
    ];
    foreach ($old as $dir => $message) {
        if (!is_dir($dir)) continue;
        PlatformI18nFrontendBuilder::remove_dir($dir);
        $results[] = ['_id' => 'dist/' . ltrim(str_replace(ENGINE_PROJECT_ROOT . '/dist', '', $dir), '/') . '/', 'post_type' => 'i18n', 'lang' => $lang, 'success' => true, 'message' => $message];
    }
    $i18n[$lang] = ['mode' => 'frontend', 'source' => 'src/content/json/data/' . $lang . '/'];
}
$i18nRoot = PlatformI18nFrontendBuilder::dist_dir();
if (is_dir($i18nRoot) && count(scandir($i18nRoot) ?: []) <= 2) @rmdir($i18nRoot);

PlatformDataService::set_language($defaultLang);

$seoFiles = null;
try {
    $seoFiles = PlatformSeoFilesBuilder::maybe_build_after_render();
} catch (Throwable $e) {
    $seoFiles = ['ok' => false, 'message' => $e->getMessage()];
}
if ($seoFiles) {
    foreach ($seoFiles['results'] ?? [] as $r) {
        $results[] = ['_id' => basename($r['file']), 'post_type' => 'seo', 'success' => $r['success']];
    }
    unset($seoFiles['results']);
}

platform_send_json([
    'ok' => true,
    'html_compile' => true,
    'languages' => $languages,
    'static_languages' => $staticLangs,
    'frontend_languages' => $frontendLangs,
    'count' => $count,
    'results' => $results,
    'i18n' => $i18n,
    'seo_files' => $seoFiles,
]);
