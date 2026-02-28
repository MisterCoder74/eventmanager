<?php
session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireAuth() {
    if (!isLoggedIn()) {
        header('Location: index.php');
        exit;
    }
}

function getCurrentUser() {
    return $_SESSION['user_id'] ?? null;
}

function login($username, $password) {
    require_once __DIR__ . '/jsonManager.php';
    require_once __DIR__ . '/antiBruteForce.php';

    $username = sanitizeInput($username);
    $password = sanitizeInput($password);

    // Check lockout
    $lockCheck = checkLoginAttempts($username);
    if (!$lockCheck['allowed']) {
        return [
            'success' => false,
            'error' => 'Account temporaneamente bloccato. Riprova tra ' . ceil($lockCheck['remaining_time'] / 60) . ' minuti.'
        ];
    }

    // Check credentials
    $users = readJson(__DIR__ . '/users.json');

    if (!$users || !isset($users['users'])) {
        return ['success' => false, 'error' => 'Errore sistema'];
    }

    foreach ($users['users'] as $user) {
        if ($user['username'] === $username && $user['password'] === $password) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['name'] = $user['name'];

            resetFailedAttempts($username);
            return ['success' => true];
        }
    }

    recordFailedAttempt($username);
    return ['success' => false, 'error' => 'Credenziali non valide'];
}

function logout() {
    session_unset();
    session_destroy();
    header('Location: index.php');
    exit;
}
?>
