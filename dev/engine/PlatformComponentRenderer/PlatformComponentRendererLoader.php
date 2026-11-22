<?php

trait PlatformComponentRendererLoader
{
    public static function render(string $component_name, array $data = []): string
    {
        if (($data['render'] ?? true) === false) return '';

        if (array_key_exists('transition', $data)) {
            $transition = $data['transition'];
            unset($data['transition']);
            return self::apply_transition(self::render($component_name, $data), $transition);
        }

        $config = self::get_component_config($component_name);
        if ($config === null) {
            return self::render_error('component_not_found', ['name' => $component_name]);
        }

        self::mark_used($component_name);

        $dependencies = $config['dependencies'] ?? [];
        foreach ($dependencies as $dep) {
            self::load_component_class($dep);
        }

        $php_files = $config['assets']['php'] ?? [];
        if (empty($php_files)) {
            return self::render_error('no_php_files', ['name' => $component_name]);
        }

        $class_name = self::component_name_to_class($component_name);
        if (!isset(self::$loaded_classes[$class_name])) {
            $loaded = false;
            foreach ($php_files as $php_file) {
                $file_path = self::get_component_file_path($component_name, $php_file);
                if ($file_path && file_exists($file_path)) {
                    require_once $file_path;
                    $loaded = true;
                }
            }

            if (!$loaded || !class_exists($class_name, false)) {
                return self::render_error('class_not_found', ['class' => $class_name]);
            }

            self::$loaded_classes[$class_name] = true;
        }

        if (!class_exists($class_name, false)) {
            return self::render_error('class_not_loaded', ['class' => $class_name]);
        }

        $component = new $class_name();

        if (method_exists($component, 'render')) {
            return $component->render($data);
        }

        if (method_exists($class_name, 'render')) {
            return $class_name::render($data);
        }

        return self::render_error('no_render_method', ['class' => $class_name]);
    }

    public static function apply_transition(string $html, mixed $transition): string
    {
        $attrs = self::transition_attrs($transition);
        if ($attrs === '') return $html;
        if (!preg_match('/^(\s*(?:<!--.*?-->\s*)*)<([a-zA-Z][a-zA-Z0-9-]*)([^>]*)>/s', $html, $m)) return $html;
        if (stripos($m[3], 'data-transition') !== false) return $html;
        self::mark_used('transitions');
        $pos = strlen($m[1]) + 1 + strlen($m[2]);
        return substr($html, 0, $pos) . $attrs . substr($html, $pos);
    }

    public static function transition_rule(string $component_name): ?array
    {
        static $settings = null;
        if ($settings === null) {
            $loaded = class_exists('PlatformDataService') ? PlatformDataService::get_data('settings_transitions') : null;
            $settings = is_array($loaded) ? $loaded : [];
        }
        if (($settings['enabled'] ?? true) === false) return null;

        $rule = $settings['components'][str_replace('-', '_', $component_name)] ?? null;
        if (is_string($rule) && trim($rule) !== '') $rule = ['effect' => $rule];
        if (!is_array($rule)) return null;

        $defaults = is_array($settings['defaults'] ?? null) ? $settings['defaults'] : [];
        return array_merge($defaults, $rule, ['auto' => true]);
    }

    public static function transition_attrs(mixed $transition): string
    {
        if (is_string($transition)) $transition = ['effect' => $transition];
        if (!is_array($transition)) return '';

        $effects = $transition['effect'] ?? $transition['effects'] ?? 'fade';
        $effects = is_array($effects) ? implode(' ', $effects) : (string) $effects;
        $effects = strtolower(trim(preg_replace('/[\s,]+/', ' ', $effects)));

        $parts = [];
        foreach ((array) ($transition['parts'] ?? []) as $part) {
            if (!is_array($part) || !is_scalar($part['target'] ?? null) || !is_scalar($part['effect'] ?? null)) continue;
            $parts[] = array_filter($part, static fn ($v) => is_scalar($v));
        }
        if ($effects === '' && $parts) $effects = 'none';
        if ($effects === '') return '';

        $e = static fn (string $v): string => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
        $attrs = ' data-transition="' . $e($effects) . '"';
        foreach (['duration', 'delay', 'easing', 'distance', 'angle', 'scale', 'blur', 'threshold', 'target', 'stagger'] as $key) {
            $value = $transition[$key] ?? null;
            if (!is_scalar($value) || trim((string) $value) === '') continue;
            $attrs .= ' data-transition-' . $key . '="' . $e((string) $value) . '"';
        }
        if (($transition['once'] ?? true) === false) $attrs .= ' data-transition-once="false"';
        if (($transition['each'] ?? false) === true) $attrs .= ' data-transition-each="true"';
        if (($transition['auto'] ?? false) === true) $attrs .= ' data-transition-auto="true"';
        if ($parts) $attrs .= ' data-transition-parts="' . $e((string) json_encode($parts, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) . '"';
        return $attrs;
    }

    private static function render_error(string $key, array $params = []): string
    {
        if (class_exists('PlatformErrorRenderer')) {
            return PlatformErrorRenderer::render($key, $params);
        }
        return '';
    }

    public static function render_component(string $component_name, array $data = []): string
    {
        return self::render($component_name, $data);
    }

    public static function value(string $component_name, array $data = []): mixed
    {
        $config = self::get_component_config($component_name);
        if ($config === null) {
            return null;
        }

        self::mark_used($component_name);

        $dependencies = $config['dependencies'] ?? [];
        foreach ($dependencies as $dep) {
            self::load_component_class($dep);
        }

        $php_files = $config['assets']['php'] ?? [];
        if (empty($php_files)) {
            return null;
        }

        $class_name = self::component_name_to_class($component_name);
        if (!isset(self::$loaded_classes[$class_name])) {
            $loaded = false;
            foreach ($php_files as $php_file) {
                $file_path = self::get_component_file_path($component_name, $php_file);
                if ($file_path && file_exists($file_path)) {
                    require_once $file_path;
                    $loaded = true;
                }
            }

            if (!$loaded || !class_exists($class_name, false)) {
                return null;
            }
            self::$loaded_classes[$class_name] = true;
        }

        if (!class_exists($class_name, false)) {
            return null;
        }

        $component = new $class_name();
        if (method_exists($component, 'value')) {
            return $component->value($data);
        }
        if (method_exists($class_name, 'value')) {
            return $class_name::value($data);
        }
        if (method_exists($component, 'render')) {
            return $component->render($data);
        }
        if (method_exists($class_name, 'render')) {
            return $class_name::render($data);
        }

        return null;
    }
}
