<?php
use App\Drivers\DriversService;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$driversService = new DriversService();

$flashSuccess = '';
$flashError   = '';

if (!empty($_GET['msg'])) {
    $flashSuccess = match ($_GET['msg']) {
        'created'        => 'Staff driver registered successfully.',
        'updated'        => 'Driver record updated successfully.',
        'status_updated' => 'Driver duty status updated successfully.',
        'deleted'        => 'Driver record removed.',
        default          => ''
    };
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    try {
        if ($action === 'create_driver') {
            $driversService->createDriver($_POST);
            header('Location: index.php?view=drivers&msg=created');
            exit;
        }

        if ($action === 'update_driver') {
            $driverId = (int)($_POST['driver_id'] ?? 0);
            $driversService->updateDriver($driverId, $_POST);
            header('Location: index.php?view=drivers&msg=updated');
            exit;
        }

        if ($action === 'update_status') {
            $driverId  = (int)($_POST['driver_id'] ?? 0);
            $newStatus = trim($_POST['status'] ?? '');
            $driversService->updateStatus($driverId, $newStatus);
            header('Location: index.php?view=drivers&msg=status_updated');
            exit;
        }

        if ($action === 'delete_driver') {
            $driverId = (int)($_POST['driver_id'] ?? 0);
            $driversService->deleteDriver($driverId);
            header('Location: index.php?view=drivers&msg=deleted');
            exit;
        }
    } catch (\Throwable $e) {
        $flashError = $e->getMessage();
    }
}

$selectedStatus = $_GET['status'] ?? 'all';
$searchTerm     = trim($_GET['search'] ?? '');

$filterParams = [
    'status' => $selectedStatus,
    'search' => $searchTerm,
];

$stats = [
    'total_drivers'   => 0,
    'available_count' => 0,
    'on_trip_count'   => 0,
    'on_leave_count'  => 0,
];
$drivers = [];

