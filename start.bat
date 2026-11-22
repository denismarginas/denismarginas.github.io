@echo off
setlocal

set "SCRIPT_DIR=%~dp0"
set "FAVICON=%SCRIPT_DIR%dev\platform\assets\img\favicon\favicon.ico"
set "START_EXE=%SCRIPT_DIR%start.exe"
set "BUILD_EXE_BAT=%SCRIPT_DIR%build-start-exe.bat"

set "NEEDS_BUILD="
if exist "%FAVICON%" (
    if not exist "%START_EXE%" (
        set "NEEDS_BUILD=1"
    ) else (
        for /f %%N in ('powershell -NoProfile -Command "if ((Get-Item -LiteralPath '%FAVICON%').LastWriteTime -gt (Get-Item -LiteralPath '%START_EXE%').LastWriteTime) { 'yes' } else { 'no' }" 2^>nul') do if "%%N"=="yes" set "NEEDS_BUILD=1"
    )
)

if defined NEEDS_BUILD if exist "%BUILD_EXE_BAT%" call "%BUILD_EXE_BAT%" auto

call "%SCRIPT_DIR%dev\platform\start-platform.bat"
endlocal
