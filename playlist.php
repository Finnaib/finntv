<?php
$server   = $_GET["server"] ?? "";
$username = $_GET["username"] ?? "";
$password = $_GET["password"] ?? "";
$type     = $_GET["type"] ?? "m3u8";

if (!$server || !$username || !$password) {
    die("ERROR: Missing parameters");
}

$server = rtrim($server, "/");
$url = "$server/get.php?username=$username&password=$password&type=$type&output=$type";

// Set stream context with User-Agent and ignore SSL errors
$options = [
    "ssl"=>["verify_peer"=>false,"verify_peer_name"=>false],
    "http"=>["header"=>"User-Agent: Mozilla/5.0\r\n"]
];
$context = stream_context_create($options);

$playlist = @file_get_contents($url,false,$context);

if(!$playlist){
    die("ERROR: Could not fetch playlist. Server may block requests or credentials are wrong.");
}

// Send proper headers for download
header("Content-Type: " . ($type==="m3u8" ? "application/vnd.apple.mpegurl" : "audio/x-mpegurl"));
header("Content-Disposition: attachment; filename=playlist.$type");
header("Content-Length: ".strlen($playlist));

echo $playlist;
exit;