<?php

function platform_card_vars(string $cardType): array
{
    if (!class_exists('PlatformWorkflowService')) {
        if (file_exists(__DIR__ . '/../../../engine/bootstrap.php')) {
            require_once __DIR__ . '/../../../engine/bootstrap.php';
        }
    }
    if (!class_exists('PlatformWorkflowService')) return [];
    return PlatformWorkflowService::vars($cardType);
}

function platform_vars_tokens(): array
{
    static $tokens = null;
    if ($tokens !== null) return $tokens;
    $root = defined('ENGINE_PROJECT_ROOT') ? ENGINE_PROJECT_ROOT : dirname(__DIR__, 4);
    $path = $root . '/src/content/json/data/settings/data_settings_routing.json';
    $routing = file_exists($path) ? json_decode((string) file_get_contents($path), true) : [];
    $tokens = ['{routing_url}' => is_array($routing) ? trim((string) ($routing['url'] ?? '')) : ''];
    return $tokens;
}

function platform_render_vars(string $cardType, string $action = ''): string
{
    platform_asset('css', 'components/vars/assets/css/vars.css');
    platform_asset('js', 'components/vars/assets/js/vars.js');

    $variables = platform_card_vars($cardType);
    if ($variables === []) return '';

    $e = static fn (string $v): string => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
    $get = static fn (string $path, string $default = ''): string => PlatformData::getString($path, $default);
    $tokens = platform_vars_tokens();
    $meta = static function (string $name, string $key) use ($cardType, $get, $tokens): string {
        return strtr($get('vars.' . $cardType . '.' . $name . '.' . $key, ''), $tokens);
    };

    $inputs = '';
    $toggles = '';
    foreach ($variables as $var) {
        $name = (string) $var['name'];
        $value = (string) $var['value'];
        $label = $meta($name, 'label');
        if ($label === '') $label = ucfirst(trim(str_replace('_', ' ', $name)));
        $hint = $meta($name, 'hint');
        $placeholder = $meta($name, 'placeholder');
        $head = '<span class="platform-vars-label">' . $e($label) . '<code class="platform-vars-key">' . $e($name) . '</code></span>';

        if ($value === 'true' || $value === 'false') {
            $toggles .= '<label class="platform-vars-field platform-vars-field-bool">'
                . '<span class="platform-vars-toggle">'
                . '<input type="checkbox" role="switch" data-vars-name="' . $e($name) . '" data-vars-bool="1"' . ($value === 'true' ? ' checked' : '') . '>'
                . '<span class="platform-vars-toggle-track" aria-hidden="true"></span>'
                . '</span>'
                . '<span class="platform-vars-copy">' . $head
                . ($hint !== '' ? '<span class="platform-vars-hint">' . $e($hint) . '</span>' : '')
                . '</span>'
                . '</label>';
            continue;
        }

        $numeric = $value !== '' && is_numeric($value);
        $inputs .= '<label class="platform-vars-field platform-vars-field-text">'
            . $head
            . '<input class="platform-input" type="text" data-vars-name="' . $e($name) . '" value="' . $e($value) . '"'
            . ($placeholder !== '' ? ' placeholder="' . $e($placeholder) . '"' : '')
            . ($numeric ? ' inputmode="numeric"' : '')
            . ' spellcheck="false">'
            . ($hint !== '' ? '<span class="platform-vars-hint">' . $e($hint) . '</span>' : '')
            . '</label>';
    }

    return '<div class="platform-vars" data-platform-vars data-vars-card-type="' . $e($cardType) . '"' . ($action !== '' ? ' data-vars-action="' . $e($action) . '"' : '') . '>'
        . '<h3 class="platform-vars-title">' . $e($get('vars.title', 'Params')) . '</h3>'
        . ($inputs !== '' ? '<div class="platform-vars-grid platform-vars-inputs">' . $inputs . '</div>' : '')
        . ($toggles !== '' ? '<div class="platform-vars-grid platform-vars-toggles">' . $toggles . '</div>' : '')
        . '<div class="platform-vars-actions">'
        . '<button class="platform-button platform-button-sm" type="button" data-vars-save>' . $e($get('vars.save', 'Save params')) . '</button>'
        . '<span class="platform-status" data-vars-status>' . $e($get('vars.idle', 'Idle')) . '</span>'
        . '</div>'
        . '</div>';
}
