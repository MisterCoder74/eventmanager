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
    <title>Fornitori - Gestionale Eventi</title>
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
                    <li class="nav-item"><a class="nav-link active" href="fornitori.php">Fornitori</a></li>
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
            <h1>Fornitori</h1>
            <div class="d-flex gap-2">
                <a href="export.php?dataset=suppliers&format=csv" class="btn btn-outline-secondary">Export CSV</a>
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#supplierModal">➕ Nuovo Fornitore</button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-striped" id="suppliersTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Tipo</th>
                        <th>Email</th>
                        <th>Telefono</th>
                        <th>Contatto</th>
                        <th>Azioni</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <!-- Modal Nuovo/Modifica Fornitore -->
    <div class="modal fade" id="supplierModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="supplierModalTitle">Nuovo Fornitore</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="supplierForm">
                        <input type="hidden" name="id" id="supplierId">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nome <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tipo</label>
                                <select class="form-select" name="type">
                                    <option value="">Seleziona tipo</option>
                                    <option value="Venue">Venue</option>
                                    <option value="Catering">Catering</option>
                                    <option value="Photography">Photography</option>
                                    <option value="Music">Music</option>
                                    <option value="Decoration">Decoration</option>
                                    <option value="Attire">Attire</option>
                                    <option value="Favors">Favors</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Telefono</label>
                                <input type="text" class="form-control" name="phone">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Indirizzo</label>
                            <input type="text" class="form-control" name="address">
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Persona di Contatto</label>
                                <input type="text" class="form-control" name="contact_person">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Website</label>
                                <input type="text" class="form-control" name="website">
                            </div>
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

    <!-- Modal Servizi Fornitore -->
    <div class="modal fade" id="servicesModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Servizi Offerti</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="currentSupplierId">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 id="supplierServicesTitle"></h6>
                        <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#serviceSupplierModal">
                            ➕ Aggiungi Servizio
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>Categoria</th>
                                    <th>Prezzo</th>
                                    <th>Descrizione</th>
                                    <th>Note</th>
                                    <th>Azioni</th>
                                </tr>
                            </thead>
                            <tbody id="supplierServicesTable">
                                <tr>
                                    <td colspan="6" class="text-center">Nessun servizio</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Aggiungi/Modifica Servizio per Fornitore -->
    <div class="modal fade" id="serviceSupplierModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="serviceSupplierModalTitle">Nuovo Servizio</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="serviceSupplierForm">
                        <input type="hidden" id="supplierServiceId">
                        <div class="mb-3">
                            <label class="form-label">Nome <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="supplierServiceName" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Categoria <span class="text-danger">*</span></label>
                            <select class="form-select" id="supplierServiceCategory" required>
                                <option value="">Seleziona categoria</option>
                                <option value="Venue">Venue</option>
                                <option value="Food & Beverage">Food & Beverage</option>
                                <option value="Decoration">Decoration</option>
                                <option value="Photography">Photography</option>
                                <option value="Music">Music</option>
                                <option value="Attire">Attire</option>
                                <option value="Favors">Favors</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Prezzo (€) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="supplierServicePrice" step="0.01" min="0" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Descrizione</label>
                            <textarea class="form-control" id="supplierServiceDescription" rows="2"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Note</label>
                            <textarea class="form-control" id="supplierServiceNotes" rows="2"></textarea>
                        </div>
                        <button type="button" class="btn btn-primary w-100" onclick="saveSupplierService()">Salva</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="app.js"></script>
    <script src="suppliers.js"></script>
</body>
</html>
