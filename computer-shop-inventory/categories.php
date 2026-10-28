<?php
$page_title = 'Hardware Categories';
require_once __DIR__ . '/includes/header.php';

$pdo = get_db_connection();
$can_edit = !has_role('auditor');
$is_admin = has_role('admin');

// Handle Add / Edit / Delete Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $can_edit) {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $name        = trim($_POST['name'] ?? '');
        $code        = strtoupper(trim($_POST['code'] ?? ''));
        $description = trim($_POST['description'] ?? '');
        $icon        = trim($_POST['icon'] ?? 'bi-cpu');

        if (empty($name) || empty($code)) {
            set_flash('danger', 'Category Name and Code are required.');
        } else {
            try {
                if ($action === 'add') {
                    $stmt = $pdo->prepare("INSERT INTO categories (name, code, description, icon) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$name, $code, $description, $icon]);
                    set_flash('success', "Category '$name' added successfully!");
                } else if ($action === 'edit') {
                    $id = (int)$_POST['category_id'];
                    $stmt = $pdo->prepare("UPDATE categories SET name = ?, code = ?, description = ?, icon = ? WHERE id = ?");
                    $stmt->execute([$name, $code, $description, $icon, $id]);
                    set_flash('success', "Category '$name' updated successfully!");
                }
            } catch (PDOException $e) {
                set_flash('danger', "Database error: " . $e->getMessage());
            }
        }
        header('Location: categories.php');
        exit;
    }

    if ($action === 'delete' && $is_admin) {
        $id = (int)$_POST['category_id'];
        try {
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$id]);
            set_flash('success', "Category deleted successfully.");
        } catch (PDOException $e) {
            set_flash('danger', "Cannot delete category: It has active products assigned to it.");
        }
        header('Location: categories.php');
        exit;
    }
}

// Fetch categories with product counts
$categories = $pdo->query("
    SELECT c.*, COUNT(p.id) as product_count, COALESCE(SUM(p.quantity), 0) as total_units
    FROM categories c
    LEFT JOIN products p ON c.id = p.category_id AND p.status = 'active'
    GROUP BY c.id
    ORDER BY c.name ASC
")->fetchAll();
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h2 class="h4 text-white fw-bold mb-1"><i class="bi bi-tags-fill text-info me-2"></i>Hardware Component Categories</h2>
        <div class="text-secondary small">Group and organize inventory by part classification (CPUs, GPUs, RAM, Storage, etc.)</div>
    </div>
    <?php if ($can_edit): ?>
        <button type="button" class="btn btn-cyber-primary btn-sm" data-bs-toggle="modal" data-bs-target="#categoryModal" onclick="prepareAddCatModal()">
            <i class="bi bi-plus-lg me-1"></i> Add Category
        </button>
    <?php endif; ?>
</div>

<div class="row g-3">
    <?php foreach ($categories as $cat): ?>
        <div class="col-md-6 col-lg-4">
            <div class="card-custom h-100 p-3">
                <div class="d-flex align-items-start justify-content-between mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <div class="stat-icon primary" style="width: 42px; height: 42px; font-size: 1.25rem;">
                            <i class="bi <?= htmlspecialchars($cat['icon'] ?: 'bi-cpu') ?>"></i>
                        </div>
                        <div>
                            <h6 class="text-white mb-0 fw-bold"><?= htmlspecialchars($cat['name']) ?></h6>
                            <span class="badge bg-dark border border-secondary text-info font-monospace"><?= htmlspecialchars($cat['code']) ?></span>
                        </div>
                    </div>
                    <?php if ($can_edit): ?>
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-secondary py-0 px-2" title="Edit" 
                                onclick='prepareEditCatModal(<?= json_encode($cat, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                                <i class="bi bi-pencil"></i>
                            </button>
                            <?php if ($is_admin && $cat['product_count'] == 0): ?>
                                <button type="button" class="btn btn-outline-danger py-0 px-2" title="Delete" onclick="deleteCategory(<?= $cat['id'] ?>, '<?= htmlspecialchars(addslashes($cat['name'])) ?>')">
                                    <i class="bi bi-trash"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <p class="text-secondary small mb-3"><?= htmlspecialchars($cat['description'] ?: 'No description provided.') ?></p>

                <div class="mt-auto pt-2 border-top border-secondary border-opacity-25 d-flex justify-content-between align-items-center small">
                    <span class="text-secondary">Products: <strong class="text-white"><?= $cat['product_count'] ?></strong></span>
                    <span class="text-secondary">Total Stock: <strong class="text-info"><?= $cat['total_units'] ?> units</strong></span>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Add / Edit Category Modal -->
<?php if ($can_edit): ?>
<div class="modal fade" id="categoryModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="categories.php" class="modal-content modal-content-custom">
            <input type="hidden" name="action" id="catModalAction" value="add">
            <input type="hidden" name="category_id" id="catModalId" value="">

            <div class="modal-header modal-header-custom">
                <h5 class="modal-title text-white" id="catModalTitle">Add Hardware Category</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label small text-secondary fw-semibold">Category Name *</label>
                    <input type="text" name="name" id="catName" class="form-control form-control-custom" placeholder="e.g., Liquid Cooling & Fans" required>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label small text-secondary fw-semibold">Category Code *</label>
                        <input type="text" name="code" id="catCode" class="form-control form-control-custom" placeholder="e.g., COOL" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label small text-secondary fw-semibold">Bootstrap Icon</label>
                        <input type="text" name="icon" id="catIcon" class="form-control form-control-custom" placeholder="bi-fan" value="bi-cpu">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small text-secondary fw-semibold">Description</label>
                    <textarea name="description" id="catDesc" rows="3" class="form-control form-control-custom" placeholder="Brief description of this component class..."></textarea>
                </div>
            </div>

            <div class="modal-footer modal-footer-custom">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-cyber-primary btn-sm" id="catSubmitBtn">Save Category</button>
            </div>
        </form>
    </div>
</div>

<form method="POST" action="categories.php" id="deleteCatForm">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="category_id" id="deleteCatId">
</form>

<script>
function prepareAddCatModal() {
    document.getElementById('catModalAction').value = 'add';
    document.getElementById('catModalId').value = '';
    document.getElementById('catModalTitle').innerText = 'Add Hardware Category';
    document.getElementById('catSubmitBtn').innerText = 'Add Category';
    document.getElementById('catName').value = '';
    document.getElementById('catCode').value = '';
    document.getElementById('catIcon').value = 'bi-cpu';
    document.getElementById('catDesc').value = '';
}

function prepareEditCatModal(cat) {
    document.getElementById('catModalAction').value = 'edit';
    document.getElementById('catModalId').value = cat.id;
    document.getElementById('catModalTitle').innerText = 'Edit Category: ' + cat.name;
    document.getElementById('catSubmitBtn').innerText = 'Update Category';
    document.getElementById('catName').value = cat.name;
    document.getElementById('catCode').value = cat.code;
    document.getElementById('catIcon').value = cat.icon || 'bi-cpu';
    document.getElementById('catDesc').value = cat.description || '';

    const modal = new bootstrap.Modal(document.getElementById('categoryModal'));
    modal.show();
}

function deleteCategory(id, name) {
    if (confirm("Are you sure you want to delete category '" + name + "'?")) {
        document.getElementById('deleteCatId').value = id;
        document.getElementById('deleteCatForm').submit();
    }
}
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
