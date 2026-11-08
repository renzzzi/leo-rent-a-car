<?php

// Main Application Entry Point (Front Controller)

session_start();

// Load Global Layout Header
require_once __DIR__ . "/../views/layouts/header.php";


$pageToLoad = $_GET["view"] ?? "inventory";
$folderName = $pageToLoad; 

$pageToShow = __DIR__ . "/../views/{$folderName}/{$pageToLoad}_view.php";

// Load the requested view
require_once $pageToShow;

// Load Global Layout Footer
require_once __DIR__ . "/../views/layouts/footer.php";