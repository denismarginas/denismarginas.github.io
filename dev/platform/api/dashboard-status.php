<?php

header('Content-Type: application/json; charset=UTF-8');

if (!defined('ENGINE_PROJECT_ROOT')) {
    define('ENGINE_PROJECT_ROOT', dirname(__DIR__, 3));
}

ini_set('display_errors', '0');
require_once ENGINE_PROJECT_ROOT . '/dev/engine/bootstrap.php';
ini_set('display_errors', '0');
require_once __DIR__ . '/posts_helpers.php';

function platform_dashboard_send(array $data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
}

function platform_dashboard_data_name(string $file): string
{
    return (string) preg_replace(['/^data_/', '/\.json$/'], '', basename($file));
}

function platform_dashboard_data_mtime(string $lang, string $file): int
{
    static $cache = [];
    $key = $lang . '|' . $file;
    if (isset($cache[$key])) return $cache[$key];
    $base = PlatformConfig::get('data_dir');
    $name = basename($file);
    if (!str_ends_with($name, '.json')) $name .= '.json';
    foreach ([$lang, PlatformDataService::default_language()] as $dirLang) {
        $dir = $base . '/' . $dirLang;
        if (!is_dir($dir)) continue;
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $item) {
            if ($item->isFile() && $item->getFilename() === $name) return $cache[$key] = (int) $item->getMTime();
        }
    }
    return $cache[$key] = 0;
}

function platform_dashboard_jobs(string $lang, bool $useFolders, string $extension): array
{
    $prefix = PlatformPathService::lang_prefix($lang);
    $jobs = [];
    $add = static function (array $entry, string $kind, string $type, string $root, string $file) use (&$jobs, $prefix, $useFolders, $extension, $lang): void {
        $id = (string) ($entry['_id'] ?? '');
        if ($id === '') return;
        $folder = trim($root, '/');
        $rel = $prefix . ($useFolders && $folder !== '' && $type !== 'page' ? $folder . '/' : '') . $id . $extension;
        $jobs[$rel] = [
            'rel'        => $rel,
            '_id'        => $id,
            'kind'       => $kind,
            'type'       => $type,
            'title'      => platform_content_title($entry),
            'edit_url'   => platform_content_edit_url($kind, $type, $id),
            'data_mtime' => platform_dashboard_data_mtime($lang, $file),
        ];
    };

    foreach (platform_get_post_types() as $t) {
        foreach ((PlatformDataService::get_data(platform_dashboard_data_name($t['file'])) ?? []) as $entry) {
            if (is_array($entry)) $add($entry, 'post', (string) $t['post_type'], (string) $t['root'], (string) $t['file']);
        }
    }
    foreach (platform_get_routable_collection_types() as $t) {
        foreach ((PlatformDataService::get_data(platform_dashboard_data_name($t['file'])) ?? []) as $entry) {
            if (!is_array($entry) || (($entry['settings']['render'] ?? true) === false)) continue;
            $add($entry, 'collection', (string) $t['post_type'], (string) $t['root'], (string) $t['file']);
        }
    }
    foreach (platform_get_taxonomy_types() as $t) {
        foreach ((PlatformDataService::get_data(platform_dashboard_data_name($t['file'])) ?? []) as $entry) {
            if (is_array($entry)) $add($entry, 'taxonomy', (string) $t['taxonomy'], (string) $t['root'], (string) $t['file']);
        }
    }

    return array_values($jobs);
}

