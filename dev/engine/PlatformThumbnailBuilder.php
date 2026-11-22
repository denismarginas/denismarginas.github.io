<?php

class PlatformThumbnailBuilder
{
    public const VARIANTS = ['default'];
    private const SIZE = 400;
    private const LAYERS = ['xl', 'md', 'sm'];
    private const ANCHORS = ['xl' => [0.0, 0.0], 'md' => [0.5, 0.0], 'sm' => [0.5, 0.0]];
    private const IMG_EXT = ['webp', 'png', 'jpg', 'jpeg'];

    private static ?array $template = null;

    public static function settings(): array
    {
        $s = PlatformWorkflowService::section('thumbnails');
        return [
            'variants'      => self::VARIANTS,
            'skip_existing' => self::truthy($s['skip_projects_with_thumbnail'] ?? true),
            'overwrite'     => self::truthy($s['overwrite'] ?? false),
            'overwrite_if_bigger'   => self::truthy($s['overwrite_if_bigger'] ?? false),
            'overwrite_bigger_than' => max(0.0, (float) str_replace(',', '.', (string) ($s['overwrite_bigger_than_kb'] ?? 20))),
            'quality'       => max(1, min(100, (int) ($s['quality'] ?? 42))),
        ];
    }

    public static function requirements(): array
    {
        $errors = [];
        if (!extension_loaded('gd')) {
            $errors[] = 'PHP GD extension is not enabled. Run dev\\platform\\require\\enable-php-gd.bat (start-platform.bat does it automatically) and restart the platform.';
        } elseif (!function_exists('imagecreatefromwebp') || !function_exists('imagewebp')) {
            $errors[] = 'PHP GD is enabled but was built without WebP support.';
        }
        foreach (array_merge(self::LAYERS, ['overlay']) as $name) {
            if (!is_file(self::template_file($name))) $errors[] = 'Missing template file: ' . self::template_file($name);
        }
        return $errors;
    }

    public static function projects(): array
    {
        $posts = PlatformDataService::get_data('post_project') ?? [];
        $out = [];
        foreach ($posts as $post) {
            if (empty($post['settings']['render'])) continue;
            $path = trim((string) ($post['data']['media']['path'] ?? ''), '/');
            if ($path === '' || str_contains($path, '..')) continue;

            $dir = self::project_dir($path);
            $existing = array_map('basename', self::images($dir . '/thumbnail'));
            $generated = [];
            foreach (self::VARIANTS as $v) {
                $f = self::output_file($path, $v);
                $generated[$v] = is_file($f) ? round(filesize($f) / 1024, 1) : false;
            }
            $out[] = [
                '_id'           => (string) ($post['_id'] ?? $path),
                'title'         => (string) ($post['data']['seo']['title'] ?? $post['data']['title'] ?? $path),
                'path'          => $path,
                'has_thumbnail' => $existing !== [],
                'thumbnails'    => $existing,
                'generated'     => $generated,
                'has_web'       => self::sources($dir, 'web') !== [],
                'has_media'     => self::sources($dir, 'media') !== [],
            ];
        }
        usort($out, static fn($a, $b) => strcmp($a['path'], $b['path']));
        return $out;
    }

