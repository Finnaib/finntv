<?php
/**
 * Password Hash Generator
 * INSTRUCTIONS:
 * 1. Upload this file to /xtream_server/
 * 2. Visit: http://finntv.atwebpages.com/xtream_server/generate_hashes.php
 * 3. Copy all the hashes shown (WITH SINGLE QUOTES)
 * 4. DELETE THIS FILE immediately after copying
 */

echo "<!DOCTYPE html>\n<html>\n<head>\n";
echo "<style>
body { font-family: monospace; padding: 20px; background: #1e1e1e; color: #0f0; }
.hash { background: #000; padding: 15px; margin: 10px 0; border: 2px solid #0f0; }
.warning { color: #f00; font-size: 20px; animation: blink 1s infinite; }
@keyframes blink { 50% { opacity: 0; } }
.important { color: #fa0; font-size: 18px; background: #330; padding: 10px; margin: 10px 0; }
code { color: #0ff; font-size: 14px; }
</style>\n";
echo "</head>\n<body>\n";
echo "<h1>🔒 Password Hash Generator</h1>\n";
echo "<p class='warning'>⚠️⚠️⚠️ DELETE THIS FILE AFTER USE! ⚠️⚠️⚠️</p>\n";
echo "<div class='important'>⚡ IMPORTANT: Use SINGLE QUOTES (') not double quotes (\") around hashes!</div>\n";
echo "<hr>\n";

$passwords = [
    'finn' => 'finn123',
    'tabby' => 'tabby123',
    'fatima' => 'fatima123',
    'devz' => 'devz123'
];

echo "<h2>📋 Copy These Lines to config.php:</h2>\n";
echo "<p style='color:#fa0;'>Copy EXACTLY as shown (including single quotes):</p>\n";

foreach ($passwords as $username => $password) {
    $hash = password_hash($password, PASSWORD_BCRYPT);
    echo "<div class='hash'>\n";
    echo "<h3>User: $username</h3>\n";
    echo "<p>Original password: <strong>$password</strong></p>\n";
    echo "<p style='color:#fa0;'>Copy this ENTIRE line (with single quotes):</p>\n";
    echo "<code>'pass' => '$hash',</code>\n";
    echo "</div>\n";
}

echo "<hr>\n";
echo "<h2 style='color:#f00;'>⚠️ NEXT STEPS:</h2>\n";
echo "<ol style='color:#fa0;'>\n";
echo "<li>Copy all the hash lines above (WITH SINGLE QUOTES!)</li>\n";
echo "<li>In config.php, find: 'pass' => 'PASTE_HASH_HERE',</li>\n";
echo "<li>Replace PASTE_HASH_HERE with the hash (keep single quotes)</li>\n";
echo "<li>Example: 'pass' => '\$2y\$10\$abc123...',</li>\n";
echo "<li>DELETE THIS FILE (generate_hashes.php) FROM SERVER</li>\n";
echo "<li>Test login at player_api.php</li>\n";
echo "</ol>\n";

echo "<hr>\n";
echo "<h2>✅ Example of correct format in config.php:</h2>\n";
echo "<pre style='background:#000;padding:15px;color:#0f0;'>\n";
echo '"finn" => [' . "\n";
$exampleHash = password_hash('finn123', PASSWORD_BCRYPT);
echo "    'pass' => '$exampleHash',\n";
echo "    \"categories\" => [\"india\", \"asia\", \"usa\", \"egypt\", \"sport\"],\n";
echo "    \"max_conn\" => 10,\n";
echo "    \"exp_date\" => strtotime('+1 year')\n";
echo "],\n";
echo "</pre>\n";

echo "</body></html>";
?>