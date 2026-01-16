<?php
// Mock Request
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTP_HOST'] = 'localhost:8080';
$_GET['username'] = 'finn';
$_GET['password'] = 'finn123';
$_GET['action'] = '';

// We define __DIR__ relative to where we run it
require_once __DIR__ . '/../api/player_api.php';
