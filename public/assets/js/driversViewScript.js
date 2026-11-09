/**
 * Drivers View Script
 */
document.addEventListener('DOMContentLoaded', function () {
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeAllDriverModals();
    });

    document.querySelectorAll('.modal-backdrop-custom').forEach(modal => {
        modal.addEventListener('click', function (e) {
            if (e.target === modal) closeAllDriverModals();
        });
    });
});

function closeAllDriverModals() {
    ['modal-create-driver', 'modal-edit-driver', 'modal-view-driver', 'modal-driver-status', 'modal-delete-driver'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.classList.add('hidden');
    });
}

function openCreateDriverModal() {
    const el = document.getElementById('modal-create-driver');
    if (el) el.classList.remove('hidden');
}

function closeCreateDriverModal() {
    const el = document.getElementById('modal-create-driver');
    if (el) el.classList.add('hidden');
}

function openEditDriverModal(d) {
    if (!d) return;
    document.getElementById('edit_driver_id').value = d.driver_id;
    document.getElementById('edit_d_first_name').value = d.first_name || '';
    document.getElementById('edit_d_last_name').value = d.last_name || '';
    document.getElementById('edit_d_phone').value = d.phone_number || '';
    document.getElementById('edit_d_license').value = d.license_number || '';
    document.getElementById('edit_d_expiry').value = d.license_expiry || '';
    document.getElementById('edit_d_status').value = d.status || 'Available';

    const el = document.getElementById('modal-edit-driver');
    if (el) el.classList.remove('hidden');
}

function closeEditDriverModal() {
    const el = document.getElementById('modal-edit-driver');
    if (el) el.classList.add('hidden');
}

function viewDriverDetails(d) {
    if (!d) return;
    document.getElementById('view-driver-name').textContent = d.full_name;
    document.getElementById('view-driver-phone').textContent = d.phone_number || '-';
    document.getElementById('view-driver-license').textContent = d.license_number || '-';
    document.getElementById('view-driver-trips').textContent = (d.total_trips || 0) + ' trips completed';

    const expSpan = `<span class="${d.license_expiry_info ? d.license_expiry_info.class : ''}">${d.license_expiry_info ? d.license_expiry_info.text : d.license_expiry}</span>`;
    document.getElementById('view-driver-expiry').innerHTML = expSpan;

    const badgeEl = document.getElementById('view-driver-badge');
    if (badgeEl && d.status_badge) {
        badgeEl.innerHTML = `
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-bold border rounded-[3px] ${d.status_badge.bg} ${d.status_badge.text} ${d.status_badge.border}">
                <span class="w-1.5 h-1.5 rounded-full ${d.status_badge.dot}"></span>
                <span>${d.status}</span>
            </span>
        `;
    }

    const el = document.getElementById('modal-view-driver');
    if (el) el.classList.remove('hidden');
}

function closeViewDriverModal() {
    const el = document.getElementById('modal-view-driver');
    if (el) el.classList.add('hidden');
}

function promptDriverStatusChange(id, status, name) {
    document.getElementById('driver_status_id').value = id;
    document.getElementById('driver_status_name').textContent = name;
    document.getElementById('driver_status_select').value = status;

    const el = document.getElementById('modal-driver-status');
    if (el) el.classList.remove('hidden');
}

function closeDriverStatusModal() {
    const el = document.getElementById('modal-driver-status');
    if (el) el.classList.add('hidden');
}

function openDeleteDriverModal(id, name) {
    document.getElementById('delete_driver_id').value = id;
    document.getElementById('delete-driver-name').textContent = name;

    const el = document.getElementById('modal-delete-driver');
    if (el) el.classList.remove('hidden');
}

function closeDeleteDriverModal() {
    const el = document.getElementById('modal-delete-driver');
    if (el) el.classList.add('hidden');
}

