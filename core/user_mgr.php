<?php
/**
 * User and Session Manager for FinnTV
 */

class UserMgr
{
    private static $sessions_file = __DIR__ . '/../data/sessions.json';
    private static $users_file = __DIR__ . '/../data/users.json';

    public static function loadUsers()
    {
        $users = [];
        if (file_exists(self::$users_file)) {
            $users = json_decode(file_get_contents(self::$users_file), true) ?: [];
        }
        return $users;
    }

    public static function saveUser($username, $data)
    {
        $users = self::loadUsers();
        $users[$username] = array_merge([
            'password' => '',
            'max_connections' => 5,
            'created_at' => time(),
            'exp_date' => strtotime('+1 year'),
            'status' => 'Active'
        ], $data);
        return file_put_contents(self::$users_file, json_encode($users, JSON_PRETTY_PRINT));
    }

    public static function getActiveConnections($username)
    {
        $sessions = self::getSessions();
        if (!isset($sessions[$username]))
            return 0;

        // Clean up expired sessions (older than 60 seconds)
        $now = time();
        $activeCount = 0;
        foreach ($sessions[$username] as $id => $timestamp) {
            if ($now - $timestamp < 60) {
                $activeCount++;
            }
        }
        return $activeCount;
    }

    public static function registerSession($username, $ip)
    {
        $sessions = self::getSessions();
        if (!isset($sessions[$username])) {
            $sessions[$username] = [];
        }

        $session_id = $ip . '_' . substr(md5($ip . $username), 0, 8);
        $sessions[$username][$session_id] = time();

        file_put_contents(self::$sessions_file, json_encode($sessions));
    }

    private static function getSessions()
    {
        if (!file_exists(self::$sessions_file))
            return [];
        return json_decode(file_get_contents(self::$sessions_file), true) ?: [];
    }
}
