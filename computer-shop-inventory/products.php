<?php
$page_title = 'Hardware Catalog';
require_once __DIR__ . '/includes/header.php';

$pdo = get_db_connection();
$can_edit = !has_role('auditor');
$is_admin = has_role('admin');

// Handle Add / Edit / Delete Product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $can_edit) {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $sku           = trim($_POST['sku'] ?? '');
        $name          = trim($_POST['name'] ?? '');
        $category_id   = (int)($_POST['category_id'] ?? 0);
        $brand         = trim($_POST['brand'] ?? '');
        $specifications= trim($_POST['specifications'] ?? '');
        $unit_cost     = (float)($_POST['unit_cost'] ?? 0);
        $selling_price = (float)($_POST['selling_price'] ?? 0);
        $quantity      = (int)($_POST['quantity'] ?? 0);
        $min_threshold = (int)($_POST['min_threshold'] ?? 5);
        $location      = trim($_POST['location'] ?? 'Shelf A1');

        if (empty($sku) || empty($name) || empty($category_id) || empty($brand)) {
            set_flash('danger', 'Please complete all required fields (SKU, Name, Category, Brand).');
        } else {
            try {
                if ($action === 'add') {
                    $stmt = $pdo->prepare("
                        INSERT INTO products (sku, name, category_id, brand, specifications, unit_cost, selling_price, quantity, min_threshold, location)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$sku, $name, $category_id, $brand, $specifications, $unit_cost, $selling_price, $quantity, $min_threshold, $location]);
                    set_flash('success', "Hardware component '$name' ($sku) added successfully!");
                } else if ($action === 'edit') {
                    $id = (int)$_POST['product_id'];
                    $stmt = $pdo->prepare("
                        UPDATE products 
                        SET sku = ?, name = ?, category_id = ?, brand = ?, specifications = ?, unit_cost = ?, selling_price = ?, quantity = ?, min_threshold = ?, location = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$sku, $name, $category_id, $brand, $specifications, $unit_cost, $selling_price, $quantity, $min_threshold, $location, $id]);
                    set_flash('success', "Hardware component '$name' updated successfully!");
                }
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    set_flash('danger', "Error: SKU '$sku' already exists in the system.");
                } else {
                    set_flash('danger', "Database error: " . $e->getMessage());
                }
            }
        }
        header('Location: products.php');
        exit;
    }

    if ($action === 'delete' && $is_admin) {
        $id = (int)$_POST['product_id'];
        try {
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$id]);
            set_flash('success', "Hardware component deleted successfully.");
        } catch (PDOException $e) {
            set_flash('danger', "Cannot delete component: It has associated stock transaction history.");
        }
        header('Location: products.php');
        exit;
    }
}

// Fetch categories for select dropdown
$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();

