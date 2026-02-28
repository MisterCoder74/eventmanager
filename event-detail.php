<?php
require_once __DIR__ . '/auth.php';
requireAuth();
require_once __DIR__ . '/jsonManager.php';

if (isset($_GET['logout'])) {
    logout();
}

// Scarica documento
if (isset($_GET['download']) && isset($_GET['doc'])) {
    $event_id = sanitizeInput($_GET['id'] ?? '');
    $filename = sanitizeInput($_GET['doc'] ?? '');

    $file_path = __DIR__ . '/events/' . $event_id . '/uploads/' . $filename;

    if (file_exists($file_path)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($file_path));
        readfile($file_path);
        exit;
    } else {
        die('File non trovato');
    }
}

$eventId = sanitizeInput($_GET['id'] ?? '');
$eventsData = readJson(__DIR__ . '/events.json') ?? ['events' => []];
$event = null;
foreach ($eventsData['events'] as $evt) {
    if ($evt['id'] === $eventId) {
        $event = $evt;
        break;
    }
}

if (!$event) {
    header('Location: eventi.php');
    exit;
}

// Get client name if available
$clientName = '';
if (!empty($event['client_id'])) {
    $clientsData = readJson(__DIR__ . '/clients.json') ?? ['clients' => []];
    foreach ($clientsData['clients'] as $client) {
        if ($client['id'] === $event['client_id']) {
            $clientName = $client['name'];
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dettaglio Evento - Gestionale Eventi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">📅 Gestionale Eventi</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="eventi.php">Eventi</a></li>
                    <li class="nav-item"><a class="nav-link" href="clienti.php">Clienti</a></li>
                    <li class="nav-item"><a class="nav-link" href="fornitori.php">Fornitori</a></li>
                    <li class="nav-item"><a class="nav-link" href="task.php">Task</a></li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <?php echo htmlspecialchars($_SESSION['name']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="?logout=1">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1><?php echo htmlspecialchars($event['title']); ?></h1>
            <a href="eventi.php" class="btn btn-outline-secondary">← Torna</a>
        </div>

        <!-- Tabs -->
        <ul class="nav nav-tabs mb-4" id="eventTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="details-tab" data-bs-toggle="tab" data-bs-target="#details-pane" type="button">
                    Dettagli
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="servizi-tab" data-bs-toggle="tab" data-bs-target="#servizi-pane" type="button">
                    Servizi & Budget
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tasks-tab" data-bs-toggle="tab" data-bs-target="#tasks-pane" type="button">
                    Task
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="documents-tab" data-bs-toggle="tab" data-bs-target="#documents-pane" type="button">
                    Documenti
                </button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="eventTabsContent">
            <!-- Dettagli Tab -->
            <div class="tab-pane fade show active" id="details-pane">
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Informazioni Generali</h5>
                                <p><strong>ID:</strong> <?php echo htmlspecialchars($event['id']); ?></p>
                                <p><strong>Tipo:</strong> <?php echo htmlspecialchars($event['type'] ?? '-'); ?></p>
                                <p><strong>Data:</strong> <?php echo htmlspecialchars($event['date']); ?>
                                <?php if (!empty($event['time'])): ?>
                                    alle <?php echo htmlspecialchars($event['time']); ?>
                                <?php endif; ?>
                                </p>
                                <p><strong>Luogo:</strong> <?php echo htmlspecialchars($event['location'] ?? '-'); ?></p>
                                <?php if (!empty($event['address'])): ?>
                                    <p><strong>Indirizzo:</strong> <?php echo htmlspecialchars($event['address']); ?></p>
                                <?php endif; ?>
                                <p><strong>Cliente:</strong> <?php echo htmlspecialchars($clientName ?: '-'); ?></p>
                                <?php if (!empty($event['guest_count'])): ?>
                                    <p><strong>Ospiti:</strong> <?php echo htmlspecialchars($event['guest_count']); ?></p>
                                <?php endif; ?>
                                <p><strong>Stato:</strong> <span class="badge bg-<?php echo getStatusColor($event['status']); ?>">
                                    <?php echo htmlspecialchars($event['status']); ?>
                                </span></p>
                                <p><strong>Note:</strong> <?php echo nl2br(htmlspecialchars($event['notes'] ?? '-')); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Budget Cliente</h5>
                                <div class="mb-3">
                                    <label class="form-label">Budget Totale Cliente (€)</label>
                                    <div class="input-group">
                                        <input type="number" id="budgetClient" class="form-control"
                                               value="<?php echo htmlspecialchars($event['budget_client'] ?? 0); ?>"
                                               step="0.01" min="0">
                                        <button class="btn btn-primary" onclick="updateClientBudget()">
                                            💾 Aggiorna
                                        </button>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Soglia Alert (%)</label>
                                    <div class="input-group">
                                        <input type="number" id="budgetThreshold" class="form-control"
                                               value="<?php echo htmlspecialchars($event['budget_alert_threshold'] ?? 80); ?>"
                                               step="5" min="50" max="100">
                                        <button class="btn btn-outline-secondary" onclick="updateBudgetThreshold()">
                                            💾
                                        </button>
                                    </div>
                                    <small class="text-muted">Alert quando il preventivo raggiunge questa % del budget cliente</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Servizi & Budget Tab -->
            <div class="tab-pane fade" id="servizi-pane">
                <!-- Budget Card -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Budget Preventivo</h5>
                        <div id="budgetAlert" class="alert alert-info">
                            Budget cliente: €<span id="displayBudgetClient">0</span> |
                            Preventivo: €<span id="displayBudgetPreventivo">0</span> |
                            Utilizzo: <span id="displayPercentage">0</span>%
                        </div>

                        <!-- Progress Bar -->
                        <div class="progress mb-3" id="budgetProgress" style="height: 25px;">
                            <div class="progress-bar" role="progressbar" style="width: 0%">
                                <span id="progressText">0%</span>
                            </div>
                        </div>

                        <div class="row text-center">
                            <div class="col-md-3">
                                <div class="p-3 border rounded">
                                    <h6>Budget Cliente</h6>
                                    <h4 class="text-primary">€<span id="summaryBudgetClient">0</span></h4>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="p-3 border rounded">
                                    <h6>Preventivo</h6>
                                    <h4 class="text-success">€<span id="summaryBudgetPreventivo">0</span></h4>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="p-3 border rounded">
                                    <h6>Rimanente</h6>
                                    <h4 class="text-warning">€<span id="summaryRemaining">0</span></h4>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="p-3 border rounded">
                                    <h6>Stato</h6>
                                    <h4 id="summaryStatus">OK</h4>
                                </div>
                            </div>
                        </div>

                        <button class="btn btn-success mt-3" data-bs-toggle="modal" data-bs-target="#serviceModal">
                            ➕ Aggiungi Servizio
                        </button>
                    </div>
                </div>

                <!-- Servizi Table -->
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Servizi Associati</h5>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Servizio</th>
                                        <th>Fornitore</th>
                                        <th>Prezzo</th>
                                        <th>Stato</th>
                                        <th>Note</th>
                                        <th>Azioni</th>
                                    </tr>
                                </thead>
                                <tbody id="servicesTableBody">
                                    <tr>
                                        <td colspan="6" class="text-center">Caricamento...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Task Tab -->
            <div class="tab-pane fade" id="tasks-pane">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="card-title">Task dell'Evento</h5>
                            <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#taskModal">
                                ➕ Nuovo Task
                            </button>
                        </div>
                        <ul class="list-group mb-3" id="tasksList"></ul>
                    </div>
                </div>
            </div>

            <!-- Documenti Tab -->
            <div class="tab-pane fade" id="documents-pane">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Documenti</h5>
                        <ul class="list-group mb-3" id="documentsList">
                            <?php if (!empty($event['documents'])): ?>
                                <?php foreach ($event['documents'] as $doc): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <?php echo htmlspecialchars($doc); ?>
                                    <a class="btn btn-sm btn-outline-primary"
                                       href="event-detail.php?id=<?php echo urlencode($event['id']); ?>&download=1&doc=<?php echo urlencode($doc); ?>">
                                        Download
                                    </a>
                                </li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li class="list-group-item text-muted">Nessun documento caricato</li>
                            <?php endif; ?>
                        </ul>
                        <form id="uploadForm" enctype="multipart/form-data">
                            <input type="hidden" name="event_id" value="<?php echo htmlspecialchars($event['id']); ?>">
                            <div class="input-group">
                                <input type="file" class="form-control" name="file" required>
                                <button class="btn btn-primary" type="submit">Carica</button>
                            </div>
                        </form>
                        <div id="uploadMessage" class="text-muted mt-2"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Aggiungi Servizio -->
    <div class="modal fade" id="serviceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Aggiungi Servizio</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="serviceForm">
                        <div class="mb-3">
                            <label class="form-label">Fornitore <span class="text-danger">*</span></label>
                            <select id="serviceSupplier" class="form-select" onchange="loadServices(this.value)" required>
                                <option value="">Seleziona fornitore</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Servizio <span class="text-danger">*</span></label>
                            <select id="serviceSelect" class="form-select" onchange="updateServicePrice()" required>
                                <option value="">Seleziona servizio</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Prezzo (€)</label>
                            <input type="number" id="servicePrice" class="form-control" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Note</label>
                            <textarea id="serviceNotes" class="form-control" rows="2"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="button" class="btn btn-primary" onclick="addService()">Aggiungi</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modifica Servizio Modal -->
    <div class="modal fade" id="editServiceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Modifica Servizio</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editServiceForm">
                        <input type="hidden" id="editServiceId">
                        <div class="mb-3">
                            <label class="form-label">Prezzo (€)</label>
                            <input type="number" id="editServicePrice" class="form-control" step="0.01" min="0">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Stato</label>
                            <select id="editServiceStatus" class="form-select">
                                <option value="pending">Pending</option>
                                <option value="confirmed">Confirmed</option>
                                <option value="paid">Paid</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Note</label>
                            <textarea id="editServiceNotes" class="form-control" rows="2"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="button" class="btn btn-primary" onclick="updateService()">Aggiorna</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Nuovo Task -->
    <div class="modal fade" id="taskModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nuovo Task</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="taskForm">
                        <div class="mb-3">
                            <label class="form-label">Titolo <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Descrizione</label>
                            <textarea class="form-control" name="description" rows="2"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Priorità</label>
                            <select class="form-select" name="priority">
                                <option value="low">Low</option>
                                <option value="medium" selected>Medium</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Scadenza <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="due_date" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="button" class="btn btn-primary" onclick="addTask()">Aggiungi</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        window.currentEventId = <?php echo json_encode($event['id']); ?>;
    </script>
    <script src="app.js"></script>
    <script src="event-detail.js"></script>
</body>
</html>

<?php
function getStatusColor($status) {
    $colors = [
        'planning' => 'secondary',
        'confirmed' => 'success',
        'in_progress' => 'primary',
        'completed' => 'info',
        'cancelled' => 'danger'
    ];
    return $colors[$status] ?? 'secondary';
}
?>
