@echo off
chcp 65001 >nul
setlocal enabledelayedexpansion

echo ========================================
echo M3U File Reorganizer and Categorizer
echo ========================================
echo.

set "INPUT_FILE=m3u\indonesia.m3u"
set "BACKUP_FILE=m3u\indonesia.m3u.backup"

if not exist "%INPUT_FILE%" (
    echo Error: Input file "%INPUT_FILE%" not found!
    pause
    exit /b 1
)

echo Creating backup: %BACKUP_FILE%
copy "%INPUT_FILE%" "%BACKUP_FILE%" >nul

echo.
echo Analyzing and reorganizing M3U file...
echo.

:: Use PowerShell to reorganize the M3U file
powershell -NoProfile -ExecutionPolicy Bypass -Command ^
"$InputFile = '%INPUT_FILE%'; ^
$lines = Get-Content -Path $InputFile -Encoding UTF8; ^
$entries = @{ ^
    'Movies' = @(); ^
    'Indonesia' = @(); ^
    'Sports' = @(); ^
    'Kids' = @(); ^
    'News' = @(); ^
    'Knowledge' = @(); ^
    'Lifestyle' = @(); ^
    'Others' = @() ^
}; ^
$currentExtinf = ''; ^
$stats = @{ ^
    'Movies' = 0; ^
    'Indonesia' = 0; ^
    'Sports' = 0; ^
    'Kids' = 0; ^
    'News' = 0; ^
    'Knowledge' = 0; ^
    'Lifestyle' = 0; ^
    'Others' = 0 ^
}; ^
foreach ($line in $lines) { ^
    if ($line -match '^#EXTM3U') { ^
        continue; ^
    } ^
    if ($line -match '^#EXTINF:') { ^
        $currentExtinf = $line; ^
    } elseif ($line -match '^[^#]' -and $line.Trim() -ne '' -and $currentExtinf -ne '') { ^
        $category = 'Others'; ^
        if ($currentExtinf -match 'group-title=\"([^\"]+)\"') { ^
            $groupTitle = $matches[1]; ^
            if ($groupTitle -match 'Movie|Cinema|HBO|Film|Cinemax') { ^
                $category = 'Movies'; ^
            } elseif ($groupTitle -match 'INDONESIA|Indo') { ^
                $category = 'Indonesia'; ^
            } elseif ($groupTitle -match 'Sport|Soccer|Football|NBA|Tennis|beIN|MMA|Boxing') { ^
                $category = 'Sports'; ^
            } elseif ($groupTitle -match 'Kids|Children|Cartoon') { ^
                $category = 'Kids'; ^
            } elseif ($groupTitle -match 'News') { ^
                $category = 'News'; ^
            } elseif ($groupTitle -match 'KNOWLEDGE|DOC|Discovery|National|Geographic|History|Animal') { ^
                $category = 'Knowledge'; ^
            } elseif ($groupTitle -match 'LIFESTYLE|ENT|Entertainment|Fashion') { ^
                $category = 'Lifestyle'; ^
            } ^
        } ^
        $entries[$category] += @($currentExtinf, $line); ^
        $stats[$category]++; ^
        $currentExtinf = ''; ^
    } ^
} ^
$output = @('#EXTM3U'); ^
$categoryOrder = @('Indonesia', 'Movies', 'Sports', 'Kids', 'News', 'Knowledge', 'Lifestyle', 'Others'); ^
foreach ($cat in $categoryOrder) { ^
    if ($entries[$cat].Count -gt 0) { ^
        $output += ''; ^
        $output += \"#EXTINF:-1 tvg-logo=\\\"\\\" group-title=\\\"--- $cat ---\\\",--- $cat ---\"; ^
        $output += '#'; ^
        $output += $entries[$cat]; ^
    } ^
} ^
Set-Content -Path $InputFile -Value $output -Encoding UTF8; ^
Write-Host 'Reorganization Complete!'; ^
Write-Host '========================'; ^
Write-Host ''; ^
Write-Host 'Category Statistics:'; ^
Write-Host \"  Indonesia Channels: $($stats['Indonesia'])\"; ^
Write-Host \"  Movies: $($stats['Movies'])\"; ^
Write-Host \"  Sports: $($stats['Sports'])\"; ^
Write-Host \"  Kids: $($stats['Kids'])\"; ^
Write-Host \"  News: $($stats['News'])\"; ^
Write-Host \"  Knowledge ^& Documentary: $($stats['Knowledge'])\"; ^
Write-Host \"  Lifestyle ^& Entertainment: $($stats['Lifestyle'])\"; ^
Write-Host \"  Others: $($stats['Others'])\"; ^
Write-Host ''; ^
Write-Host \"Total Channels: $(($stats.Values | Measure-Object -Sum).Sum)\"; ^
Write-Host ''; ^
Write-Host 'File has been reorganized and saved as: %INPUT_FILE%'; ^
Write-Host 'Original file backed up as: %BACKUP_FILE%'; ^
"

echo.
echo ========================================
echo Process completed!
echo Your M3U file has been reorganized.
echo ========================================
echo.
pause
