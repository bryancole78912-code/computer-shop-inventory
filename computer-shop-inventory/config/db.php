<?php
// ==========================================================
// Database Configuration: Computer Shop Inventory System
// ==========================================================

define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'computer_shop_inventory');
define('DB_USER', 'root');
define('DB_PASS', '');

function get_db_connection() {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Render a friendly error guide for local development
            die('
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <title>Database Connection Error | Computer Shop Inventory</title>
                <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
                <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
                <style>
                    body { background: #0f172a; color: #e2e8f0; font-family: system-ui, sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 20px; }
                    .card { background: #1e293b; border: 1px solid #334155; border-radius: 12px; max-width: 650px; }
                    pre { background: #0b0f19; padding: 15px; border-radius: 8px; color: #38bdf8; }
                </style>
            </head>
            <body>
                <div class="card p-4 shadow-lg">
                    <div class="text-center mb-4">
                        <i class="bi bi-database-exclamation text-warning display-4"></i>
                        <h3 class="mt-2 text-white">Database Connection Required</h3>
                        <p class="text-secondary">Could not connect to MySQL database <code>' . htmlspecialchars(DB_NAME) . '</code> on <code>' . htmlspecialchars(DB_HOST) . '</code>.</p>
                    </div>
                    <div class="alert alert-danger bg-danger bg-opacity-10 border-danger border-opacity-25 text-danger">
                        <strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '
                    </div>
                    <h6 class="text-white mt-3">Quick Setup for XAMPP / WAMP:</h6>
                    <ol class="small text-secondary">
                        <li>Make sure <strong>Apache</strong> and <strong>MySQL</strong> are started in the XAMPP Control Panel.</li>
                        <li>Open <a href="http://localhost/phpmyadmin" target="_blank" class="text-info">phpMyAdmin</a>.</li>
                        <li>Create a database named <code>' . htmlspecialchars(DB_NAME) . '</code>.</li>
                        <li>Import the schema: <code>database/schema.sql</code> and seed data: <code>database/seed_data.sql</code>.</li>
                    </ol>
                    <div class="mt-3 text-center">
                        <a href="demo.html" class="btn btn-primary px-4 me-2"><i class="bi bi-play-circle me-1"></i> Launch Standalone Demo Mode</a>
                        <a href="javascript:location.reload()" class="btn btn-outline-light px-3"><i class="bi bi-arrow-clockwise me-1"></i> Retry Connection</a>
                    </div>
                </div>
            </body>
            </html>
            ');
        }
    }

    return $pdo;
}
