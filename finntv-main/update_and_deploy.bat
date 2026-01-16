@echo off
echo ==============================================
echo      IPTV Server - Auto Update Tool
echo ==============================================
echo.

echo [1/2] Fetching latest playlists & categories...
python import_xtream.py
if %errorlevel% neq 0 (
    echo Error: Failed to update playlists!
    pause
    exit /b %errorlevel%
)
)
echo.

echo [1.5/3] Organizing VOD and Series playlists...
python m3u/organize_playlists.py

echo.
echo [2/3] Building data cache (Optimizing for Vercel)...
php build_data.php
if %errorlevel% neq 0 (
    echo Error: Failed to build data cache!
    pause
    exit /b %errorlevel%
)

echo.
echo Playlists updated and categorized successfully!
echo.

echo ==============================================
set /p deploy="Do you want to deploy these changes to Vercel now? (Y/N): "
if /i "%deploy%" neq "Y" (
    echo.
    echo Deployment skipped. You can deploy later using 'vercel --prod'.
    pause
    exit /b 0
)

echo.
echo [2/2] Deploying to Vercel (Production)...
cmd /c vercel --prod
if %errorlevel% neq 0 (
    echo Error: Deployment failed!
    pause
    exit /b %errorlevel%
)

echo.
echo ==============================================
echo      SUCCESS! Server updated and deployed.
echo ==============================================
echo.
pause
