<?php
$_SERVER['REQUEST_URI'] = '/live/shoaibwwe01@gmail.com/Fatima786@/6394.ts';
$_GET['type'] = 'live';

$uri = $_SERVER['REQUEST_URI'];
$parts = explode('/', trim(parse_url($uri, PHP_URL_PATH), '/'));
echo "Parts: " . print_r($parts, true) . "\n";

$id_part = end($parts);
$user_part = $parts[1] ?? 'unknown';

$leaf = explode('?', $id_part)[0];
$leaf = explode('.', $leaf)[0];
$id = 0;
if (preg_match('/^(\d+)/', $leaf, $matches)) {
    $id = $matches[1];
}

echo "ID: $id, User: $user_part\n";
