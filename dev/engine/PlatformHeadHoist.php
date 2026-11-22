<?php

class PlatformHeadHoist
{
    public static function enabled(): bool
    {
        return PlatformWorkflowService::bool('render', 'hoist_to_head', false);
    }

    public static function maybe_apply(string $html): string
    {
        return self::enabled() ? self::apply($html) : $html;
    }

    public static function apply(string $html): string
    {
        $headEnd = stripos($html, '</head>');
        if ($headEnd === false) return $html;
        $bodyStart = stripos($html, '<body', $headEnd);
        if ($bodyStart === false) return $html;

        $head = substr($html, 0, $headEnd);
        $between = substr($html, $headEnd, $bodyStart - $headEnd);
        $body = substr($html, $bodyStart);

        $headHrefs = [];
        if (preg_match_all('/<link\b[^>]*\bhref\s*=\s*([\'"])(.*?)\1[^>]*>/is', $head, $m)) {
            foreach ($m[2] as $href) $headHrefs[$href] = true;
        }

        $pattern = '/([ \t]*)(<noscript\b[^>]*>.*?<\/noscript\s*>|<script\b[^>]*>.*?<\/script\s*>|<style\b[^>]*>.*?<\/style\s*>|<link\b[^>]*>)(\r?\n)?/is';

        $moved = [];
        $body = preg_replace_callback($pattern, function ($match) use (&$moved, &$headHrefs) {
            $out = self::hoistable($match[2], $headHrefs);
            if ($out === null) return $match[0];
            if ($out !== '') $moved[] = $out;
            return '';
        }, $body) ?? $body;

        if (!$moved) return $html;

        return $head . "\n    " . implode("\n    ", $moved) . "\n" . $between . $body;
    }

    private static function hoistable(string $tag, array &$headHrefs): ?string
    {
        $open = preg_match('/^<[^>]*>/s', $tag, $m) ? $m[0] : $tag;
        if (preg_match('/\bdata-no-hoist\b/i', $open)) return null;

        $name = strtolower(preg_match('/^<\s*([a-z]+)/i', $open, $n) ? $n[1] : '');

        if ($name === 'link') {
            $rel = strtolower(self::attr($open, 'rel') ?? '');
            if (!preg_match('/\b(stylesheet|preload|modulepreload)\b/', $rel)) return null;
            $href = self::attr($open, 'href');
            if ($href !== null) {
                if (isset($headHrefs[$href])) return '';
                $headHrefs[$href] = true;
            }
            return $tag;
        }

        if ($name === 'style') return $tag;

        if ($name === 'noscript') {
            $inner = preg_replace('/^<noscript\b[^>]*>|<\/noscript\s*>$/i', '', $tag);
            $rest = preg_replace('/<style\b[^>]*>.*?<\/style\s*>|<link\b[^>]*>|\s+/is', '', $inner);
            return $rest === '' ? $tag : null;
        }

        if ($name === 'script') {
            $type = strtolower(trim(self::attr($open, 'type') ?? ''));
            $isCode = $type === '' || $type === 'module' || str_contains($type, 'javascript') || $type === 'text/ecmascript';
            if (!$isCode) return null;

            $src = self::attr($open, 'src');
            if ($src !== null) {
                if (isset($headHrefs['script:' . $src])) return '';
                $headHrefs['script:' . $src] = true;
                $deferred = $type === 'module' || preg_match('/\s(async|defer)\b/i', $open);
                return $deferred ? $tag : preg_replace('/^<script\b/i', '<script defer', $tag, 1);
            }

            return preg_match('/\bdata-hoist\b/i', $open) ? $tag : null;
        }

        return null;
    }

    private static function attr(string $openTag, string $name): ?string
    {
        if (preg_match('/\s' . preg_quote($name, '/') . '\s*=\s*("([^"]*)"|\'([^\']*)\'|([^\s>]+))/i', $openTag, $m)) {
            return $m[2] !== '' ? $m[2] : ($m[3] ?? '') . ($m[4] ?? '');
        }
        return null;
    }
}
