<?php
/**
 * Master Header Component
 * Graduate Predicting Analysis System
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helper.php';

$user = getCurrentUser();
$pageTitle = $pageTitle ?? 'Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($pageTitle) ?> | Graduate Predicting Analysis System</title>
    
    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    
    <!-- Chart.js 4.4 -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
    
    <!-- Custom Application CSS -->
    <link href="<?= baseUrl('assets/css/style.css') ?>" rel="stylesheet">
</head>
<body>

<div class="app-wrapper">
    <!-- Include Navigation Sidebar -->
    <?php include __DIR__ . '/sidebar.php'; ?>

    <main class="app-main">
        <!-- Top Navigation Bar -->
        <header class="app-topbar">
            <div class="d-flex align-items-center gap-3">
                <button type="button" class="sidebar-toggler" aria-label="Toggle Navigation">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="d-none d-sm-block">
                    <h5 class="mb-0 fw-bold text-dark"><?= sanitize($pageTitle) ?></h5>
                    <small class="text-muted"><i class="far fa-calendar-alt me-1"></i> <?= date('l, d F Y') ?></small>
                </div>
            </div>

            <div class="d-flex align-items-center gap-3">
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 rounded-pill font-monospace fw-semibold text-capitalize">
                    <i class="fas fa-shield-halved me-1"></i> <?= sanitize($user['role'] ?? 'guest') ?>
                </span>
                
                <a href="<?= baseUrl('logout.php') ?>" class="btn btn-sm btn-outline-danger d-none d-md-inline-flex align-items-center gap-2 rounded-pill px-3">
                    <i class="fas fa-arrow-right-from-bracket"></i> Logout
                </a>
            </div>
        </header>

        <!-- Main Workspace Container -->
        <div class="app-content">
            <?php include __DIR__ . '/flash.php'; ?>
