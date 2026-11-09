<?php
use App\Users\UsersService;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Role gate: Only the Owner can manage user accounts
$currentRole   = $_SESSION['role'] ?? '';
$currentUserId = (int)($_SESSION['user_id'] ?? 0);

if ($currentRole !== 'Owner') {
    echo '<div class="max-w-2xl mx-auto my-16 p-8 bg-white border border-red-200 rounded-[4px] text-center shadow-xs">
            <div class="w-12 h-12 mx-auto rounded-[4px] bg-red-100 text-red-600 flex items-center justify-center mb-3">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m0 0v2m0-2h2m-2 0H10m4-11a4 4 0 00-8 0v4h8V4z" />
                </svg>
            </div>
            <h3 class="text-base font-extrabold text-slate-900 mb-1">Access Restricted &bull; Owner Privilege Only</h3>
            <p class="text-xs text-slate-500 max-w-md mx-auto mb-4">
                Managing Manager user accounts is strictly reserved for the business Owner. Your current role is <strong>' . htmlspecialchars($currentRole) . '</strong>.
            </p>
            <a href="index.php?view=bookings" class="inline-block px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-xs font-bold rounded-[4px] transition-colors">
                Return to Bookings
            </a>
          </div>';
    return;
}

$usersService = new UsersService();

$flashSuccess = '';
$flashError   = '';

if (!empty($_GET['msg'])) {
    $flashSuccess = match ($_GET['msg']) {
        'created'          => 'New account created successfully.',
        'updated'          => 'Account updated successfully.',
        'password_reset'  => 'Password successfully updated.',
        'deleted'          => 'User account deleted successfully.',
        default            => ''
    };
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    try {
        if ($action === 'create_user') {
            $usersService->createUser($_POST);
            header('Location: index.php?view=users&msg=created');
            exit;
        }

        if ($action === 'update_user') {
            $targetId = (int)($_POST['target_user_id'] ?? 0);
            $usersService->updateUser($targetId, $_POST, $currentUserId);
            header('Location: index.php?view=users&msg=updated');
            exit;
        }

        if ($action === 'reset_password') {
            $targetId = (int)($_POST['target_user_id'] ?? 0);
            $newPw    = $_POST['new_password'] ?? '';
            $usersService->resetPassword($targetId, $newPw);
            header('Location: index.php?view=users&msg=password_reset');
            exit;
        }

        if ($action === 'delete_user') {
            $targetId = (int)($_POST['target_user_id'] ?? 0);
            $usersService->deleteUser($targetId, $currentUserId);
            header('Location: index.php?view=users&msg=deleted');
            exit;
        }
    } catch (\Throwable $e) {
        $flashError = $e->getMessage();
    }
}

$selectedRole = $_GET['role'] ?? 'all';
$searchTerm   = trim($_GET['search'] ?? '');

$filterParams = [
    'role'   => $selectedRole,
    'search' => $searchTerm,
];

$stats = [
    'total_users'   => 0,
    'owner_count'   => 0,
    'manager_count' => 0,
];
$users = [];

try {
    $stats = $usersService->getStats();
    $users = $usersService->getUsers($filterParams);
} catch (\Throwable $e) {
    $flashError = 'Database error: ' . $e->getMessage();
}
?>

