<?php
require_once __DIR__ . '/auth.php';
requireAuth();
require_once __DIR__ . '/jsonManager.php';

header('Content-Type: application/json');

function getTaskRequestData() {
    $input = file_get_contents('php://input');
    $data = [];
    if (!empty($input)) {
        $decoded = json_decode($input, true);
        if (is_array($decoded)) {
            $data = $decoded;
        }
    }
    if (!empty($_POST)) {
        $data = array_merge($data, $_POST);
    }
    return sanitizeInput($data);
}

$action = sanitizeInput($_GET['action'] ?? ($_POST['action'] ?? 'list'));
$tasksFile = __DIR__ . '/tasks.json';
$tasksData = readJson($tasksFile) ?? ['tasks' => [], 'last_id' => 0];

switch ($action) {
    case 'list':
        $eventId = sanitizeInput($_GET['event_id'] ?? '');
        $tasks = $tasksData['tasks'];
        if ($eventId) {
            $tasks = array_values(array_filter($tasks, function($task) use ($eventId) {
                return $task['event_id'] === $eventId;
            }));
        }
        echo json_encode(['success' => true, 'data' => $tasks]);
        break;
    case 'create':
        $data = getTaskRequestData();
        $title = $data['title'] ?? '';
        $dueDate = $data['due_date'] ?? '';
        $eventId = $data['event_id'] ?? '';

        if (!$title || !$dueDate || !$eventId) {
            echo json_encode(['success' => false, 'error' => 'Dati task incompleti']);
            exit;
        }

        $tasksData['last_id'] = ($tasksData['last_id'] ?? 0) + 1;
        $id = 'T' . str_pad((string)$tasksData['last_id'], 3, '0', STR_PAD_LEFT);

        $task = [
            'id' => $id,
            'event_id' => $eventId,
            'title' => $title,
            'due_date' => $dueDate,
            'status' => $data['status'] ?? 'pending',
            'notes' => $data['notes'] ?? '',
            'created_at' => date('c'),
            'updated_at' => date('c')
        ];

        $tasksData['tasks'][] = $task;
        writeJson($tasksFile, $tasksData);
        echo json_encode(['success' => true, 'data' => $task]);
        break;
    case 'update':
        $data = getTaskRequestData();
        $id = $data['id'] ?? '';
        if (!$id) {
            echo json_encode(['success' => false, 'error' => 'ID mancante']);
            exit;
        }

        foreach ($tasksData['tasks'] as &$task) {
            if ($task['id'] === $id) {
                $task['title'] = $data['title'] ?? $task['title'];
                $task['due_date'] = $data['due_date'] ?? $task['due_date'];
                $task['status'] = $data['status'] ?? $task['status'];
                $task['notes'] = $data['notes'] ?? $task['notes'];
                $task['updated_at'] = date('c');
                writeJson($tasksFile, $tasksData);
                echo json_encode(['success' => true, 'data' => $task]);
                exit;
            }
        }
        echo json_encode(['success' => false, 'error' => 'Task non trovato']);
        break;
    case 'delete':
        $data = getTaskRequestData();
        $id = $data['id'] ?? '';
        if (!$id) {
            echo json_encode(['success' => false, 'error' => 'ID mancante']);
            exit;
        }

        $tasksData['tasks'] = array_values(array_filter($tasksData['tasks'], function($task) use ($id) {
            return $task['id'] !== $id;
        }));
        writeJson($tasksFile, $tasksData);
        echo json_encode(['success' => true, 'data' => true]);
        break;
    default:
        echo json_encode(['success' => false, 'error' => 'Azione non valida']);
        break;
}
?>
