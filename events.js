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
            form.date.value = data.date;
            form.location.value = data.location;
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
    const budgetList = document.getElementById('budgetList');
    const taskForm = document.getElementById('taskForm');
    const budgetForm = document.getElementById('budgetForm');
    const uploadForm = document.getElementById('uploadForm');
    const uploadMessage = document.getElementById('uploadMessage');

    if (!eventId || !tasksList || !budgetList) return;

    const loadTasks = async () => {
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
                    <strong>${task.title}</strong><br>
                    <small>Scadenza: ${task.due_date}</small>
                </div>
                <div class="d-flex gap-2">
                    <span class="badge ${task.status === 'done' ? 'bg-success' : 'bg-warning'}">${task.status}</span>
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
    };

    const loadBudget = async () => {
        const result = await apiRequest(`api_budget.php?action=list&event_id=${eventId}`);
        budgetList.innerHTML = '';
        if (!result.success) {
            budgetList.innerHTML = '<li class="list-group-item text-muted">Errore caricamento</li>';
            return;
        }
        if (result.data.length === 0) {
            budgetList.innerHTML = '<li class="list-group-item text-muted">Nessuna voce budget</li>';
            return;
        }
        result.data.forEach(item => {
            const li = document.createElement('li');
            li.className = 'list-group-item d-flex justify-content-between align-items-center';
            li.innerHTML = `
                <div>
                    <strong>${item.description}</strong><br>
                    <small>${item.type}</small>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <span>€${parseFloat(item.amount).toFixed(2)}</span>
                    <button class="btn btn-sm btn-outline-danger" data-id="${item.id}">X</button>
                </div>
            `;
            li.querySelector('button').addEventListener('click', async () => {
                const resultDelete = await apiRequest('api_budget.php?action=delete', 'POST', { id: item.id });
                if (resultDelete.success) {
                    loadBudget();
                } else {
                    showMessage(resultDelete.error || 'Errore eliminazione', 'danger');
                }
            });
            budgetList.appendChild(li);
        });
    };

    if (taskForm) {
        taskForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            const data = Object.fromEntries(new FormData(taskForm).entries());
            const result = await apiRequest('api_tasks.php?action=create', 'POST', data);
            if (result.success) {
                taskForm.reset();
                loadTasks();
            } else {
                showMessage(result.error || 'Errore salvataggio', 'danger');
            }
        });
    }

    if (budgetForm) {
        budgetForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            const data = Object.fromEntries(new FormData(budgetForm).entries());
            const result = await apiRequest('api_budget.php?action=create', 'POST', data);
            if (result.success) {
                budgetForm.reset();
                loadBudget();
            } else {
                showMessage(result.error || 'Errore salvataggio', 'danger');
            }
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

    loadTasks();
    loadBudget();
};

document.addEventListener('DOMContentLoaded', () => {
    setupEventsList();
    setupEventDetail();
});
