<?php
require_once __DIR__ . '/auth.php';
require_login();
$user = current_user();

// Fetch low stock count for sidebar notification badge
$low_stock_count = 0;
try {
    $pdo = get_db_connection();
    $stmt = $pdo->query("SELECT COUNT(*) FROM products WHERE quantity <= min_threshold");
    $low_stock_count = (int)$stmt->fetchColumn();
} catch (Exception $e) {
    // Graceful fallback if database connection fails
}

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? htmlspecialchars($page_title) . ' | ' : '' ?>TechStock Computer Shop Inventory</title>
    <link rel="icon" type="image/png" href="assets/images/logo_icon_badge.png">
    <!-- Bootstrap 5 CSS & Icons CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Custom Style -->
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- 3D Galaxy Engine -->
    <script src="assets/js/galaxy.js" defer></script>
</head>
<body>

<!-- 3D Galaxy Stars Background Canvas -->
<canvas id="galaxyStarsCanvas"></canvas>

<div class="app-wrapper">
    <!-- Sidebar Navigation -->
    <aside class="sidebar">
        <a href="index.php" class="sidebar-brand">
            <img src="assets/images/logo_icon_transparent.png" alt="TechStock Logo" class="sidebar-brand-icon" onerror="this.onerror=null; this.src='assets/images/logo_icon_badge.png';">
            <div>
                <div class="fw-bold text-white lh-1">TECHSTOCK</div>
                <div class="small text-secondary" style="font-size: 0.68rem; letter-spacing: 0.05em;">INVENTORY SYSTEM</div>
            </div>
        </a>

        <div class="sidebar-nav">
            <div class="sidebar-section-title">Core Modules</div>
            <a href="index.php" class="nav-link-custom <?= $current_page === 'index.php' ? 'active' : '' ?>">
                <i class="bi bi-grid-1x2-fill"></i>
                <span>Dashboard</span>
            </a>
            <a href="products.php" class="nav-link-custom <?= $current_page === 'products.php' ? 'active' : '' ?>">
                <i class="bi bi-cpu-fill"></i>
                <span>Hardware Catalog</span>
            </a>
            <a href="categories.php" class="nav-link-custom <?= $current_page === 'categories.php' ? 'active' : '' ?>">
                <i class="bi bi-tags-fill"></i>
                <span>Categories</span>
            </a>

            <div class="sidebar-section-title">Stock Movements</div>
            <a href="stock_in.php" class="nav-link-custom <?= $current_page === 'stock_in.php' ? 'active' : '' ?>">
                <i class="bi bi-box-arrow-in-down"></i>
                <span>Stock-In (Restock)</span>
            </a>
            <a href="stock_out.php" class="nav-link-custom <?= $current_page === 'stock_out.php' ? 'active' : '' ?>">
                <i class="bi bi-box-arrow-up-right"></i>
                <span>Stock-Out (Sales)</span>
            </a>
            <a href="low_stock.php" class="nav-link-custom <?= $current_page === 'low_stock.php' ? 'active' : '' ?>">
                <i class="bi bi-exclamation-triangle-fill text-warning"></i>
                <span>Low-Stock Alerts</span>
                <?php if ($low_stock_count > 0): ?>
                    <span class="badge bg-danger rounded-pill ms-auto px-2"><?= $low_stock_count ?></span>
                <?php endif; ?>
            </a>

            <div class="sidebar-section-title">Reports & Logs</div>
            <a href="transactions.php" class="nav-link-custom <?= $current_page === 'transactions.php' ? 'active' : '' ?>">
                <i class="bi bi-journal-text"></i>
                <span>Transaction History</span>
            </a>

            <?php if (has_role('admin')): ?>
            <div class="sidebar-section-title">Administration</div>
            <a href="users.php" class="nav-link-custom <?= $current_page === 'users.php' ? 'active' : '' ?>">
                <i class="bi bi-people-fill"></i>
                <span>User Management</span>
            </a>
            <?php endif; ?>
        </div>

        <div class="p-3 border-top border-secondary border-opacity-25 small text-secondary">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="d-block text-white fw-medium"><?= htmlspecialchars($user['full_name']) ?></span>
                    <span class="badge-role <?= htmlspecialchars($user['role']) ?>"><?= htmlspecialchars($user['role']) ?></span>
                </div>
                <a href="logout.php" class="btn btn-outline-danger btn-sm p-1 px-2" title="Logout">
                    <i class="bi bi-box-arrow-right"></i>
                </a>
            </div>
        </div>
    </aside>

    <!-- Main Workspace Container -->
    <div class="main-content">
        <!-- Sticky Top Navigation Bar -->
        <header class="top-navbar">
            <div class="d-flex align-items-center gap-3">
                <span class="text-secondary small d-none d-md-inline">Prepared by: <strong>Bryan Cahayag Cole</strong></span>
            </div>

            <div class="d-flex align-items-center gap-3">
                <?php if (!has_role('auditor')): ?>
                <a href="stock_in.php" class="btn btn-sm btn-cyber-success d-none d-sm-inline-flex align-items-center gap-1">
                    <i class="bi bi-plus-circle"></i> Quick Restock
                </a>
                <a href="stock_out.php" class="btn btn-sm btn-cyber-primary d-none d-sm-inline-flex align-items-center gap-1">
                    <i class="bi bi-cart-dash"></i> Record Sale
                </a>
                <?php endif; ?>

                <a href="demo.html" target="_blank" class="btn btn-sm btn-outline-dark" title="Launch Standalone Client-Side Preview">
                    <i class="bi bi-window-fullscreen me-1"></i> Standalone Demo
                </a>

                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-dark dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle me-1"></i> <?= htmlspecialchars($user['username']) ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border">
                        <li><h6 class="dropdown-header">Signed in as <?= htmlspecialchars($user['role']) ?></h6></li>
                        <li><a class="dropdown-item" href="low_stock.php"><i class="bi bi-bell me-2"></i> Low Stock Alerts (<?= $low_stock_count ?>)</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i> Sign Out</a></li>
                    </ul>
                </div>
            </div>
        </header>

        <!-- Flash Messages Display -->
        <?php $flash = get_flash(); if ($flash): ?>
        <div class="px-4 pt-3 no-print">
            <div class="alert alert-<?= htmlspecialchars($flash['type']) ?> alert-dismissible fade show" role="alert">
                <i class="bi bi-info-circle-fill me-2"></i> <?= htmlspecialchars($flash['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
        <?php endif; ?>

        <!-- Page View Body starts here -->
        <main class="page-container">
