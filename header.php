<?php
// Include authentication helpers so navigation can react to user role.
require_once __DIR__ . '/auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Basic metadata for browser rendering and mobile responsiveness. -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LegalEase | Premium Lawyer Appointment & Consultation Portal</title>
    <meta name="description" content="LegalEase connects you with expert lawyers instantly. Browse profiles, check specializations, and book legal consultations online through our secure portal.">
    <meta name="keywords" content="lawyer portal, book lawyer online, legal consultation, find layers, legal advice, appointment booking">
    <link rel="icon" href="favicon.ico" type="image/x-icon">
    <!-- Use Bootstrap for quick, clean styling. -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Load custom project styles. -->
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Main navigation bar for all public and private pages. -->
    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-2"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                LegalEase
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navMenu">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="services.php">Services</a></li>
                    <li class="nav-item"><a class="nav-link" href="search.php">Find Lawyers</a></li>
                    <li class="nav-item"><a class="nav-link" href="about.php">About</a></li>
                    <li class="nav-item"><a class="nav-link" href="contact.php">Contact</a></li>
                </ul>
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center">
                    <?php if (is_logged_in()): ?>
                        <?php if (has_role('customer')): ?>
                            <li class="nav-item"><a class="nav-link" href="customer_dashboard.php">Dashboard</a></li>
                        <?php endif; ?>
                        <?php if (has_role('lawyer')): ?>
                            <li class="nav-item"><a class="nav-link" href="lawyer_dashboard.php">Dashboard</a></li>
                        <?php endif; ?>
                        <?php if (has_role('admin')): ?>
                            <li class="nav-item"><a class="nav-link" href="admin_dashboard.php">Admin</a></li>
                        <?php endif; ?>
                        <li class="nav-item ms-lg-3"><a class="btn btn-primary" href="logout.php">Logout</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
                        <li class="nav-item ms-lg-3"><a class="btn btn-primary" href="register.php">Get Started</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    <!-- Main page wrapper for consistent spacing across screens. -->
    <main class="container py-4">
