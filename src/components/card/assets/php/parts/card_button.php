<?php

class card_button extends card_resolve
{
    public static function render_button(array $data, array $postData, string $link): string
    {
        $raw = $data['button'] ?? null;

        if (is_array($raw) && isset($raw['component'])) {
            return self::resolve_component_spec($raw, $postData);
        }

        if (is_string($raw) && str_starts_with(ltrim($raw), '<')) {
            return $raw;
        }

        $label = self::resolve_text_field($data, $postData, 'button');
        if ($label === '' || $link === '') return '';

        return PlatformTemplateRenderer::render(__DIR__ . '/../../html/parts/button.html', [
            'link' => htmlspecialchars($link, ENT_QUOTES, 'UTF-8'),
            'label' => htmlspecialchars($label, ENT_QUOTES, 'UTF-8'),
        ]);
    }

    public static function resolve_component_spec(array $spec, array $postData): string
    {
        $component = (string) ($spec['component'] ?? '');
        $rawParams = $spec['params'] ?? $spec['data'] ?? [];
        if (!is_array($rawParams)) {
            $rawParams = [];
        }

        $params = [];
        foreach ($rawParams as $key => $value) {
            if (is_string($value) && str_starts_with($value, '@')) {
                $params[$key] = PlatformDataService::resolve_path_string($postData, substr($value, 1)) ?? $value;
            } else {
                $params[$key] = $value;
            }
        }

        return (string) PlatformComponentRenderer::value($component, $params);
    }
}

