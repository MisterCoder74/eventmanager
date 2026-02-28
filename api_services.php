<?php
require_once __DIR__ . '/auth.php';
requireAuth();
require_once __DIR__ . '/jsonManager.php';

header('Content-Type: application/json');

function getServiceRequestData() {
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
$servicesFile = __DIR__ . '/services.json';
$servicesData = readJson($servicesFile) ?? ['services' => [], 'last_id' => 0];

switch ($action) {
    case 'list':
        echo json_encode(['success' => true, 'data' => $servicesData['services']]);
        break;

    case 'get':
        $id = sanitizeInput($_GET['id'] ?? '');
        foreach ($servicesData['services'] as $service) {
            if ($service['id'] === $id) {
                echo json_encode(['success' => true, 'data' => $service]);
                exit;
            }
        }
        echo json_encode(['success' => false, 'error' => 'Servizio non trovato']);
        break;

    case 'create':
        $data = getServiceRequestData();
        $supplier_id = $data['supplier_id'] ?? '';
        $name = $data['name'] ?? '';
        $category = $data['category'] ?? '';
        $price = $data['price'] ?? '';

        if (!$supplier_id || !$name || !$category || $price === '') {
            echo json_encode(['success' => false, 'error' => 'Dati non validi: supplier_id, name, category e price sono obbligatori']);
            exit;
        }

        $servicesData['last_id'] = ($servicesData['last_id'] ?? 0) + 1;
        $id = 'SVC' . str_pad((string)$servicesData['last_id'], 3, '0', STR_PAD_LEFT);

        $service = [
            'id' => $id,
            'supplier_id' => $supplier_id,
            'name' => $name,
            'category' => $category,
            'price' => (float)$price,
            'description' => $data['description'] ?? '',
            'notes' => $data['notes'] ?? '',
            'active' => true,
            'created_at' => date('c'),
            'updated_at' => date('c')
        ];

        $servicesData['services'][] = $service;
        writeJson($servicesFile, $servicesData);
        echo json_encode(['success' => true, 'data' => $service]);
        break;

    case 'update':
        $data = getServiceRequestData();
        $id = $data['id'] ?? '';
        if (!$id) {
            echo json_encode(['success' => false, 'error' => 'ID mancante']);
            exit;
        }

        $updated = false;
        foreach ($servicesData['services'] as &$service) {
            if ($service['id'] === $id) {
                $service['supplier_id'] = $data['supplier_id'] ?? $service['supplier_id'];
                $service['name'] = $data['name'] ?? $service['name'];
                $service['category'] = $data['category'] ?? $service['category'];
                if (isset($data['price'])) {
                    $service['price'] = (float)$data['price'];
                }
                $service['description'] = $data['description'] ?? $service['description'];
                $service['notes'] = $data['notes'] ?? $service['notes'];
                if (isset($data['active'])) {
                    $service['active'] = (bool)$data['active'];
                }
                $service['updated_at'] = date('c');
                $updated = true;
                break;
            }
        }

        if ($updated) {
            writeJson($servicesFile, $servicesData);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Servizio non trovato']);
        }
        break;

    case 'delete':
        $data = getServiceRequestData();
        $id = $data['id'] ?? '';
        if (!$id) {
            echo json_encode(['success' => false, 'error' => 'ID mancante']);
            exit;
        }

        $deleted = false;
        foreach ($servicesData['services'] as &$service) {
            if ($service['id'] === $id) {
                $service['active'] = false;
                $service['updated_at'] = date('c');
                $deleted = true;
                break;
            }
        }

        if ($deleted) {
            writeJson($servicesFile, $servicesData);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Servizio non trovato']);
        }
        break;

    case 'by_supplier':
        $supplier_id = sanitizeInput($_GET['supplier_id'] ?? '');
        if (!$supplier_id) {
            echo json_encode(['success' => false, 'error' => 'Supplier ID mancante']);
            exit;
        }

        $supplier_services = array_values(array_filter($servicesData['services'], function($s) use ($supplier_id) {
            return $s['supplier_id'] === $supplier_id && $s['active'];
        }));
        echo json_encode(['success' => true, 'data' => $supplier_services]);
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Azione non valida']);
        break;
}
?>
