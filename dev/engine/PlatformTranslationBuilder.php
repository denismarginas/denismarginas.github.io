<?php

class PlatformTranslationBuilder
{
    private const SEP = "\x1f";

    private const SKIP_KEYS = [
        '_id', 'id', 'slug', 'img', 'src', 'url', 'href', 'path', 'file', 'filename', 'component', 'variant', 'iso',
        'post_type', 'extension', 'index', 'keywords', 'icon', 'svg', 'menu_name', 'settings', 'class', 'style',
        'json-path', 'json-search-mode', 'effect', 'transition', 'date_format', 'banner', 'step', 'publish', 'date',
        'start', 'end', 'option-suffix', 'hash', 'target', 'color', 'colors', 'lang', 'mode', 'name', 'value',
        'taxonomy', 'category', 'types', 'tags', 'tag', 'companies', 'employer', 'contractors', 'filter_by',
    ];

    private const LABEL_KEYS = ['singular', 'plural'];

    private const BLOCK_SPLIT = '/(<br\s*\/?>|<\/?(?:p|ul|ol|li|h[1-6]|div|table|thead|tbody|tr|td|th|blockquote)\b[^>]*>|\r?\n)/i';
    private const TOKENS = '/<[^>]*>|\{\{.*?\}\}|\{[a-z0-9_]+\}/i';

    private const DEFAULT_GLOSSARY = [
        'Denis Marginas', 'WordPress', 'WooCommerce', 'PrestaShop', 'Elementor', 'Sony Vegas Pro', 'Adobe After Effects',
        'Photopea', 'Topaz Video Enhance', 'Topaz Gigapixel AI', 'eMAG', 'GitHub', 'GitLab', 'Figma', 'PhpStorm',
        'Visual Studio Code', 'Laragon', 'HeidiSQL', 'phpMyAdmin',
    ];

    private static array $skipKeys = [];
    private static ?array $rules = null;
    private static array $overrides = [];

    public static function rules(): array
    {
        if (self::$rules !== null) return self::$rules;
        $stored = self::read_json(self::rules_path(), true);
        if ($stored === null) {
            $section = PlatformWorkflowService::section('translation');
            $glossary = array_filter(array_map('trim', explode(',', (string) ($section['glossary'] ?? ''))));
            $extra = array_filter(array_map('trim', explode(',', (string) ($section['skip_keys'] ?? ''))));
            $translate = array_filter(array_map('trim', explode(',', (string) ($section['translate_keys'] ?? ''))));
            $stored = [
                'skip_keys' => array_values(array_diff(array_unique(array_merge(self::SKIP_KEYS, $extra)), $translate)),
                'glossary' => $glossary !== [] ? array_values($glossary) : self::DEFAULT_GLOSSARY,
                'skip_values' => [],
            ];
        }
        return self::$rules = self::normalize_rules($stored);
    }

    public static function save_rules(array $rules): array
    {
        self::$rules = self::normalize_rules($rules);
        self::write_json(self::rules_path(), self::$rules, true);
        return self::$rules;
    }

    public static function key_report(): array
    {
        $config = self::language_config();
        $rules = self::rules();
        self::$skipKeys = [];
        $keys = [];
        foreach (self::files($config['default']) as $rel) {
            $data = self::read_json(self::source_dir($config['default']) . '/' . $rel, true);
            if ($data !== null) self::scan_keys($data, $keys, $rel);
        }
        foreach ($rules['skip_keys'] as $key) {
            if (!isset($keys[$key])) $keys[$key] = ['key' => $key, 'texts' => 0, 'example' => '', 'files' => []];
        }
        $rows = [];
        foreach ($keys as $key => $row) {
            $row['translate'] = !in_array((string) $key, $rules['skip_keys'], true);
            $row['files'] = array_values(array_slice(array_keys($row['files']), 0, 6));
            $rows[] = $row;
        }
        usort($rows, fn($a, $b) => [$b['texts'] > 0, $b['texts']] <=> [$a['texts'] > 0, $a['texts']] ?: strcmp((string) $a['key'], (string) $b['key']));
        return $rows;
    }

