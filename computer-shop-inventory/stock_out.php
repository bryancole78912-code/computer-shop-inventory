<?php
$page_title = 'Stock-Out (Sales / Issue)';
require_once __DIR__ . '/includes/header.php';

$pdo = get_db_connection();
$can_transact = !has_role('auditor');
$preselected_id = (int)($_GET['product_id'] ?? 0);

// Process Stock-Out Transaction
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $can_transact) {
    $product_id   = (int)($_POST['product_id'] ?? 0);
    $quantity     = (int)($_POST['quantity'] ?? 0);
    $purpose      = trim($_POST['purpose'] ?? 'Customer Sale');
    $reference_no = trim($_POST['reference_no'] ?? '');
    $recipient    = trim($_POST['recipient'] ?? '');
    $unit_price   = (float)($_POST['unit_price'] ?? 0);
    $notes        = trim($_POST['notes'] ?? '');

    if ($product_id <= 0 || $quantity <= 0) {
        set_flash('danger', 'Please select a hardware component and specify a valid quantity to issue.');
    } else {
        try {
            $pdo->beginTransaction();

            // 1. Fetch current product and lock row
            $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? FOR UPDATE");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch();

            if (!$product) {
                throw new Exception("Hardware component not found.");
            }

            // 2. Strict stock validation: prevent negative inventory
            if ($quantity > $product['quantity']) {
                throw new Exception("Cannot issue {$quantity} units. Only {$product['quantity']} units are currently available in stock for '{$product['name']}'.");
            }

            // 3. Generate unique transaction code
            $txn_code = 'TXN-OUT-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));

            // 4. Update product quantity
            $new_quantity = $product['quantity'] - $quantity;
            $update_stmt = $pdo->prepare("UPDATE products SET quantity = ? WHERE id = ?");
            $update_stmt->execute([$new_quantity, $product_id]);

            // 5. Log in stock_transactions
            $log_stmt = $pdo->prepare("
                INSERT INTO stock_transactions (transaction_code, product_id, user_id, type, purpose, reference_no, quantity, unit_price, recipient, notes)
                VALUES (?, ?, ?, 'OUT', ?, ?, ?, ?, ?, ?)
            ");
            $log_stmt->execute([
                $txn_code,
                $product_id,
                $user['id'],
                $purpose,
                $reference_no,
                $quantity,
                $unit_price > 0 ? $unit_price : $product['selling_price'],
                $recipient,
                $notes
            ]);

            $pdo->commit();
            set_flash('success', "Stock-Out recorded! Issued {$quantity} units of '{$product['name']}'. Remaining stock: {$new_quantity}.");
            header('Location: stock_out.php');
            exit;

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            set_flash('danger', $e->getMessage());
        }
    }
}

// Fetch available products
$products = $pdo->query("SELECT id, sku, name, brand, quantity, selling_price FROM products WHERE status = 'active' ORDER BY name ASC")->fetchAll();

// Fetch recent Stock-Out history
$recent_out = $pdo->query("
    SELECT t.*, p.name as product_name, p.sku, u.full_name as handler_name
    FROM stock_transactions t
    JOIN products p ON t.product_id = p.id
    JOIN users u ON t.user_id = u.id
    WHERE t.type = 'OUT'
    ORDER BY t.created_at DESC
    LIMIT 15
")->fetchAll();
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h2 class="h4 text-white fw-bold mb-1"><i class="bi bi-box-arrow-up-right text-warning me-2"></i>Stock-Out (Sales / Component Issuance)</h2>
        <div class="text-secondary small">Record customer sales, warranty RMA releases, system builds, and internal shop issues</div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Stock-Out Entry Form -->
    <?php if ($can_transact): ?>
    <div class="col-lg-5">
        <div class="card-custom p-4">
            <h5 class="text-white mb-3 fw-bold"><i class="bi bi-cart-dash text-warning me-2"></i>Record Component Issue</h5>
            <form method="POST" action="stock_out.php" id="stockOutForm">
                <div class="mb-3">
                    <label class="form-label small text-secondary fw-semibold">Hardware Component *</label>
                    <select name="product_id" id="stockOutProduct" class="form-select form-select-custom" required onchange="onProductChange(this)">
                        <option value="">-- Choose Component --</option>
                        <?php foreach ($products as $p): ?>
                            <option value="<?= $p['id'] ?>" 
                                    data-price="<?= $p['selling_price'] ?>" 
                                    data-max-qty="<?= $p['quantity'] ?>"
                                    <?= $p['id'] === $preselected_id ? 'selected' : '' ?>>
                                <?= htmlspecialchars($p['name']) ?> (<?= htmlspecialchars($p['sku']) ?>) - Stock: <?= $p['quantity'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div id="stockAvailableInfo" class="small mt-1 text-info"></div>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label small text-secondary fw-semibold">Quantity to Issue *</label>
                        <input type="number" name="quantity" id="stockOutQty" class="form-control form-control-custom" min="1" value="1" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label small text-secondary fw-semibold">Unit Price (₱)</label>
                        <input type="number" step="0.01" name="unit_price" id="stockOutPrice" class="form-control form-control-custom" placeholder="0.00">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small text-secondary fw-semibold">Issue Purpose / Reason *</label>
                    <select name="purpose" class="form-select form-select-custom" required>
                        <option value="Customer Sale">Customer Sale (Walk-in / Online)</option>
                        <option value="Shop Build">Shop Custom PC Assembly</option>
                        <option value="Defective/RMA">Defective / RMA Return to Supplier</option>
                        <option value="Internal Use">Internal Shop Testing / Upgrade</option>
                        <option value="Stock Adjustment">Stock Audit Adjustment</option>
                    </select>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label small text-secondary fw-semibold">Invoice / Reference No.</label>
                        <input type="text" name="reference_no" class="form-control form-control-custom" placeholder="e.g., INV-2026-1090">
                    </div>
                    <div class="col-6">
                        <label class="form-label small text-secondary fw-semibold">Customer / Recipient</label>
                        <input type="text" name="recipient" class="form-control form-control-custom" placeholder="Customer or Rig Name">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label small text-secondary fw-semibold">Remarks / Serial Notes</label>
                    <textarea name="notes" rows="2" class="form-control form-control-custom" placeholder="Customer warranty serial, component notes, bundle details..."></textarea>
                </div>

                <button type="submit" class="btn btn-cyber-primary w-100 py-2">
                    <i class="bi bi-box-arrow-up-right me-1"></i> Confirm Stock-Out Release
                </button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Recent Stock-Out Table -->
    <div class="<?= $can_transact ? 'col-lg-7' : 'col-12' ?>">
        <div class="card-custom h-100">
            <div class="card-custom-header">
                <h6 class="text-white mb-0"><i class="bi bi-clock-history text-warning me-2"></i>Recent Stock-Out Operations</h6>
                <span class="badge bg-dark border border-secondary text-secondary"><?= count($recent_out) ?> Entries</span>
            </div>
            <div class="table-responsive">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>Ref Code</th>
                            <th>Hardware Component</th>
                            <th>Purpose / Recipient</th>
                            <th class="text-center">Qty</th>
                            <th class="text-end">Price</th>
                            <th>Date</th>
                            <th>Handler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_out)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-secondary">No stock-out movements recorded yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recent_out as $row): ?>
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
                                    <td>
                                        <div class="text-white small"><?= htmlspecialchars($row['purpose']) ?></div>
                                        <?php if (!empty($row['recipient'])): ?>
                                            <div class="small text-muted"><i class="bi bi-person me-1"></i><?= htmlspecialchars($row['recipient']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center"><span class="badge bg-danger bg-opacity-25 text-danger fw-bold">-<?= $row['quantity'] ?></span></td>
                                    <td class="text-end text-white small">₱<?= number_format($row['unit_price'], 2) ?></td>
                                    <td class="text-secondary small"><?= date('M d, H:i', strtotime($row['created_at'])) ?></td>
                                    <td class="text-secondary small"><?= htmlspecialchars($row['handler_name']) ?></td>
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
function onProductChange(select) {
    const selected = select.options[select.selectedIndex];
    const price = selected.getAttribute('data-price');
    const maxQty = parseInt(selected.getAttribute('data-max-qty') || '0', 10);
    const infoDiv = document.getElementById('stockAvailableInfo');
    const qtyInput = document.getElementById('stockOutQty');

    if (price) {
        document.getElementById('stockOutPrice').value = price;
    }

    if (selected.value) {
        qtyInput.max = maxQty;
        if (maxQty <= 0) {
            infoDiv.className = 'small mt-1 text-danger fw-bold';
            infoDiv.innerHTML = '<i class="bi bi-x-circle me-1"></i> This item is OUT OF STOCK (0 units). Cannot issue.';
        } else {
            infoDiv.className = 'small mt-1 text-info';
            infoDiv.innerHTML = `<i class="bi bi-check-circle me-1"></i> Available in shop: <strong>${maxQty}</strong> units`;
        }
    } else {
        infoDiv.innerHTML = '';
        qtyInput.removeAttribute('max');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const select = document.getElementById('stockOutProduct');
    if (select && select.value) {
        onProductChange(select);
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
