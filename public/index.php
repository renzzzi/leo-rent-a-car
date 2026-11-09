<?php

// Main Application Entry Point (Front Controller)
session_start();

// Register PSR-4 Autoloader for the App\ namespace
spl_autoload_register(function (string $class) {
    $prefix  = 'App\\';
    $baseDir = __DIR__ . '/../src/';
    $len     = strlen($prefix);

    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file          = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

// Handle logout first (before auth gate so session is destroyed cleanly)
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header('Location: ../index.php');
    exit;
}

// --- Authentication Gate ---
// If not logged in, redirect to the login landing page
if (empty($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

// If no ?view= param, default to bookings
$pageToLoad = $_GET['view'] ?? null;

if ($pageToLoad === null) {
    header('Location: index.php?view=bookings');
    exit;
}

$folderName = $pageToLoad;

// Load Global Layout Header
require_once __DIR__ . '/../views/layouts/header.php';

$pageToShow = __DIR__ . "/../views/{$folderName}/{$pageToLoad}_view.php";

// Load the requested view
if (file_exists($pageToShow)) {
    require_once $pageToShow;
} else {
    echo '<div class="max-w-4xl mx-auto my-12 p-8 bg-white border border-slate-200 rounded-[4px] text-center shadow-sm">
            <h3 class="text-base font-bold text-slate-900 mb-2">Module Under Construction</h3>
            <p class="text-sm text-slate-500">The <code>' . htmlspecialchars($pageToLoad) . '</code> view has not been initialized yet.</p>
            <a href="index.php?view=bookings" class="inline-block mt-4 px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-xs font-bold rounded-[4px] transition-colors">Return to Bookings</a>
          </div>';
}

// Load Global Layout Footer
require_once __DIR__ . '/../views/layouts/footer.php';
