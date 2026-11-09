<?php
use App\Customers\CustomersService;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$customersService = new CustomersService();

$flashSuccess = '';
$flashError   = '';

if (!empty($_GET['msg'])) {
    $flashSuccess = match ($_GET['msg']) {
        'created' => 'Customer registered successfully.',
        'updated' => 'Customer profile updated successfully.',
        'deleted' => 'Customer record deleted successfully.',
        default   => ''
    };
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    try {
        if ($action === 'create_customer') {
            $customersService->createCustomer($_POST);
            header('Location: index.php?view=customers&msg=created');
            exit;
        }

        if ($action === 'update_customer') {
            $customerId = (int)($_POST['customer_id'] ?? 0);
            $customersService->updateCustomer($customerId, $_POST);
            header('Location: index.php?view=customers&msg=updated');
            exit;
        }

        if ($action === 'delete_customer') {
            $customerId = (int)($_POST['customer_id'] ?? 0);
            $customersService->deleteCustomer($customerId);
            header('Location: index.php?view=customers&msg=deleted');
            exit;
        }
    } catch (\Throwable $e) {
        $flashError = $e->getMessage();
    }
}

$selectedFilter = $_GET['filter'] ?? 'all';
$searchTerm     = trim($_GET['search'] ?? '');

$filterParams = [
    'filter' => $selectedFilter,
    'search' => $searchTerm,
];

$stats = [
    'total_customers'         => 0,
    'vip_count'               => 0,
    'returning_count'         => 0,
    'currently_renting_count' => 0,
];
$customers = [];

