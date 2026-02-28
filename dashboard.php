<?php
require_once __DIR__ . '/auth.php';
requireAuth();
require_once __DIR__ . '/stats.php';
require_once __DIR__ . '/notifications.php';

if (isset($_GET['logout'])) {
    logout();
}

$stats = getDashboardStats();
$due_tasks = getDueTasks(3);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Gestionale Eventi</title>
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
                    <li class="nav-item"><a class="nav-link active" href="dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="eventi.php">Eventi</a></li>
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
        <h1 class="mb-4">Dashboard</h1>

        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <h5 class="card-title">Totale Eventi</h5>
                        <p class="stat-number"><?php echo $stats['total_events']; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <h5 class="card-title">Eventi Prossimi</h5>
                        <p class="stat-number"><?php echo $stats['upcoming_events']; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <h5 class="card-title">Task Pendenti</h5>
                        <p class="stat-number"><?php echo $stats['pending_tasks']; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <h5 class="card-title">Budget Totale</h5>
                        <p class="stat-number">€<?php echo number_format($stats['total_planned_budget'], 2); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($due_tasks)): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="card alert-warning">
                    <div class="card-body">
                        <h5 class="card-title">⚠️ Task in Scadenza</h5>
                        <ul>
                            <?php foreach ($due_tasks as $item): ?>
                            <li>
                                <strong><?php echo htmlspecialchars($item['task']['title']); ?></strong>
                                (Evento: <?php echo htmlspecialchars($item['event']['title']); ?>) -
                                Scadenza: <?php echo htmlspecialchars($item['task']['due_date']); ?>
                                (<?php echo $item['days_until_due']; ?> giorni)
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="row mb-4">
            <div class="col-12">
                <a href="eventi.php?action=new" class="btn btn-success btn-lg">➕ Nuovo Evento</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="dashboard.js"></script>
</body>
</html>