    public static function generate(string $path, string $variant = 'default', bool $preview = false, ?bool $overwrite = null): array
    {
        $path = trim($path, '/');
        $settings = self::settings();
        $overwrite ??= $settings['overwrite'];
        $result = ['path' => $path, 'variant' => $variant, 'success' => false];

        if ($errors = self::requirements()) return $result + ['message' => implode(' ', $errors)];
        if (!in_array($variant, self::VARIANTS, true)) return $result + ['message' => 'unknown variant ' . $variant];
        if ($path === '' || str_contains($path, '..') || !is_dir(self::project_dir($path))) {
            return $result + ['message' => 'project folder not found'];
        }

        $file = self::output_file($path, $variant);
        $result['file'] = self::relative($file);
        if (!$preview && !$overwrite && is_file($file)) {
            $kb = filesize($file) / 1024;
            $limit = $settings['overwrite_bigger_than'];
            if (!$settings['overwrite_if_bigger'] || $kb <= $limit) {
                $why = $settings['overwrite_if_bigger'] ? sprintf(' (%.1f KB, not over %s KB)', $kb, $limit) : '';
                return $result + ['skipped' => true, 'message' => 'already exists' . $why];
            }
            $result['replaced_kb'] = round($kb, 1);
        }

        $sources = self::sources(self::project_dir($path), $variant);
        $result['sources'] = array_map(fn($f) => self::relative($f), $sources);
        if ($sources === []) {
            return $result + ['skipped' => true, 'message' => 'no ' . ($variant === 'default' ? 'web or media' : $variant) . ' images'];
        }

        $img = self::compose($sources);
        if ($img === null) return $result + ['message' => 'could not read the source images'];

        if ($preview) {
            ob_start();
            imagewebp($img, null, $settings['quality']);
            $result['data_uri'] = 'data:image/webp;base64,' . base64_encode((string) ob_get_clean());
        } else {
            if (!is_dir(dirname($file))) mkdir(dirname($file), 0777, true);
            if (!imagewebp($img, $file, $settings['quality'])) {
                return $result + ['message' => 'could not write ' . $result['file']];
            }
            $result['bytes'] = filesize($file);
        }
        $result['success'] = true;
        return $result;
    }

    public static function sources(string $projectDir, string $variant): array
    {
        $desktop = self::images($projectDir . '/web/website/desktop');
        $phone   = self::images($projectDir . '/web/website/phone');

        if ($desktop && self::is_cover($desktop[0]) && count($desktop) > 2) {
            $desktop = array_merge([$desktop[0]], array_slice($desktop, 2), [$desktop[1]]);
        }
        $web = $desktop;

        if ($variant === 'default') $variant = $web ? 'web' : 'media';

        if ($variant === 'web') {
            if (!$web) return [];
            return array_filter([
                'md' => $web[0],
                'xl' => $web[1] ?? $web[0],
                'sm' => $phone[0] ?? ($web[2] ?? null),
            ]);
        }

        $media = self::images_recursive($projectDir . '/media');
        if (!$media) return [];
        $n = count($media);
        return [
            'md' => $media[0],
            'sm' => $media[intdiv($n, 2)],
            'xl' => $media[min($n - 1, intdiv(2 * $n, 3) + 1)],
        ];
    }

    private static function compose(array $sources): ?GdImage
    {
        $tpl = self::template();
        $size = self::SIZE;

        $out = self::canvas($size, $size);
        foreach (self::LAYERS as $key) {
            if (empty($sources[$key])) continue;
            $layer = self::layer($sources[$key], $tpl['masks'][$key], $tpl['geometry'][$key], self::ANCHORS[$key]);
            if ($layer === null) continue;
            imagealphablending($out, true);
            imagecopy($out, $layer, 0, 0, 0, 0, $size, $size);
        }
        imagealphablending($out, true);
        imagecopy($out, $tpl['overlay'], 0, 0, 0, 0, $size, $size);
        imagesavealpha($out, true);
        return $out;
    }

    private static function layer(string $source, array $mask, array $geo, array $anchor): ?GdImage
    {
        $src = self::load($source);
        if ($src === null) return null;

        [$x0, $y0, $w, $h] = $geo['rect'];
        $tile = self::cover($src, $w, $h, $anchor[0], $anchor[1]);

        $size = self::SIZE;
        $c = $size / 2;
        $cos = $geo['cos'];
        $sin = $geo['sin'];
        $layer = self::canvas($size, $size);
        imagealphablending($layer, false);

        foreach ($mask as $y => $row) {
            foreach ($row as $x => $a) {
                $dx = $x + 0.5 - $c;
                $dy = $y + 0.5 - $c;
                $u = $cos * $dx + $sin * $dy + $c - $x0 - 0.5;
                $v = -$sin * $dx + $cos * $dy + $c - $y0 - 0.5;
                [$r, $g, $b, $ta] = self::sample($tile, $u, $v, $w, $h);
                $alpha = $a / 255 * $ta;
                if ($alpha <= 0) continue;
                imagesetpixel($layer, $x, $y, imagecolorallocatealpha($layer, $r, $g, $b, 127 - (int) round($alpha * 127)));
            }
        }
        return $layer;
    }

