<?php
require_once __DIR__ . '/auth.php';
requireAuth();
require_once __DIR__ . '/jsonManager.php';

function exportJsonFile($filename, $data) {
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="' . $filename . '.json"');
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

function exportCsvFile($filename, $data) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');

    $output = fopen('php://output', 'w');

    if (empty($data)) {
        fclose($output);
        exit;
    }

    fputcsv($output, array_keys($data[0]));
    foreach ($data as $row) {
        fputcsv($output, $row);
    }

    fclose($output);
    exit;
}

function exportExcelFile($filename, $data) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="' . $filename . '.xls"');

    if (empty($data)) {
        echo '';
        exit;
    }

    echo implode("\t", array_keys($data[0])) . "\n";
    foreach ($data as $row) {
        echo implode("\t", $row) . "\n";
    }
    exit;
}

$dataset = sanitizeInput($_GET['dataset'] ?? 'events');
$format = sanitizeInput($_GET['format'] ?? 'csv');

$fileMap = [
    'events' => ['file' => __DIR__ . '/events.json', 'key' => 'events'],
    'clients' => ['file' => __DIR__ . '/clients.json', 'key' => 'clients'],
    'suppliers' => ['file' => __DIR__ . '/suppliers.json', 'key' => 'suppliers'],
    'tasks' => ['file' => __DIR__ . '/tasks.json', 'key' => 'tasks'],
    'services' => ['file' => __DIR__ . '/services.json', 'key' => 'services'],
    'budget' => ['file' => __DIR__ . '/budget.json', 'key' => 'items']
];

if (!isset($fileMap[$dataset])) {
    http_response_code(400);
    echo 'Dataset non valido';
    exit;
}

$data = readJson($fileMap[$dataset]['file']);
$items = $data[$fileMap[$dataset]['key']] ?? [];

switch ($format) {
    case 'json':
        exportJsonFile($dataset, $items);
        break;
    case 'excel':
        exportExcelFile($dataset, $items);
        break;
    case 'csv':
    default:
        exportCsvFile($dataset, $items);
        break;
}
?>
