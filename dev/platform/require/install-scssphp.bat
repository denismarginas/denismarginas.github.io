@echo off
setlocal

set "SCRIPT_DIR=%~dp0"
for %%I in ("%SCRIPT_DIR%..\..\..") do set "ROOT_DIR=%%~fI"
set "DEP_DIR=%ROOT_DIR%\dev\dependencies"
set "SCSSPHP_DIR=%DEP_DIR%\scssphp"

if exist "%SCSSPHP_DIR%\vendor\autoload.php" (
    echo scssphp is already installed in %SCSSPHP_DIR%!
    goto END
)

rem git isn't always on PATH (Laragon bundles its own copy but doesn't add
rem it to the system PATH), so look for it there too before giving up.
set "GIT_EXE="
for /f "delims=" %%G in ('where git 2^>nul') do if not defined GIT_EXE set "GIT_EXE=%%G"

if not defined GIT_EXE (
    for %%C in (
        "%ROOT_DIR%\..\..\bin\git\cmd\git.exe"
        "C:\laragon\bin\git\cmd\git.exe"
        "%ProgramFiles%\Git\cmd\git.exe"
    ) do (
        if not defined GIT_EXE if exist %%C set "GIT_EXE=%%~fC"
    )
)

if not defined GIT_EXE (
    echo.
    echo git was not found on PATH or in the usual Laragon/Git-for-Windows locations.
    echo Install Git, or add Laragon's bundled Git ^(C:\laragon\bin\git\cmd^) to your PATH, then run this script again.
    echo scssphp was NOT installed.
    pause
    endlocal
    exit /b 1
)

if not exist "%SCSSPHP_DIR%\.git" (
    echo Cloning scssphp into %SCSSPHP_DIR% using %GIT_EXE%...
    "%GIT_EXE%" clone https://github.com/scssphp/scssphp.git "%SCSSPHP_DIR%"

    if not exist "%SCSSPHP_DIR%\.git" (
        echo.
        echo scssphp clone appears to have failed - check your network connection and the message above.
        pause
        endlocal
        exit /b 1
    )
) else (
    echo scssphp repo already cloned in %SCSSPHP_DIR%, but its Composer dependencies are missing.
)

rem scssphp itself declares real runtime dependencies (league/uri,
rem scssphp/source-span, symfony/filesystem, ...), so a bare git clone is
rem not enough - we still need Composer to fetch those into vendor/.
rem This project doesn't rely on Laragon's (or any global) PHP or Composer,
rem so we keep a project-local composer.phar right next to the other
rem dependencies instead of depending on anything outside the project.
set "COMPOSER_DIR=%DEP_DIR%\composer"
set "COMPOSER_PHAR=%COMPOSER_DIR%\composer.phar"

rem Resolve this project's own local PHP install (dev\dependencies\php-v-8.5*)
rem - never Laragon's or any other global PHP.
set "PHP_EXE="
set "PHP_VERSION_PREFIX=php-v-8.5"
if exist "%DEP_DIR%" (
    for /d %%D in ("%DEP_DIR%\%PHP_VERSION_PREFIX%*") do (
        if not defined PHP_EXE if exist "%%D\current\php.exe" set "PHP_EXE=%%D\current\php.exe"
    )
)

if not defined PHP_EXE (
    echo.
    echo This project's local PHP install wasn't found under %DEP_DIR%.
    echo Run dev\platform\require\install-php.bat first, then run this script again.
    echo scssphp was cloned but its dependencies are NOT installed yet.
    pause
    endlocal
    exit /b 1
)

if not exist "%COMPOSER_PHAR%" (
    echo Downloading a project-local composer.phar into %COMPOSER_DIR%...
    if not exist "%COMPOSER_DIR%" mkdir "%COMPOSER_DIR%"
    powershell -NoProfile -Command "try { Invoke-WebRequest -Uri 'https://getcomposer.org/download/latest-stable/composer.phar' -OutFile '%COMPOSER_PHAR%' -UseBasicParsing } catch { exit 1 }"

    if not exist "%COMPOSER_PHAR%" (
        echo.
        echo composer.phar download failed - check your network connection.
        pause
        endlocal
        exit /b 1
    )
)

echo Running composer install in %SCSSPHP_DIR% using %COMPOSER_PHAR% and %PHP_EXE%...
"%PHP_EXE%" "%COMPOSER_PHAR%" install --no-dev --optimize-autoloader --working-dir="%SCSSPHP_DIR%"

if not exist "%SCSSPHP_DIR%\vendor\autoload.php" (
    echo.
    echo composer install ran but vendor\autoload.php still wasn't created - check the message above.
    pause
    endlocal
    exit /b 1
)

echo scssphp installed successfully.

:END
if "%~1" neq "auto" pause
endlocal
