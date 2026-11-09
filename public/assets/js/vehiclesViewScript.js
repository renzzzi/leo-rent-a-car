/**
 * Vehicles View Script
 */
document.addEventListener('DOMContentLoaded', function () {
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeAllVehicleModals();
    });

    document.querySelectorAll('.modal-backdrop-custom').forEach(modal => {
        modal.addEventListener('click', function (e) {
            if (e.target === modal) closeAllVehicleModals();
        });
    });
});

function closeAllVehicleModals() {
    ['modal-create-vehicle', 'modal-edit-vehicle', 'modal-view-vehicle', 'modal-veh-status', 'modal-delete-vehicle'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.classList.add('hidden');
    });
}

function openCreateVehicleModal() {
    const el = document.getElementById('modal-create-vehicle');
    if (el) el.classList.remove('hidden');
}

function closeCreateVehicleModal() {
    const el = document.getElementById('modal-create-vehicle');
    if (el) el.classList.add('hidden');
}

function openEditVehicleModal(v) {
    if (!v) return;
    document.getElementById('edit_vehicle_id').value = v.vehicle_id;
    document.getElementById('edit-veh-title').textContent = `(${v.license_plate})`;
    document.getElementById('edit_make').value = v.make;
    document.getElementById('edit_model').value = v.model;
    document.getElementById('edit_year').value = v.year;
    document.getElementById('edit_type').value = v.type;
    document.getElementById('edit_seating_capacity').value = v.seating_capacity;
    document.getElementById('edit_license_plate').value = v.license_plate;
    document.getElementById('edit_vin').value = v.vin;
    document.getElementById('edit_mileage').value = v.mileage;
    document.getElementById('edit_status').value = v.status;
    document.getElementById('edit_registration_expiry').value = v.registration_expiry;
    document.getElementById('edit_insurance_expiry').value = v.insurance_expiry;

    const el = document.getElementById('modal-edit-vehicle');
    if (el) el.classList.remove('hidden');
}

function closeEditVehicleModal() {
    const el = document.getElementById('modal-edit-vehicle');
    if (el) el.classList.add('hidden');
}

function viewVehicleDetails(v) {
    if (!v) return;
    document.getElementById('view-veh-name').textContent = v.full_name;
    document.getElementById('view-veh-plate').textContent = v.license_plate;
    document.getElementById('view-veh-vin').textContent = v.vin;
    document.getElementById('view-veh-type').textContent = v.type;
    document.getElementById('view-veh-seats').textContent = v.seating_capacity + ' Pax';
    document.getElementById('view-veh-mileage').textContent = v.formatted_mileage;
    document.getElementById('view-veh-trips').textContent = v.total_trips || 0;
    document.getElementById('view-veh-maint').textContent = v.total_maintenance_logs || 0;

    const regSpan = `<span class="${v.reg_expiry_info ? v.reg_expiry_info.class : ''}">${v.reg_expiry_info ? v.reg_expiry_info.text : v.registration_expiry}</span>`;
    document.getElementById('view-veh-reg').innerHTML = regSpan;

    const insSpan = `<span class="${v.ins_expiry_info ? v.ins_expiry_info.class : ''}">${v.ins_expiry_info ? v.ins_expiry_info.text : v.insurance_expiry}</span>`;
    document.getElementById('view-veh-ins').innerHTML = insSpan;

    const badgeEl = document.getElementById('view-veh-badge');
    if (badgeEl && v.status_badge) {
        badgeEl.innerHTML = `
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-bold border rounded-[3px] ${v.status_badge.bg} ${v.status_badge.text} ${v.status_badge.border}">
                <span class="w-1.5 h-1.5 rounded-full ${v.status_badge.dot}"></span>
                <span>${v.status}</span>
            </span>
        `;
    }

    const el = document.getElementById('modal-view-vehicle');
    if (el) el.classList.remove('hidden');
}

function closeViewVehicleModal() {
    const el = document.getElementById('modal-view-vehicle');
    if (el) el.classList.add('hidden');
}

function promptVehicleStatusChange(id, status, name, plate) {
    document.getElementById('veh_status_id').value = id;
    document.getElementById('veh_status_display').textContent = `${name} [${plate}]`;
    document.getElementById('veh_status_select').value = status;

    const el = document.getElementById('modal-veh-status');
    if (el) el.classList.remove('hidden');
}

function closeVehStatusModal() {
    const el = document.getElementById('modal-veh-status');
    if (el) el.classList.add('hidden');
}

function openDeleteVehicleModal(id, name, plate) {
    document.getElementById('delete_vehicle_id').value = id;
    document.getElementById('delete-veh-name').textContent = name;
    document.getElementById('delete-veh-plate').textContent = plate;

    const el = document.getElementById('modal-delete-vehicle');
    if (el) el.classList.remove('hidden');
}

function closeDeleteVehicleModal() {
    const el = document.getElementById('modal-delete-vehicle');
    if (el) el.classList.add('hidden');
}

