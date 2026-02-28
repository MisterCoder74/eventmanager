const apiRequest = async (url, method = 'GET', data = null) => {
    const options = { method, headers: {} };
    if (data) {
        options.headers['Content-Type'] = 'application/json';
        options.body = JSON.stringify(data);
    }
    const response = await fetch(url, options);
    return response.json();
};

const showMessage = (message, type = 'success') => {
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.textContent = message;
    document.body.prepend(alert);
    setTimeout(() => alert.remove(), 3000);
};

const setupClients = () => {
    const tableBody = document.querySelector('#clientsTable tbody');
    const form = document.getElementById('clientForm');
    const modalElement = document.getElementById('clientModal');
    if (!tableBody || !form || !modalElement) return;

    const modal = new bootstrap.Modal(modalElement);

    const loadClients = async () => {
        const result = await apiRequest('api_clients.php?action=list');
        if (!result.success) {
            showMessage(result.error || 'Errore caricamento', 'danger');
            return;
        }
        tableBody.innerHTML = '';
        result.data.forEach(client => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${client.id}</td>
                <td>${client.name}</td>
                <td>${client.email || '-'}</td>
                <td>${client.phone || '-'}</td>
                <td>${client.company || '-'}</td>
                <td class="table-actions">
                    <button class="btn btn-sm btn-outline-primary" data-action="edit" data-id="${client.id}">Modifica</button>
                    <button class="btn btn-sm btn-outline-danger" data-action="delete" data-id="${client.id}">Elimina</button>
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
            if (!confirm('Eliminare il cliente?')) return;
            const result = await apiRequest('api_clients.php?action=delete', 'POST', { id });
            if (result.success) {
                loadClients();
            } else {
                showMessage(result.error || 'Errore eliminazione', 'danger');
            }
        }
        if (button.dataset.action === 'edit') {
            const result = await apiRequest('api_clients.php?action=list');
            const client = result.data.find(item => item.id === id);
            if (!client) return;
            form.id.value = client.id;
            form.name.value = client.name;
            form.email.value = client.email;
            form.phone.value = client.phone;
            form.company.value = client.company;
            form.notes.value = client.notes;
            modal.show();
        }
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const data = Object.fromEntries(new FormData(form).entries());
        const action = data.id ? 'update' : 'create';
        const result = await apiRequest(`api_clients.php?action=${action}`, 'POST', data);
        if (result.success) {
            form.reset();
            modal.hide();
            loadClients();
        } else {
            showMessage(result.error || 'Errore salvataggio', 'danger');
        }
    });

    modalElement.addEventListener('hidden.bs.modal', () => form.reset());

    loadClients();
};

document.addEventListener('DOMContentLoaded', () => {
    setupClients();
});
