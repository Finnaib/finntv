@echo off
echo ==============================================
echo      IPTV Server - Auto Update & Deploy
echo ==============================================
echo.

echo [1/2] Fetching latest playlists from external server...
python import_xtream.py
if %errorlevel% neq 0 (
    echo Error: Failed to update playlists!
    pause
    exit /b %errorlevel%
)
echo Playlists updated successfully.
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
