<?php
require_once 'config.php';

header('Content-Type: application/json');

$streamId = (int)($_GET['stream'] ?? $_GET['id'] ?? 0);

function getRequestCredentials() {
    $u = '';
    $p = '';
    $sources = array_merge($_GET ?? [], $_POST ?? []);
    foreach (['username','user','u'] as $key) { if (isset($sources[$key]) && $sources[$key] !== '') { $u = $sources[$key]; break; } }
    foreach (['password','pass','p'] as $key) { if (isset($sources[$key]) && $sources[$key] !== '') { $p = $sources[$key]; break; } }
    if (empty($u) && isset($_SERVER['PHP_AUTH_USER'])) $u = $_SERVER['PHP_AUTH_USER'];
    if (empty($p) && isset($_SERVER['PHP_AUTH_PW'])) $p = $_SERVER['PHP_AUTH_PW'];
    if ((empty($u) || empty($p)) && isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $auth = $_SERVER['HTTP_AUTHORIZATION'];
        if (stripos($auth, 'basic ') === 0) {
            $decoded = base64_decode(substr($auth, 6));
            if ($decoded !== false) {
                $parts = explode(':', $decoded, 2);
                if (count($parts) == 2) { if (empty($u)) $u = $parts[0]; if (empty($p)) $p = $parts[1]; }
            }
        }
    }
    if ((empty($u) || empty($p))) {
        $body = @file_get_contents('php://input');
        if ($body) {
            $json = json_decode($body, true);
            if (is_array($json)) { foreach (['username','user','u'] as $key) { if (empty($u) && isset($json[$key])) $u = $json[$key]; } foreach (['password','pass','p'] as $key) { if (empty($p) && isset($json[$key])) $p = $json[$key]; } }
        }
    }
    return [trim((string)$u), trim((string)$p)];
}

list($username, $password) = getRequestCredentials();

if (empty($username) || empty($password) || $streamId == 0) {
    echo json_encode(['ok' => false, 'message' => 'Missing parameters: username,password,stream']);
    exit;
}

function authenticateUserSimple($username, $password, $users) {
    if (!isset($users[$username])) return false;
    $user = $users[$username];
    if (is_string($user['pass']) && password_verify($password, $user['pass'])) return $user;
    if ($password === $user['pass']) return $user;
    return false;
}

$user = authenticateUserSimple($username, $password, $users);
if (!$user) {
    echo json_encode(['ok' => false, 'message' => 'Auth failed']);
    exit;
}

$channel = null;
foreach ($channels as $ch) {
    if ($ch['id'] == $streamId) { $channel = $ch; break; }
}

if (!$channel) {
    echo json_encode(['ok' => false, 'message' => 'Stream not found']);
    exit;
}

// Build server redirect URL
$streamBaseUrl = $server_config['base_url'];
$stream_url = rtrim($streamBaseUrl, '/') . '/live/' . rawurlencode($username) . '/' . rawurlencode($password) . '/' . $channel['id'] . '.m3u8';

echo json_encode([
    'ok' => true,
    'stream_id' => $channel['id'],
    'channel_name' => $channel['name'],
    'direct_source' => $channel['url'],
    'stream_url' => $stream_url
]);
exit;

?>
