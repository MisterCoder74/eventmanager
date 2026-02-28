<?php
/**
 * JSON Management Functions (NO LOCKING)
 * Simplified version without file locking
 */

/**
 * Read JSON file
 */
function readJson($filepath) {
    if (!file_exists($filepath)) {
        return null;
    }

    $content = file_get_contents($filepath);
    if ($content === false) {
        return null;
    }

    $data = json_decode($content, true);
    return $data;
}

/**
 * Write JSON file (atomic operation with LOCK_EX)
 * LOCK_EX ensures atomic write without blocking lock
 */
function writeJson($filepath, $data) {
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        return false;
    }

    $result = file_put_contents($filepath, $json, LOCK_EX);
    return $result !== false;
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

/**
 * Backup JSON file before modification
 */
function backupJsonFile($filepath) {
    if (!file_exists($filepath)) {
        return false;
    }

    $backupPath = $filepath . '.backup.' . time();
    return copy($filepath, $backupPath);
}

/**
 * Write JSON with automatic backup
 */
function writeJsonWithBackup($filepath, $data) {
    if (file_exists($filepath)) {
        backupJsonFile($filepath);
    }

    return writeJson($filepath, $data);
}

/**
 * Validate JSON file integrity
 */
function validateJson($filepath) {
    if (!file_exists($filepath)) {
        return false;
    }

    $content = file_get_contents($filepath);
    $decoded = json_decode($content, true);

    return json_last_error() === JSON_ERROR_NONE;
}

/**
 * Restore from latest backup
 */
function restoreFromBackup($filepath) {
    $backupFiles = glob($filepath . '.backup.*');

    if (empty($backupFiles)) {
        return false;
    }

    rsort($backupFiles);
    $latestBackup = $backupFiles[0];

    return copy($latestBackup, $filepath);
}
?>
