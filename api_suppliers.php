<?php
require_once __DIR__ . '/auth.php';
requireAuth();
require_once __DIR__ . '/jsonManager.php';

header('Content-Type: application/json');

function getSupplierRequestData() {
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
$suppliersFile = __DIR__ . '/suppliers.json';
$suppliersData = readJson($suppliersFile) ?? ['suppliers' => [], 'last_id' => 0];

switch ($action) {
    case 'list':
        echo json_encode(['success' => true, 'data' => $suppliersData['suppliers']]);
        break;

    case 'create':
        $data = getSupplierRequestData();
        $name = $data['name'] ?? '';
        $email = $data['email'] ?? '';
        $type = $data['type'] ?? '';

        if (!$name || ($email && !validateEmail($email))) {
            echo json_encode(['success' => false, 'error' => 'Dati fornitore non validi']);
            exit;
        }

        $suppliersData['last_id'] = ($suppliersData['last_id'] ?? 0) + 1;
        $id = 'S' . str_pad((string)$suppliersData['last_id'], 3, '0', STR_PAD_LEFT);

        $supplier = [
            'id' => $id,
            'name' => $name,
            'type' => $type,
            'email' => $email,
            'phone' => $data['phone'] ?? '',
            'address' => $data['address'] ?? '',
            'contact_person' => $data['contact_person'] ?? '',
            'website' => $data['website'] ?? '',
            'notes' => $data['notes'] ?? '',
            'service_categories' => [],
            'created_at' => date('c'),
            'updated_at' => date('c')
        ];

        $suppliersData['suppliers'][] = $supplier;
        writeJson($suppliersFile, $suppliersData);

        echo json_encode(['success' => true, 'data' => $supplier]);
        break;

    case 'update':
        $data = getSupplierRequestData();
        $id = $data['id'] ?? '';
        if (!$id) {
            echo json_encode(['success' => false, 'error' => 'ID mancante']);
            exit;
        }

        foreach ($suppliersData['suppliers'] as &$supplier) {
            if ($supplier['id'] === $id) {
                $supplier['name'] = $data['name'] ?? $supplier['name'];
                $supplier['type'] = $data['type'] ?? $supplier['type'];
                $supplier['email'] = $data['email'] ?? $supplier['email'];
                $supplier['phone'] = $data['phone'] ?? $supplier['phone'];
                $supplier['address'] = $data['address'] ?? $supplier['address'];
                $supplier['contact_person'] = $data['contact_person'] ?? $supplier['contact_person'];
                $supplier['website'] = $data['website'] ?? $supplier['website'];
                $supplier['notes'] = $data['notes'] ?? $supplier['notes'];
                if (isset($data['service_categories'])) {
                    $supplier['service_categories'] = is_array($data['service_categories'])
                        ? $data['service_categories']
                        : [];
                }
                $supplier['updated_at'] = date('c');
                writeJson($suppliersFile, $suppliersData);
                echo json_encode(['success' => true, 'data' => $supplier]);
                exit;
            }
        }
        echo json_encode(['success' => false, 'error' => 'Fornitore non trovato']);
        break;

    case 'delete':
        $data = getSupplierRequestData();
        $id = $data['id'] ?? '';
        if (!$id) {
            echo json_encode(['success' => false, 'error' => 'ID mancante']);
            exit;
        }

        $suppliersData['suppliers'] = array_values(array_filter($suppliersData['suppliers'], function($supplier) use ($id) {
            return $supplier['id'] !== $id;
        }));
        writeJson($suppliersFile, $suppliersData);
        echo json_encode(['success' => true, 'data' => true]);
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Azione non valida']);
        break;
}
?>
