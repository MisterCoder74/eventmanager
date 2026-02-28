<?php
require_once __DIR__ . '/auth.php';
requireAuth();

if (isset($_GET['logout'])) {
    logout();
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task - Gestionale Eventi</title>
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
                    <li class="nav-item"><a class="nav-link active" href="task.php">Task</a></li>
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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Gestione Task</h1>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#taskModal">
                ➕ Nuovo Task
            </button>
        </div>

        <!-- Filtri -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Evento</label>
                        <select id="filter-event" class="form-select">
                            <option value="">Tutti gli eventi</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Stato</label>
                        <select id="filter-status" class="form-select">
                            <option value="">Tutti gli stati</option>
                            <option value="pending">Pending</option>
                            <option value="in_progress">In Progress</option>
                            <option value="done">Done</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Priorità</label>
                        <select id="filter-priority" class="form-select">
                            <option value="">Tutte le priorità</option>
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Scadenza</label>
                        <input type="date" id="filter-due" class="form-control" placeholder="Scadenza">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button class="btn btn-primary w-100" onclick="filterTasks()">Filtra</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabella Task -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Titolo</th>
                                <th>Evento</th>
                                <th>Stato</th>
                                <th>Priorità</th>
                                <th>Scadenza</th>
                                <th>Azioni</th>
                            </tr>
                        </thead>
                        <tbody id="tasksTableBody">
                            <tr>
                                <td colspan="6" class="text-center">Caricamento...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Nuovo/Modifica Task -->
    <div class="modal fade" id="taskModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="taskModalTitle">Nuovo Task</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="taskForm">
                        <input type="hidden" id="task_id">
                        <div class="mb-3">
                            <label class="form-label">Evento <span class="text-danger">*</span></label>
                            <select id="task_event" class="form-select" required></select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Titolo <span class="text-danger">*</span></label>
                            <input type="text" id="task_title" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Descrizione</label>
                            <textarea id="task_description" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Priorità</label>
                            <select id="task_priority" class="form-select">
                                <option value="low">Low</option>
                                <option value="medium" selected>Medium</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Stato</label>
                            <select id="task_status" class="form-select">
                                <option value="pending" selected>Pending</option>
                                <option value="in_progress">In Progress</option>
                                <option value="done">Done</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Scadenza <span class="text-danger">*</span></label>
                            <input type="date" id="task_due_date" class="form-control" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="button" class="btn btn-primary" onclick="saveTask()">Salva</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="app.js"></script>
    <script src="task.js"></script>
</body>
</html>