    private static function scan_keys(array $data, array &$keys, string $rel, ?string $parentKey = null): void
    {
        $isList = array_is_list($data);
        foreach ($data as $key => $value) {
            $name = $isList ? $parentKey : (string) $key;
            if (is_array($value)) {
                self::scan_keys($value, $keys, $rel, $name);
            } elseif ($name !== null && is_string($value) && self::is_translatable($value)) {
                if (!isset($keys[$name])) $keys[$name] = ['key' => $name, 'texts' => 0, 'example' => '', 'files' => []];
                $keys[$name]['texts']++;
                $keys[$name]['files'][$rel] = true;
                if ($keys[$name]['example'] === '' || (mb_strlen($keys[$name]['example']) < 12 && mb_strlen($value) > mb_strlen($keys[$name]['example']))) {
                    $keys[$name]['example'] = mb_substr(trim(strip_tags($value)), 0, 140);
                }
            }
        }
    }

    private static function override(array $overrides, string $text): ?string
    {
        $text = trim($text);
        if ($text === '' || $overrides === []) return null;
        if (isset($overrides[$text])) return $overrides[$text];
        foreach ($overrides as $source => $translation) {
            if (strcasecmp((string) $source, $text) === 0) return $translation;
        }
        return null;
    }

    private static function normalize_rules(array $rules): array
    {
        $list = function ($value): array {
            if (is_string($value)) $value = preg_split('/[\r\n,]+/', $value) ?: [];
            if (!is_array($value)) return [];
            $out = [];
            foreach ($value as $item) {
                $item = trim((string) $item);
                if ($item !== '' && !in_array($item, $out, true)) $out[] = $item;
            }
            return $out;
        };
        return [
            'skip_keys' => $list($rules['skip_keys'] ?? []),
            'glossary' => $list($rules['glossary'] ?? []),
            'skip_values' => $list($rules['skip_values'] ?? []),
            'overrides' => self::normalize_overrides($rules['overrides'] ?? []),
        ];
    }

    private static function normalize_overrides(mixed $overrides): array
    {
        if (!is_array($overrides)) return [];
        $out = [];
        foreach ($overrides as $lang => $pairs) {
            $lang = strtolower(trim((string) $lang));
            if ($lang === '' || !preg_match('/^[a-z]{2,3}(-[a-z0-9]+)?$/', $lang)) continue;
            if (is_string($pairs)) {
                $lines = preg_split('/\r?\n/', $pairs) ?: [];
                $pairs = [];
                foreach ($lines as $line) {
                    $parts = explode('=>', $line, 2);
                    if (count($parts) === 2) $pairs[trim($parts[0])] = trim($parts[1]);
                }
            }
            if (!is_array($pairs)) continue;
            foreach ($pairs as $source => $translation) {
                $source = trim((string) $source);
                $translation = trim((string) $translation);
                if ($source !== '' && $translation !== '') $out[$lang][$source] = $translation;
            }
        }
        return $out;
    }

    public static function language_config(): array
    {
        $languages = PlatformDataService::get_data('settings_languages') ?? [];
        $list = $languages['list'] ?? [];
        $default = $languages['default'] ?? ($list[0]['iso'] ?? 'en');

        $translation = PlatformWorkflowService::section('translation');
        $defaultValue = trim((string) ($translation['default_lang'] ?? ''));
        if ($defaultValue !== '') $default = $defaultValue;

        $targets = array_values(array_filter(array_map('trim', explode(',', (string) ($translation['target_langs'] ?? '')))));
        if ($targets !== []) {
            $known = [];
            foreach ($list as $lang) $known[$lang['iso'] ?? ''] = $lang;
            $list = array_map(fn($iso) => $known[$iso] ?? ['iso' => $iso, 'text' => $iso], $targets);
        }
        $list = array_values(array_filter($list, fn($lang) => ($lang['iso'] ?? '') !== '' && ($lang['iso'] ?? '') !== $default));

        return ['default' => $default, 'list' => $list];
    }

    public static function status(): array
    {
        $config = self::language_config();
        $languages = [];
        foreach ($config['list'] as $lang) {
            $iso = (string) $lang['iso'];
            $cache = self::read_json(self::cache_path($iso));
            $languages[] = ['iso' => $iso, 'text' => (string) ($lang['text'] ?? $iso), 'cached' => count($cache['strings'] ?? [])];
        }

        return [
            'default' => $config['default'],
            'languages' => $languages,
            'files' => self::files($config['default']),
            'provider' => PlatformTranslationService::status(),
        ];
    }

