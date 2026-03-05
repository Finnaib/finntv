<?php
/**
 * FinnTV Xtream Server V2 - Stream Redirector (SIMPLIFIED)
 */

$uri = $_SERVER['REQUEST_URI'];
$parts = explode('/', trim(parse_url($uri, PHP_URL_PATH), '/'));

// Detect ID and Type
$type = $_GET['type'] ?? "live";
$id_part = end($parts);
$user_part = $parts[1] ?? 'unknown';

$leaf = explode('?', $id_part)[0];
$leaf = explode('.', $leaf)[0];
$id = 0;
if (preg_match('/^(\d+)/', $leaf, $matches)) {
    $id = $matches[1];
}

$map_key = $type . "_" . $id;

// --- Log Activity (Playing Now) ---
$act_file = __DIR__ . '/../data/activity.json';
if (!is_writable(__DIR__ . '/../data'))
    $act_file = sys_get_temp_dir() . '/finntv_activity.json';

$log = [];
if (file_exists($act_file))
    $log = json_decode(file_get_contents($act_file), true) ?: [];
array_unshift($log, ['user' => $user_part, 'stream_id' => $id, 'type' => $type, 'time' => time()]);
$log = array_slice($log, 0, 20);
@file_put_contents($act_file, json_encode($log));

// Load Map
$map_file = __DIR__ . '/../data/id_map.json';
$target_url = "";
if (file_exists($map_file)) {
    $id_map = json_decode(file_get_contents($map_file), true);
    if (isset($id_map[$map_key])) {
        $target_url = $id_map[$map_key];
    } elseif (isset($id_map[$id])) {
        $target_url = $id_map[$id];
    }
}

if (!$target_url) {
    http_response_code(404);
    die("Stream not found.");
}

// Redirect
header("Location: " . $target_url);
exit;