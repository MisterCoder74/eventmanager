<?php
/**
 * Anti-brute force protection
 */

function checkLoginAttempts($username) {
    require_once __DIR__ . '/jsonManager.php';

    $users = readJson(__DIR__ . '/users.json');

    if (!$users) {
        return ['allowed' => true];
    }

    foreach ($users['users'] as &$user) {
        if ($user['username'] === $username) {
            if ($user['locked_until'] && time() < strtotime($user['locked_until'])) {
                return [
                    'allowed' => false,
                    'remaining_time' => strtotime($user['locked_until']) - time()
                ];
            }

            if ($user['locked_until'] && time() >= strtotime($user['locked_until'])) {
                $user['failed_attempts'] = 0;
                $user['locked_until'] = null;
                writeJson(__DIR__ . '/users.json', $users);
            }

            break;
        }
    }

    return ['allowed' => true];
}

function recordFailedAttempt($username) {
    require_once __DIR__ . '/jsonManager.php';

    $users = readJson(__DIR__ . '/users.json');
    $lockout_time = $users['lockout_time'] ?? 300; // 5 min default

    if (!$users) {
        return;
    }

    foreach ($users['users'] as &$user) {
        if ($user['username'] === $username) {
            $user['failed_attempts'] = ($user['failed_attempts'] ?? 0) + 1;

            if ($user['failed_attempts'] >= 5) {
                $user['locked_until'] = date('c', time() + $lockout_time);
            }
            break;
        }
    }

    writeJson(__DIR__ . '/users.json', $users);
}

function resetFailedAttempts($username) {
    require_once __DIR__ . '/jsonManager.php';

    $users = readJson(__DIR__ . '/users.json');

    if (!$users) {
        return;
    }

    foreach ($users['users'] as &$user) {
        if ($user['username'] === $username) {
            $user['failed_attempts'] = 0;
            $user['locked_until'] = null;
            $user['last_login'] = date('c');
            break;
        }
    }

    writeJson(__DIR__ . '/users.json', $users);
}
?>