    public static function progress(): array
    {
        $config = self::language_config();
        $from = $config['default'];
        self::$skipKeys = self::skip_keys();
        $rules = self::rules();
        $modes = PlatformPathService::language_modes();

        $sources = [];
        foreach (self::files($from) as $rel) {
            $source = self::read_json(self::source_dir($from) . '/' . $rel, true);
            if ($source === null) continue;
            $leaves = [];
            self::collect($source, $leaves, '', true);
            if ($leaves !== []) $sources[$rel] = $leaves;
        }

        $languages = [];
        foreach ($config['list'] as $lang) {
            $iso = (string) $lang['iso'];
            $cache = self::read_json(self::cache_path($iso));
            $strings = $cache['strings'] ?? [];
            $failedCache = $cache['failed'] ?? [];
            $manifest = self::read_json(self::manifest_path($iso));
            $overrides = $rules['overrides'][$iso] ?? [];
            self::$overrides = $overrides;

            $total = 0;
            $done = 0;
            $todo = 0;
            $failed = 0;
            $files = [];
            foreach ($sources as $rel => $leaves) {
                $target = self::read_json(PlatformConfig::get('data_dir') . '/' . $iso . '/' . $rel, true);
                $existing = [];
                if (is_array($target)) self::collect($target, $existing, '', false);
                $records = is_array($manifest[$rel] ?? null) ? $manifest[$rel] : [];
                $fileTodo = 0;
                $fileFailed = 0;

                foreach ($leaves as $path => $text) {
                    $total++;
                    if (self::override($overrides, $text) !== null) { $done++; continue; }
                    $current = $existing[$path] ?? null;
                    $record = $records[$path] ?? null;
                    $outdated = is_array($record) && is_string($current) && ($record[0] ?? '') !== sha1($text) && sha1($current) === ($record[1] ?? '');
                    if (is_string($current) && $current !== $text && !$outdated) { $done++; continue; }

                    $state = 'cached';
                    foreach (self::segments($text) as $segment) {
                        if (!$segment['translate'] || self::override($overrides, $segment['text']) !== null) continue;
                        $key = self::key($from, $iso, $segment['text']);
                        if (isset($strings[$key])) continue;
                        if (isset($failedCache[$key])) {
                            $state = 'failed';
                            continue;
                        }
                        $state = 'todo';
                        break;
                    }
                    if ($state === 'cached' && is_string($current) && !$outdated) { $done++; continue; }
                    if ($state === 'failed') { $failed++; $fileFailed++; } else { $todo++; $fileTodo++; }
                }
                if ($fileTodo + $fileFailed > 0) $files[] = ['file' => $rel, 'todo' => $fileTodo, 'failed' => $fileFailed];
            }

            usort($files, static fn ($a, $b) => ($b['todo'] + $b['failed']) <=> ($a['todo'] + $a['failed']));
            $languages[] = [
                'iso'     => $iso,
                'text'    => (string) ($lang['text'] ?? $iso),
                'mode'    => $modes[$iso] ?? 'static',
                'total'   => $total,
                'done'    => $done,
                'todo'    => $todo,
                'failed'  => $failed,
                'percent' => $total > 0 ? floor($done * 1000 / $total) / 10 : 100,
                'files'   => $files,
            ];
        }

        return ['default' => $from, 'files' => count($sources), 'languages' => $languages];
    }

    public static function files(string $default): array
    {
        $sourceDir = self::source_dir($default);
        $files = [];
        foreach (self::glob_recursive($sourceDir, 'data_*.json') as $file) {
            $name = basename($file);
            if (str_starts_with($name, 'data_settings_') || !preg_match('/^data_[a-z0-9_]+\.json$/i', $name)) continue;
            $files[] = str_replace('\\', '/', trim(substr($file, strlen($sourceDir)), '/\\'));
        }
        sort($files);
        return $files;
    }

