<?php
use App\Vehicles\VehiclesService;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$vehiclesService = new VehiclesService();

$flashSuccess = '';
$flashError   = '';

if (!empty($_GET['msg'])) {
    $flashSuccess = match ($_GET['msg']) {
        'created'        => 'New vehicle added to fleet successfully.',
        'updated'        => 'Vehicle information updated successfully.',
        'status_updated' => 'Vehicle status updated successfully.',
        'deleted'        => 'Vehicle record removed from fleet.',
        default          => ''
    };
}

// Handle incoming POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    try {
        if ($action === 'create_vehicle') {
            $vehiclesService->createVehicle($_POST);
            header('Location: index.php?view=vehicles&msg=created');
            exit;
        }

        if ($action === 'update_vehicle') {
            $vehicleId = (int)($_POST['vehicle_id'] ?? 0);
            $vehiclesService->updateVehicle($vehicleId, $_POST);
            header('Location: index.php?view=vehicles&msg=updated');
            exit;
        }

        if ($action === 'update_status') {
            $vehicleId = (int)($_POST['vehicle_id'] ?? 0);
            $newStatus = trim($_POST['status'] ?? '');
            $vehiclesService->updateStatus($vehicleId, $newStatus);
            header('Location: index.php?view=vehicles&msg=status_updated');
            exit;
        }

        if ($action === 'delete_vehicle') {
            $vehicleId = (int)($_POST['vehicle_id'] ?? 0);
            $vehiclesService->deleteVehicle($vehicleId);
            header('Location: index.php?view=vehicles&msg=deleted');
            exit;
        }
    } catch (\Throwable $e) {
        $flashError = $e->getMessage();
    }
}

// Filters
$selectedStatus = $_GET['status'] ?? 'all';
$selectedType   = $_GET['type'] ?? 'all';
$searchTerm     = trim($_GET['search'] ?? '');

$filterParams = [
    'status' => $selectedStatus,
    'type'   => $selectedType,
    'search' => $searchTerm,
];

$stats = [
    'total_vehicles'      => 0,
    'available_count'     => 0,
    'rented_count'        => 0,
    'maintenance_count'   => 0,
    'inactive_count'      => 0,
    'total_fleet_mileage' => '0 km',
];
$vehicles = [];

