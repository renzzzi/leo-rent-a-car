<?php

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leo Rent-A-Car System</title>

    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/universal-style.css">
</head>

<body class="bg-light">

    <!-- Navigation Bar -->
    <header class="sticky-top shadow-sm">
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
            <div class="container">

                <!-- Brand -->
                <a class="navbar-brand fw-bold" href="index.php">
                    Leo Rent-A-Car
                </a>

                <!-- Mobile Navbar Toggler -->
                <button type="button"
                        class="navbar-toggler"
                        data-bs-toggle="collapse"
                        data-bs-target="#navbarNav"
                        aria-controls="navbarNav"
                        aria-expanded="false"
                        aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <!-- Navigation Links -->
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                         <li class="nav-item">
                            <a class="nav-link <?= (empty($_GET['view']) || $_GET['view'] === 'bookings') ? 'active fw-bold' : '' ?>" href="index.php?view=bookings">
                                Bookings
                            </a>
                        </li>
                    </ul>
                </div>

            </div>
        </nav>
    </header>

    <!-- Insert Main Content Here (Assembled in /public/index.php)-->
    <main>