<?php
require_once __DIR__ . '/auth.php';
requireAuth();
require_once __DIR__ . '/jsonManager.php';

header('Content-Type: application/json');

$action = sanitizeInput($_GET['action'] ?? '');

switch ($action) {
    case 'generate':
        generateReport();
        break;
    
    default:
        echo json_encode(['success' => false, 'error' => 'Azione non valida']);
        break;
}

/**
 * Genera report evento e restituisce contenuto
 */
function generateReport() {
    $eventId = sanitizeInput($_GET['event_id'] ?? '');
    
    if (!$eventId) {
        echo json_encode(['success' => false, 'error' => 'ID evento mancante']);
        exit;
    }
    
    // Carica dati evento
    $events = readJson(__DIR__ . '/events.json');
    $event = null;
    
    foreach ($events['events'] as $e) {
        if ($e['id'] === $eventId) {
            $event = $e;
            break;
        }
    }
    
    if (!$event) {
        echo json_encode(['success' => false, 'error' => 'Evento non trovato']);
        exit;
    }
    
    // Carica dati collegati
    $clients = readJson(__DIR__ . '/clients.json');
    $suppliers = readJson(__DIR__ . '/suppliers.json');
    $tasks = readJson(__DIR__ . '/tasks.json');
    
    // Trova cliente
    $client = null;
    foreach ($clients['clients'] ?? [] as $c) {
        if ($c['id'] === $event['client_id']) {
            $client = $c;
            break;
        }
    }
    
    // Trova fornitori
    $supplierMap = [];
    foreach ($suppliers['suppliers'] ?? [] as $s) {
        $supplierMap[$s['id']] = $s;
    }
    
    // Filtra task dell'evento
    $eventTasks = array_filter($tasks['tasks'] ?? [], function($t) use ($eventId) {
        return $t['event_id'] === $eventId;
    });
    
    // Calcola budget
    $budgetPreventivo = 0;
    $confirmedServices = [];
    foreach ($event['servizi'] ?? [] as $servizio) {
        if (in_array($servizio['status'], ['confirmed', 'paid'])) {
            $budgetPreventivo += $servizio['price'];
            $confirmedServices[] = $servizio;
        }
    }
    
    $budgetCliente = $event['budget_client'] ?? 0;
    $percentage = $budgetCliente > 0 ? ($budgetPreventivo / $budgetCliente) * 100 : 0;
    
    // Genera contenuto HTML del report
    $html = generateHTMLReport($event, $client, $supplierMap, $eventTasks, $confirmedServices, $budgetPreventivo, $percentage);
    
    // Genera contenuto TXT del report
    $txt = generateTXTReport($event, $client, $supplierMap, $eventTasks, $confirmedServices, $budgetPreventivo, $percentage);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'event' => $event,
            'html' => $html,
            'txt' => $txt,
            'filename' => 'Report_Evento_' . $event['title'] . '_' . date('Y-m-d')
        ]
    ]);
}

/**
 * Genera report in formato HTML
 */