    public static function run_file(string $lang, string $rel, float $budget = 20.0, bool $overwrite = false, string $run = ''): array
    {
        $run = preg_replace('/[^a-z0-9\-]/i', '', $run) ?? '';
        if ($overwrite && $run === '') return ['ok' => false, 'message' => 'Overwrite needs a run id'];
        @set_time_limit(0);
        $started = microtime(true);
        $config = self::language_config();
        $from = $config['default'];
        $rel = str_replace('\\', '/', $rel);

        if (!in_array($lang, array_column($config['list'], 'iso'), true)) {
            return ['ok' => false, 'message' => 'Unknown target language: ' . $lang];
        }
        if (!in_array($rel, self::files($from), true)) {
            return ['ok' => false, 'message' => 'Unknown data file: ' . $rel];
        }

        $status = PlatformTranslationService::status();
        if (!$status['available']) return ['ok' => false, 'message' => $status['message']];
        $copyOnly = $status['provider'] === 'copy';

        self::$skipKeys = self::skip_keys();
        $source = self::read_json(self::source_dir($from) . '/' . $rel, true);
        if ($source === null) return ['ok' => false, 'message' => 'Invalid JSON: ' . $rel];

        $targetPath = PlatformConfig::get('data_dir') . '/' . $lang . '/' . $rel;
        $target = self::read_json($targetPath, true) ?? [];

        $cacheFile = self::cache_path($lang);
        $cache = self::read_json($cacheFile);
        $cache['strings'] = $cache['strings'] ?? [];
        $cache['failed'] = $cache['failed'] ?? [];
        $cache['run'] = $cache['run'] ?? [];
        $overrides = self::rules()['overrides'][$lang] ?? [];
        self::$overrides = $overrides;
        $manifestFile = self::manifest_path($lang);
        $manifest = self::read_json($manifestFile);
        $records = is_array($manifest[$rel] ?? null) ? $manifest[$rel] : [];

        $leaves = [];
        self::collect($source, $leaves, '', true);
        $existing = [];
        self::collect($target, $existing, '', false);

        $plan = [];
        $pending = [];
        $kept = 0;
        $overridden = 0;
        foreach ($leaves as $path => $text) {
            $hash = sha1($text);
            $current = $existing[$path] ?? null;
            $record = $records[$path] ?? null;

            $override = self::override($overrides, $text);
            if ($override !== null) {
                $plan[$path] = ['keep' => $override];
                $overridden++;
                continue;
            }

            if (!$overwrite && is_string($current) && $current !== $text) {
                $outdatedMachine = is_array($record) && $record[0] !== $hash && sha1($current) === $record[1];
                if (!$outdatedMachine) {
                    $plan[$path] = ['keep' => $current];
                    $kept++;
                    continue;
                }
            }

            $plan[$path] = ['segments' => self::segments($text)];
            if ($copyOnly) continue;
            foreach ($plan[$path]['segments'] as $segment) {
                if (!$segment['translate'] || self::override($overrides, $segment['text']) !== null) continue;
                $key = self::key($from, $lang, $segment['text']);
                $needs = $overwrite
                    ? ($cache['run'][$key] ?? '') !== $run && ($cache['failed'][$key] ?? null) !== $run
                    : !isset($cache['strings'][$key]) && !isset($cache['failed'][$key]);
                if ($needs) $pending[$key] = $segment['text'];
            }
        }

        $translated = 0;
        $failed = 0;
        $sinceSave = 0;
        foreach ($pending as $key => $text) {
            if (($translated + $failed) > 0 && microtime(true) - $started > $budget) break;
            $result = PlatformTranslationService::translate($text, $from, $lang);
            if ($result !== null && !self::same_tokens($text, $result)) {
                $result = preg_match(self::TOKENS, $text) ? self::translate_between_tokens($text, $from, $lang) : null;
            }
            if ($result !== null) {
                $cache['strings'][$key] = $result;
                unset($cache['failed'][$key]);
                if ($overwrite) $cache['run'][$key] = $run;
                $translated++;
            } else {
                $cache['failed'][$key] = $overwrite ? $run : true;
                $failed++;
            }
            unset($pending[$key]);
            if (++$sinceSave >= 10) {
                self::write_json($cacheFile, $cache);
                $sinceSave = 0;
            }
        }
        if ($translated + $failed > 0) self::write_json($cacheFile, $cache);

        $result = [
            'ok' => true,
            'lang' => $lang,
            'file' => $rel,
            'strings' => count($leaves),
            'translated' => $translated,
            'failed' => $failed,
            'kept' => $kept,
            'overridden' => $overridden,
            'remaining' => count($pending),
            'done' => $pending === [],
            'written' => false,
        ];
        if ($pending !== []) return $result;

        $newRecords = [];
        $output = self::apply($source, $plan, $cache['strings'], $from, $lang, '', $newRecords);
        $result['written'] = self::write_json($targetPath, $output, true);
        if (!$copyOnly) {
            $manifest[$rel] = $newRecords;
            self::write_json($manifestFile, $manifest);
        }

        return $result;
    }

