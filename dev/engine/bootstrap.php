<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

define('ENGINE_ROOT', dirname(__DIR__));
define('ENGINE_DIR', __DIR__);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/PlatformAssetResolver.php';
require_once __DIR__ . '/PlatformDataService/PlatformDataServiceCore.php';
require_once __DIR__ . '/PlatformDataService/PlatformDataServicePosts.php';
require_once __DIR__ . '/PlatformDataService/PlatformDataServiceCollections.php';
require_once __DIR__ . '/PlatformDataService/PlatformDataServiceComponents.php';
require_once __DIR__ . '/PlatformDataService/PlatformDataService.php';
require_once __DIR__ . '/PlatformUrlService.php';
require_once __DIR__ . '/PlatformImageRenderer.php';
require_once __DIR__ . '/PlatformComponentRenderer/PlatformComponentRendererLoader.php';
require_once __DIR__ . '/PlatformComponentRenderer/PlatformComponentRendererConfig.php';
require_once __DIR__ . '/PlatformComponentRenderer/PlatformComponentRendererAssets.php';
require_once __DIR__ . '/PlatformComponentRenderer/PlatformComponentRendererClass.php';
require_once __DIR__ . '/PlatformComponentRenderer/PlatformComponentRenderer.php';
require_once __DIR__ . '/PlatformPathService.php';
require_once __DIR__ . '/PlatformTemplateRenderer.php';
require_once __DIR__ . '/PlatformTextService.php';
require_once __DIR__ . '/PlatformScssService.php';
require_once __DIR__ . '/PlatformScssBuilder.php';
require_once __DIR__ . '/PlatformBundleBuilder.php';
require_once __DIR__ . '/PlatformWorkflowService.php';
require_once __DIR__ . '/PlatformHeadHoist.php';
require_once __DIR__ . '/PlatformTranslationService.php';
require_once __DIR__ . '/PlatformTranslationBuilder.php';
require_once __DIR__ . '/PlatformThumbnailBuilder.php';
require_once __DIR__ . '/PlatformResumeImageBuilder.php';
require_once __DIR__ . '/PlatformSearchIndexBuilder.php';
require_once __DIR__ . '/PlatformSeoFilesBuilder.php';
require_once __DIR__ . '/PlatformI18nFrontendBuilder.php';

PlatformConfig::init();
PlatformDataService::init();

if (isset($_GET['lang']) && is_string($_GET['lang'])) {
    PlatformDataService::set_language(trim($_GET['lang']));
}

$GLOBALS['url_path'] = '';

$global_settings = PlatformDataService::get_global_settings();
if ($global_settings && !empty($global_settings['url'])) {
    $parsed = parse_url($global_settings['url']);
    $path = $parsed['path'] ?? '';
    $GLOBALS['url_path'] = rtrim($path, '/') . '/';
}

$GLOBALS['urlPath'] = $GLOBALS['url_path'];
