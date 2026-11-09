<?php
use App\Maintenance\MaintenanceService;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$maintenanceService = new MaintenanceService();

$flashSuccess = '';
$flashError   = '';

if (!empty($_GET['msg'])) {
    $flashSuccess = match ($_GET['msg']) {
        'created' => 'Maintenance record logged successfully.',
        'updated' => 'Maintenance log updated successfully.',
        'deleted' => 'Maintenance record removed.',
        default   => ''
    };
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    try {
        if ($action === 'create_maintenance') {
            $maintenanceService->createLog($_POST);
            header('Location: index.php?view=maintenance&msg=created');
            exit;
        }

        if ($action === 'update_maintenance') {
            $logId = (int)($_POST['maintenance_id'] ?? 0);
            $maintenanceService->updateLog($logId, $_POST);
            header('Location: index.php?view=maintenance&msg=updated');
            exit;
        }

        if ($action === 'delete_maintenance') {
            $logId = (int)($_POST['maintenance_id'] ?? 0);
            $maintenanceService->deleteLog($logId);
            header('Location: index.php?view=maintenance&msg=deleted');
            exit;
        }
    } catch (\Throwable $e) {
        $flashError = $e->getMessage();
    }
}

$selectedVehicle = $_GET['vehicle_id'] ?? 'all';
$searchTerm      = trim($_GET['search'] ?? '');
$dateFrom        = trim($_GET['date_from'] ?? '');
$dateTo          = trim($_GET['date_to'] ?? '');

$filterParams = [
    'vehicle_id' => $selectedVehicle,
    'search'     => $searchTerm,
    'date_from'  => $dateFrom,
    'date_to'    => $dateTo,
];

$stats = [
    'total_maintenance_logs'  => 0,
    'vehicles_in_maintenance' => 0,
    'logs_this_month'         => 0,
];
$logs     = [];
$vehicles = [];

