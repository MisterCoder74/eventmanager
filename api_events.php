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
            'type' => $data['type'] ?? '',
            'date' => $date,
            'time' => $data['time'] ?? '',
            'location' => $data['location'] ?? '',
            'address' => $data['address'] ?? '',
            'client_id' => $data['client_id'] ?? '',
            'status' => $data['status'] ?? 'planning',
            'notes' => $data['notes'] ?? '',
            'guest_count' => intval($data['guest_count'] ?? 0),
            'documents' => [],
            'task_ids' => [],
            'supplier_ids' => [],
            'budget_client' => floatval($data['budget_client'] ?? 0),
            'budget_preventivo' => 0,
            'budget_alert_threshold' => 80,
            'servizi' => [],
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
                $event['type'] = $data['type'] ?? $event['type'];
                $event['date'] = $data['date'] ?? $event['date'];
                $event['time'] = $data['time'] ?? $event['time'];
                $event['location'] = $data['location'] ?? $event['location'];
                $event['address'] = $data['address'] ?? $event['address'];
                $event['client_id'] = $data['client_id'] ?? $event['client_id'];
                $event['status'] = $data['status'] ?? $event['status'];
                $event['notes'] = $data['notes'] ?? $event['notes'];
                if (isset($data['guest_count'])) {
                    $event['guest_count'] = intval($data['guest_count']);
                }
                if (isset($data['budget_client'])) {
                    $event['budget_client'] = floatval($data['budget_client']);
                }
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

        // Delete associated tasks
        $tasksData = readJson(__DIR__ . '/tasks.json') ?? ['tasks' => []];
        $tasksData['tasks'] = array_values(array_filter($tasksData['tasks'], function($task) use ($id) {
            return $task['event_id'] !== $id;
        }));
        writeJson(__DIR__ . '/tasks.json', $tasksData);

        // Delete old budget items (deprecated)
        if (file_exists(__DIR__ . '/budget.json')) {
            $budgetData = readJson(__DIR__ . '/budget.json') ?? ['items' => []];
            $budgetData['items'] = array_values(array_filter($budgetData['items'], function($item) use ($id) {
                return $item['event_id'] !== $id;
            }));
            writeJson(__DIR__ . '/budget.json', $budgetData);
        }

        echo json_encode(['success' => true, 'data' => true]);
        break;

    case 'add_service':
        addServiceToEvent();
        break;

    case 'update_service':
        updateEventService();
        break;

    case 'remove_service':
        removeEventService();
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Azione non valida']);
        break;
}

/**
 * Aggiunge un servizio all'evento
 */
function addServiceToEvent() {
    $data = getRequestData();
    $event_id = $data['event_id'] ?? '';
    $service_id = $data['service_id'] ?? '';
    $supplier_id = $data['supplier_id'] ?? '';

    if (!$event_id || !$service_id || !$supplier_id) {
        echo json_encode(['success' => false, 'error' => 'Dati non completi']);
        return;
    }

    // Recupera dettagli servizio
    $servicesData = readJson(__DIR__ . '/services.json') ?? ['services' => []];
    $service_data = null;
    foreach ($servicesData['services'] as $s) {
        if ($s['id'] === $service_id && $s['active']) {
            $service_data = $s;
            break;
        }
    }

    if (!$service_data) {
        echo json_encode(['success' => false, 'error' => 'Servizio non trovato']);
        return;
    }

    // Aggiungi servizio all'evento
    $eventsFile = __DIR__ . '/events.json';
    $eventsData = readJson($eventsFile) ?? ['events' => []];

    foreach ($eventsData['events'] as &$event) {
        if ($event['id'] === $event_id) {
            if (!isset($event['servizi'])) {
                $event['servizi'] = [];
            }

            // Genera ID servizio evento
            $es_id = 'ES' . str_pad(count($event['servizi']) + 1, 3, '0', STR_PAD_LEFT);

            $event_service = [
                'id' => $es_id,
                'service_id' => $service_id,
                'supplier_id' => $supplier_id,
                'name' => $service_data['name'],
                'price' => floatval($service_data['price']),
                'status' => 'pending',
                'notes' => $data['notes'] ?? '',
                'added_at' => date('c')
            ];

            $event['servizi'][] = $event_service;

            // Ricalcola budget preventivo
            $event['budget_preventivo'] = calculateEventBudget($event);
            $event['updated_at'] = date('c');

            writeJson($eventsFile, $eventsData);
            echo json_encode(['success' => true, 'data' => $event_service]);
            return;
        }
    }

    echo json_encode(['success' => false, 'error' => 'Evento non trovato']);
}