    private static function collect(array $data, array &$out, string $prefix, bool $onlyTranslatable, bool $skipped = false): void
    {
        $isList = array_is_list($data);
        $seen = [];
        foreach ($data as $key => $value) {
            $inSkipped = $skipped || (!$isList && self::is_skip_key((string) $key));
            if ($inSkipped && !is_array($value) && ($isList || !in_array((string) $key, self::LABEL_KEYS, true))) continue;
            $path = $prefix === '' ? self::segment_key($data, $key, $isList, $seen) : $prefix . self::SEP . self::segment_key($data, $key, $isList, $seen);
            if (is_array($value)) {
                self::collect($value, $out, $path, $onlyTranslatable, $inSkipped);
            } elseif (is_string($value) && (!$onlyTranslatable || self::is_translatable($value))) {
                $out[$path] = $value;
            }
        }
    }

    private static function apply(array $data, array $plan, array $strings, string $from, string $to, string $prefix, array &$records, bool $skipped = false): array
    {
        $isList = array_is_list($data);
        $seen = [];
        foreach ($data as $key => $value) {
            $inSkipped = $skipped || (!$isList && self::is_skip_key((string) $key));
            if ($inSkipped && !is_array($value) && ($isList || !in_array((string) $key, self::LABEL_KEYS, true))) continue;
            $path = $prefix === '' ? self::segment_key($data, $key, $isList, $seen) : $prefix . self::SEP . self::segment_key($data, $key, $isList, $seen);
            if (is_array($value)) {
                $data[$key] = self::apply($value, $plan, $strings, $from, $to, $path, $records, $inSkipped);
                continue;
            }
            if (!isset($plan[$path]) || !is_string($value)) continue;

            if (array_key_exists('keep', $plan[$path])) {
                $out = (string) $plan[$path]['keep'];
            } else {
                $out = '';
                foreach ($plan[$path]['segments'] as $segment) {
                    $out .= $segment['translate']
                        ? $segment['lead'] . (self::override(self::$overrides, $segment['text']) ?? $strings[self::key($from, $to, $segment['text'])] ?? $segment['text']) . $segment['trail']
                        : $segment['raw'];
                }
            }
            $data[$key] = $out;
            $records[$path] = [sha1($value), sha1($out)];
        }
        return $data;
    }

    private static function segment_key(array $data, int|string $key, bool $isList, array &$seen): string
    {
        if ($isList && is_array($data[$key]) && is_string($data[$key]['_id'] ?? null) && $data[$key]['_id'] !== '') {
            $id = '#' . $data[$key]['_id'];
            if (!isset($seen[$id])) {
                $seen[$id] = true;
                return $id;
            }
        }
        return (string) $key;
    }

