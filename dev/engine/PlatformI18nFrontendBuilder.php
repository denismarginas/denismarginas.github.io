<?php

class PlatformI18nFrontendBuilder
{
    public const FOLDER = 'i18n';
    public const COMMON_FILE = '_common';
    public const COMMON_RATIO = 0.5;
    public const COMMON_MIN_PAGES = 3;

    public const SKIP_TAGS = ['script', 'style', 'noscript', 'template', 'svg', 'math', 'textarea', 'iframe', 'object', 'canvas'];
    public const ATTRIBUTES = ['alt', 'title', 'placeholder', 'aria-label', 'aria-description', 'data-tooltip'];
    public const ATTRIBUTE_PREFIXES = ['data-label'];
    public const META = ['description', 'og:title', 'og:description', 'og:image:alt', 'twitter:title', 'twitter:description', 'twitter:image:alt'];
    public const INPUT_VALUE_TYPES = ['submit', 'button', 'reset'];

    private const WS = '/[\t\n\x0B\f\r \x{00A0}\x{1680}\x{2000}-\x{200A}\x{2028}\x{2029}\x{202F}\x{205F}\x{3000}\x{FEFF}]+/u';
    private const SEED_A = 2166136261;
    private const SEED_B = 84696351;

    private static array $pages = [];
    private static array $stats = [];

    public static function client_config(): array
    {
        return [
            'skip' => self::SKIP_TAGS,
            'attrs' => self::ATTRIBUTES,
            'prefixes' => self::ATTRIBUTE_PREFIXES,
            'meta' => self::META,
            'inputs' => self::INPUT_VALUE_TYPES,
        ];
    }

    public static function normalize(string $text): string
    {
        return trim((string) preg_replace(self::WS, ' ', $text), ' ');
    }

    public static function key(string $normalized): string
    {
        return self::pad(self::fnv($normalized, self::SEED_A)) . self::pad(self::fnv($normalized, self::SEED_B));
    }

    public static function dist_dir(string $lang = ''): string
    {
        return ENGINE_PROJECT_ROOT . '/dist/' . self::FOLDER . ($lang !== '' ? '/' . $lang : '');
    }

    public static function page_key(string $relPath): string
    {
        return (string) preg_replace('/\.[a-z0-9]+$/i', '', ltrim($relPath, '/'));
    }

    public static function reset(string $lang): void
    {
        self::$pages[$lang] = [];
        self::$stats[$lang] = ['pages' => 0, 'missing_default' => 0, 'skipped_blocks' => 0, 'conflicts' => 0, 'collisions' => 0];
    }

    public static function add_page(string $lang, string $relPath, string $translatedHtml): array
    {
        if (!isset(self::$pages[$lang])) self::reset($lang);

        $defaultFile = ENGINE_PROJECT_ROOT . '/dist/' . ltrim($relPath, '/');
        if (!is_file($defaultFile)) {
            self::$stats[$lang]['missing_default']++;
            return ['ok' => false, 'message' => 'Default page not rendered: ' . $relPath];
        }

        $result = self::collect((string) file_get_contents($defaultFile), $translatedHtml);
        self::$pages[$lang][self::page_key($relPath)] = $result['pairs'];
        self::$stats[$lang]['pages']++;
        self::$stats[$lang]['skipped_blocks'] += $result['skipped'];
        self::$stats[$lang]['conflicts'] += $result['conflicts'];

        return ['ok' => true, 'strings' => count($result['pairs']), 'skipped' => $result['skipped']];
    }

    public static function collect(string $sourceHtml, string $translatedHtml): array
    {
        $a = self::parse($sourceHtml);
        $b = self::parse($translatedHtml);
        $state = ['pairs' => [], 'skipped' => 0, 'conflicts' => 0];
        if ($a && $b) self::walk($a, $b, $state);
        return $state;
    }

    public static function write(string $lang): array
    {
        $pages = self::$pages[$lang] ?? [];
        $stats = self::$stats[$lang] ?? [];
        $dir = self::dist_dir($lang);
        self::remove_dir($dir);

        $usage = [];
        foreach ($pages as $pairs) {
            foreach ($pairs as $src => $dst) {
                $usage[$src][$dst] = ($usage[$src][$dst] ?? 0) + 1;
            }
        }
        $threshold = max(self::COMMON_MIN_PAGES, (int) ceil(count($pages) * self::COMMON_RATIO));

        $common = [];
        foreach ($usage as $src => $targets) {
            if (count($targets) !== 1) continue;
            $count = reset($targets);
            if ($count >= $threshold) $common[$src] = key($targets);
        }

        $keys = [];
        $bytes = 0;
        $files = 0;
        $encode = function (array $pairs) use (&$keys, &$stats): string {
            $out = [];
            foreach ($pairs as $src => $dst) {
                $k = self::key((string) $src);
                if (isset($keys[$k]) && $keys[$k] !== $src) $stats['collisions']++;
                $keys[$k] = $src;
                $out[$k] = (string) $dst;
            }
            return $out ? json_encode($out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '{}';
        };

        $json = $encode($common);
        self::put($dir . '/' . self::COMMON_FILE . '.json', $json);
        $bytes += strlen($json);
        $files++;

        foreach ($pages as $pageKey => $pairs) {
            $own = array_diff_key($pairs, $common);
            $json = $encode($own);
            self::put($dir . '/' . $pageKey . '.json', $json);
            $bytes += strlen($json);
            $files++;
        }

        return $stats + [
            'lang' => $lang,
            'files' => $files,
            'bytes' => $bytes,
            'common_strings' => count($common),
            'common_threshold' => $threshold,
            'folder' => 'dist/' . self::FOLDER . '/' . $lang,
        ];
    }

    public static function remove_dir(string $dir): void
    {
        if (!is_dir($dir)) return;
        $items = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($items as $item) {
            $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
        }
        @rmdir($dir);
    }

    private static function put(string $file, string $content): void
    {
        if (!is_dir(dirname($file))) mkdir(dirname($file), 0777, true);
        file_put_contents($file, $content);
    }

    private static function parse(string $html): mixed
    {
        if ($html === '') return null;
        if (class_exists('\Dom\HTMLDocument')) {
            try {
                return \Dom\HTMLDocument::createFromString($html, LIBXML_NOERROR)->documentElement;
            } catch (Throwable $e) {
            }
        }
        $doc = new DOMDocument();
        $prev = libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);
        return $doc->documentElement;
    }

