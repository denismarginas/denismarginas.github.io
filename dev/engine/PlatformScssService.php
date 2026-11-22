<?php

class PlatformScssService
{
    private static bool $loaded = false;

    public static function ensure_loaded(): void
    {
        if (self::$loaded) return;

        $root = defined('ENGINE_PROJECT_ROOT') ? ENGINE_PROJECT_ROOT : dirname(__DIR__, 2);
        $autoload = $root . '/dev/dependencies/scssphp/vendor/autoload.php';

        if (!file_exists($autoload)) {
            throw new \RuntimeException('scssphp not installed. Run dev/platform/require/install-scssphp.bat');
        }

        require_once $autoload;
        self::$loaded = true;
    }

    private static function compiler(array $load_paths = [], bool $compressed = false): object
    {
        self::ensure_loaded();

        $compiler = new \ScssPhp\ScssPhp\Compiler();
        foreach ($load_paths as $load_path) {
            if (is_dir($load_path)) {
                $compiler->addImportPath($load_path);
            }
        }
        if ($compressed) {
            $compiler->setOutputStyle(\ScssPhp\ScssPhp\OutputStyle::COMPRESSED);
        }

        return $compiler;
    }

    public static function compile_file(string $path, array $load_paths = [], bool $compressed = false): string
    {
        return self::compiler($load_paths, $compressed)->compileFile($path)->getCss();
    }

    public static function compile_string(string $scss, array $load_paths = [], bool $compressed = false): string
    {
        return self::compiler($load_paths, $compressed)->compileString($scss)->getCss();
    }

    public static function minify(string $css): string
    {
        return self::compile_string($css, [], true);
    }
}
