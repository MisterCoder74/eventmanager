<?php
require_once __DIR__ . '/auth.php';
requireAuth();
require_once __DIR__ . '/jsonManager.php';

header('Content-Type: application/json');

function getRequestData() {
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
$eventsFile = __DIR__ . '/events.json';
$eventsData = readJson($eventsFile) ?? ['events' => [], 'last_id' => 0];

switch ($action) {
    case 'list':
        echo json_encode(['success' => true, 'data' => $eventsData['events']]);
        break;
    case 'get':
        $id = sanitizeInput($_GET['id'] ?? '');
        foreach ($eventsData['events'] as $event) {
            if ($event['id'] === $id) {
                echo json_encode(['success' => true, 'data' => $event]);
                exit;
            }
        }
        echo json_encode(['success' => false, 'error' => 'Evento non trovato']);
        break;
    case 'create':
        $data = getRequestData();
        $title = $data['title'] ?? '';
        $date = $data['date'] ?? '';

        if (!$title || !$date) {
            echo json_encode(['success' => false, 'error' => 'Titolo e data obbligatori']);
            exit;
        }

        $eventsData['last_id'] = ($eventsData['last_id'] ?? 0) + 1;
        $id = 'E' . str_pad((string)$eventsData['last_id'], 3, '0', STR_PAD_LEFT);

        $event = [
            'id' => $id,
            'title' => $title,
            'date' => $date,
            'location' => $data['location'] ?? '',
            'client_id' => $data['client_id'] ?? '',
            'status' => $data['status'] ?? 'planning',
            'notes' => $data['notes'] ?? '',
            'documents' => [],
            'created_at' => date('c'),
            'updated_at' => date('c')
        ];

        $eventsData['events'][] = $event;
        writeJson($eventsFile, $eventsData);

        echo json_encode(['success' => true, 'data' => $event]);
        break;
    case 'update':
        $data = getRequestData();
        $id = $data['id'] ?? '';
        if (!$id) {
            echo json_encode(['success' => false, 'error' => 'ID mancante']);
            exit;
        }

        foreach ($eventsData['events'] as &$event) {
            if ($event['id'] === $id) {
                $event['title'] = $data['title'] ?? $event['title'];
                $event['date'] = $data['date'] ?? $event['date'];
                $event['location'] = $data['location'] ?? $event['location'];
                $event['client_id'] = $data['client_id'] ?? $event['client_id'];
                $event['status'] = $data['status'] ?? $event['status'];
                $event['notes'] = $data['notes'] ?? $event['notes'];
                $event['updated_at'] = date('c');
                writeJson($eventsFile, $eventsData);
                echo json_encode(['success' => true, 'data' => $event]);
                exit;
            }
        }
        echo json_encode(['success' => false, 'error' => 'Evento non trovato']);
        break;
    case 'delete':
        $data = getRequestData();
        $id = $data['id'] ?? '';
        if (!$id) {
            echo json_encode(['success' => false, 'error' => 'ID mancante']);
            exit;
        }
        $eventsData['events'] = array_values(array_filter($eventsData['events'], function($event) use ($id) {
            return $event['id'] !== $id;
        }));
        writeJson($eventsFile, $eventsData);

        $tasksData = readJson(__DIR__ . '/tasks.json') ?? ['tasks' => []];
        $tasksData['tasks'] = array_values(array_filter($tasksData['tasks'], function($task) use ($id) {
            return $task['event_id'] !== $id;
        }));
        writeJson(__DIR__ . '/tasks.json', $tasksData);

        $budgetData = readJson(__DIR__ . '/budget.json') ?? ['items' => []];
        $budgetData['items'] = array_values(array_filter($budgetData['items'], function($item) use ($id) {
            return $item['event_id'] !== $id;
        }));
        writeJson(__DIR__ . '/budget.json', $budgetData);

        echo json_encode(['success' => true, 'data' => true]);
        break;
    default:
        echo json_encode(['success' => false, 'error' => 'Azione non valida']);
        break;
}
?>
