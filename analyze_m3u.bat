@echo off
chcp 65001 >nul
setlocal enabledelayedexpansion

echo ========================================
echo M3U File Analyzer and Categorizer
echo ========================================
echo.

set "INPUT_FILE=m3u\indonesia.m3u"
set "OUTPUT_DIR=output"

if not exist "%INPUT_FILE%" (
    echo Error: Input file "%INPUT_FILE%" not found!
    pause
    exit /b 1
)

:: Create output directory
if not exist "%OUTPUT_DIR%" mkdir "%OUTPUT_DIR%"

echo Analyzing M3U file: %INPUT_FILE%
echo.

:: Initialize counters
set /a total_entries=0
set /a movies_count=0
set /a indonesia_count=0
set /a sports_count=0
set /a kids_count=0
set /a news_count=0
set /a knowledge_count=0
set /a lifestyle_count=0
set /a others_count=0

:: Create category files
set "MOVIES_FILE=%OUTPUT_DIR%\movies.m3u"
set "INDONESIA_FILE=%OUTPUT_DIR%\indonesia.m3u"
set "SPORTS_FILE=%OUTPUT_DIR%\sports.m3u"
set "KIDS_FILE=%OUTPUT_DIR%\kids.m3u"
set "NEWS_FILE=%OUTPUT_DIR%\news.m3u"
set "KNOWLEDGE_FILE=%OUTPUT_DIR%\knowledge.m3u"
set "LIFESTYLE_FILE=%OUTPUT_DIR%\lifestyle.m3u"
set "OTHERS_FILE=%OUTPUT_DIR%\others.m3u"

:: Write M3U headers
echo #EXTM3U > "%MOVIES_FILE%"
echo #EXTM3U > "%INDONESIA_FILE%"
echo #EXTM3U > "%SPORTS_FILE%"
echo #EXTM3U > "%KIDS_FILE%"
echo #EXTM3U > "%NEWS_FILE%"
echo #EXTM3U > "%KNOWLEDGE_FILE%"
echo #EXTM3U > "%LIFESTYLE_FILE%"
echo #EXTM3U > "%OTHERS_FILE%"

:: Use PowerShell to process the M3U file
powershell -NoProfile -ExecutionPolicy Bypass -Command ^
"$InputFile = '%INPUT_FILE%'; ^
$OutputDir = '%OUTPUT_DIR%'; ^
$lines = Get-Content -Path $InputFile -Encoding UTF8; ^
$currentExtinf = ''; ^
$movieCount = 0; ^
$indoCount = 0; ^
$sportsCount = 0; ^
$kidsCount = 0; ^
$newsCount = 0; ^
$knowledgeCount = 0; ^
$lifestyleCount = 0; ^
$othersCount = 0; ^
$totalCount = 0; ^
foreach ($line in $lines) { ^
    if ($line -match '^#EXTINF:') { ^
        $currentExtinf = $line; ^
        $totalCount++; ^
    } elseif ($line -match '^[^#]' -and $line.Trim() -ne '' -and $currentExtinf -ne '') { ^
        $category = 'Others'; ^
        if ($currentExtinf -match 'group-title=\"([^\"]+)\"') { ^
            $groupTitle = $matches[1]; ^
            if ($groupTitle -match 'Movie|Cinema|HBO|Film') { ^
                $category = 'Movies'; ^
                $movieCount++; ^
                Add-Content -Path \"$OutputDir\movies.m3u\" -Value $currentExtinf -Encoding UTF8; ^
                Add-Content -Path \"$OutputDir\movies.m3u\" -Value $line -Encoding UTF8; ^
            } elseif ($groupTitle -match 'INDONESIA|Indo|Nasional') { ^
                $category = 'Indonesia'; ^
                $indoCount++; ^
                Add-Content -Path \"$OutputDir\indonesia.m3u\" -Value $currentExtinf -Encoding UTF8; ^
                Add-Content -Path \"$OutputDir\indonesia.m3u\" -Value $line -Encoding UTF8; ^
            } elseif ($groupTitle -match 'Sport|Soccer|Football|NBA|Tennis') { ^
                $category = 'Sports'; ^
                $sportsCount++; ^
                Add-Content -Path \"$OutputDir\sports.m3u\" -Value $currentExtinf -Encoding UTF8; ^
                Add-Content -Path \"$OutputDir\sports.m3u\" -Value $line -Encoding UTF8; ^
            } elseif ($groupTitle -match 'Kids|Children|Cartoon') { ^
                $category = 'Kids'; ^
                $kidsCount++; ^
                Add-Content -Path \"$OutputDir\kids.m3u\" -Value $currentExtinf -Encoding UTF8; ^
                Add-Content -Path \"$OutputDir\kids.m3u\" -Value $line -Encoding UTF8; ^
            } elseif ($groupTitle -match 'News') { ^
                $category = 'News'; ^
                $newsCount++; ^
                Add-Content -Path \"$OutputDir\news.m3u\" -Value $currentExtinf -Encoding UTF8; ^
                Add-Content -Path \"$OutputDir\news.m3u\" -Value $line -Encoding UTF8; ^
            } elseif ($groupTitle -match 'KNOWLEDGE|DOC|Discovery|National|Geographic') { ^
                $category = 'Knowledge'; ^
                $knowledgeCount++; ^
                Add-Content -Path \"$OutputDir\knowledge.m3u\" -Value $currentExtinf -Encoding UTF8; ^
                Add-Content -Path \"$OutputDir\knowledge.m3u\" -Value $line -Encoding UTF8; ^
            } elseif ($groupTitle -match 'LIFESTYLE|ENT|Entertainment|Fashion') { ^
                $category = 'Lifestyle'; ^
                $lifestyleCount++; ^
                Add-Content -Path \"$OutputDir\lifestyle.m3u\" -Value $currentExtinf -Encoding UTF8; ^
                Add-Content -Path \"$OutputDir\lifestyle.m3u\" -Value $line -Encoding UTF8; ^
            } else { ^
                $othersCount++; ^
                Add-Content -Path \"$OutputDir\others.m3u\" -Value $currentExtinf -Encoding UTF8; ^
                Add-Content -Path \"$OutputDir\others.m3u\" -Value $line -Encoding UTF8; ^
            } ^
        } ^
        $currentExtinf = ''; ^
    } ^
} ^
Write-Host ''; ^
Write-Host 'Analysis Complete!'; ^
Write-Host '=================='; ^
Write-Host \"Total Entries: $totalCount\"; ^
Write-Host \"Movies: $movieCount\"; ^
Write-Host \"Indonesia Channels: $indoCount\"; ^
Write-Host \"Sports: $sportsCount\"; ^
Write-Host \"Kids: $kidsCount\"; ^
Write-Host \"News: $newsCount\"; ^
Write-Host \"Knowledge ^& Documentary: $knowledgeCount\"; ^
Write-Host \"Lifestyle ^& Entertainment: $lifestyleCount\"; ^
Write-Host \"Others: $othersCount\"; ^
Write-Host ''; ^
Write-Host 'Categorized files saved in output folder:'; ^
Write-Host '  - movies.m3u'; ^
Write-Host '  - indonesia.m3u'; ^
Write-Host '  - sports.m3u'; ^
Write-Host '  - kids.m3u'; ^
Write-Host '  - news.m3u'; ^
Write-Host '  - knowledge.m3u'; ^
Write-Host '  - lifestyle.m3u'; ^
Write-Host '  - others.m3u'; ^
"

echo.
echo ========================================
echo Process completed!
echo Check the 'output' folder for results.
echo ========================================
pause
