<?php
use App\Bookings\BookingsService;

// Ensure session exists
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$bookingsService = new BookingsService();

$flashSuccess = '';
$flashError   = '';

// Check query string flash messages (PRG pattern)
if (!empty($_GET['msg'])) {
    $flashSuccess = match ($_GET['msg']) {
        'created'        => 'Booking created successfully.',
        'updated'        => 'Booking updated successfully.',
        'status_updated' => 'Booking status updated successfully.',
        'deleted'        => 'Booking record removed successfully.',
        'customer_added' => 'New customer registered successfully.',
        default          => ''
    };
}

// Handle incoming POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    try {
        if ($action === 'create_booking') {
            $userId = (int)($_SESSION['user_id'] ?? 1);
            $bookingsService->createBooking($_POST, $userId);
            header('Location: index.php?view=bookings&msg=created');
            exit;
        }

        if ($action === 'update_booking') {
            $bookingId = (int)($_POST['booking_id'] ?? 0);
            $bookingsService->updateBooking($bookingId, $_POST);
            header('Location: index.php?view=bookings&msg=updated');
            exit;
        }

        if ($action === 'update_status') {
            $bookingId = (int)($_POST['booking_id'] ?? 0);
            $newStatus = trim($_POST['booking_status'] ?? '');
            $bookingsService->updateBookingStatus($bookingId, $newStatus);
            header('Location: index.php?view=bookings&msg=status_updated');
            exit;
        }

        if ($action === 'delete_booking') {
            $bookingId = (int)($_POST['booking_id'] ?? 0);
            $bookingsService->deleteBooking($bookingId);
            header('Location: index.php?view=bookings&msg=deleted');
            exit;
        }

        if ($action === 'quick_customer') {
            $bookingsService->createCustomer($_POST);
            header('Location: index.php?view=bookings&msg=customer_added');
            exit;
        }
    } catch (\Throwable $e) {
        $flashError = $e->getMessage();
    }
}

// Filters
$selectedStatus = $_GET['status'] ?? 'all';
$searchTerm     = trim($_GET['search'] ?? '');
$dateFrom       = trim($_GET['date_from'] ?? '');
$dateTo         = trim($_GET['date_to'] ?? '');

$filterParams = [
    'status'    => $selectedStatus,
    'search'    => $searchTerm,
    'date_from' => $dateFrom,
    'date_to'   => $dateTo,
];

// Data loading with graceful fallback
$stats = [
    'total_bookings'    => 0,
    'pending_count'     => 0,
    'confirmed_count'   => 0,
    'ongoing_count'     => 0,
    'completed_count'   => 0,
    'cancelled_count'   => 0,
    'formatted_revenue' => '₱ 0.00',
];
$bookings = [];
$formData = [
    'customers' => [],
    'vehicles'  => [],
    'drivers'   => [],
];

try {
    $stats    = $bookingsService->getDashboardStats();
    $bookings = $bookingsService->getBookings($filterParams);
    $formData = $bookingsService->getFormData();
} catch (\Throwable $e) {
    $flashError = 'Database error: ' . $e->getMessage();
}
?>

