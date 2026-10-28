<?php
$page_title = 'Executive Dashboard';
require_once __DIR__ . '/includes/header.php';

$pdo = get_db_connection();

// 1. Calculate Dashboard Metrics
$metrics = [
    'total_items'     => 0,
    'total_units'     => 0,
    'total_cost_val'  => 0,
    'total_retail_val'=> 0,
    'low_stock_count' => 0,
    'out_stock_count' => 0,
];

try {
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_items,
            COALESCE(SUM(quantity), 0) as total_units,
            COALESCE(SUM(unit_cost * quantity), 0) as total_cost_val,
            COALESCE(SUM(selling_price * quantity), 0) as total_retail_val,
            COALESCE(SUM(CASE WHEN quantity <= min_threshold AND quantity > 0 THEN 1 ELSE 0 END), 0) as low_stock_count,
            COALESCE(SUM(CASE WHEN quantity = 0 THEN 1 ELSE 0 END), 0) as out_stock_count
        FROM products WHERE status = 'active'
    ");
    $metrics = $stmt->fetch() ?: $metrics;

    // 2. Fetch Category Breakdown for Chart
    $cat_stmt = $pdo->query("
        SELECT c.name, COUNT(p.id) as item_count, COALESCE(SUM(p.quantity), 0) as unit_count
        FROM categories c
        LEFT JOIN products p ON c.id = p.category_id AND p.status = 'active'
        GROUP BY c.id, c.name
        ORDER BY unit_count DESC
    ");
    $category_stats = $cat_stmt->fetchAll();

    // 3. Fetch Critical Low Stock & Out of Stock Items
    $alert_stmt = $pdo->query("
        SELECT p.*, c.name as category_name
        FROM products p
        JOIN categories c ON p.category_id = c.id
        WHERE p.quantity <= p.min_threshold AND p.status = 'active'
        ORDER BY p.quantity ASC
        LIMIT 6
    ");
    $critical_items = $alert_stmt->fetchAll();

    // 4. Fetch Recent Stock Movement Transactions
    $tx_stmt = $pdo->query("
        SELECT t.*, p.name as product_name, p.sku, u.full_name as handler_name
        FROM stock_transactions t
        JOIN products p ON t.product_id = p.id
        JOIN users u ON t.user_id = u.id
        ORDER BY t.created_at DESC
        LIMIT 8
    ");
    $recent_transactions = $tx_stmt->fetchAll();

} catch (Exception $e) {
    echo '<div class="alert alert-danger">Error loading dashboard: ' . htmlspecialchars($e->getMessage()) . '</div>';
}
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h2 class="h4 text-white fw-bold mb-1"><i class="bi bi-speedometer2 text-info me-2"></i>Shop Inventory Dashboard</h2>
        <div class="text-secondary small">Real-time computer hardware stock, valuation, and restocking signals</div>
    </div>
    <div class="d-flex gap-2">
        <a href="transactions.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i> Audit Logs
        </a>
        <a href="low_stock.php" class="btn btn-outline-warning btn-sm position-relative">
            <i class="bi bi-exclamation-triangle-fill me-1"></i> Low Stock Alerts
            <?php if ($metrics['low_stock_count'] + $metrics['out_stock_count'] > 0): ?>
                <span class="badge bg-danger rounded-pill ms-1"><?= $metrics['low_stock_count'] + $metrics['out_stock_count'] ?></span>
            <?php endif; ?>
        </a>
    </div>
</div>

<!-- Key Performance Indicators (KPI Cards) -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card-custom stat-card">
            <div class="stat-icon primary"><i class="bi bi-cpu"></i></div>
            <div>
                <div class="stat-value"><?= number_format($metrics['total_items']) ?></div>
                <div class="stat-label">Hardware SKUs</div>
                <div class="small text-secondary mt-1"><?= number_format($metrics['total_units']) ?> total units in shop</div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card-custom stat-card">
            <div class="stat-icon success"><i class="bi bi-currency-dollar"></i></div>
            <div>
                <div class="stat-value">₱<?= number_format($metrics['total_retail_val'], 2) ?></div>
                <div class="stat-label">Inventory Retail Value</div>
                <div class="small text-muted mt-1">Cost: ₱<?= number_format($metrics['total_cost_val'], 2) ?></div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card-custom stat-card">
            <div class="stat-icon warning"><i class="bi bi-exclamation-diamond"></i></div>
            <div>
                <div class="stat-value text-warning"><?= number_format($metrics['low_stock_count']) ?></div>
                <div class="stat-label">Low Stock Items</div>
                <div class="small text-secondary mt-1">Below reorder threshold</div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card-custom stat-card">
            <div class="stat-icon danger"><i class="bi bi-x-octagon"></i></div>
            <div>
                <div class="stat-value text-danger"><?= number_format($metrics['out_stock_count']) ?></div>
                <div class="stat-label">Out of Stock</div>
                <div class="small text-secondary mt-1">Immediate restock required</div>
            </div>
        </div>
    </div>
</div>

<!-- Main Row: Chart & Low Stock Alert Feed -->
<div class="row g-4 mb-4">
    <!-- Category Distribution Chart -->
    <div class="col-lg-7">
        <div class="card-custom h-100">
            <div class="card-custom-header">
                <h6 class="text-white mb-0"><i class="bi bi-pie-chart-fill text-info me-2"></i>Stock Distribution by Category</h6>
                <span class="badge bg-dark text-secondary border border-secondary">Hardware Units</span>
            </div>
            <div class="card-custom-body d-flex flex-column justify-content-center">
                <canvas id="categoryStockChart" style="max-height: 280px;"></canvas>
            </div>
        </div>
    </div>

    <!-- Critical Low Stock Attention Box -->
    <div class="col-lg-5">
        <div class="card-custom h-100">
            <div class="card-custom-header">
                <h6 class="text-warning mb-0"><i class="bi bi-bell-fill me-2"></i>Restock Priority Signals</h6>
                <a href="low_stock.php" class="small text-info text-decoration-none">View All</a>
            </div>
            <div class="card-custom-body p-0">
                <?php if (empty($critical_items)): ?>
                    <div class="text-center py-5 text-secondary">
                        <i class="bi bi-check-circle-fill text-success display-5 d-block mb-2"></i>
                        All hardware parts are healthy above minimum thresholds!
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($critical_items as $item): ?>
                            <div class="list-group-item bg-transparent border-secondary border-opacity-25 py-3 px-3 d-flex align-items-center justify-content-between">
                                <div>
                                    <div class="text-white fw-semibold small"><?= htmlspecialchars($item['name']) ?></div>
                                    <div class="text-secondary" style="font-size: 0.78rem;">
                                        <span class="badge bg-dark border border-secondary text-info"><?= htmlspecialchars($item['sku']) ?></span> &bull; 
                                        <?= htmlspecialchars($item['category_name']) ?>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <?php if ($item['quantity'] == 0): ?>
                                        <span class="badge-status badge-out-of-stock">0 in stock</span>
                                    <?php else: ?>
                                        <span class="badge-status badge-low-stock"><?= $item['quantity'] ?> left (Min: <?= $item['min_threshold'] ?>)</span>
                                    <?php endif; ?>
                                    <?php if (!has_role('auditor')): ?>
                                        <a href="stock_in.php?product_id=<?= $item['id'] ?>" class="btn btn-xs btn-outline-info d-block mt-1 py-0 px-2" style="font-size: 0.72rem;">
                                            <i class="bi bi-plus"></i> Restock
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Recent Stock Movements Audit Table -->
<div class="card-custom">
    <div class="card-custom-header">
        <h6 class="text-white mb-0"><i class="bi bi-clock-history text-info me-2"></i>Recent Stock Transactions</h6>
        <a href="transactions.php" class="small text-info text-decoration-none">Full Audit Log &rarr;</a>
    </div>
    <div class="table-responsive">
        <table class="table-custom">
            <thead>
                <tr>
                    <th>Ref Code</th>
                    <th>Date & Time</th>
                    <th>Hardware Item</th>
                    <th>Type</th>
                    <th>Purpose</th>
                    <th>Qty</th>
                    <th>Handler</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recent_transactions)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-secondary">No stock movement transactions recorded yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recent_transactions as $tx): ?>
                        <tr>
                            <td><code class="text-info"><?= htmlspecialchars($tx['transaction_code']) ?></code></td>
                            <td class="text-secondary small"><?= date('M d, Y h:i A', strtotime($tx['created_at'])) ?></td>
                            <td>
                                <span class="fw-medium text-white"><?= htmlspecialchars($tx['product_name']) ?></span>
                                <span class="small text-muted d-block"><?= htmlspecialchars($tx['sku']) ?></span>
                            </td>
                            <td>
                                <?php if ($tx['type'] === 'IN'): ?>
                                    <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-50">
                                        <i class="bi bi-arrow-down-left"></i> IN (+<?= $tx['quantity'] ?>)
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-danger bg-opacity-25 text-danger border border-danger border-opacity-50">
                                        <i class="bi bi-arrow-up-right"></i> OUT (-<?= $tx['quantity'] ?>)
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-secondary small"><?= htmlspecialchars($tx['purpose']) ?></td>
                            <td class="fw-bold text-white"><?= $tx['quantity'] ?></td>
                            <td class="text-secondary small"><i class="bi bi-person me-1"></i><?= htmlspecialchars($tx['handler_name']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Chart initialization script -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    const ctx = document.getElementById('categoryStockChart');
    if (!ctx) return;

    const catLabels = <?= json_encode(array_column($category_stats, 'name')) ?>;
    const catUnits  = <?= json_encode(array_column($category_stats, 'unit_count')) ?>;

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: catLabels,
            datasets: [{
                label: 'Stock Quantity',
                data: catUnits,
                backgroundColor: [
                    '#0f172a', '#1e293b', '#334155', '#475569', '#64748b',
                    '#0f172a', '#1e293b', '#334155', '#475569', '#64748b'
                ],
                borderColor: '#0f172a',
                borderWidth: 1,
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { color: '#475569', font: { size: 11 } }
                },
                y: {
                    grid: { color: '#e2e8f0' },
                    ticks: { color: '#475569', precision: 0 }
                }
            }
        }
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