try {
    $stats     = $customersService->getStats();
    $customers = $customersService->getCustomers($filterParams);
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
                <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Customer Directory</h1>
                <span class="px-2.5 py-0.5 text-xs font-semibold bg-red-50 text-red-600 border border-red-200/80 rounded-full">
                    <?= count($customers) ?> Clients
                </span>
            </div>
            <p class="text-xs text-slate-500 mt-1 font-medium">
                Client contact registry, VIP tags, loyalty tracking, and booking history.
            </p>
        </div>

        <div>
            <button
                type="button"
                onclick="openCreateCustomerModal()"
                class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold text-white bg-red-600 hover:bg-red-700 active:bg-red-800 rounded-lg shadow-xs transition-colors"
            >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                </svg>
                <span>Register Customer</span>
            </button>
        </div>
    </div>

    <!-- Alert Notifications -->
    <?php if ($flashSuccess !== ''): ?>
    <div class="flex items-center justify-between p-3.5 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold rounded-xl">
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
    <div class="flex items-center justify-between p-3.5 bg-red-50 border border-red-200 text-red-800 text-xs font-semibold rounded-xl">
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
    <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
        <div class="bg-white p-4 border border-slate-200 rounded-xl shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Registered Clients</span>
                <span class="p-1.5 bg-slate-100 rounded-lg text-slate-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </span>
            </div>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-2xl font-black text-slate-900"><?= $stats['total_customers'] ?></span>
                <span class="text-[10px] text-slate-400 font-medium">accounts</span>
            </div>
        </div>

        <div class="bg-white p-4 border border-amber-200 bg-amber-50/20 rounded-xl shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-amber-800 uppercase tracking-wider">VIP Priority Accounts</span>
                <span class="p-1.5 bg-amber-100 rounded-lg text-amber-800">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                    </svg>
                </span>
            </div>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-2xl font-black text-amber-800"><?= $stats['vip_count'] ?></span>
                <span class="text-[10px] text-amber-600 font-semibold">executive status</span>
            </div>
        </div>

        <div class="bg-white p-4 border border-blue-200 bg-blue-50/20 rounded-xl shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-blue-700 uppercase tracking-wider">Returning Clients</span>
                <span class="p-1.5 bg-blue-100 rounded-lg text-blue-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                </span>
            </div>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-2xl font-black text-blue-700"><?= $stats['returning_count'] ?></span>
                <span class="text-[10px] text-blue-600 font-semibold">repeat renters</span>
            </div>
        </div>

        <div class="bg-white p-4 border border-emerald-200 bg-emerald-50/20 rounded-xl shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-emerald-700 uppercase tracking-wider">Currently Renting</span>
                <span class="p-1.5 bg-emerald-100 rounded-lg text-emerald-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </span>
            </div>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-2xl font-black text-emerald-700"><?= $stats['currently_renting_count'] ?></span>
                <span class="text-[10px] text-emerald-600 font-semibold">active trips</span>
            </div>
        </div>
    </div>

    <!-- Toolbar -->
    <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-xs space-y-3">
        <form method="GET" action="index.php" class="flex flex-col md:flex-row md:items-center justify-between gap-3">
            <input type="hidden" name="view" value="customers">

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
                    placeholder="Search Customer Name, Phone, Email, Address..."
                    class="w-full pl-9 pr-3.5 py-2 text-xs font-medium text-slate-900 bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-colors"
                >
            </div>

            <div class="flex items-center gap-2">
                <button
                    type="submit"
                    class="px-3.5 py-2 bg-slate-800 hover:bg-slate-700 text-white text-xs font-semibold rounded-lg transition-colors shadow-xs"
                >
                    Search
                </button>

                <?php if ($searchTerm !== '' || $selectedFilter !== 'all'): ?>
                <a
                    href="index.php?view=customers"
                    class="px-2.5 py-2 text-xs font-semibold text-red-600 hover:text-red-700 bg-red-50 hover:bg-red-100 border border-red-200 rounded-lg transition-colors"
                >
                    Reset
                </a>
                <?php endif; ?>
            </div>
        </form>

        <!-- Filter Pills -->
        <div class="flex items-center gap-1.5 pt-3 border-t border-slate-100 overflow-x-auto">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mr-2">Filter:</span>
            <?php
            $custTabs = [
                'all'       => 'All Clients (' . $stats['total_customers'] . ')',
                'vip'       => '⭐ VIP Only (' . $stats['vip_count'] . ')',
                'returning' => 'Returning (' . $stats['returning_count'] . ')',
            ];
            foreach ($custTabs as $cVal => $cLabel):
                $isActive = ($selectedFilter === $cVal);
            ?>
            <a
                href="index.php?view=customers&filter=<?= urlencode($cVal) ?><?= $searchTerm !== '' ? '&search=' . urlencode($searchTerm) : '' ?>"
                class="px-3 py-1 text-xs font-semibold whitespace-nowrap rounded-lg transition-colors <?= $isActive ? 'bg-slate-800 text-white shadow-xs' : 'bg-slate-100 hover:bg-slate-200 text-slate-600' ?>"
            >
                <?= $cLabel ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Customers Data Table Card -->
    <div class="bg-white border border-slate-200 rounded-xl shadow-xs overflow-hidden">
        <?php if (empty($customers)): ?>
        <div class="py-16 text-center">
            <div class="w-12 h-12 mx-auto rounded-xl bg-red-50 border border-red-200 text-red-600 flex items-center justify-center mb-3">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
            </div>
            <h3 class="text-sm font-bold text-slate-800">No Customers Found</h3>
            <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">
                No customer profiles match your search criteria.
            </p>
            <div class="mt-4">
                <button type="button" onclick="openCreateCustomerModal()" class="px-3.5 py-1.5 text-xs font-semibold text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors">
                    Register New Customer
                </button>
            </div>
        </div>
        <?php else: ?>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/80 border-b border-slate-200 text-slate-600">
                        <th class="py-3 px-4 font-bold text-[11px] uppercase tracking-wider">Client Name &amp; Badges</th>
                        <th class="py-3 px-4 font-bold text-[11px] uppercase tracking-wider">Phone Number</th>
                        <th class="py-3 px-4 font-bold text-[11px] uppercase tracking-wider">Email Address</th>
                        <th class="py-3 px-4 font-bold text-[11px] uppercase tracking-wider">Address</th>
                        <th class="py-3 px-4 font-bold text-[11px] uppercase tracking-wider">Bookings &amp; Spend</th>
                        <th class="py-3 px-4 font-bold text-[11px] uppercase tracking-wider">Registered</th>
                        <th class="py-3 px-4 font-bold text-[11px] uppercase tracking-wider text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">
                    <?php foreach ($customers as $c): ?>
                    <tr class="hover:bg-slate-50/80 transition-colors">

                        <!-- Name & Badges -->
                        <td class="py-3 px-4">
                            <div class="font-bold text-slate-900 flex items-center gap-1.5">
                                <span><?= htmlspecialchars($c['full_name']) ?></span>
                                <?php if (!empty($c['is_vip'])): ?>
                                    <span class="px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider bg-amber-50 text-amber-700 border border-amber-200/80 rounded-md">VIP</span>
                                <?php endif; ?>
                                <?php if (!empty($c['is_returning'])): ?>
                                    <span class="px-2 py-0.5 text-[10px] font-semibold bg-blue-50 text-blue-700 border border-blue-200/80 rounded-md">Returning</span>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($c['notes'])): ?>
                                <div class="text-[11px] text-slate-400 italic max-w-xs truncate mt-0.5" title="<?= htmlspecialchars($c['notes']) ?>">
                                    "<?= htmlspecialchars($c['notes']) ?>"
                                </div>
                            <?php endif; ?>
                        </td>

                        <!-- Phone -->
                        <td class="py-3 px-4 whitespace-nowrap">
                            <span class="font-mono font-semibold text-slate-800">
                                <?= htmlspecialchars($c['phone_number']) ?>
                            </span>
                        </td>

                        <!-- Email -->
                        <td class="py-3 px-4">
                            <span class="text-slate-600">
                                <?= !empty($c['email']) ? htmlspecialchars($c['email']) : '<span class="text-slate-400 italic">None</span>' ?>
                            </span>
                        </td>

                        <!-- Address -->
                        <td class="py-3 px-4">
                            <div class="max-w-[200px] truncate text-slate-700" title="<?= htmlspecialchars($c['address'] ?? '') ?>">
                                <?= !empty($c['address']) ? htmlspecialchars($c['address']) : '<span class="text-slate-400 italic">-</span>' ?>
                            </div>
                        </td>

                        <!-- Bookings & Spend -->
                        <td class="py-3 px-4 whitespace-nowrap">
                            <span class="font-bold text-slate-900"><?= (int)$c['total_bookings_count'] ?></span>
                            <span class="text-slate-500">trips &bull;</span>
                            <span class="font-bold text-red-600"><?= htmlspecialchars($c['formatted_spent']) ?></span>
                        </td>

                        <!-- Registered Date -->
                        <td class="py-3 px-4 whitespace-nowrap text-slate-500">
                            <?= htmlspecialchars($c['registered_date_fmt']) ?>
                        </td>

                        <!-- Actions -->
                        <td class="py-3 px-4 whitespace-nowrap text-right">
                            <div class="inline-flex items-center gap-1">
                                <!-- View Customer Profile -->
                                <button
                                    type="button"
                                    onclick="viewCustomerDetails(<?= htmlspecialchars(json_encode($c), ENT_QUOTES, 'UTF-8') ?>)"
                                    title="View Customer Profile"
                                    class="p-1.5 text-slate-500 hover:text-slate-900 hover:bg-slate-100 border border-transparent hover:border-slate-200 rounded-lg transition-colors"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </button>

                                <!-- Edit Customer -->
                                <button
                                    type="button"
                                    onclick="openEditCustomerModal(<?= htmlspecialchars(json_encode($c), ENT_QUOTES, 'UTF-8') ?>)"
                                    title="Edit Profile"
                                    class="p-1.5 text-slate-500 hover:text-blue-600 hover:bg-blue-50 border border-transparent hover:border-blue-200 rounded-lg transition-colors"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>

                                <!-- Delete Customer -->
                                <button
                                    type="button"
                                    onclick="openDeleteCustomerModal(<?= $c['customer_id'] ?>, '<?= htmlspecialchars($c['full_name']) ?>')"
                                    title="Delete Customer"
                                    class="p-1.5 text-slate-500 hover:text-red-600 hover:bg-red-50 border border-transparent hover:border-red-200 rounded-lg transition-colors"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </td>

                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

