@echo off
setlocal EnableDelayedExpansion

set "SCRIPT_DIR=%~dp0"
for %%I in ("%SCRIPT_DIR%..\..\..") do set "ROOT_DIR=%%~fI"
set "WORKFLOW=%ROOT_DIR%\src\content\json\data\settings\data_settings_workflow.json"
set "PROVIDER=ollama"
set "MODEL=translategemma:4b"
set "OLLAMA_URL=http://127.0.0.1:11434"

if exist "%WORKFLOW%" (
    for /f "usebackq tokens=1,* delims==" %%A in (`powershell -NoProfile -Command "$t = (Get-Content -Raw -LiteralPath '%WORKFLOW%' | ConvertFrom-Json).translation; if ($t) { if ($t.provider) { 'PROVIDER=' + $t.provider }; if ($t.model) { 'MODEL=' + $t.model }; if ($t.ollama_url) { 'OLLAMA_URL=' + $t.ollama_url } }" 2^>nul`) do set "%%A=%%B"
)

if /i not "%PROVIDER%"=="ollama" (
    echo Translation provider is "%PROVIDER%", Ollama is not needed.
    goto END
)

call :FindOllama
if not defined OLLAMA_EXE (
    echo Ollama was not found. Installing it for the local translations...
    where winget >nul 2>nul
    if not errorlevel 1 (
        winget install -e --id Ollama.Ollama --source winget --silent --accept-package-agreements --accept-source-agreements
    )
    call :FindOllama
)

if not defined OLLAMA_EXE (
    echo winget is not available or failed, downloading the Ollama installer from ollama.com...
    set "OLLAMA_SETUP=%TEMP%\OllamaSetup.exe"
    powershell -NoProfile -Command "[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12; try { Invoke-WebRequest -Uri 'https://ollama.com/download/OllamaSetup.exe' -OutFile '!OLLAMA_SETUP!' -UseBasicParsing } catch { exit 1 }"
    if exist "!OLLAMA_SETUP!" (
        "!OLLAMA_SETUP!" /VERYSILENT /SUPPRESSMSGBOXES /NORESTART
        del "!OLLAMA_SETUP!" >nul 2>nul
    )
    call :FindOllama
)

if not defined OLLAMA_EXE (
    echo.
    echo Ollama could not be installed automatically. Install it from https://ollama.com/download
    echo The platform still works, only Generate - Translations needs Ollama.
    goto END
)

call :CheckServer
if not defined OLLAMA_UP (
    echo Starting Ollama...
    start "Ollama" /min "%OLLAMA_EXE%" serve
    for /l %%N in (1,1,20) do (
        if not defined OLLAMA_UP (
            timeout /t 1 /nobreak >nul
            call :CheckServer
        )
    )
)

if not defined OLLAMA_UP (
    echo Ollama is installed but did not start at %OLLAMA_URL%. Open the Ollama app, then use Generate - Translations.
    goto END
)

set "HAS_MODEL="
for /f "skip=1 tokens=1" %%M in ('call "%OLLAMA_EXE%" list 2^>nul') do (
    if /i "%%M"=="%MODEL%" set "HAS_MODEL=1"
    if /i "%%M"=="%MODEL%:latest" set "HAS_MODEL=1"
)

if defined HAS_MODEL (
    echo Ollama is running with the translation model %MODEL%.
) else (
    echo Downloading the translation model %MODEL% in a separate window, this happens only once...
    start "Ollama - downloading %MODEL%" "%OLLAMA_EXE%" pull %MODEL%
)

:END
if "%~1" neq "auto" pause
endlocal
exit /b 0

:FindOllama
set "OLLAMA_EXE="
for /f "delims=" %%P in ('where ollama 2^>nul') do if not defined OLLAMA_EXE set "OLLAMA_EXE=%%P"
if not defined OLLAMA_EXE if exist "%LOCALAPPDATA%\Programs\Ollama\ollama.exe" set "OLLAMA_EXE=%LOCALAPPDATA%\Programs\Ollama\ollama.exe"
if not defined OLLAMA_EXE if exist "%ProgramFiles%\Ollama\ollama.exe" set "OLLAMA_EXE=%ProgramFiles%\Ollama\ollama.exe"
exit /b 0

:CheckServer
set "OLLAMA_UP="
powershell -NoProfile -Command "try { Invoke-WebRequest -Uri '%OLLAMA_URL%/api/version' -UseBasicParsing -TimeoutSec 2 | Out-Null; exit 0 } catch { exit 1 }" >nul 2>nul
if not errorlevel 1 set "OLLAMA_UP=1"
exit /b 0
