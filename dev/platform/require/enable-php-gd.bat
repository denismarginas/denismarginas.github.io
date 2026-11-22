@echo off
setlocal

rem Enables the GD image extension in this project's local PHP (dev\dependencies\php-v-8.5*).
rem GD ships with PHP (ext\php_gd.dll), so nothing is downloaded - only php.ini is updated.
rem Used by the "Generate Thumbnails" tool of the platform.

set "SCRIPT_DIR=%~dp0"
for %%I in ("%SCRIPT_DIR%..\..\..") do set "ROOT_DIR=%%~fI"
set "DEP_DIR=%ROOT_DIR%\dev\dependencies"
set "PHP_VERSION_PREFIX=php-v-8.5"
set "PHP_CURRENT="

if exist "%DEP_DIR%" (
    for /d %%D in ("%DEP_DIR%\%PHP_VERSION_PREFIX%*") do (
        if not defined PHP_CURRENT if exist "%%D\current\php.exe" set "PHP_CURRENT=%%D\current"
    )
)

if not defined PHP_CURRENT (
    echo This project's local PHP install wasn't found under %DEP_DIR%.
    echo Run dev\platform\require\install-php.bat first, then run this script again.
    goto END
)

set "PHP_INI=%PHP_CURRENT%\php.ini"

if not exist "%PHP_CURRENT%\ext\php_gd.dll" (
    echo php_gd.dll was not found in %PHP_CURRENT%\ext - GD can't be enabled for this PHP build.
    goto END
)

if not exist "%PHP_INI%" (
    if exist "%PHP_CURRENT%\php.ini-production" (
        copy "%PHP_CURRENT%\php.ini-production" "%PHP_INI%" >nul
        >>"%PHP_INI%" echo.
        >>"%PHP_INI%" echo extension_dir = ext
    ) else (
        echo php.ini was not found in %PHP_CURRENT%.
        goto END
    )
)

powershell -NoProfile -ExecutionPolicy Bypass -Command ^
    "$ini = '%PHP_INI%';" ^
    "$lines = Get-Content -LiteralPath $ini;" ^
    "if ($lines -match '^\s*extension\s*=\s*(php_)?gd(\.dll)?\s*$') { Write-Host 'PHP GD is already enabled.'; exit 0 };" ^
    "if ($lines -match '^\s*;\s*extension\s*=\s*gd\s*$') {" ^
    "  $lines = $lines -replace '^\s*;\s*extension\s*=\s*gd\s*$', 'extension=gd';" ^
    "  Set-Content -LiteralPath $ini -Value $lines;" ^
    "} else {" ^
    "  Add-Content -LiteralPath $ini -Value 'extension=gd';" ^
    "};" ^
    "Write-Host 'PHP GD enabled in' $ini"

:END
if "%~1" neq "auto" pause
endlocal