</div>


<!-- =========================================================================
     CUSTOMERS MODALS
     ========================================================================= -->

<!-- 1. CREATE CUSTOMER MODAL -->
<div id="modal-create-customer" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 modal-backdrop-custom overflow-y-auto">
    <div class="bg-white border border-slate-200/80 rounded-2xl shadow-2xl max-w-md w-full my-auto overflow-hidden">
        <div class="px-6 py-4 bg-white border-b border-slate-100 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-red-50 text-red-600 border border-red-100/80 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Register Customer Account</h3>
                    <p class="text-xs text-slate-500">Create new client directory record</p>
                </div>
            </div>
            <button type="button" onclick="closeCreateCustomerModal()" class="w-8 h-8 flex items-center justify-center text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg transition-colors">&times;</button>
        </div>

        <form method="POST" action="index.php?view=customers" class="p-6 space-y-4">
            <input type="hidden" name="action" value="create_customer">

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">First Name *</label>
                    <input type="text" name="first_name" required placeholder="e.g. Carlos" class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-colors">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Last Name *</label>
                    <input type="text" name="last_name" required placeholder="e.g. Mendoza" class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-colors">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Mobile Phone *</label>
                <input type="text" name="phone_number" required placeholder="e.g. 09171234567" class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-colors">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Email Address</label>
                <input type="email" name="email" placeholder="client@example.com" class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-colors">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Address / Residence</label>
                <textarea name="address" rows="2" placeholder="e.g. 142 Rizal St., Makati City" class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-colors"></textarea>
            </div>

            <div class="flex items-center gap-4 pt-1">
                <label class="inline-flex items-center gap-2 text-xs font-semibold text-slate-700 cursor-pointer">
                    <input type="checkbox" name="is_vip" value="1" class="w-4 h-4 rounded text-red-600 focus:ring-red-500 border-slate-300">
                    <span>Mark as VIP Account</span>
                </label>
                <label class="inline-flex items-center gap-2 text-xs font-semibold text-slate-700 cursor-pointer">
                    <input type="checkbox" name="is_returning" value="1" class="w-4 h-4 rounded text-red-600 focus:ring-red-500 border-slate-300">
                    <span>Returning Customer</span>
                </label>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Customer Preferences / Notes</label>
                <textarea name="notes" rows="2" placeholder="e.g. Preferred vehicle types, corporate invoicing info..." class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-colors"></textarea>
            </div>

            <div class="pt-4 border-t border-slate-100 flex justify-end gap-2">
                <button type="button" onclick="closeCreateCustomerModal()" class="px-4 py-2 text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2 text-xs font-semibold text-white bg-red-600 hover:bg-red-700 active:bg-red-800 rounded-lg transition-colors shadow-xs">
                    Register Client
                </button>
            </div>
        </form>
    </div>
