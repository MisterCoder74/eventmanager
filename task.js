// Global variables
let allTasks = [];
let allEvents = [];

// Load events into selects
async function loadEvents() {
    try {
        const response = await fetch('api_events.php?action=list', { cache: 'no-store' });
        const result = await response.json();

        if (result.success) {
            allEvents = result.data;
            const selectEvent = document.getElementById('task_event');
            const filterEvent = document.getElementById('filter-event');

            let options = '<option value="">Seleziona evento</option>';
            let filterOptions = '<option value="">Tutti gli eventi</option>';

            result.data.forEach(event => {
                options += `<option value="${event.id}">${event.title} (${event.date})</option>`;
                filterOptions += `<option value="${event.id}">${event.title}</option>`;
            });

            selectEvent.innerHTML = options;
            filterEvent.innerHTML = filterOptions;
        }
    } catch (error) {
        console.error('Error loading events:', error);
    }
}

// Load all tasks
async function loadTasks() {
    try {
        const response = await fetch('api_tasks.php?action=list', { cache: 'no-store' });
        const result = await response.json();

        if (result.success) {
            allTasks = result.data;
            applyFilters();
        }
    } catch (error) {
        console.error('Error loading tasks:', error);
        document.getElementById('tasksTableBody').innerHTML =
            '<tr><td colspan="6" class="text-center text-danger">Errore nel caricamento dei task</td></tr>';
    }
}

// Filter tasks
function applyFilters() {
    const eventFilter = document.getElementById('filter-event').value;
    const statusFilter = document.getElementById('filter-status').value;
    const priorityFilter = document.getElementById('filter-priority').value;
    const dueFilter = document.getElementById('filter-due').value;

    let filteredTasks = [...allTasks];

    if (eventFilter) {
        filteredTasks = filteredTasks.filter(task => task.event_id === eventFilter);
    }

    if (statusFilter) {
        filteredTasks = filteredTasks.filter(task => task.status === statusFilter);
    }

    if (priorityFilter) {
        filteredTasks = filteredTasks.filter(task => task.priority === priorityFilter);
    }

    if (dueFilter) {
        filteredTasks = filteredTasks.filter(task => task.due_date === dueFilter);
    }

    displayTasks(filteredTasks);
}

function filterTasks() {
    applyFilters();
}

// Save task (create or update)
async function saveTask() {
    const taskId = document.getElementById('task_id').value;
    const event_id = document.getElementById('task_event').value;
    const title = document.getElementById('task_title').value.trim();
    const description = document.getElementById('task_description').value.trim();
    const priority = document.getElementById('task_priority').value;
    const status = document.getElementById('task_status').value;
    const due_date = document.getElementById('task_due_date').value;

    // Validation
    if (!event_id) {
        alert('Seleziona un evento');
        return;
    }
    if (!title) {
        alert('Inserisci il titolo del task');
        return;
    }
    if (!due_date) {
        alert('Inserisci la data di scadenza');
        return;
    }

    const taskData = {
        event_id: event_id,
        title: title,
        description: description,
        priority: priority,
        status: status,
        due_date: due_date
    };

    const action = taskId ? 'update' : 'create';
    const url = `api_tasks.php?action=${action}`;

    if (taskId) {
        taskData.id = taskId;
    }

    try {
        const response = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(taskData)
        });

        const result = await response.json();

        if (result.success) {
            bootstrap.Modal.getInstance(document.getElementById('taskModal')).hide();
            loadTasks();
        } else {
            alert(result.error || 'Errore nel salvataggio del task');
        }
    } catch (error) {
        console.error('Error saving task:', error);
        alert('Errore di comunicazione con il server');
    }
}

// Edit task
function editTask(taskId) {
    const task = allTasks.find(t => t.id === taskId);
    if (!task) return;

    document.getElementById('task_id').value = task.id;
    document.getElementById('task_event').value = task.event_id;
    document.getElementById('task_title').value = task.title;
    document.getElementById('task_description').value = task.description || '';
    document.getElementById('task_priority').value = task.priority || 'medium';
    document.getElementById('task_status').value = task.status || 'pending';
    document.getElementById('task_due_date').value = task.due_date;

    document.getElementById('taskModalTitle').textContent = 'Modifica Task';
    new bootstrap.Modal(document.getElementById('taskModal')).show();
}

// Delete task
async function deleteTask(taskId) {
    if (!confirm('Sei sicuro di voler eliminare questo task?')) {
        return;
    }

    try {
        const response = await fetch('api_tasks.php?action=delete', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: taskId })
        });

        const result = await response.json();

        if (result.success) {
            loadTasks();
        } else {
            alert(result.error || 'Errore nell\'eliminazione del task');
        }
    } catch (error) {
        console.error('Error deleting task:', error);
        alert('Errore di comunicazione con il server');
    }
}

// Display tasks in table
function displayTasks(tasks) {
    const tbody = document.getElementById('tasksTableBody');

    if (tasks.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">Nessun task trovato</td></tr>';
        return;
    }

    tbody.innerHTML = tasks.map(task => `
        <tr>
            <td>
                <strong>${escapeHtml(task.title)}</strong>
                ${task.description ? `<br><small class="text-muted">${escapeHtml(task.description)}</small>` : ''}
            </td>
            <td>${getEventTitle(task.event_id)}</td>
            <td>${getTaskStatusBadge(task.status)}</td>
            <td>${getPriorityBadge(task.priority)}</td>
            <td>${formatDate(task.due_date)}</td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="editTask('${task.id}')">
                    ✏️ Modifica
                </button>
                <button class="btn btn-sm btn-danger" onclick="deleteTask('${task.id}')">
                    🗑️ Elimina
                </button>
                <a href="event-detail.php?id=${task.event_id}" class="btn btn-sm btn-outline-secondary" target="_blank">
                    🔗 Evento
                </a>
            </td>
        </tr>
    `).join('');
}

// Helper: Get event title by ID
function getEventTitle(eventId) {
    const event = allEvents.find(e => e.id === eventId);
    return event ? escapeHtml(event.title) : eventId;
}

// Helper: Get task status badge
function getTaskStatusBadge(status) {
    const badges = {
        'pending': '<span class="badge bg-secondary">Pending</span>',
        'in_progress': '<span class="badge bg-primary">In Progress</span>',
        'done': '<span class="badge bg-success">Done</span>',
        'cancelled': '<span class="badge bg-danger">Cancelled</span>'
    };
    return badges[status] || '<span class="badge bg-secondary">' + escapeHtml(status) + '</span>';
}

// Helper: Get priority badge
function getPriorityBadge(priority) {
    const badges = {
        'low': '<span class="badge bg-info">Low</span>',
        'medium': '<span class="badge bg-warning text-dark">Medium</span>',
        'high': '<span class="badge bg-danger">High</span>',
        'urgent': '<span class="badge bg-dark">Urgent</span>'
    };
    return badges[priority] || '<span class="badge bg-secondary">' + escapeHtml(priority) + '</span>';
}

// Helper: Format date
function formatDate(dateStr) {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    return date.toLocaleDateString('it-IT');
}

// Helper: Escape HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Reset form when modal opens
document.addEventListener('DOMContentLoaded', function() {
    loadEvents();
    loadTasks();

    const taskModal = document.getElementById('taskModal');
    taskModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        if (button && !button.getAttribute('onclick')) {
            // New task button
            document.getElementById('taskForm').reset();
            document.getElementById('task_id').value = '';
            document.getElementById('taskModalTitle').textContent = 'Nuovo Task';
        }
    });
});
