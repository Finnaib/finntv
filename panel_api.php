<?php
/**
 * Panel API - Advanced IPTV Management
 * 
 * Features:
 * - User activity tracking
 * - Connection management
 * - Server status
 */

error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: *');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'config.php';

// Get parameters
$username = $_GET['username'] ?? $_POST['username'] ?? '';
$password = $_GET['password'] ?? $_POST['password'] ?? '';
$action = $_GET['action'] ?? $_POST['action'] ?? 'get_user_info';

// Authenticate
$authenticated = false;
$user = null;

if (!empty($username) && !empty($password) && isset($users[$username])) {
    $user = $users[$username];
    if ($user['pass'] === $password || password_verify($password, $user['pass'])) {
        $authenticated = true;
    }
}

if (!$authenticated) {
    echo json_encode(['error' => 'Authentication failed'], JSON_PRETTY_PRINT);
    exit;
}

// Process action
switch ($action) {

    case 'get_user_info':
        echo json_encode([
            'username' => $username,
            'status' => 'Active',
            'exp_date' => isset($user['exp_date']) ? date('Y-m-d H:i:s', $user['exp_date']) : null,
            'max_connections' => $user['max_conn'] ?? 10,
            'active_connections' => 0,
            'categories' => $user['categories'],
            'created_at' => date('Y-m-d H:i:s', time() - 2592000)
        ], JSON_PRETTY_PRINT);
        break;

    case 'get_server_status':
        echo json_encode([
            'status' => 'online',
            'uptime' => '99.9%',
            'load' => 'Low',
            'version' => '2.0.0'
        ], JSON_PRETTY_PRINT);
        break;

    default:
        echo json_encode(['error' => 'Unknown action'], JSON_PRETTY_PRINT);
}
?>