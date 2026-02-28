<?php
require_once __DIR__ . '/auth.php';
requireAuth();
require_once __DIR__ . '/jsonManager.php';

header('Content-Type: application/json');

function getClientRequestData() {
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
$clientsFile = __DIR__ . '/clients.json';
$clientsData = readJson($clientsFile) ?? ['clients' => [], 'last_id' => 0];

switch ($action) {
    case 'list':
        echo json_encode(['success' => true, 'data' => $clientsData['clients']]);
        break;
    case 'create':
        $data = getClientRequestData();
        $name = $data['name'] ?? '';
        $email = $data['email'] ?? '';

        if (!$name || ($email && !validateEmail($email))) {
            echo json_encode(['success' => false, 'error' => 'Dati cliente non validi']);
            exit;
        }

        $clientsData['last_id'] = ($clientsData['last_id'] ?? 0) + 1;
        $id = 'C' . str_pad((string)$clientsData['last_id'], 3, '0', STR_PAD_LEFT);

        $client = [
            'id' => $id,
            'name' => $name,
            'email' => $email,
            'phone' => $data['phone'] ?? '',
            'company' => $data['company'] ?? '',
            'notes' => $data['notes'] ?? '',
            'created_at' => date('c'),
            'updated_at' => date('c')
        ];

        $clientsData['clients'][] = $client;
        writeJson($clientsFile, $clientsData);

        echo json_encode(['success' => true, 'data' => $client]);
        break;
    case 'update':
        $data = getClientRequestData();
        $id = $data['id'] ?? '';
        if (!$id) {
            echo json_encode(['success' => false, 'error' => 'ID mancante']);
            exit;
        }

        foreach ($clientsData['clients'] as &$client) {
            if ($client['id'] === $id) {
                $client['name'] = $data['name'] ?? $client['name'];
                $client['email'] = $data['email'] ?? $client['email'];
                $client['phone'] = $data['phone'] ?? $client['phone'];
                $client['company'] = $data['company'] ?? $client['company'];
                $client['notes'] = $data['notes'] ?? $client['notes'];
                $client['updated_at'] = date('c');
                writeJson($clientsFile, $clientsData);
                echo json_encode(['success' => true, 'data' => $client]);
                exit;
            }
        }
        echo json_encode(['success' => false, 'error' => 'Cliente non trovato']);
        break;
    case 'delete':
        $data = getClientRequestData();
        $id = $data['id'] ?? '';
        if (!$id) {
            echo json_encode(['success' => false, 'error' => 'ID mancante']);
            exit;
        }

        $clientsData['clients'] = array_values(array_filter($clientsData['clients'], function($client) use ($id) {
            return $client['id'] !== $id;
        }));
        writeJson($clientsFile, $clientsData);
        echo json_encode(['success' => true, 'data' => true]);
        break;
    default:
        echo json_encode(['success' => false, 'error' => 'Azione non valida']);
        break;
}
?>
