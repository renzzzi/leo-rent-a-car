<?php
/**
 * Leo Rent-A-Car - Public Landing Page
 * Dominant White with a Touch of Red
 * Razor-Sharp Corners (0px border-radius)
 */

// Optional: Attempt to load real vehicles from DB if available
$dbVehicles = [];
if (file_exists(__DIR__ . '/config/database.php')) {
    try {
        $dbConfig = require __DIR__ . '/config/database.php';
        $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['dbname']};charset={$dbConfig['charset']}";
        $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => 2
        ]);
        $stmt = $pdo->query("SELECT vehicle_id, make, model, year, daily_rate, status FROM Vehicles WHERE status = 'Available' LIMIT 12");
        if ($stmt) {
            $dbVehicles = $stmt->fetchAll();
        }
    } catch (\Throwable $e) {
        $dbVehicles = [];
    }
}

// Fallback curated fleet showcase
$fleet = [
    [
        'id' => 1,
        'name' => 'Toyota Fortuner GR-S',
        'category' => 'suv',
        'year' => 2024,
        'transmission' => 'Automatic',
        'seats' => 7,
        'fuel' => 'Diesel',
        'luggage' => '4 Bags',
        'rate' => 3800,
        'badge' => 'Most Popular',
        'image' => 'https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?auto=format&fit=crop&w=800&q=80',
        'desc' => 'Rugged 7-seater midsize SUV built for executive business trips and Philippine provincial getaways.'
    ],
    [
        'id' => 2,
        'name' => 'Toyota HiAce Super Grandia Elite',
        'category' => 'van',
        'year' => 2024,
        'transmission' => 'Automatic',
        'seats' => 10,
        'fuel' => 'Diesel',
        'luggage' => '6 Bags',
        'rate' => 5200,
        'badge' => 'VIP Comfort',
        'image' => 'https://images.unsplash.com/photo-1549399542-7e3f8b79c341?auto=format&fit=crop&w=800&q=80',
        'desc' => 'Ultimate luxury captain seats, premium ride suspension, and high-roof executive travel.'
    ],
    [
        'id' => 3,
        'name' => 'Toyota Innova 2.8 V',
        'category' => 'mpv',
        'year' => 2023,
        'transmission' => 'Automatic',
        'seats' => 7,
        'fuel' => 'Diesel',
        'luggage' => '4 Bags',
        'rate' => 2800,
        'badge' => 'Family Choice',
        'image' => 'https://images.unsplash.com/photo-1503376780353-7e6692767b70?auto=format&fit=crop&w=800&q=80',
        'desc' => 'The gold standard in Filipino family comfort, exceptional fuel economy, and effortless reliability.'
    ],
    [
        'id' => 4,
        'name' => 'Toyota Vios 1.5 G CVT',
        'category' => 'sedan',
        'year' => 2024,
        'transmission' => 'Automatic',
        'seats' => 5,
        'fuel' => 'Gasoline',
        'luggage' => '2 Bags',
        'rate' => 1800,
        'badge' => 'City Economy',
        'image' => 'https://images.unsplash.com/photo-1552519507-da3b142c6e3d?auto=format&fit=crop&w=800&q=80',
        'desc' => 'Nimble, fuel-efficient sedan optimal for urban city drives, airport pickups, and daily business commutes.'
    ],
    [
        'id' => 5,
        'name' => 'Ford Everest Titanium+ 4x4',
        'category' => 'suv',
        'year' => 2024,
        'transmission' => 'Automatic',
        'seats' => 7,
        'fuel' => 'Diesel',
        'luggage' => '4 Bags',
        'rate' => 4300,
        'badge' => 'Premium 4WD',
        'image' => 'https://images.unsplash.com/photo-1519641471654-76ce0107ad1b?auto=format&fit=crop&w=800&q=80',
        'desc' => 'Panoramiic sunroof, advanced driver-assist safety suite, and commanding high-clearance off-road capability.'
    ],
    [
        'id' => 6,
        'name' => 'Nissan Urvan NV350 Commuter',
        'category' => 'van',
        'year' => 2023,
        'transmission' => 'Manual',
        'seats' => 15,
        'fuel' => 'Diesel',
        'luggage' => '5 Bags',
        'rate' => 3400,
        'badge' => 'Group Travel',
        'image' => 'https://images.unsplash.com/photo-1570125909232-eb263c188f7e?auto=format&fit=crop&w=800&q=80',
        'desc' => 'High-capacity 15-passenger van tailored for corporate tours, team outings, and airport transfers.'
    ]
];
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leo Rent-A-Car | Premium Self-Drive & Chauffeur Services</title>

    <!-- Downloaded Tailwind CSS -->
    <link rel="stylesheet" href="public/assets/css/tailwind.min.css">
    <script src="public/assets/js/tailwind.js"></script>

    <script>
        // Custom Tailwind Configuration
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            red: '#DC2626',      // Primary vibrant red
                            darkred: '#991B1B',  // Deep accent red
                            softred: '#FEF2F2',  // Light red background tint
                            black: '#0F172A',
                            charcoal: '#1E293B',
                            gray: '#64748B',
                            border: '#E2E8F0'
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'Segoe UI', 'system-ui', '-apple-system', 'sans-serif'],
                        display: ['Oswald', 'Impact', 'system-ui', 'sans-serif']
                    }
                }
            }
        };
    </script>

    <!-- Strict Zero Corner Radius Override: Guarantee razor-sharp geometry everywhere -->
    <style>
        *, *::before, *::after {
            border-radius: 0px !important;
        }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        ::-webkit-scrollbar-thumb {
            background: #dc2626;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #991b1b;
        }

        .sharp-border {
            border: 1px solid #e2e8f0;
        }
        .sharp-border-red {
            border: 1px solid #dc2626;
        }
        .sharp-shadow {
            box-shadow: 4px 4px 0px 0px rgba(15, 23, 42, 0.08);
        }
        .sharp-shadow-red {
            box-shadow: 4px 4px 0px 0px #dc2626;
        }
        .sharp-shadow-hover:hover {
            box-shadow: 6px 6px 0px 0px rgba(220, 38, 38, 0.2);
            transform: translate(-2px, -2px);
        }
    </style>