    private static function sample(GdImage $img, float $u, float $v, int $w, int $h): array
    {
        $u = max(0.0, min($w - 1.0, $u));
        $v = max(0.0, min($h - 1.0, $v));
        $x0 = (int) $u; $y0 = (int) $v;
        $x1 = min($x0 + 1, $w - 1); $y1 = min($y0 + 1, $h - 1);
        $fx = $u - $x0; $fy = $v - $y0;

        $p = [imagecolorat($img, $x0, $y0), imagecolorat($img, $x1, $y0), imagecolorat($img, $x0, $y1), imagecolorat($img, $x1, $y1)];
        $wt = [(1 - $fx) * (1 - $fy), $fx * (1 - $fy), (1 - $fx) * $fy, $fx * $fy];
        $r = $g = $b = $o = 0.0;
        foreach ($p as $i => $col) {
            $op = (127 - (($col >> 24) & 0x7F)) / 127 * $wt[$i];
            $r += (($col >> 16) & 0xFF) * $op;
            $g += (($col >> 8) & 0xFF) * $op;
            $b += ($col & 0xFF) * $op;
            $o += $op;
        }
        if ($o <= 0) return [0, 0, 0, 0.0];
        return [(int) round($r / $o), (int) round($g / $o), (int) round($b / $o), $o];
    }

    private static function cover(GdImage $src, int $w, int $h, float $ax, float $ay): GdImage
    {
        $sw = imagesx($src); $sh = imagesy($src);
        $scale = max($w / $sw, $h / $sh);
        $cw = $w / $scale; $ch = $h / $scale;
        $cx = ($sw - $cw) * $ax; $cy = ($sh - $ch) * $ay;
        $tile = self::canvas($w, $h);
        imagealphablending($tile, false);
        imagecopyresampled($tile, $src, 0, 0, (int) round($cx), (int) round($cy), $w, $h, (int) round($cw), (int) round($ch));
        return $tile;
    }

    private static function template(): array
    {
        if (self::$template !== null) return self::$template;

        $masks = []; $best = null;
        foreach (self::LAYERS as $key) {
            $img = self::load(self::template_file($key));
            $masks[$key] = self::mask_pixels($img);
            $fit = self::tilt($masks[$key]);
            if ($best === null || $fit['samples'] > $best['samples']) $best = $fit;
        }
        $geometry = [];
        foreach (self::LAYERS as $key) {
            $geometry[$key] = self::geometry($masks[$key], $best['angle']);
        }
        $overlay = self::load(self::template_file('overlay'));
        imagealphablending($overlay, true);

        return self::$template = ['masks' => $masks, 'geometry' => $geometry, 'overlay' => $overlay];
    }

