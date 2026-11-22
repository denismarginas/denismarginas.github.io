<?php

class PlatformImage
{
    public static function render(array $data = []): string
    {
        $src = $data['src'] ?? '';
        $path = $data['path'] ?? '';
        $value = $data['value'] ?? '';
        $class = $data['class'] ?? 'platform-img-thumb';
        $type = $data['type'] ?? 'img';

        if ($src === '' && $path !== '' && $value !== '') {
            $imgSubdir = $data['global_img_path'] ?? '';
            $vidSubdir = $data['global_vid_path'] ?? '';

            if ($type === 'vid' && $vidSubdir !== '') {
                $leaf = basename($vidSubdir);
                $baseDir = ENGINE_PROJECT_ROOT . '/src/content/vid/' . $leaf . '/';
                $apiPrefix = 'api/serve-file.php?type=vid&path=';
            } elseif ($type !== 'vid' && $imgSubdir !== '') {
                $leaf = basename($imgSubdir);
                $baseDir = ENGINE_PROJECT_ROOT . '/src/content/img/' . $leaf . '/';
                $apiPrefix = 'api/serve-file.php?type=img&path=';
            } else {
                $baseDir = $type === 'vid'
                    ? (ENGINE_PROJECT_ROOT . '/src/content/vid/')
                    : (ENGINE_PROJECT_ROOT . '/src/content/img/');
                $apiPrefix = 'api/serve-file.php?type=' . $type . '&path=';
            }

            $fullPath = $baseDir . $path . '/' . $value;
            if (file_exists($fullPath)) {
                $src = $apiPrefix . urlencode($path . '/' . $value);
            } else {
                return '<span class="platform-img-not-found" style="color:var(--platform-danger);font-size:11px">IMG Not Found</span>';
            }
        }

        if ($src === '') {
            return '<span class="platform-img-not-found" style="color:var(--platform-danger);font-size:11px">IMG Not Found</span>';
        }

        $cls = htmlspecialchars($class, ENT_QUOTES, 'UTF-8');
        return '<img src="' . htmlspecialchars($src, ENT_QUOTES, 'UTF-8') . '" class="' . $cls . '" loading="lazy">';
    }
}