try {
    $stats    = $maintenanceService->getStats();
    $logs     = $maintenanceService->getLogs($filterParams);
    $vehicles = $maintenanceService->getVehicles();
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
                <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Maintenance &amp; Repairs</h1>
                <span class="px-2.5 py-0.5 text-xs font-semibold bg-red-50 text-red-600 border border-red-200/80 rounded-full">
                    <?= count($logs) ?> Logs
                </span>
            </div>
            <p class="text-xs text-slate-500 mt-1 font-medium">
                Log vehicle servicing, track odometer updates, preventative care, and repair logs.
            </p>
        </div>

        <div>
            <button
                type="button"
                onclick="openCreateMaintenanceModal()"
                class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold text-white bg-red-600 hover:bg-red-700 active:bg-red-800 rounded-lg shadow-xs transition-colors"
            >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <span>Log Service Record</span>
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
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
        <div class="bg-white p-4 border border-slate-200 rounded-xl shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Total Service Logs</span>
                <span class="p-1.5 bg-slate-100 rounded-lg text-slate-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                </span>
            </div>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-2xl font-black text-slate-900"><?= $stats['total_maintenance_logs'] ?></span>
                <span class="text-[10px] text-slate-400 font-medium">all-time records</span>
            </div>
        </div>

        <div class="bg-white p-4 border border-amber-200 bg-amber-50/20 rounded-xl shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-amber-800 uppercase tracking-wider">Vehicles in Shop</span>
                <span class="p-1.5 bg-amber-100 rounded-lg text-amber-800">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </span>
            </div>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-2xl font-black text-amber-800"><?= $stats['vehicles_in_maintenance'] ?></span>
                <span class="text-[10px] text-amber-600 font-semibold">under repair</span>
            </div>
        </div>

        <div class="bg-white p-4 border border-blue-200 bg-blue-50/20 rounded-xl shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-blue-700 uppercase tracking-wider">Logged This Month</span>
                <span class="p-1.5 bg-blue-100 rounded-lg text-blue-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </span>
            </div>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-2xl font-black text-blue-700"><?= $stats['logs_this_month'] ?></span>
                <span class="text-[10px] text-blue-600 font-semibold">recent services</span>
            </div>
        </div>
    </div>

    <!-- Toolbar -->
    <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-xs space-y-3">
        <form method="GET" action="index.php" class="flex flex-col md:flex-row md:items-center justify-between gap-3">
            <input type="hidden" name="view" value="maintenance">

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
                    placeholder="Search Service Type, Vehicle, Plate, or Notes..."
                    class="w-full pl-9 pr-3.5 py-2 text-xs font-medium text-slate-900 bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-colors"
                >
            </div>

            <div class="flex items-center gap-2">
                <select
                    name="vehicle_id"
                    onchange="this.form.submit()"
                    class="px-2.5 py-2 text-xs font-semibold bg-slate-50 border border-slate-200 rounded-lg text-slate-700 focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-colors"
                >
                    <option value="all">All Vehicles</option>
                    <?php foreach ($vehicles as $veh): ?>
                        <option value="<?= $veh['vehicle_id'] ?>" <?= (string)$selectedVehicle === (string)$veh['vehicle_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($veh['make'] . ' ' . $veh['model'] . ' [' . $veh['license_plate'] . ']') ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <div class="flex items-center gap-1.5 bg-slate-50 border border-slate-200 px-2.5 py-1.5 rounded-lg">
                    <span class="text-[11px] font-bold text-slate-500 uppercase">From</span>
                    <input type="date" name="date_from" value="<?= htmlspecialchars($dateFrom) ?>" class="text-xs bg-transparent border-none p-0 text-slate-800 focus:outline-none">
                </div>

                <div class="flex items-center gap-1.5 bg-slate-50 border border-slate-200 px-2.5 py-1.5 rounded-lg">
                    <span class="text-[11px] font-bold text-slate-500 uppercase">To</span>
                    <input type="date" name="date_to" value="<?= htmlspecialchars($dateTo) ?>" class="text-xs bg-transparent border-none p-0 text-slate-800 focus:outline-none">
                </div>

                <button
                    type="submit"
                    class="px-3.5 py-2 bg-slate-800 hover:bg-slate-700 text-white text-xs font-semibold rounded-lg transition-colors shadow-xs"
                >
                    Filter
                </button>

                <?php if ($searchTerm !== '' || $selectedVehicle !== 'all' || $dateFrom !== '' || $dateTo !== ''): ?>
                <a
                    href="index.php?view=maintenance"
                    class="px-2.5 py-2 text-xs font-semibold text-red-600 hover:text-red-700 bg-red-50 hover:bg-red-100 border border-red-200 rounded-lg transition-colors"
                >
                    Reset
                </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Maintenance Data Table Card -->
    <div class="bg-white border border-slate-200 rounded-xl shadow-xs overflow-hidden">
        <?php if (empty($logs)): ?>
        <div class="py-16 text-center">
            <div class="w-12 h-12 mx-auto rounded-xl bg-red-50 border border-red-200 text-red-600 flex items-center justify-center mb-3">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
            </div>
            <h3 class="text-sm font-bold text-slate-800">No Service Logs Found</h3>
            <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">
                No maintenance records match your search or date criteria.
            </p>
            <div class="mt-4">
                <button type="button" onclick="openCreateMaintenanceModal()" class="px-3.5 py-1.5 text-xs font-semibold text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors">
                    Log Maintenance Service
                </button>
            </div>
        </div>
        <?php else: ?>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/80 border-b border-slate-200 text-slate-600">
                        <th class="py-3 px-4 font-bold text-[11px] uppercase tracking-wider">Log #</th>
                        <th class="py-3 px-4 font-bold text-[11px] uppercase tracking-wider">Serviced Vehicle</th>
                        <th class="py-3 px-4 font-bold text-[11px] uppercase tracking-wider">Service Type</th>
                        <th class="py-3 px-4 font-bold text-[11px] uppercase tracking-wider">Service Date</th>
                        <th class="py-3 px-4 font-bold text-[11px] uppercase tracking-wider">Odometer Mileage</th>
                        <th class="py-3 px-4 font-bold text-[11px] uppercase tracking-wider">Work Notes</th>
                        <th class="py-3 px-4 font-bold text-[11px] uppercase tracking-wider text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">
                    <?php foreach ($logs as $l): ?>
                    <tr class="hover:bg-slate-50/80 transition-colors">

                        <!-- ID -->
                        <td class="py-3 px-4 whitespace-nowrap font-mono font-semibold text-slate-700">
                            #M-<?= str_pad((string)$l['maintenance_id'], 4, '0', STR_PAD_LEFT) ?>
                        </td>

                        <!-- Serviced Vehicle -->
                        <td class="py-3 px-4 whitespace-nowrap">
                            <div class="font-bold text-slate-900">
                                <?= htmlspecialchars($l['vehicle_make'] . ' ' . $l['vehicle_model']) ?>
                            </div>
                            <div class="flex items-center gap-1.5 text-[11px] text-slate-500 mt-0.5">
                                <span class="font-mono font-semibold bg-slate-100 px-1.5 py-0.5 rounded-md border border-slate-200 text-slate-700">
                                    <?= htmlspecialchars($l['vehicle_plate']) ?>
                                </span>
                                <span>&bull; <?= htmlspecialchars($l['vehicle_type']) ?></span>
                            </div>
                        </td>

                        <!-- Service Type -->
                        <td class="py-3 px-4">
                            <span class="font-bold text-slate-900">
                                <?= htmlspecialchars($l['service_type']) ?>
                            </span>
                        </td>

                        <!-- Service Date -->
                        <td class="py-3 px-4 whitespace-nowrap text-slate-700 font-medium">
                            <?= htmlspecialchars($l['service_date_fmt']) ?>
                        </td>

                        <!-- Mileage -->
                        <td class="py-3 px-4 whitespace-nowrap font-mono font-semibold text-slate-800">
                            <?= htmlspecialchars($l['formatted_mileage']) ?>
                        </td>

                        <!-- Notes -->
                        <td class="py-3 px-4">
                            <div class="max-w-[220px] truncate text-slate-600 italic" title="<?= htmlspecialchars($l['notes'] ?? '') ?>">
                                <?= !empty($l['notes']) ? htmlspecialchars($l['notes']) : '<span class="text-slate-400 not-italic">-</span>' ?>
                            </div>
                        </td>

                        <!-- Actions -->
                        <td class="py-3 px-4 whitespace-nowrap text-right">
                            <div class="inline-flex items-center gap-1">
                                <!-- View Log -->
                                <button
                                    type="button"
                                    onclick="viewMaintenanceDetails(<?= htmlspecialchars(json_encode($l), ENT_QUOTES, 'UTF-8') ?>)"
                                    title="View Service Details"
                                    class="p-1.5 text-slate-500 hover:text-slate-900 hover:bg-slate-100 border border-transparent hover:border-slate-200 rounded-lg transition-colors"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </button>

                                <!-- Edit Log -->
                                <button
                                    type="button"
                                    onclick="openEditMaintenanceModal(<?= htmlspecialchars(json_encode($l), ENT_QUOTES, 'UTF-8') ?>)"
                                    title="Edit Service Record"
                                    class="p-1.5 text-slate-500 hover:text-blue-600 hover:bg-blue-50 border border-transparent hover:border-blue-200 rounded-lg transition-colors"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>

                                <!-- Delete Log -->
                                <button
                                    type="button"
                                    onclick="openDeleteMaintenanceModal(<?= $l['maintenance_id'] ?>, '<?= htmlspecialchars($l['service_type']) ?>', '<?= htmlspecialchars($l['vehicle_plate']) ?>')"
                                    title="Delete Record"
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
     MAINTENANCE MODALS
     ========================================================================= -->

<!-- 1. CREATE MAINTENANCE MODAL -->
<div id="modal-create-maintenance" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 modal-backdrop-custom overflow-y-auto">
    <div class="bg-white border border-slate-200/80 rounded-2xl shadow-2xl max-w-md w-full my-auto overflow-hidden">
        <div class="px-6 py-4 bg-white text-slate-900 flex items-center justify-between border-b border-slate-100">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-red-50 text-red-600 flex items-center justify-center border border-red-100/80">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Log Vehicle Maintenance</h3>
                    <p class="text-[11px] text-slate-500 font-medium">Schedule service or record completed maintenance</p>
                </div>
            </div>
            <button type="button" onclick="closeCreateMaintenanceModal()" class="w-8 h-8 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 flex items-center justify-center text-lg">&times;</button>
        </div>

        <form method="POST" action="index.php?view=maintenance" class="p-6 space-y-4">
            <input type="hidden" name="action" value="create_maintenance">

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Target Vehicle *</label>
                <select name="vehicle_id" id="create_maint_veh_id" required onchange="updateOdometerPlaceholder(this)" class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500">
                    <option value="">-- Choose Serviced Vehicle --</option>
                    <?php foreach ($vehicles as $veh): ?>
                        <option value="<?= $veh['vehicle_id'] ?>" data-mileage="<?= (int)$veh['mileage'] ?>">
                            <?= htmlspecialchars($veh['make'] . ' ' . $veh['model'] . ' [' . $veh['license_plate'] . ']') ?> (Current: <?= number_format($veh['mileage']) ?> km)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Service Type *</label>
                <input type="text" name="service_type" required placeholder="e.g. Periodic Maintenance, Brake Pad Replacement" class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Service Date *</label>
                    <input type="date" name="service_date" value="<?= date('Y-m-d') ?>" required class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Mileage at Service (km) *</label>
                    <input type="number" name="mileage" id="create_maint_mileage" min="0" required class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Update Vehicle Duty Status</label>
                <select name="vehicle_status_after" class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500">
                    <option value="">-- Keep Current Status --</option>
                    <option value="Available">Set Vehicle to Available (Work Finished)</option>
                    <option value="Maintenance">Keep in Maintenance (Still in Bay)</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Service Notes &amp; Replaced Parts</label>
                <textarea name="notes" rows="3" placeholder="e.g. Synthetic oil replaced, genuine brake pads installed, tire rotation performed..." class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500"></textarea>
            </div>

            <div class="pt-4 border-t border-slate-100 flex justify-end gap-2.5">
                <button type="button" onclick="closeCreateMaintenanceModal()" class="px-4 py-2 text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg transition">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2 text-xs font-bold text-white bg-red-600 hover:bg-red-700 rounded-lg shadow-sm transition">
                    Save Service Record
                </button>
            </div>
        </form>
    </div>
</div>


<!-- 2. EDIT MAINTENANCE MODAL -->
<div id="modal-edit-maintenance" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 modal-backdrop-custom overflow-y-auto">
    <div class="bg-white border border-slate-200/80 rounded-2xl shadow-2xl max-w-md w-full my-auto overflow-hidden">
        <div class="px-6 py-4 bg-white text-slate-900 flex items-center justify-between border-b border-slate-100">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center border border-blue-100/80">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Edit Service Record <span id="edit-maint-id-display" class="font-mono text-red-600 text-xs font-semibold"></span></h3>
                    <p class="text-[11px] text-slate-500 font-medium">Update maintenance log details</p>
                </div>
            </div>
            <button type="button" onclick="closeEditMaintenanceModal()" class="w-8 h-8 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 flex items-center justify-center text-lg">&times;</button>
        </div>

        <form method="POST" action="index.php?view=maintenance" class="p-6 space-y-4">
            <input type="hidden" name="action" value="update_maintenance">
            <input type="hidden" name="maintenance_id" id="edit_maint_id" value="">

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Vehicle *</label>
                <select name="vehicle_id" id="edit_maint_vehicle_id" required class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500">
                    <?php foreach ($vehicles as $veh): ?>
                        <option value="<?= $veh['vehicle_id'] ?>">
                            <?= htmlspecialchars($veh['make'] . ' ' . $veh['model'] . ' [' . $veh['license_plate'] . ']') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Service Type *</label>
                <input type="text" name="service_type" id="edit_maint_service_type" required class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Service Date *</label>
                    <input type="date" name="service_date" id="edit_maint_date" required class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Mileage (km) *</label>
                    <input type="number" name="mileage" id="edit_maint_mileage" min="0" required class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Notes</label>
                <textarea name="notes" id="edit_maint_notes" rows="3" class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500"></textarea>
            </div>

            <div class="pt-4 border-t border-slate-100 flex justify-end gap-2.5">
                <button type="button" onclick="closeEditMaintenanceModal()" class="px-4 py-2 text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg transition">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-sm transition">
                    Update Record
                </button>
            </div>
        </form>
    </div>
</div>


<!-- 3. VIEW MAINTENANCE MODAL -->
<div id="modal-view-maintenance" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 modal-backdrop-custom overflow-y-auto">
    <div class="bg-white border border-slate-200/80 rounded-2xl shadow-2xl max-w-sm w-full my-auto overflow-hidden">
        <div class="px-6 py-4 bg-white text-slate-900 flex items-center justify-between border-b border-slate-100">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-slate-100 text-slate-600 flex items-center justify-center border border-slate-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <div>
                    <div class="text-[10px] uppercase font-bold tracking-wider text-slate-400">Service Record Details</div>
                    <div class="text-sm font-extrabold font-mono text-slate-900" id="view-maint-id"></div>
                </div>
            </div>
            <button type="button" onclick="closeViewMaintenanceModal()" class="w-8 h-8 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 flex items-center justify-center text-lg">&times;</button>
        </div>

        <div class="p-6 space-y-4 text-xs">
            <div class="p-3 bg-slate-50 border border-slate-200/80 rounded-xl">
                <span class="text-[10px] uppercase font-bold text-slate-400">Vehicle</span>
                <div class="font-extrabold text-slate-900 text-sm mt-0.5" id="view-maint-veh"></div>
                <div class="font-mono text-slate-500 font-semibold text-xs" id="view-maint-plate"></div>
            </div>

            <div>
                <span class="text-[10px] uppercase font-bold text-slate-400">Service Type</span>
                <div class="font-bold text-slate-800 text-sm mt-0.5" id="view-maint-type"></div>
            </div>

            <div class="grid grid-cols-2 gap-3 pb-3 border-b border-slate-100">
                <div>
                    <span class="text-[10px] uppercase font-bold text-slate-400">Date</span>
                    <div class="text-slate-800 font-medium mt-0.5" id="view-maint-date"></div>
                </div>
                <div>
                    <span class="text-[10px] uppercase font-bold text-slate-400">Odometer</span>
                    <div class="font-mono font-bold text-slate-800 mt-0.5" id="view-maint-mileage"></div>
                </div>
            </div>

            <div>
                <span class="text-[10px] uppercase font-bold text-slate-400">Service Notes</span>
                <div class="p-3 bg-slate-50 border border-slate-200/80 rounded-xl text-slate-600 text-xs mt-1" id="view-maint-notes"></div>
            </div>
        </div>

        <div class="px-6 py-3.5 bg-slate-50 border-t border-slate-100 flex justify-end">
            <button type="button" onclick="closeViewMaintenanceModal()" class="px-4 py-2 text-xs font-bold text-slate-700 bg-white border border-slate-200 hover:bg-slate-100 rounded-lg shadow-sm transition">
                Close
            </button>
        </div>
    </div>
</div>


<!-- 4. DELETE MAINTENANCE MODAL -->
<div id="modal-delete-maintenance" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 modal-backdrop-custom overflow-y-auto">
    <div class="bg-white border border-slate-200/80 rounded-2xl shadow-2xl max-w-sm w-full my-auto overflow-hidden">
        <div class="p-6 text-center space-y-3">
            <div class="w-12 h-12 mx-auto rounded-full bg-red-50 text-red-600 border border-red-100/80 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
            </div>
            <h3 class="text-sm font-bold text-slate-900">Delete Maintenance Log</h3>
            <p class="text-xs text-slate-500 leading-relaxed">
                Are you sure you want to delete <strong id="delete-maint-type" class="text-slate-900"></strong> for [<span id="delete-maint-plate" class="font-mono"></span>]?
            </p>

            <form method="POST" action="index.php?view=maintenance" class="pt-3 flex justify-center gap-2.5">
                <input type="hidden" name="action" value="delete_maintenance">
                <input type="hidden" name="maintenance_id" id="delete_maintenance_id" value="">

                <button type="button" onclick="closeDeleteMaintenanceModal()" class="px-4 py-2 text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg transition">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 text-xs font-bold text-white bg-red-600 hover:bg-red-700 rounded-lg shadow-sm transition">
                    Confirm Delete
                </button>
            </form>
        </div>
    </div>
</div>

