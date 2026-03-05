<?php
/**
 * Quick API Debugger
 */
$_GET['username'] = 'test';
$_GET['password'] = 'test';
require_once 'core/player_api.php';
// The player_api.php will call json_out() which exits.
