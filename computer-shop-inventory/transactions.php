<?php
$page_title = 'Transaction Audit Trail';
require_once __DIR__ . '/includes/header.php';

$pdo = get_db_connection();

// Filter parameters
$type_filter    = $_GET['type'] ?? '';
$purpose_filter = $_GET['purpose'] ?? '';
$date_from      = $_GET['date_from'] ?? '';
$date_to        = $_GET['date_to'] ?? '';

$sql = "
    SELECT t.*, p.name as product_name, p.sku, c.name as category_name, 
           u.full_name as handler_name, u.role as handler_role, s.name as supplier_name
    FROM stock_transactions t
    JOIN products p ON t.product_id = p.id
    JOIN categories c ON p.category_id = c.id
    JOIN users u ON t.user_id = u.id
    LEFT JOIN suppliers s ON t.supplier_id = s.id
    WHERE 1=1
";
$params = [];

if (!empty($type_filter)) {
    $sql .= " AND t.type = ?";
    $params[] = $type_filter;
}
if (!empty($purpose_filter)) {
    $sql .= " AND t.purpose = ?";
    $params[] = $purpose_filter;
}
if (!empty($date_from)) {
    $sql .= " AND DATE(t.created_at) >= ?";
    $params[] = $date_from;
}
if (!empty($date_to)) {
    $sql .= " AND DATE(t.created_at) <= ?";
    $params[] = $date_to;
}

$sql .= " ORDER BY t.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$transactions = $stmt->fetchAll();

// Calculate totals for currently filtered records
$total_in_qty  = 0;
$total_out_qty = 0;
foreach ($transactions as $t) {
    if ($t['type'] === 'IN') {
        $total_in_qty += $t['quantity'];
    } else {
        $total_out_qty += $t['quantity'];
    }
}
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 no-print">
    <div>
        <h2 class="h4 text-white fw-bold mb-1"><i class="bi bi-journal-check text-info me-2"></i>Inventory Transaction History & Audit Log</h2>
        <div class="text-secondary small">Immutable chronological audit trail of all component restocks and customer disbursements</div>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="exportTableToCSV('transactionsTable', 'transactions_audit_log.csv')">
            <i class="bi bi-file-earmark-arrow-down me-1"></i> Export CSV
        </button>
        <button type="button" class="btn btn-outline-info btn-sm" onclick="window.print()">
            <i class="bi bi-printer me-1"></i> Print Audit Trail
        </button>
    </div>
</div>

<!-- Print-only Header -->
<div class="d-none d-print-block mb-3">
    <h3 class="mb-1">COMPUSHOP INVENTORY - TRANSACTION AUDIT REPORT</h3>
    <div>Generated: <?= date('F d, Y h:i A') ?> &bull; Prepared by: <strong>Bryan Cahayag Cole</strong></div>
    <hr>
</div>

<!-- Summary Badges -->
<div class="row g-3 mb-4 no-print">
    <div class="col-sm-4">
        <div class="card-custom p-3 d-flex align-items-center justify-content-between">
            <div>
                <div class="small text-secondary">Total Transactions</div>
                <div class="fs-4 fw-bold text-white"><?= count($transactions) ?></div>
            </div>
            <i class="bi bi-receipt text-info fs-2"></i>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card-custom p-3 d-flex align-items-center justify-content-between">
            <div>
                <div class="small text-secondary">Total Units Restocked (IN)</div>
                <div class="fs-4 fw-bold text-success">+<?= number_format($total_in_qty) ?></div>
            </div>
            <i class="bi bi-arrow-down-left-circle text-success fs-2"></i>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card-custom p-3 d-flex align-items-center justify-content-between">
            <div>
                <div class="small text-secondary">Total Units Issued (OUT)</div>
                <div class="fs-4 fw-bold text-danger">-<?= number_format($total_out_qty) ?></div>
            </div>
            <i class="bi bi-arrow-up-right-circle text-danger fs-2"></i>
        </div>
    </div>
</div>