<!-- Main Container -->
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    <!-- Page Title & Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-slate-200">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Manager &amp; Staff Accounts</h1>
                <span class="px-2 py-0.5 text-xs font-bold bg-red-100 text-red-700 border border-red-200 rounded-[3px]">
                    Owner Control Panel
                </span>
            </div>
            <p class="text-xs text-slate-500 mt-1 font-medium">
                Provision Manager credentials, reset passwords, and regulate operations portal access.
            </p>
        </div>

        <div>
            <button
                type="button"
                onclick="openCreateUserModal()"
                class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-bold text-white bg-red-600 hover:bg-red-700 active:bg-red-800 rounded-[4px] shadow-xs transition-colors"
            >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                </svg>
                <span>Add Manager Account</span>
            </button>
        </div>
    </div>

    <!-- Alert Notifications -->
    <?php if ($flashSuccess !== ''): ?>
    <div class="flex items-center justify-between p-3.5 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold rounded-[4px]">
        <div class="flex items-center gap-2">
            <svg class="w-4 h-4 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span><?= htmlspecialchars($flashSuccess) ?></span>
        </div>
        <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-800">&times;</button>
    </div>
    <?php endif; ?>

    <?php if ($flashError !== ''): ?>
    <div class="flex items-center justify-between p-3.5 bg-red-50 border border-red-200 text-red-800 text-xs font-semibold rounded-[4px]">
        <div class="flex items-center gap-2">
            <svg class="w-4 h-4 text-red-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span><?= htmlspecialchars($flashError) ?></span>
        </div>
        <button type="button" onclick="this.parentElement.remove()" class="text-red-500 hover:text-red-800">&times;</button>
    </div>
    <?php endif; ?>

    <!-- KPI Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
        <div class="bg-white p-4 border border-slate-200 rounded-[4px] shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Total Accounts</span>
                <span class="p-1.5 bg-slate-100 rounded-[3px] text-slate-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </span>
            </div>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-2xl font-black text-slate-900"><?= $stats['total_users'] ?></span>
                <span class="text-[10px] text-slate-400 font-medium">registered</span>
            </div>
        </div>

        <div class="bg-white p-4 border border-blue-200 bg-blue-50/20 rounded-[4px] shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-blue-700 uppercase tracking-wider">Operational Managers</span>
                <span class="p-1.5 bg-blue-100 rounded-[3px] text-blue-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                </span>
            </div>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-2xl font-black text-blue-700"><?= $stats['manager_count'] ?></span>
                <span class="text-[10px] text-blue-600 font-semibold">managers</span>
            </div>
        </div>

        <div class="bg-white p-4 border border-red-200 bg-red-50/20 rounded-[4px] shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-red-700 uppercase tracking-wider">Business Owners</span>
                <span class="p-1.5 bg-red-100 rounded-[3px] text-red-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                    </svg>
                </span>
            </div>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-2xl font-black text-red-700"><?= $stats['owner_count'] ?></span>
                <span class="text-[10px] text-red-600 font-semibold">administrator</span>
            </div>
        </div>
    </div>

    <!-- Toolbar -->
    <div class="bg-white border border-slate-200 rounded-[4px] p-4 shadow-xs space-y-3">
        <form method="GET" action="index.php" class="flex flex-col md:flex-row md:items-center justify-between gap-3">
            <input type="hidden" name="view" value="users">

            <div class="relative flex-1 max-w-md">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input
                    type="text"
                    name="search"
                    value="<?= htmlspecialchars($searchTerm) ?>"
                    placeholder="Search Username..."
                    class="w-full pl-9 pr-3.5 py-2 text-xs font-medium text-slate-900 bg-slate-50 border border-slate-300 rounded-[4px] focus:outline-none focus:ring-1 focus:ring-red-600 focus:border-red-600"
                >
            </div>

            <div class="flex items-center gap-2">
                <button
                    type="submit"
                    class="px-3 py-2 bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold rounded-[4px] transition-colors shadow-xs"
                >
                    Search
                </button>

                <?php if ($searchTerm !== '' || $selectedRole !== 'all'): ?>
                <a
                    href="index.php?view=users"
                    class="px-2.5 py-2 text-xs font-bold text-red-600 hover:text-red-700 bg-red-50 hover:bg-red-100 border border-red-200 rounded-[4px] transition-colors"
                >
                    Reset
                </a>
                <?php endif; ?>
            </div>
        </form>

        <!-- Role Filter Tabs -->
        <div class="flex items-center gap-1.5 pt-3 border-t border-slate-100 overflow-x-auto">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mr-2">Role:</span>
            <?php
            $roleTabs = [
                'all'     => 'All Accounts (' . $stats['total_users'] . ')',
                'Manager' => 'Managers Only (' . $stats['manager_count'] . ')',
                'Owner'   => 'Owners (' . $stats['owner_count'] . ')',
            ];
            foreach ($roleTabs as $rVal => $rLabel):
                $isActive = ($selectedRole === $rVal);
            ?>
            <a
                href="index.php?view=users&role=<?= urlencode($rVal) ?><?= $searchTerm !== '' ? '&search=' . urlencode($searchTerm) : '' ?>"
                class="px-2.5 py-1 text-xs font-bold whitespace-nowrap rounded-[4px] transition-colors <?= $isActive ? 'bg-slate-900 text-white shadow-xs' : 'bg-slate-100 hover:bg-slate-200 text-slate-600' ?>"
            >
                <?= $rLabel ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Users Table Card -->
    <div class="bg-white border border-slate-200 rounded-[4px] shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse table-sharp">
                <thead>
                    <tr class="bg-slate-100 border-b border-slate-200 text-slate-600">
                        <th class="py-3 px-4 font-bold text-[11px] uppercase tracking-wider">Account ID</th>
                        <th class="py-3 px-4 font-bold text-[11px] uppercase tracking-wider">Username</th>
                        <th class="py-3 px-4 font-bold text-[11px] uppercase tracking-wider">System Role</th>
                        <th class="py-3 px-4 font-bold text-[11px] uppercase tracking-wider">Bookings Logged</th>
                        <th class="py-3 px-4 font-bold text-[11px] uppercase tracking-wider">Created Timestamp</th>
                        <th class="py-3 px-4 font-bold text-[11px] uppercase tracking-wider text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">
                    <?php foreach ($users as $u):
                        $isCurrent = ($u['user_id'] === $currentUserId);
                    ?>
                    <tr class="hover:bg-slate-50/80 transition-colors <?= $isCurrent ? 'bg-amber-50/20' : '' ?>">

                        <!-- Account ID -->
                        <td class="py-3 px-4 whitespace-nowrap font-mono text-slate-600 font-bold">
                            #USR-<?= str_pad((string)$u['user_id'], 3, '0', STR_PAD_LEFT) ?>
                        </td>

                        <!-- Username -->
                        <td class="py-3 px-4 whitespace-nowrap">
                            <span class="font-extrabold text-slate-900 font-mono text-xs">
                                <?= htmlspecialchars($u['username']) ?>
                            </span>
                            <?php if ($isCurrent): ?>
                                <span class="ml-1.5 px-1.5 py-0.2 text-[9px] font-bold bg-amber-100 text-amber-800 border border-amber-200 rounded-[2px]">
                                    You (Active Session)
                                </span>
                            <?php endif; ?>
                        </td>

                        <!-- Role -->
                        <td class="py-3 px-4 whitespace-nowrap">
                            <?php if ($u['role'] === 'Owner'): ?>
                                <span class="px-2 py-0.5 text-[10px] font-black uppercase tracking-wider bg-red-100 text-red-700 border border-red-200 rounded-[2px]">
                                    Owner
                                </span>
                            <?php else: ?>
                                <span class="px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider bg-blue-100 text-blue-700 border border-blue-200 rounded-[2px]">
                                    Manager
                                </span>
                            <?php endif; ?>
                        </td>

                        <!-- Bookings created -->
                        <td class="py-3 px-4 whitespace-nowrap">
                            <span class="font-bold text-slate-800"><?= (int)$u['total_created_bookings'] ?></span>
                            <span class="text-slate-500">bookings handled</span>
                        </td>

                        <!-- Created Timestamp -->
                        <td class="py-3 px-4 whitespace-nowrap text-slate-500">
                            <?= htmlspecialchars($u['created_at_fmt']) ?>
                        </td>

                        <!-- Actions -->
                        <td class="py-3 px-4 whitespace-nowrap text-right">
                            <div class="inline-flex items-center gap-1">
                                <!-- Reset Password Button -->
                                <button
                                    type="button"
                                    onclick="openResetPasswordModal(<?= $u['user_id'] ?>, '<?= htmlspecialchars($u['username']) ?>')"
                                    title="Reset Password"
                                    class="p-1.5 text-slate-500 hover:text-amber-700 hover:bg-amber-50 rounded-[3px] transition-colors"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                                    </svg>
                                </button>

                                <!-- Edit Account Button -->
                                <button
                                    type="button"
                                    onclick="openEditUserModal(<?= $u['user_id'] ?>, '<?= htmlspecialchars($u['username']) ?>', '<?= htmlspecialchars($u['role']) ?>')"
                                    title="Edit Account Details"
                                    class="p-1.5 text-slate-500 hover:text-blue-600 hover:bg-blue-50 rounded-[3px] transition-colors"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>

                                <!-- Delete Account Button (Disabled for own account) -->
                                <?php if (!$isCurrent): ?>
                                <button
                                    type="button"
                                    onclick="openDeleteUserModal(<?= $u['user_id'] ?>, '<?= htmlspecialchars($u['username']) ?>')"
                                    title="Delete Account"
                                    class="p-1.5 text-slate-500 hover:text-red-600 hover:bg-red-50 rounded-[3px] transition-colors"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>

                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>


