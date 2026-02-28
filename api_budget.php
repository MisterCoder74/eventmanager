<?php
require_once __DIR__ . '/auth.php';
requireAuth();
require_once __DIR__ . '/jsonManager.php';

header('Content-Type: application/json');

function getBudgetRequestData() {
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

switch ($action) {
    case 'update_client_budget':
        updateClientBudget();
        break;

    case 'get':
        getEventBudget();
        break;

    case 'update_threshold':
        updateBudgetThreshold();
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Azione non valida']);
        break;
}

/**
 * Aggiorna il budget totale del cliente per un evento
 */
function updateClientBudget() {
    $data = getBudgetRequestData();
    $event_id = $data['event_id'] ?? '';
    $budget_client = floatval($data['budget_client'] ?? 0);

    if (!$event_id) {
        echo json_encode(['success' => false, 'error' => 'Evento non valido']);
        return;
    }

    $eventsFile = __DIR__ . '/events.json';
    $eventsData = readJson($eventsFile) ?? ['events' => [], 'last_id' => 0];

    foreach ($eventsData['events'] as &$event) {
        if ($event['id'] === $event_id) {
            $event['budget_client'] = $budget_client;
            $event['updated_at'] = date('c');
            writeJson($eventsFile, $eventsData);
            echo json_encode(['success' => true, 'budget_client' => $budget_client]);
            return;
        }
    }

    echo json_encode(['success' => false, 'error' => 'Evento non trovato']);
}

/**
 * Recupera il budget completo di un evento
 */
function getEventBudget() {
    $event_id = sanitizeInput($_GET['event_id'] ?? '');

    if (!$event_id) {
        echo json_encode(['success' => false, 'error' => 'ID evento mancante']);
        return;
    }

    $eventsFile = __DIR__ . '/events.json';
    $eventsData = readJson($eventsFile) ?? ['events' => []];

    foreach ($eventsData['events'] as $event) {
        if ($event['id'] === $event_id) {
            // Calcola preventivo da servizi confermati/pagati
            $budget_preventivo = 0;
            if (!empty($event['servizi'])) {
                foreach ($event['servizi'] as $servizio) {
                    if (in_array($servizio['status'] ?? 'pending', ['confirmed', 'paid'])) {
                        $budget_preventivo += floatval($servizio['price'] ?? 0);
                    }
                }
            }

            // Calcola percentuale
            $budget_client = floatval($event['budget_client'] ?? 0);
            $percentage = $budget_client > 0 ? ($budget_preventivo / $budget_client) * 100 : 0;

            // Determina livello alert
            $alert_level = 'green';
            $threshold = intval($event['budget_alert_threshold'] ?? 80);

            if ($percentage >= 95) {
                $alert_level = 'red';
            } elseif ($percentage >= $threshold) {
                $alert_level = 'yellow';
            } elseif ($percentage >= 80) {
                $alert_level = 'orange';
            }

            echo json_encode([
                'success' => true,
                'data' => [
                    'budget_client' => $budget_client,
                    'budget_preventivo' => $budget_preventivo,
                    'percentage' => round($percentage, 2),
                    'alert_level' => $alert_level,
                    'threshold' => $threshold,
                    'remaining' => max(0, $budget_client - $budget_preventivo),
                    'over_budget' => $budget_preventivo > $budget_client
                ]
            ]);
            return;
        }
    }

    echo json_encode(['success' => false, 'error' => 'Evento non trovato']);
}

/**
 * Aggiorna la soglia di alert per il budget
 */
function updateBudgetThreshold() {
    $data = getBudgetRequestData();
    $event_id = $data['event_id'] ?? '';
    $threshold = intval($data['threshold'] ?? 80);

    if (!$event_id || $threshold < 50 || $threshold > 100) {
        echo json_encode(['success' => false, 'error' => 'Parametri non validi']);
        return;
    }

    $eventsFile = __DIR__ . '/events.json';
    $eventsData = readJson($eventsFile) ?? ['events' => [], 'last_id' => 0];

    foreach ($eventsData['events'] as &$event) {
        if ($event['id'] === $event_id) {
            $event['budget_alert_threshold'] = $threshold;
            $event['updated_at'] = date('c');
            writeJson($eventsFile, $eventsData);
            echo json_encode(['success' => true, 'threshold' => $threshold]);
            return;
        }
    }

    echo json_encode(['success' => false, 'error' => 'Evento non trovato']);
}
?>
