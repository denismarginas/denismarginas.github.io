<?php

class post_items_value
{
    public static function value_at(array $data, mixed $path): ?string
    {
        if (!is_array($path)) return null;
        $value = $data;
        foreach ($path as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) return null;
            $value = $value[$segment];
        }
        return is_string($value) ? $value : null;
    }

    public static function mixed_at(array $data, array $path): mixed
    {
        $value = $data;
        foreach ($path as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) return null;
            $value = $value[$segment];
        }
        return $value;
    }

    public static function resolve_template_value(mixed $value, array $context): mixed
    {
        if (is_array($value)) {
            if (isset($value['component']) && is_string($value['component'])) {
                return self::resolve_component_value($value, $context);
            }
            $out = [];
            foreach ($value as $key => $item) {
                $out[$key] = self::resolve_template_value($item, $context);
            }
            return $out;
        }
        if (!is_string($value)) return $value;

        $trimmed = trim($value);
        if ($trimmed === '') return '';
        if (str_contains($trimmed, '+')) {
            return self::concat_value($trimmed, $context);
        }

        return self::resolve_token($trimmed, $context);
    }

    public static function resolve_component_value(array $spec, array $context): mixed
    {
        $component = (string) ($spec['component'] ?? '');
        $rawParams = $spec['params'] ?? $spec['data'] ?? [];
        if (!is_array($rawParams)) {
            $rawParams = [];
        }

        $params = self::resolve_template_value($rawParams, $context);
        if (!is_array($params)) {
            $params = [];
        }

        return PlatformComponentRenderer::value($component, $params);
    }

    public static function concat_value(string $value, array $context): ?string
    {
        $parts = preg_split('/\s*\+(?=\s*|$)/', $value, -1, PREG_SPLIT_NO_EMPTY);
        $out = '';
        foreach ($parts as $part) {
            $resolved = self::resolve_token(trim($part), $context);
            if (is_array($resolved)) {
                $resolved = '';
            }
            $out .= (string) $resolved;
        }
        return $out;
    }

    public static function resolve_token(string $token, array $context): mixed
    {
        $len = strlen($token);
        if ($token !== '' && ($token[0] === '"' && $token[$len - 1] === '"' || $token[0] === "'" && $token[$len - 1] === "'") && $len >= 2) {
            return substr($token, 1, -1);
        }
        if (str_starts_with($token, '@')) {
            $ref = substr($token, 1);

            if (!str_contains($ref, '/')) {
                return self::resolve_ref($ref, $context);
            }

            $segments = explode('/', $ref);
            $out = [];
            foreach ($segments as $segment) {
                $segment = ltrim(trim($segment), '@');
                $resolved = self::resolve_ref($segment, $context);
                $out[] = is_array($resolved) ? (string) ($resolved[0] ?? '') : (string) $resolved;
            }
            return implode('/', $out);
        }
        return $token;
    }

    public static function resolve_ref(string $ref, array $context): mixed
    {
        if ($ref === 'post_id' || $ref === '_id') {
            return (string) ($context['post_id'] ?? '');
        }
        if ($ref === 'post_link') {
            return PlatformPathService::post_link((string) ($context['post_id'] ?? ''));
        }
        if ($ref === 'img_base') {
            return (string) ($context['img_base'] ?? '');
        }
        if (str_starts_with($ref, 'data.')) {
            return self::mixed_at($context['data'] ?? [], explode('.', substr($ref, 5)));
        }
        if (str_starts_with($ref, 'settings.')) {
            return self::mixed_at($context['settings'] ?? [], explode('.', substr($ref, 9)));
        }
        return $ref;
    }
}


