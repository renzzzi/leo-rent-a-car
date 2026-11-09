/**
 * Users / Manager Accounts View Script
 */
document.addEventListener('DOMContentLoaded', function () {
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeAllUserModals();
    });

    document.querySelectorAll('.modal-backdrop-custom').forEach(modal => {
        modal.addEventListener('click', function (e) {
            if (e.target === modal) closeAllUserModals();
        });
    });
});

function closeAllUserModals() {
    ['modal-create-user', 'modal-edit-user', 'modal-reset-password', 'modal-delete-user'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.classList.add('hidden');
    });
}

function openCreateUserModal() {
    const el = document.getElementById('modal-create-user');
    if (el) el.classList.remove('hidden');
}

function closeCreateUserModal() {
    const el = document.getElementById('modal-create-user');
    if (el) el.classList.add('hidden');
}

function openEditUserModal(userId, username, role) {
    document.getElementById('edit_user_id').value = userId;
    document.getElementById('edit_user_username').value = username;
    document.getElementById('edit_user_role').value = role;

    const el = document.getElementById('modal-edit-user');
    if (el) el.classList.remove('hidden');
}

function closeEditUserModal() {
    const el = document.getElementById('modal-edit-user');
    if (el) el.classList.add('hidden');
}

function openResetPasswordModal(userId, username) {
    document.getElementById('reset_user_id').value = userId;
    document.getElementById('reset_username_display').textContent = username;

    const el = document.getElementById('modal-reset-password');
    if (el) el.classList.remove('hidden');
}

function closeResetPasswordModal() {
    const el = document.getElementById('modal-reset-password');
    if (el) el.classList.add('hidden');
}

function openDeleteUserModal(userId, username) {
    document.getElementById('delete_user_id').value = userId;
    document.getElementById('delete-username-display').textContent = username;

    const el = document.getElementById('modal-delete-user');
    if (el) el.classList.remove('hidden');
}

function closeDeleteUserModal() {
    const el = document.getElementById('modal-delete-user');
    if (el) el.classList.add('hidden');
}

