@echo off
setlocal

set "SCRIPT_DIR=%~dp0"
for %%I in ("%SCRIPT_DIR%..\..\..") do set "ROOT_DIR=%%~fI"
set "DEP_DIR=%ROOT_DIR%\dev\dependencies"
set "PHP_VERSION=8.5.10"
set "PHP_DIR=%DEP_DIR%\php-v-%PHP_VERSION%"

if exist "%PHP_DIR%\current\php.exe" (
    echo PHP %PHP_VERSION% is already installed in %PHP_DIR%!
    goto END
)

echo Installing PHP %PHP_VERSION% into %PHP_DIR%...

powershell -NoProfile -ExecutionPolicy Bypass -Command ^
    "$targetDir = '%PHP_DIR%\current';" ^
    "if (-not (Test-Path $targetDir)) { New-Item -ItemType Directory -Force -Path $targetDir | Out-Null };" ^
    "$zipUrl = 'https://windows.php.net/downloads/releases/php-%PHP_VERSION%-Win32-vs17-x64.zip';" ^
    "$zipPath = Join-Path $env:TEMP 'php_temp.zip';" ^
    "[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12;" ^
    "Write-Host 'Downloading PHP from windows.php.net...';" ^
    "Invoke-WebRequest -Uri $zipUrl -OutFile $zipPath;" ^
    "Write-Host 'Extracting files...';" ^
    "Expand-Archive -Path $zipPath -DestinationPath $targetDir -Force;" ^
    "Remove-Item $zipPath -ErrorAction SilentlyContinue;" ^
    "$iniSrc = Join-Path $targetDir 'php.ini-production';" ^
    "$iniDst = Join-Path $targetDir 'php.ini';" ^
    "if ((Test-Path $iniSrc) -and -not (Test-Path $iniDst)) {" ^
    "  Write-Host 'Configuring php.ini (enabling extension_dir + openssl/curl/mbstring/fileinfo/gd/zip)...';" ^
    "  Copy-Item $iniSrc $iniDst;" ^
    "  Add-Content -Path $iniDst -Value '';" ^
    "  Add-Content -Path $iniDst -Value 'extension_dir = ext';" ^
    "  Add-Content -Path $iniDst -Value 'extension=curl';" ^
    "  Add-Content -Path $iniDst -Value 'extension=fileinfo';" ^
    "  Add-Content -Path $iniDst -Value 'extension=gd';" ^
    "  Add-Content -Path $iniDst -Value 'extension=mbstring';" ^
    "  Add-Content -Path $iniDst -Value 'extension=openssl';" ^
    "  Add-Content -Path $iniDst -Value 'extension=zip';" ^
    "}"

:END
if "%~1" neq "auto" pause
endlocal