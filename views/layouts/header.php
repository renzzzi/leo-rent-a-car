<?php
// Ensure session is available in the layout
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_loggedUser = htmlspecialchars($_SESSION['username'] ?? 'User');
$_loggedRole = htmlspecialchars($_SESSION['role'] ?? 'Staff');
$currentView = $_GET['view'] ?? 'bookings';
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leo Rent-A-Car | Operations System</title>

    <!-- Google Font: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind CSS -->
    <link rel="stylesheet" href="assets/css/tailwind.min.css">
    <script src="assets/js/tailwind.js"></script>

    <!-- Tailwind Theme Extension -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'system-ui', '-apple-system', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            red:       '#DC2626',
                            darkred:   '#991B1B',
                            hover:     '#B91C1C',
                            lightred:  '#FEF2F2',
                            redborder: '#FECACA'
                        }
                    }
                }
            }
        };
    </script>

    <!-- Universal Custom Style (Sharp 4px Border Radius System) -->
    <link rel="stylesheet" href="assets/css/universal-style.css">
</head>
<body class="min-h-full flex flex-col bg-slate-50 text-slate-800 antialiased selection:bg-red-600 selection:text-white">

    <!-- Global Navigation Header -->
    <header class="sticky top-0 z-40 bg-white border-b border-slate-200 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">

                <!-- Left: Brand Logo & Title -->
                <div class="flex items-center gap-3">
                    <a href="index.php?view=bookings" class="flex items-center gap-3 group">
                        <div class="w-10 h-10 rounded-xl border border-red-200 bg-white p-0.5 flex items-center justify-center shadow-xs overflow-hidden">
                            <img src="assets/images/Leo-rent-a-car.jpg" alt="Leo Logo" class="w-full h-full object-cover rounded-lg" onerror="this.onerror=null; this.parentElement.innerHTML='<span class=\'font-extrabold text-red-600 text-sm\'>LR</span>';">
                        </div>
                        <div>
                            <div class="text-base font-extrabold tracking-tight text-slate-900 group-hover:text-red-600 transition-colors">
                                LEO <span class="text-red-600">RENT-A-CAR</span>
                            </div>
                            <div class="text-[10px] uppercase font-bold tracking-wider text-slate-400">
                                Operations Portal
                            </div>
                        </div>
                    </a>

                    <!-- Divider -->
                    <div class="hidden md:block h-6 w-px bg-slate-200 ml-3"></div>

                    <!-- Main Navigation Tabs -->
                    <nav class="hidden md:flex items-center gap-1.5 ml-4">
                        <a href="index.php?view=bookings"
                           class="px-3.5 py-1.5 text-xs font-semibold rounded-lg transition-colors <?= ($currentView === 'bookings') ? 'bg-red-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' ?>">
                            Bookings
                        </a>
                        <a href="index.php?view=vehicles"
                           class="px-3.5 py-1.5 text-xs font-semibold rounded-lg transition-colors <?= ($currentView === 'vehicles') ? 'bg-red-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' ?>">
                            Vehicles
                        </a>
                        <a href="index.php?view=customers"
                           class="px-3.5 py-1.5 text-xs font-semibold rounded-lg transition-colors <?= ($currentView === 'customers') ? 'bg-red-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' ?>">
                            Customers
                        </a>
                        <a href="index.php?view=drivers"
                           class="px-3.5 py-1.5 text-xs font-semibold rounded-lg transition-colors <?= ($currentView === 'drivers') ? 'bg-red-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' ?>">
                            Drivers
                        </a>
                        <a href="index.php?view=maintenance"
                           class="px-3.5 py-1.5 text-xs font-semibold rounded-lg transition-colors <?= ($currentView === 'maintenance') ? 'bg-red-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' ?>">
                            Maintenance
                        </a>
                        <?php if ($_loggedRole === 'Owner'): ?>
                        <a href="index.php?view=users"
                           class="px-3.5 py-1.5 text-xs font-semibold rounded-lg transition-colors <?= ($currentView === 'users') ? 'bg-red-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' ?>">
                            Managers
                        </a>
                        <?php endif; ?>
                    </nav>
                </div>

                <!-- Right: User Identity & Sign Out -->
                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-2 px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg">
                        <div class="w-6 h-6 rounded-md bg-slate-800 text-white flex items-center justify-center text-[11px] font-bold">
                            <?= strtoupper(substr($_loggedUser, 0, 1)) ?>
                        </div>
                        <div class="text-xs">
                            <span class="font-bold text-slate-800"><?= $_loggedUser ?></span>
                            <?php if (!empty($_loggedRole)): ?>
                                <span class="ml-1 px-2 py-0.5 text-[10px] font-semibold bg-red-50 text-red-700 border border-red-200/80 rounded-md">
                                    <?= $_loggedRole ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <a href="index.php?logout=1"
                       onclick="return confirm('Sign out of Leo Rent-A-Car operations portal?');"
                       title="Sign Out"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:text-red-600 hover:bg-red-50 border border-slate-200 hover:border-red-200 rounded-lg transition-colors">
                        <svg class="w-3.5 h-3.5 text-slate-500 hover:text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        <span>Sign Out</span>
                    </a>
                </div>

            </div>

            <!-- Mobile Navigation Bar -->
            <div class="md:hidden flex items-center gap-1 py-2 border-t border-slate-100 overflow-x-auto">
                <a href="index.php?view=bookings"
                   class="px-2.5 py-1 text-xs font-semibold whitespace-nowrap rounded-lg <?= ($currentView === 'bookings') ? 'bg-red-600 text-white' : 'text-slate-600' ?>">
                    Bookings
                </a>
                <a href="index.php?view=vehicles"
                   class="px-2.5 py-1 text-xs font-semibold whitespace-nowrap rounded-lg <?= ($currentView === 'vehicles') ? 'bg-red-600 text-white' : 'text-slate-600' ?>">
                    Vehicles
                </a>
                <a href="index.php?view=customers"
                   class="px-2.5 py-1 text-xs font-semibold whitespace-nowrap rounded-lg <?= ($currentView === 'customers') ? 'bg-red-600 text-white' : 'text-slate-600' ?>">
                    Customers
                </a>
                <a href="index.php?view=drivers"
                   class="px-2.5 py-1 text-xs font-semibold whitespace-nowrap rounded-lg <?= ($currentView === 'drivers') ? 'bg-red-600 text-white' : 'text-slate-600' ?>">
                    Drivers
                </a>
                <a href="index.php?view=maintenance"
                   class="px-2.5 py-1 text-xs font-semibold whitespace-nowrap rounded-lg <?= ($currentView === 'maintenance') ? 'bg-red-600 text-white' : 'text-slate-600' ?>">
                    Maintenance
                </a>
                <?php if ($_loggedRole === 'Owner'): ?>
                <a href="index.php?view=users"
                   class="px-2.5 py-1 text-xs font-semibold whitespace-nowrap rounded-lg <?= ($currentView === 'users') ? 'bg-red-600 text-white' : 'text-slate-600' ?>">
                    Managers
                </a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Main Content Container -->
    <main class="flex-1 w-full">
