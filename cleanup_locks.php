<?php
require_once __DIR__ . '/auth.php';
requireAuth();

header('Content-Type: text/plain; charset=UTF-8');

echo "Pulizia file di lock...\n";

$lockFiles = glob(__DIR__ . '/*.lock');

if (empty($lockFiles)) {
    echo "Nessun file di lock trovato.\n";
} else {
    foreach ($lockFiles as $lockFile) {
        if (unlink($lockFile)) {
            echo "✓ Rimosso: " . basename($lockFile) . "\n";
        } else {
            echo "✗ Errore rimozione: " . basename($lockFile) . "\n";
        }
    }
    echo "Totale file rimossi: " . count($lockFiles) . "\n";
}

echo "\nPulizia backup vecchi...\n";
$backupFiles = glob(__DIR__ . '/*.backup.*');
$deleted = 0;

foreach ($backupFiles as $backupFile) {
    if (preg_match('/\.backup\.(\d+)$/', $backupFile, $matches)) {
        $timestamp = intval($matches[1]);
        $age = time() - $timestamp;

        if ($age > 604800) {
            if (unlink($backupFile)) {
                echo "✓ Rimosso backup vecchio: " . basename($backupFile) . "\n";
                $deleted++;
            }
        }
    }
}

if ($deleted === 0) {
    echo "Nessun backup vecchio trovato.\n";
} else {
    echo "Totale backup rimossi: " . $deleted . "\n";
}

echo "\nPulizia completata!\n";
?>
