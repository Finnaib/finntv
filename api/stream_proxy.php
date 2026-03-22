<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") exit;
if (!isset($_GET["url"])) { http_response_code(400); die("No URL"); }
$raw = str_replace(" ", "+", $_GET["url"]);
$url = base64_decode($raw);
if (!$url || filter_var($url, FILTER_VALIDATE_URL) === false) { http_response_code(400); die("Invalid URL"); }
function resolve_url($b, $r) {
    if (parse_url($r, PHP_URL_SCHEME) != "") return $r;
    if ($r[0] == "#" || $r[0] == "?") return $b.$r;
    $p = parse_url($b);
    $h = $p["scheme"] . "://" . (isset($p["host"]) ? $p["host"] : "") . (isset($p["port"]) ? ":" . $p["port"] : "");
    $pa = isset($p["path"]) ? $p["path"] : "/";
    if ($r[0] == "/") return $h . $r;
    $pa = dirname($pa);
    if ($pa == "/" || $pa == "\\") $pa = "";
    $a = $h . $pa . "/" . $r;
    $re = ["#(/\.?/)#", "#/(?!\.\.)[^/]+/\.\./#"];
    for($n=1; $n>0; $a=preg_replace($re, "/", $a, -1, $n)) {}
    return $a;
}
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_USERAGENT, "VLC/3.0.16 LibVLC/3.0.16");
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$res = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
$furl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
curl_close($ch);
if ($code !== 200) { http_response_code(502); die("Error $code"); }
if ($type) header("Content-Type: $type");
$m = (strpos(strtolower($type), "mpegurl") !== false || strpos(trim($res), "#EXTM3U") === 0);
if ($m) {
    $lines = explode("\n", $res);
    $out = [];
    $host = "https://" . $_SERVER["HTTP_HOST"] . "/api/stream_proxy.php?url=";
    foreach ($lines as $l) {
        $l = trim($l);
        if (empty($l)) continue;
        if ($l[0] === "#") {
            if (strpos($l, "URI=\"") !== false && preg_match("/URI=\"([^\"]+)\"/", $l, $ma)) {
                $abs = resolve_url($furl, $ma[1]);
                $l = str_replace($ma[1], $host . urlencode(base64_encode($abs)), $l);
            }
            $out[] = $l;
        } else {
            $abs = resolve_url($furl, $l);
            $out[] = $host . urlencode(base64_encode($abs));
        }
    }
    echo implode("\n", $out);
} else {
    header("Cache-Control: public, max-age=86400");
    echo $res;
}
?>
