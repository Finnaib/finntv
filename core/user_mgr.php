<?php
/**
 * User and Session Manager for FinnTV
 */

class UserMgr
{
    public static function getSessionsFile()
    {
        $dir = __DIR__ . '/../data';
        return is_writable($dir) ? $dir . '/sessions.json' : sys_get_temp_dir() . '/sessions.json';
    }

    public static function getUsersFile()
    {
        $dir = __DIR__ . '/../data';
        return is_writable($dir) ? $dir . '/users.json' : sys_get_temp_dir() . '/users.json';
    }

    public static function loadUsers()
    {
        $data = ['persistent' => [], 'blacklist' => []];
        $file = self::getUsersFile();
        if (file_exists($file)) {
            $data = array_merge($data, json_decode(file_get_contents($file), true) ?: []);
        }
        return $data;
    }

    public static function saveUser($username, $data)
    {
        $all = self::loadUsers();
        $existing = isset($all['persistent'][$username]) ? $all['persistent'][$username] : [];

        $all['persistent'][$username] = array_merge([
            'password' => '',
            'max_connections' => 5,
            'created_at' => time(),
            'exp_date' => strtotime('+1 year'),
            'status' => 'Active'
        ], $existing, $data);

        // Remove from blacklist if being re-added
        $all['blacklist'] = array_diff($all['blacklist'] ?? [], [$username]);

        return file_put_contents(self::getUsersFile(), json_encode($all, JSON_PRETTY_PRINT));
    }

    public static function deleteUser($username)
    {
        $all = self::loadUsers();
        if (isset($all['persistent'][$username])) {
            unset($all['persistent'][$username]);
        } else {
            // It's a static user, add to blacklist
            if (!in_array($username, $all['blacklist'])) {
                $all['blacklist'][] = $username;
            }
        }
        return file_put_contents(self::getUsersFile(), json_encode($all, JSON_PRETTY_PRINT));
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