<!-- Filter Bar -->
<div class="card-custom mb-4 p-3 no-print">
    <form method="GET" action="transactions.php" class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label small text-secondary fw-semibold">Movement Type</label>
            <select name="type" class="form-select form-select-custom">
                <option value="">All Types (IN & OUT)</option>
                <option value="IN" <?= $type_filter === 'IN' ? 'selected' : '' ?>>Stock-In (Deliveries)</option>
                <option value="OUT" <?= $type_filter === 'OUT' ? 'selected' : '' ?>>Stock-Out (Disbursements)</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small text-secondary fw-semibold">Purpose / Reason</label>
            <select name="purpose" class="form-select form-select-custom">
                <option value="">All Reasons</option>
                <option value="Supplier Delivery" <?= $purpose_filter === 'Supplier Delivery' ? 'selected' : '' ?>>Supplier Delivery</option>
                <option value="Customer Sale" <?= $purpose_filter === 'Customer Sale' ? 'selected' : '' ?>>Customer Sale</option>
                <option value="Shop Build" <?= $purpose_filter === 'Shop Build' ? 'selected' : '' ?>>Shop Build</option>
                <option value="Defective/RMA" <?= $purpose_filter === 'Defective/RMA' ? 'selected' : '' ?>>Defective/RMA</option>
                <option value="Internal Use" <?= $purpose_filter === 'Internal Use' ? 'selected' : '' ?>>Internal Use</option>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small text-secondary fw-semibold">From Date</label>
            <input type="date" name="date_from" value="<?= htmlspecialchars($date_from) ?>" class="form-control form-control-custom">
        </div>
        <div class="col-md-2">
            <label class="form-label small text-secondary fw-semibold">To Date</label>
            <input type="date" name="date_to" value="<?= htmlspecialchars($date_to) ?>" class="form-control form-control-custom">
        </div>
        <div class="col-md-2 d-flex gap-1">
            <button type="submit" class="btn btn-cyber-primary btn-sm flex-grow-1">
                <i class="bi bi-funnel me-1"></i> Filter
            </button>
            <a href="transactions.php" class="btn btn-outline-secondary btn-sm" title="Reset Filters">
                <i class="bi bi-arrow-counterclockwise"></i>
            </a>
        </div>
    </form>
</div>

<!-- Transactions Table -->
<div class="card-custom">
    <div class="table-responsive">
        <table class="table-custom" id="transactionsTable">
            <thead>
                <tr>
                    <th>Ref Code</th>
                    <th>Date & Time</th>
                    <th>Hardware Component</th>
                    <th>Category</th>
                    <th>Movement</th>
                    <th>Purpose / Reason</th>
                    <th class="text-center">Qty</th>
                    <th>Party / Reference</th>
                    <th>Handler</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($transactions)): ?>
                    <tr>
                        <td colspan="10" class="text-center py-5 text-secondary">No stock movement transactions matched your criteria.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($transactions as $t): ?>
                        <tr>
                            <td><span class="font-monospace text-info small"><?= htmlspecialchars($t['transaction_code']) ?></span></td>
                            <td class="text-secondary small"><?= date('M d, Y H:i', strtotime($t['created_at'])) ?></td>
                            <td>
                                <div class="text-white fw-semibold small"><?= htmlspecialchars($t['product_name']) ?></div>
                                <div class="small text-secondary font-monospace"><?= htmlspecialchars($t['sku']) ?></div>
                            </td>
                            <td><span class="badge bg-secondary bg-opacity-25 text-white small"><?= htmlspecialchars($t['category_name']) ?></span></td>
                            <td>
                                <?php if ($t['type'] === 'IN'): ?>
                                    <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-50">
                                        <i class="bi bi-arrow-down-left"></i> IN
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-danger bg-opacity-25 text-danger border border-danger border-opacity-50">
                                        <i class="bi bi-arrow-up-right"></i> OUT
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-secondary small"><?= htmlspecialchars($t['purpose']) ?></td>
                            <td class="text-center fw-bold <?= $t['type'] === 'IN' ? 'text-success' : 'text-danger' ?>">
                                <?= ($t['type'] === 'IN' ? '+' : '-') . $t['quantity'] ?>
                            </td>
                            <td class="text-secondary small">
                                <?php if (!empty($t['supplier_name'])): ?>
                                    <span class="text-white"><?= htmlspecialchars($t['supplier_name']) ?></span>
                                <?php elseif (!empty($t['recipient'])): ?>
                                    <span class="text-white"><?= htmlspecialchars($t['recipient']) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($t['reference_no'])): ?>
                                    <div class="text-muted small">Ref: <?= htmlspecialchars($t['reference_no']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="text-secondary small">
                                <div><i class="bi bi-person me-1"></i><?= htmlspecialchars($t['handler_name']) ?></div>
                                <span class="badge-role <?= htmlspecialchars($t['handler_role']) ?>"><?= htmlspecialchars($t['handler_role']) ?></span>
                            </td>
                            <td class="text-secondary small" style="max-width: 200px;">
                                <?= htmlspecialchars($t['notes'] ?: '-') ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
