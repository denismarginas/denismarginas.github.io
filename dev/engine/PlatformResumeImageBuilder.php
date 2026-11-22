<?php

class PlatformResumeImageBuilder
{
    private const COLLECTION_FILE = 'collection/data_collection_resume.json';

    public static function settings(): array
    {
        $s = PlatformWorkflowService::section('resumes');
        $folder = trim(str_replace(['\\', '..'], ['/', ''], (string) ($s['output_folder'] ?? 'resumes')), '/');
        return [
            'output_folder'     => $folder !== '' ? $folder : 'resumes',
            'width'             => max(100, min(4000, (int) ($s['width'] ?? 1000))),
            'thumbnail_width'   => max(20, min(1000, (int) ($s['thumbnail_width'] ?? 130))),
            'quality'           => max(1, min(100, (int) ($s['quality'] ?? 85))),
            'thumbnail_quality' => max(1, min(100, (int) ($s['thumbnail_quality'] ?? 80))),
            'page'              => max(1, (int) ($s['page'] ?? 1)),
            'skip_existing'     => self::truthy($s['skip_existing'] ?? true),
            'update_json'       => self::truthy($s['update_json'] ?? true),
        ];
    }

    public static function resumes(): array
    {
        $settings = self::settings();
        $out = [];
        foreach (self::read_items() as $item) {
            if (!is_array($item)) continue;
            $id = self::safe_id((string) ($item['_id'] ?? ''));
            if ($id === '') continue;

            $d = is_array($item['data'] ?? null) ? $item['data'] : [];
            $media = is_array($d['media'] ?? null) ? $d['media'] : [];
            $pdf = ltrim(str_replace('\\', '/', (string) ($media['pdf'] ?? '')), '/');
            $targets = self::targets($id, $settings);

            $out[] = [
                '_id'              => $id,
                'title'            => (string) ($d['seo']['title'] ?? ''),
                'description'      => (string) ($d['seo']['description'] ?? ''),
                'lang'             => (string) ($d['lang'] ?? $item['settings']['lang'] ?? ''),
                'model'            => $d['model'] ?? '',
                'pdf'              => $pdf,
                'has_pdf'          => $pdf !== '' && !str_contains($pdf, '..') && is_file(self::root() . '/' . $pdf),
                'image'            => $targets['image'],
                'thumbnail'        => $targets['thumbnail'],
                'has_image'        => is_file(self::root() . '/' . $targets['image']),
                'has_thumbnail'    => is_file(self::root() . '/' . $targets['thumbnail']),
                'json_up_to_date'  => (string) ($media['image'] ?? '') === $targets['image']
                    && (string) ($media['thumbnail'] ?? '') === $targets['thumbnail'],
            ];
        }
        return $out;
    }

    public static function save(string $id, string $image, string $thumbnail, ?bool $overwrite = null): array
    {
        $settings = self::settings();
        $id = self::safe_id($id);
        $result = ['_id' => $id, 'success' => false];

        $resume = null;
        foreach (self::resumes() as $r) {
            if ($r['_id'] === $id) $resume = $r;
        }
        if ($resume === null) return $result + ['message' => 'resume item not found'];

        $result['image'] = $resume['image'];
        $result['thumbnail'] = $resume['thumbnail'];

        $overwrite ??= !$settings['skip_existing'];
        if (!$overwrite && $resume['has_image'] && $resume['has_thumbnail']) {
            $json = $settings['update_json'] ? self::update_json($id, $resume['image'], $resume['thumbnail']) : null;
            return array_merge($result, ['success' => true, 'skipped' => true, 'json_updated' => $json, 'message' => 'images already exist']);
        }

        $files = ['image' => $image, 'thumbnail' => $thumbnail];
        $bytes = [];
        foreach ($files as $key => $data) {
            $binary = self::decode_webp($data);
            if ($binary === null) return $result + ['message' => $key . ' is not a valid WebP image'];

            $file = self::root() . '/' . $resume[$key];
            if (!is_dir(dirname($file))) mkdir(dirname($file), 0777, true);
            if (file_put_contents($file, $binary, LOCK_EX) === false) {
                return $result + ['message' => 'could not write ' . $resume[$key]];
            }
            $bytes[$key] = strlen($binary);
        }

        $result['bytes'] = $bytes;
        $result['json_updated'] = $settings['update_json'] ? self::update_json($id, $resume['image'], $resume['thumbnail']) : null;
        $result['success'] = true;
        return $result;
    }

