param(
    [string]$InputFile
)

$lines = Get-Content -Path $InputFile -Encoding UTF8 -Raw
$lines = $lines -split "`r`n|`n"

# Extract the header (x-tvg-url line)
$header = $lines[0]

# Parse entries
$categoriesMap = @{}
$currentExtinf = ''
$totalChannels = 0

foreach ($line in $lines[1..($lines.Length - 1)]) {
    if ($line -match '^#EXTINF:') {
        $currentExtinf = $line
    }
    elseif ($line -match '^[^#]' -and $line.Trim() -ne '' -and $currentExtinf -ne '') {
        $category = 'Uncategorized'
        
        # Extract the original group-title
        if ($currentExtinf -match 'group-title="([^"]+)"') {
            $category = $matches[1]
        }
        
        # Initialize category if not exists
        if (-not $categoriesMap.ContainsKey($category)) {
            $categoriesMap[$category] = @()
        }
        
        # Add entry to category
        $categoriesMap[$category] += @($currentExtinf, $line)
        $totalChannels++
        $currentExtinf = ''
    }
}

# Build output with category separators
$output = @($header, '')
$sortedCategories = $categoriesMap.Keys | Sort-Object

foreach ($category in $sortedCategories) {
    $count = $categoriesMap[$category].Count / 2
    Write-Host "    $category : $count channels"
    
    # Add category separator
    $output += ''
    $output += '################################################################################'
    $output += "# $category"
    $output += '################################################################################'
    $output += ''
    
    # Add channels
    for ($i = 0; $i -lt $categoriesMap[$category].Count; $i += 2) {
        $output += $categoriesMap[$category][$i]
        $output += $categoriesMap[$category][$i + 1]
        $output += ''
    }
}

Set-Content -Path $InputFile -Value ($output -join "`r`n") -Encoding UTF8 -NoNewline

Write-Host "  Total: $totalChannels channels in $($categoriesMap.Count) categories"