<!-- =========================================================================
     USERS MODALS
     ========================================================================= -->

<!-- 1. CREATE USER MODAL -->
<div id="modal-create-user" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 modal-backdrop-custom overflow-y-auto">
    <div class="bg-white border border-slate-200 rounded-[6px] shadow-xl max-w-sm w-full my-auto overflow-hidden">
        <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between border-b border-slate-800">
            <h3 class="text-sm font-extrabold">Create Manager Account</h3>
            <button type="button" onclick="closeCreateUserModal()" class="text-slate-400 hover:text-white">&times;</button>
        </div>

        <form method="POST" action="index.php?view=users" class="p-6 space-y-4">
            <input type="hidden" name="action" value="create_user">

            <div>
                <label class="block text-xs font-bold text-slate-800 mb-1">Username *</label>
                <input type="text" name="username" required placeholder="e.g. manager_sarah" class="w-full px-3 py-2 text-xs font-mono bg-slate-50 border border-slate-300 rounded-[4px] focus:ring-1 focus:ring-red-600 focus:border-red-600">
                <p class="text-[10px] text-slate-400 mt-0.5">Letters, numbers, underscores (3-30 chars).</p>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-800 mb-1">Password *</label>
                <input type="password" name="password" required minlength="6" placeholder="At least 6 characters" class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-300 rounded-[4px] focus:ring-1 focus:ring-red-600 focus:border-red-600">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-800 mb-1">Account Role *</label>
                <select name="role" class="w-full px-3 py-2 text-xs font-semibold bg-slate-50 border border-slate-300 rounded-[4px]">
                    <option value="Manager" selected>Manager (Standard Operational Rights)</option>
                    <option value="Owner">Owner (Full Business Rights)</option>
                </select>
            </div>

            <div class="pt-4 border-t border-slate-200 flex justify-end gap-2">
                <button type="button" onclick="closeCreateUserModal()" class="px-4 py-2 text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-[4px]">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2 text-xs font-bold text-white bg-red-600 hover:bg-red-700 rounded-[4px]">
                    Create Account
                </button>
            </div>
        </form>
    </div>
