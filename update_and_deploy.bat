@echo off
echo ==============================================
echo   FinnTV - Deploy to GitHub
echo ==============================================
echo.

echo Checking for changes...
git status
echo.

set /p deploy="Commit and push all changes to GitHub? (Y/N): "
if /i "%deploy%" neq "Y" (
    echo.
    echo Cancelled.
    pause
    exit /b 0
)

echo.
echo Staging all changes...
git add -A

echo.
echo Committing...
for /f "tokens=2-4 delims=/ " %%a in ('date /t') do (set mydate=%%c-%%a-%%b)
for /f "tokens=1-2 delims=/: " %%a in ('time /t') do (set mytime=%%a:%%b)
git commit -m "Update channels and playlists - %mydate% %mytime%"

echo.
echo Pushing to GitHub...
git push origin main
if %errorlevel% neq 0 (
    echo.
    echo ERROR: Push failed! Check internet connection.
    pause
    exit /b 1
)

echo.
echo ==============================================
echo   SUCCESS! Pushed to GitHub.
echo   Vercel will deploy automatically in ~60s.
echo ==============================================
echo.
pause
