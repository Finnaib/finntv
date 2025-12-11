<?php
// CLI script to print stream ID -> name mapping
require_once __DIR__ . '/config.php';

function pad($s, $n) { return str_pad($s, $n); }

echo "Stream ID    | Category          | Name\n";
echo str_repeat('-', 80) . "\n";
foreach ($channels as $ch) {
    $id = $ch['id'];
    $cat = $categories[$ch['category']]['category_name'] ?? $ch['category'];
    $name = $ch['name'];
    echo pad($id, 12) . ' | ' . pad($cat, 17) . ' | ' . $name . "\n";
}

?>
