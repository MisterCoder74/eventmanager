// Global variables
let allSuppliers = [];
let allServices = [];
let currentSupplierId = null;

// Load suppliers
async function loadSuppliers() {
    try {
        const response = await fetch('api_suppliers.php?action=list', { cache: 'no-store' });
        const result = await response.json();

        if (result.success) {
            allSuppliers = result.data;
            displaySuppliers();
        }
    } catch (error) {
        console.error('Error loading suppliers:', error);
    }
}

// Display suppliers in table
function displaySuppliers() {
    const tbody = document.querySelector('#suppliersTable tbody');
    tbody.innerHTML = allSuppliers.map(supplier => `
        <tr>
            <td>${escapeHtml(supplier.id)}</td>
            <td><strong>${escapeHtml(supplier.name)}</strong></td>
            <td>${escapeHtml(supplier.type || '-')}</td>
            <td>${escapeHtml(supplier.email || '-')}</td>
            <td>${escapeHtml(supplier.phone || '-')}</td>
            <td>${escapeHtml(supplier.contact_person || '-')}</td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="editSupplier('${supplier.id}')">✏️ Modifica</button>
                <button class="btn btn-sm btn-info" onclick="viewServices('${supplier.id}')">📋 Servizi</button>
                <button class="btn btn-sm btn-danger" onclick="deleteSupplier('${supplier.id}')">🗑️ Elimina</button>
            </td>
        </tr>
    `).join('');
}

