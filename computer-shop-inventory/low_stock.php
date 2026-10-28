<?php
$page_title = 'Low-Stock Alerts & Reorder Report';
require_once __DIR__ . '/includes/header.php';

$pdo = get_db_connection();

// Query all items at or below minimum reorder threshold
$stmt = $pdo->query("
    SELECT p.*, c.name as category_name
    FROM products p
    JOIN categories c ON p.category_id = c.id
    WHERE p.quantity <= p.min_threshold AND p.status = 'active'
    ORDER BY p.quantity ASC, p.name ASC
");
$low_stock_items = $stmt->fetchAll();

// Calculate total estimated reorder budget
$total_restock_cost = 0;
$total_restock_units = 0;
foreach ($low_stock_items as $item) {
    // Suggested replenishment to reach 2.5x the threshold
    $suggested_qty = max(5, ($item['min_threshold'] * 2) - $item['quantity']);
    $total_restock_units += $suggested_qty;
    $total_restock_cost += ($suggested_qty * $item['unit_cost']);
}
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 no-print">
    <div>
        <h2 class="h4 text-white fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>Low-Stock Warnings & Reorder Manager</h2>
        <div class="text-secondary small">Critical hardware parts nearing stock depletion requiring supplier purchase orders</div>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="exportTableToCSV('lowStockTable', 'low_stock_reorder_sheet.csv')">
            <i class="bi bi-file-earmark-arrow-down me-1"></i> Export Reorder List
        </button>
        <button type="button" class="btn btn-outline-info btn-sm" onclick="window.print()">
            <i class="bi bi-printer me-1"></i> Print Purchase Reorder Sheet
        </button>
    </div>
</div>

<!-- Print-only Header -->
<div class="d-none d-print-block mb-4">
    <h3 class="mb-1">COMPUSHOP INVENTORY - SUPPLIER REORDER SHEET</h3>
    <div>Generated on: <?= date('F d, Y h:i A') ?> &bull; Prepared by: <strong>Bryan Cahayag Cole</strong></div>
    <hr>
</div>

<!-- Reorder Budget Summary Cards -->
<div class="row g-3 mb-4 no-print">
    <div class="col-md-4">
        <div class="card-custom stat-card">
            <div class="stat-icon warning"><i class="bi bi-exclamation-diamond"></i></div>
            <div>
                <div class="stat-value text-warning"><?= count($low_stock_items) ?></div>
                <div class="stat-label">Hardware Items Below Threshold</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card-custom stat-card">
            <div class="stat-icon primary"><i class="bi bi-box-seam"></i></div>
            <div>
                <div class="stat-value"><?= number_format($total_restock_units) ?></div>
                <div class="stat-label">Suggested Restock Units</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card-custom stat-card">
            <div class="stat-icon success"><i class="bi bi-cash-stack"></i></div>
            <div>
                <div class="stat-value text-success">₱<?= number_format($total_restock_cost, 2) ?></div>
                <div class="stat-label">Estimated Supplier Budget</div>
            </div>
        </div>
    </div>
</div>

<!-- Low Stock Table -->
<div class="card-custom">
    <div class="card-custom-header no-print">
        <h6 class="text-white mb-0"><i class="bi bi-list-check text-warning me-2"></i>Depleted & Low Inventory Items</h6>
        <span class="badge bg-warning bg-opacity-25 text-warning border border-warning border-opacity-50">Action Needed</span>
    </div>
    <div class="table-responsive">
        <table class="table-custom" id="lowStockTable">
            <thead>
                <tr>
                    <th>SKU</th>
                    <th>Hardware Component</th>
                    <th>Category</th>
                    <th class="text-center">Current Stock</th>
                    <th class="text-center">Min Threshold</th>
                    <th class="text-center">Suggested Order</th>
                    <th class="text-end">Unit Cost</th>
                    <th class="text-end">Subtotal Cost</th>
                    <th class="text-center no-export no-print">Restock Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($low_stock_items)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-5 text-secondary">
                            <i class="bi bi-shield-check text-success display-5 d-block mb-2"></i>
                            Excellent! No hardware items are currently below reorder thresholds.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($low_stock_items as $item): 
                        $suggested = max(5, ($item['min_threshold'] * 2) - $item['quantity']);
                        $subtotal  = $suggested * $item['unit_cost'];
                    ?>
                        <tr>
                            <td><span class="font-monospace text-info"><?= htmlspecialchars($item['sku']) ?></span></td>
                            <td>
                                <div class="text-white fw-semibold"><?= htmlspecialchars($item['name']) ?></div>
                                <div class="small text-secondary"><?= htmlspecialchars($item['brand']) ?> &bull; <?= htmlspecialchars($item['location']) ?></div>
                            </td>
                            <td><span class="badge bg-secondary bg-opacity-25 text-white"><?= htmlspecialchars($item['category_name']) ?></span></td>
                            <td class="text-center">
                                <?php if ($item['quantity'] == 0): ?>
                                    <span class="badge-status badge-out-of-stock">0 (Depleted)</span>
                                <?php else: ?>
                                    <span class="badge-status badge-low-stock"><?= $item['quantity'] ?> units</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center text-secondary"><?= $item['min_threshold'] ?></td>
                            <td class="text-center fw-bold text-info">+<?= $suggested ?> units</td>
                            <td class="text-end text-secondary">₱<?= number_format($item['unit_cost'], 2) ?></td>
                            <td class="text-end text-white fw-semibold">₱<?= number_format($subtotal, 2) ?></td>
                            <td class="text-center no-export no-print">
                                <?php if (!has_role('auditor')): ?>
                                    <a href="stock_in.php?product_id=<?= $item['id'] ?>" class="btn btn-sm btn-cyber-success py-1 px-3">
                                        <i class="bi bi-plus-circle me-1"></i> Restock
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted small">View Only</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
