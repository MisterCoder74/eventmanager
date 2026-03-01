const setupEventDetail = () => {
    const eventId = window.currentEventId;
    const tasksList = document.getElementById('tasksList');
    const taskForm = document.getElementById('taskForm');
    const uploadForm = document.getElementById('uploadForm');
    const uploadMessage = document.getElementById('uploadMessage');
    const servicesTableBody = document.getElementById('servicesTableBody');

    if (!eventId) return;

    let supplierMap = {};

    const loadSuppliers = async () => {
        try {
            const result = await apiRequest('api_suppliers.php?action=list');
            if (result.success) {
                supplierMap = result.data.reduce((acc, supplier) => {
                    acc[supplier.id] = supplier.name;
                    return acc;
                }, {});

                const select = document.getElementById('serviceSupplier');
                if (select) {
                    select.innerHTML = '<option value="">Seleziona fornitore</option>' +
                        result.data.map(supplier => `<option value="${supplier.id}">${escapeHtml(supplier.name)}</option>`).join('');
                }
            }
        } catch (error) {
            console.error('Error loading suppliers:', error);
        }
    };

    const loadSupplierServices = async (supplierId) => {
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
                    result.data.map(service => `
                        <option value="${service.id}" data-price="${service.price}">
                            ${escapeHtml(service.name)} - €${parseFloat(service.price).toFixed(2)}
                        </option>
                    `).join('');
            }
        } catch (error) {
            console.error('Error loading services:', error);
        }
    };

    const updateServicePrice = () => {
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

    const loadEventServices = async () => {
        if (!servicesTableBody) return;

        try {
            const result = await apiRequest('api_events.php?action=get&id=' + eventId);
            if (result.success && result.data) {
                displayServices(result.data.servizi || []);
                loadBudget();
            }
        } catch (error) {
            console.error('Error loading event services:', error);
        }
    };

    const displayServices = (servizi) => {
        if (!servicesTableBody) return;

        if (servizi.length === 0) {
            servicesTableBody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">Nessun servizio aggiunto</td></tr>';
            return;
        }

        servicesTableBody.innerHTML = servizi.map(servizio => {
            const supplierName = supplierMap[servizio.supplier_id] || servizio.supplier_id || '-';
            return `
                <tr>
                    <td><strong>${escapeHtml(servizio.name)}</strong></td>
                    <td>${escapeHtml(supplierName)}</td>
                    <td>€${parseFloat(servizio.price).toFixed(2)}</td>
                    <td>${getServiceStatusBadge(servizio.status)}</td>
                    <td><small>${escapeHtml(servizio.notes || '-')}</small></td>
                    <td>
                        <button class="btn btn-sm btn-primary" onclick="editService('${servizio.id}')">✏️</button>
                        <button class="btn btn-sm btn-danger" onclick="removeService('${servizio.id}')">🗑️</button>
                    </td>
                </tr>
            `;
        }).join('');
    };

    const loadBudget = async () => {
        try {
            const result = await apiRequest(`api_budget.php?action=get&event_id=${eventId}`);
            if (result.success && result.data) {
                displayBudget(result.data);
            }
        } catch (error) {
            console.error('Error loading budget:', error);
        }
    };

    const displayBudget = (budget) => {
        const displayBudgetClient = document.getElementById('displayBudgetClient');
        const displayBudgetPreventivo = document.getElementById('displayBudgetPreventivo');
        const displayPercentage = document.getElementById('displayPercentage');
        const summaryBudgetClient = document.getElementById('summaryBudgetClient');
        const summaryBudgetPreventivo = document.getElementById('summaryBudgetPreventivo');
        const summaryRemaining = document.getElementById('summaryRemaining');
        const summaryStatus = document.getElementById('summaryStatus');
        const budgetProgress = document.getElementById('budgetProgress');
        const progressText = document.getElementById('progressText');
        const budgetAlert = document.getElementById('budgetAlert');

        if (displayBudgetClient) displayBudgetClient.textContent = budget.budget_client.toFixed(2);
        if (displayBudgetPreventivo) displayBudgetPreventivo.textContent = budget.budget_preventivo.toFixed(2);
        if (displayPercentage) displayPercentage.textContent = budget.percentage.toFixed(1);
        if (summaryBudgetClient) summaryBudgetClient.textContent = budget.budget_client.toFixed(2);
        if (summaryBudgetPreventivo) summaryBudgetPreventivo.textContent = budget.budget_preventivo.toFixed(2);
        if (summaryRemaining) summaryRemaining.textContent = budget.remaining.toFixed(2);

        if (!budgetProgress) return;

        const progressBar = budgetProgress.querySelector('.progress-bar');
        if (!progressBar) return;

        progressBar.style.width = Math.min(budget.percentage, 100) + '%';
        if (progressText) {
            progressText.textContent = budget.percentage.toFixed(1) + '%';
        }

        progressBar.className = 'progress-bar';
        if (budgetAlert) {
            budgetAlert.className = 'alert';
        }

        if (budget.alert_level === 'red') {
            progressBar.classList.add('bg-danger');
            if (budgetAlert) budgetAlert.classList.add('bg-danger', 'text-white');
            if (summaryStatus) {
                summaryStatus.textContent = '🔴 Oltre Budget';
                summaryStatus.className = 'text-danger';
            }
        } else if (budget.alert_level === 'yellow' || budget.alert_level === 'orange') {
            progressBar.classList.add('bg-warning');
            if (budgetAlert) budgetAlert.classList.add('bg-warning');
            if (summaryStatus) {
                summaryStatus.textContent = '⚠️ Attenzione';
                summaryStatus.className = 'text-warning';
            }
        } else {
            progressBar.classList.add('bg-success');
            if (budgetAlert) budgetAlert.classList.add('bg-success');
            if (summaryStatus) {
                summaryStatus.textContent = '✅ OK';
                summaryStatus.className = 'text-success';
            }
        }

        if (summaryRemaining) {
            if (budget.over_budget) {
                summaryRemaining.textContent = '0.00';
                summaryRemaining.className = 'text-danger';
            } else {
                summaryRemaining.className = 'text-warning';
            }
        }
    };

    window.updateClientBudget = async function() {
        const budgetClientInput = document.getElementById('budgetClient');
        if (!budgetClientInput) return;

        try {
            const result = await apiRequest('api_budget.php?action=update_client_budget', 'POST', {
                event_id: eventId,
                budget_client: parseFloat(budgetClientInput.value || 0)
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

    window.updateBudgetThreshold = async function() {
        const thresholdInput = document.getElementById('budgetThreshold');
        if (!thresholdInput) return;

        try {
            const result = await apiRequest('api_budget.php?action=update_threshold', 'POST', {
                event_id: eventId,
                threshold: parseInt(thresholdInput.value, 10)
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

    window.addService = async function() {
        const supplierId = document.getElementById('serviceSupplier')?.value;
        const serviceId = document.getElementById('serviceSelect')?.value;
        const notes = document.getElementById('serviceNotes')?.value || '';

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
                const modalElement = document.getElementById('serviceModal');
                if (modalElement) {
                    bootstrap.Modal.getInstance(modalElement).hide();
                }
                const form = document.getElementById('serviceForm');
                if (form) form.reset();
                loadEventServices();
            } else {
                alert(result.error || 'Errore nell\'aggiunta del servizio');
            }
        } catch (error) {
            console.error('Error adding service:', error);
            alert('Errore di comunicazione con il server');
        }
    };

    window.editService = async function(serviceId) {
        try {
            const result = await apiRequest('api_events.php?action=get&id=' + eventId);
            if (result.success && result.data && result.data.servizi) {
                const servizio = result.data.servizi.find(service => service.id === serviceId);
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
                loadEventServices();
            } else {
                alert(result.error || 'Errore nell\'aggiornamento del servizio');
            }
        } catch (error) {
            console.error('Error updating service:', error);
            alert('Errore di comunicazione con il server');
        }
    };

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
                loadEventServices();
            } else {
                alert(result.error || 'Errore nella rimozione del servizio');
            }
        } catch (error) {
            console.error('Error removing service:', error);
            alert('Errore di comunicazione con il server');
        }
    };

    window.loadServices = loadSupplierServices;
    window.updateServicePrice = updateServicePrice;

    const loadTasks = async () => {
        if (!tasksList) return;

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
                } else if (typeof showMessage === 'function') {
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
    };

    window.addTask = async function() {
        if (!taskForm) return;

        const formData = new FormData(taskForm);
        const data = Object.fromEntries(formData.entries());
        data.event_id = eventId;

        const result = await apiRequest('api_tasks.php?action=create', 'POST', data);
        if (result.success) {
            taskForm.reset();
            bootstrap.Modal.getInstance(document.getElementById('taskModal')).hide();
            loadTasks();
        } else if (typeof showMessage === 'function') {
            showMessage(result.error || 'Errore salvataggio', 'danger');
        }
    };

    if (taskForm) {
        taskForm.addEventListener('submit', (event) => {
            event.preventDefault();
            window.addTask();
        });
    }

    if (uploadForm) {
        uploadForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            const formData = new FormData(uploadForm);
            if (uploadMessage) {
                uploadMessage.textContent = 'Caricamento...';
            }
            const response = await fetch('api_upload.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            if (result.success) {
                if (uploadMessage) {
                    uploadMessage.textContent = 'File caricato con successo.';
                }
                window.location.reload();
            } else if (uploadMessage) {
                uploadMessage.textContent = result.error || 'Errore upload';
            }
        });
    }

    loadSuppliers().then(loadEventServices);
    loadTasks();

    // Event detail edit modal functionality
    window.openEditEventModal = function() {
        const event = window.currentEventData;
        if (!event) return;

        document.getElementById('editEventId').value = event.id;
        document.getElementById('editEventTitle').value = event.title || '';
        document.getElementById('editEventType').value = event.type || '';
        document.getElementById('editEventDate').value = event.date || '';
        document.getElementById('editEventTime').value = event.time || '';
        document.getElementById('editEventLocation').value = event.location || '';
        document.getElementById('editEventGuestCount').value = event.guest_count || '';
        document.getElementById('editEventAddress').value = event.address || '';
        document.getElementById('editEventClient').value = event.client_id || '';
        document.getElementById('editEventStatus').value = event.status || 'planning';
        document.getElementById('editEventNotes').value = event.notes || '';

        new bootstrap.Modal(document.getElementById('editEventModal')).show();
    };

    window.saveEventChanges = async function() {
        const form = document.getElementById('editEventForm');
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());

        // Convert guest_count to integer
        if (data.guest_count) {
            data.guest_count = parseInt(data.guest_count, 10);
        } else {
            data.guest_count = 0;
        }

        try {
            const result = await apiRequest('api_events.php?action=update', 'POST', data);
            if (result.success) {
                bootstrap.Modal.getInstance(document.getElementById('editEventModal')).hide();
                // Reload the page to show updated data
                window.location.reload();
            } else {
                alert(result.error || 'Errore nel salvataggio delle modifiche');
            }
        } catch (error) {
            console.error('Error saving event changes:', error);
            alert('Errore di comunicazione con il server');
        }
    };
};

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
    setupEventDetail();
});
