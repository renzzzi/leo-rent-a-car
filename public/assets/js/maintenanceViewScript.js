/**
 * Maintenance View Script
 */
document.addEventListener('DOMContentLoaded', function () {
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeAllMaintenanceModals();
    });

    document.querySelectorAll('.modal-backdrop-custom').forEach(modal => {
        modal.addEventListener('click', function (e) {
            if (e.target === modal) closeAllMaintenanceModals();
        });
    });
});

function closeAllMaintenanceModals() {
    ['modal-create-maintenance', 'modal-edit-maintenance', 'modal-view-maintenance', 'modal-delete-maintenance'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.classList.add('hidden');
    });
}

function updateOdometerPlaceholder(selectElem) {
    const opt = selectElem.options[selectElem.selectedIndex];
    const mileage = opt ? opt.getAttribute('data-mileage') : null;
    const input = document.getElementById('create_maint_mileage');
    if (mileage && input && (!input.value || input.value === '0')) {
        input.value = mileage;
    }
}

function openCreateMaintenanceModal() {
    const el = document.getElementById('modal-create-maintenance');
    if (el) el.classList.remove('hidden');
}

function closeCreateMaintenanceModal() {
    const el = document.getElementById('modal-create-maintenance');
    if (el) el.classList.add('hidden');
}

function openEditMaintenanceModal(l) {
    if (!l) return;
    document.getElementById('edit_maint_id').value = l.maintenance_id;
    document.getElementById('edit-maint-id-display').textContent = `(#M-${String(l.maintenance_id).padStart(4, '0')})`;
    document.getElementById('edit_maint_vehicle_id').value = l.vehicle_id;
    document.getElementById('edit_maint_service_type').value = l.service_type || '';
    document.getElementById('edit_maint_date').value = l.service_date || '';
    document.getElementById('edit_maint_mileage').value = l.mileage || 0;
    document.getElementById('edit_maint_notes').value = l.notes || '';

    const el = document.getElementById('modal-edit-maintenance');
    if (el) el.classList.remove('hidden');
}

function closeEditMaintenanceModal() {
    const el = document.getElementById('modal-edit-maintenance');
    if (el) el.classList.add('hidden');
}

function viewMaintenanceDetails(l) {
    if (!l) return;
    document.getElementById('view-maint-id').textContent = `#M-${String(l.maintenance_id).padStart(4, '0')}`;
    document.getElementById('view-maint-veh').textContent = l.vehicle_name;
    document.getElementById('view-maint-plate').textContent = `Plate: ${l.vehicle_plate}`;
    document.getElementById('view-maint-type').textContent = l.service_type;
    document.getElementById('view-maint-date').textContent = l.service_date_fmt;
    document.getElementById('view-maint-mileage').textContent = l.formatted_mileage;
    document.getElementById('view-maint-notes').textContent = l.notes || 'No work notes provided.';

    const el = document.getElementById('modal-view-maintenance');
    if (el) el.classList.remove('hidden');
}

function closeViewMaintenanceModal() {
    const el = document.getElementById('modal-view-maintenance');
    if (el) el.classList.add('hidden');
}

function openDeleteMaintenanceModal(id, type, plate) {
    document.getElementById('delete_maintenance_id').value = id;
    document.getElementById('delete-maint-type').textContent = type;
    document.getElementById('delete-maint-plate').textContent = plate;

    const el = document.getElementById('modal-delete-maintenance');
    if (el) el.classList.remove('hidden');
}

function closeDeleteMaintenanceModal() {
    const el = document.getElementById('modal-delete-maintenance');
    if (el) el.classList.add('hidden');
}

