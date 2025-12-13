<?php
// Mock Request
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTP_HOST'] = 'localhost';
$_GET['username'] = 'finn';
$_GET['password'] = 'finn123';
$_GET['action'] = 'get_live_streams';
// $_GET['category_id'] = '...'; 

// Define __DIR__ relative to where we run it
// We will run this from the root `xtream_server` directory
require_once __DIR__ . '/../api/player_api.php';