<!-- Main Container -->
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    <!-- Page Title & Primary Actions Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-slate-200">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Bookings Management</h1>
                <span class="px-2 py-0.5 text-xs font-bold bg-red-100 text-red-700 border border-red-200 rounded-[3px]">
                    <?= count($bookings) ?> Records
                </span>
            </div>
            <p class="text-xs text-slate-500 mt-1 font-medium">
                Dispatch, schedule, track client trips, and monitor active fleet operations.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <button
                type="button"
                onclick="openQuickCustomerModal()"
                class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-bold text-slate-700 bg-white hover:bg-slate-50 border border-slate-300 rounded-[4px] shadow-xs transition-colors"
            >
                <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                </svg>
                <span>New Customer</span>
            </button>

            <button
                type="button"
                onclick="openCreateBookingModal()"
                class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-bold text-white bg-red-600 hover:bg-red-700 active:bg-red-800 rounded-[4px] shadow-xs transition-colors"
            >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <span>Create Booking</span>
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

    <!-- Dashboard KPI Summary Cards (Sharp 4px Radius) -->
    <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-5 gap-3.5">

        <!-- Total Bookings -->
        <div class="bg-white p-4 border border-slate-200 rounded-[4px] shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Total Fleet Bookings</span>
                <span class="p-1.5 bg-slate-100 rounded-[3px] text-slate-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                </span>
            </div>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-2xl font-black text-slate-900"><?= $stats['total_bookings'] ?></span>
                <span class="text-[10px] text-slate-400 font-medium">all-time records</span>
            </div>
        </div>

        <!-- Ongoing Trips -->
        <div class="bg-white p-4 border border-emerald-200 bg-emerald-50/20 rounded-[4px] shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-emerald-700 uppercase tracking-wider">Active / Ongoing</span>
                <span class="p-1.5 bg-emerald-100 rounded-[3px] text-emerald-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </span>
            </div>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-2xl font-black text-emerald-700"><?= $stats['ongoing_count'] ?></span>
                <span class="text-[10px] text-emerald-600 font-semibold">on the road</span>
            </div>
        </div>

        <!-- Pending Approvals -->
        <div class="bg-white p-4 border border-amber-200 bg-amber-50/20 rounded-[4px] shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-amber-800 uppercase tracking-wider">Pending Bookings</span>
                <span class="p-1.5 bg-amber-100 rounded-[3px] text-amber-800">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </span>
            </div>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-2xl font-black text-amber-800"><?= $stats['pending_count'] ?></span>
                <span class="text-[10px] text-amber-600 font-semibold">awaiting action</span>
            </div>
        </div>

        <!-- Confirmed Upcoming -->
        <div class="bg-white p-4 border border-blue-200 bg-blue-50/20 rounded-[4px] shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-blue-700 uppercase tracking-wider">Confirmed Trips</span>
                <span class="p-1.5 bg-blue-100 rounded-[3px] text-blue-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </span>
            </div>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-2xl font-black text-blue-700"><?= $stats['confirmed_count'] ?></span>
                <span class="text-[10px] text-blue-600 font-semibold">scheduled</span>
            </div>
        </div>

        <!-- Gross Operations Revenue -->
        <div class="col-span-2 sm:col-span-2 lg:col-span-1 bg-white p-4 border border-slate-200 rounded-[4px] shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Gross Booking Value</span>
                <span class="p-1.5 bg-red-100 rounded-[3px] text-red-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </span>
            </div>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-xl font-extrabold text-red-600"><?= $stats['formatted_revenue'] ?></span>
            </div>
        </div>

    </div>

    <!-- Filter & Search Toolbar (Sharp 4px Radius) -->
    <div class="bg-white border border-slate-200 rounded-[4px] p-4 shadow-xs space-y-3">
        <form method="GET" action="index.php" class="flex flex-col md:flex-row md:items-center justify-between gap-3">
            <input type="hidden" name="view" value="bookings">

            <!-- Left: Search Box -->
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
                    placeholder="Search by Reference, Customer, Plate, Destination..."
                    class="w-full pl-9 pr-3.5 py-2 text-xs font-medium text-slate-900 bg-slate-50 border border-slate-300 rounded-[4px] focus:outline-none focus:ring-1 focus:ring-red-600 focus:border-red-600 transition-colors"
                >
            </div>

            <!-- Middle: Date Range Picker -->
            <div class="flex items-center gap-2">
                <div class="flex items-center gap-1.5 bg-slate-50 border border-slate-300 px-2 py-1 rounded-[4px]">
                    <span class="text-[11px] font-bold text-slate-500 uppercase">From</span>
                    <input
                        type="date"
                        name="date_from"
                        value="<?= htmlspecialchars($dateFrom) ?>"
                        class="text-xs bg-transparent border-none p-0 text-slate-800 focus:outline-none"
                    >
                </div>

                <div class="flex items-center gap-1.5 bg-slate-50 border border-slate-300 px-2 py-1 rounded-[4px]">
                    <span class="text-[11px] font-bold text-slate-500 uppercase">To</span>
                    <input
                        type="date"
                        name="date_to"
                        value="<?= htmlspecialchars($dateTo) ?>"
                        class="text-xs bg-transparent border-none p-0 text-slate-800 focus:outline-none"
                    >
                </div>

                <button
                    type="submit"
                    class="px-3 py-1.5 bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold rounded-[4px] transition-colors shadow-xs"
                >
                    Apply
                </button>

                <?php if ($searchTerm !== '' || $selectedStatus !== 'all' || $dateFrom !== '' || $dateTo !== ''): ?>
                <a
                    href="index.php?view=bookings"
                    class="px-2.5 py-1.5 text-xs font-bold text-red-600 hover:text-red-700 bg-red-50 hover:bg-red-100 border border-red-200 rounded-[4px] transition-colors"
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
            $statusOptions = [
                'all'       => 'All (' . $stats['total_bookings'] . ')',
                'Pending'   => 'Pending (' . $stats['pending_count'] . ')',
                'Confirmed' => 'Confirmed (' . $stats['confirmed_count'] . ')',
                'Ongoing'   => 'Ongoing (' . $stats['ongoing_count'] . ')',
                'Completed' => 'Completed (' . $stats['completed_count'] . ')',
                'Cancelled' => 'Cancelled (' . $stats['cancelled_count'] . ')',
            ];

            foreach ($statusOptions as $val => $label):
                $isActive = ($selectedStatus === $val);
            ?>
            <a
                href="index.php?view=bookings&status=<?= urlencode($val) ?><?= $searchTerm !== '' ? '&search=' . urlencode($searchTerm) : '' ?><?= $dateFrom !== '' ? '&date_from=' . urlencode($dateFrom) : '' ?><?= $dateTo !== '' ? '&date_to=' . urlencode($dateTo) : '' ?>"
                class="px-2.5 py-1 text-xs font-bold whitespace-nowrap rounded-[4px] transition-colors <?= $isActive ? 'bg-slate-900 text-white shadow-xs' : 'bg-slate-100 hover:bg-slate-200 text-slate-600' ?>"
            >
                <?= $label ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Bookings Data Table Card -->
    <div class="bg-white border border-slate-200 rounded-[4px] shadow-xs overflow-hidden">

        <?php if (empty($bookings)): ?>
        <div class="py-16 text-center">
            <div class="w-12 h-12 mx-auto rounded-[4px] bg-red-50 border border-red-200 text-red-600 flex items-center justify-center mb-3">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
            </div>
            <h3 class="text-sm font-bold text-slate-800">No Bookings Found</h3>
            <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">
                No reservation records match the active search criteria or status filter.
            </p>
            <div class="mt-4 flex items-center justify-center gap-2">
                <?php if ($searchTerm !== '' || $selectedStatus !== 'all' || $dateFrom !== '' || $dateTo !== ''): ?>
                <a href="index.php?view=bookings" class="px-3 py-1.5 text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-[4px] transition-colors">
                    Clear Filters
                </a>
                <?php endif; ?>
                <button type="button" onclick="openCreateBookingModal()" class="px-3.5 py-1.5 text-xs font-bold text-white bg-red-600 hover:bg-red-700 rounded-[4px] transition-colors">
                    Create New Booking
                </button>
            </div>
        </div>
        <?php else: ?>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse table-sharp">
                <thead>
                    <tr class="bg-slate-100 border-b border-slate-200 text-slate-600">
                        <th class="py-3 px-4 font-bold text-[11px] uppercase tracking-wider">Ref #</th>
                        <th class="py-3 px-4 font-bold text-[11px] uppercase tracking-wider">Customer</th>
                        <th class="py-3 px-4 font-bold text-[11px] uppercase tracking-wider">Vehicle Assigned</th>
                        <th class="py-3 px-4 font-bold text-[11px] uppercase tracking-wider">Driver</th>
                        <th class="py-3 px-4 font-bold text-[11px] uppercase tracking-wider">Destination &amp; Pax</th>
                        <th class="py-3 px-4 font-bold text-[11px] uppercase tracking-wider">Schedule / Duration</th>
                        <th class="py-3 px-4 font-bold text-[11px] uppercase tracking-wider">Amount</th>
                        <th class="py-3 px-4 font-bold text-[11px] uppercase tracking-wider">Status</th>
                        <th class="py-3 px-4 font-bold text-[11px] uppercase tracking-wider text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">
                    <?php foreach ($bookings as $b): ?>
                    <tr class="hover:bg-slate-50/80 transition-colors">

                        <!-- Reference -->
                        <td class="py-3 px-4 whitespace-nowrap">
                            <span class="font-mono font-bold text-slate-900 bg-slate-100 border border-slate-200 px-2 py-0.5 rounded-[3px]">
                                <?= htmlspecialchars($b['booking_reference']) ?>
                            </span>
                        </td>

                        <!-- Customer -->
                        <td class="py-3 px-4">
                            <div class="font-bold text-slate-900 flex items-center gap-1.5">
                                <span><?= htmlspecialchars($b['customer_name']) ?></span>
                                <?php if (!empty($b['customer_is_vip'])): ?>
                                    <span class="px-1.5 py-0.2 text-[9px] font-black uppercase tracking-wider bg-amber-100 text-amber-800 border border-amber-200 rounded-[2px]">VIP</span>
                                <?php endif; ?>
                            </div>
                            <div class="text-[11px] text-slate-500 font-mono mt-0.5">
                                <?= htmlspecialchars($b['customer_phone'] ?? '-') ?>
                            </div>
                        </td>

                        <!-- Vehicle -->
                        <td class="py-3 px-4">
                            <div class="font-semibold text-slate-800">
                                <?= htmlspecialchars($b['vehicle_make'] . ' ' . $b['vehicle_model']) ?>
                            </div>
                            <div class="flex items-center gap-1.5 text-[11px] text-slate-500 mt-0.5">
                                <span class="font-mono font-bold bg-slate-100 px-1.5 py-0.5 rounded-[2px] border border-slate-200 text-slate-700">
                                    <?= htmlspecialchars($b['vehicle_plate']) ?>
                                </span>
                                <span>&bull; <?= htmlspecialchars($b['vehicle_type']) ?></span>
                            </div>
                        </td>

                        <!-- Driver -->
                        <td class="py-3 px-4">
                            <?php if (!empty($b['driver_id'])): ?>
                                <div class="font-medium text-slate-800 flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    <span><?= htmlspecialchars($b['driver_name']) ?></span>
                                </div>
                                <div class="text-[10px] text-slate-400 font-mono">
                                    <?= htmlspecialchars($b['driver_phone'] ?? '') ?>
                                </div>
                            <?php else: ?>
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200 rounded-[3px]">
                                    Self-Drive
                                </span>
                            <?php endif; ?>
                        </td>

                        <!-- Destination & Passengers -->
                        <td class="py-3 px-4">
                            <div class="font-medium text-slate-800 max-w-[200px] truncate" title="<?= htmlspecialchars($b['destination']) ?>">
                                <?= htmlspecialchars($b['destination']) ?>
                            </div>
                            <div class="text-[11px] text-slate-500 mt-0.5">
                                <span class="font-bold text-slate-700"><?= (int)$b['number_of_passengers'] ?></span>
                                passenger<?= (int)$b['number_of_passengers'] > 1 ? 's' : '' ?>
                                <span class="text-slate-400">/ cap <?= (int)$b['vehicle_seating_capacity'] ?></span>
                            </div>
                        </td>

                        <!-- Schedule / Duration -->
                        <td class="py-3 px-4 whitespace-nowrap">
                            <div class="text-slate-800 font-medium">
                                <?= date('M d, Y h:i A', strtotime($b['start_date'])) ?>
                            </div>
                            <div class="text-[11px] text-slate-500 flex items-center gap-1 mt-0.5">
                                <span>to <?= date('M d, Y h:i A', strtotime($b['end_date'])) ?></span>
                                <span class="px-1.5 py-0.2 bg-slate-100 font-bold text-slate-600 border border-slate-200 rounded-[2px]">
                                    <?= htmlspecialchars($b['duration_text']) ?>
                                </span>
                            </div>
                        </td>

                        <!-- Amount -->
                        <td class="py-3 px-4 whitespace-nowrap">
                            <span class="font-bold text-slate-900 text-xs">
                                <?= htmlspecialchars($b['formatted_amount']) ?>
                            </span>
                        </td>

                        <!-- Status Badge -->
                        <td class="py-3 px-4 whitespace-nowrap">
                            <div class="relative inline-block group">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-[11px] font-bold border rounded-[3px] <?= $b['status_badge']['bg'] ?> <?= $b['status_badge']['text'] ?> <?= $b['status_badge']['border'] ?>">
                                    <span class="w-1.5 h-1.5 rounded-full <?= $b['status_badge']['dot'] ?>"></span>
                                    <span><?= htmlspecialchars($b['booking_status']) ?></span>
                                </span>

                                <!-- Quick Status Changer Trigger -->
                                <button
                                    type="button"
                                    onclick="promptStatusChange(<?= $b['booking_id'] ?>, '<?= $b['booking_status'] ?>', '<?= htmlspecialchars($b['booking_reference']) ?>')"
                                    title="Change Status"
                                    class="ml-1 text-slate-400 hover:text-slate-700 focus:outline-none"
                                >
                                    <svg class="w-3.5 h-3.5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                            </div>
                        </td>

                        <!-- Action Buttons -->
                        <td class="py-3 px-4 whitespace-nowrap text-right">
                            <div class="inline-flex items-center gap-1">
                                <!-- View Details -->
                                <button
                                    type="button"
                                    onclick="viewBookingDetails(<?= htmlspecialchars(json_encode($b), ENT_QUOTES, 'UTF-8') ?>)"
                                    title="View Full Booking Details"
                                    class="p-1.5 text-slate-500 hover:text-slate-900 hover:bg-slate-100 border border-transparent hover:border-slate-200 rounded-[3px] transition-colors"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </button>

                                <!-- Edit Booking -->
                                <button
                                    type="button"
                                    onclick="openEditBookingModal(<?= htmlspecialchars(json_encode($b), ENT_QUOTES, 'UTF-8') ?>)"
                                    title="Edit Booking"
                                    class="p-1.5 text-slate-500 hover:text-blue-600 hover:bg-blue-50 border border-transparent hover:border-blue-200 rounded-[3px] transition-colors"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>

                                <!-- Delete / Cancel -->
                                <button
                                    type="button"
                                    onclick="openDeleteBookingModal(<?= $b['booking_id'] ?>, '<?= htmlspecialchars($b['booking_reference']) ?>')"
                                    title="Cancel or Delete Booking"
                                    class="p-1.5 text-slate-500 hover:text-red-600 hover:bg-red-50 border border-transparent hover:border-red-200 rounded-[3px] transition-colors"
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

        <!-- Table Footer Info -->
        <div class="px-4 py-3 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-2 text-xs text-slate-500">
            <div>
                Showing <strong class="text-slate-800"><?= count($bookings) ?></strong> of <strong class="text-slate-800"><?= $stats['total_bookings'] ?></strong> total bookings
            </div>
            <div class="flex items-center gap-2 text-[11px] text-slate-400">
                <span>All timestamps in Philippine Standard Time (PST)</span>
            </div>
        </div>

        <?php endif; ?>

    </div>