</div>


<!-- 2. EDIT USER MODAL -->
<div id="modal-edit-user" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 modal-backdrop-custom overflow-y-auto">
    <div class="bg-white border border-slate-200 rounded-[6px] shadow-xl max-w-sm w-full my-auto overflow-hidden">
        <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between border-b border-slate-800">
            <h3 class="text-sm font-extrabold">Edit Account Details</h3>
            <button type="button" onclick="closeEditUserModal()" class="text-slate-400 hover:text-white">&times;</button>
        </div>

        <form method="POST" action="index.php?view=users" class="p-6 space-y-4">
            <input type="hidden" name="action" value="update_user">
            <input type="hidden" name="target_user_id" id="edit_user_id" value="">

            <div>
                <label class="block text-xs font-bold text-slate-800 mb-1">Username *</label>
                <input type="text" name="username" id="edit_user_username" required class="w-full px-3 py-2 text-xs font-mono bg-slate-50 border border-slate-300 rounded-[4px]">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-800 mb-1">Account Role *</label>
                <select name="role" id="edit_user_role" class="w-full px-3 py-2 text-xs font-semibold bg-slate-50 border border-slate-300 rounded-[4px]">
                    <option value="Manager">Manager</option>
                    <option value="Owner">Owner</option>
                </select>
            </div>

            <div class="pt-4 border-t border-slate-200 flex justify-end gap-2">
                <button type="button" onclick="closeEditUserModal()" class="px-4 py-2 text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-[4px]">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-[4px]">
                    Update Account
                </button>
            </div>
        </form>
    </div>