// Fetch products with category details
$products = $pdo->query("
    SELECT p.*, c.name as category_name, c.code as category_code
    FROM products p
    JOIN categories c ON p.category_id = c.id
    WHERE p.status = 'active'
    ORDER BY p.name ASC
")->fetchAll();
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h2 class="h4 text-white fw-bold mb-1"><i class="bi bi-cpu text-info me-2"></i>Hardware Catalog & Items</h2>
        <div class="text-secondary small">Centralized inventory of computer parts, components, laptops, and peripherals</div>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="exportTableToCSV('productsTable', 'computer_hardware_inventory.csv')">
            <i class="bi bi-file-earmark-arrow-down me-1"></i> Export CSV
        </button>
        <?php if ($can_edit): ?>
            <button type="button" class="btn btn-cyber-primary btn-sm" data-bs-toggle="modal" data-bs-target="#productModal" onclick="prepareAddModal()">
                <i class="bi bi-plus-lg me-1"></i> Add Hardware Item
            </button>
        <?php endif; ?>
    </div>
</div>

<!-- Search & Filter Controls -->
<div class="card-custom mb-4 p-3">
    <div class="row g-2 align-items-center">
        <div class="col-md-5">
            <div class="input-group">
                <span class="input-group-text bg-dark border-secondary text-secondary"><i class="bi bi-search"></i></span>
                <input type="text" class="form-control form-control-custom" id="catalogSearch" data-table-search="productsTable" placeholder="Search by SKU, item name, brand, or specs...">
            </div>
        </div>
        <div class="col-md-4">
            <select class="form-select form-select-custom" id="categoryFilter" onchange="filterProducts()">
                <option value="ALL">All Categories (<?= count($categories) ?>)</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= htmlspecialchars($cat['name']) ?>"><?= htmlspecialchars($cat['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <select class="form-select form-select-custom" id="stockStatusFilter" onchange="filterProducts()">
                <option value="ALL">All Stock Levels</option>
                <option value="IN_STOCK">In Stock Only</option>
                <option value="LOW_STOCK">Low Stock Warning</option>
                <option value="OUT_OF_STOCK">Out of Stock</option>
            </select>
        </div>
    </div>
</div>

<!-- Product Table -->
<div class="card-custom">
    <div class="table-responsive">
        <table class="table-custom" id="productsTable">
            <thead>
                <tr>
                    <th>SKU / Part No.</th>
                    <th>Hardware Item</th>
                    <th>Category</th>
                    <th>Brand</th>
                    <th class="text-end">Cost</th>
                    <th class="text-end">Retail Price</th>
                    <th class="text-center">Stock</th>
                    <th>Status</th>
                    <th>Shelf/Rack</th>
                    <?php if ($can_edit): ?>
                        <th class="text-end no-export">Actions</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="10" class="text-center py-5 text-secondary">No hardware items recorded. Click "Add Hardware Item" to start.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($products as $p): 
                        $status_class = 'badge-in-stock';
                        $status_label = 'In Stock';
                        $status_key   = 'IN_STOCK';
                        if ($p['quantity'] == 0) {
                            $status_class = 'badge-out-of-stock';
                            $status_label = 'Out of Stock';
                            $status_key   = 'OUT_OF_STOCK';
                        } else if ($p['quantity'] <= $p['min_threshold']) {
                            $status_class = 'badge-low-stock';
                            $status_label = 'Low Stock (' . $p['quantity'] . '/' . $p['min_threshold'] . ')';
                            $status_key   = 'LOW_STOCK';
                        }
                    ?>
                        <tr data-category="<?= htmlspecialchars($p['category_name']) ?>" data-status="<?= $status_key ?>">
                            <td><span class="badge bg-dark border border-secondary text-info font-monospace"><?= htmlspecialchars($p['sku']) ?></span></td>
                            <td>
                                <div class="text-white fw-semibold"><?= htmlspecialchars($p['name']) ?></div>
                                <?php if (!empty($p['specifications'])): ?>
                                    <div class="text-secondary small text-truncate" style="max-width: 280px;" title="<?= htmlspecialchars($p['specifications']) ?>">
                                        <?= htmlspecialchars($p['specifications']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge bg-secondary bg-opacity-25 text-white"><?= htmlspecialchars($p['category_name']) ?></span></td>
                            <td class="text-secondary"><?= htmlspecialchars($p['brand']) ?></td>
                            <td class="text-end text-secondary">₱<?= number_format($p['unit_cost'], 2) ?></td>
                            <td class="text-end fw-semibold text-white">₱<?= number_format($p['selling_price'], 2) ?></td>
                            <td class="text-center">
                                <span class="fs-6 fw-bold <?= $p['quantity'] == 0 ? 'text-danger' : ($p['quantity'] <= $p['min_threshold'] ? 'text-warning' : 'text-success') ?>">
                                    <?= $p['quantity'] ?>
                                </span>
                            </td>
                            <td><span class="badge-status <?= $status_class ?>"><?= $status_label ?></span></td>
                            <td class="text-secondary small"><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($p['location'] ?: 'Unassigned') ?></td>
                            <?php if ($can_edit): ?>
                                <td class="text-end no-export">
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-info" title="Edit Item" 
                                            onclick='prepareEditModal(<?= json_encode($p, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <a href="stock_in.php?product_id=<?= $p['id'] ?>" class="btn btn-outline-success" title="Restock (Stock-In)">
                                            <i class="bi bi-plus-lg"></i>
                                        </a>
                                        <a href="stock_out.php?product_id=<?= $p['id'] ?>" class="btn btn-outline-warning" title="Issue / Sale (Stock-Out)">
                                            <i class="bi bi-dash-lg"></i>
                                        </a>
                                        <?php if ($is_admin): ?>
                                            <button type="button" class="btn btn-outline-danger" title="Delete Item" onclick="confirmDelete(<?= $p['id'] ?>, '<?= htmlspecialchars(addslashes($p['name'])) ?>')">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add / Edit Product Modal -->
<?php if ($can_edit): ?>
<div class="modal fade" id="productModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="products.php" class="modal-content modal-content-custom">
            <input type="hidden" name="action" id="modalAction" value="add">
            <input type="hidden" name="product_id" id="modalProductId" value="">

            <div class="modal-header modal-header-custom">
                <h5 class="modal-title text-white" id="modalTitle"><i class="bi bi-cpu text-info me-2"></i>Add Hardware Component</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small text-secondary fw-semibold">SKU / Model Part Number *</label>
                        <input type="text" name="sku" id="modalSku" class="form-control form-control-custom" placeholder="e.g., CPU-AMD-7800X3D" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small text-secondary fw-semibold">Brand / Manufacturer *</label>
                        <input type="text" name="brand" id="modalBrand" class="form-control form-control-custom" placeholder="e.g., AMD, ASUS, Corsair, Logitech" required>
                    </div>

                    <div class="col-12">
                        <label class="form-label small text-secondary fw-semibold">Hardware Product Name *</label>
                        <input type="text" name="name" id="modalName" class="form-control form-control-custom" placeholder="e.g., AMD Ryzen 7 7800X3D Processor" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small text-secondary fw-semibold">Category Classification *</label>
                        <select name="category_id" id="modalCategory" class="form-select form-select-custom" required>
                            <option value="">-- Select Hardware Category --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small text-secondary fw-semibold">Shop Shelf / Bin Location</label>
                        <input type="text" name="location" id="modalLocation" class="form-control form-control-custom" placeholder="e.g., Cabinet CPU-1, Shelf MB-2">
                    </div>

                    <div class="col-12">
                        <label class="form-label small text-secondary fw-semibold">Technical Specifications</label>
                        <textarea name="specifications" id="modalSpecs" rows="2" class="form-control form-control-custom" placeholder="e.g., 8-Cores, 16-Threads, 5.0GHz Boost, 104MB Cache, AM5 Socket"></textarea>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small text-secondary fw-semibold">Supplier Unit Cost (₱)</label>
                        <input type="number" step="0.01" name="unit_cost" id="modalCost" class="form-control form-control-custom" placeholder="0.00" value="0.00">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-secondary fw-semibold">Selling Price (₱) *</label>
                        <input type="number" step="0.01" name="selling_price" id="modalPrice" class="form-control form-control-custom" placeholder="0.00" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-secondary fw-semibold">Initial Quantity</label>
                        <input type="number" name="quantity" id="modalQuantity" class="form-control form-control-custom" value="0" min="0">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-secondary fw-semibold">Min Alert Threshold</label>
                        <input type="number" name="min_threshold" id="modalThreshold" class="form-control form-control-custom" value="5" min="1">
                    </div>
                </div>
            </div>

            <div class="modal-footer modal-footer-custom">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-cyber-primary btn-sm" id="modalSubmitBtn">Save Component</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<?php if ($is_admin): ?>
<form method="POST" action="products.php" id="deleteForm">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="product_id" id="deleteProductId">
</form>
<?php endif; ?>

<script>
function prepareAddModal() {
    document.getElementById('modalAction').value = 'add';
    document.getElementById('modalProductId').value = '';
    document.getElementById('modalTitle').innerHTML = '<i class="bi bi-cpu text-info me-2"></i>Add Hardware Component';
    document.getElementById('modalSubmitBtn').innerText = 'Add Component';
    
    document.getElementById('modalSku').value = '';
    document.getElementById('modalName').value = '';
    document.getElementById('modalBrand').value = '';
    document.getElementById('modalCategory').value = '';
    document.getElementById('modalLocation').value = 'Shelf A1';
    document.getElementById('modalSpecs').value = '';
    document.getElementById('modalCost').value = '0.00';
    document.getElementById('modalPrice').value = '0.00';
    document.getElementById('modalQuantity').value = '0';
    document.getElementById('modalThreshold').value = '5';
}

function prepareEditModal(p) {
    document.getElementById('modalAction').value = 'edit';
    document.getElementById('modalProductId').value = p.id;
    document.getElementById('modalTitle').innerHTML = '<i class="bi bi-pencil-square text-info me-2"></i>Edit: ' + p.name;
    document.getElementById('modalSubmitBtn').innerText = 'Update Component';

    document.getElementById('modalSku').value = p.sku;
    document.getElementById('modalName').value = p.name;
    document.getElementById('modalBrand').value = p.brand;
    document.getElementById('modalCategory').value = p.category_id;
    document.getElementById('modalLocation').value = p.location || '';
    document.getElementById('modalSpecs').value = p.specifications || '';
    document.getElementById('modalCost').value = p.unit_cost;
    document.getElementById('modalPrice').value = p.selling_price;
    document.getElementById('modalQuantity').value = p.quantity;
    document.getElementById('modalThreshold').value = p.min_threshold;

    const modal = new bootstrap.Modal(document.getElementById('productModal'));
    modal.show();
}

function confirmDelete(id, name) {
    if (confirm("Are you sure you want to permanently delete '" + name + "'?")) {
        document.getElementById('deleteProductId').value = id;
        document.getElementById('deleteForm').submit();
    }
}

function filterProducts() {
    const cat = document.getElementById('categoryFilter').value;
    const status = document.getElementById('stockStatusFilter').value;
    const rows = document.querySelectorAll('#productsTable tbody tr');

    rows.forEach(row => {
        const rowCat = row.getAttribute('data-category');
        const rowStatus = row.getAttribute('data-status');

        const matchCat = (cat === 'ALL' || rowCat === cat);
        const matchStatus = (status === 'ALL' || rowStatus === status);

        if (matchCat && matchStatus) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