</div>


<!-- 2. EDIT CUSTOMER MODAL -->
<div id="modal-edit-customer" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 modal-backdrop-custom overflow-y-auto">
    <div class="bg-white border border-slate-200/80 rounded-2xl shadow-2xl max-w-md w-full my-auto overflow-hidden">
        <div class="px-6 py-4.5 bg-white border-b border-slate-100 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 border border-blue-100/80 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Edit Customer Profile</h3>
                    <p class="text-xs text-slate-500">Update client contact info and status</p>
                </div>
            </div>
            <button type="button" onclick="closeEditCustomerModal()" class="w-8 h-8 flex items-center justify-center text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg transition-colors">&times;</button>
        </div>

        <form method="POST" action="index.php?view=customers" class="p-6 space-y-4">
            <input type="hidden" name="action" value="update_customer">
            <input type="hidden" name="customer_id" id="edit_customer_id" value="">

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">First Name *</label>
                    <input type="text" name="first_name" id="edit_cust_first_name" required class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-colors">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Last Name *</label>
                    <input type="text" name="last_name" id="edit_cust_last_name" required class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-colors">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Mobile Phone *</label>
                <input type="text" name="phone_number" id="edit_cust_phone" required class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-colors">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Email Address</label>
                <input type="email" name="email" id="edit_cust_email" class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-colors">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Address</label>
                <textarea name="address" id="edit_cust_address" rows="2" class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-colors"></textarea>
            </div>

            <div class="flex items-center gap-4 pt-1">
                <label class="inline-flex items-center gap-2 text-xs font-semibold text-slate-700 cursor-pointer">
                    <input type="checkbox" name="is_vip" id="edit_cust_is_vip" value="1" class="w-4 h-4 rounded text-red-600 focus:ring-red-500 border-slate-300">
                    <span>VIP Account</span>
                </label>
                <label class="inline-flex items-center gap-2 text-xs font-semibold text-slate-700 cursor-pointer">
                    <input type="checkbox" name="is_returning" id="edit_cust_is_returning" value="1" class="w-4 h-4 rounded text-red-600 focus:ring-red-500 border-slate-300">
                    <span>Returning Client</span>
                </label>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Notes</label>
                <textarea name="notes" id="edit_cust_notes" rows="2" class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-colors"></textarea>
            </div>

            <div class="pt-4 border-t border-slate-100 flex justify-end gap-2">
                <button type="button" onclick="closeEditCustomerModal()" class="px-4 py-2 text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 active:bg-blue-800 rounded-lg transition-colors shadow-xs">
                    Update Profile
                </button>
            </div>
        </form>
    </div>