</div>


<!-- 3. RESET PASSWORD MODAL -->
<div id="modal-reset-password" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 modal-backdrop-custom overflow-y-auto">
    <div class="bg-white border border-slate-200 rounded-[6px] shadow-xl max-w-sm w-full my-auto overflow-hidden">
        <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between border-b border-slate-800">
            <h3 class="text-sm font-extrabold">Reset Account Password</h3>
            <button type="button" onclick="closeResetPasswordModal()" class="text-slate-400 hover:text-white">&times;</button>
        </div>

        <form method="POST" action="index.php?view=users" class="p-6 space-y-4">
            <input type="hidden" name="action" value="reset_password">
            <input type="hidden" name="target_user_id" id="reset_user_id" value="">

            <div>
                <label class="block text-[11px] font-bold text-slate-500 uppercase mb-0.5">Account</label>
                <div id="reset_username_display" class="font-bold font-mono text-xs text-slate-900"></div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-800 mb-1">New Password *</label>
                <input type="password" name="new_password" required minlength="6" placeholder="Enter new password" class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-300 rounded-[4px] focus:ring-1 focus:ring-red-600 focus:border-red-600">
            </div>

            <div class="pt-4 border-t border-slate-200 flex justify-end gap-2">
                <button type="button" onclick="closeResetPasswordModal()" class="px-4 py-2 text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-[4px]">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2 text-xs font-bold text-white bg-amber-600 hover:bg-amber-700 rounded-[4px]">
                    Update Password
                </button>
            </div>
        </form>
    </div>
</div>


<!-- 4. DELETE USER MODAL -->
<div id="modal-delete-user" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 modal-backdrop-custom overflow-y-auto">
    <div class="bg-white border border-slate-200 rounded-[6px] shadow-xl max-w-sm w-full my-auto overflow-hidden">
        <div class="p-5 text-center space-y-3">
            <div class="w-12 h-12 mx-auto rounded-[4px] bg-red-100 text-red-600 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
            </div>
            <h3 class="text-sm font-extrabold text-slate-900">Delete User Account</h3>
            <p class="text-xs text-slate-500">
                Are you sure you want to permanently delete account <strong id="delete-username-display" class="font-mono text-slate-900"></strong>?
            </p>

            <form method="POST" action="index.php?view=users" class="pt-2 flex justify-center gap-2">
                <input type="hidden" name="action" value="delete_user">
                <input type="hidden" name="target_user_id" id="delete_user_id" value="">

                <button type="button" onclick="closeDeleteUserModal()" class="px-4 py-2 text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-[4px]">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 text-xs font-bold text-white bg-red-600 hover:bg-red-700 rounded-[4px]">
                    Confirm Delete
                </button>
            </form>
        </div>
    </div>
</div>

