<?php
$page_title = 'Stock-In (Restock)';
require_once __DIR__ . '/includes/header.php';

$pdo = get_db_connection();
$can_transact = !has_role('auditor');
$preselected_id = (int)($_GET['product_id'] ?? 0);

// Process Stock-In Transaction
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $can_transact) {
    $product_id   = (int)($_POST['product_id'] ?? 0);
    $quantity     = (int)($_POST['quantity'] ?? 0);
    $supplier_id  = !empty($_POST['supplier_id']) ? (int)$_POST['supplier_id'] : null;
    $reference_no = trim($_POST['reference_no'] ?? '');
    $unit_cost    = (float)($_POST['unit_cost'] ?? 0);
    $notes        = trim($_POST['notes'] ?? '');

    if ($product_id <= 0 || $quantity <= 0) {
        set_flash('danger', 'Please select a valid hardware component and enter a quantity greater than zero.');
    } else {
        try {
            $pdo->beginTransaction();

            // 1. Fetch current product info
            $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? FOR UPDATE");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch();

            if (!$product) {
                throw new Exception("Product not found.");
            }

            // 2. Generate unique transaction code
            $txn_code = 'TXN-IN-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));

            // 3. Update product quantity and unit cost if supplied
            $new_quantity = $product['quantity'] + $quantity;
            $update_cost  = $unit_cost > 0 ? $unit_cost : $product['unit_cost'];

            $update_stmt = $pdo->prepare("UPDATE products SET quantity = ?, unit_cost = ? WHERE id = ?");
            $update_stmt->execute([$new_quantity, $update_cost, $product_id]);

            // 4. Log in stock_transactions
            $log_stmt = $pdo->prepare("
                INSERT INTO stock_transactions (transaction_code, product_id, user_id, type, purpose, reference_no, quantity, unit_cost, supplier_id, notes)
                VALUES (?, ?, ?, 'IN', 'Supplier Delivery', ?, ?, ?, ?, ?)
            ");
            $log_stmt->execute([
                $txn_code,
                $product_id,
                $user['id'],
                $reference_no,
                $quantity,
                $update_cost,
                $supplier_id,
                $notes
            ]);

            $pdo->commit();
            set_flash('success', "Stock-In successful! Added {$quantity} units of '{$product['name']}'. New stock count: {$new_quantity}.");
            header('Location: stock_in.php');
            exit;

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            set_flash('danger', 'Transaction failed: ' . $e->getMessage());
        }
    }
}

// Fetch products & suppliers
$products = $pdo->query("SELECT id, sku, name, brand, quantity, unit_cost FROM products WHERE status = 'active' ORDER BY name ASC")->fetchAll();
$suppliers = $pdo->query("SELECT id, name FROM suppliers ORDER BY name ASC")->fetchAll();

// Fetch recent Stock-In history
$recent_in = $pdo->query("
    SELECT t.*, p.name as product_name, p.sku, s.name as supplier_name, u.full_name as handler_name
    FROM stock_transactions t
    JOIN products p ON t.product_id = p.id
    LEFT JOIN suppliers s ON t.supplier_id = s.id
    JOIN users u ON t.user_id = u.id
    WHERE t.type = 'IN'
    ORDER BY t.created_at DESC
    LIMIT 15
")->fetchAll();
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h2 class="h4 text-white fw-bold mb-1"><i class="bi bi-box-arrow-in-down text-success me-2"></i>Stock-In (Restocking / Supplier Arrivals)</h2>
        <div class="text-secondary small">Record incoming inventory shipments from distributors and increase stock quantities</div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Stock-In Entry Form -->
    <?php if ($can_transact): ?>
    <div class="col-lg-5">
        <div class="card-custom p-4">
            <h5 class="text-white mb-3 fw-bold"><i class="bi bi-pencil-square text-success me-2"></i>Receive New Stock</h5>
            <form method="POST" action="stock_in.php">
                <div class="mb-3">
                    <label class="form-label small text-secondary fw-semibold">Hardware Component *</label>
                    <select name="product_id" id="stockInProduct" class="form-select form-select-custom" required onchange="onProductSelect(this)">
                        <option value="">-- Choose Component --</option>
                        <?php foreach ($products as $p): ?>
                            <option value="<?= $p['id'] ?>" 
                                    data-cost="<?= $p['unit_cost'] ?>" 
                                    data-current-qty="<?= $p['quantity'] ?>"
                                    <?= $p['id'] === $preselected_id ? 'selected' : '' ?>>
                                <?= htmlspecialchars($p['name']) ?> (<?= htmlspecialchars($p['sku']) ?>) - Current: <?= $p['quantity'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label small text-secondary fw-semibold">Quantity Received *</label>
                        <input type="number" name="quantity" class="form-control form-control-custom" min="1" value="1" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label small text-secondary fw-semibold">Supplier Unit Cost (₱)</label>
                        <input type="number" step="0.01" name="unit_cost" id="stockInCost" class="form-control form-control-custom" placeholder="0.00">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small text-secondary fw-semibold">Supplier / Distributor</label>
                    <select name="supplier_id" class="form-select form-select-custom">
                        <option value="">-- Direct / Walk-in Distributor --</option>
                        <?php foreach ($suppliers as $s): ?>
                            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label small text-secondary fw-semibold">Delivery Receipt / Purchase Order No.</label>
                    <input type="text" name="reference_no" class="form-control form-control-custom" placeholder="e.g., PO-TF-2026-091">
                </div>

                <div class="mb-4">
                    <label class="form-label small text-secondary fw-semibold">Batch Remarks / Notes</label>
                    <textarea name="notes" rows="2" class="form-control form-control-custom" placeholder="Batch condition, supplier remarks, warranty coverage..."></textarea>
                </div>

                <button type="submit" class="btn btn-cyber-success w-100 py-2">
                    <i class="bi bi-box-arrow-in-down me-1"></i> Confirm Stock-In Entry
                </button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- History Table -->
    <div class="<?= $can_transact ? 'col-lg-7' : 'col-12' ?>">
        <div class="card-custom h-100">
            <div class="card-custom-header">
                <h6 class="text-white mb-0"><i class="bi bi-clock-history text-success me-2"></i>Recent Stock-In Deliveries</h6>
                <span class="badge bg-dark border border-secondary text-secondary"><?= count($recent_in) ?> Entries</span>
            </div>
            <div class="table-responsive">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>Ref Code</th>
                            <th>Hardware Component</th>
                            <th>Supplier</th>
                            <th class="text-center">Qty</th>
                            <th class="text-end">Cost</th>
                            <th>Date</th>
                            <th>Handler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_in)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-secondary">No stock arrivals recorded yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recent_in as $row): ?>
                                <tr>
                                    <td>
                                        <span class="font-monospace text-info small"><?= htmlspecialchars($row['transaction_code']) ?></span>
                                        <?php if (!empty($row['reference_no'])): ?>
                                            <div class="small text-muted"><?= htmlspecialchars($row['reference_no']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="text-white fw-semibold small"><?= htmlspecialchars($row['product_name']) ?></div>
                                        <div class="small text-secondary"><?= htmlspecialchars($row['sku']) ?></div>
                                    </td>
                                    <td class="small text-secondary"><?= htmlspecialchars($row['supplier_name'] ?: 'Direct Distributor') ?></td>
                                    <td class="text-center"><span class="badge bg-success bg-opacity-25 text-success fw-bold">+<?= $row['quantity'] ?></span></td>
                                    <td class="text-end text-secondary small">₱<?= number_format($row['unit_cost'], 2) ?></td>
                                    <td class="text-secondary small"><?= date('M d, H:i', strtotime($row['created_at'])) ?></td>
                                    <td class="text-secondary small"><i class="bi bi-person me-1"></i><?= htmlspecialchars($row['handler_name']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function onProductSelect(select) {
    const selectedOption = select.options[select.selectedIndex];
    const cost = selectedOption.getAttribute('data-cost');
    if (cost) {
        document.getElementById('stockInCost').value = cost;
    }
}
// Trigger on page load if preselected
document.addEventListener('DOMContentLoaded', () => {
    const select = document.getElementById('stockInProduct');
    if (select && select.value) {
        onProductSelect(select);
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