    private static function targets(string $id, array $settings): array
    {
        $dir = 'src/content/img/' . $settings['output_folder'] . '/' . $id . '/';
        return ['image' => $dir . $id . '.webp', 'thumbnail' => $dir . $id . '-thumbnail.webp'];
    }

    private static function update_json(string $id, string $image, string $thumbnail): bool
    {
        $file = self::collection_file();
        $raw = is_file($file) ? (string) file_get_contents($file) : '';
        if (strncmp($raw, "\xEF\xBB\xBF", 3) === 0) $raw = substr($raw, 3);
        $items = json_decode($raw, true);
        if (!is_array($items)) return false;

        $changed = false;
        foreach ($items as &$item) {
            if (!is_array($item) || self::safe_id((string) ($item['_id'] ?? '')) !== $id) continue;
            $media = is_array($item['data']['media'] ?? null) ? $item['data']['media'] : [];
            if (($media['image'] ?? null) === $image && ($media['thumbnail'] ?? null) === $thumbnail) return true;
            $media['image'] = $image;
            $media['thumbnail'] = $thumbnail;
            $item['data']['media'] = $media;
            $changed = true;
        }
        unset($item);
        if (!$changed) return false;

        $json = json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($json === false) return false;

        if (preg_match('/^\[?\s*\n( +)\S/', $raw, $m) && strlen($m[1]) !== 4) {
            $size = strlen($m[1]);
            $json = preg_replace_callback('/^( +)/m', fn ($s) => str_repeat(' ', intdiv(strlen($s[1]), 4) * $size), $json);
        }
        if (str_contains($raw, "\r\n")) $json = str_replace("\n", "\r\n", $json);
        if (preg_match('/\R$/', $raw)) $json .= str_contains($raw, "\r\n") ? "\r\n" : "\n";

        return file_put_contents($file, $json, LOCK_EX) !== false;
    }

    private static function decode_webp(string $data): ?string
    {
        $data = preg_replace('#^data:image/webp;base64,#', '', trim($data));
        $binary = base64_decode((string) $data, true);
        if ($binary === false || strlen($binary) < 16) return null;
        if (substr($binary, 0, 4) !== 'RIFF' || substr($binary, 8, 4) !== 'WEBP') return null;
        return $binary;
    }

    private static function read_items(): array
    {
        $file = self::collection_file();
        if (!is_file($file)) return [];
        $raw = (string) file_get_contents($file);
        if (strncmp($raw, "\xEF\xBB\xBF", 3) === 0) $raw = substr($raw, 3);
        $items = json_decode($raw, true);
        return is_array($items) ? $items : [];
    }

    public static function collection_file(): string
    {
        $dataDir = PlatformConfig::get('data_dir');
        $langs = json_decode((string) @file_get_contents($dataDir . '/settings/data_settings_languages.json'), true);
        $lang = is_array($langs) && is_string($langs['default'] ?? null) ? $langs['default'] : 'en';
        return $dataDir . '/' . $lang . '/' . self::COLLECTION_FILE;
    }

    private static function root(): string
    {
        return rtrim(str_replace('\\', '/', PlatformConfig::get('root_dir')), '/');
    }

    private static function safe_id(string $id): string
    {
        return preg_replace('/[^a-zA-Z0-9_-]/', '', trim($id)) ?? '';
    }

    private static function truthy(mixed $v): bool
    {
        if (is_bool($v)) return $v;
        return in_array(strtolower(trim((string) $v)), ['1', 'true', 'yes', 'on'], true);
    }
}
