<?php

require_once __DIR__ . '/engine-bridge.php';
require_once __DIR__ . '/../core/autoload.php';

function platform_load_preview_state(): array
{
    $typesPath = ENGINE_PROJECT_ROOT . '/src/content/json/data/settings/data_settings_types.json';
    $debugMode = false;
    $compileAssets = false;
    $htmlCompile = false;
    $htmlCompileFolder = false;
    $structureVars = [];
    $seoVars = [];
    $globalPaths = [];
    $compileScssFlags = [
        'compile_scss_platform_components' => true,
        'compile_scss_platform_assets' => true,
        'compile_scss_src_components' => true,
        'compile_scss_assets' => false,
        'compile_scss_everytime' => true,
    ];

    $workflow = PlatformWorkflowService::read();

    $renderSection = is_array($workflow['render'] ?? null) ? $workflow['render'] : [];
    $debugMode = (($renderSection['debug_post_data'] ?? false) === true) || ((string) ($renderSection['debug_post_data'] ?? '') === 'true');
    $scssSection = PlatformWorkflowService::section('compile_scss');
    $compileAssetsRaw = array_key_exists('compile_assets', $scssSection) ? $scssSection['compile_assets'] : ($renderSection['compile_assets'] ?? false);
    $compileAssets = $compileAssetsRaw === true || (string) $compileAssetsRaw === 'true';
    $htmlCompile = (($renderSection['html_compile'] ?? false) === true) || ((string) ($renderSection['html_compile'] ?? '') === 'true');
    $htmlCompileFolder = (($renderSection['html_compile_folder'] ?? false) === true) || ((string) ($renderSection['html_compile_folder'] ?? '') === 'true');

    foreach (PlatformWorkflowService::section('compile_scss') as $name => $value) {
        if (array_key_exists($name, $compileScssFlags)) $compileScssFlags[$name] = $value === true || (string) $value === 'true';
    }

    foreach (['project_structure', 'page_structure'] as $structureKey) {
        $structureVars[$structureKey] = PlatformWorkflowService::section($structureKey);
    }
    foreach (['seo_project', 'seo_page'] as $seoKey) {
        $seoVars[$seoKey] = PlatformWorkflowService::section($seoKey);
    }

    $typesPath = ENGINE_PROJECT_ROOT . '/src/content/json/data/settings/data_settings_types.json';
    if (file_exists($typesPath)) {
        $types = json_decode(file_get_contents($typesPath), true);
        if ($types && !empty($types['post'])) {
            foreach ($types['post'] as $typeKey => $config) {
                $globalPaths[$typeKey] = [
                    'global_content_path' => $config['global_content_path'] ?? '',
                    'global_img_path' => $config['global_img_path'] ?? '',
                    'global_vid_path' => $config['global_vid_path'] ?? '',
                ];
            }
        }
        foreach (($types['collection'] ?? []) as $typeKey => $config) {
            if (($config['routable'] ?? false) !== true || isset($globalPaths[$typeKey])) continue;
            $globalPaths[$typeKey] = [
                'global_content_path' => $config['global_content_path'] ?? '',
                'global_img_path' => $config['global_img_path'] ?? '',
                'global_vid_path' => $config['global_vid_path'] ?? '',
            ];
        }
    }

    if (isset($_GET['debug_post_data']) && $_GET['debug_post_data'] === 'true') {
        $debugMode = true;
    }

    if ($compileAssets) {
        $compileScssFlags['compile_scss_assets'] = true;
    }

    return [
        'debug_mode' => $debugMode,
        'compile_assets' => $compileAssets,
        'html_compile' => $htmlCompile,
        'html_compile_folder' => $htmlCompileFolder,
        'structure_vars' => $structureVars,
        'seo_vars' => $seoVars,
        'global_paths' => $globalPaths,
        'compile_scss_flags' => $compileScssFlags,
    ];
}