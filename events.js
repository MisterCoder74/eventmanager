const setupEventsList = () => {
    const tableBody = document.querySelector('#eventsTable tbody');
    const form = document.getElementById('eventForm');
    const modalElement = document.getElementById('eventModal');
    if (!tableBody || !form || !modalElement) return;

    const modal = new bootstrap.Modal(modalElement);
    const clientMap = (window.clientsData || []).reduce((acc, client) => {
        acc[client.id] = client.name;
        return acc;
    }, {});

    const loadEvents = async () => {
        const result = await apiRequest('api_events.php?action=list');
        if (!result.success) {
            showMessage(result.error || 'Errore caricamento', 'danger');
            return;
        }
        tableBody.innerHTML = '';
        result.data.forEach(event => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${event.id}</td>
                <td><a href="event-detail.php?id=${event.id}">${event.title}</a></td>
                <td>${event.date}</td>
                <td>${event.location || '-'}</td>
                <td>${event.status}</td>
                <td class="table-actions">
                    <button class="btn btn-sm btn-outline-primary" data-action="edit" data-id="${event.id}">Modifica</button>
                    <button class="btn btn-sm btn-outline-danger" data-action="delete" data-id="${event.id}">Elimina</button>
                </td>
            `;
            tableBody.appendChild(row);
        });
    };

    tableBody.addEventListener('click', async (event) => {
        const button = event.target.closest('button');
        if (!button) return;
        const id = button.dataset.id;
        if (button.dataset.action === 'delete') {
            if (!confirm('Eliminare l\'evento?')) return;
            const result = await apiRequest('api_events.php?action=delete', 'POST', { id });
            if (result.success) {
                loadEvents();
            } else {
                showMessage(result.error || 'Errore eliminazione', 'danger');
            }
        }
        if (button.dataset.action === 'edit') {
            const result = await apiRequest('api_events.php?action=get&id=' + id);
            if (!result.success) {
                showMessage(result.error || 'Evento non trovato', 'danger');
                return;
            }
            const data = result.data;
            form.id.value = data.id;
            form.title.value = data.title;
            form.type.value = data.type || '';
            form.date.value = data.date;
            form.time.value = data.time || '';
            form.location.value = data.location;
            form.guest_count.value = data.guest_count || '';
            form.address.value = data.address || '';
            form.client_id.value = data.client_id;
            form.status.value = data.status;
            form.notes.value = data.notes;
            modal.show();
        }
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const data = Object.fromEntries(new FormData(form).entries());
        const action = data.id ? 'update' : 'create';
        const result = await apiRequest(`api_events.php?action=${action}`, 'POST', data);
        if (result.success) {
            form.reset();
            modal.hide();
            loadEvents();
        } else {
            showMessage(result.error || 'Errore salvataggio', 'danger');
        }
    });

    modalElement.addEventListener('hidden.bs.modal', () => form.reset());

    loadEvents();
};

const setupEventDetail = () => {
    const eventId = window.currentEventId;
    const tasksList = document.getElementById('tasksList');
    const taskForm = document.getElementById('taskForm');
    const uploadForm = document.getElementById('uploadForm');
    const uploadMessage = document.getElementById('uploadMessage');

    // Services and budget elements
    const servicesTableBody = document.getElementById('servicesTableBody');

    if (!eventId || !tasksList) return;

    // Load services for event
    async function loadServices() {
        if (!servicesTableBody) return;

        try {
            const result = await apiRequest('api_events.php?action=get&id=' + eventId);
            if (result.success && result.data) {
                const event = result.data;
                displayServices(event.servizi || []);
                loadBudget();
            }
        } catch (error) {
            console.error('Error loading services:', error);
        }
    }

    // Display services table
    function displayServices(servizi) {
        if (!servicesTableBody) return;

        if (servizi.length === 0) {
            servicesTableBody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">Nessun servizio aggiunto</td></tr>';
            return;
        }

        servicesTableBody.innerHTML = servizi.map(servizio => `
            <tr>
                <td><strong>${escapeHtml(servizio.name)}</strong></td>
                <td>${escapeHtml(servizio.supplier_id)}</td>
                <td>€${parseFloat(servizio.price).toFixed(2)}</td>
                <td>${getServiceStatusBadge(servizio.status)}</td>
                <td><small>${escapeHtml(servizio.notes || '-')}</small></td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick="editService('${servizio.id}')">✏️</button>
                    <button class="btn btn-sm btn-danger" onclick="removeService('${servizio.id}')">🗑️</button>
                </td>
            </tr>
        `).join('');
    }

    // Load suppliers for service modal
    async function loadSuppliers() {
        try {
            const result = await apiRequest('api_suppliers.php?action=list');
            if (result.success) {
                const select = document.getElementById('serviceSupplier');
                if (select) {
                    select.innerHTML = '<option value="">Seleziona fornitore</option>' +
                        result.data.map(s => `<option value="${s.id}">${escapeHtml(s.name)}</option>`).join('');
                }
            }
        } catch (error) {
            console.error('Error loading suppliers:', error);
        }
    }

    // Load services when supplier is selected
    window.loadServices = async function(supplierId) {
        const serviceSelect = document.getElementById('serviceSelect');
        const priceInput = document.getElementById('servicePrice');

        if (!serviceSelect || !priceInput) return;

        if (!supplierId) {
            serviceSelect.innerHTML = '<option value="">Seleziona servizio</option>';
            priceInput.value = '';
            return;
        }

        try {
            const result = await apiRequest(`api_services.php?action=by_supplier&supplier_id=${supplierId}`);
            if (result.success) {
                serviceSelect.innerHTML = '<option value="">Seleziona servizio</option>' +
                    result.data.map(s => `<option value="${s.id}" data-price="${s.price}">${escapeHtml(s.name)} - €${parseFloat(s.price).toFixed(2)}</option>`).join('');
            }
        } catch (error) {
            console.error('Error loading services:', error);
        }
    };

    // Update service price when service is selected
    window.updateServicePrice = function() {
        const serviceSelect = document.getElementById('serviceSelect');
        const priceInput = document.getElementById('servicePrice');

        if (!serviceSelect || !priceInput) return;

        const selectedOption = serviceSelect.options[serviceSelect.selectedIndex];
        if (selectedOption && selectedOption.value) {
            priceInput.value = selectedOption.dataset.price || 0;
        } else {
            priceInput.value = '';
        }
    };

    // Add service to event
    window.addService = async function() {
        const supplierId = document.getElementById('serviceSupplier').value;
        const serviceId = document.getElementById('serviceSelect').value;
        const notes = document.getElementById('serviceNotes').value;

        if (!supplierId || !serviceId) {
            alert('Seleziona fornitore e servizio');
            return;
        }

        try {
            const result = await apiRequest('api_events.php?action=add_service', 'POST', {
                event_id: eventId,
                supplier_id: supplierId,
                service_id: serviceId,
                notes: notes
            });

            if (result.success) {
                bootstrap.Modal.getInstance(document.getElementById('serviceModal')).hide();
                document.getElementById('serviceForm').reset();
                loadServices();
            } else {
                alert(result.error || 'Errore nell\'aggiunta del servizio');
            }
        } catch (error) {
            console.error('Error adding service:', error);
            alert('Errore di comunicazione con il server');
        }
    };

    // Edit service
    window.editService = async function(serviceId) {
        try {
            const result = await apiRequest('api_events.php?action=get&id=' + eventId);
            if (result.success && result.data && result.data.servizi) {
                const servizio = result.data.servizi.find(s => s.id === serviceId);
                if (servizio) {
                    document.getElementById('editServiceId').value = servizio.id;
                    document.getElementById('editServicePrice').value = servizio.price;
                    document.getElementById('editServiceStatus').value = servizio.status || 'pending';
                    document.getElementById('editServiceNotes').value = servizio.notes || '';
                    new bootstrap.Modal(document.getElementById('editServiceModal')).show();
                }
            }
        } catch (error) {
            console.error('Error loading service:', error);
        }
    };

    // Update service
    window.updateService = async function() {
        const serviceId = document.getElementById('editServiceId').value;
        const price = document.getElementById('editServicePrice').value;
        const status = document.getElementById('editServiceStatus').value;
        const notes = document.getElementById('editServiceNotes').value;

        try {
            const result = await apiRequest('api_events.php?action=update_service', 'POST', {
                event_id: eventId,
                service_id: serviceId,
                price: parseFloat(price),
                status: status,
                notes: notes
            });

            if (result.success) {
                bootstrap.Modal.getInstance(document.getElementById('editServiceModal')).hide();
                loadServices();
            } else {
                alert(result.error || 'Errore nell\'aggiornamento del servizio');
            }
        } catch (error) {
            console.error('Error updating service:', error);
            alert('Errore di comunicazione con il server');
        }
    };

    // Remove service
    window.removeService = async function(serviceId) {
        if (!confirm('Sei sicuro di voler rimuovere questo servizio?')) {
            return;
        }

        try {
            const result = await apiRequest('api_events.php?action=remove_service', 'POST', {
                event_id: eventId,
                service_id: serviceId
            });

            if (result.success) {
                loadServices();
            } else {
                alert(result.error || 'Errore nella rimozione del servizio');
            }
        } catch (error) {
            console.error('Error removing service:', error);
            alert('Errore di comunicazione con il server');
        }
    };

    // Load budget
    async function loadBudget() {
        try {
            const result = await apiRequest(`api_budget.php?action=get&event_id=${eventId}`);
            if (result.success && result.data) {
                const budget = result.data;
                displayBudget(budget);
            }
        } catch (error) {
            console.error('Error loading budget:', error);
        }
    }

    // Display budget
    function displayBudget(budget) {
        document.getElementById('displayBudgetClient').textContent = budget.budget_client.toFixed(2);
        document.getElementById('displayBudgetPreventivo').textContent = budget.budget_preventivo.toFixed(2);
        document.getElementById('displayPercentage').textContent = budget.percentage.toFixed(1);

        document.getElementById('summaryBudgetClient').textContent = budget.budget_client.toFixed(2);
        document.getElementById('summaryBudgetPreventivo').textContent = budget.budget_preventivo.toFixed(2);
        document.getElementById('summaryRemaining').textContent = budget.remaining.toFixed(2);

        const progressBar = document.getElementById('budgetProgress').querySelector('.progress-bar');
        const progressText = document.getElementById('progressText');
        const budgetAlert = document.getElementById('budgetAlert');

        progressBar.style.width = Math.min(budget.percentage, 100) + '%';
        progressText.textContent = budget.percentage.toFixed(1) + '%';

        // Set colors based on alert level
        progressBar.className = 'progress-bar';
        budgetAlert.className = 'alert';

        if (budget.alert_level === 'red') {
            progressBar.classList.add('bg-danger');
            budgetAlert.classList.add('bg-danger', 'text-white');
            document.getElementById('summaryStatus').textContent = '🔴 Oltre Budget';
            document.getElementById('summaryStatus').className = 'text-danger';
        } else if (budget.alert_level === 'yellow' || budget.alert_level === 'orange') {
            progressBar.classList.add('bg-warning');
            budgetAlert.classList.add('bg-warning');
            document.getElementById('summaryStatus').textContent = '⚠️ Attenzione';
            document.getElementById('summaryStatus').className = 'text-warning';
        } else {
            progressBar.classList.add('bg-success');
            budgetAlert.classList.add('bg-success');
            document.getElementById('summaryStatus').textContent = '✅ OK';
            document.getElementById('summaryStatus').className = 'text-success';
        }

        if (budget.over_budget) {
            document.getElementById('summaryRemaining').textContent = '0.00';
            document.getElementById('summaryRemaining').className = 'text-danger';
        } else {
            document.getElementById('summaryRemaining').className = 'text-warning';
        }
    }

    // Update client budget
    window.updateClientBudget = async function() {
        const budgetClient = document.getElementById('budgetClient').value;

        try {
            const result = await apiRequest('api_budget.php?action=update_client_budget', 'POST', {
                event_id: eventId,
                budget_client: parseFloat(budgetClient)
            });

            if (result.success) {
                loadBudget();
            } else {
                alert(result.error || 'Errore nell\'aggiornamento del budget');
            }
        } catch (error) {
            console.error('Error updating budget:', error);
            alert('Errore di comunicazione con il server');
        }
    };

    // Update budget threshold
    window.updateBudgetThreshold = async function() {
        const threshold = document.getElementById('budgetThreshold').value;

        try {
            const result = await apiRequest('api_budget.php?action=update_threshold', 'POST', {
                event_id: eventId,
                threshold: parseInt(threshold)
            });

            if (result.success) {
                loadBudget();
            } else {
                alert(result.error || 'Errore nell\'aggiornamento della soglia');
            }
        } catch (error) {
            console.error('Error updating threshold:', error);
            alert('Errore di comunicazione con il server');
        }
    };

    // Load tasks
    async function loadTasks() {
        const result = await apiRequest(`api_tasks.php?action=list&event_id=${eventId}`);
        tasksList.innerHTML = '';
        if (!result.success) {
            tasksList.innerHTML = '<li class="list-group-item text-muted">Errore caricamento</li>';
            return;
        }
        if (result.data.length === 0) {
            tasksList.innerHTML = '<li class="list-group-item text-muted">Nessun task</li>';
            return;
        }
        result.data.forEach(task => {
            const item = document.createElement('li');
            item.className = 'list-group-item d-flex justify-content-between align-items-center';
            item.innerHTML = `
                <div>
                    <strong>${escapeHtml(task.title)}</strong><br>
                    <small>${task.priority ? getPriorityBadge(task.priority) + ' | ' : ''}Scadenza: ${task.due_date}</small>
                    ${task.description ? `<br><small class="text-muted">${escapeHtml(task.description)}</small>` : ''}
                </div>
                <div class="d-flex gap-2">
                    <span class="badge ${getTaskStatusClass(task.status)}">${task.status}</span>
                    <button class="btn btn-sm btn-outline-danger" data-id="${task.id}">X</button>
                </div>
            `;
            item.querySelector('button').addEventListener('click', async () => {
                const resultDelete = await apiRequest('api_tasks.php?action=delete', 'POST', { id: task.id });
                if (resultDelete.success) {
                    loadTasks();
                } else {
                    showMessage(resultDelete.error || 'Errore eliminazione', 'danger');
                }
            });
            item.addEventListener('dblclick', async () => {
                const newStatus = task.status === 'done' ? 'pending' : 'done';
                const resultUpdate = await apiRequest('api_tasks.php?action=update', 'POST', { id: task.id, status: newStatus });
                if (resultUpdate.success) {
                    loadTasks();
                }
            });
            tasksList.appendChild(item);
        });
    }

    // Add task
    window.addTask = async function() {
        const formData = new FormData(taskForm);
        const data = Object.fromEntries(formData.entries());
        data.event_id = eventId;

        const result = await apiRequest('api_tasks.php?action=create', 'POST', data);
        if (result.success) {
            taskForm.reset();
            bootstrap.Modal.getInstance(document.getElementById('taskModal')).hide();
            loadTasks();
        } else {
            showMessage(result.error || 'Errore salvataggio', 'danger');
        }
    };

    if (taskForm) {
        taskForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            window.addTask();
        });
    }

    if (uploadForm) {
        uploadForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            const formData = new FormData(uploadForm);
            uploadMessage.textContent = 'Caricamento...';
            const response = await fetch('api_upload.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            if (result.success) {
                uploadMessage.textContent = 'File caricato con successo.';
                window.location.reload();
            } else {
                uploadMessage.textContent = result.error || 'Errore upload';
            }
        });
    }

    // Initialize
    loadSuppliers();
    loadTasks();
    loadServices();
};

// Helper functions
function getServiceStatusBadge(status) {
    const badges = {
        'pending': '<span class="badge bg-secondary">Pending</span>',
        'confirmed': '<span class="badge bg-primary">Confirmed</span>',
        'paid': '<span class="badge bg-success">Paid</span>',
        'cancelled': '<span class="badge bg-danger">Cancelled</span>'
    };
    return badges[status] || '<span class="badge bg-secondary">' + escapeHtml(status) + '</span>';
}

function getTaskStatusClass(status) {
    const classes = {
        'pending': 'bg-secondary',
        'in_progress': 'bg-primary',
        'done': 'bg-success',
        'cancelled': 'bg-danger'
    };
    return classes[status] || 'bg-secondary';
}

function getPriorityBadge(priority) {
    const badges = {
        'low': '<span class="badge bg-info">Low</span>',
        'medium': '<span class="badge bg-warning text-dark">Medium</span>',
        'high': '<span class="badge bg-danger">High</span>',
        'urgent': '<span class="badge bg-dark">Urgent</span>'
    };
    return badges[priority] || '';
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

document.addEventListener('DOMContentLoaded', () => {
    setupEventsList();
    setupEventDetail();
});