    private static function walk(mixed $a, mixed $b, array &$state): void
    {
        self::element_pairs($a, $b, $state);

        $ca = self::children($a);
        $cb = self::children($b);
        if (count($ca) !== count($cb)) {
            $state['skipped']++;
            return;
        }

        foreach ($ca as $i => $na) {
            $nb = $cb[$i];
            if ($na->nodeType !== $nb->nodeType) {
                $state['skipped']++;
                continue;
            }
            if ($na->nodeType === XML_TEXT_NODE || $na->nodeType === XML_CDATA_SECTION_NODE) {
                self::pair((string) $na->textContent, (string) $nb->textContent, $state);
                continue;
            }
            if (strtolower((string) $na->localName) !== strtolower((string) $nb->localName)) {
                $state['skipped']++;
                continue;
            }
            self::walk($na, $nb, $state);
        }
    }

    private static function children(mixed $node): array
    {
        $out = [];
        foreach ($node->childNodes as $child) {
            $type = $child->nodeType;
            if ($type === XML_TEXT_NODE || $type === XML_CDATA_SECTION_NODE) {
                if (self::normalize((string) $child->textContent) !== '') $out[] = $child;
                continue;
            }
            if ($type !== XML_ELEMENT_NODE || self::skipped_element($child)) continue;
            $out[] = $child;
        }
        return $out;
    }

    private static function skipped_element(mixed $el): bool
    {
        if (in_array(strtolower((string) $el->localName), self::SKIP_TAGS, true)) return true;
        if (strtolower(trim((string) $el->getAttribute('translate'))) === 'no') return true;
        if ($el->hasAttribute('data-i18n-skip')) return true;
        return preg_match('/(^|\s)notranslate(\s|$)/', (string) $el->getAttribute('class')) === 1;
    }

    private static function element_pairs(mixed $a, mixed $b, array &$state): void
    {
        $tag = strtolower((string) $a->localName);

        foreach ($a->attributes as $attr) {
            $name = strtolower((string) $attr->name);
            if (!self::tracked_attribute($name)) continue;
            if (!$b->hasAttribute($attr->name)) continue;
            self::pair((string) $attr->value, (string) $b->getAttribute($attr->name), $state);
        }

        if ($tag === 'meta') {
            $name = strtolower(trim((string) ($a->getAttribute('name') ?: $a->getAttribute('property'))));
            if ($name !== '' && in_array($name, self::META, true)) {
                self::pair((string) $a->getAttribute('content'), (string) $b->getAttribute('content'), $state);
            }
        }

        if ($tag === 'input' && in_array(strtolower((string) $a->getAttribute('type')), self::INPUT_VALUE_TYPES, true)) {
            self::pair((string) $a->getAttribute('value'), (string) $b->getAttribute('value'), $state);
        }
    }

    private static function tracked_attribute(string $name): bool
    {
        if (in_array($name, self::ATTRIBUTES, true)) return true;
        foreach (self::ATTRIBUTE_PREFIXES as $prefix) {
            if (str_starts_with($name, $prefix)) return true;
        }
        return false;
    }

    private static function pair(string $source, string $translated, array &$state): void
    {
        $src = self::normalize($source);
        $dst = self::normalize($translated);
        if ($src === '' || $dst === '' || $src === $dst) return;
        if (!preg_match('/\p{L}/u', $src)) return;
        if (isset($state['pairs'][$src])) {
            if ($state['pairs'][$src] !== $dst) $state['conflicts']++;
            return;
        }
        $state['pairs'][$src] = $dst;
    }

    private static function fnv(string $text, int $hash): int
    {
        $length = strlen($text);
        for ($i = 0; $i < $length; $i++) {
            $hash ^= ord($text[$i]);
            $hash = ($hash * 16777619) & 0xFFFFFFFF;
        }
        return $hash;
    }

    private static function pad(int $hash): string
    {
        return str_pad(base_convert((string) $hash, 10, 36), 7, '0', STR_PAD_LEFT);
    }
}
