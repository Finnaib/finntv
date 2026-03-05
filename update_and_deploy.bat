@echo off
echo ==============================================
echo   FinnTV - Auto Update and Deploy Tool
echo ==============================================
echo.

echo [Step 0] Reorganizing M3U Files by Categories...
call reorganize_all_m3u.bat
echo.

echo [1/3] Syncing with Provider (M3U Import)...
python import_xtream.py
if %errorlevel% neq 0 (
    echo Error: Failed to fetch data from provider!
    pause
    exit /b %errorlevel%
)
echo.

echo [2/3] Building Optimized Data Cache ^& ID Map...
python build_data.py
if %errorlevel% neq 0 (
    echo Error: Failed to build data cache!
    pause
    exit /b %errorlevel%
)

echo.
echo Playlists updated and categorized successfully!
echo.

echo ==============================================
set /p deploy="Do you want to commit and deploy to GitHub/Vercel? (Y/N): "
if /i "%deploy%" neq "Y" (
    echo.
    echo Deployment skipped. Changes are ready but not committed.
    pause
    exit /b 0
)

echo.
echo [3/3] Committing and pushing to GitHub...
echo.

REM Get current date and time for commit message
for /f "tokens=2-4 delims=/ " %%a in ('date /t') do (set mydate=%%c-%%a-%%b)
for /f "tokens=1-2 delims=/:" %%a in ('time /t') do (set mytime=%%a:%%b)

REM Add all changes
git add -A
if %errorlevel% neq 0 (
    echo Error: Failed to stage changes!
    pause
    exit /b %errorlevel%
)

REM Commit with timestamp
git commit -m "Update channels and playlists - %mydate% %mytime%"
if %errorlevel% neq 0 (
    echo Warning: No changes to commit or commit failed.
    echo Continuing anyway...
)

REM Push to GitHub
git push origin main
if %errorlevel% neq 0 (
    echo Error: Failed to push to GitHub!
    echo Please check your internet connection and Git credentials.
    pause
    exit /b %errorlevel%
)

echo.
echo ==============================================
echo   SUCCESS! Changes pushed to GitHub.
echo   Vercel will automatically deploy in 1-2 minutes.
echo ==============================================
echo.
echo You can check deployment status at:
echo https://vercel.com/dashboard
echo.
pause
