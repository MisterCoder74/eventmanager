<?php
require_once __DIR__ . '/auth.php';
requireAuth();
require_once __DIR__ . '/jsonManager.php';

if (isset($_GET['logout'])) {
    logout();
}

$clientsData = readJson(__DIR__ . '/clients.json') ?? ['clients' => []];
$clients = $clientsData['clients'];
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eventi - Gestionale Eventi</title>
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
                    <li class="nav-item"><a class="nav-link active" href="eventi.php">Eventi</a></li>
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
            <h1>Eventi</h1>
            <div class="d-flex gap-2">
                <a href="export.php?dataset=events&format=csv" class="btn btn-outline-secondary">Export CSV</a>
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#eventModal">➕ Nuovo Evento</button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-striped" id="eventsTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Titolo</th>
                        <th>Data</th>
                        <th>Luogo</th>
                        <th>Stato</th>
                        <th>Azioni</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="eventModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nuovo Evento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="eventForm">
                        <input type="hidden" name="id" id="eventId">
                        <div class="mb-3">
                            <label class="form-label">Titolo</label>
                            <input type="text" class="form-control" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tipo</label>
                            <select class="form-select" name="type">
                                <option value="">Seleziona tipo</option>
                                <option value="Corporate">Corporate</option>
                                <option value="Wedding">Wedding</option>
                                <option value="Birthday">Birthday</option>
                                <option value="Conference">Conference</option>
                                <option value="Private">Private</option>
                                <option value="Other">Altro</option>
                            </select>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Data *</label>
                                <input type="date" class="form-control" name="date" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Ora</label>
                                <input type="time" class="form-control" name="time">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Location *</label>
                                <input type="text" class="form-control" name="location" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Numero di Invitati Previsti</label>
                                <input type="number" class="form-control" name="guest_count" min="0" placeholder="0">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Indirizzo</label>
                            <input type="text" class="form-control" name="address">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Cliente</label>
                            <select class="form-select" name="client_id">
                                <option value="">Seleziona cliente</option>
                                <?php foreach ($clients as $client): ?>
                                <option value="<?php echo htmlspecialchars($client['id']); ?>"><?php echo htmlspecialchars($client['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Stato</label>
                            <select class="form-select" name="status">
                                <option value="planning">Planning</option>
                                <option value="confirmed">Confermato</option>
                                <option value="completed">Completato</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Note</label>
                            <textarea class="form-control" name="notes" rows="2"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Salva</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        window.clientsData = <?php echo json_encode($clients); ?>;
    </script>
    <script src="app.js"></script>
    <script src="events.js"></script>
</body>
</html>