</div>


<!-- =========================================================================
     MODALS SECTION (Strict Sharp 4px-6px Border Radius Architecture)
     ========================================================================= -->

<!-- 1. CREATE BOOKING MODAL -->
<div id="modal-create-booking" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 modal-backdrop-custom overflow-y-auto">
    <div class="bg-white border border-slate-200 rounded-[10px] shadow-xl max-w-2xl w-full max-h-[90vh] flex flex-col my-auto overflow-hidden">

        <!-- Modal Header -->
        <div class="px-6 py-4 bg-slate-700 text-white flex items-center justify-between border-b border-slate-600">
            <div class="flex items-center gap-2.5">
                <div class="w-7 h-7 bg-red-500 rounded-[6px] flex items-center justify-center text-white">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-extrabold tracking-tight">Create Fleet Reservation</h3>
                    <p class="text-[11px] text-slate-400">Schedule vehicle dispatch and customer booking</p>
                </div>
            </div>
            <button type="button" onclick="closeCreateBookingModal()" class="text-slate-400 hover:text-white text-lg font-bold">&times;</button>
        </div>

        <!-- Modal Form Body -->
        <form method="POST" action="index.php?view=bookings" class="flex-1 overflow-y-auto p-6 space-y-4" id="form-create-booking">
            <input type="hidden" name="action" value="create_booking">

            <!-- Personalized Customer Selection -->
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label class="block text-xs font-bold text-slate-800">
                        Customer <span class="text-red-600">*</span>
                    </label>
                    <button type="button" onclick="openQuickCustomerModal()" class="text-[11px] font-bold text-red-600 hover:text-red-700 underline">
                        + New Customer
                    </button>
                </div>

                <!-- Hidden Input holding the selected customer ID -->
                <input type="hidden" id="create_customer_id" name="customer_id" required value="">

                <!-- Empty State Prompt -->
                <div id="create_customer_empty_state" class="border border-dashed border-slate-300 rounded-[8px] p-3.5 bg-slate-50 hover:bg-slate-100/80 transition-colors cursor-pointer" onclick="openCustomerPickerModal('create')">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-[8px] bg-white border border-slate-200 flex items-center justify-center text-slate-400 shadow-xs">
                                <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                            <div>
                                <div class="text-xs font-extrabold text-slate-800">No Customer Selected</div>
                                <div class="text-[11px] text-slate-400">Click to search directory by name, mobile, or email</div>
                            </div>
                        </div>
                        <button type="button" onclick="event.stopPropagation(); openCustomerPickerModal('create');" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-600 hover:bg-slate-700 text-white text-xs font-semibold rounded-[6px] shadow-xs">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <span>Search Customer</span>
                        </button>
                    </div>
                </div>

                <!-- Personalized Selected Customer Card -->
                <div id="create_customer_selected_card" class="hidden border border-slate-200 bg-slate-50 rounded-[8px] p-3.5 flex items-center justify-between shadow-xs">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-[8px] bg-red-500 text-white flex items-center justify-center font-black text-sm shadow-xs" id="create_cust_avatar">
                            --
                        </div>
                        <div>
                            <div class="flex items-center gap-1.5">
                                <span class="font-extrabold text-xs text-slate-900" id="create_cust_name"></span>
                                <span id="create_cust_vip_badge"></span>
                            </div>
                            <div class="text-[11px] text-slate-500 flex items-center gap-2 mt-0.5">
                                <span class="font-mono text-slate-700 font-semibold" id="create_cust_phone"></span>
                                <span class="text-slate-300">&bull;</span>
                                <span id="create_cust_email" class="text-slate-500"></span>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <button type="button" onclick="openCustomerPickerModal('create')" class="px-2.5 py-1 text-xs font-semibold text-slate-600 bg-white hover:bg-slate-100 border border-slate-200 rounded-[6px] shadow-xs">
                            Change
                        </button>
                        <button type="button" onclick="clearCustomerSelection('create')" class="p-1 text-slate-400 hover:text-red-500 rounded-[6px]" title="Clear selection">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Vehicle & Driver Selection Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Personalized Vehicle Selector -->
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1.5">
                        Assigned Vehicle <span class="text-red-500">*</span>
                    </label>

                    <!-- Hidden Input holding the selected vehicle ID -->
                    <input type="hidden" id="create_vehicle_id" name="vehicle_id" required value="">

                    <!-- Empty State Prompt -->
                    <div id="create_vehicle_empty_state" class="border border-dashed border-slate-300 rounded-[8px] p-3.5 bg-slate-50 hover:bg-slate-100/80 transition-colors cursor-pointer" onclick="openVehiclePickerModal('create')">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <div class="w-9 h-9 rounded-[8px] bg-white border border-slate-200 flex items-center justify-center text-slate-400 shadow-xs">
                                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                    </svg>
                                </div>
                                <div>
                                    <div class="text-xs font-extrabold text-slate-800">No Vehicle Assigned</div>
                                    <div class="text-[11px] text-slate-400">Click to choose available unit</div>
                                </div>
                            </div>
                            <button type="button" onclick="event.stopPropagation(); openVehiclePickerModal('create');" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-600 hover:bg-slate-700 text-white text-xs font-semibold rounded-[6px] shadow-xs">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                                <span>Search Vehicle</span>
                            </button>
                        </div>
                    </div>

                    <!-- Personalized Selected Vehicle Card -->
                    <div id="create_vehicle_selected_card" class="hidden border border-slate-200 bg-slate-50 rounded-[8px] p-3.5 flex items-center justify-between shadow-xs">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-[8px] bg-slate-500 text-white flex items-center justify-center font-bold text-xs shadow-xs" id="create_veh_icon">
                                CAR
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="font-extrabold text-xs text-slate-900" id="create_veh_name"></span>
                                    <span class="font-mono font-semibold text-[11px] bg-slate-100 border border-slate-200 px-1.5 py-0.5 rounded-[4px]" id="create_veh_plate"></span>
                                </div>
                                <div class="text-[11px] text-slate-500 mt-0.5">
                                    <span id="create_veh_type"></span> &bull;
                                    Max: <strong class="text-slate-700" id="create_veh_capacity"></strong> Pax &bull;
                                    <span class="text-emerald-600 font-semibold" id="create_veh_status">Available</span>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <button type="button" onclick="openVehiclePickerModal('create')" class="px-2.5 py-1 text-xs font-semibold text-slate-600 bg-white hover:bg-slate-100 border border-slate-200 rounded-[6px] shadow-xs">
                                Change
                            </button>
                            <button type="button" onclick="clearVehicleSelection('create')" class="p-1 text-slate-400 hover:text-red-500 rounded-[6px]" title="Clear selection">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <p id="vehicle_capacity_hint" class="text-[10px] text-slate-400 mt-1 font-medium"></p>
                </div>

                <!-- Driver -->
                <div>
                    <label for="create_driver_id" class="block text-xs font-bold text-slate-800 mb-1.5">
                        Assigned Chauffeur / Driver
                    </label>
                    <select
                        id="create_driver_id"
                        name="driver_id"
                        class="w-full px-3 py-2 text-xs font-medium text-slate-900 bg-slate-50 border border-slate-200 rounded-[6px] focus:outline-none focus:ring-1 focus:ring-red-500 focus:border-red-500"
                    >
                        <option value="">Self-Drive (No Staff Driver)</option>
                        <?php foreach ($formData['drivers'] as $d): ?>
                            <option value="<?= $d['driver_id'] ?>">
                                <?= htmlspecialchars($d['first_name'] . ' ' . $d['last_name']) ?> (<?= htmlspecialchars($d['phone_number']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Destination & Passengers -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="sm:col-span-2">
                    <label for="create_destination" class="block text-xs font-bold text-slate-800 mb-1.5">
                        Trip Destination / Itinerary <span class="text-red-600">*</span>
                    </label>
                    <input
                        type="text"
                        id="create_destination"
                        name="destination"
                        required
                        placeholder="e.g. Baguio City Heritage Tour"
                        class="w-full px-3 py-2 text-xs font-medium text-slate-900 bg-slate-50 border border-slate-300 rounded-[4px] focus:outline-none focus:ring-1 focus:ring-red-600 focus:border-red-600"
                    >
                </div>

                <div>
                    <label for="create_passengers" class="block text-xs font-bold text-slate-800 mb-1.5">
                        Passengers <span class="text-red-600">*</span>
                    </label>
                    <input
                        type="number"
                        id="create_passengers"
                        name="number_of_passengers"
                        value="1"
                        min="1"
                        max="20"
                        required
                        class="w-full px-3 py-2 text-xs font-medium text-slate-900 bg-slate-50 border border-slate-300 rounded-[4px] focus:outline-none focus:ring-1 focus:ring-red-600 focus:border-red-600"
                    >
                </div>
            </div>

            <!-- Rental Dates -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="create_start_date" class="block text-xs font-bold text-slate-800 mb-1.5">
                        Start Date &amp; Time <span class="text-red-600">*</span>
                    </label>
                    <input
                        type="datetime-local"
                        id="create_start_date"
                        name="start_date"
                        required
                        onchange="computeDurationHint('create_start_date', 'create_end_date', 'create_duration_hint')"
                        class="w-full px-3 py-2 text-xs font-medium text-slate-900 bg-slate-50 border border-slate-300 rounded-[4px] focus:outline-none focus:ring-1 focus:ring-red-600 focus:border-red-600"
                    >
                </div>

                <div>
                    <label for="create_end_date" class="block text-xs font-bold text-slate-800 mb-1.5">
                        End Date &amp; Time <span class="text-red-600">*</span>
                    </label>
                    <input
                        type="datetime-local"
                        id="create_end_date"
                        name="end_date"
                        required
                        onchange="computeDurationHint('create_start_date', 'create_end_date', 'create_duration_hint')"
                        class="w-full px-3 py-2 text-xs font-medium text-slate-900 bg-slate-50 border border-slate-300 rounded-[4px] focus:outline-none focus:ring-1 focus:ring-red-600 focus:border-red-600"
                    >
                </div>
            </div>

            <div id="create_duration_hint" class="text-[11px] font-semibold text-slate-500"></div>

            <!-- Booking Amount & Status -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="create_amount" class="block text-xs font-bold text-slate-800 mb-1.5">
                        Total Rental Amount (PHP ₱) <span class="text-red-600">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center font-bold text-slate-400 text-xs">₱</span>
                        <input
                            type="number"
                            id="create_amount"
                            name="booking_amount"
                            step="0.01"
                            min="0"
                            placeholder="0.00"
                            required
                            class="w-full pl-7 pr-3 py-2 text-xs font-bold text-slate-900 bg-slate-50 border border-slate-300 rounded-[4px] focus:outline-none focus:ring-1 focus:ring-red-600 focus:border-red-600"
                        >
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1.5">
                        Initial Booking Status <span class="text-red-600">*</span>
                    </label>
                    <div class="grid grid-cols-3 gap-2">
                        <!-- Confirmed Radio Card -->
                        <label class="flex flex-col p-2.5 border-2 border-blue-400 bg-blue-50 rounded-[8px] cursor-pointer transition-all hover:shadow-xs status-radio-card" id="label-create-status-Confirmed" onclick="selectStatusRadio('create', 'Confirmed')">
                            <div class="flex items-center justify-between">
                                <span class="inline-flex items-center gap-1 text-[10px] font-bold text-blue-600">
                                    <span class="w-1.5 h-1.5 rounded-full bg-blue-400"></span>
                                    Confirmed
                                </span>
                                <input type="radio" name="booking_status" value="Confirmed" checked class="text-blue-600 focus:ring-blue-500">
                            </div>
                            <span class="text-[10px] text-slate-500 mt-1 leading-tight">Fleet reserved for trip</span>
                        </label>

                        <!-- Pending Radio Card -->
                        <label class="flex flex-col p-2.5 border border-slate-200 bg-white rounded-[8px] cursor-pointer transition-all hover:shadow-xs status-radio-card" id="label-create-status-Pending" onclick="selectStatusRadio('create', 'Pending')">
                            <div class="flex items-center justify-between">
                                <span class="inline-flex items-center gap-1 text-[10px] font-bold text-amber-600">
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                                    Pending
                                </span>
                                <input type="radio" name="booking_status" value="Pending" class="text-amber-600 focus:ring-amber-500">
                            </div>
                            <span class="text-[10px] text-slate-500 mt-1 leading-tight">Draft / Unconfirmed</span>
                        </label>

                        <!-- Ongoing Radio Card -->
                        <label class="flex flex-col p-2.5 border border-slate-200 bg-white rounded-[8px] cursor-pointer transition-all hover:shadow-xs status-radio-card" id="label-create-status-Ongoing" onclick="selectStatusRadio('create', 'Ongoing')">
                            <div class="flex items-center justify-between">
                                <span class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-600">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                    Ongoing
                                </span>
                                <input type="radio" name="booking_status" value="Ongoing" class="text-emerald-600 focus:ring-emerald-500">
                            </div>
                            <span class="text-[10px] text-slate-500 mt-1 leading-tight">Immediate dispatch</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Custom Reference (Optional) -->
            <div>
                <label for="create_reference" class="block text-xs font-bold text-slate-800 mb-1.5">
                    Booking Reference <span class="text-[10px] font-normal text-slate-400">(leave blank to auto-generate LR-YYYYMM-XXX)</span>
                </label>
                <input
                    type="text"
                    id="create_reference"
                    name="booking_reference"
                    placeholder="e.g. LR-202611-999"
                    class="w-full px-3 py-2 text-xs font-mono font-medium text-slate-900 bg-slate-50 border border-slate-300 rounded-[4px] focus:outline-none focus:ring-1 focus:ring-red-600 focus:border-red-600"
                >
            </div>

            <!-- Modal Footer Buttons -->
            <div class="pt-4 border-t border-slate-200 flex items-center justify-end gap-2">
                <button
                    type="button"
                    onclick="closeCreateBookingModal()"
                    class="px-4 py-2 text-xs font-bold text-slate-600 hover:text-slate-900 bg-slate-100 hover:bg-slate-200 rounded-[4px] transition-colors"
                >
                    Cancel
                </button>
                <button
                    type="submit"
                    class="px-5 py-2 text-xs font-bold text-white bg-red-600 hover:bg-red-700 active:bg-red-800 rounded-[4px] shadow-xs transition-colors"
                >
                    Confirm &amp; Save Booking
                </button>
            </div>

        </form>

    </div>
</div>


<!-- 2. QUICK ADD CUSTOMER MODAL -->
<div id="modal-quick-customer" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 modal-backdrop-custom overflow-y-auto">
    <div class="bg-white border border-slate-200 rounded-[6px] shadow-xl max-w-md w-full my-auto overflow-hidden">
        <div class="px-5 py-3.5 bg-slate-900 text-white flex items-center justify-between border-b border-slate-800">
            <h3 class="text-xs font-extrabold uppercase tracking-wider">Quick Register Customer</h3>
            <button type="button" onclick="closeQuickCustomerModal()" class="text-slate-400 hover:text-white">&times;</button>
        </div>

        <form method="POST" action="index.php?view=bookings" class="p-5 space-y-3.5">
            <input type="hidden" name="action" value="quick_customer">

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 mb-1">First Name *</label>
                    <input type="text" name="first_name" required placeholder="e.g. Juan" class="w-full px-3 py-1.5 text-xs bg-slate-50 border border-slate-300 rounded-[4px] focus:ring-1 focus:ring-red-600 focus:border-red-600">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 mb-1">Last Name *</label>
                    <input type="text" name="last_name" required placeholder="e.g. Dela Cruz" class="w-full px-3 py-1.5 text-xs bg-slate-50 border border-slate-300 rounded-[4px] focus:ring-1 focus:ring-red-600 focus:border-red-600">
                </div>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-700 mb-1">Mobile / Phone Number *</label>
                <input type="text" name="phone_number" required placeholder="e.g. 09171234567" class="w-full px-3 py-1.5 text-xs bg-slate-50 border border-slate-300 rounded-[4px] focus:ring-1 focus:ring-red-600 focus:border-red-600">
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-700 mb-1">Email Address</label>
                <input type="email" name="email" placeholder="client@example.com" class="w-full px-3 py-1.5 text-xs bg-slate-50 border border-slate-300 rounded-[4px] focus:ring-1 focus:ring-red-600 focus:border-red-600">
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-700 mb-1">Address / Residence</label>
                <input type="text" name="address" placeholder="e.g. Quezon City, Metro Manila" class="w-full px-3 py-1.5 text-xs bg-slate-50 border border-slate-300 rounded-[4px] focus:ring-1 focus:ring-red-600 focus:border-red-600">
            </div>

            <div class="flex items-center gap-4 pt-1">
                <label class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-700 cursor-pointer">
                    <input type="checkbox" name="is_vip" value="1" class="rounded-[2px] text-red-600 focus:ring-red-500">
                    <span>Mark as VIP Account</span>
                </label>
                <label class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-700 cursor-pointer">
                    <input type="checkbox" name="is_returning" value="1" class="rounded-[2px] text-red-600 focus:ring-red-500">
                    <span>Returning Customer</span>
                </label>
            </div>

            <div class="pt-3 border-t border-slate-200 flex items-center justify-end gap-2">
                <button type="button" onclick="closeQuickCustomerModal()" class="px-3 py-1.5 text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-[4px]">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-1.5 text-xs font-bold text-white bg-red-600 hover:bg-red-700 rounded-[4px]">
                    Save Customer
                </button>
            </div>
        </form>
    </div>
</div>


<!-- 3. VIEW BOOKING DETAILS MODAL (VOUCHER STYLE) -->
<div id="modal-view-booking" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 modal-backdrop-custom overflow-y-auto">
    <div class="bg-white border border-slate-200 rounded-[6px] shadow-xl max-w-xl w-full my-auto overflow-hidden" id="booking-voucher-printable">

        <!-- Voucher Header -->
        <div class="p-5 bg-slate-900 text-white flex items-center justify-between border-b border-slate-800">
            <div>
                <div class="text-[10px] uppercase font-bold tracking-wider text-red-500">Leo Rent-A-Car Booking Voucher</div>
                <div class="text-base font-extrabold font-mono mt-0.5" id="view-ref">LR-XXXXXX</div>
            </div>
            <div id="view-status-badge"></div>
        </div>

        <!-- Voucher Body -->
        <div class="p-6 space-y-4 text-xs">

            <!-- Customer & Vehicle Grid -->
            <div class="grid grid-cols-2 gap-4 pb-4 border-b border-slate-200">
                <div>
                    <span class="text-[10px] uppercase font-bold text-slate-400">Renter / Customer</span>
                    <div class="font-extrabold text-slate-900 text-sm mt-0.5" id="view-customer"></div>
                    <div class="text-slate-500 mt-0.5" id="view-customer-phone"></div>
                    <div class="text-slate-400 text-[11px]" id="view-customer-email"></div>
                </div>

                <div>
                    <span class="text-[10px] uppercase font-bold text-slate-400">Assigned Vehicle</span>
                    <div class="font-extrabold text-slate-900 text-sm mt-0.5" id="view-vehicle"></div>
                    <div class="font-mono text-slate-600 mt-0.5" id="view-vehicle-plate"></div>
                    <div class="text-slate-400 text-[11px]" id="view-vehicle-meta"></div>
                </div>
            </div>

            <!-- Itinerary Details -->
            <div class="space-y-2 pb-4 border-b border-slate-200">
                <div>
                    <span class="text-[10px] uppercase font-bold text-slate-400">Destination &amp; Route</span>
                    <div class="font-bold text-slate-800 text-xs mt-0.5" id="view-destination"></div>
                </div>

                <div class="grid grid-cols-2 gap-4 pt-1">
                    <div>
                        <span class="text-[10px] uppercase font-bold text-slate-400">Start Date &amp; Time</span>
                        <div class="font-medium text-slate-800 mt-0.5" id="view-start"></div>
                    </div>
                    <div>
                        <span class="text-[10px] uppercase font-bold text-slate-400">End Date &amp; Time</span>
                        <div class="font-medium text-slate-800 mt-0.5" id="view-end"></div>
                    </div>
                </div>

                <div class="flex items-center gap-4 pt-1">
                    <div>
                        <span class="text-[10px] uppercase font-bold text-slate-400">Rental Duration</span>
                        <div class="font-bold text-slate-800" id="view-duration"></div>
                    </div>
                    <div>
                        <span class="text-[10px] uppercase font-bold text-slate-400">Passenger Count</span>
                        <div class="font-bold text-slate-800" id="view-passengers"></div>
                    </div>
                    <div>
                        <span class="text-[10px] uppercase font-bold text-slate-400">Chauffeur / Driver</span>
                        <div class="font-bold text-slate-800" id="view-driver"></div>
                    </div>
                </div>
            </div>

            <!-- Financials & Audit -->
            <div class="flex items-center justify-between p-3.5 bg-slate-50 border border-slate-200 rounded-[4px]">
                <div>
                    <span class="text-[10px] uppercase font-bold text-slate-400">Total Booking Amount</span>
                    <div class="text-lg font-black text-red-600" id="view-amount">₱ 0.00</div>
                </div>
                <div class="text-right text-[11px] text-slate-500">
                    <div>Created by: <strong id="view-creator" class="text-slate-700"></strong></div>
                    <div id="view-created-at" class="text-[10px] text-slate-400"></div>
                </div>
            </div>

        </div>

        <!-- Voucher Actions -->
        <div class="px-6 py-3.5 bg-slate-100 border-t border-slate-200 flex items-center justify-between no-print">
            <button
                type="button"
                onclick="window.print()"
                class="px-3.5 py-1.5 text-xs font-bold text-slate-700 bg-white hover:bg-slate-50 border border-slate-300 rounded-[4px] shadow-xs flex items-center gap-1.5"
            >
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                <span>Print Voucher</span>
            </button>

            <button
                type="button"
                onclick="closeViewBookingModal()"
                class="px-4 py-1.5 text-xs font-bold text-white bg-slate-800 hover:bg-slate-900 rounded-[4px]"
            >
                Close
            </button>
        </div>

    </div>
</div>


<!-- 4. EDIT BOOKING MODAL -->
<div id="modal-edit-booking" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 modal-backdrop-custom overflow-y-auto">
    <div class="bg-white border border-slate-200 rounded-[6px] shadow-xl max-w-2xl w-full max-h-[90vh] flex flex-col my-auto overflow-hidden">

        <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between border-b border-slate-800">
            <div class="flex items-center gap-2">
                <h3 class="text-sm font-extrabold">Edit Booking <span id="edit-ref-display" class="font-mono text-red-500"></span></h3>
            </div>
            <button type="button" onclick="closeEditBookingModal()" class="text-slate-400 hover:text-white text-lg font-bold">&times;</button>
        </div>

        <form method="POST" action="index.php?view=bookings" class="flex-1 overflow-y-auto p-6 space-y-4" id="form-edit-booking">
            <input type="hidden" name="action" value="update_booking">
            <input type="hidden" name="booking_id" id="edit_booking_id" value="">

            <!-- Personalized Customer Selection -->
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label class="block text-xs font-bold text-slate-800">
                        Customer <span class="text-red-600">*</span>
                    </label>
                    <button type="button" onclick="openQuickCustomerModal()" class="text-[11px] font-bold text-red-600 hover:text-red-700 underline">
                        + New Customer
                    </button>
                </div>

                <!-- Hidden Input holding the selected customer ID -->
                <input type="hidden" id="edit_customer_id" name="customer_id" required value="">

                <!-- Empty State Prompt -->
                <div id="edit_customer_empty_state" class="border border-dashed border-slate-300 rounded-[4px] p-3.5 bg-slate-50/70 hover:bg-slate-100/70 transition-colors cursor-pointer" onclick="openCustomerPickerModal('edit')">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-[4px] bg-white border border-slate-200 flex items-center justify-center text-slate-400 shadow-xs">
                                <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                            <div>
                                <div class="text-xs font-extrabold text-slate-800">No Customer Selected</div>
                                <div class="text-[11px] text-slate-400">Click to search directory by name, mobile, or email</div>
                            </div>
                        </div>
                        <button type="button" onclick="event.stopPropagation(); openCustomerPickerModal('edit');" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold rounded-[4px] shadow-xs">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <span>Search Customer</span>
                        </button>
                    </div>
                </div>

                <!-- Personalized Selected Customer Card -->
                <div id="edit_customer_selected_card" class="hidden border border-red-200 bg-red-50/20 rounded-[4px] p-3.5 flex items-center justify-between shadow-xs">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-[4px] bg-red-600 text-white flex items-center justify-center font-black text-sm shadow-xs" id="edit_cust_avatar">
                            --
                        </div>
                        <div>
                            <div class="flex items-center gap-1.5">
                                <span class="font-extrabold text-xs text-slate-900" id="edit_cust_name"></span>
                                <span id="edit_cust_vip_badge"></span>
                            </div>
                            <div class="text-[11px] text-slate-500 flex items-center gap-2 mt-0.5">
                                <span class="font-mono text-slate-700 font-semibold" id="edit_cust_phone"></span>
                                <span class="text-slate-300">&bull;</span>
                                <span id="edit_cust_email" class="text-slate-500"></span>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <button type="button" onclick="openCustomerPickerModal('edit')" class="px-2.5 py-1 text-xs font-bold text-slate-700 bg-white hover:bg-slate-50 border border-slate-300 rounded-[4px] shadow-xs">
                            Change
                        </button>
                    </div>
                </div>
            </div>

            <!-- Vehicle & Driver Selection Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Personalized Vehicle Selector -->
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1.5">
                        Assigned Vehicle <span class="text-red-600">*</span>
                    </label>

                    <!-- Hidden Input holding the selected vehicle ID -->
                    <input type="hidden" id="edit_vehicle_id" name="vehicle_id" required value="">

                    <!-- Empty State Prompt -->
                    <div id="edit_vehicle_empty_state" class="border border-dashed border-slate-300 rounded-[4px] p-3.5 bg-slate-50/70 hover:bg-slate-100/70 transition-colors cursor-pointer" onclick="openVehiclePickerModal('edit')">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <div class="w-9 h-9 rounded-[4px] bg-white border border-slate-200 flex items-center justify-center text-slate-400 shadow-xs">
                                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                    </svg>
                                </div>
                                <div>
                                    <div class="text-xs font-extrabold text-slate-800">No Vehicle Assigned</div>
                                    <div class="text-[11px] text-slate-400">Click to choose unit</div>
                                </div>
                            </div>
                            <button type="button" onclick="event.stopPropagation(); openVehiclePickerModal('edit');" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold rounded-[4px] shadow-xs">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                                <span>Search Vehicle</span>
                            </button>
                        </div>
                    </div>

                    <!-- Personalized Selected Vehicle Card -->
                    <div id="edit_vehicle_selected_card" class="hidden border border-slate-300 bg-slate-50 rounded-[4px] p-3.5 flex items-center justify-between shadow-xs">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-[4px] bg-slate-900 text-white flex items-center justify-center font-bold text-xs shadow-xs" id="edit_veh_icon">
                                CAR
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="font-extrabold text-xs text-slate-900" id="edit_veh_name"></span>
                                    <span class="font-mono font-bold text-[11px] bg-white border border-slate-300 px-1.5 py-0.2 rounded-[2px]" id="edit_veh_plate"></span>
                                </div>
                                <div class="text-[11px] text-slate-500 mt-0.5">
                                    <span id="edit_veh_type"></span> &bull;
                                    Max: <strong class="text-slate-700" id="edit_veh_capacity"></strong> Pax &bull;
                                    <span class="text-emerald-700 font-semibold" id="edit_veh_status">Assigned</span>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <button type="button" onclick="openVehiclePickerModal('edit')" class="px-2.5 py-1 text-xs font-bold text-slate-700 bg-white hover:bg-slate-50 border border-slate-300 rounded-[4px] shadow-xs">
                                Change
                            </button>
                        </div>
                    </div>

                    <p id="edit_vehicle_capacity_hint" class="text-[10px] text-slate-400 mt-1 font-medium"></p>
                </div>

                <!-- Driver -->
                <div>
                    <label for="edit_driver_id" class="block text-xs font-bold text-slate-800 mb-1.5">
                        Assigned Chauffeur / Driver
                    </label>
                    <select
                        id="edit_driver_id"
                        name="driver_id"
                        class="w-full px-3 py-2 text-xs font-medium text-slate-900 bg-slate-50 border border-slate-300 rounded-[4px] focus:outline-none focus:ring-1 focus:ring-red-600 focus:border-red-600"
                    >
                        <option value="">Self-Drive (No Staff Driver)</option>
                        <?php foreach ($formData['drivers'] as $d): ?>
                            <option value="<?= $d['driver_id'] ?>">
                                <?= htmlspecialchars($d['first_name'] . ' ' . $d['last_name']) ?> (<?= htmlspecialchars($d['phone_number']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="sm:col-span-2">
                    <label for="edit_destination" class="block text-xs font-bold text-slate-800 mb-1.5">Destination *</label>
                    <input
                        type="text"
                        id="edit_destination"
                        name="destination"
                        required
                        class="w-full px-3 py-2 text-xs font-medium text-slate-900 bg-slate-50 border border-slate-300 rounded-[4px] focus:ring-1 focus:ring-red-600 focus:border-red-600"
                    >
                </div>

                <div>
                    <label for="edit_passengers" class="block text-xs font-bold text-slate-800 mb-1.5">Passengers *</label>
                    <input
                        type="number"
                        id="edit_passengers"
                        name="number_of_passengers"
                        min="1"
                        max="25"
                        required
                        class="w-full px-3 py-2 text-xs font-medium text-slate-900 bg-slate-50 border border-slate-300 rounded-[4px] focus:ring-1 focus:ring-red-600 focus:border-red-600"
                    >
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="edit_start_date" class="block text-xs font-bold text-slate-800 mb-1.5">Start Date &amp; Time *</label>
                    <input
                        type="datetime-local"
                        id="edit_start_date"
                        name="start_date"
                        required
                        class="w-full px-3 py-2 text-xs font-medium text-slate-900 bg-slate-50 border border-slate-300 rounded-[4px] focus:ring-1 focus:ring-red-600 focus:border-red-600"
                    >
                </div>

                <div>
                    <label for="edit_end_date" class="block text-xs font-bold text-slate-800 mb-1.5">End Date &amp; Time *</label>
                    <input
                        type="datetime-local"
                        id="edit_end_date"
                        name="end_date"
                        required
                        class="w-full px-3 py-2 text-xs font-medium text-slate-900 bg-slate-50 border border-slate-300 rounded-[4px] focus:ring-1 focus:ring-red-600 focus:border-red-600"
                    >
                </div>
            </div>

            <!-- Booking Amount & Status -->
            <div class="space-y-3">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-start">
                    <div>
                        <label for="edit_amount" class="block text-xs font-bold text-slate-800 mb-1.5">Amount (PHP ₱) *</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center font-bold text-slate-400 text-xs">₱</span>
                            <input
                                type="number"
                                id="edit_amount"
                                name="booking_amount"
                                step="0.01"
                                min="0"
                                required
                                class="w-full pl-7 pr-3 py-2 text-xs font-bold text-slate-900 bg-slate-50 border border-slate-300 rounded-[4px] focus:ring-1 focus:ring-red-600 focus:border-red-600"
                            >
                        </div>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-slate-800 mb-1.5">Booking Status *</label>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                            <!-- Pending -->
                            <label class="flex flex-col p-2 border border-slate-300 bg-white rounded-[4px] cursor-pointer transition-all hover:shadow-xs status-radio-card" id="label-edit-status-Pending" onclick="selectStatusRadio('edit', 'Pending')">
                                <div class="flex items-center justify-between">
                                    <span class="inline-flex items-center gap-1 text-[10px] font-extrabold text-amber-800">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                        Pending
                                    </span>
                                    <input type="radio" name="booking_status" value="Pending" class="text-amber-600 focus:ring-amber-500">
                                </div>
                                <span class="text-[9px] text-slate-500 mt-1 leading-tight">Draft / Unconfirmed</span>
                            </label>

                            <!-- Confirmed -->
                            <label class="flex flex-col p-2 border border-slate-300 bg-white rounded-[4px] cursor-pointer transition-all hover:shadow-xs status-radio-card" id="label-edit-status-Confirmed" onclick="selectStatusRadio('edit', 'Confirmed')">
                                <div class="flex items-center justify-between">
                                    <span class="inline-flex items-center gap-1 text-[10px] font-extrabold text-blue-700">
                                        <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                                        Confirmed
                                    </span>
                                    <input type="radio" name="booking_status" value="Confirmed" class="text-blue-600 focus:ring-blue-500">
                                </div>
                                <span class="text-[9px] text-slate-500 mt-1 leading-tight">Vehicle Reserved</span>
                            </label>

                            <!-- Ongoing -->
                            <label class="flex flex-col p-2 border border-slate-300 bg-white rounded-[4px] cursor-pointer transition-all hover:shadow-xs status-radio-card" id="label-edit-status-Ongoing" onclick="selectStatusRadio('edit', 'Ongoing')">
                                <div class="flex items-center justify-between">
                                    <span class="inline-flex items-center gap-1 text-[10px] font-extrabold text-emerald-700">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        Ongoing
                                    </span>
                                    <input type="radio" name="booking_status" value="Ongoing" class="text-emerald-600 focus:ring-emerald-500">
                                </div>
                                <span class="text-[9px] text-slate-500 mt-1 leading-tight">Active on Trip</span>
                            </label>

                            <!-- Completed -->
                            <label class="flex flex-col p-2 border border-slate-300 bg-white rounded-[4px] cursor-pointer transition-all hover:shadow-xs status-radio-card" id="label-edit-status-Completed" onclick="selectStatusRadio('edit', 'Completed')">
                                <div class="flex items-center justify-between">
                                    <span class="inline-flex items-center gap-1 text-[10px] font-extrabold text-slate-700">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-500"></span>
                                        Completed
                                    </span>
                                    <input type="radio" name="booking_status" value="Completed" class="text-slate-600 focus:ring-slate-500">
                                </div>
                                <span class="text-[9px] text-slate-500 mt-1 leading-tight">Returned / Finished</span>
                            </label>

                            <!-- Cancelled -->
                            <label class="flex flex-col p-2 border border-slate-300 bg-white rounded-[4px] cursor-pointer transition-all hover:shadow-xs status-radio-card" id="label-edit-status-Cancelled" onclick="selectStatusRadio('edit', 'Cancelled')">
                                <div class="flex items-center justify-between">
                                    <span class="inline-flex items-center gap-1 text-[10px] font-extrabold text-red-700">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                        Cancelled
                                    </span>
                                    <input type="radio" name="booking_status" value="Cancelled" class="text-red-600 focus:ring-red-500">
                                </div>
                                <span class="text-[9px] text-slate-500 mt-1 leading-tight">Void / Cancelled</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="pt-4 border-t border-slate-200 flex items-center justify-end gap-2">
                <button
                    type="button"
                    onclick="closeEditBookingModal()"
                    class="px-4 py-2 text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-[4px]"
                >
                    Cancel
                </button>
                <button
                    type="submit"
                    class="px-5 py-2 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-[4px]"
                >
                    Update Booking
                </button>
            </div>
        </form>

    </div>
</div>


<!-- 5. DELETE / CANCEL CONFIRMATION MODAL -->
<div id="modal-delete-booking" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 modal-backdrop-custom overflow-y-auto">
    <div class="bg-white border border-slate-200 rounded-[6px] shadow-xl max-w-sm w-full my-auto overflow-hidden">
        <div class="p-5 text-center space-y-3">
            <div class="w-12 h-12 mx-auto rounded-[4px] bg-red-100 text-red-600 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
            </div>
            <h3 class="text-sm font-extrabold text-slate-900">Delete Booking Record</h3>
            <p class="text-xs text-slate-500">
                Are you sure you want to permanently delete booking <strong id="delete-ref-display" class="font-mono text-slate-900"></strong>? If the vehicle was rented, it will be restored to available.
            </p>

            <form method="POST" action="index.php?view=bookings" class="pt-2 flex items-center justify-center gap-2">
                <input type="hidden" name="action" value="delete_booking">
                <input type="hidden" name="booking_id" id="delete_booking_id" value="">

                <button
                    type="button"
                    onclick="closeDeleteBookingModal()"
                    class="px-4 py-2 text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-[4px]"
                >
                    No, Keep
                </button>
                <button
                    type="submit"
                    class="px-4 py-2 text-xs font-bold text-white bg-red-600 hover:bg-red-700 rounded-[4px]"
                >
                    Yes, Delete
                </button>
            </form>
        </div>
    </div>
</div>


<!-- 6. QUICK STATUS CHANGE MODAL -->
<div id="modal-status-change" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 modal-backdrop-custom overflow-y-auto">
    <div class="bg-white border border-slate-200 rounded-[6px] shadow-xl max-w-xs w-full my-auto overflow-hidden">
        <div class="px-4 py-3 bg-slate-900 text-white flex items-center justify-between">
            <h4 class="text-xs font-bold uppercase tracking-wider">Update Trip Status</h4>
            <button type="button" onclick="closeStatusModal()" class="text-slate-400 hover:text-white">&times;</button>
        </div>

        <form method="POST" action="index.php?view=bookings" class="p-4 space-y-3">
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" name="booking_id" id="status_booking_id" value="">

            <div>
                <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Booking Reference</label>
                <div id="status_ref_display" class="font-mono font-bold text-xs text-slate-900"></div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-800 mb-1">New Status</label>
                <select
                    id="status_select_value"
                    name="booking_status"
                    required
                    class="w-full px-3 py-2 text-xs font-semibold text-slate-900 bg-slate-50 border border-slate-300 rounded-[4px] focus:ring-1 focus:ring-red-600 focus:border-red-600"
                >
                    <option value="Pending">Pending (Draft / Unconfirmed)</option>
                    <option value="Confirmed">Confirmed (Reserved)</option>
                    <option value="Ongoing">Ongoing (Active Trip)</option>
                    <option value="Completed">Completed (Returned)</option>
                    <option value="Cancelled">Cancelled</option>
                </select>
            </div>

            <div class="pt-2 flex items-center justify-end gap-2">
                <button type="button" onclick="closeStatusModal()" class="px-3 py-1.5 text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-[4px]">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-1.5 text-xs font-bold text-white bg-red-600 hover:bg-red-700 rounded-[4px]">
                    Save Status
                </button>
            </div>
        </form>
    </div>
</div>


<!-- =========================================================================
     7. PERSONALIZED CUSTOMER PICKER MODAL
     ========================================================================= -->
<div id="modal-customer-picker" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 modal-backdrop-custom overflow-y-auto">
    <div class="bg-white border border-slate-200 rounded-[6px] shadow-2xl max-w-2xl w-full max-h-[85vh] flex flex-col my-auto overflow-hidden">
        
        <!-- Header -->
        <div class="px-5 py-3.5 bg-slate-900 text-white flex items-center justify-between border-b border-slate-800">
            <div class="flex items-center gap-2.5">
                <div class="w-7 h-7 bg-red-600 rounded-[3px] flex items-center justify-center text-white">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-xs font-extrabold tracking-tight uppercase">Select Customer for Reservation</h3>
                    <p class="text-[11px] text-slate-400">Search directory by name, mobile number, or email</p>
                </div>
            </div>
            <button type="button" onclick="closeCustomerPickerModal()" class="text-slate-400 hover:text-white text-lg font-bold">&times;</button>
        </div>

        <!-- Search Bar Toolbar -->
        <div class="p-4 bg-slate-50 border-b border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-3">
            <div class="relative w-full sm:w-80">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </span>
                <input
                    type="text"
                    id="customer_picker_search"
                    oninput="filterCustomerPicker()"
                    placeholder="Type name, phone, or email to filter..."
                    class="w-full pl-9 pr-3 py-1.5 text-xs bg-white border border-slate-300 rounded-[4px] focus:outline-none focus:ring-1 focus:ring-red-600 focus:border-red-600"
                >
            </div>
            <div class="flex items-center gap-2 w-full sm:w-auto justify-between sm:justify-end">
                <span class="text-[11px] text-slate-500 font-medium" id="customer_picker_count">
                    <?= count($formData['customers']) ?> customers
                </span>
                <button
                    type="button"
                    onclick="closeCustomerPickerModal(); openQuickCustomerModal();"
                    class="px-2.5 py-1 text-xs font-bold text-red-600 bg-red-50 hover:bg-red-100 border border-red-200 rounded-[4px] transition-colors"
                >
                    + Register New
                </button>
            </div>
        </div>

        <!-- Scrollable Customer Cards List -->
        <div class="flex-1 overflow-y-auto p-4 space-y-2 max-h-[55vh]" id="customer_picker_list">
            <?php foreach ($formData['customers'] as $c): 
                $fullName = trim($c['first_name'] . ' ' . $c['last_name']);
                $initials = strtoupper(substr($c['first_name'], 0, 1) . substr($c['last_name'], 0, 1));
                $searchIndex = strtolower($fullName . ' ' . ($c['phone_number'] ?? '') . ' ' . ($c['email'] ?? ''));
            ?>
            <div
                class="customer-card-item border border-slate-200 rounded-[4px] p-3 hover:border-red-500 hover:bg-red-50/20 transition-all flex items-center justify-between gap-3 cursor-pointer group"
                data-search="<?= htmlspecialchars($searchIndex) ?>"
                data-id="<?= (int)$c['customer_id'] ?>"
                data-name="<?= htmlspecialchars($fullName) ?>"
                data-phone="<?= htmlspecialchars($c['phone_number'] ?? '') ?>"
                data-email="<?= htmlspecialchars($c['email'] ?? '') ?>"
                data-vip="<?= !empty($c['is_vip']) ? '1' : '0' ?>"
                data-initials="<?= htmlspecialchars($initials) ?>"
                onclick="selectCustomerFromCard(this)"
            >
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-10 h-10 rounded-[4px] bg-slate-900 group-hover:bg-red-600 text-white font-extrabold flex items-center justify-center text-xs shadow-xs transition-colors shrink-0">
                        <?= $initials ?>
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <span class="font-extrabold text-xs text-slate-900 group-hover:text-red-700 transition-colors">
                                <?= htmlspecialchars($fullName) ?>
                            </span>
                            <?php if (!empty($c['is_vip'])): ?>
                                <span class="px-1.5 py-0.2 text-[9px] font-black uppercase tracking-wider bg-amber-100 text-amber-800 border border-amber-200 rounded-[2px]">VIP</span>
                            <?php endif; ?>
                            <?php if (!empty($c['is_returning'])): ?>
                                <span class="px-1.5 py-0.2 text-[9px] font-semibold bg-blue-50 text-blue-700 border border-blue-200 rounded-[2px]">Returning</span>
                            <?php endif; ?>
                        </div>
                        <div class="text-[11px] text-slate-500 flex items-center gap-2 mt-0.5 truncate">
                            <span class="font-mono text-slate-700 font-semibold"><?= htmlspecialchars($c['phone_number']) ?></span>
                            <?php if (!empty($c['email'])): ?>
                                <span class="text-slate-300">&bull;</span>
                                <span class="text-slate-500 truncate"><?= htmlspecialchars($c['email']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="shrink-0">
                    <button
                        type="button"
                        class="px-3 py-1.5 text-xs font-bold text-white bg-slate-900 group-hover:bg-red-600 rounded-[4px] shadow-xs transition-colors"
                    >
                        Select
                    </button>
                </div>
            </div>
            <?php endforeach; ?>

            <div id="customer_picker_no_results" class="hidden py-12 text-center text-slate-400">
                <svg class="w-8 h-8 mx-auto text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="text-xs font-bold text-slate-600">No customers found</p>
                <p class="text-[11px] text-slate-400 mt-0.5">Try searching with a different term or register a new customer.</p>
            </div>
        </div>

        <!-- Footer -->
        <div class="px-5 py-3 bg-slate-100 border-t border-slate-200 flex items-center justify-between text-xs">
            <span class="text-[11px] text-slate-500">Click anywhere on a row or click Select</span>
            <button
                type="button"
                onclick="closeCustomerPickerModal()"
                class="px-4 py-1.5 text-xs font-bold text-slate-700 bg-white hover:bg-slate-50 border border-slate-300 rounded-[4px]"
            >
                Close
            </button>
        </div>

    </div>
</div>


<!-- =========================================================================
     8. PERSONALIZED VEHICLE PICKER MODAL
     ========================================================================= -->
<div id="modal-vehicle-picker" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 modal-backdrop-custom overflow-y-auto">
    <div class="bg-white border border-slate-200 rounded-[6px] shadow-2xl max-w-3xl w-full max-h-[85vh] flex flex-col my-auto overflow-hidden">

        <!-- Header -->
        <div class="px-5 py-3.5 bg-slate-900 text-white flex items-center justify-between border-b border-slate-800">
            <div class="flex items-center gap-2.5">
                <div class="w-7 h-7 bg-red-600 rounded-[3px] flex items-center justify-center text-white">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-xs font-extrabold tracking-tight uppercase">Assign Fleet Vehicle</h3>
                    <p class="text-[11px] text-slate-400">Search and select an operational unit for this booking</p>
                </div>
            </div>
            <button type="button" onclick="closeVehiclePickerModal()" class="text-slate-400 hover:text-white text-lg font-bold">&times;</button>
        </div>

        <!-- Search & Category Filter Toolbar -->
        <div class="p-4 bg-slate-50 border-b border-slate-200 space-y-3">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-3">
                <div class="relative w-full sm:w-80">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </span>
                    <input
                        type="text"
                        id="vehicle_picker_search"
                        oninput="filterVehiclePicker()"
                        placeholder="Search make, model, plate, or type..."
                        class="w-full pl-9 pr-3 py-1.5 text-xs bg-white border border-slate-300 rounded-[4px] focus:outline-none focus:ring-1 focus:ring-red-600 focus:border-red-600"
                    >
                </div>
                <div class="flex items-center gap-2 text-[11px] text-slate-500 font-medium">
                    <span id="vehicle_picker_count"><?= count($formData['vehicles']) ?> vehicles</span>
                </div>
            </div>

            <!-- Type Filter Chips -->
            <div class="flex items-center gap-1.5 flex-wrap">
                <span class="text-[10px] uppercase font-bold text-slate-400 mr-1">Category:</span>
                <button type="button" onclick="filterVehiclePickerByType('All', this)" class="vehicle-filter-chip px-2.5 py-1 text-[11px] font-bold rounded-[3px] bg-red-600 text-white border border-red-600 transition-colors">
                    All
                </button>
                <button type="button" onclick="filterVehiclePickerByType('SUV', this)" class="vehicle-filter-chip px-2.5 py-1 text-[11px] font-bold rounded-[3px] bg-white text-slate-700 border border-slate-300 hover:bg-slate-100 transition-colors">
                    SUV
                </button>
                <button type="button" onclick="filterVehiclePickerByType('Van', this)" class="vehicle-filter-chip px-2.5 py-1 text-[11px] font-bold rounded-[3px] bg-white text-slate-700 border border-slate-300 hover:bg-slate-100 transition-colors">
                    Van
                </button>
                <button type="button" onclick="filterVehiclePickerByType('Sedan', this)" class="vehicle-filter-chip px-2.5 py-1 text-[11px] font-bold rounded-[3px] bg-white text-slate-700 border border-slate-300 hover:bg-slate-100 transition-colors">
                    Sedan
                </button>
                <button type="button" onclick="filterVehiclePickerByType('MPV', this)" class="vehicle-filter-chip px-2.5 py-1 text-[11px] font-bold rounded-[3px] bg-white text-slate-700 border border-slate-300 hover:bg-slate-100 transition-colors">
                    MPV
                </button>
                <button type="button" onclick="filterVehiclePickerByType('Pickup', this)" class="vehicle-filter-chip px-2.5 py-1 text-[11px] font-bold rounded-[3px] bg-white text-slate-700 border border-slate-300 hover:bg-slate-100 transition-colors">
                    Pickup
                </button>
            </div>
        </div>

        <!-- Scrollable Vehicle Cards List -->
        <div class="flex-1 overflow-y-auto p-4 space-y-2.5 max-h-[55vh]" id="vehicle_picker_list">
            <?php foreach ($formData['vehicles'] as $v): 
                $vehicleName = trim($v['make'] . ' ' . $v['model'] . ' (' . $v['year'] . ')');
                $searchIndex = strtolower($vehicleName . ' ' . ($v['license_plate'] ?? '') . ' ' . ($v['type'] ?? '') . ' ' . ($v['status'] ?? ''));
                $statusColor = match($v['status']) {
                    'Available'   => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                    'Rented'      => 'bg-blue-50 text-blue-700 border-blue-200',
                    'Maintenance' => 'bg-amber-50 text-amber-800 border-amber-200',
                    default       => 'bg-slate-100 text-slate-700 border-slate-200',
                };
                $statusDot = match($v['status']) {
                    'Available'   => 'bg-emerald-500',
                    'Rented'      => 'bg-blue-500',
                    'Maintenance' => 'bg-amber-500',
                    default       => 'bg-slate-500',
                };
            ?>
            <div
                class="vehicle-card-item border border-slate-200 rounded-[4px] p-3 hover:border-red-500 hover:bg-red-50/20 transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-3 cursor-pointer group"
                data-search="<?= htmlspecialchars($searchIndex) ?>"
                data-type="<?= htmlspecialchars($v['type']) ?>"
                data-status="<?= htmlspecialchars($v['status']) ?>"
                data-id="<?= (int)$v['vehicle_id'] ?>"
                data-name="<?= htmlspecialchars($vehicleName) ?>"
                data-plate="<?= htmlspecialchars($v['license_plate']) ?>"
                data-veh-type="<?= htmlspecialchars($v['type']) ?>"
                data-capacity="<?= (int)$v['seating_capacity'] ?>"
                onclick="selectVehicleFromCard(this)"
            >
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-11 h-11 rounded-[4px] bg-slate-900 group-hover:bg-red-600 text-white font-black flex flex-col items-center justify-center text-[10px] shadow-xs transition-colors shrink-0">
                        <span><?= htmlspecialchars($v['type']) ?></span>
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="font-extrabold text-xs text-slate-900 group-hover:text-red-700 transition-colors">
                                <?= htmlspecialchars($vehicleName) ?>
                            </span>
                            <span class="font-mono font-bold text-[11px] bg-slate-100 border border-slate-300 px-1.5 py-0.2 rounded-[2px] text-slate-800">
                                <?= htmlspecialchars($v['license_plate']) ?>
                            </span>
                        </div>
                        <div class="text-[11px] text-slate-500 flex items-center gap-2 mt-0.5 flex-wrap">
                            <span class="font-semibold text-slate-700">Max <?= (int)$v['seating_capacity'] ?> Pax</span>
                            <span class="text-slate-300">&bull;</span>
                            <span><?= number_format((int)($v['mileage'] ?? 0)) ?> km</span>
                            <span class="text-slate-300">&bull;</span>
                            <span class="inline-flex items-center gap-1 px-1.5 py-0.2 text-[10px] font-bold border rounded-[2px] <?= $statusColor ?>">
                                <span class="w-1.5 h-1.5 rounded-full <?= $statusDot ?>"></span>
                                <span><?= htmlspecialchars($v['status']) ?></span>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="shrink-0 flex items-center justify-end">
                    <button
                        type="button"
                        class="px-3.5 py-1.5 text-xs font-bold text-white bg-slate-900 group-hover:bg-red-600 rounded-[4px] shadow-xs transition-colors"
                    >
                        Select Unit
                    </button>
                </div>
            </div>
            <?php endforeach; ?>

            <div id="vehicle_picker_no_results" class="hidden py-12 text-center text-slate-400">
                <svg class="w-8 h-8 mx-auto text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                </svg>
                <p class="text-xs font-bold text-slate-600">No vehicles match filters</p>
                <p class="text-[11px] text-slate-400 mt-0.5">Try searching with a different keyword or select "All" categories.</p>
            </div>
        </div>

        <!-- Footer -->
        <div class="px-5 py-3 bg-slate-100 border-t border-slate-200 flex items-center justify-between text-xs">
            <span class="text-[11px] text-slate-500">Click anywhere on a vehicle to select</span>
            <button
                type="button"
                onclick="closeVehiclePickerModal()"
                class="px-4 py-1.5 text-xs font-bold text-slate-700 bg-white hover:bg-slate-50 border border-slate-300 rounded-[4px]"
            >
                Close
            </button>
        </div>

    </div>
</div>
