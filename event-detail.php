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
                    <li class="nav-item"><a class="nav-link active" href="eventi.php">Eventi</a></li>
                    <li class="nav-item"><a class="nav-link" href="clienti.php">Clienti</a></li>
                    <li class="nav-item"><a class="nav-link" href="fornitori.php">Fornitori</a></li>
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

        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Dettagli</h5>
                        <p><strong>ID:</strong> <?php echo htmlspecialchars($event['id']); ?></p>
                        <p><strong>Data:</strong> <?php echo htmlspecialchars($event['date']); ?></p>
                        <p><strong>Luogo:</strong> <?php echo htmlspecialchars($event['location']); ?></p>
                        <p><strong>Stato:</strong> <?php echo htmlspecialchars($event['status']); ?></p>
                        <p><strong>Note:</strong> <?php echo htmlspecialchars($event['notes']); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Documenti</h5>
                        <ul class="list-group mb-3" id="documentsList">
                            <?php if (!empty($event['documents'])): ?>
                                <?php foreach ($event['documents'] as $doc): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <?php echo htmlspecialchars($doc); ?>
                                    <a class="btn btn-sm btn-outline-primary" href="event-detail.php?id=<?php echo urlencode($event['id']); ?>&download=1&doc=<?php echo urlencode($doc); ?>">Download</a>
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

        <div class="row g-4">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Task</h5>
                        <ul class="list-group mb-3" id="tasksList"></ul>
                        <form id="taskForm">
                            <input type="hidden" name="event_id" value="<?php echo htmlspecialchars($event['id']); ?>">
                            <div class="mb-2">
                                <input type="text" class="form-control" name="title" placeholder="Titolo task" required>
                            </div>
                            <div class="mb-2">
                                <input type="date" class="form-control" name="due_date" required>
                            </div>
                            <button class="btn btn-outline-primary w-100" type="submit">Aggiungi Task</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Budget</h5>
                        <ul class="list-group mb-3" id="budgetList"></ul>
                        <form id="budgetForm">
                            <input type="hidden" name="event_id" value="<?php echo htmlspecialchars($event['id']); ?>">
                            <div class="mb-2">
                                <input type="text" class="form-control" name="description" placeholder="Voce budget" required>
                            </div>
                            <div class="mb-2">
                                <input type="number" class="form-control" name="amount" placeholder="Importo" step="0.01" required>
                            </div>
                            <div class="mb-2">
                                <select class="form-select" name="type">
                                    <option value="planned">Previsto</option>
                                    <option value="actual">Consuntivo</option>
                                </select>
                            </div>
                            <button class="btn btn-outline-primary w-100" type="submit">Aggiungi Budget</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        window.currentEventId = <?php echo json_encode($event['id']); ?>;
    </script>
    <script src="app.js"></script>
    <script src="events.js"></script>
</body>
</html>