</div>


<!-- 3. VIEW CUSTOMER DETAILS MODAL -->
<div id="modal-view-customer" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 modal-backdrop-custom overflow-y-auto">
    <div class="bg-white border border-slate-200/80 rounded-2xl shadow-2xl max-w-lg w-full my-auto overflow-hidden">
        <div class="p-5 bg-white border-b border-slate-100 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-slate-100 text-slate-700 border border-slate-200 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </div>
                <div>
                    <div class="text-[10px] uppercase font-bold tracking-wider text-slate-400">Client Profile</div>
                    <div class="text-sm font-extrabold text-slate-900 mt-0.5" id="view-cust-name"></div>
                </div>
            </div>
            <div id="view-cust-badges"></div>
        </div>

        <div class="p-5 space-y-3.5 text-xs">
            <div class="grid grid-cols-2 gap-3 pb-3 border-b border-slate-100">
                <div>
                    <span class="text-[10px] uppercase font-bold text-slate-400">Phone</span>
                    <div class="font-mono font-bold text-slate-800 text-xs mt-0.5" id="view-cust-phone"></div>
                </div>
                <div>
                    <span class="text-[10px] uppercase font-bold text-slate-400">Email</span>
                    <div class="text-slate-800 mt-0.5" id="view-cust-email"></div>
                </div>
            </div>

            <div class="pb-3 border-b border-slate-100">
                <span class="text-[10px] uppercase font-bold text-slate-400">Address</span>
                <div class="text-slate-800 mt-0.5" id="view-cust-address"></div>
            </div>

            <div class="p-3.5 bg-slate-50 border border-slate-200/80 rounded-xl flex items-center justify-between">
                <div>
                    <span class="text-[10px] uppercase font-bold text-slate-400">Total Bookings</span>
                    <div class="text-base font-extrabold text-slate-900 mt-0.5" id="view-cust-trips">0</div>
                </div>
                <div class="text-right">
                    <span class="text-[10px] uppercase font-bold text-slate-400">Lifetime Spent</span>
                    <div class="text-base font-extrabold text-red-600 mt-0.5" id="view-cust-spent">₱ 0.00</div>
                </div>
            </div>

            <div id="view-cust-notes-box">
                <span class="text-[10px] uppercase font-bold text-slate-400">Notes &amp; Preferences</span>
                <div class="p-3 bg-slate-50 border border-slate-200/80 rounded-xl text-slate-600 italic mt-1 text-xs" id="view-cust-notes"></div>
            </div>
        </div>

        <div class="px-5 py-3 bg-slate-50 border-t border-slate-100 flex justify-end">
            <button type="button" onclick="closeViewCustomerModal()" class="px-4 py-1.5 text-xs font-semibold text-slate-700 bg-white hover:bg-slate-100 border border-slate-200 rounded-lg transition-colors">
                Close
            </button>
        </div>
    </div>
</div>


<!-- 4. DELETE CUSTOMER MODAL -->
<div id="modal-delete-customer" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 modal-backdrop-custom overflow-y-auto">
    <div class="bg-white border border-slate-200/80 rounded-2xl shadow-2xl max-w-sm w-full my-auto overflow-hidden">
        <div class="p-6 text-center space-y-3.5">
            <div class="w-12 h-12 mx-auto rounded-xl bg-red-50 border border-red-100 text-red-600 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
            </div>
            <h3 class="text-sm font-extrabold text-slate-900">Delete Client Account</h3>
            <p class="text-xs text-slate-500 leading-relaxed">
                Are you sure you want to remove <strong id="delete-cust-name" class="text-slate-900"></strong>? Note: customers with existing bookings cannot be deleted.
            </p>

            <form method="POST" action="index.php?view=customers" class="pt-2 flex justify-center gap-2">
                <input type="hidden" name="action" value="delete_customer">
                <input type="hidden" name="customer_id" id="delete_customer_id" value="">

                <button type="button" onclick="closeDeleteCustomerModal()" class="px-4 py-2 text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 text-xs font-semibold text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors shadow-xs">
                    Confirm Delete
                </button>
            </form>
        </div>
    </div>
</div>

