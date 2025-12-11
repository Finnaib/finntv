<?php
require_once 'config.php';

header('Content-Type: application/json');

// Accept credentials from multiple sources
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

if (empty($username) || empty($password)) {
    echo json_encode(['ok' => false, 'message' => 'Missing username or password']);
    exit;
}

// Reuse same auth logic (support bcrypt and plain-text)
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

echo json_encode(['ok' => true, 'message' => 'Auth OK', 'username' => $username, 'max_conn' => $user['max_conn']]);
exit;

?>
