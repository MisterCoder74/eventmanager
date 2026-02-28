<?php
/**
 * File locking and JSON management functions
 * All file paths use __DIR__ for folder-agnostic behavior
 */

/**
 * Read JSON file with file locking
 */
function readJson($filepath) {
    return withLock($filepath, function() use ($filepath) {
        if (!file_exists($filepath)) {
            return null;
        }
        $content = file_get_contents($filepath);
        return json_decode($content, true);
    });
}

/**
 * Write JSON file with file locking
 */
function writeJson($filepath, $data) {
    return withLock($filepath, function() use ($filepath, $data) {
        return file_put_contents($filepath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) !== false;
    });
}

/**
 * Acquire exclusive lock on file
 */
function acquireLock($filepath, $timeout = 5) {
    $lockfile = $filepath . '.lock';
    $start = microtime(true);
    $handle = fopen($lockfile, 'w');

    if (!$handle) {
        return false;
    }

    while (microtime(true) - $start < $timeout) {
        if (flock($handle, LOCK_EX | LOCK_NB)) {
            return $handle;
        }
        usleep(50000); // 50ms retry
    }

    fclose($handle);
    return false;
}

/**
 * Release file lock
 */
function releaseLock($lockHandle) {
    if ($lockHandle) {
        flock($lockHandle, LOCK_UN);
        fclose($lockHandle);
    }
}

/**
 * Execute callback with automatic file locking
 */
function withLock($filepath, callable $callback, $timeout = 5) {
    $lock = acquireLock($filepath, $timeout);

    if ($lock === false) {
        throw new Exception("Could not acquire lock on file: " . basename($filepath));
    }

    try {
        $result = $callback();
        return $result;
    } finally {
        releaseLock($lock);
    }
}

/**
 * Sanitize input to prevent XSS
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email address
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate phone number
 */
function validatePhone($phone) {
    return preg_match('/^[0-9\+\s\-]{8,20}$/', $phone);
}

/**
 * Validate date (YYYY-MM-DD)
 */
function validateDate($date) {
    return (bool)DateTime::createFromFormat('Y-m-d', $date);
}
?>
