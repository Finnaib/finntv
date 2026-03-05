<?php
/**
 * Admin Actions API for FinnTV
 */

require_once __DIR__ . '/../config.php';
header('Content-Type: application/json');

$admin_user = 'shoaibwwe01@gmail.com';
$admin_pass = 'Fatima786@';

$auth_user = $_GET['auth_user'] ?? $_POST['auth_user'] ?? '';
$auth_pass = $_GET['auth_pass'] ?? $_POST['auth_pass'] ?? '';

if ($auth_user !== $admin_user || $auth_pass !== $admin_pass) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized admin access']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'create_user') {
    $u = $_POST['username'] ?? '';
    $p = $_POST['password'] ?? '';
    $m = (int) ($_POST['max_connections'] ?? 5);
    $e = $_POST['exp_date'] ?? ''; // YYYY-MM-DD

    if (empty($u) || empty($p)) {
        echo json_encode(['error' => 'Username and password required']);
        exit;
    }

    $res = UserMgr::saveUser($u, [
        'password' => $p,
        'max_connections' => $m,
        'exp_date' => $e ? strtotime($e) : strtotime('+1 year')
    ]);

    if ($res) {
        echo json_encode(['success' => true, 'message' => "User $u created successfully"]);
    } else {
        echo json_encode(['error' => 'Failed to save user']);
    }
} elseif ($action === 'delete_user') {
    $u = $_POST['username'] ?? '';
    if (empty($u))
        exit;

    $users = UserMgr::loadUsers();
    if (isset($users[$u])) {
        unset($users[$u]);
        file_put_contents(__DIR__ . '/../data/users.json', json_encode($users, JSON_PRETTY_PRINT));
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'User not found in persistent store']);
    }
} else {
    echo json_encode(['error' => 'Unknown action']);
}