// Save supplier (create or update)
document.getElementById('supplierForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    // Prevenzione doppio submit
    const submitBtn = this.querySelector('button[type="submit"]');
    if (submitBtn.disabled) return;
    submitBtn.disabled = true;
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Salvataggio...';

    const formData = new FormData(this);
    const data = Object.fromEntries(formData.entries());
    const supplierId = document.getElementById('supplierId').value;

    const action = supplierId ? 'update' : 'create';

    try {
        const response = await fetch(`api_suppliers.php?action=${action}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            bootstrap.Modal.getInstance(document.getElementById('supplierModal')).hide();
            loadSuppliers();
        } else {
            alert(result.error || 'Errore nel salvataggio');
        }
    } catch (error) {
        console.error('Error saving supplier:', error);
        alert('Errore di comunicazione con il server');
    } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
    }
});

// Edit supplier
function editSupplier(id) {
    const supplier = allSuppliers.find(s => s.id === id);
    if (!supplier) return;

    const supplierIdInput = document.getElementById('supplierId');
    if (!supplierIdInput) {
        console.error('Elemento supplierId non trovato');
        return;
    }
    
    supplierIdInput.value = supplier.id;
    
    const nameInput = document.querySelector('[name="name"]');
    if (nameInput) nameInput.value = supplier.name;
    
    const typeInput = document.querySelector('[name="type"]');
    if (typeInput) typeInput.value = supplier.type || '';
    
    const emailInput = document.querySelector('[name="email"]');
    if (emailInput) emailInput.value = supplier.email || '';
    
    const phoneInput = document.querySelector('[name="phone"]');
    if (phoneInput) phoneInput.value = supplier.phone || '';
    
    const addressInput = document.querySelector('[name="address"]');
    if (addressInput) addressInput.value = supplier.address || '';
    
    const contactPersonInput = document.querySelector('[name="contact_person"]');
    if (contactPersonInput) contactPersonInput.value = supplier.contact_person || '';
    
    const websiteInput = document.querySelector('[name="website"]');
    if (websiteInput) websiteInput.value = supplier.website || '';
    
    const notesInput = document.querySelector('[name="notes"]');
    if (notesInput) notesInput.value = supplier.notes || '';

    document.getElementById('supplierModalTitle').textContent = 'Modifica Fornitore';
    new bootstrap.Modal(document.getElementById('supplierModal')).show();
}

// Delete supplier
async function deleteSupplier(id) {
    if (!confirm('Sei sicuro di voler eliminare questo fornitore?')) {
        return;
    }

    try {
        const response = await fetch('api_suppliers.php?action=delete', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id })
        });

        const result = await response.json();

        if (result.success) {
            loadSuppliers();
        } else {
            alert(result.error || 'Errore nell\'eliminazione');
        }
    } catch (error) {
        console.error('Error deleting supplier:', error);
        alert('Errore di comunicazione con il server');
    }
}

// View supplier services
async function viewServices(supplierId) {
    currentSupplierId = supplierId;
    const supplier = allSuppliers.find(s => s.id === supplierId);

    if (supplier) {
        document.getElementById('supplierServicesTitle').textContent = `Servizi offerti da ${escapeHtml(supplier.name)}`;
    }

    document.getElementById('currentSupplierId').value = supplierId;

    try {
        const response = await fetch(`api_services.php?by_supplier=true&supplier_id=${supplierId}`, { cache: 'no-store' });
        const result = await response.json();

        if (result.success) {
            allServices = result.data;
            displaySupplierServices();
        }
    } catch (error) {
        console.error('Error loading services:', error);
    }

    new bootstrap.Modal(document.getElementById('servicesModal')).show();
}

// Display supplier services
function displaySupplierServices() {
    const tbody = document.getElementById('supplierServicesTable');

    if (allServices.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center">Nessun servizio</td></tr>';
        return;
    }

    tbody.innerHTML = allServices.map(service => `
        <tr>
            <td><strong>${escapeHtml(service.name)}</strong></td>
            <td>${escapeHtml(service.category)}</td>
            <td>€${parseFloat(service.price).toFixed(2)}</td>
            <td>${escapeHtml(service.description || '-')}</td>
            <td>${escapeHtml(service.notes || '-')}</td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="editSupplierService('${service.id}')">✏️</button>
                <button class="btn btn-sm btn-danger" onclick="deleteSupplierService('${service.id}')">🗑️</button>
            </td>
        </tr>
    `).join('');
}

// Save supplier service
async function saveSupplierService() {
    const serviceId = document.getElementById('supplierServiceId').value;
    const name = document.getElementById('supplierServiceName').value.trim();
    const category = document.getElementById('supplierServiceCategory').value;
    const price = document.getElementById('supplierServicePrice').value;
    const description = document.getElementById('supplierServiceDescription').value.trim();
    const notes = document.getElementById('supplierServiceNotes').value.trim();

    if (!name || !category || price === '') {
        alert('Nome, categoria e prezzo sono obbligatori');
        return;
    }

    const data = {
        supplier_id: currentSupplierId,
        name: name,
        category: category,
        price: parseFloat(price),
        description: description,
        notes: notes
    };

    const action = serviceId ? 'update' : 'create';
    let url = `api_services.php?action=${action}`;

    if (serviceId) {
        data.id = serviceId;
    }

    try {
        const response = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            bootstrap.Modal.getInstance(document.getElementById('serviceSupplierModal')).hide();
            viewServices(currentSupplierId);
        } else {
            alert(result.error || 'Errore nel salvataggio del servizio');
        }
    } catch (error) {
        console.error('Error saving service:', error);
        alert('Errore di comunicazione con il server');
    }
}

// Edit supplier service
function editSupplierService(serviceId) {
    const service = allServices.find(s => s.id === serviceId);
    if (!service) return;

    document.getElementById('supplierServiceId').value = service.id;
    document.getElementById('supplierServiceName').value = service.name;
    document.getElementById('supplierServiceCategory').value = service.category;
    document.getElementById('supplierServicePrice').value = service.price;
    document.getElementById('supplierServiceDescription').value = service.description || '';
    document.getElementById('supplierServiceNotes').value = service.notes || '';

    document.getElementById('serviceSupplierModalTitle').textContent = 'Modifica Servizio';
    new bootstrap.Modal(document.getElementById('serviceSupplierModal')).show();
}

// Delete supplier service
async function deleteSupplierService(serviceId) {
    if (!confirm('Sei sicuro di voler eliminare questo servizio?')) {
        return;
    }

    try {
        const response = await fetch(`api_services.php?action=delete`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: serviceId })
        });

        const result = await response.json();

        if (result.success) {
            viewServices(currentSupplierId);
        } else {
            alert(result.error || 'Errore nell\'eliminazione del servizio');
        }
    } catch (error) {
        console.error('Error deleting service:', error);
        alert('Errore di comunicazione con il server');
    }
}

// Reset form when supplier modal opens
document.getElementById('supplierModal').addEventListener('show.bs.modal', function(event) {
    const button = event.relatedTarget;
    if (button && !button.getAttribute('onclick')) {
        // New supplier button
        document.getElementById('supplierForm').reset();
        document.getElementById('supplierId').value = '';
        document.getElementById('supplierModalTitle').textContent = 'Nuovo Fornitore';
    }
});

// Reset form when service modal opens
document.getElementById('serviceSupplierModal').addEventListener('show.bs.modal', function(event) {
    const button = event.relatedTarget;
    if (button && !button.getAttribute('onclick')) {
        // New service button
        document.getElementById('supplierServiceForm').reset();
        document.getElementById('supplierServiceId').value = '';
        document.getElementById('serviceSupplierModalTitle').textContent = 'Nuovo Servizio';
    }
});

// Helper: Escape HTML
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    loadSuppliers();
});