try {
    $stats    = $vehiclesService->getStats();
    $vehicles = $vehiclesService->getVehicles($filterParams);
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
                <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Vehicles Fleet</h1>
                <span class="px-2.5 py-0.5 text-xs font-semibold bg-red-50 text-red-600 border border-red-200/80 rounded-full">
                    <?= count($vehicles) ?> Units
                </span>
            </div>
            <p class="text-xs text-slate-500 mt-1 font-medium">
                Manage fleet inventory, seating capacities, license compliance, and availability.
            </p>
        </div>

        <div>
            <button
                type="button"
                onclick="openCreateVehicleModal()"
                class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold text-white bg-red-600 hover:bg-red-700 active:bg-red-800 rounded-lg shadow-xs transition-colors"
            >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <span>Add Fleet Vehicle</span>
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
    <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-5 gap-3.5">

        <div class="bg-white p-4 border border-slate-200 rounded-xl shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Total Fleet</span>
                <span class="p-1.5 bg-slate-100 rounded-lg text-slate-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                    </svg>
                </span>
            </div>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-2xl font-black text-slate-900"><?= $stats['total_vehicles'] ?></span>
                <span class="text-[10px] text-slate-400 font-medium">registered</span>
            </div>
        </div>

        <div class="bg-white p-4 border border-emerald-200 bg-emerald-50/20 rounded-xl shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-emerald-700 uppercase tracking-wider">Available for Rent</span>
                <span class="p-1.5 bg-emerald-100 rounded-lg text-emerald-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </span>
            </div>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-2xl font-black text-emerald-700"><?= $stats['available_count'] ?></span>
                <span class="text-[10px] text-emerald-600 font-semibold">ready for dispatch</span>
            </div>
        </div>

        <div class="bg-white p-4 border border-blue-200 bg-blue-50/20 rounded-xl shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-blue-700 uppercase tracking-wider">Currently Rented</span>
                <span class="p-1.5 bg-blue-100 rounded-lg text-blue-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </span>
            </div>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-2xl font-black text-blue-700"><?= $stats['rented_count'] ?></span>
                <span class="text-[10px] text-blue-600 font-semibold">active on trips</span>
            </div>
        </div>

        <div class="bg-white p-4 border border-amber-200 bg-amber-50/20 rounded-xl shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-amber-800 uppercase tracking-wider">In Maintenance</span>
                <span class="p-1.5 bg-amber-100 rounded-lg text-amber-800">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    </svg>
                </span>
            </div>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-2xl font-black text-amber-800"><?= $stats['maintenance_count'] ?></span>
                <span class="text-[10px] text-amber-600 font-semibold">service bay</span>
            </div>
        </div>

        <div class="col-span-2 sm:col-span-2 lg:col-span-1 bg-white p-4 border border-slate-200 rounded-xl shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Fleet Odometer</span>
                <span class="p-1.5 bg-red-100 rounded-lg text-red-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                </span>
            </div>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-xl font-extrabold text-red-600"><?= $stats['total_fleet_mileage'] ?></span>
            </div>
        </div>

    </div>

    <!-- Filter & Search Toolbar -->
    <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-xs space-y-3">
        <form method="GET" action="index.php" class="flex flex-col md:flex-row md:items-center justify-between gap-3">
            <input type="hidden" name="view" value="vehicles">

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
                    placeholder="Search Make, Model, License Plate, or VIN..."
                    class="w-full pl-9 pr-3.5 py-2 text-xs font-medium text-slate-900 bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-colors"
                >
            </div>

            <div class="flex items-center gap-2">
                <select
                    name="type"
                    onchange="this.form.submit()"
                    class="px-2.5 py-2 text-xs font-semibold bg-slate-50 border border-slate-200 rounded-lg text-slate-700 focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-colors"
                >
                    <option value="all">All Vehicle Types</option>
                    <option value="SUV" <?= $selectedType === 'SUV' ? 'selected' : '' ?>>SUV</option>
                    <option value="Van" <?= $selectedType === 'Van' ? 'selected' : '' ?>>Van</option>
                    <option value="Sedan" <?= $selectedType === 'Sedan' ? 'selected' : '' ?>>Sedan</option>
                    <option value="MPV" <?= $selectedType === 'MPV' ? 'selected' : '' ?>>MPV</option>
                    <option value="Pickup" <?= $selectedType === 'Pickup' ? 'selected' : '' ?>>Pickup</option>
                </select>

                <button
                    type="submit"
                    class="px-3.5 py-2 bg-slate-800 hover:bg-slate-700 text-white text-xs font-semibold rounded-lg transition-colors shadow-xs"
                >
                    Search
                </button>

                <?php if ($searchTerm !== '' || $selectedStatus !== 'all' || $selectedType !== 'all'): ?>
                <a
                    href="index.php?view=vehicles"
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
            $statusTabs = [
                'all'         => 'All Units (' . $stats['total_vehicles'] . ')',
                'Available'   => 'Available (' . $stats['available_count'] . ')',
                'Rented'      => 'Rented (' . $stats['rented_count'] . ')',
                'Maintenance' => 'Maintenance (' . $stats['maintenance_count'] . ')',
                'Inactive'    => 'Inactive (' . $stats['inactive_count'] . ')',
            ];
            foreach ($statusTabs as $sVal => $sLabel):
                $isActive = ($selectedStatus === $sVal);
            ?>
            <a
                href="index.php?view=vehicles&status=<?= urlencode($sVal) ?><?= $searchTerm !== '' ? '&search=' . urlencode($searchTerm) : '' ?><?= $selectedType !== 'all' ? '&type=' . urlencode($selectedType) : '' ?>"
                class="px-3 py-1 text-xs font-semibold whitespace-nowrap rounded-lg transition-colors <?= $isActive ? 'bg-slate-800 text-white shadow-xs' : 'bg-slate-100 hover:bg-slate-200 text-slate-600' ?>"
            >
                <?= $sLabel ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Vehicles Data Table Card -->
    <div class="bg-white border border-slate-200 rounded-xl shadow-xs overflow-hidden">
        <?php if (empty($vehicles)): ?>
        <div class="py-16 text-center">
            <div class="w-12 h-12 mx-auto rounded-xl bg-red-50 border border-red-200 text-red-600 flex items-center justify-center mb-3">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <h3 class="text-sm font-bold text-slate-800">No Vehicles Found</h3>
            <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">
                No fleet units match the current filter or search criteria.
            </p>
            <div class="mt-4 flex items-center justify-center gap-2">
                <button type="button" onclick="openCreateVehicleModal()" class="px-3.5 py-1.5 text-xs font-semibold text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors">
                    Add New Vehicle
                </button>
            </div>
        </div>
        <?php else: ?>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/80 border-b border-slate-200 text-slate-600">
                        <th class="py-3 px-4 font-bold text-[11px] uppercase tracking-wider">Vehicle Details</th>
                        <th class="py-3 px-4 font-bold text-[11px] uppercase tracking-wider">Plate &amp; VIN</th>
                        <th class="py-3 px-4 font-bold text-[11px] uppercase tracking-wider">Type / Capacity</th>
                        <th class="py-3 px-4 font-bold text-[11px] uppercase tracking-wider">Odometer</th>
                        <th class="py-3 px-4 font-bold text-[11px] uppercase tracking-wider">Registration Expiry</th>
                        <th class="py-3 px-4 font-bold text-[11px] uppercase tracking-wider">Insurance Expiry</th>
                        <th class="py-3 px-4 font-bold text-[11px] uppercase tracking-wider">Status</th>
                        <th class="py-3 px-4 font-bold text-[11px] uppercase tracking-wider text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">
                    <?php foreach ($vehicles as $v): ?>
                    <tr class="hover:bg-slate-50/80 transition-colors">

                        <!-- Vehicle Make / Model / Year -->
                        <td class="py-3 px-4">
                            <div class="font-bold text-slate-900">
                                <?= htmlspecialchars($v['make'] . ' ' . $v['model']) ?>
                            </div>
                            <div class="text-[11px] text-slate-500 mt-0.5">
                                Year <?= htmlspecialchars((string)$v['year']) ?> &bull; <?= (int)$v['total_trips'] ?> lifetime trips
                            </div>
                        </td>

                        <!-- Plate & VIN -->
                        <td class="py-3 px-4 whitespace-nowrap">
                            <div class="font-mono font-semibold text-slate-900 bg-slate-100 border border-slate-200 px-2 py-0.5 rounded-md inline-block">
                                <?= htmlspecialchars($v['license_plate']) ?>
                            </div>
                            <div class="text-[10px] text-slate-400 font-mono mt-0.5">
                                VIN: <?= htmlspecialchars($v['vin']) ?>
                            </div>
                        </td>

                        <!-- Type & Seating Capacity -->
                        <td class="py-3 px-4 whitespace-nowrap">
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-semibold bg-slate-100 text-slate-700 border border-slate-200 rounded-md">
                                <?= htmlspecialchars($v['type']) ?>
                            </span>
                            <div class="text-[11px] text-slate-500 font-medium mt-0.5">
                                <?= (int)$v['seating_capacity'] ?> Passengers max
                            </div>
                        </td>

                        <!-- Mileage -->
                        <td class="py-3 px-4 whitespace-nowrap">
                            <span class="font-mono font-semibold text-slate-800">
                                <?= htmlspecialchars($v['formatted_mileage']) ?>
                            </span>
                        </td>

                        <!-- Registration Expiry -->
                        <td class="py-3 px-4 whitespace-nowrap">
                            <span class="<?= $v['reg_expiry_info']['class'] ?>">
                                <?= htmlspecialchars($v['reg_expiry_info']['text']) ?>
                            </span>
                        </td>

                        <!-- Insurance Expiry -->
                        <td class="py-3 px-4 whitespace-nowrap">
                            <span class="<?= $v['ins_expiry_info']['class'] ?>">
                                <?= htmlspecialchars($v['ins_expiry_info']['text']) ?>
                            </span>
                        </td>

                        <!-- Status Badge -->
                        <td class="py-3 px-4 whitespace-nowrap">
                            <div class="relative inline-flex items-center gap-1">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-[11px] font-semibold border rounded-full <?= $v['status_badge']['bg'] ?> <?= $v['status_badge']['text'] ?> <?= $v['status_badge']['border'] ?>">
                                    <span class="w-1.5 h-1.5 rounded-full <?= $v['status_badge']['dot'] ?>"></span>
                                    <span><?= htmlspecialchars($v['status']) ?></span>
                                </span>
                                <button
                                    type="button"
                                    onclick="promptVehicleStatusChange(<?= $v['vehicle_id'] ?>, '<?= $v['status'] ?>', '<?= htmlspecialchars($v['make'] . ' ' . $v['model']) ?>', '<?= htmlspecialchars($v['license_plate']) ?>')"
                                    title="Quick Change Status"
                                    class="p-1 text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded-full transition-colors focus:outline-none"
                                >
                                    <svg class="w-3.5 h-3.5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                            </div>
                        </td>

                        <!-- Actions -->
                        <td class="py-3 px-4 whitespace-nowrap text-right">
                            <div class="inline-flex items-center gap-1">
                                <!-- View Details -->
                                <button
                                    type="button"
                                    onclick="viewVehicleDetails(<?= htmlspecialchars(json_encode($v), ENT_QUOTES, 'UTF-8') ?>)"
                                    title="View Vehicle Info"
                                    class="p-1.5 text-slate-500 hover:text-slate-900 hover:bg-slate-100 border border-transparent hover:border-slate-200 rounded-lg transition-colors"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </button>

                                <!-- Edit Vehicle -->
                                <button
                                    type="button"
                                    onclick="openEditVehicleModal(<?= htmlspecialchars(json_encode($v), ENT_QUOTES, 'UTF-8') ?>)"
                                    title="Edit Fleet Info"
                                    class="p-1.5 text-slate-500 hover:text-blue-600 hover:bg-blue-50 border border-transparent hover:border-blue-200 rounded-lg transition-colors"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>

                                <!-- Delete Vehicle -->
                                <button
                                    type="button"
                                    onclick="openDeleteVehicleModal(<?= $v['vehicle_id'] ?>, '<?= htmlspecialchars($v['make'] . ' ' . $v['model']) ?>', '<?= htmlspecialchars($v['license_plate']) ?>')"
                                    title="Delete Vehicle"
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
     VEHICLES MODALS
     ========================================================================= -->

<!-- 1. CREATE VEHICLE MODAL -->
<div id="modal-create-vehicle" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 modal-backdrop-custom overflow-y-auto">
    <div class="bg-white border border-slate-200/80 rounded-2xl shadow-2xl max-w-xl w-full max-h-[90vh] flex flex-col my-auto overflow-hidden">
        <div class="px-6 py-4.5 bg-white border-b border-slate-100 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-red-50 text-red-600 border border-red-100/80 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Register New Fleet Unit</h3>
                    <p class="text-xs text-slate-500">Add a vehicle to active operations inventory</p>
                </div>
            </div>
            <button type="button" onclick="closeCreateVehicleModal()" class="w-8 h-8 flex items-center justify-center text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg transition-colors">&times;</button>
        </div>

        <form method="POST" action="index.php?view=vehicles" class="flex-1 overflow-y-auto p-6 space-y-4">
            <input type="hidden" name="action" value="create_vehicle">

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Make *</label>
                    <input type="text" name="make" required placeholder="e.g. Toyota" class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-colors">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Model *</label>
                    <input type="text" name="model" required placeholder="e.g. Fortuner GR-S" class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-colors">
                </div>
            </div>

            <div class="grid grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Year *</label>
                    <input type="number" name="year" value="<?= date('Y') ?>" min="1990" max="<?= date('Y') + 2 ?>" required class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-colors">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Type *</label>
                    <select name="type" required class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-colors">
                        <option value="SUV">SUV</option>
                        <option value="Van">Van</option>
                        <option value="Sedan">Sedan</option>
                        <option value="MPV">MPV</option>
                        <option value="Pickup">Pickup</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Seats (Pax) *</label>
                    <input type="number" name="seating_capacity" value="7" min="1" max="60" required class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-colors">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">License Plate *</label>
                    <input type="text" name="license_plate" required placeholder="e.g. NGA-8821" class="w-full px-3 py-2 text-xs font-mono uppercase bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-colors">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">VIN / Chassis # *</label>
                    <input type="text" name="vin" required placeholder="e.g. VIN-TOY-FT-2024" class="w-full px-3 py-2 text-xs font-mono uppercase bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-colors">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Current Mileage (km)</label>
                    <input type="number" name="mileage" value="0" min="0" class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-colors">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Initial Status</label>
                    <select name="status" class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-colors">
                        <option value="Available" selected>Available (Ready)</option>
                        <option value="Maintenance">Maintenance</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Registration Expiry *</label>
                    <input type="date" name="registration_expiry" required class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-colors">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Insurance Expiry *</label>
                    <input type="date" name="insurance_expiry" required class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-colors">
                </div>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2">
                <button type="button" onclick="closeCreateVehicleModal()" class="px-4 py-2 text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2 text-xs font-semibold text-white bg-red-600 hover:bg-red-700 active:bg-red-800 rounded-lg transition-colors shadow-xs">
                    Register Vehicle
                </button>
            </div>
        </form>
    </div>
</div>


<!-- 2. EDIT VEHICLE MODAL -->
<div id="modal-edit-vehicle" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 modal-backdrop-custom overflow-y-auto">
    <div class="bg-white border border-slate-200/80 rounded-2xl shadow-2xl max-w-xl w-full max-h-[90vh] flex flex-col my-auto overflow-hidden">
        <div class="px-6 py-4.5 bg-white border-b border-slate-100 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 border border-blue-100/80 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Edit Vehicle <span id="edit-veh-title" class="text-blue-600 font-mono text-xs"></span></h3>
                    <p class="text-xs text-slate-500">Update unit specifications and compliance records</p>
                </div>
            </div>
            <button type="button" onclick="closeEditVehicleModal()" class="w-8 h-8 flex items-center justify-center text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg transition-colors">&times;</button>
        </div>

        <form method="POST" action="index.php?view=vehicles" class="flex-1 overflow-y-auto p-6 space-y-4">
            <input type="hidden" name="action" value="update_vehicle">
            <input type="hidden" name="vehicle_id" id="edit_vehicle_id" value="">

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Make *</label>
                    <input type="text" name="make" id="edit_make" required class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-colors">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Model *</label>
                    <input type="text" name="model" id="edit_model" required class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-colors">
                </div>
            </div>

            <div class="grid grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Year *</label>
                    <input type="number" name="year" id="edit_year" required class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-colors">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Type *</label>
                    <select name="type" id="edit_type" required class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-colors">
                        <option value="SUV">SUV</option>
                        <option value="Van">Van</option>
                        <option value="Sedan">Sedan</option>
                        <option value="MPV">MPV</option>
                        <option value="Pickup">Pickup</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Seats *</label>
                    <input type="number" name="seating_capacity" id="edit_seating_capacity" min="1" max="60" required class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-colors">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">License Plate *</label>
                    <input type="text" name="license_plate" id="edit_license_plate" required class="w-full px-3 py-2 text-xs font-mono uppercase bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-colors">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">VIN *</label>
                    <input type="text" name="vin" id="edit_vin" required class="w-full px-3 py-2 text-xs font-mono uppercase bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-colors">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Mileage (km)</label>
                    <input type="number" name="mileage" id="edit_mileage" min="0" class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-colors">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Status</label>
                    <select name="status" id="edit_status" class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-colors">
                        <option value="Available">Available</option>
                        <option value="Rented">Rented</option>
                        <option value="Maintenance">Maintenance</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Registration Expiry *</label>
                    <input type="date" name="registration_expiry" id="edit_registration_expiry" required class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-colors">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Insurance Expiry *</label>
                    <input type="date" name="insurance_expiry" id="edit_insurance_expiry" required class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-colors">
                </div>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2">
                <button type="button" onclick="closeEditVehicleModal()" class="px-4 py-2 text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 active:bg-blue-800 rounded-lg transition-colors shadow-xs">
                    Update Vehicle
                </button>
            </div>
        </form>
    </div>
</div>


<!-- 3. VIEW VEHICLE DETAILS MODAL -->
<div id="modal-view-vehicle" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 modal-backdrop-custom overflow-y-auto">
    <div class="bg-white border border-slate-200/80 rounded-2xl shadow-2xl max-w-md w-full my-auto overflow-hidden">
        <div class="p-5 bg-white border-b border-slate-100 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-slate-100 text-slate-700 border border-slate-200 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0" />
                    </svg>
                </div>
                <div>
                    <div class="text-[10px] uppercase font-bold tracking-wider text-slate-400">Fleet Unit Specification</div>
                    <div class="text-sm font-extrabold text-slate-900 mt-0.5" id="view-veh-name"></div>
                </div>
            </div>
            <div id="view-veh-badge"></div>
        </div>

        <div class="p-5 space-y-3.5 text-xs">
            <div class="grid grid-cols-2 gap-3 pb-3 border-b border-slate-100">
                <div>
                    <span class="text-[10px] uppercase font-bold text-slate-400">License Plate</span>
                    <div class="font-mono font-bold text-slate-900 text-sm mt-0.5" id="view-veh-plate"></div>
                </div>
                <div>
                    <span class="text-[10px] uppercase font-bold text-slate-400">VIN / Chassis</span>
                    <div class="font-mono text-slate-700 mt-0.5" id="view-veh-vin"></div>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-2 pb-3 border-b border-slate-100">
                <div>
                    <span class="text-[10px] uppercase font-bold text-slate-400">Body Type</span>
                    <div class="font-bold text-slate-800 mt-0.5" id="view-veh-type"></div>
                </div>
                <div>
                    <span class="text-[10px] uppercase font-bold text-slate-400">Seating</span>
                    <div class="font-bold text-slate-800 mt-0.5" id="view-veh-seats"></div>
                </div>
                <div>
                    <span class="text-[10px] uppercase font-bold text-slate-400">Odometer</span>
                    <div class="font-bold text-slate-800 mt-0.5" id="view-veh-mileage"></div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3 pb-3 border-b border-slate-100">
                <div>
                    <span class="text-[10px] uppercase font-bold text-slate-400">Registration Expiry</span>
                    <div class="mt-0.5" id="view-veh-reg"></div>
                </div>
                <div>
                    <span class="text-[10px] uppercase font-bold text-slate-400">Insurance Expiry</span>
                    <div class="mt-0.5" id="view-veh-ins"></div>
                </div>
            </div>

            <div class="p-3 bg-slate-50 border border-slate-200/80 rounded-xl flex items-center justify-between text-[11px] text-slate-600">
                <div>Total Trips: <strong id="view-veh-trips" class="text-slate-900">0</strong></div>
                <div>Service Records: <strong id="view-veh-maint" class="text-slate-900">0</strong></div>
            </div>
        </div>

        <div class="px-5 py-3 bg-slate-50 border-t border-slate-100 flex justify-end">
            <button type="button" onclick="closeViewVehicleModal()" class="px-4 py-1.5 text-xs font-semibold text-slate-700 bg-white hover:bg-slate-100 border border-slate-200 rounded-lg transition-colors">
                Close
            </button>
        </div>
    </div>
</div>


<!-- 4. QUICK STATUS MODAL -->
<div id="modal-veh-status" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 modal-backdrop-custom overflow-y-auto">
    <div class="bg-white border border-slate-200/80 rounded-2xl shadow-2xl max-w-xs w-full my-auto overflow-hidden">
        <div class="px-5 py-4 bg-white border-b border-slate-100 flex items-center justify-between">
            <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Change Unit Status</h4>
            <button type="button" onclick="closeVehStatusModal()" class="w-7 h-7 flex items-center justify-center text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg transition-colors">&times;</button>
        </div>

        <form method="POST" action="index.php?view=vehicles" class="p-5 space-y-3.5">
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" name="vehicle_id" id="veh_status_id" value="">

            <div>
                <label class="block text-[11px] font-bold text-slate-500 uppercase mb-0.5">Vehicle</label>
                <div id="veh_status_display" class="font-bold text-xs text-slate-900"></div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">New Fleet Status</label>
                <select name="status" id="veh_status_select" required class="w-full px-3 py-2 text-xs font-semibold bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-colors">
                    <option value="Available">Available (Ready for Dispatch)</option>
                    <option value="Rented">Rented (Active On Trip)</option>
                    <option value="Maintenance">Maintenance (Service Bay)</option>
                    <option value="Inactive">Inactive (Grounded)</option>
                </select>
            </div>

            <div class="pt-2 flex justify-end gap-2">
                <button type="button" onclick="closeVehStatusModal()" class="px-3.5 py-1.5 text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-1.5 text-xs font-semibold text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors shadow-xs">
                    Update
                </button>
            </div>
        </form>
    </div>
</div>


<!-- 5. DELETE VEHICLE MODAL -->
<div id="modal-delete-vehicle" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 modal-backdrop-custom overflow-y-auto">
    <div class="bg-white border border-slate-200/80 rounded-2xl shadow-2xl max-w-sm w-full my-auto overflow-hidden">
        <div class="p-6 text-center space-y-3.5">
            <div class="w-12 h-12 mx-auto rounded-xl bg-red-50 border border-red-100 text-red-600 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
            </div>
            <h3 class="text-sm font-extrabold text-slate-900">Delete Fleet Record</h3>
            <p class="text-xs text-slate-500 leading-relaxed">
                Are you sure you want to delete <strong id="delete-veh-name" class="text-slate-900"></strong> [<span id="delete-veh-plate" class="font-mono font-semibold"></span>]? This cannot be undone.
            </p>

            <form method="POST" action="index.php?view=vehicles" class="pt-2 flex justify-center gap-2">
                <input type="hidden" name="action" value="delete_vehicle">
                <input type="hidden" name="vehicle_id" id="delete_vehicle_id" value="">

                <button type="button" onclick="closeDeleteVehicleModal()" class="px-4 py-2 text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 text-xs font-semibold text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors shadow-xs">
                    Confirm Delete
                </button>
            </form>
        </div>
    </div>
</div>