</head>
<body class="bg-white text-zinc-900 antialiased font-sans selection:bg-red-600 selection:text-white">

    <!-- Top Announcement Bar -->
    <div class="bg-zinc-950 text-white text-xs border-b border-zinc-800 py-2.5 px-4">
        <div class="max-w-7xl mx-auto flex flex-col md:flex-row justify-between items-center gap-2">
            <div class="flex items-center gap-3">
                <span class="inline-block w-2 h-2 bg-red-600 animate-pulse"></span>
                <span class="font-medium tracking-wide">LEO RENT-A-CAR: Premium Fleet, Clean Sanitized Units & Verified Drivers</span>
            </div>
            <div class="flex items-center gap-6 text-zinc-300">
                <span class="flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 text-red-500" fill="currentColor" viewBox="0 0 20 20"><path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 4V3z"/></svg>
                    24/7 Hotline: <strong class="text-white font-mono">+63 (917) 555-LEOS</strong>
                </span>
                <span class="hidden sm:inline-block text-zinc-600">|</span>
                <a href="public/index.php" class="text-red-400 hover:text-white transition-colors uppercase tracking-wider font-semibold text-[11px] flex items-center gap-1">
                    <span>Staff Portal</span>
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="square" stroke-linejoin="miter" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
            </div>
        </div>
    </div>

    <!-- Main Navigation Bar -->
    <header class="sticky top-0 z-50 bg-white/95 backdrop-blur-md border-b-2 border-neutral-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-20">
                
                <!-- Brand / Logo -->
                <a href="index.php" class="flex items-center gap-3.5 group">
                    <div class="relative w-12 h-12 overflow-hidden border-2 border-red-600 p-0.5 bg-white">
                        <img src="public/assets/images/leo-rent-a-car.jpg" alt="Leo Rent-A-Car Logo" class="w-full h-full object-cover">
                    </div>
                    <div class="flex flex-col">
                        <span class="text-xl sm:text-2xl font-black tracking-tight text-black flex items-center gap-1 uppercase">
                            LEO <span class="text-red-600">RENT-A-CAR</span>
                        </span>
                        <span class="text-[10px] tracking-widest text-zinc-500 uppercase font-semibold">Reliable &bull; Clean &bull; Professional</span>
                    </div>
                </a>

                <!-- Desktop Navigation Menu -->
                <nav class="hidden lg:flex items-center gap-8 font-medium text-sm tracking-wide">
                    <a href="#fleet" class="text-zinc-800 hover:text-red-600 py-1 border-b-2 border-transparent hover:border-red-600 transition-colors uppercase font-semibold text-xs tracking-wider">Fleet Showcase</a>
                    <a href="#search-bar" class="text-zinc-800 hover:text-red-600 py-1 border-b-2 border-transparent hover:border-red-600 transition-colors uppercase font-semibold text-xs tracking-wider">Quick Booking</a>
                    <a href="#services" class="text-zinc-800 hover:text-red-600 py-1 border-b-2 border-transparent hover:border-red-600 transition-colors uppercase font-semibold text-xs tracking-wider">Services</a>
                    <a href="#requirements" class="text-zinc-800 hover:text-red-600 py-1 border-b-2 border-transparent hover:border-red-600 transition-colors uppercase font-semibold text-xs tracking-wider">Requirements</a>
                    <a href="#faq" class="text-zinc-800 hover:text-red-600 py-1 border-b-2 border-transparent hover:border-red-600 transition-colors uppercase font-semibold text-xs tracking-wider">FAQs</a>
                    <a href="#contact" class="text-zinc-800 hover:text-red-600 py-1 border-b-2 border-transparent hover:border-red-600 transition-colors uppercase font-semibold text-xs tracking-wider">Contact</a>
                </nav>

                <!-- Action Button -->
                <div class="hidden sm:flex items-center gap-3">
                    <button onclick="openBookingModal('General Inquiry')" class="bg-red-600 hover:bg-red-700 text-white font-bold text-xs uppercase tracking-widest px-6 py-3.5 border-2 border-red-600 transition-all flex items-center gap-2 cursor-pointer shadow-sm">
                        <span>Book A Vehicle</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="square" stroke-linejoin="miter" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </button>
                </div>

                <!-- Mobile Hamburger Button -->
                <div class="flex lg:hidden items-center">
                    <button id="mobileMenuBtn" type="button" class="p-2 border border-zinc-300 text-zinc-800 hover:text-red-600 hover:border-red-600 focus:outline-none">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="square" stroke-linejoin="miter" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Menu Drawer -->
        <div id="mobileMenu" class="hidden lg:hidden border-t border-zinc-200 bg-white px-4 pt-3 pb-6 space-y-3">
            <a href="#fleet" class="block py-2 text-sm font-semibold uppercase tracking-wider text-zinc-800 hover:text-red-600">Fleet Showcase</a>
            <a href="#search-bar" class="block py-2 text-sm font-semibold uppercase tracking-wider text-zinc-800 hover:text-red-600">Quick Booking</a>
            <a href="#services" class="block py-2 text-sm font-semibold uppercase tracking-wider text-zinc-800 hover:text-red-600">Services</a>
            <a href="#requirements" class="block py-2 text-sm font-semibold uppercase tracking-wider text-zinc-800 hover:text-red-600">Requirements</a>
            <a href="#faq" class="block py-2 text-sm font-semibold uppercase tracking-wider text-zinc-800 hover:text-red-600">FAQs</a>
            <a href="#contact" class="block py-2 text-sm font-semibold uppercase tracking-wider text-zinc-800 hover:text-red-600">Contact</a>
            <div class="pt-2 border-t border-zinc-100 flex flex-col gap-2">
                <button onclick="openBookingModal('General Inquiry')" class="w-full text-center bg-red-600 text-white font-bold text-xs uppercase tracking-widest py-3 border border-red-600">
                    Book A Vehicle
                </button>
                <a href="public/index.php" class="w-full text-center border border-zinc-800 text-zinc-900 font-bold text-xs uppercase tracking-widest py-3">
                    Staff Portal Login
                </a>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="relative bg-white pt-10 pb-16 lg:pt-16 lg:pb-24 border-b border-neutral-200 overflow-hidden">
        <!-- Subtle grid background accent -->
        <div class="absolute inset-0 opacity-[0.03] pointer-events-none" style="background-image: radial-gradient(#000 1px, transparent 1px); background-size: 24px 24px;"></div>
        
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
                
                <!-- Hero Left: Typography & CTAs -->
                <div class="lg:col-span-7 space-y-6">
                    <div class="inline-flex items-center gap-2 bg-red-50 border border-red-200 px-3.5 py-1.5">
                        <span class="w-2 h-2 bg-red-600"></span>
                        <span class="text-xs font-bold uppercase tracking-widest text-red-700">Official Car Rental Service &bull; Self-Drive & Chauffeur</span>
                    </div>

                    <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black text-black tracking-tight leading-[1.08] uppercase">
                        DRIVE WITH <span class="text-red-600 underline decoration-red-600 decoration-4 underline-offset-8">CONFIDENCE</span> & FREEDOM.
                    </h1>

                    <p class="text-base sm:text-lg text-zinc-600 max-w-2xl leading-relaxed">
                        Rent reliable, modern, and thoroughly inspected Sedans, SUVs, and Vans in the Philippines. Whether navigating city business or embarking on provincial road trips, Leo Rent-A-Car delivers unbeatable service with transparent daily rates and zero hidden charges.
                    </p>

                    <!-- Hero Quick Stats / Trust Points -->
                    <div class="grid grid-cols-3 gap-4 pt-2 border-t border-neutral-200 max-w-xl">
                        <div class="border-l-2 border-red-600 pl-3">
                            <div class="text-2xl font-black text-black">100%</div>
                            <div class="text-xs text-zinc-500 uppercase font-semibold">Insured Fleet</div>
                        </div>
                        <div class="border-l-2 border-red-600 pl-3">
                            <div class="text-2xl font-black text-black">24/7</div>
                            <div class="text-xs text-zinc-500 uppercase font-semibold">Road Assistance</div>
                        </div>
                        <div class="border-l-2 border-red-600 pl-3">
                            <div class="text-2xl font-black text-black">&#8369;0</div>
                            <div class="text-xs text-zinc-500 uppercase font-semibold">Surprise Fees</div>
                        </div>
                    </div>

                    <!-- Call To Action Buttons -->
                    <div class="flex flex-wrap items-center gap-4 pt-4">
                        <a href="#search-bar" class="bg-red-600 hover:bg-red-700 text-white font-bold text-xs uppercase tracking-widest px-8 py-4 border-2 border-red-600 transition-all flex items-center gap-3 cursor-pointer">
                            <span>Check Rates & Availability</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="square" stroke-linejoin="miter" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </a>
                        <a href="#fleet" class="bg-white hover:bg-zinc-50 text-black font-bold text-xs uppercase tracking-widest px-8 py-4 border-2 border-zinc-900 transition-all">
                            View All Vehicles
                        </a>
                    </div>
                </div>

                <!-- Hero Right: Sharp Feature Box with Logo & Featured Unit -->
                <div class="lg:col-span-5">
                    <div class="border-2 border-zinc-900 bg-white p-6 relative sharp-shadow-red">
                        <div class="absolute -top-3 left-6 bg-red-600 text-white text-[10px] font-black uppercase tracking-widest px-3 py-1">
                            Featured Deal Of The Month
                        </div>

                        <!-- Car Image in Hero -->
                        <div class="relative bg-zinc-100 border border-zinc-200 overflow-hidden mb-6 mt-2">
                            <img src="https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?auto=format&fit=crop&w=800&q=80" 
                                 alt="Toyota Fortuner" 
                                 class="w-full h-56 object-cover hover:scale-105 transition-transform duration-500">
                            <div class="absolute bottom-0 right-0 bg-zinc-950 text-white px-3 py-1 text-xs font-mono font-bold">
                                7-SEATER DIESEL
                            </div>
                        </div>

                        <!-- Car Quick Specs -->
                        <div class="space-y-4">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h3 class="text-xl font-bold uppercase text-black">Toyota Fortuner 4x4</h3>
                                    <p class="text-xs text-zinc-500 font-medium">Automatic Transmission &bull; Cruise Control</p>
                                </div>
                                <div class="text-right">
                                    <span class="text-xs text-zinc-500 block uppercase font-medium">Starting at</span>
                                    <span class="text-2xl font-black text-red-600">&#8369;3,800</span>
                                    <span class="text-xs text-zinc-500">/day</span>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-2 text-xs border-t border-b border-neutral-200 py-3">
                                <div class="flex items-center gap-2 text-zinc-700">
                                    <span class="w-1.5 h-1.5 bg-red-600"></span>
                                    <span>Self-Drive or Chauffeur</span>
                                </div>
                                <div class="flex items-center gap-2 text-zinc-700">
                                    <span class="w-1.5 h-1.5 bg-red-600"></span>
                                    <span>Comprehensive Insurance</span>
                                </div>
                                <div class="flex items-center gap-2 text-zinc-700">
                                    <span class="w-1.5 h-1.5 bg-red-600"></span>
                                    <span>Clean & Fresh Sanitized</span>
                                </div>
                                <div class="flex items-center gap-2 text-zinc-700">
                                    <span class="w-1.5 h-1.5 bg-red-600"></span>
                                    <span>Free Airport Drop/Pick Option</span>
                                </div>
                            </div>

                            <button onclick="openBookingModal('Toyota Fortuner 4x4')" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold text-xs uppercase tracking-widest py-3.5 border border-red-600 transition-colors">
                                Reserve This Unit Now
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- Interactive Instant Booking / Search Bar Widget -->
    <section id="search-bar" class="relative -mt-8 z-30 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white border-2 border-red-600 p-6 md:p-8 sharp-shadow">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6 pb-4 border-b border-neutral-200">
                <div>
                    <h2 class="text-xl sm:text-2xl font-black uppercase text-black tracking-tight">
                        Quick Reservation <span class="text-red-600">Finder</span>
                    </h2>
                    <p class="text-xs text-zinc-500">Select your schedule to check vehicle availability and instant quote</p>
                </div>
                <div class="flex items-center gap-2 text-xs font-semibold">
                    <span class="bg-red-50 text-red-700 border border-red-200 px-2.5 py-1 uppercase tracking-wider">Fast Response</span>
                    <span class="text-zinc-400">&bull;</span>
                    <span class="text-zinc-600">No Upfront Payment to Inquire</span>
                </div>
            </div>

            <form id="reservationSearchForm" onsubmit="handleSearch(event)" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                
                <!-- Pick Up Location -->
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-zinc-700 mb-1.5">Pickup Location</label>
                    <select id="pickupLoc" class="w-full bg-neutral-50 border border-neutral-300 px-3.5 py-3 text-sm focus:border-red-600 focus:bg-white focus:outline-none transition-colors">
                        <option value="Main Office">Leo Rent-A-Car Main Office</option>
                        <option value="NAIA Airport">NAIA Airport (Terminals 1-4)</option>
                        <option value="Clark Airport">Clark International Airport</option>
                        <option value="Metro Manila Hotel">Metro Manila Hotel Delivery</option>
                        <option value="Custom Address">Door-to-Door Delivery</option>
                    </select>
                </div>

                <!-- Rental Mode (Self-Drive vs Chauffeur) -->
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-zinc-700 mb-1.5">Rental Mode</label>
                    <select id="rentalMode" class="w-full bg-neutral-50 border border-neutral-300 px-3.5 py-3 text-sm focus:border-red-600 focus:bg-white focus:outline-none transition-colors">
                        <option value="Self-Drive">Self-Drive (I will drive)</option>
                        <option value="With Chauffeur">With Leo Staff Chauffeur (+&#8369;800/day)</option>
                    </select>
                </div>

                <!-- Pick-up Date -->
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-zinc-700 mb-1.5">Start Date</label>
                    <input type="date" id="pickupDate" required class="w-full bg-neutral-50 border border-neutral-300 px-3.5 py-2.5 text-sm focus:border-red-600 focus:bg-white focus:outline-none transition-colors">
                </div>

                <!-- Return Date -->
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-zinc-700 mb-1.5">End Date</label>
                    <input type="date" id="returnDate" required class="w-full bg-neutral-50 border border-neutral-300 px-3.5 py-2.5 text-sm focus:border-red-600 focus:bg-white focus:outline-none transition-colors">
                </div>

                <!-- Search Button -->
                <div class="flex items-end">
                    <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold text-xs uppercase tracking-widest py-3.5 border-2 border-red-600 transition-all flex items-center justify-center gap-2 cursor-pointer shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="square" stroke-linejoin="miter" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <span>Find Available</span>
                    </button>
                </div>

            </form>
        </div>
    </section>

    <!-- Why Choose Leo Rent-A-Car (Sharp Grid Cards) -->
    <section class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <div class="text-center max-w-3xl mx-auto mb-16">
                <div class="inline-block bg-red-50 text-red-700 border border-red-200 px-3 py-1 text-xs font-bold uppercase tracking-widest mb-3">
                    Why Leo Rent-A-Car
                </div>
                <h2 class="text-3xl sm:text-4xl font-black text-black uppercase tracking-tight">
                    ENGINEERED FOR <span class="text-red-600">SAFETY, COMFORT</span> & RELIABILITY
                </h2>
                <p class="text-zinc-600 text-sm sm:text-base mt-3">
                    We maintain our own vehicles to factory specifications with rigorous maintenance schedules. Here is why travelers and corporate clients trust us.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                
                <!-- Feature 1 -->
                <div class="p-6 bg-white border border-neutral-300 hover:border-red-600 sharp-shadow-hover transition-all">
                    <div class="w-12 h-12 bg-red-600 text-white flex items-center justify-center mb-5 font-bold text-lg">
                        01
                    </div>
                    <h3 class="text-lg font-bold uppercase text-black mb-2">Fleet In Top Condition</h3>
                    <p class="text-xs text-zinc-600 leading-relaxed">
                        Every vehicle undergoes strict multi-point safety inspections, brake checks, and deep interior sanitization prior to every turnover.
                    </p>
                </div>

                <!-- Feature 2 -->
                <div class="p-6 bg-white border border-neutral-300 hover:border-red-600 sharp-shadow-hover transition-all">
                    <div class="w-12 h-12 bg-zinc-950 text-white flex items-center justify-center mb-5 font-bold text-lg">
                        02
                    </div>
                    <h3 class="text-lg font-bold uppercase text-black mb-2">Vetted Professional Drivers</h3>
                    <p class="text-xs text-zinc-600 leading-relaxed">
                        Need a chauffeur? Our in-house staff drivers are courteous, background-verified, and familiar with top provincial routes and Metro Manila shortcuts.
                    </p>
                </div>

                <!-- Feature 3 -->
                <div class="p-6 bg-white border border-neutral-300 hover:border-red-600 sharp-shadow-hover transition-all">
                    <div class="w-12 h-12 bg-red-600 text-white flex items-center justify-center mb-5 font-bold text-lg">
                        03
                    </div>
                    <h3 class="text-lg font-bold uppercase text-black mb-2">Transparent Low Rates</h3>
                    <p class="text-xs text-zinc-600 leading-relaxed">
                        No surprise extra fees upon vehicle turnover. What you see is what you pay, with honest security deposit return policies.
                    </p>
                </div>

                <!-- Feature 4 -->
                <div class="p-6 bg-white border border-neutral-300 hover:border-red-600 sharp-shadow-hover transition-all">
                    <div class="w-12 h-12 bg-zinc-950 text-white flex items-center justify-center mb-5 font-bold text-lg">
                        04
                    </div>
                    <h3 class="text-lg font-bold uppercase text-black mb-2">24/7 Road Assistance</h3>
                    <p class="text-xs text-zinc-600 leading-relaxed">
                        Drive with complete peace of mind anywhere in Luzon or adjacent corridors with our active standby mechanic and towing network.
                    </p>
                </div>

            </div>

        </div>
    </section>

    <!-- Fleet Showcase Section -->
    <section id="fleet" class="py-20 bg-neutral-50 border-t border-b border-neutral-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <div class="flex flex-col md:flex-row md:items-end justify-between mb-12 gap-6">
                <div>
                    <span class="inline-block bg-red-600 text-white text-[11px] font-black uppercase tracking-widest px-3 py-1 mb-2">
                        Available Fleet
                    </span>
                    <h2 class="text-3xl sm:text-4xl font-black text-black uppercase tracking-tight">
                        EXPLORE OUR <span class="text-red-600">MODELS</span>
                    </h2>
                    <p class="text-xs sm:text-sm text-zinc-500 mt-1">
                        Select a vehicle to inspect features or request an instant reservation quote.
                    </p>
                </div>

                <!-- Category Filter Tabs -->
                <div class="flex flex-wrap gap-2">
                    <button onclick="filterFleet('all')" class="fleet-tab active px-4 py-2 text-xs font-bold uppercase tracking-wider border-2 border-red-600 bg-red-600 text-white transition-all cursor-pointer">
                        All Units
                    </button>
                    <button onclick="filterFleet('suv')" class="fleet-tab px-4 py-2 text-xs font-bold uppercase tracking-wider border-2 border-zinc-300 bg-white text-zinc-800 hover:border-red-600 hover:text-red-600 transition-all cursor-pointer">
                        SUVs & 4x4
                    </button>
                    <button onclick="filterFleet('van')" class="fleet-tab px-4 py-2 text-xs font-bold uppercase tracking-wider border-2 border-zinc-300 bg-white text-zinc-800 hover:border-red-600 hover:text-red-600 transition-all cursor-pointer">
                        Vans
                    </button>
                    <button onclick="filterFleet('mpv')" class="fleet-tab px-4 py-2 text-xs font-bold uppercase tracking-wider border-2 border-zinc-300 bg-white text-zinc-800 hover:border-red-600 hover:text-red-600 transition-all cursor-pointer">
                        MPVs
                    </button>
                    <button onclick="filterFleet('sedan')" class="fleet-tab px-4 py-2 text-xs font-bold uppercase tracking-wider border-2 border-zinc-300 bg-white text-zinc-800 hover:border-red-600 hover:text-red-600 transition-all cursor-pointer">
                        Sedans
                    </button>
                </div>
            </div>

            <!-- Fleet Cards Grid -->
            <div id="fleetContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach ($fleet as $car): ?>
                <div class="fleet-item bg-white border border-neutral-300 hover:border-red-600 transition-all flex flex-col sharp-shadow-hover" data-category="<?= htmlspecialchars($car['category']) ?>">
                    
                    <!-- Vehicle Photo & Badge -->
                    <div class="relative bg-zinc-100 overflow-hidden h-52 border-b border-neutral-200">
                        <img src="<?= htmlspecialchars($car['image']) ?>" alt="<?= htmlspecialchars($car['name']) ?>" class="w-full h-full object-cover hover:scale-105 transition-transform duration-500">
                        <div class="absolute top-3 left-3 bg-red-600 text-white text-[10px] font-black uppercase tracking-widest px-2.5 py-1">
                            <?= htmlspecialchars($car['badge']) ?>
                        </div>
                        <div class="absolute bottom-2 right-2 bg-zinc-950/80 backdrop-blur-sm text-white text-[11px] px-2 py-0.5 font-mono">
                            <?= htmlspecialchars($car['year']) ?> Model
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="p-6 flex-1 flex flex-col justify-between">
                        <div>
                            <div class="flex justify-between items-start mb-2">
                                <h3 class="text-xl font-bold uppercase text-black"><?= htmlspecialchars($car['name']) ?></h3>
                            </div>
                            <p class="text-xs text-zinc-600 mb-4 line-clamp-2">
                                <?= htmlspecialchars($car['desc']) ?>
                            </p>

                            <!-- Specs Grid -->
                            <div class="grid grid-cols-2 gap-2 text-[11px] bg-neutral-50 p-3 border border-neutral-200 mb-6 font-medium text-zinc-700">
                                <div class="flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="square" stroke-linejoin="miter" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
                                    <span><?= htmlspecialchars($car['transmission']) ?></span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="square" stroke-linejoin="miter" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                    <span><?= htmlspecialchars($car['seats']) ?> Passengers</span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="square" stroke-linejoin="miter" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                    <span><?= htmlspecialchars($car['luggage']) ?></span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="square" stroke-linejoin="miter" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                    <span><?= htmlspecialchars($car['fuel']) ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Price & Reserve Button -->
                        <div class="pt-4 border-t border-neutral-200 flex items-center justify-between gap-4">
                            <div>
                                <span class="text-[10px] text-zinc-500 uppercase block font-semibold">Daily Rate</span>
                                <div class="text-2xl font-black text-red-600">
                                    &#8369;<?= number_format($car['rate']) ?>
                                    <span class="text-xs text-zinc-400 font-normal">/day</span>
                                </div>
                            </div>
                            <button onclick="openBookingModal('<?= htmlspecialchars($car['name']) ?>', <?= $car['rate'] ?>)" class="bg-red-600 hover:bg-red-700 text-white font-bold text-xs uppercase tracking-wider px-5 py-3 border border-red-600 transition-colors cursor-pointer">
                                Book Now
                            </button>
                        </div>
                    </div>

                </div>
                <?php endforeach; ?>
            </div>

            <!-- Custom Quote Banner -->
            <div class="mt-12 bg-white border-2 border-zinc-900 p-8 flex flex-col md:flex-row items-center justify-between gap-6">
                <div>
                    <h3 class="text-xl font-bold uppercase text-black">Need A Weekly, Monthly, or Fleet Package?</h3>
                    <p class="text-xs sm:text-sm text-zinc-600 mt-1">We provide specialized discount corporate leasing and custom chauffeur arrangements for embassies, film shoots, and corporate conventions.</p>
                </div>
                <button onclick="openBookingModal('Long-term Corporate Inquiry')" class="bg-zinc-900 hover:bg-black text-white font-bold text-xs uppercase tracking-widest px-8 py-4 border border-zinc-900 shrink-0">
                    Request Corporate Quote
                </button>
            </div>

        </div>
    </section>

    <!-- Services Section -->
    <section id="services" class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <div class="inline-block bg-red-50 text-red-700 border border-red-200 px-3 py-1 text-xs font-bold uppercase tracking-widest mb-3">
                    Tailored Solutions
                </div>
                <h2 class="text-3xl sm:text-4xl font-black text-black uppercase tracking-tight">
                    FLEXIBLE RENTAL <span class="text-red-600">SERVICES</span>
                </h2>
                <p class="text-zinc-600 text-sm sm:text-base mt-3">
                    Whether you prefer taking the wheel yourself or relaxing while our licensed staff chauffeur navigates, we adapt to your itinerary.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                
                <!-- Service 1: Self-Drive -->
                <div class="border-2 border-neutral-300 hover:border-red-600 p-8 bg-white transition-all flex flex-col justify-between sharp-shadow-hover">
                    <div>
                        <div class="w-10 h-1 bg-red-600 mb-6"></div>
                        <h3 class="text-2xl font-black uppercase text-black mb-3">Self-Drive Rentals</h3>
                        <p class="text-xs sm:text-sm text-zinc-600 leading-relaxed mb-6">
                            Enjoy total independence. Complete digital document submission, straightforward security deposit, and instant keys handover. Drive anywhere across mainland Luzon.
                        </p>
                        <ul class="space-y-2.5 text-xs text-zinc-700 font-medium">
                            <li class="flex items-center gap-2"><span class="w-1.5 h-1.5 bg-red-600"></span> Unlimited mileage options available</li>
                            <li class="flex items-center gap-2"><span class="w-1.5 h-1.5 bg-red-600"></span> 24-hour rental cycle billing</li>
                            <li class="flex items-center gap-2"><span class="w-1.5 h-1.5 bg-red-600"></span> Additional driver endorsements permitted</li>
                        </ul>
                    </div>
                    <div class="pt-8">
                        <button onclick="openBookingModal('Self-Drive Inquiry')" class="w-full text-center border-2 border-zinc-900 hover:border-red-600 hover:text-red-600 text-zinc-900 font-bold text-xs uppercase tracking-wider py-3 transition-colors">
                            Inquire Self-Drive
                        </button>
                    </div>
                </div>

                <!-- Service 2: With Chauffeur Driver -->
                <div class="border-2 border-red-600 p-8 bg-red-50/30 transition-all flex flex-col justify-between relative sharp-shadow-red">
                    <div class="absolute -top-3 right-6 bg-red-600 text-white text-[10px] font-black uppercase tracking-widest px-3 py-1">
                        Recommended
                    </div>
                    <div>
                        <div class="w-10 h-1 bg-red-600 mb-6"></div>
                        <h3 class="text-2xl font-black uppercase text-black mb-3">With Staff Chauffeur</h3>
                        <p class="text-xs sm:text-sm text-zinc-600 leading-relaxed mb-6">
                            Avoid the stress of traffic, parking hassles, and unfamiliar mountain roads. Our professional drivers are punctual, uniformed, and safety certified.
                        </p>
                        <ul class="space-y-2.5 text-xs text-zinc-700 font-medium">
                            <li class="flex items-center gap-2"><span class="w-1.5 h-1.5 bg-red-600"></span> No driver license required from passenger</li>
                            <li class="flex items-center gap-2"><span class="w-1.5 h-1.5 bg-red-600"></span> Zero self-drive liability deposit</li>
                            <li class="flex items-center gap-2"><span class="w-1.5 h-1.5 bg-red-600"></span> Ideal for VIP business executives & family vacations</li>
                        </ul>
                    </div>
                    <div class="pt-8">
                        <button onclick="openBookingModal('Chauffeur Driver Inquiry')" class="w-full text-center bg-red-600 hover:bg-red-700 text-white font-bold text-xs uppercase tracking-wider py-3 transition-colors">
                            Book With Chauffeur
                        </button>
                    </div>
                </div>

                <!-- Service 3: Airport & Point-to-Point Transfers -->
                <div class="border-2 border-neutral-300 hover:border-red-600 p-8 bg-white transition-all flex flex-col justify-between sharp-shadow-hover">
                    <div>
                        <div class="w-10 h-1 bg-red-600 mb-6"></div>
                        <h3 class="text-2xl font-black uppercase text-black mb-3">Airport & City Transfers</h3>
                        <p class="text-xs sm:text-sm text-zinc-600 leading-relaxed mb-6">
                            Direct, guaranteed terminal pick-up and drop-off to and from NAIA, Clark, Batangas Port, or regional business districts with real-time flight tracking.
                        </p>
                        <ul class="space-y-2.5 text-xs text-zinc-700 font-medium">
                            <li class="flex items-center gap-2"><span class="w-1.5 h-1.5 bg-red-600"></span> Meet & greet with nameplate paging</li>
                            <li class="flex items-center gap-2"><span class="w-1.5 h-1.5 bg-red-600"></span> Generous luggage accommodation</li>
                            <li class="flex items-center gap-2"><span class="w-1.5 h-1.5 bg-red-600"></span> Fixed flat-rate quotes (no surge pricing)</li>
                        </ul>
                    </div>
                    <div class="pt-8">
                        <button onclick="openBookingModal('Airport Transfer Inquiry')" class="w-full text-center border-2 border-zinc-900 hover:border-red-600 hover:text-red-600 text-zinc-900 font-bold text-xs uppercase tracking-wider py-3 transition-colors">
                            Reserve Transfer
                        </button>
                    </div>
                </div>

            </div>

        </div>
    </section>

    <!-- Rental Requirements & How It Works -->
    <section id="requirements" class="py-20 bg-neutral-900 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
                
                <!-- Left: How It Works Steps -->
                <div class="lg:col-span-7 space-y-8">
                    <div>
                        <div class="inline-block bg-red-600 text-white text-[11px] font-black uppercase tracking-widest px-3 py-1 mb-3">
                            Step-By-Step Process
                        </div>
                        <h2 class="text-3xl sm:text-4xl font-black uppercase tracking-tight">
                            HOW TO RENT IN <span class="text-red-500">3 SIMPLE STEPS</span>
                        </h2>
                    </div>

                    <div class="space-y-6">
                        <div class="flex gap-4">
                            <div class="w-12 h-12 bg-red-600 text-white font-black text-lg flex items-center justify-center shrink-0 border border-red-500">
                                1
                            </div>
                            <div>
                                <h3 class="text-lg font-bold uppercase text-white">Choose Unit & Submit Schedule</h3>
                                <p class="text-xs text-zinc-400 mt-1 leading-relaxed">
                                    Pick your preferred vehicle model, pickup and return dates, and indicate whether you need self-drive or a dedicated staff chauffeur.
                                </p>
                            </div>
                        </div>

                        <div class="flex gap-4">
                            <div class="w-12 h-12 bg-zinc-800 text-white font-black text-lg flex items-center justify-center shrink-0 border border-zinc-700">
                                2
                            </div>
                            <div>
                                <h3 class="text-lg font-bold uppercase text-white">Fast Document Verification</h3>
                                <p class="text-xs text-zinc-400 mt-1 leading-relaxed">
                                    Send snapshots of your valid Driver's License and secondary ID via online submission or Viber/WhatsApp for swift 15-minute verification.
                                </p>
                            </div>
                        </div>

                        <div class="flex gap-4">
                            <div class="w-12 h-12 bg-red-600 text-white font-black text-lg flex items-center justify-center shrink-0 border border-red-500">
                                3
                            </div>
                            <div>
                                <h3 class="text-lg font-bold uppercase text-white">Turnover & Hit The Road</h3>
                                <p class="text-xs text-zinc-400 mt-1 leading-relaxed">
                                    Inspect the car, sign the digital rental agreement, receive keys, and hit the road with total confidence and 24/7 support.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: Sharp Requirements Card -->
                <div class="lg:col-span-5">
                    <div class="bg-white text-zinc-900 border-2 border-red-600 p-8 sharp-shadow-red">
                        <div class="flex items-center gap-3 border-b-2 border-neutral-200 pb-4 mb-6">
                            <div class="w-3 h-3 bg-red-600"></div>
                            <h3 class="text-xl font-black uppercase text-black">Rental Requirements</h3>
                        </div>

                        <div class="space-y-5 text-xs text-zinc-700">
                            <div>
                                <h4 class="font-bold uppercase text-black flex items-center gap-2 mb-1">
                                    <span class="text-red-600">&bull;</span> For Self-Drive Clients:
                                </h4>
                                <ul class="list-disc pl-5 space-y-1 text-zinc-600">
                                    <li>Valid Professional or Non-Professional Driver's License (Philippine or International)</li>
                                    <li>1 Government-Issued Secondary ID (Passport, UMID, National ID)</li>
                                    <li>Proof of Billing or Hotel/Flight Confirmation</li>
                                    <li>Refundable Security Deposit (Returned upon unit check-in)</li>
                                </ul>
                            </div>

                            <div class="border-t border-neutral-200 pt-4">
                                <h4 class="font-bold uppercase text-black flex items-center gap-2 mb-1">
                                    <span class="text-red-600">&bull;</span> For With-Chauffeur Trips:
                                </h4>
                                <ul class="list-disc pl-5 space-y-1 text-zinc-600">
                                    <li>Any 1 Valid Identification Card</li>
                                    <li>Zero driver's license required from passenger</li>
                                    <li>No large security deposit required</li>
                                </ul>
                            </div>
                        </div>

                        <div class="mt-8 pt-4 border-t border-neutral-200">
                            <button onclick="openBookingModal('Requirements Verification Inquiry')" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold text-xs uppercase tracking-widest py-3.5 border border-red-600 transition-colors">
                                Inquire & Pre-Verify Docs
                            </button>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </section>

    <!-- Testimonials / Client Trust -->
    <section class="py-20 bg-white border-b border-neutral-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-2xl mx-auto mb-16">
                <div class="inline-block bg-red-50 text-red-700 border border-red-200 px-3 py-1 text-xs font-bold uppercase tracking-widest mb-3">
                    Client Feedback
                </div>
                <h2 class="text-3xl sm:text-4xl font-black text-black uppercase tracking-tight">
                    TRUSTED BY <span class="text-red-600">HUNDREDS</span> OF DRIVERS
                </h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Review 1 -->
                <div class="border border-neutral-300 p-6 bg-white sharp-shadow-hover transition-all">
                    <div class="flex text-red-600 text-sm mb-3">&#9733;&#9733;&#9733;&#9733;&#9733;</div>
                    <p class="text-xs text-zinc-600 italic mb-4">
                        "Rented the Toyota Fortuner for our family trip to Baguio. The car was spotless, tires had deep tread, and pickup at NAIA Terminal 3 was flawless. Best rental experience in Manila!"
                    </p>
                    <div class="border-t border-neutral-200 pt-3 flex items-center justify-between">
                        <div>
                            <span class="text-xs font-bold uppercase text-black block">Marc R.</span>
                            <span class="text-[10px] text-zinc-400">Balikbayan from California</span>
                        </div>
                        <span class="text-[10px] font-mono bg-zinc-100 text-zinc-700 px-2 py-0.5">Verified</span>
                    </div>
                </div>

                <!-- Review 2 -->
                <div class="border border-neutral-300 p-6 bg-white sharp-shadow-hover transition-all">
                    <div class="flex text-red-600 text-sm mb-3">&#9733;&#9733;&#9733;&#9733;&#9733;</div>
                    <p class="text-xs text-zinc-600 italic mb-4">
                        "We booked the HiAce Super Grandia with a staff driver for our corporate executives during an ASEAN expo. Driver was on time, courteous, and drove with maximum caution."
                    </p>
                    <div class="border-t border-neutral-200 pt-3 flex items-center justify-between">
                        <div>
                            <span class="text-xs font-bold uppercase text-black block">Janice T.</span>
                            <span class="text-[10px] text-zinc-400">Corporate Events Director</span>
                        </div>
                        <span class="text-[10px] font-mono bg-zinc-100 text-zinc-700 px-2 py-0.5">Verified</span>
                    </div>
                </div>

                <!-- Review 3 -->
                <div class="border border-neutral-300 p-6 bg-white sharp-shadow-hover transition-all">
                    <div class="flex text-red-600 text-sm mb-3">&#9733;&#9733;&#9733;&#9733;&#9733;</div>
                    <p class="text-xs text-zinc-600 italic mb-4">
                        "Smooth transaction from inquiry to vehicle return. The security deposit was returned within the promised 24 hours. Leo Rent-A-Car is now my go-to for all business trips."
                    </p>
                    <div class="border-t border-neutral-200 pt-3 flex items-center justify-between">
                        <div>
                            <span class="text-xs font-bold uppercase text-black block">Carlos M.</span>
                            <span class="text-[10px] text-zinc-400">Regional Sales Executive</span>
                        </div>
                        <span class="text-[10px] font-mono bg-zinc-100 text-zinc-700 px-2 py-0.5">Verified</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Frequently Asked Questions -->
    <section id="faq" class="py-20 bg-neutral-50 border-b border-neutral-200">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <div class="inline-block bg-red-50 text-red-700 border border-red-200 px-3 py-1 text-xs font-bold uppercase tracking-widest mb-3">
                    Clarifications
                </div>
                <h2 class="text-3xl font-black text-black uppercase tracking-tight">
                    FREQUENTLY ASKED <span class="text-red-600">QUESTIONS</span>
                </h2>
            </div>

            <div class="space-y-4">
                <!-- FAQ 1 -->
                <div class="bg-white border border-neutral-300 p-5">
                    <h3 class="font-bold text-sm uppercase text-black flex items-center gap-2">
                        <span class="text-red-600">Q:</span> What is your fuel policy?
                    </h3>
                    <p class="text-xs text-zinc-600 mt-2 pl-5 leading-relaxed">
                        We operate on a transparent Full-to-Full policy. You receive the vehicle with a full tank of fuel and simply return it full. No inflated pump surcharge calculations.
                    </p>
                </div>

                <!-- FAQ 2 -->
                <div class="bg-white border border-neutral-300 p-5">
                    <h3 class="font-bold text-sm uppercase text-black flex items-center gap-2">
                        <span class="text-red-600">Q:</span> When is the security deposit returned?
                    </h3>
                    <p class="text-xs text-zinc-600 mt-2 pl-5 leading-relaxed">
                        For self-drive rentals, security deposits are refunded via GCash, Bank Transfer, or Cash upon unit turnover and verification that no toll fee violations or damages occurred.
                    </p>
                </div>

                <!-- FAQ 3 -->
                <div class="bg-white border border-neutral-300 p-5">
                    <h3 class="font-bold text-sm uppercase text-black flex items-center gap-2">
                        <span class="text-red-600">Q:</span> Can someone else drive the vehicle besides the person booking?
                    </h3>
                    <p class="text-xs text-zinc-600 mt-2 pl-5 leading-relaxed">
                        Yes! In our system, additional drivers can be listed as authorized operators so long as their valid driver's licenses are registered in the contract beforehand for insurance compliance.
                    </p>
                </div>

                <!-- FAQ 4 -->
                <div class="bg-white border border-neutral-300 p-5">
                    <h3 class="font-bold text-sm uppercase text-black flex items-center gap-2">
                        <span class="text-red-600">Q:</span> Can I hire a staff chauffeur for provincial trips?
                    </h3>
                    <p class="text-xs text-zinc-600 mt-2 pl-5 leading-relaxed">
                        Yes, our dedicated company chauffeurs are experienced with long-distance provincial routes (Tagaytay, Baguio, La Union, Bicol, Batangas, Subic). Chauffeur daily fee is &#8369;800/day plus meals and accommodation if overnight.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact & Direct Location Section -->
    <section id="contact" class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-12">
                
                <!-- Contact Info & Hours -->
                <div class="lg:col-span-5 space-y-6">
                    <div class="inline-block bg-red-50 text-red-700 border border-red-200 px-3 py-1 text-xs font-bold uppercase tracking-widest">
                        Get In Touch
                    </div>
                    <h2 class="text-3xl font-black text-black uppercase tracking-tight">
                        READY TO BOOK YOUR <span class="text-red-600">NEXT TRIP?</span>
                    </h2>
                    <p class="text-xs sm:text-sm text-zinc-600 leading-relaxed">
                        Our reservation specialists are available 24/7 to answer fleet queries, prepare customized quotations, and arrange fast terminal delivery.
                    </p>

                    <div class="space-y-4 pt-4 border-t border-neutral-200 text-xs">
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 bg-red-600 text-white flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/></svg>
                            </div>
                            <div>
                                <strong class="text-black uppercase block">Main Dispatch Office:</strong>
                                <span class="text-zinc-600">Leo Rent-A-Car Hub, Metro Manila, Philippines</span>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 bg-zinc-950 text-white flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 4V3z"/></svg>
                            </div>
                            <div>
                                <strong class="text-black uppercase block">Direct Hotlines & Viber:</strong>
                                <span class="text-zinc-600 font-mono">+63 (917) 555-LEOS / +63 (2) 8888-0000</span>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 bg-red-600 text-white flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/><path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/></svg>
                            </div>
                            <div>
                                <strong class="text-black uppercase block">Email Inquiries:</strong>
                                <span class="text-zinc-600 font-mono">reservations@leorentacar.com</span>
                            </div>
                        </div>
                    </div>

                    <div class="p-4 bg-zinc-100 border border-zinc-200 text-xs">
                        <strong class="text-black block mb-1 uppercase">Operating Hours:</strong>
                        <span class="text-zinc-600 block">&bull; Emergency Dispatch & Roadside: 24 Hours / 7 Days</span>
                        <span class="text-zinc-600 block">&bull; Office Customer Service: 7:00 AM &ndash; 9:00 PM Daily</span>
                    </div>
                </div>

                <!-- Direct Message / Inquiry Form -->
                <div class="lg:col-span-7">
                    <div class="border-2 border-zinc-900 p-8 bg-white sharp-shadow">
                        <div class="flex items-center justify-between border-b border-neutral-200 pb-4 mb-6">
                            <h3 class="text-xl font-black uppercase text-black">Send A Fast Message</h3>
                            <span class="text-[11px] font-bold text-red-600 uppercase">Average Reply: &lt; 15 Mins</span>
                        </div>

                        <form id="contactForm" onsubmit="handleContact(event)" class="space-y-4">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[11px] font-bold uppercase tracking-wider text-zinc-700 mb-1">Full Name</label>
                                    <input type="text" required placeholder="Juan Dela Cruz" class="w-full bg-neutral-50 border border-neutral-300 px-3.5 py-2.5 text-sm focus:border-red-600 focus:bg-white focus:outline-none">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold uppercase tracking-wider text-zinc-700 mb-1">Mobile / WhatsApp / Viber</label>
                                    <input type="tel" required placeholder="+63 917 000 0000" class="w-full bg-neutral-50 border border-neutral-300 px-3.5 py-2.5 text-sm focus:border-red-600 focus:bg-white focus:outline-none">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[11px] font-bold uppercase tracking-wider text-zinc-700 mb-1">Email Address</label>
                                    <input type="email" placeholder="name@example.com" class="w-full bg-neutral-50 border border-neutral-300 px-3.5 py-2.5 text-sm focus:border-red-600 focus:bg-white focus:outline-none">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold uppercase tracking-wider text-zinc-700 mb-1">Preferred Vehicle</label>
                                    <select class="w-full bg-neutral-50 border border-neutral-300 px-3.5 py-2.5 text-sm focus:border-red-600 focus:bg-white focus:outline-none">
                                        <option value="Any SUV">Toyota Fortuner / Ford Everest (SUV)</option>
                                        <option value="Any Van">Toyota HiAce Super Grandia / Commuter (Van)</option>
                                        <option value="Any MPV">Toyota Innova (MPV)</option>
                                        <option value="Any Sedan">Toyota Vios (Sedan)</option>
                                        <option value="Other">Corporate Fleet / Other Needs</option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label class="block text-[11px] font-bold uppercase tracking-wider text-zinc-700 mb-1">Trip Details / Special Requests</label>
                                <textarea rows="3" placeholder="Target dates, destination, whether self-drive or chauffeur needed..." class="w-full bg-neutral-50 border border-neutral-300 px-3.5 py-2 text-sm focus:border-red-600 focus:bg-white focus:outline-none"></textarea>
                            </div>

                            <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold text-xs uppercase tracking-widest py-4 border-2 border-red-600 transition-all flex items-center justify-center gap-2 cursor-pointer">
                                <span>Submit Fast Inquiry</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="square" stroke-linejoin="miter" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                            </button>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-zinc-950 text-white border-t-4 border-red-600 pt-16 pb-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-10 pb-12 border-b border-zinc-800">
                
                <!-- Brand Info -->
                <div class="lg:col-span-2 space-y-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 border-2 border-red-600 bg-white p-0.5 overflow-hidden">
                            <img src="public/assets/images/leo-rent-a-car.jpg" alt="Leo Rent-A-Car" class="w-full h-full object-cover">
                        </div>
                        <span class="text-xl font-black uppercase tracking-tight text-white">
                            LEO <span class="text-red-500">RENT-A-CAR</span>
                        </span>
                    </div>
                    <p class="text-xs text-zinc-400 max-w-sm leading-relaxed">
                        Your trusted car rental partner in the Philippines. Providing premium self-drive vehicles, professional chauffeur services, and airport drop-offs with sharp precision and competitive rates.
                    </p>
                    <div class="text-[11px] text-zinc-500 pt-2 font-mono">
                        DTI Registered &bull; Insured Fleet &bull; 24/7 Operations
                    </div>
                </div>

                <!-- Navigation Links -->
                <div>
                    <h4 class="text-xs font-bold uppercase tracking-widest text-white mb-4 border-b border-red-600 pb-1 inline-block">Quick Links</h4>
                    <ul class="space-y-2 text-xs text-zinc-400">
                        <li><a href="#fleet" class="hover:text-red-400 transition-colors">Our Fleet</a></li>
                        <li><a href="#search-bar" class="hover:text-red-400 transition-colors">Reserve Online</a></li>
                        <li><a href="#services" class="hover:text-red-400 transition-colors">Self-Drive & Chauffeur</a></li>
                        <li><a href="#requirements" class="hover:text-red-400 transition-colors">Rental Requirements</a></li>
                        <li><a href="#faq" class="hover:text-red-400 transition-colors">FAQs</a></li>
                    </ul>
                </div>

                <!-- Popular Fleet Categories -->
                <div>
                    <h4 class="text-xs font-bold uppercase tracking-widest text-white mb-4 border-b border-red-600 pb-1 inline-block">Fleet Categories</h4>
                    <ul class="space-y-2 text-xs text-zinc-400">
                        <li><a href="#fleet" class="hover:text-red-400 transition-colors">7-Seater SUVs</a></li>
                        <li><a href="#fleet" class="hover:text-red-400 transition-colors">Executive HiAce Vans</a></li>
                        <li><a href="#fleet" class="hover:text-red-400 transition-colors">Family MPVs</a></li>
                        <li><a href="#fleet" class="hover:text-red-400 transition-colors">Fuel-Efficient Sedans</a></li>
                        <li><a href="#fleet" class="hover:text-red-400 transition-colors">Long-term Leasing</a></li>
                    </ul>
                </div>

                <!-- Management & Staff Access -->
                <div>
                    <h4 class="text-xs font-bold uppercase tracking-widest text-white mb-4 border-b border-red-600 pb-1 inline-block">System Access</h4>
                    <p class="text-xs text-zinc-400 mb-3 leading-relaxed">
                        Authorized staff and managers can access the internal rental management portal below.
                    </p>
                    <a href="public/index.php" class="inline-block bg-zinc-900 hover:bg-red-600 text-white font-bold text-xs uppercase tracking-wider px-4 py-2.5 border border-zinc-700 transition-colors">
                        Staff Portal &rarr;
                    </a>
                </div>

            </div>

            <!-- Bottom Copyright -->
            <div class="pt-8 flex flex-col sm:flex-row justify-between items-center gap-4 text-xs text-zinc-500">
                <div>
                    &copy; <?= date('Y') ?> Leo Rent-A-Car System. All rights reserved.
                </div>
                <div class="flex items-center gap-6">
                    <span class="hover:text-white cursor-pointer">Terms & Conditions</span>
                    <span>&bull;</span>
                    <span class="hover:text-white cursor-pointer">Privacy Policy</span>
                    <span>&bull;</span>
                    <span class="hover:text-white cursor-pointer">Rental Agreement Guidelines</span>
                </div>
            </div>
        </div>
    </footer>

    <!-- Interactive Booking Modal (Razor-sharp corners) -->
    <div id="bookingModal" class="fixed inset-0 z-50 hidden bg-black/75 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white w-full max-w-lg border-2 border-red-600 p-6 sm:p-8 sharp-shadow relative">
            
            <button onclick="closeBookingModal()" class="absolute top-4 right-4 text-zinc-400 hover:text-red-600 font-mono text-xl focus:outline-none">
                &times;
            </button>

            <div class="mb-5 pb-3 border-b border-neutral-200">
                <span class="text-[10px] bg-red-50 text-red-700 border border-red-200 px-2 py-0.5 font-bold uppercase tracking-wider">Fast Booking</span>
                <h3 id="modalTitle" class="text-xl font-black uppercase text-black mt-1">Book A Vehicle</h3>
                <p class="text-xs text-zinc-500">Submit your schedule and our dispatcher will confirm your slot immediately.</p>
            </div>

            <form onsubmit="handleModalSubmit(event)" class="space-y-4">
                <input type="hidden" id="modalVehicleField" value="">

                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-zinc-700 mb-1">Selected Vehicle / Service</label>
                    <input type="text" id="modalVehicleDisplay" readonly class="w-full bg-zinc-100 border border-neutral-300 px-3.5 py-2 text-sm font-semibold text-zinc-900 cursor-not-allowed">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-zinc-700 mb-1">Your Full Name</label>
                        <input type="text" required placeholder="Juan Dela Cruz" class="w-full bg-neutral-50 border border-neutral-300 px-3 py-2 text-sm focus:border-red-600 focus:bg-white focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-zinc-700 mb-1">Mobile / Viber</label>
                        <input type="tel" required placeholder="+63 917 000 0000" class="w-full bg-neutral-50 border border-neutral-300 px-3 py-2 text-sm focus:border-red-600 focus:bg-white focus:outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-zinc-700 mb-1">Pickup Date</label>
                        <input type="date" required id="modalPickupDate" class="w-full bg-neutral-50 border border-neutral-300 px-3 py-2 text-sm focus:border-red-600 focus:bg-white focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-zinc-700 mb-1">Return Date</label>
                        <input type="date" required id="modalReturnDate" class="w-full bg-neutral-50 border border-neutral-300 px-3 py-2 text-sm focus:border-red-600 focus:bg-white focus:outline-none">
                    </div>
                </div>

                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-zinc-700 mb-1">Driver Preference</label>
                    <select class="w-full bg-neutral-50 border border-neutral-300 px-3 py-2 text-sm focus:border-red-600 focus:bg-white focus:outline-none">
                        <option value="Self-Drive">Self-Drive (I will provide driver license)</option>
                        <option value="With Chauffeur">With Dedicated Staff Chauffeur (+&#8369;800/day)</option>
                    </select>
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold text-xs uppercase tracking-widest py-3.5 border-2 border-red-600 transition-all flex items-center justify-center gap-2 cursor-pointer">
                        <span>Confirm & Send Reservation</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="square" stroke-linejoin="miter" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Feedback Toast -->
    <div id="toast" class="fixed bottom-6 right-6 z-50 hidden bg-zinc-950 text-white border-l-4 border-red-600 p-4 shadow-xl max-w-sm">
        <div class="flex items-center gap-3">
            <div class="w-2 h-2 bg-red-600"></div>
            <div>
                <strong id="toastTitle" class="block font-bold text-xs uppercase">Notification</strong>
                <span id="toastMsg" class="text-xs text-zinc-400">Action recorded.</span>
            </div>
        </div>
    </div>

    <!-- Interactive Client Scripts -->
    <script>
        // Set default minimum dates to today
        const today = new Date().toISOString().split('T')[0];
        const tomorrow = new Date(Date.now() + 86400000).toISOString().split('T')[0];

        if (document.getElementById('pickupDate')) {
            document.getElementById('pickupDate').value = today;
            document.getElementById('pickupDate').min = today;
        }
        if (document.getElementById('returnDate')) {
            document.getElementById('returnDate').value = tomorrow;
            document.getElementById('returnDate').min = today;
        }
        if (document.getElementById('modalPickupDate')) {
            document.getElementById('modalPickupDate').value = today;
            document.getElementById('modalPickupDate').min = today;
        }
        if (document.getElementById('modalReturnDate')) {
            document.getElementById('modalReturnDate').value = tomorrow;
            document.getElementById('modalReturnDate').min = today;
        }

        // Mobile menu toggle
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const mobileMenu = document.getElementById('mobileMenu');
        if (mobileMenuBtn && mobileMenu) {
            mobileMenuBtn.addEventListener('click', () => {
                mobileMenu.classList.toggle('hidden');
            });
        }

        // Fleet Filtering
        function filterFleet(category) {
            const buttons = document.querySelectorAll('.fleet-tab');
            buttons.forEach(btn => {
                btn.classList.remove('bg-red-600', 'text-white', 'border-red-600');
                btn.classList.add('bg-white', 'text-zinc-800', 'border-zinc-300');
            });
            event.target.classList.add('bg-red-600', 'text-white', 'border-red-600');
            event.target.classList.remove('bg-white', 'text-zinc-800', 'border-zinc-300');

            const items = document.querySelectorAll('.fleet-item');
            items.forEach(item => {
                if (category === 'all' || item.dataset.category === category) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        }

        // Search Form Handling
        function handleSearch(e) {
            e.preventDefault();
            const loc = document.getElementById('pickupLoc').value;
            const mode = document.getElementById('rentalMode').value;
            const start = document.getElementById('pickupDate').value;
            const end = document.getElementById('returnDate').value;

            // Smooth scroll to fleet
            const fleetSec = document.getElementById('fleet');
            if (fleetSec) {
                fleetSec.scrollIntoView({ behavior: 'smooth' });
            }

            showToast('Search Completed', `Showing available vehicles for ${mode} from ${start} to ${end}.`);
        }

        // Booking Modal Management
        function openBookingModal(vehicleName = 'General Inquiry', rate = null) {
            const modal = document.getElementById('bookingModal');
            const display = document.getElementById('modalVehicleDisplay');
            const field = document.getElementById('modalVehicleField');
            const title = document.getElementById('modalTitle');

            if (display && field) {
                display.value = rate ? `${vehicleName} (₱${rate.toLocaleString()}/day)` : vehicleName;
                field.value = vehicleName;
            }
            if (title) {
                title.innerText = vehicleName === 'General Inquiry' ? 'Book A Vehicle' : `Reserve: ${vehicleName}`;
            }
            if (modal) {
                modal.classList.remove('hidden');
            }
        }

        function closeBookingModal() {
            const modal = document.getElementById('bookingModal');
            if (modal) {
                modal.classList.add('hidden');
            }
        }

        function handleModalSubmit(e) {
            e.preventDefault();
            closeBookingModal();
            showToast('Reservation Requested!', 'Thank you! Our dispatcher will contact you within 15 minutes.');
        }

        function handleContact(e) {
            e.preventDefault();
            e.target.reset();
            showToast('Message Sent!', 'Our reservation team received your inquiry and will contact you promptly.');
        }

        function showToast(title, msg) {
            const toast = document.getElementById('toast');
            const tTitle = document.getElementById('toastTitle');
            const tMsg = document.getElementById('toastMsg');
            if (toast && tTitle && tMsg) {
                tTitle.innerText = title;
                tMsg.innerText = msg;
                toast.classList.remove('hidden');
                setTimeout(() => {
                    toast.classList.add('hidden');
                }, 4000);
            }
        }

        // Close modal when clicking outside
        window.addEventListener('click', (e) => {
            const modal = document.getElementById('bookingModal');
            if (e.target === modal) {
                closeBookingModal();
            }
        });
    </script>
</body>
</html>