    private static function segments(string $text): array
    {
        $parts = preg_split(self::BLOCK_SPLIT, $text, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [$text];
        $segments = [];
        foreach ($parts as $index => $part) {
            if ($part === '') continue;
            $isSeparator = $index % 2 === 1;
            $core = trim($part);
            if ($isSeparator || $core === '' || !self::is_segment_translatable($core)) {
                $segments[] = ['translate' => false, 'raw' => $part];
                continue;
            }
            preg_match('/^\s*/u', $part, $lead);
            preg_match('/\s*$/u', $part, $trail);
            $segments[] = ['translate' => true, 'text' => $core, 'lead' => $lead[0] ?? '', 'trail' => $trail[0] ?? '', 'raw' => $part];
        }
        return $segments;
    }

    private static function translate_between_tokens(string $text, string $from, string $to): ?string
    {
        preg_match_all(self::TOKENS, $text, $tokens);
        $pieces = preg_split(self::TOKENS, $text) ?: [$text];
        $out = '';
        foreach ($pieces as $i => $piece) {
            $core = trim($piece);
            if ($core !== '' && self::is_segment_translatable($core)) {
                $translated = PlatformTranslationService::translate($core, $from, $to);
                if ($translated === null) return null;
                preg_match('/^\s*/u', $piece, $lead);
                preg_match('/\s*$/u', $piece, $trail);
                $out .= ($lead[0] ?? '') . $translated . ($trail[0] ?? '');
            } else {
                $out .= $piece;
            }
            $out .= $tokens[0][$i] ?? '';
        }
        return $out;
    }

    private static function same_tokens(string $source, string $result): bool
    {
        preg_match_all(self::TOKENS, $source, $a);
        preg_match_all(self::TOKENS, $result, $b);
        return $a[0] === $b[0];
    }

    private static function is_translatable(string $value): bool
    {
        $value = trim($value);
        if ($value === '' || is_numeric($value)) return false;
        if (!preg_match('/\p{L}/u', $value)) return false;
        if (str_starts_with($value, 'data:') || str_contains($value, "['")) return false;
        $rules = self::rules();
        foreach (array_merge($rules['glossary'], $rules['skip_values']) as $term) {
            if (strcasecmp($value, $term) === 0) return false;
        }
        if (preg_match('/^[\w\-.+]+@[\w\-.]+$/', $value)) return false;
        if (preg_match('~^(https?:)?//|^(mailto|tel):~i', $value)) return false;
        if (preg_match('/^#[0-9a-f]{3,8}$/i', $value)) return false;
        if (preg_match('/^\p{Lu}\p{L}*[:.!?]?$/u', $value)) return true;
        if (!preg_match('/\s/u', $value)) {
            if (str_contains($value, '/') || preg_match('/\.(webp|png|jpe?g|gif|svg|ico|json|html?|css|js|txt|pdf|mp4|webm|mp3|woff2?|ttf|otf)$/i', $value)) return false;
            if (preg_match('/[\-_.\d@#:;=]/', $value) || preg_match('/^[a-z]+$/', $value)) return false;
        }
        return true;
    }

    private static function is_segment_translatable(string $value): bool
    {
        $plain = trim(strip_tags($value));
        if ($plain === '' || !preg_match('/\p{L}/u', $plain)) return false;
        if (preg_match('~^(https?:)?//\S+$~i', $plain)) return false;
        $rules = self::rules();
        foreach (array_merge($rules['glossary'], $rules['skip_values']) as $term) {
            if (strcasecmp($plain, $term) === 0) return false;
        }
        return true;
    }

    private static function is_skip_key(string $key): bool
    {
        if (in_array($key, self::$skipKeys, true)) return true;
        if (!str_contains($key, '.')) return false;
        foreach (explode('.', $key) as $part) {
            if (in_array($part, self::$skipKeys, true)) return true;
        }
        return false;
    }

    private static function skip_keys(): array
    {
        return self::rules()['skip_keys'];
    }

    private static function key(string $from, string $to, string $text): string
    {
        return sha1($from . '|' . $to . '|' . $text);
    }

    private static function source_dir(string $default): string
    {
        $dataDir = PlatformConfig::get('data_dir');
        return is_dir($dataDir . '/' . $default) ? $dataDir . '/' . $default : $dataDir;
    }

    private static function storage_dir(): string
    {
        $root = defined('ENGINE_PROJECT_ROOT') ? ENGINE_PROJECT_ROOT : dirname(__DIR__, 2);
        return $root . '/dev/platform/data/translations';
    }

    private static function rules_path(): string
    {
        return self::storage_dir() . '/rules.json';
    }

    private static function cache_path(string $lang): string
    {
        return self::storage_dir() . '/' . $lang . '.cache.json';
    }

    private static function manifest_path(string $lang): string
    {
        return self::storage_dir() . '/' . $lang . '.manifest.json';
    }

    private static function read_json(string $path, bool $nullable = false): ?array
    {
        if (!is_file($path)) return $nullable ? null : [];
        $content = (string) file_get_contents($path);
        if (strncmp($content, "\xEF\xBB\xBF", 3) === 0) $content = substr($content, 3);
        $data = json_decode($content, true);
        return is_array($data) ? $data : ($nullable ? null : []);
    }

    private static function write_json(string $path, array $data, bool $pretty = false): bool
    {
        if (!is_dir(dirname($path))) mkdir(dirname($path), 0777, true);
        $flags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | ($pretty ? JSON_PRETTY_PRINT : 0);
        $json = json_encode($data === [] && !$pretty ? new stdClass() : $data, $flags);
        return $json !== false && file_put_contents($path, $json, LOCK_EX) !== false;
    }

    private static function glob_recursive(string $dir, string $pattern): array
    {
        if (!is_dir($dir)) return [];
        $files = glob($dir . '/' . $pattern) ?: [];
        foreach (glob($dir . '/*', GLOB_ONLYDIR) ?: [] as $subDir) {
            $files = array_merge($files, self::glob_recursive($subDir, $pattern));
        }
        return $files;
    }
}
