<?php

class PlatformTranslationService
{
    private const LANGUAGE_NAMES = [
        'en' => 'English', 'ro' => 'Romanian', 'de' => 'German', 'fr' => 'French', 'es' => 'Spanish',
        'it' => 'Italian', 'pt' => 'Portuguese', 'nl' => 'Dutch', 'pl' => 'Polish', 'hu' => 'Hungarian',
        'cs' => 'Czech', 'sk' => 'Slovak', 'bg' => 'Bulgarian', 'el' => 'Greek', 'ru' => 'Russian',
        'uk' => 'Ukrainian', 'tr' => 'Turkish', 'he' => 'Hebrew', 'ar' => 'Arabic', 'sv' => 'Swedish',
        'da' => 'Danish', 'fi' => 'Finnish', 'no' => 'Norwegian', 'ja' => 'Japanese', 'zh' => 'Chinese', 'ko' => 'Korean',
    ];

    private static ?object $google = null;
    private static bool $googleLoaded = false;

    public static function settings(): array
    {
        $section = PlatformWorkflowService::section('translation');
        return [
            'provider' => strtolower(trim((string) ($section['provider'] ?? 'ollama'))) ?: 'ollama',
            'model' => trim((string) ($section['model'] ?? 'translategemma:4b')) ?: 'translategemma:4b',
            'ollama_url' => rtrim(trim((string) ($section['ollama_url'] ?? 'http://127.0.0.1:11434')) ?: 'http://127.0.0.1:11434', '/'),
            'glossary' => PlatformTranslationBuilder::rules()['glossary'],
        ];
    }

    public static function status(): array
    {
        $s = self::settings();
        if ($s['provider'] === 'copy') {
            return ['provider' => 'copy', 'available' => true, 'message' => 'Copy mode: files are synced without translating.'];
        }
        if ($s['provider'] === 'google') {
            $ok = self::google() !== null;
            return ['provider' => 'google', 'available' => $ok, 'message' => $ok ? 'Google Translate (online)' : 'google-translate-php is not installed in dev/dependencies.'];
        }

        $version = self::ollama_request($s['ollama_url'] . '/api/version', null, 5);
        if (!is_array($version)) {
            return ['provider' => 'ollama', 'available' => false, 'model' => $s['model'], 'message' => 'Ollama is not running at ' . $s['ollama_url'] . '. Start the platform with start.bat (it installs and starts Ollama).'];
        }
        $tags = self::ollama_request($s['ollama_url'] . '/api/tags', null, 5);
        $models = [];
        foreach ((is_array($tags) ? ($tags['models'] ?? []) : []) as $m) $models[] = (string) ($m['name'] ?? '');
        $hasModel = in_array($s['model'], $models, true) || in_array($s['model'] . ':latest', $models, true);

        return [
            'provider' => 'ollama',
            'available' => $hasModel,
            'version' => (string) ($version['version'] ?? ''),
            'model' => $s['model'],
            'models' => $models,
            'message' => $hasModel
                ? 'Ollama ' . ($version['version'] ?? '') . ' · ' . $s['model']
                : 'Model ' . $s['model'] . ' is not downloaded yet. It is pulled by start.bat, or run: ollama pull ' . $s['model'],
        ];
    }

    public static function translate(string $text, string $from, string $to): ?string
    {
        $s = self::settings();
        if ($s['provider'] === 'copy') return $text;
        if ($s['provider'] === 'google') return self::translate_google($text, $from, $to);
        return self::translate_ollama($text, $from, $to, $s);
    }

    public static function language_name(string $iso): string
    {
        return self::LANGUAGE_NAMES[strtolower($iso)] ?? strtoupper($iso);
    }

    private static function translate_ollama(string $text, string $from, string $to, array $s): ?string
    {
        $fromName = self::language_name($from);
        $toName = self::language_name($to);
        $glossary = array_values(array_filter($s['glossary'], fn($term) => stripos($text, $term) !== false));

        $prompt = 'You are a professional ' . $fromName . ' (' . $from . ') to ' . $toName . ' (' . $to . ') translator. '
            . 'Your goal is to accurately convey the meaning and nuances of the original ' . $fromName . ' text while adhering to '
            . $toName . ' grammar, vocabulary, and cultural sensitivities. The text is from a web developer and video editor portfolio website.' . "\n"
            . 'Produce only the ' . $toName . ' translation, without any additional explanations or commentary.'
            . ($glossary !== [] ? ' Keep these terms exactly as written, do not translate them: ' . implode(', ', $glossary) . '.' : '')
            . ' Please translate the following ' . $fromName . ' text into ' . $toName . ":\n\n\n" . $text;

        $response = self::ollama_request($s['ollama_url'] . '/api/generate', [
            'model' => $s['model'],
            'prompt' => $prompt,
            'stream' => false,
            'keep_alive' => '30m',
            'options' => ['temperature' => 0],
        ], 300);

        if (!is_array($response) || !isset($response['response'])) return null;
        return self::clean_output((string) $response['response'], $text);
    }

    private static function clean_output(string $output, string $source): ?string
    {
        $output = trim($output);
        $output = preg_replace('/^```[a-z]*\s*|\s*```$/i', '', $output) ?? $output;
        if ($output === '') return null;

        $quoted = '/^(["\'„“«])(.*)(["\'”»])$/us';
        if (!preg_match($quoted, trim($source)) && preg_match($quoted, $output, $m)) $output = $m[2];

        if (!str_contains($source, "\n")) $output = trim(preg_replace('/\s*\n\s*/u', ' ', $output) ?? $output);
        if (mb_strlen($output) > max(40, mb_strlen($source) * 4)) return null;

        return $output;
    }

    private static function ollama_request(string $url, ?array $body, int $timeout): ?array
    {
        $payload = $body === null ? null : json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 3,
                CURLOPT_TIMEOUT => $timeout,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            ]);
            if ($payload !== null) {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            }
            $raw = curl_exec($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            if (PHP_VERSION_ID < 80000) curl_close($ch);
            if (!is_string($raw) || $code < 200 || $code >= 300) return null;
        } else {
            $context = stream_context_create(['http' => [
                'method' => $payload === null ? 'GET' : 'POST',
                'header' => "Content-Type: application/json\r\n",
                'content' => $payload ?? '',
                'timeout' => $timeout,
                'ignore_errors' => false,
            ]]);
            $raw = @file_get_contents($url, false, $context);
            if (!is_string($raw)) return null;
        }

        $data = json_decode($raw, true);
        return is_array($data) ? $data : null;
    }

    private static function translate_google(string $text, string $from, string $to): ?string
    {
        $translator = self::google();
        if ($translator === null) return null;
        try {
            $translator->setSource($from);
            $translator->setTarget($to);
            $result = $translator->translate($text);
            return is_string($result) && $result !== '' ? $result : null;
        } catch (\Throwable $error) {
            return null;
        }
    }

    private static function google(): ?object
    {
        if (self::$googleLoaded) return self::$google;
        self::$googleLoaded = true;
        $root = defined('ENGINE_PROJECT_ROOT') ? ENGINE_PROJECT_ROOT : dirname(__DIR__, 2);
        $autoload = $root . '/dev/dependencies/google-translate-php/vendor/autoload.php';
        if (!file_exists($autoload)) return null;
        require_once $autoload;
        if (class_exists('Stichoza\GoogleTranslate\GoogleTranslate')) self::$google = new \Stichoza\GoogleTranslate\GoogleTranslate();
        return self::$google;
    }
}
