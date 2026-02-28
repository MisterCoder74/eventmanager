<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/jsonManager.php';

requireAuth();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Metodo non permesso']);
    exit;
}

$event_id = sanitizeInput($_POST['event_id'] ?? '');

$events = readJson(__DIR__ . '/events.json');
$event_exists = false;
if ($events && isset($events['events'])) {
    foreach ($events['events'] as $e) {
        if ($e['id'] === $event_id) {
            $event_exists = true;
            break;
        }
    }
}

if (!$event_id || !$event_exists) {
    echo json_encode(['success' => false, 'error' => 'Evento non valido']);
    exit;
}

$upload_dir = __DIR__ . '/events/' . $event_id . '/uploads';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'Nessun file caricato']);
    exit;
}

$file = $_FILES['file'];
$allowed_types = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png'];
$max_size = 10 * 1024 * 1024; // 10MB

$file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

if (!in_array($file_ext, $allowed_types)) {
    echo json_encode(['success' => false, 'error' => 'Tipo file non permesso']);
    exit;
}

if ($file['size'] > $max_size) {
    echo json_encode(['success' => false, 'error' => 'File troppo grande (max 10MB)']);
    exit;
}

$safe_filename = sanitizeInput(pathinfo($file['name'], PATHINFO_FILENAME));
$safe_filename = preg_replace('/[^a-zA-Z0-9\-_]/', '_', $safe_filename);
$filename = $safe_filename . '_' . time() . '.' . $file_ext;

$destination = $upload_dir . '/' . $filename;

if (move_uploaded_file($file['tmp_name'], $destination)) {
    foreach ($events['events'] as &$e) {
        if ($e['id'] === $event_id) {
            if (!isset($e['documents'])) {
                $e['documents'] = [];
            }
            $e['documents'][] = $filename;
            $e['updated_at'] = date('c');
            break;
        }
    }

    writeJson(__DIR__ . '/events.json', $events);

    echo json_encode([
        'success' => true,
        'data' => [
            'filename' => $filename,
            'url' => "events/{$event_id}/uploads/{$filename}"
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Errore durante il caricamento']);
}
?>
