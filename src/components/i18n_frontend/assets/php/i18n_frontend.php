<?php

class i18n_frontend
{
    public const TIMEOUT_MS = 6000;

    public static function render(array $data = []): string
    {
        if (($GLOBALS['render_target'] ?? '') !== 'dist') return '';

        $frontend = PlatformPathService::frontend_languages();
        if (!$frontend) return '';

        $config = [
            'default' => PlatformDataService::default_language(),
            'page_lang' => PlatformDataService::get_language(),
            'langs' => array_keys(PlatformPathService::languages()),
            'frontend' => $frontend,
            'root' => PlatformPathService::asset_relative_prefix(),
            'timeout' => self::TIMEOUT_MS,
        ] + self::data_config($frontend) + PlatformI18nFrontendBuilder::client_config();

        $initPath = __DIR__ . '/../js/i18n_frontend_init.js';
        $init = is_file($initPath) ? trim((string) file_get_contents($initPath)) : '';

        return '<style>html.i18n-loading body>:not([data-preloader]){visibility:hidden}</style>'
            . '<script>window.dmI18n=' . json_encode($config, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) . ';' . $init . '</script>';
    }

    private static function data_config(array $frontend): array
    {
        static $cache = null;
        if ($cache !== null) return $cache;

        $default = PlatformDataService::default_language();
        $dataDir = str_replace('\\', '/', (string) PlatformConfig::get('data_dir'));
        $root = str_replace('\\', '/', ENGINE_PROJECT_ROOT);
        $relative = trim(str_starts_with($dataDir, $root) ? substr($dataDir, strlen($root)) : 'src/content/json/data', '/');

        $files = [];
        $newest = 0;
        foreach (PlatformTranslationBuilder::files($default) as $file) {
            $source = $dataDir . '/' . $default . '/' . $file;
            if (!is_file($source)) continue;
            $langs = [];
            foreach ($frontend as $lang) {
                $target = $dataDir . '/' . $lang . '/' . $file;
                if (!is_file($target)) continue;
                $langs[] = $lang;
                $newest = max($newest, (int) filemtime($target));
            }
            if ($langs === []) continue;
            $newest = max($newest, (int) filemtime($source));
            $files[$file] = $langs;
        }

        return $cache = [
            'data' => $relative . '/',
            'files' => $files,
            'v' => base_convert((string) $newest, 10, 36),
        ];
    }
}