/**
 * Aggiorna un servizio nell'evento
 */
function updateEventService() {
    $data = getRequestData();
    $event_id = $data['event_id'] ?? '';
    $service_id = $data['service_id'] ?? '';
    $status = $data['status'] ?? 'pending';
    $notes = $data['notes'] ?? '';
    $price = isset($data['price']) ? floatval($data['price']) : null;

    if (!$event_id || !$service_id) {
        echo json_encode(['success' => false, 'error' => 'Dati non completi']);
        return;
    }

    $eventsFile = __DIR__ . '/events.json';
    $eventsData = readJson($eventsFile) ?? ['events' => []];

    foreach ($eventsData['events'] as &$event) {
        if ($event['id'] === $event_id) {
            if (!isset($event['servizi'])) {
                echo json_encode(['success' => false, 'error' => 'Nessun servizio trovato']);
                return;
            }

            foreach ($event['servizi'] as &$es) {
                if ($es['id'] === $service_id) {
                    $es['status'] = $status;
                    $es['notes'] = $notes;
                    if ($price !== null) {
                        $es['price'] = $price;
                    }
                    break;
                }
            }

            // Ricalcola budget preventivo
            $event['budget_preventivo'] = calculateEventBudget($event);
            $event['updated_at'] = date('c');

            writeJson($eventsFile, $eventsData);
            echo json_encode(['success' => true]);
            return;
        }
    }

    echo json_encode(['success' => false, 'error' => 'Evento non trovato']);
}

/**
 * Rimuove un servizio dall'evento
 */
function removeEventService() {
    $data = getRequestData();
    $event_id = $data['event_id'] ?? '';
    $service_id = $data['service_id'] ?? '';

    if (!$event_id || !$service_id) {
        echo json_encode(['success' => false, 'error' => 'Dati non completi']);
        return;
    }

    $eventsFile = __DIR__ . '/events.json';
    $eventsData = readJson($eventsFile) ?? ['events' => []];

    foreach ($eventsData['events'] as &$event) {
        if ($event['id'] === $event_id) {
            if (!isset($event['servizi'])) {
                echo json_encode(['success' => false, 'error' => 'Nessun servizio trovato']);
                return;
            }

            foreach ($event['servizi'] as $key => $es) {
                if ($es['id'] === $service_id) {
                    unset($event['servizi'][$key]);
                    $event['servizi'] = array_values($event['servizi']);
                    break;
                }
            }

            // Ricalcola budget preventivo
            $event['budget_preventivo'] = calculateEventBudget($event);
            $event['updated_at'] = date('c');

            writeJson($eventsFile, $eventsData);
            echo json_encode(['success' => true]);
            return;
        }
    }

    echo json_encode(['success' => false, 'error' => 'Evento non trovato']);
}

/**
 * Calcola il budget preventivo dell'evento
 */
function calculateEventBudget($event) {
    $total = 0;
    if (!empty($event['servizi'])) {
        foreach ($event['servizi'] as $servizio) {
            if (in_array($servizio['status'] ?? 'pending', ['confirmed', 'paid'])) {
                $total += floatval($servizio['price'] ?? 0);
            }
        }
    }
    return $total;
}
?>
