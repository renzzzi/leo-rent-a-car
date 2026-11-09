/**
 * Customers View Script
 */
document.addEventListener('DOMContentLoaded', function () {
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeAllCustomerModals();
    });

    document.querySelectorAll('.modal-backdrop-custom').forEach(modal => {
        modal.addEventListener('click', function (e) {
            if (e.target === modal) closeAllCustomerModals();
        });
    });
});

function closeAllCustomerModals() {
    ['modal-create-customer', 'modal-edit-customer', 'modal-view-customer', 'modal-delete-customer'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.classList.add('hidden');
    });
}

function openCreateCustomerModal() {
    const el = document.getElementById('modal-create-customer');
    if (el) el.classList.remove('hidden');
}

function closeCreateCustomerModal() {
    const el = document.getElementById('modal-create-customer');
    if (el) el.classList.add('hidden');
}

function openEditCustomerModal(c) {
    if (!c) return;
    document.getElementById('edit_customer_id').value = c.customer_id;
    document.getElementById('edit_cust_first_name').value = c.first_name || '';
    document.getElementById('edit_cust_last_name').value = c.last_name || '';
    document.getElementById('edit_cust_phone').value = c.phone_number || '';
    document.getElementById('edit_cust_email').value = c.email || '';
    document.getElementById('edit_cust_address').value = c.address || '';
    document.getElementById('edit_cust_is_vip').checked = Boolean(parseInt(c.is_vip, 10));
    document.getElementById('edit_cust_is_returning').checked = Boolean(parseInt(c.is_returning, 10));
    document.getElementById('edit_cust_notes').value = c.notes || '';

    const el = document.getElementById('modal-edit-customer');
    if (el) el.classList.remove('hidden');
}

function closeEditCustomerModal() {
    const el = document.getElementById('modal-edit-customer');
    if (el) el.classList.add('hidden');
}

function viewCustomerDetails(c) {
    if (!c) return;
    document.getElementById('view-cust-name').textContent = c.full_name;
    document.getElementById('view-cust-phone').textContent = c.phone_number || '-';
    document.getElementById('view-cust-email').textContent = c.email || 'None registered';
    document.getElementById('view-cust-address').textContent = c.address || 'None provided';
    document.getElementById('view-cust-trips').textContent = (c.total_bookings_count || 0) + ' Bookings';
    document.getElementById('view-cust-spent').textContent = c.formatted_spent || '₱ 0.00';
    document.getElementById('view-cust-notes').textContent = c.notes || 'No customer notes.';

    let badgesHtml = '';
    if (parseInt(c.is_vip, 10)) {
        badgesHtml += '<span class="px-2 py-0.5 text-xs font-bold uppercase bg-amber-50 text-amber-700 border border-amber-200/80 rounded-md mr-1">VIP</span>';
    }
    if (parseInt(c.is_returning, 10)) {
        badgesHtml += '<span class="px-2 py-0.5 text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200/80 rounded-md">Returning</span>';
    }
    document.getElementById('view-cust-badges').innerHTML = badgesHtml;

    const el = document.getElementById('modal-view-customer');
    if (el) el.classList.remove('hidden');
}

function closeViewCustomerModal() {
    const el = document.getElementById('modal-view-customer');
    if (el) el.classList.add('hidden');
}

function openDeleteCustomerModal(id, name) {
    document.getElementById('delete_customer_id').value = id;
    document.getElementById('delete-cust-name').textContent = name;

    const el = document.getElementById('modal-delete-customer');
    if (el) el.classList.remove('hidden');
}

function closeDeleteCustomerModal() {
    const el = document.getElementById('modal-delete-customer');
    if (el) el.classList.add('hidden');
}

