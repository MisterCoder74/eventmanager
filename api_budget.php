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
$budgetFile = __DIR__ . '/budget.json';
$budgetData = readJson($budgetFile) ?? ['items' => [], 'last_id' => 0];

switch ($action) {
    case 'list':
        $eventId = sanitizeInput($_GET['event_id'] ?? '');
        $items = $budgetData['items'];
        if ($eventId) {
            $items = array_values(array_filter($items, function($item) use ($eventId) {
                return $item['event_id'] === $eventId;
            }));
        }
        echo json_encode(['success' => true, 'data' => $items]);
        break;
    case 'create':
        $data = getBudgetRequestData();
        $description = $data['description'] ?? '';
        $amount = $data['amount'] ?? '';
        $eventId = $data['event_id'] ?? '';

        if (!$description || !$amount || !$eventId) {
            echo json_encode(['success' => false, 'error' => 'Dati budget incompleti']);
            exit;
        }

        $budgetData['last_id'] = ($budgetData['last_id'] ?? 0) + 1;
        $id = 'B' . str_pad((string)$budgetData['last_id'], 3, '0', STR_PAD_LEFT);

        $item = [
            'id' => $id,
            'event_id' => $eventId,
            'description' => $description,
            'amount' => (float)$amount,
            'type' => $data['type'] ?? 'planned',
            'created_at' => date('c'),
            'updated_at' => date('c')
        ];

        $budgetData['items'][] = $item;
        writeJson($budgetFile, $budgetData);
        echo json_encode(['success' => true, 'data' => $item]);
        break;
    case 'update':
        $data = getBudgetRequestData();
        $id = $data['id'] ?? '';
        if (!$id) {
            echo json_encode(['success' => false, 'error' => 'ID mancante']);
            exit;
        }

        foreach ($budgetData['items'] as &$item) {
            if ($item['id'] === $id) {
                $item['description'] = $data['description'] ?? $item['description'];
                if (isset($data['amount'])) {
                    $item['amount'] = (float)$data['amount'];
                }
                $item['type'] = $data['type'] ?? $item['type'];
                $item['updated_at'] = date('c');
                writeJson($budgetFile, $budgetData);
                echo json_encode(['success' => true, 'data' => $item]);
                exit;
            }
        }
        echo json_encode(['success' => false, 'error' => 'Voce budget non trovata']);
        break;
    case 'delete':
        $data = getBudgetRequestData();
        $id = $data['id'] ?? '';
        if (!$id) {
            echo json_encode(['success' => false, 'error' => 'ID mancante']);
            exit;
        }

        $budgetData['items'] = array_values(array_filter($budgetData['items'], function($item) use ($id) {
            return $item['id'] !== $id;
        }));
        writeJson($budgetFile, $budgetData);
        echo json_encode(['success' => true, 'data' => true]);
        break;
    default:
        echo json_encode(['success' => false, 'error' => 'Azione non valida']);
        break;
}
?>