function platform_dashboard_html(): array
{
    $workflow = PlatformWorkflowService::section('render');
    $useFolders = filter_var($workflow['html_compile_folder'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $routing = PlatformDataService::get_data('settings_routing') ?? [];
    $extension = (string) ($routing['routing']['extension'] ?? '.html');
    $dist = ENGINE_PROJECT_ROOT . '/dist';
    $default = PlatformDataService::default_language();
    $frontend = PlatformPathService::frontend_languages();

    $languages = [];
    $expectedDirs = [];
    $expected = [];
    $lastBuild = 0;
    $short = static fn (array $job): array => ['_id' => $job['_id'], 'title' => $job['title'], 'type' => $job['type'], 'file' => $job['rel'], 'edit_url' => $job['edit_url']];

    foreach (PlatformPathService::languages() as $iso => $name) {
        PlatformDataService::set_language($iso);
        $jobs = platform_dashboard_jobs($iso, $useFolders, $extension);
        $isFrontend = in_array($iso, $frontend, true);

        if ($isFrontend) {
            $dataDir = PlatformConfig::get('data_dir');
            $files = PlatformTranslationBuilder::files($default);
            $missingFiles = [];
            foreach ($files as $file) {
                if (!is_file($dataDir . '/' . $iso . '/' . $file)) $missingFiles[] = ['_id' => $file, 'title' => basename($file), 'type' => 'data', 'file' => '', 'edit_url' => '?page=generate&tab=translate'];
            }
            $languages[] = [
                'iso'            => $iso,
                'name'           => $name,
                'mode'           => 'frontend',
                'source'         => 'src/content/json/data/' . $iso . '/',
                'expected'       => count($files),
                'built'          => count($files) - count($missingFiles),
                'missing'        => [],
                'missing_files'  => $missingFiles,
                'outdated'       => [],
                'outdated_count' => 0,
                'stale'          => false,
                'last_build'     => null,
            ];
            continue;
        }

        $missing = [];
        $outdated = [];
        $built = 0;
        foreach ($jobs as $job) {
            $expected[$job['rel']] = true;
            $expectedDirs[dirname($job['rel'])] = true;
            $path = $dist . '/' . $job['rel'];
            if (!is_file($path)) { $missing[] = $short($job); continue; }
            $built++;
            $mtime = (int) filemtime($path);
            $lastBuild = max($lastBuild, $mtime);
            if ($job['data_mtime'] > $mtime) $outdated[] = $short($job);
        }
        $languages[] = [
            'iso'            => $iso,
            'name'           => $name,
            'mode'           => 'static',
            'expected'       => count($jobs),
            'built'          => $built,
            'missing'        => $missing,
            'outdated'       => $outdated,
            'outdated_count' => count($outdated),
            'stale'          => $outdated !== [],
            'last_build'     => null,
        ];
    }
    PlatformDataService::set_language($default);

    $orphans = [];
    foreach (array_keys($expectedDirs) as $dir) {
        $abs = $dist . ($dir === '.' ? '' : '/' . $dir);
        foreach (is_dir($abs) ? (scandir($abs) ?: []) : [] as $name) {
            if (!str_ends_with($name, $extension) || !is_file($abs . '/' . $name)) continue;
            $rel = ($dir === '.' ? '' : $dir . '/') . $name;
            if (!isset($expected[$rel])) $orphans[] = $rel;
        }
    }
    sort($orphans);

    return [
        'html_compile' => filter_var($workflow['html_compile'] ?? false, FILTER_VALIDATE_BOOLEAN),
        'languages'    => $languages,
        'orphans'      => $orphans,
        'last_build'   => $lastBuild ?: null,
        'sitemap'      => is_file($dist . '/sitemap.xml') ? (int) filemtime($dist . '/sitemap.xml') : null,
    ];
}

function platform_dashboard_projects(): array
{
    $types = platform_get_content_types() ?? [];
    $config = $types['post']['project'] ?? null;
    if (!is_array($config)) return ['available' => false];

    PlatformDataService::set_language(PlatformDataService::default_language());
    $entries = platform_read_data_file((string) $config['file']) ?? [];
    $imgBase = ENGINE_PROJECT_ROOT . '/' . trim((string) ($config['global_img_path'] ?? 'src/content/img/projects'), '/');
    $routing = PlatformDataService::get_data('settings_routing') ?? [];
    $svgBase = ENGINE_PROJECT_ROOT . '/' . trim((string) ($routing['theme']['svg_root'] ?? 'src/content/svg/'), '/');

    $total = 0;
    $hidden = [];
    $noThumb = [];
    $noLogo = [];
    foreach ($entries as $entry) {
        if (!is_array($entry)) continue;
        $id = (string) ($entry['_id'] ?? '');
        if ($id === '') continue;
        $total++;
        $data = is_array($entry['data'] ?? null) ? $entry['data'] : [];
        $media = is_array($data['media'] ?? null) ? $data['media'] : [];
        $settings = is_array($entry['settings'] ?? null) ? $entry['settings'] : [];
        $row = ['_id' => $id, 'title' => platform_content_title($entry), 'edit_url' => platform_content_edit_url('post', 'project', $id)];
        $isHidden = ($settings['render'] ?? true) === false;
        if ($isHidden) $hidden[] = $row;

        $path = trim((string) ($media['path'] ?? ''), '/');
        $logo = trim((string) ($media['logo']['img'] ?? ''), '/');
        $logoSvg = trim((string) ($media['logo']['svg'] ?? ''), '/');
        if ($logo !== '') {
            if (!is_file($imgBase . '/' . $logo)) $noLogo[] = $row + ['reason' => 'file', 'value' => $logo, 'hidden' => $isHidden];
        } elseif ($logoSvg !== '') {
            $svgFile = str_ends_with($logoSvg, '.svg') ? $logoSvg : $logoSvg . '.svg';
            if (!is_file($svgBase . '/' . $svgFile) && !is_file($imgBase . '/' . $svgFile)) $noLogo[] = $row + ['reason' => 'svg', 'value' => $logoSvg, 'hidden' => $isHidden];
        } else {
            $noLogo[] = $row + ['reason' => 'unset', 'value' => '', 'hidden' => $isHidden];
        }

        $thumb = trim((string) ($media['thumbnail']['default'] ?? ''), '/');
        $hasThumb = $thumb !== '' && is_file($imgBase . '/' . $thumb);
        if (!$hasThumb && $path !== '') {
            $thumbDir = $imgBase . '/' . $path . '/thumbnail';
            foreach (is_dir($thumbDir) ? (scandir($thumbDir) ?: []) : [] as $name) {
                if (preg_match('/\.(webp|png|jpe?g|avif)$/i', $name)) { $hasThumb = true; break; }
            }
        }
        if (!$hasThumb) $noThumb[] = $row + ['hidden' => $isHidden];
    }

    return [
        'available'    => true,
        'total'        => $total,
        'rendered'     => $total - count($hidden),
        'hidden'       => $hidden,
        'no_thumbnail' => $noThumb,
        'no_logo'      => $noLogo,
    ];
}

function platform_dashboard_recent(int $limit = 15): array
{
    $dataDir = str_replace('\\', '/', (string) PlatformConfig::get('data_dir'));
    $default = PlatformDataService::default_language();
    $types = platform_get_content_types() ?? [];
    $byFile = [];
    foreach ($types as $kind => $list) {
        foreach ((array) $list as $type => $config) {
            if (!is_array($config) || empty($config['file'])) continue;
            $name = basename((string) $config['file']);
            if (!str_ends_with($name, '.json')) $name .= '.json';
            $labels = platform_content_type_labels((string) $kind, (string) $type);
            $byFile[$name] = [
                'label' => $labels['plural'] !== '' ? $labels['plural'] : (string) ($config['label'] ?? $type),
                'url'   => platform_content_list_url((string) $kind, (string) $type),
            ];
        }
    }

    $files = [];
    foreach ([$dataDir . '/settings' => 'settings/', $dataDir . '/' . $default => ''] as $dir => $prefix) {
        if (!is_dir($dir)) continue;
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $item) {
            if (!$item->isFile() || strtolower($item->getExtension()) !== 'json') continue;
            $name = $item->getFilename();
            if (str_contains($name, ' ')) continue;
            $relative = $prefix . str_replace('\\', '/', substr($item->getPathname(), strlen($dir) + 1));
            $known = $byFile[$name] ?? null;
            $label = $known['label'] ?? ucfirst(str_replace(['_', '-'], ' ', (string) preg_replace('~^data[_-](settings[_-]|content[_-])?|\.json$~i', '', $name)));
            $files[] = [
                'file'  => $relative,
                'title' => $label,
                'kind'  => $prefix !== '' ? 'settings' : ($known ? 'content' : 'data'),
                'mtime' => (int) $item->getMTime(),
                'url'   => $known['url'] ?? ('?page=settings&file=' . rawurlencode($relative)),
            ];
        }
    }
    usort($files, static fn ($a, $b) => $b['mtime'] <=> $a['mtime']);
    return ['files' => array_slice($files, 0, $limit), 'total' => count($files)];
}

$section = (string) ($_GET['section'] ?? '');
try {
    $out = ['ok' => true, 'checked_at' => time()];
    if ($section === '' || $section === 'translations') $out['translations'] = PlatformTranslationBuilder::progress();
    if ($section === '' || $section === 'html') $out['html'] = platform_dashboard_html();
    if ($section === '' || $section === 'projects') $out['projects'] = platform_dashboard_projects();
    if ($section === '' || $section === 'recent') $out['recent'] = platform_dashboard_recent();
    platform_dashboard_send($out);
} catch (Throwable $e) {
    platform_dashboard_send(['ok' => false, 'message' => $e->getMessage()], 500);
}
