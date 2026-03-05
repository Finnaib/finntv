<?php
$host = "http://mhav1.com:80";
$user = "ht2990";
$pass = "742579548";

$url = "$host/player_api.php?username=$user&password=$pass";
$res = file_get_contents($url);
echo "Status: " . ($res ? "OK" : "FAIL") . "\n";
echo "Response: " . $res . "\n";