try {
    $stats   = $driversService->getStats();
    $drivers = $driversService->getDrivers($filterParams);
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
                <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Drivers &amp; Chauffeurs</h1>
                <span class="px-2.5 py-0.5 text-xs font-semibold bg-red-50 text-red-600 border border-red-200/80 rounded-full">
                    <?= count($drivers) ?> Chauffeurs
                </span>
            </div>
            <p class="text-xs text-slate-500 mt-1 font-medium">
                Manage staff drivers, dispatch rosters, license compliance, and trip assignments.
            </p>
        </div>

        <div>
            <button
                type="button"
                onclick="openCreateDriverModal()"
                class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold text-white bg-red-600 hover:bg-red-700 active:bg-red-800 rounded-lg shadow-xs transition-colors"
            >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <span>Add Staff Driver</span>
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
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Total Chauffeurs</span>
                <span class="p-1.5 bg-slate-100 rounded-lg text-slate-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </span>
            </div>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-2xl font-black text-slate-900"><?= $stats['total_drivers'] ?></span>
                <span class="text-[10px] text-slate-400 font-medium">staff</span>
            </div>
        </div>

        <div class="bg-white p-4 border border-emerald-200 bg-emerald-50/20 rounded-xl shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-emerald-700 uppercase tracking-wider">Available for Duty</span>
                <span class="p-1.5 bg-emerald-100 rounded-lg text-emerald-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </span>
            </div>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-2xl font-black text-emerald-700"><?= $stats['available_count'] ?></span>
                <span class="text-[10px] text-emerald-600 font-semibold">ready to dispatch</span>
            </div>
        </div>

        <div class="bg-white p-4 border border-blue-200 bg-blue-50/20 rounded-xl shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-blue-700 uppercase tracking-wider">Currently On Trip</span>
                <span class="p-1.5 bg-blue-100 rounded-lg text-blue-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </span>
            </div>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-2xl font-black text-blue-700"><?= $stats['on_trip_count'] ?></span>
                <span class="text-[10px] text-blue-600 font-semibold">on the road</span>
            </div>
        </div>

        <div class="bg-white p-4 border border-slate-200 rounded-xl shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">On Leave / Off-Duty</span>
                <span class="p-1.5 bg-slate-100 rounded-lg text-slate-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </span>
            </div>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-2xl font-black text-slate-700"><?= $stats['on_leave_count'] ?></span>
                <span class="text-[10px] text-slate-400 font-medium">scheduled off</span>
            </div>
        </div>
    </div>

    <!-- Toolbar -->
    <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-xs space-y-3">
        <form method="GET" action="index.php" class="flex flex-col md:flex-row md:items-center justify-between gap-3">
            <input type="hidden" name="view" value="drivers">

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
                    placeholder="Search Driver Name, Phone Number, or License..."
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

                <?php if ($searchTerm !== '' || $selectedStatus !== 'all'): ?>
                <a
                    href="index.php?view=drivers"
                    class="px-2.5 py-2 text-xs font-semibold text-red-600 hover:text-red-700 bg-red-50 hover:bg-red-100 border border-red-200 rounded-lg transition-colors"
                >
                    Reset
                </a>
                <?php endif; ?>
            </div>
        </form>

        <!-- Status Filter Tabs -->
        <div class="flex items-center gap-1.5 pt-3 border-t border-slate-100 overflow-x-auto">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mr-2">Status:</span>
            <?php
            $driverTabs = [
                'all'       => 'All Drivers (' . $stats['total_drivers'] . ')',
                'Available' => 'Available (' . $stats['available_count'] . ')',
                'On Trip'   => 'On Trip (' . $stats['on_trip_count'] . ')',
                'On Leave'  => 'On Leave (' . $stats['on_leave_count'] . ')',
            ];
            foreach ($driverTabs as $dVal => $dLabel):
                $isActive = ($selectedStatus === $dVal);
            ?>
            <a
                href="index.php?view=drivers&status=<?= urlencode($dVal) ?><?= $searchTerm !== '' ? '&search=' . urlencode($searchTerm) : '' ?>"
                class="px-3 py-1 text-xs font-semibold whitespace-nowrap rounded-lg transition-colors <?= $isActive ? 'bg-slate-800 text-white shadow-xs' : 'bg-slate-100 hover:bg-slate-200 text-slate-600' ?>"
            >
                <?= $dLabel ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Drivers Data Table Card -->
    <div class="bg-white border border-slate-200 rounded-xl shadow-xs overflow-hidden">
        <?php if (empty($drivers)): ?>
        <div class="py-16 text-center">
            <div class="w-12 h-12 mx-auto rounded-xl bg-red-50 border border-red-200 text-red-600 flex items-center justify-center mb-3">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
            </div>
            <h3 class="text-sm font-bold text-slate-800">No Drivers Found</h3>
            <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">
                No staff drivers match your current filter or search criteria.
            </p>
            <div class="mt-4">
                <button type="button" onclick="openCreateDriverModal()" class="px-3.5 py-1.5 text-xs font-semibold text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors">
                    Register Staff Driver
                </button>
            </div>
        </div>
        <?php else: ?>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/80 border-b border-slate-200 text-slate-600">
                        <th class="py-3 px-4 font-bold text-[11px] uppercase tracking-wider">Driver Name</th>
                        <th class="py-3 px-4 font-bold text-[11px] uppercase tracking-wider">Mobile Phone</th>
                        <th class="py-3 px-4 font-bold text-[11px] uppercase tracking-wider">Driver License #</th>
                        <th class="py-3 px-4 font-bold text-[11px] uppercase tracking-wider">License Expiry</th>
                        <th class="py-3 px-4 font-bold text-[11px] uppercase tracking-wider">Duty Status</th>
                        <th class="py-3 px-4 font-bold text-[11px] uppercase tracking-wider">Trip History</th>
                        <th class="py-3 px-4 font-bold text-[11px] uppercase tracking-wider text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">
                    <?php foreach ($drivers as $d): ?>
                    <tr class="hover:bg-slate-50/80 transition-colors">

                        <!-- Driver Name -->
                        <td class="py-3 px-4">
                            <div class="font-bold text-slate-900">
                                <?= htmlspecialchars($d['full_name']) ?>
                            </div>
                        </td>

                        <!-- Phone -->
                        <td class="py-3 px-4 whitespace-nowrap">
                            <span class="font-mono font-semibold text-slate-800">
                                <?= htmlspecialchars($d['phone_number']) ?>
                            </span>
                        </td>

                        <!-- License Number -->
                        <td class="py-3 px-4 whitespace-nowrap">
                            <span class="font-mono font-semibold text-slate-900 bg-slate-100 border border-slate-200 px-2 py-0.5 rounded-md">
                                <?= htmlspecialchars($d['license_number']) ?>
                            </span>
                        </td>

                        <!-- License Expiry -->
                        <td class="py-3 px-4 whitespace-nowrap">
                            <span class="<?= $d['license_expiry_info']['class'] ?>">
                                <?= htmlspecialchars($d['license_expiry_info']['text']) ?>
                            </span>
                        </td>

                        <!-- Duty Status -->
                        <td class="py-3 px-4 whitespace-nowrap">
                            <div class="relative inline-flex items-center gap-1">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-[11px] font-semibold border rounded-full <?= $d['status_badge']['bg'] ?> <?= $d['status_badge']['text'] ?> <?= $d['status_badge']['border'] ?>">
                                    <span class="w-1.5 h-1.5 rounded-full <?= $d['status_badge']['dot'] ?>"></span>
                                    <span><?= htmlspecialchars($d['status']) ?></span>
                                </span>
                                <button
                                    type="button"
                                    onclick="promptDriverStatusChange(<?= $d['driver_id'] ?>, '<?= $d['status'] ?>', '<?= htmlspecialchars($d['full_name']) ?>')"
                                    title="Quick Status Change"
                                    class="p-1 text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded-full transition-colors focus:outline-none"
                                >
                                    <svg class="w-3.5 h-3.5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                            </div>
                        </td>

                        <!-- Trip History -->
                        <td class="py-3 px-4 whitespace-nowrap">
                            <span class="font-bold text-slate-800"><?= (int)$d['total_trips'] ?></span>
                            <span class="text-slate-500">trips completed</span>
                        </td>

                        <!-- Actions -->
                        <td class="py-3 px-4 whitespace-nowrap text-right">
                            <div class="inline-flex items-center gap-1">
                                <!-- View Driver -->
                                <button
                                    type="button"
                                    onclick="viewDriverDetails(<?= htmlspecialchars(json_encode($d), ENT_QUOTES, 'UTF-8') ?>)"
                                    title="View Driver Info"
                                    class="p-1.5 text-slate-500 hover:text-slate-900 hover:bg-slate-100 border border-transparent hover:border-slate-200 rounded-lg transition-colors"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </button>

                                <!-- Edit Driver -->
                                <button
                                    type="button"
                                    onclick="openEditDriverModal(<?= htmlspecialchars(json_encode($d), ENT_QUOTES, 'UTF-8') ?>)"
                                    title="Edit Driver"
                                    class="p-1.5 text-slate-500 hover:text-blue-600 hover:bg-blue-50 border border-transparent hover:border-blue-200 rounded-lg transition-colors"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>

                                <!-- Delete Driver -->
                                <button
                                    type="button"
                                    onclick="openDeleteDriverModal(<?= $d['driver_id'] ?>, '<?= htmlspecialchars($d['full_name']) ?>')"
                                    title="Delete Driver"
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
     DRIVERS MODALS
     ========================================================================= -->

<!-- 1. CREATE DRIVER MODAL -->
<div id="modal-create-driver" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 modal-backdrop-custom overflow-y-auto">
    <div class="bg-white border border-slate-200/80 rounded-2xl shadow-2xl max-w-md w-full my-auto overflow-hidden">
        <div class="px-6 py-4.5 bg-white border-b border-slate-100 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-red-50 text-red-600 border border-red-100/80 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Register Staff Chauffeur</h3>
                    <p class="text-xs text-slate-500">Add a professional driver to dispatch roster</p>
                </div>
            </div>
            <button type="button" onclick="closeCreateDriverModal()" class="w-8 h-8 flex items-center justify-center text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg transition-colors">&times;</button>
        </div>

        <form method="POST" action="index.php?view=drivers" class="p-6 space-y-4">
            <input type="hidden" name="action" value="create_driver">

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">First Name *</label>
                    <input type="text" name="first_name" required placeholder="e.g. Juan" class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-colors">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Last Name *</label>
                    <input type="text" name="last_name" required placeholder="e.g. Dela Cruz" class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-colors">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Mobile Phone *</label>
                <input type="text" name="phone_number" required placeholder="e.g. 09193334444" class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-colors">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">License Number *</label>
                    <input type="text" name="license_number" required placeholder="e.g. N01-15-889012" class="w-full px-3 py-2 text-xs font-mono uppercase bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-colors">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">License Expiry *</label>
                    <input type="date" name="license_expiry" required class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-colors">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Initial Duty Status</label>
                <select name="status" class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-colors">
                    <option value="Available" selected>Available (Ready for Dispatch)</option>
                    <option value="On Trip">On Trip</option>
                    <option value="On Leave">On Leave</option>
                </select>
            </div>

            <div class="pt-4 border-t border-slate-100 flex justify-end gap-2">
                <button type="button" onclick="closeCreateDriverModal()" class="px-4 py-2 text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2 text-xs font-semibold text-white bg-red-600 hover:bg-red-700 active:bg-red-800 rounded-lg transition-colors shadow-xs">
                    Register Driver
                </button>
            </div>
        </form>
    </div>
</div>


<!-- 2. EDIT DRIVER MODAL -->
<div id="modal-edit-driver" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 modal-backdrop-custom overflow-y-auto">
    <div class="bg-white border border-slate-200/80 rounded-2xl shadow-2xl max-w-md w-full my-auto overflow-hidden">
        <div class="px-6 py-4.5 bg-white border-b border-slate-100 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 border border-blue-100/80 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Edit Driver Record</h3>
                    <p class="text-xs text-slate-500">Update license and duty information</p>
                </div>
            </div>
            <button type="button" onclick="closeEditDriverModal()" class="w-8 h-8 flex items-center justify-center text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg transition-colors">&times;</button>
        </div>

        <form method="POST" action="index.php?view=drivers" class="p-6 space-y-4">
            <input type="hidden" name="action" value="update_driver">
            <input type="hidden" name="driver_id" id="edit_driver_id" value="">

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">First Name *</label>
                    <input type="text" name="first_name" id="edit_d_first_name" required class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-colors">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Last Name *</label>
                    <input type="text" name="last_name" id="edit_d_last_name" required class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-colors">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Mobile Phone *</label>
                <input type="text" name="phone_number" id="edit_d_phone" required class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-colors">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">License Number *</label>
                    <input type="text" name="license_number" id="edit_d_license" required class="w-full px-3 py-2 text-xs font-mono uppercase bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-colors">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">License Expiry *</label>
                    <input type="date" name="license_expiry" id="edit_d_expiry" required class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-colors">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Duty Status</label>
                <select name="status" id="edit_d_status" class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-colors">
                    <option value="Available">Available (Ready)</option>
                    <option value="On Trip">On Trip</option>
                    <option value="On Leave">On Leave</option>
                </select>
            </div>

            <div class="pt-4 border-t border-slate-100 flex justify-end gap-2">
                <button type="button" onclick="closeEditDriverModal()" class="px-4 py-2 text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 active:bg-blue-800 rounded-lg transition-colors shadow-xs">
                    Update Driver
                </button>
            </div>
        </form>
    </div>
</div>


<!-- 3. VIEW DRIVER DETAILS MODAL -->
<div id="modal-view-driver" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 modal-backdrop-custom overflow-y-auto">
    <div class="bg-white border border-slate-200/80 rounded-2xl shadow-2xl max-w-sm w-full my-auto overflow-hidden">
        <div class="p-5 bg-white border-b border-slate-100 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-slate-100 text-slate-700 border border-slate-200 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </div>
                <div>
                    <div class="text-[10px] uppercase font-bold tracking-wider text-slate-400">Chauffeur Profile</div>
                    <div class="text-sm font-extrabold text-slate-900 mt-0.5" id="view-driver-name"></div>
                </div>
            </div>
            <div id="view-driver-badge"></div>
        </div>

        <div class="p-5 space-y-3.5 text-xs">
            <div>
                <span class="text-[10px] uppercase font-bold text-slate-400">Mobile Phone</span>
                <div class="font-mono font-bold text-slate-900 mt-0.5" id="view-driver-phone"></div>
            </div>

            <div class="grid grid-cols-2 gap-3 pb-3 border-b border-slate-100">
                <div>
                    <span class="text-[10px] uppercase font-bold text-slate-400">License Number</span>
                    <div class="font-mono text-slate-900 mt-0.5" id="view-driver-license"></div>
                </div>
                <div>
                    <span class="text-[10px] uppercase font-bold text-slate-400">License Expiry</span>
                    <div class="mt-0.5" id="view-driver-expiry"></div>
                </div>
            </div>

            <div class="p-3 bg-slate-50 border border-slate-200/80 rounded-xl flex items-center justify-between">
                <div class="text-slate-600">Total Trips Dispatched:</div>
                <strong id="view-driver-trips" class="text-slate-900 font-bold">0</strong>
            </div>
        </div>

        <div class="px-5 py-3 bg-slate-50 border-t border-slate-100 flex justify-end">
            <button type="button" onclick="closeViewDriverModal()" class="px-4 py-1.5 text-xs font-semibold text-slate-700 bg-white hover:bg-slate-100 border border-slate-200 rounded-lg transition-colors">
                Close
            </button>
        </div>
    </div>
</div>


<!-- 4. QUICK STATUS MODAL -->
<div id="modal-driver-status" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 modal-backdrop-custom overflow-y-auto">
    <div class="bg-white border border-slate-200/80 rounded-2xl shadow-2xl max-w-xs w-full my-auto overflow-hidden">
        <div class="px-5 py-4 bg-white border-b border-slate-100 flex items-center justify-between">
            <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Change Duty Status</h4>
            <button type="button" onclick="closeDriverStatusModal()" class="w-7 h-7 flex items-center justify-center text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg transition-colors">&times;</button>
        </div>

        <form method="POST" action="index.php?view=drivers" class="p-5 space-y-3.5">
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" name="driver_id" id="driver_status_id" value="">

            <div>
                <label class="block text-[11px] font-bold text-slate-500 uppercase mb-0.5">Driver</label>
                <div id="driver_status_name" class="font-bold text-xs text-slate-900"></div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">New Duty Status</label>
                <select name="status" id="driver_status_select" required class="w-full px-3 py-2 text-xs font-semibold bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-colors">
                    <option value="Available">Available (Ready to Dispatch)</option>
                    <option value="On Trip">On Trip (Active)</option>
                    <option value="On Leave">On Leave (Off-Duty)</option>
                </select>
            </div>

            <div class="pt-2 flex justify-end gap-2">
                <button type="button" onclick="closeDriverStatusModal()" class="px-3.5 py-1.5 text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-1.5 text-xs font-semibold text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors shadow-xs">
                    Update
                </button>
            </div>
        </form>
    </div>
</div>


<!-- 5. DELETE DRIVER MODAL -->
<div id="modal-delete-driver" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 modal-backdrop-custom overflow-y-auto">
    <div class="bg-white border border-slate-200/80 rounded-2xl shadow-2xl max-w-sm w-full my-auto overflow-hidden">
        <div class="p-6 text-center space-y-3.5">
            <div class="w-12 h-12 mx-auto rounded-xl bg-red-50 border border-red-100 text-red-600 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
            </div>
            <h3 class="text-sm font-extrabold text-slate-900">Delete Driver Record</h3>
            <p class="text-xs text-slate-500 leading-relaxed">
                Are you sure you want to remove <strong id="delete-driver-name" class="text-slate-900"></strong>? Drivers currently on trips cannot be removed.
            </p>

            <form method="POST" action="index.php?view=drivers" class="pt-2 flex justify-center gap-2">
                <input type="hidden" name="action" value="delete_driver">
                <input type="hidden" name="driver_id" id="delete_driver_id" value="">

                <button type="button" onclick="closeDeleteDriverModal()" class="px-4 py-2 text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 text-xs font-semibold text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors shadow-xs">
                    Confirm Delete
                </button>
            </form>
        </div>
    </div>
</div>

