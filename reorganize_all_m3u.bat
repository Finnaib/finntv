@echo off
chcp 65001 >nul
setlocal enabledelayedexpansion

echo ========================================
echo M3U Batch Reorganizer and Categorizer
echo ========================================
echo.
echo Processing all M3U files except live, vod, and series...
echo.

set "M3U_DIR=m3u"
set "SCRIPT_FILE=reorganize_script.ps1"

if not exist "%M3U_DIR%" (
    echo Error: Directory "%M3U_DIR%" not found!
    exit /b 1
)

if not exist "%SCRIPT_FILE%" (
    echo Error: PowerShell script "%SCRIPT_FILE%" not found!
    exit /b 1
)

:: Process each M3U file
for %%F in ("%M3U_DIR%\*.m3u") do (
    set "filename=%%~nF"
    
    :: Skip live.m3u, vod.m3u, and series.m3u
    if /i not "!filename!"=="live" (
        if /i not "!filename!"=="vod" (
            if /i not "!filename!"=="series" (
                echo Processing: %%~nxF
                powershell -NoProfile -ExecutionPolicy Bypass -File "%SCRIPT_FILE%" -InputFile "%%F"
                if !errorlevel! neq 0 (
                    echo   Error processing %%~nxF
                )
                echo.
            )
        )
    )
)

echo ========================================
echo All files processed successfully!
echo ========================================