function generateHTMLReport($event, $client, $supplierMap, $tasks, $services, $budgetPreventivo, $percentage) {
    $eventTitle = htmlspecialchars($event['title'] ?? '');
    $eventType = htmlspecialchars($event['type'] ?? '-');
    $eventDate = htmlspecialchars($event['date'] ?? '-');
    $eventTime = htmlspecialchars($event['time'] ?? '-');
    $eventLocation = htmlspecialchars($event['location'] ?? '-');
    $eventAddress = htmlspecialchars($event['address'] ?? '-');
    $eventNotes = htmlspecialchars($event['notes'] ?? '-');
    $eventStatus = htmlspecialchars($event['status'] ?? '-');
    $guestCount = $event['guest_count'] ?? 0;
    $budgetCliente = $event['budget_client'] ?? 0;
    $createdAt = $event['created_at'] ?? '-';
    $updatedAt = $event['updated_at'] ?? '-';
    
    $clientName = $client ? htmlspecialchars($client['name']) : '-';
    $clientEmail = $client ? htmlspecialchars($client['email'] ?? '-') : '-';
    $clientPhone = $client ? htmlspecialchars($client['phone'] ?? '-') : '-';
    
    $html = '<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Report Evento - ' . $eventTitle . '</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; line-height: 1.4; margin: 0; padding: 0; }
        .header { background-color: #007bff; color: white; padding: 20px; text-align: center; }
        .header h1 { margin: 0; font-size: 24px; }
        .header p { margin: 5px 0 0; opacity: 0.9; }
        .container { padding: 20px; max-width: 800px; margin: 0 auto; }
        .section { margin-bottom: 30px; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .section h2 { margin-top: 0; color: #007bff; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
        .section h3 { color: #333; margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table th, table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        table th { background-color: #f5f5f5; font-weight: bold; }
        .status-badge { padding: 3px 8px; border-radius: 3px; font-size: 11px; font-weight: bold; }
        .status-pending { background-color: #ffc107; color: #000; }
        .status-confirmed { background-color: #17a2b8; color: #fff; }
        .status-paid { background-color: #28a745; color: #fff; }
        .status-cancelled { background-color: #dc3545; color: #fff; }
        .status-planning { background-color: #6c757d; color: #fff; }
        .status-in_progress { background-color: #007bff; color: #fff; }
        .status-completed { background-color: #17a2b8; color: #fff; }
        .status-done { background-color: #28a745; color: #fff; }
        .priority-high { color: #dc3545; font-weight: bold; }
        .priority-medium { color: #ffc107; }
        .priority-low { color: #28a745; }
        .priority-urgent { color: #dc3545; font-weight: bold; text-decoration: underline; }
        .total-row { font-weight: bold; background-color: #f5f5f5; }
        .alert { padding: 10px; border-radius: 5px; margin: 10px 0; }
        .alert-success { background-color: #d4edda; border: 1px solid #c3e6cb; }
        .alert-warning { background-color: #fff3cd; border: 1px solid #ffeaa7; }
        .alert-danger { background-color: #f8d7da; border: 1px solid #f5c6cb; }
        .info-row { display: flex; margin-bottom: 8px; }
        .info-label { font-weight: bold; min-width: 150px; }
        .info-value { flex: 1; }
        .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; text-align: center; color: #666; font-size: 11px; }
        @media print {
            body { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
            .section { break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📊 Report Evento</h1>
        <p>Generato il ' . date('d/m/Y H:i') . '</p>
    </div>

    <div class="container">
        <!-- SEZIONE 1: DETTAGLI SETUP -->
        <div class="section">
            <h2>1. Dettagli Setup</h2>
            
            <div class="info-row">
                <div class="info-label">ID Evento:</div>
                <div class="info-value">' . htmlspecialchars($event['id']) . '</div>
            </div>
            <div class="info-row">
                <div class="info-label">Titolo:</div>
                <div class="info-value">' . $eventTitle . '</div>
            </div>
            <div class="info-row">
                <div class="info-label">Tipo:</div>
                <div class="info-value">' . $eventType . '</div>
            </div>
            <div class="info-row">
                <div class="info-label">Data:</div>
                <div class="info-value">' . $eventDate . '</div>
            </div>
            <div class="info-row">
                <div class="info-label">Ora:</div>
                <div class="info-value">' . $eventTime . '</div>
            </div>
            <div class="info-row">
                <div class="info-label">Location:</div>
                <div class="info-value">' . $eventLocation . '</div>
            </div>
            <div class="info-row">
                <div class="info-label">Indirizzo:</div>
                <div class="info-value">' . $eventAddress . '</div>
            </div>
            <div class="info-row">
                <div class="info-label">Cliente:</div>
                <div class="info-value">' . $clientName . '</div>
            </div>
            <div class="info-row">
                <div class="info-label">Email Cliente:</div>
                <div class="info-value">' . $clientEmail . '</div>
            </div>
            <div class="info-row">
                <div class="info-label">Telefono Cliente:</div>
                <div class="info-value">' . $clientPhone . '</div>
            </div>
            <div class="info-row">
                <div class="info-label">Numero Invitati:</div>
                <div class="info-value">' . number_format($guestCount) . '</div>
            </div>
            <div class="info-row">
                <div class="info-label">Stato:</div>
                <div class="info-value"><span class="status-badge status-' . htmlspecialchars($event['status'] ?? 'planning') . '">' . $eventStatus . '</span></div>
            </div>
            <div class="info-row">
                <div class="info-label">Note:</div>
                <div class="info-value">' . ($eventNotes ?: 'Nessuna nota') . '</div>
            </div>
            <div class="info-row">
                <div class="info-label">Creato il:</div>
                <div class="info-value">' . $createdAt . '</div>
            </div>
            <div class="info-row">
                <div class="info-label">Aggiornato il:</div>
                <div class="info-value">' . $updatedAt . '</div>
            </div>
        </div>';

    // SEZIONE 2: BUDGET
    $html .= '
        <div class="section">
            <h2>2. Budget</h2>
            
            <div class="info-row">
                <div class="info-label">Budget Cliente:</div>
                <div class="info-value">€' . number_format($budgetCliente, 2) . '</div>
            </div>
            <div class="info-row">
                <div class="info-label">Preventivo (Servizi Confermati/Pagati):</div>
                <div class="info-value">€' . number_format($budgetPreventivo, 2) . '</div>
            </div>
            <div class="info-row">
                <div class="info-label">Varianza:</div>
                <div class="info-value">€' . number_format($budgetPreventivo - $budgetCliente, 2) . '</div>
            </div>
            <div class="info-row">
                <div class="info-label">Utilizzo:</div>
                <div class="info-value">' . number_format($percentage, 1) . '%</div>
            </div>';
    
    // Alert budget
    if ($percentage >= 95) {
        $html .= '<div class="alert alert-danger">⚠️ ATTENZIONE! Hai superato il 95% del budget cliente!</div>';
    } elseif ($percentage >= 80) {
        $html .= '<div class="alert alert-warning">⚠️ Stai raggiungendo il limite del budget cliente (80%)</div>';
    } else {
        $html .= '<div class="alert alert-success">✓ Budget sotto controllo</div>';
    }
    
    $html .= '</div>';

    // SEZIONE 3: SERVIZI
    $html .= '
        <div class="section">
            <h2>3. Servizi</h2>';
    
    if (!empty($event['servizi'])) {
        $html .= '<table>
            <thead>
                <tr>
                    <th>Servizio</th>
                    <th>Fornitore</th>
                    <th>Prezzo</th>
                    <th>Stato</th>
                    <th>Note</th>
                </tr>
            </thead>
            <tbody>';
        
        $totalServices = 0;
        foreach ($event['servizi'] as $servizio) {
            $serviceName = htmlspecialchars($servizio['name']);
            $supplier = $supplierMap[$servizio['supplier_id']] ?? null;
            $supplierName = $supplier ? htmlspecialchars($supplier['name']) : '-';
            $price = $servizio['price'];
            $status = htmlspecialchars($servizio['status']);
            $notes = htmlspecialchars($servizio['notes'] ?? '-');
            $totalServices += $price;
            
            $html .= '
                <tr>
                    <td>' . $serviceName . '</td>
                    <td>' . $supplierName . '</td>
                    <td>€' . number_format($price, 2) . '</td>
                    <td><span class="status-badge status-' . $status . '">' . $status . '</span></td>
                    <td>' . $notes . '</td>
                </tr>';
        }
        
        $html .= '
                <tr class="total-row">
                    <td colspan="2" style="text-align: right;"><strong>TOTALE:</strong></td>
                    <td><strong>€' . number_format($totalServices, 2) . '</strong></td>
                    <td colspan="2"></td>
                </tr>
            </tbody>
        </table>';
    } else {
        $html .= '<p>Nessun servizio associato</p>';
    }
    
    $html .= '</div>';

    // SEZIONE 4: FORNITORI COINVOLTI
    $html .= '
        <div class="section">
            <h2>4. Fornitori Coinvolti</h2>';
    
    if (!empty($event['servizi'])) {
        $fornitoriCoinvolti = [];
        foreach ($event['servizi'] as $servizio) {
            $supplierId = $servizio['supplier_id'];
            if (!isset($fornitoriCoinvolti[$supplierId]) && isset($supplierMap[$supplierId])) {
                $fornitoriCoinvolti[$supplierId] = $supplierMap[$supplierId];
            }
        }
        
        if (!empty($fornitoriCoinvolti)) {
            $html .= '<table>
                <thead>
                    <tr>
                        <th>Nome Fornitore</th>
                        <th>Tipo</th>
                        <th>Email</th>
                        <th>Telefono</th>
                        <th>Contatto</th>
                    </tr>
                </thead>
                <tbody>';
            
            foreach ($fornitoriCoinvolti as $fornitore) {
                $html .= '
                    <tr>
                        <td>' . htmlspecialchars($fornitore['name']) . '</td>
                        <td>' . htmlspecialchars($fornitore['type'] ?? '-') . '</td>
                        <td>' . htmlspecialchars($fornitore['email'] ?? '-') . '</td>
                        <td>' . htmlspecialchars($fornitore['phone'] ?? '-') . '</td>
                        <td>' . htmlspecialchars($fornitore['contact_person'] ?? '-') . '</td>
                    </tr>';
            }
            
            $html .= '
                </tbody>
            </table>';
        } else {
            $html .= '<p>Nessun fornitore coinvolto</p>';
        }
    } else {
        $html .= '<p>Nessun fornitore coinvolto</p>';
    }
    
    $html .= '</div>';

    // SEZIONE 5: TASK
    $html .= '
        <div class="section">
            <h2>5. Task</h2>';
    
    $tasksArray = is_array($tasks) ? array_values($tasks) : [];
    
    if (!empty($tasksArray)) {
        $html .= '<table>
            <thead>
                <tr>
                    <th>Titolo</th>
                    <th>Descrizione</th>
                    <th>Priorità</th>
                    <th>Scadenza</th>
                    <th>Stato</th>
                    <th>Assegnato a</th>
                </tr>
            </thead>
            <tbody>';
        
        $totalTask = count($tasksArray);
        $completedTask = 0;
        
        foreach ($tasksArray as $task) {
            $title = htmlspecialchars($task['title']);
            $description = htmlspecialchars($task['description'] ?? '-');
            $priority = htmlspecialchars($task['priority'] ?? '-');
            $dueDate = htmlspecialchars($task['due_date'] ?? '-');
            $status = htmlspecialchars($task['status']);
            $assignedTo = htmlspecialchars($task['assigned_to'] ?? '-');
            
            if ($status === 'done') {
                $completedTask++;
            }
            
            $priorityClass = 'priority-' . strtolower($priority);
            
            $html .= '
                <tr>
                    <td>' . $title . '</td>
                    <td>' . $description . '</td>
                    <td><span class="' . $priorityClass . '">' . $priority . '</span></td>
                    <td>' . $dueDate . '</td>
                    <td><span class="status-badge status-' . $status . '">' . $status . '</span></td>
                    <td>' . $assignedTo . '</td>
                </tr>';
        }
        
        $taskProgress = $totalTask > 0 ? ($completedTask / $totalTask) * 100 : 0;
        
        $html .= '
                <tr class="total-row">
                    <td colspan="6" style="text-align: center;">
                        <strong>Task Completati: ' . $completedTask . ' / ' . $totalTask . ' (' . number_format($taskProgress, 1) . '%)</strong>
                    </td>
                </tr>
            </tbody>
        </table>';
    } else {
        $html .= '<p>Nessun task associato</p>';
        $totalTask = 0;
        $completedTask = 0;
        $taskProgress = 0;
    }
    
    $html .= '</div>';

    // SEZIONE 6: STATO OPERAZIONI
    $html .= '
        <div class="section">
            <h2>6. Stato Operazioni</h2>
            
            <div class="info-row">
                <div class="info-label">Stato Evento:</div>
                <div class="info-value"><span class="status-badge status-' . htmlspecialchars($event['status'] ?? 'planning') . '">' . $eventStatus . '</span></div>
            </div>
            <div class="info-row">
                <div class="info-label">Progresso Task:</div>
                <div class="info-value">' . (!empty($tasksArray) ? number_format(($completedTask / $totalTask) * 100, 1) : 'N/A') . '% completati</div>
            </div>
            <div class="info-row">
                <div class="info-label">Servizi Totali:</div>
                <div class="info-value">' . count($event['servizi'] ?? []) . '</div>
            </div>
            <div class="info-row">
                <div class="info-label">Servizi Confermati/Pagati:</div>
                <div class="info-value">' . count($confirmedServices) . '</div>
            </div>
            <div class="info-row">
                <div class="info-label">Stato Budget:</div>
                <div class="info-value">' . ($percentage >= 95 ? 'CRITICO' : ($percentage >= 80 ? 'ATTENZIONE' : 'OK')) . '</div>
            </div>
        </div>';

    // SEZIONE 7: DOCUMENTI
    $html .= '
        <div class="section">
            <h2>7. Documenti</h2>';
    
    if (!empty($event['documents'])) {
        $html .= '<table>
            <thead>
                <tr>
                    <th>Nome File</th>
                </tr>
            </thead>
            <tbody>';
        
        foreach ($event['documents'] as $doc) {
            $docName = htmlspecialchars($doc);
            
            $html .= '
                <tr>
                    <td>' . $docName . '</td>
                </tr>';
        }
        
        $html .= '
            </tbody>
        </table>';
    } else {
        $html .= '<p>Nessun documento allegato</p>';
    }
    
    $html .= '</div>';

    // FOOTER
    $html .= '
        <div class="footer">
            <p>Report generato dal Gestionale Eventi</p>
            <p>Data generazione: ' . date('d/m/Y H:i:s') . '</p>
        </div>
    </div>
</body>
</html>';
    
    return $html;
}

/**
 * Genera report in formato TXT
 */
function generateTXTReport($event, $client, $supplierMap, $tasks, $services, $budgetPreventivo, $percentage) {
    $lines = [];
    
    $lines[] = '================================================================================';
    $lines[] = '                              REPORT EVENTO';
    $lines[] = '================================================================================';
    $lines[] = '';
    $lines[] = 'Generato il: ' . date('d/m/Y H:i');
    $lines[] = '';
    
    // SEZIONE 1: DETTAGLI SETUP
    $lines[] = '1. DETTAGLI SETUP';
    $lines[] = str_repeat('-', 80);
    $lines[] = 'ID Evento:    ' . ($event['id'] ?? '-');
    $lines[] = 'Titolo:       ' . ($event['title'] ?? '-');
    $lines[] = 'Tipo:         ' . ($event['type'] ?? '-');
    $lines[] = 'Data:         ' . ($event['date'] ?? '-');
    $lines[] = 'Ora:          ' . ($event['time'] ?? '-');
    $lines[] = 'Location:     ' . ($event['location'] ?? '-');
    $lines[] = 'Indirizzo:    ' . ($event['address'] ?? '-');
    $lines[] = 'Cliente:      ' . ($client['name'] ?? '-');
    $lines[] = 'Email:        ' . ($client['email'] ?? '-');
    $lines[] = 'Telefono:     ' . ($client['phone'] ?? '-');
    $lines[] = 'Invitati:     ' . ($event['guest_count'] ?? 0);
    $lines[] = 'Stato:        ' . ($event['status'] ?? '-');
    $lines[] = 'Note:         ' . ($event['notes'] ?? 'Nessuna nota');
    $lines[] = 'Creato il:    ' . ($event['created_at'] ?? '-');
    $lines[] = 'Aggiornato:   ' . ($event['updated_at'] ?? '-');
    $lines[] = '';
    
    // SEZIONE 2: BUDGET
    $lines[] = '2. BUDGET';
    $lines[] = str_repeat('-', 80);
    $lines[] = 'Budget Cliente:           €' . number_format($event['budget_client'] ?? 0, 2);
    $lines[] = 'Preventivo:               €' . number_format($budgetPreventivo, 2);
    $lines[] = 'Varianza:                 €' . number_format($budgetPreventivo - ($event['budget_client'] ?? 0), 2);
    $lines[] = 'Utilizzo:                 ' . number_format($percentage, 1) . '%';
    $lines[] = '';
    
    // SEZIONE 3: SERVIZI
    $lines[] = '3. SERVIZI';
    $lines[] = str_repeat('-', 80);
    
    if (!empty($event['servizi'])) {
        $lines[] = sprintf('%-40s %-20s %12s %12s', 'Servizio', 'Fornitore', 'Prezzo', 'Stato');
        $lines[] = str_repeat('-', 80);
        
        foreach ($event['servizi'] as $servizio) {
            $supplier = $supplierMap[$servizio['supplier_id']] ?? null;
            $serviceName = substr($servizio['name'] ?? '-', 0, 40);
            $supplierName = substr($supplier['name'] ?? '-', 0, 20);
            $price = '€' . number_format($servizio['price'], 2);
            $status = $servizio['status'] ?? '-';
            
            $lines[] = sprintf('%-40s %-20s %12s %12s', $serviceName, $supplierName, $price, $status);
        }
    } else {
        $lines[] = 'Nessun servizio associato';
    }
    $lines[] = '';
    
    // SEZIONE 4: FORNITORI
    $lines[] = '4. FORNITORI COINVOLTI';
    $lines[] = str_repeat('-', 80);
    
    if (!empty($event['servizi'])) {
        $fornitoriCoinvolti = [];
        foreach ($event['servizi'] as $servizio) {
            $supplierId = $servizio['supplier_id'];
            if (!isset($fornitoriCoinvolti[$supplierId]) && isset($supplierMap[$supplierId])) {
                $fornitoriCoinvolti[$supplierId] = $supplierMap[$supplierId];
            }
        }
        
        if (!empty($fornitoriCoinvolti)) {
            $lines[] = sprintf('%-30s %-15s %-25s %-15s', 'Nome', 'Tipo', 'Email', 'Telefono');
            $lines[] = str_repeat('-', 80);
            
            foreach ($fornitoriCoinvolti as $fornitore) {
                $name = substr($fornitore['name'] ?? '-', 0, 30);
                $type = substr($fornitore['type'] ?? '-', 0, 15);
                $email = substr($fornitore['email'] ?? '-', 0, 25);
                $phone = substr($fornitore['phone'] ?? '-', 0, 15);
                
                $lines[] = sprintf('%-30s %-15s %-25s %-15s', $name, $type, $email, $phone);
            }
        } else {
            $lines[] = 'Nessun fornitore coinvolto';
        }
    } else {
        $lines[] = 'Nessun fornitore coinvolto';
    }
    $lines[] = '';
    
    // SEZIONE 5: TASK
    $lines[] = '5. TASK';
    $lines[] = str_repeat('-', 80);
    
    $tasksArray = is_array($tasks) ? array_values($tasks) : [];
    
    if (!empty($tasksArray)) {
        $lines[] = sprintf('%-30s %-20s %-10s %-12s %-12s', 'Titolo', 'Descrizione', 'Priorità', 'Scadenza', 'Stato');
        $lines[] = str_repeat('-', 80);
        
        foreach ($tasksArray as $task) {
            $title = substr($task['title'] ?? '-', 0, 30);
            $description = substr($task['description'] ?? '-', 0, 20);
            $priority = substr($task['priority'] ?? '-', 0, 10);
            $dueDate = substr($task['due_date'] ?? '-', 0, 12);
            $status = substr($task['status'] ?? '-', 0, 12);
            
            $lines[] = sprintf('%-30s %-20s %-10s %-12s %-12s', $title, $description, $priority, $dueDate, $status);
        }
    } else {
        $lines[] = 'Nessun task associato';
    }
    $lines[] = '';
    
    // SEZIONE 6: STATO OPERAZIONI
    $lines[] = '6. STATO OPERAZIONI';
    $lines[] = str_repeat('-', 80);
    $lines[] = 'Stato Evento:             ' . ($event['status'] ?? '-');
    $lines[] = 'Servizi Totali:           ' . count($event['servizi'] ?? []);
    $lines[] = 'Servizi Confermati:       ' . count($services);
    $lines[] = 'Stato Budget:             ' . ($percentage >= 95 ? 'CRITICO' : ($percentage >= 80 ? 'ATTENZIONE' : 'OK'));
    $lines[] = '';
    
    // SEZIONE 7: DOCUMENTI
    $lines[] = '7. DOCUMENTI';
    $lines[] = str_repeat('-', 80);
    
    if (!empty($event['documents'])) {
        foreach ($event['documents'] as $doc) {
            $lines[] = '- ' . $doc;
        }
    } else {
        $lines[] = 'Nessun documento allegato';
    }
    $lines[] = '';
    
    // FOOTER
    $lines[] = str_repeat('=', 80);
    $lines[] = 'Report generato dal Gestionale Eventi';
    $lines[] = 'Data generazione: ' . date('d/m/Y H:i:s');
    $lines[] = '';
    
    return implode("\n", $lines);
}
?>
