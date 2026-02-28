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
        } else {
            alert(result.error || 'Errore caricamento fornitori');
        }
    } catch (error) {
        console.error('Error loading suppliers:', error);
        alert('Errore di comunicazione con il server');
    }
}

// Display suppliers in table
function displaySuppliers() {
    const tbody = document.querySelector('#suppliersTable tbody');
    if (!tbody) {
        console.error('Elemento #suppliersTable tbody non trovato');
        return;
    }

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

// Edit supplier
function editSupplier(id) {
    const supplier = allSuppliers.find(s => s.id === id);
    if (!supplier) {
        console.error('Fornitore non trovato:', id);
        return;
    }

    const supplierIdInput = document.getElementById('supplierId');
    if (!supplierIdInput) {
        console.error('Elemento #supplierId non trovato');
        return;
    }

    supplierIdInput.value = supplier.id;

    const elements = {
        'name': supplier.name,
        'type': supplier.type || '',
        'email': supplier.email || '',
        'phone': supplier.phone || '',
        'address': supplier.address || '',
        'contact_person': supplier.contact_person || '',
        'website': supplier.website || '',
        'notes': supplier.notes || ''
    };

    for (const [name, value] of Object.entries(elements)) {
        const elem = document.querySelector(`[name="${name}"]`);
        if (elem) {
            elem.value = value;
        } else {
            console.warn(`Elemento [name="${name}"] non trovato`);
        }
    }

    const titleElement = document.getElementById('supplierModalTitle');
    if (titleElement) {
        titleElement.textContent = 'Modifica Fornitore';
    }

    const modalElement = document.getElementById('supplierModal');
    if (modalElement) {
        new bootstrap.Modal(modalElement).show();
    }
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

    const titleElement = document.getElementById('supplierServicesTitle');
    if (titleElement) {
        titleElement.textContent = supplier ? `Servizi offerti da ${escapeHtml(supplier.name)}` : 'Servizi';
    }

    const currentSupplierIdInput = document.getElementById('currentSupplierId');
    if (currentSupplierIdInput) {
        currentSupplierIdInput.value = supplierId;
    }

    try {
        const response = await fetch(`api_services.php?by_supplier=true&supplier_id=${supplierId}`, { cache: 'no-store' });
        const result = await response.json();

        if (result.success) {
            allServices = result.data;
            displaySupplierServices();
        } else {
            alert(result.error || 'Errore caricamento servizi');
        }
    } catch (error) {
        console.error('Error loading services:', error);
        alert('Errore di comunicazione con il server');
    }

    const modalElement = document.getElementById('servicesModal');
    if (modalElement) {
        new bootstrap.Modal(modalElement).show();
    }
}

// Display supplier services
function displaySupplierServices() {
    const tbody = document.getElementById('supplierServicesTable');
    if (!tbody) {
        console.error('Elemento #supplierServicesTable non trovato');
        return;
    }

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
    if (!currentSupplierId) {
        alert('Nessun fornitore selezionato');
        return;
    }

    const serviceIdElement = document.getElementById('supplierServiceId');
    const nameElement = document.getElementById('supplierServiceName');
    const categoryElement = document.getElementById('supplierServiceCategory');
    const priceElement = document.getElementById('supplierServicePrice');
    const descriptionElement = document.getElementById('supplierServiceDescription');
    const notesElement = document.getElementById('supplierServiceNotes');

    if (!nameElement || !categoryElement || !priceElement) {
        alert('Elementi del form non trovati');
        return;
    }

    const serviceId = serviceIdElement ? serviceIdElement.value : '';
    const name = nameElement.value.trim();
    const category = categoryElement.value;
    const price = priceElement.value;
    const description = descriptionElement ? descriptionElement.value.trim() : '';
    const notes = notesElement ? notesElement.value.trim() : '';

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

    if (serviceId) {
        data.id = serviceId;
    }

    try {
        const action = serviceId ? 'update' : 'create';
        const response = await fetch(`api_services.php?action=${action}`, {
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
    if (!service) {
        console.error('Servizio non trovato:', serviceId);
        return;
    }

    const elements = {
        'supplierServiceId': service.id,
        'supplierServiceName': service.name,
        'supplierServiceCategory': service.category,
        'supplierServicePrice': service.price,
        'supplierServiceDescription': service.description || '',
        'supplierServiceNotes': service.notes || ''
    };

    for (const [elemId, value] of Object.entries(elements)) {
        const elem = document.getElementById(elemId);
        if (elem) {
            elem.value = value;
        } else {
            console.warn(`Elemento #${elemId} non trovato`);
        }
    }

    const titleElement = document.getElementById('serviceSupplierModalTitle');
    if (titleElement) {
        titleElement.textContent = 'Modifica Servizio';
    }

    const modalElement = document.getElementById('serviceSupplierModal');
    if (modalElement) {
        new bootstrap.Modal(modalElement).show();
    }
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

// Helper: Escape HTML
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', function() {
    const supplierForm = document.getElementById('supplierForm');

    if (!supplierForm) {
        console.warn('Elemento #supplierForm non trovato');
        return;
    }

    supplierForm.addEventListener('submit', async function(e) {
        e.preventDefault();

        const submitBtn = this.querySelector('button[type="submit"]');
        if (!submitBtn) {
            console.error('Bottone submit non trovato');
            return;
        }

        if (submitBtn.disabled) {
            console.log('Submit già in corso, ignoro click');
            return;
        }

        const originalText = submitBtn.textContent;
        submitBtn.disabled = true;
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

    const supplierModal = document.getElementById('supplierModal');
    if (supplierModal) {
        supplierModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            if (button && !button.getAttribute('onclick')) {
                const form = document.getElementById('supplierForm');
                if (form) {
                    form.reset();
                }

                const supplierIdInput = document.getElementById('supplierId');
                if (supplierIdInput) {
                    supplierIdInput.value = '';
                }

                const titleElement = document.getElementById('supplierModalTitle');
                if (titleElement) {
                    titleElement.textContent = 'Nuovo Fornitore';
                }
            }
        });
    }

    const serviceSupplierModal = document.getElementById('serviceSupplierModal');
    if (serviceSupplierModal) {
        serviceSupplierModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;

            const serviceForm = document.getElementById('serviceSupplierForm');
            if (!serviceForm) {
                console.warn('Elemento #serviceSupplierForm non trovato nel DOM');
                return;
            }

            if (button && !button.getAttribute('onclick')) {
                serviceForm.reset();

                const elements = [
                    'supplierServiceId',
                    'supplierServiceName',
                    'supplierServiceCategory',
                    'supplierServicePrice',
                    'supplierServiceDescription',
                    'supplierServiceNotes'
                ];

                elements.forEach(elemId => {
                    const elem = document.getElementById(elemId);
                    if (elem) {
                        elem.value = '';
                    } else {
                        console.warn(`Elemento #${elemId} non trovato`);
                    }
                });

                const titleElement = document.getElementById('serviceSupplierModalTitle');
                if (titleElement) {
                    titleElement.textContent = 'Nuovo Servizio';
                }
            }
        });
    }

    loadSuppliers();
});
