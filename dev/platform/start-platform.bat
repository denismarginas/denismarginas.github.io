@echo off
setlocal

set "SCRIPT_DIR=%~dp0"
for %%I in ("%SCRIPT_DIR%..\..") do set "ROOT_DIR=%%~fI"
set "HOST=portfolio-cms.local"
set "BIND=127.0.0.1"
set "PORT=8088"
set "REQUIRE_DIR=%ROOT_DIR%\dev\platform\require"
set "DEP_DIR=%ROOT_DIR%\dev\dependencies"
set "PHP_VERSION_PREFIX=php-v-8.5"
set "PHP_EXE="

if exist "%REQUIRE_DIR%\install-scssphp.bat" (
    call "%REQUIRE_DIR%\install-scssphp.bat" auto
)

call :FindLocalPhp
if defined PHP_LOCAL set "PHP_EXE=%PHP_LOCAL%"

if not defined PHP_EXE (
    if exist "%REQUIRE_DIR%\install-php.bat" (
        call "%REQUIRE_DIR%\install-php.bat" auto
    )
    call :FindLocalPhp
    if defined PHP_LOCAL set "PHP_EXE=%PHP_LOCAL%"
)

if defined PHP_LOCAL if exist "%REQUIRE_DIR%\enable-php-gd.bat" (
    call "%REQUIRE_DIR%\enable-php-gd.bat" auto
)

if not defined PHP_EXE (
    for /f "delims=" %%P in ('where php 2^>nul') do if not defined PHP_EXE set "PHP_EXE=%%P"
)

if not defined PHP_EXE (
    echo PHP was not found.
    echo Install PHP or let %REQUIRE_DIR%\install-php.bat bootstrap the local dependency.
    pause
    exit /b 1
)

set "OPEN_HOST=%HOST%"
set "HOSTS_IP=%BIND%"
if "%BIND%"=="0.0.0.0" set "HOSTS_IP=127.0.0.1"
if /i not "%HOST%"=="localhost" if not "%HOST%"=="%HOSTS_IP%" if exist "%REQUIRE_DIR%\add-hosts-entry.ps1" (
    powershell -NoProfile -ExecutionPolicy Bypass -File "%REQUIRE_DIR%\add-hosts-entry.ps1" -HostName "%HOST%" -Ip "%HOSTS_IP%"
    if errorlevel 1 (
        echo Could not add %HOST% to the hosts file, opening %HOSTS_IP% instead.
        set "OPEN_HOST=%HOSTS_IP%"
    )
)

netstat -ano | findstr /r /c:"%BIND%:%PORT% .*LISTENING" >nul && echo Port %PORT% is already in use on %BIND%.

start "Platform PHP Server" "%PHP_EXE%" -S %BIND%:%PORT% -t "%ROOT_DIR%"
timeout /t 2 /nobreak >nul
set "URL=http://%OPEN_HOST%"
if not "%PORT%"=="80" set "URL=%URL%:%PORT%"
start "" "%URL%/dev/platform/"

if exist "%REQUIRE_DIR%\install-ollama.bat" (
    call "%REQUIRE_DIR%\install-ollama.bat" auto
)

endlocal
exit /b 0

:FindLocalPhp
set "PHP_LOCAL="
if not exist "%DEP_DIR%" exit /b 0
for /d %%D in ("%DEP_DIR%\%PHP_VERSION_PREFIX%*") do (
    if not defined PHP_LOCAL if exist "%%D\current\php.exe" set "PHP_LOCAL=%%D\current\php.exe"
)
exit /b 0
