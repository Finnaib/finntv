<?php
/**
 * Xtream Panel API
 * Standardized to match Xtream Codes API v2 specifications
 * Compatible with all Xtream Panel clients
 */

error_reporting(0);
ini_set('display_errors', 0);

// Load config
require_once 'config.php';

// CORS headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: *');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Get parameters
$username = $_GET['username'] ?? $_POST['username'] ?? '';
$password = $_GET['password'] ?? $_POST['password'] ?? '';

// Build server URL
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$serverUrl = $server_config['base_url'] ?? ($protocol . '://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/'));

// Authenticate
$authenticated = false;
$user = null;

if (!empty($username) && !empty($password) && isset($users[$username])) {
    $user = $users[$username];
    if ($user['pass'] === $password || password_verify($password, $user['pass'])) {
        $authenticated = true;
    }
}

// Failed Authentication Response
if (!$authenticated) {
    echo json_encode([
        'user_info' => [
            'username' => $username,
            'password' => $password,
            'message' => 'Authentication failed',
            'auth' => 0,
            'status' => 'Disabled',
            'exp_date' => null,
            'is_trial' => '0',
            'active_cons' => '0',
            'created_at' => null,
            'max_connections' => '0',
            'allowed_output_formats' => []
        ],
        'server_info' => [
            'url' => $serverUrl,
            'port' => '80',
            'https_port' => '443',
            'server_protocol' => $protocol,
            'rtmp_port' => '1935',
            'timezone' => 'UTC',
            'timestamp_now' => time(),
            'time_now' => date('Y-m-d H:i:s')
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}

// Successful Authentication Response (Standard Xtream Format)
$expired = (isset($user['exp_date']) && time() > $user['exp_date']);

echo json_encode([
    'user_info' => [
        'username' => $username,
        'password' => $password,
        'message' => $expired ? 'Your account has expired' : 'Logged in successfully',
        'auth' => $expired ? 0 : 1,
        'status' => $expired ? 'Expired' : 'Active',
        'exp_date' => isset($user['exp_date']) ? (string) $user['exp_date'] : null,
        'is_trial' => '0',
        'active_cons' => '0',
        'created_at' => (string) (time() - 2592000), // Mock creation date
        'max_connections' => (string) ($user['max_conn'] ?? 10),
        'allowed_output_formats' => ['m3u8', 'ts', 'rtmp']
    ],
    'server_info' => [
        'xui' => true,
        'version' => '2.0.0',
        'revision' => 5,
        'url' => $serverUrl,
        'port' => '80',
        'https_port' => '443',
        'server_protocol' => $protocol,
        'rtmp_port' => '1935',
        'timezone' => 'UTC',
        'timestamp_now' => time(),
        'time_now' => date('Y-m-d H:i:s'),
        'process' => true
    ]
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
?>