    private static function mask_pixels(GdImage $img): array
    {
        $rows = [];
        $w = imagesx($img); $h = imagesy($img);
        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $a = 127 - ((imagecolorat($img, $x, $y) >> 24) & 0x7F);
                if ($a > 0) $rows[$y][$x] = (int) round($a * 255 / 127);
            }
        }
        return $rows;
    }

    private static function tilt(array $mask): array
    {
        $top = [];
        foreach ($mask as $y => $row) {
            foreach ($row as $x => $a) {
                if ($a > 127 && (!isset($top[$x]) || $y < $top[$x])) $top[$x] = $y;
            }
        }
        ksort($top);
        $xs = array_keys($top);
        $minX = $xs[0]; $maxX = end($xs);
        $span = $maxX - $minX;
        $n = 0; $sx = $sy = $sxx = $sxy = 0.0;
        foreach ($top as $x => $y) {
            if ($x < $minX + $span * 0.2 || $x > $maxX - $span * 0.2) continue;
            $n++; $sx += $x; $sy += $y; $sxx += $x * $x; $sxy += $x * $y;
        }
        $slope = $n > 1 ? ($n * $sxy - $sx * $sy) / ($n * $sxx - $sx * $sx) : 0.0;
        return ['angle' => atan($slope), 'samples' => $n];
    }

    private static function geometry(array $mask, float $angle): array
    {
        $cos = cos($angle); $sin = sin($angle);

        $c = self::SIZE / 2;
        $u0 = $v0 = INF; $u1 = $v1 = -INF;
        foreach ($mask as $y => $row) {
            foreach ($row as $x => $a) {
                if ($a <= 127) continue;
                $dx = $x + 0.5 - $c; $dy = $y + 0.5 - $c;
                $u = $cos * $dx + $sin * $dy + $c;
                $v = -$sin * $dx + $cos * $dy + $c;
                $u0 = min($u0, $u); $u1 = max($u1, $u);
                $v0 = min($v0, $v); $v1 = max($v1, $v);
            }
        }
        $x0 = (int) floor($u0); $y0 = (int) floor($v0);
        return [
            'angle' => rad2deg($angle),
            'cos'   => $cos,
            'sin'   => $sin,
            'rect'  => [$x0, $y0, (int) ceil($u1) - $x0, (int) ceil($v1) - $y0],
        ];
    }

    private static function template_file(string $name): string
    {
        $dir = PlatformConfig::get('dev_dir') . '/platform/assets/img/project_thumbnail/';
        return $name === 'overlay' ? $dir . 'thumbnail_project_overlay.webp' : $dir . 'thumbnail_screen_1_' . $name . '.webp';
    }

    private static function project_dir(string $path): string
    {
        return PlatformConfig::get('img_dir') . '/projects/' . $path;
    }

    public static function output_file(string $path, string $variant = 'default'): string
    {
        return self::project_dir($path) . '/thumbnail/thumbnail.webp';
    }

    private static function relative(string $file): string
    {
        $root = rtrim(str_replace('\\', '/', PlatformConfig::get('img_dir')), '/') . '/';
        $file = str_replace('\\', '/', $file);
        return str_starts_with($file, $root) ? substr($file, strlen($root)) : $file;
    }

    private static function images(string $dir, string $contains = ''): array
    {
        if (!is_dir($dir)) return [];
        $files = [];
        foreach (scandir($dir) ?: [] as $name) {
            if (!in_array(strtolower(pathinfo($name, PATHINFO_EXTENSION)), self::IMG_EXT, true)) continue;
            if ($contains !== '' && stripos($name, $contains) === false) continue;
            if (is_file($dir . '/' . $name)) $files[] = $dir . '/' . $name;
        }
        usort($files, static function (string $a, string $b): int {
            $ca = self::is_cover($a);
            $cb = self::is_cover($b);
            if ($ca !== $cb) return $ca ? -1 : 1;
            return strnatcasecmp(basename($a), basename($b));
        });
        return $files;
    }

    private static function is_cover(string $file): bool
    {
        return (bool) preg_match('/(^|[_\-])0+$/', pathinfo(basename($file), PATHINFO_FILENAME));
    }

    private static function images_recursive(string $dir): array
    {
        if (!is_dir($dir)) return [];
        $files = [];
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
        foreach ($it as $f) {
            if ($f->isFile() && in_array(strtolower($f->getExtension()), self::IMG_EXT, true)) {
                $files[] = str_replace('\\', '/', $f->getPathname());
            }
        }
        sort($files, SORT_NATURAL | SORT_FLAG_CASE);
        return $files;
    }

    private static function load(string $file): ?GdImage
    {
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $img = match ($ext) {
            'webp' => @imagecreatefromwebp($file),
            'png' => @imagecreatefrompng($file),
            'jpg', 'jpeg' => @imagecreatefromjpeg($file),
            default => false,
        };
        if (!$img) return null;
        if (!imageistruecolor($img)) imagepalettetotruecolor($img);
        imagesavealpha($img, true);
        return $img;
    }

    private static function canvas(int $w, int $h): GdImage
    {
        $img = imagecreatetruecolor($w, $h);
        imagealphablending($img, false);
        imagefill($img, 0, 0, imagecolorallocatealpha($img, 0, 0, 0, 127));
        imagesavealpha($img, true);
        return $img;
    }

    private static function truthy(mixed $v): bool
    {
        return is_bool($v) ? $v : in_array(strtolower((string) $v), ['1', 'true', 'yes', 'on'], true);
    }
}
