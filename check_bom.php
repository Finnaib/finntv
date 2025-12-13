<?php
$files = [
    'api/player_api.php',
    'core/player_api.php',
    'config.php',
    'tools/reproduce_issue.php'
];

foreach ($files as $f) {
    if (!file_exists($f))
        continue;
    $content = file_get_contents($f);
    $bom = substr($content, 0, 3);
    if ($bom === "\xEF\xBB\xBF") {
        echo "BOM FOUND in $f\n";
    } else {
        echo "No BOM in $f\n";
    }

    // Check for whitespace before <?php
    if (strpos($content, '<?php') !== 0) {
        // Double check BOM didn't mess up index
        if ($bom !== "\xEF\xBB\xBF") {
            echo "Whitespace/Content before <?php in $f: " . bin2hex(substr($content, 0, 5)) . "\n";
        }
    }
}
