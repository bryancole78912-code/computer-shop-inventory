<?php
$page_title = 'User Management';
require_once __DIR__ . '/includes/header.php';
require_role('admin');

$pdo = get_db_connection();

// Process User Creation / Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $username  = trim($_POST['username'] ?? '');
        $password  = trim($_POST['password'] ?? '');
        $full_name = trim($_POST['full_name'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $role      = $_POST['role'] ?? 'staff';

        if (empty($username) || empty($password) || empty($full_name) || empty($email)) {
            set_flash('danger', 'All fields are required to register a new user.');
        } else {
            try {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, email, role) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$username, $hash, $full_name, $email, $role]);
                set_flash('success', "User account for '{$full_name}' ({$role}) created successfully!");
            } catch (PDOException $e) {
                set_flash('danger', "Failed to create user: Username or Email may already exist.");
            }
        }
        header('Location: users.php');
        exit;
    }

    if ($action === 'edit') {
        $user_id   = (int)$_POST['user_id'];
        $full_name = trim($_POST['full_name'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $role      = $_POST['role'] ?? 'staff';
        $status    = $_POST['status'] ?? 'active';
        $new_pass  = trim($_POST['new_password'] ?? '');

        try {
            if (!empty($new_pass)) {
                $hash = password_hash($new_pass, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, role = ?, status = ?, password = ? WHERE id = ?");
                $stmt->execute([$full_name, $email, $role, $status, $hash, $user_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, role = ?, status = ? WHERE id = ?");
                $stmt->execute([$full_name, $email, $role, $status, $user_id]);
            }
            set_flash('success', "User '{$full_name}' updated successfully!");
        } catch (PDOException $e) {
            set_flash('danger', "Database error updating user: " . $e->getMessage());
        }
        header('Location: users.php');
        exit;
    }
}

// Fetch all users
$users = $pdo->query("SELECT id, username, full_name, email, role, status, created_at FROM users ORDER BY id ASC")->fetchAll();
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h2 class="h4 text-white fw-bold mb-1"><i class="bi bi-people-fill text-info me-2"></i>User & Role Administration</h2>
        <div class="text-secondary small">Manage computer shop personnel accounts and role-based permissions (Admin, Staff, Auditor)</div>
    </div>
    <button type="button" class="btn btn-cyber-primary btn-sm" data-bs-toggle="modal" data-bs-target="#userModal" onclick="prepareAddUserModal()">
        <i class="bi bi-person-plus-fill me-1"></i> Add System User
    </button>
</div>

<div class="card-custom">
    <div class="table-responsive">
        <table class="table-custom">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Email</th>
                    <th>Role Classification</th>
                    <th>Account Status</th>
                    <th>Date Registered</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td>
                            <div class="text-white fw-semibold"><?= htmlspecialchars($u['full_name']) ?></div>
                            <div class="small text-secondary font-monospace">@<?= htmlspecialchars($u['username']) ?></div>
                        </td>
                        <td class="text-secondary small"><?= htmlspecialchars($u['email']) ?></td>
                        <td>
                            <span class="badge-role <?= htmlspecialchars($u['role']) ?>"><?= htmlspecialchars($u['role']) ?></span>
                        </td>
                        <td>
                            <?php if ($u['status'] === 'active'): ?>
                                <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-50">Active</span>
                            <?php else: ?>
                                <span class="badge bg-danger bg-opacity-25 text-danger border border-danger border-opacity-50">Disabled</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-secondary small"><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
                        <td class="text-end">
                            <button type="button" class="btn btn-outline-info btn-sm py-1 px-2" 
                                onclick='prepareEditUserModal(<?= json_encode($u, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                                <i class="bi bi-pencil-square me-1"></i> Edit
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add / Edit User Modal -->
<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="users.php" class="modal-content modal-content-custom">
            <input type="hidden" name="action" id="userModalAction" value="add">
            <input type="hidden" name="user_id" id="userModalId" value="">

            <div class="modal-header modal-header-custom">
                <h5 class="modal-title text-white" id="userModalTitle">Add System User</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label small text-secondary fw-semibold">Full Name *</label>
                    <input type="text" name="full_name" id="userFullName" class="form-control form-control-custom" placeholder="e.g., John Reyes" required>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label small text-secondary fw-semibold">Username *</label>
                        <input type="text" name="username" id="userUsername" class="form-control form-control-custom" placeholder="jreyes" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label small text-secondary fw-semibold">Email Address *</label>
                        <input type="email" name="email" id="userEmail" class="form-control form-control-custom" placeholder="jreyes@shop.local" required>
                    </div>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label small text-secondary fw-semibold">Role *</label>
                        <select name="role" id="userRole" class="form-select form-select-custom" required>
                            <option value="staff">Staff (Inventory Ops)</option>
                            <option value="admin">Admin (Full Control)</option>
                            <option value="auditor">Auditor (Read Only)</option>
                        </select>
                    </div>
                    <div class="col-6" id="statusGroup" style="display: none;">
                        <label class="form-label small text-secondary fw-semibold">Status</label>
                        <select name="status" id="userStatus" class="form-select form-select-custom">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small text-secondary fw-semibold" id="passwordLabel">Password *</label>
                    <input type="password" name="password" id="userPassword" class="form-control form-control-custom" placeholder="••••••••">
                    <div class="small text-muted mt-1" id="passwordHelp" style="display: none;">Leave blank to preserve current password.</div>
                </div>
            </div>

            <div class="modal-footer modal-footer-custom">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-cyber-primary btn-sm" id="userSubmitBtn">Save Account</button>
            </div>
        </form>
    </div>
</div>

<script>
function prepareAddUserModal() {
    document.getElementById('userModalAction').value = 'add';
    document.getElementById('userModalId').value = '';
    document.getElementById('userModalTitle').innerText = 'Add System User';
    document.getElementById('userSubmitBtn').innerText = 'Create Account';
    document.getElementById('userFullName').value = '';
    document.getElementById('userUsername').value = '';
    document.getElementById('userUsername').removeAttribute('readonly');
    document.getElementById('userEmail').value = '';
    document.getElementById('userRole').value = 'staff';
    document.getElementById('statusGroup').style.display = 'none';
    document.getElementById('passwordLabel').innerText = 'Password *';
    document.getElementById('userPassword').setAttribute('required', 'required');
    document.getElementById('passwordHelp').style.display = 'none';
}

function prepareEditUserModal(u) {
    document.getElementById('userModalAction').value = 'edit';
    document.getElementById('userModalId').value = u.id;
    document.getElementById('userModalTitle').innerText = 'Edit User: ' + u.full_name;
    document.getElementById('userSubmitBtn').innerText = 'Save Changes';
    document.getElementById('userFullName').value = u.full_name;
    document.getElementById('userUsername').value = u.username;
    document.getElementById('userUsername').setAttribute('readonly', 'readonly');
    document.getElementById('userEmail').value = u.email;
    document.getElementById('userRole').value = u.role;
    document.getElementById('userStatus').value = u.status;
    document.getElementById('statusGroup').style.display = 'block';
    document.getElementById('passwordLabel').innerText = 'New Password (Optional)';
    document.getElementById('userPassword').removeAttribute('required');
    document.getElementById('userPassword').value = '';
    document.getElementById('passwordHelp').style.display = 'block';

    const modal = new bootstrap.Modal(document.getElementById('userModal'));
    modal.show();
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
