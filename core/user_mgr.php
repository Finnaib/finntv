<?php
/**
 * User and Session Manager for FinnTV
 */

class UserMgr
{
    private static function getSessionsFile()
    {
        $dir = __DIR__ . '/../data';
        return is_writable($dir) ? $dir . '/sessions.json' : sys_get_temp_dir() . '/sessions.json';
    }

    private static function getUsersFile()
    {
        $dir = __DIR__ . '/../data';
        return is_writable($dir) ? $dir . '/users.json' : sys_get_temp_dir() . '/users.json';
    }

    public static function loadUsers()
    {
        $users = [];
        $file = self::getUsersFile();
        if (file_exists($file)) {
            $users = json_decode(file_get_contents($file), true) ?: [];
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
        return file_put_contents(self::getUsersFile(), json_encode($users, JSON_PRETTY_PRINT));
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

        @file_put_contents(self::getSessionsFile(), json_encode($sessions));
    }

    private static function getSessions()
    {
        $file = self::getSessionsFile();
        if (!file_exists($file))
            return [];
        return json_decode(file_get_contents($file), true) ?: [];
    }
}
