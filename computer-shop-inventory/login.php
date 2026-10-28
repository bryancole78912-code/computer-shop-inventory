<?php
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        try {
            $pdo = get_db_connection();
            // Support both 'auditor' and 'audit' for the audit compliance role
            $search_user = ($username === 'audit') ? 'auditor' : $username;
            $stmt = $pdo->prepare("SELECT * FROM users WHERE (username = ? OR username = ?) AND status = 'active' LIMIT 1");
            $stmt->execute([$username, $search_user]);
            $user = $stmt->fetch();

            $valid = false;
            if ($user) {
                // Check bcrypt hash or quick demo password
                if (password_verify($password, $user['password']) || 
                   ($username === 'admin' && $password === 'admin123') ||
                   ($username === 'staff' && $password === 'staff123') ||
                   (($username === 'auditor' || $username === 'audit') && ($password === 'auditor123' || $password === 'audit123'))) {
                    $valid = true;
                }
            }

            if ($valid) {
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['username']  = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email']     = $user['email'];
                $_SESSION['role']      = $user['role'];

                set_flash('success', 'Welcome back, ' . $user['full_name'] . '!');
                header('Location: index.php');
                exit;
            } else {
                $error = 'Invalid username or password.';
            }
        } catch (Exception $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | TechStock Computer Shop Inventory</title>
    <link rel="icon" type="image/png" href="assets/images/logo_icon_badge.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/galaxy.js" defer></script>
    <style>
        .login-bg {
            min-height: 100vh;
            background: transparent;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-card {
            width: 100%;
            max-width: 530px;
            background: rgba(13, 20, 38, 0.90);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(56, 189, 248, 0.35);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.8), 0 0 30px rgba(56, 189, 248, 0.25);
            overflow: hidden;
            color: #ffffff;
        }
        .pc-setup-badge {
            width: 72px;
            height: 72px;
            margin: 0 auto 12px;
            background: radial-gradient(circle, rgba(56, 189, 248, 0.22) 0%, rgba(13, 20, 38, 0.8) 70%);
            border: 2px solid rgba(56, 189, 248, 0.45);
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 0 25px rgba(56, 189, 248, 0.35), inset 0 0 15px rgba(56, 189, 248, 0.2);
            transition: all 0.3s ease;
        }
        .pc-setup-badge:hover {
            transform: scale(1.05);
            box-shadow: 0 0 35px rgba(56, 189, 248, 0.55), inset 0 0 20px rgba(56, 189, 248, 0.3);
        }
        .pc-setup-badge i {
            font-size: 2.5rem;
            color: #38bdf8;
            filter: drop-shadow(0 0 12px rgba(56, 189, 248, 0.8));
        }
        .brand-hero-box {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 6px 12px;
            border-radius: 18px;
            transition: transform 0.3s ease;
        }
        .brand-hero-box:hover {
            transform: scale(1.03);
        }
        .brand-hero-img {
            max-width: 280px;
            height: auto;
            display: block;
            margin: 0 auto;
            filter: drop-shadow(0 0 20px rgba(56, 189, 248, 0.45));
        }
        .role-cards-grid {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .role-select-card {
            background: rgba(15, 23, 42, 0.82);
            border: 1px solid rgba(147, 197, 253, 0.18);
            border-radius: 14px;
            padding: 13px 15px;
            transition: all 0.25s ease;
        }
        .role-select-card:hover {
            background: rgba(23, 33, 56, 0.92);
        }
        .role-card-admin:focus-within, .role-card-admin:hover {
            border-color: rgba(56, 189, 248, 0.75);
            box-shadow: 0 4px 22px rgba(56, 189, 248, 0.3);
        }
        .role-card-audit:focus-within, .role-card-audit:hover {
            border-color: rgba(245, 158, 11, 0.75);
            box-shadow: 0 4px 22px rgba(245, 158, 11, 0.3);
        }
        .role-card-staff:focus-within, .role-card-staff:hover {
            border-color: rgba(16, 185, 129, 0.75);
            box-shadow: 0 4px 22px rgba(16, 185, 129, 0.3);
        }
        .role-icon-box {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.15rem;
            flex-shrink: 0;
        }
        .role-sub {
            font-size: 0.72rem;
            color: #94a3b8;
        }
        .role-desc {
            font-size: 0.78rem;
            color: #cbd5e1;
            margin-top: 3px;
            line-height: 1.35;
        }
        .badge-role.auditor {
            background: rgba(245, 158, 11, 0.2);
            color: #fcd34d;
            border: 1px solid rgba(245, 158, 11, 0.4);
        }
        .btn-xs {
            font-size: 0.73rem;
            padding: 0.2rem 0.55rem;
            border-radius: 6px;
        }
    </style>
</head>
<body class="login-bg">

<!-- 3D Galaxy Canvas -->
<canvas id="galaxyStarsCanvas"></canvas>

<div class="login-card">
    <div class="p-4 text-center border-bottom border-secondary border-opacity-25" style="background: rgba(18, 27, 49, 0.75);">
        <div class="brand-hero-box mb-2">
            <img src="assets/images/logo_full_darkmode.png" alt="TECHSTOCK Computer Shop Inventory" class="brand-hero-img" onerror="this.onerror=null; this.src='assets/images/logo_full_badge.png';">
        </div>
        <div class="text-secondary small">Computer Parts & Peripherals Management Portal</div>
    </div>

    <div class="p-4">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger py-2 small mb-3">
                <i class="bi bi-exclamation-octagon me-1"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <div class="mb-3 text-center">
            <span class="badge bg-dark bg-opacity-75 border border-secondary text-info small px-3 py-1 text-uppercase tracking-wider">
                <i class="bi bi-shield-lock me-1"></i> Enter Password to Access Role
            </span>
        </div>

        <div class="role-cards-grid">
            <!-- 1. Admin Role Card -->
            <div class="role-select-card role-card-admin">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <div class="d-flex align-items-center gap-2">
                        <span class="role-icon-box bg-info bg-opacity-25 text-info">
                            <i class="bi bi-shield-lock-fill"></i>
                        </span>
                        <div>
                            <div class="fw-bold text-white">Administrator</div>
                            <div class="role-sub text-secondary">Bryan Cahayag Cole (Full Control)</div>
                        </div>
                    </div>
                    <span class="badge-role admin">ADMIN</span>
                </div>
                <div class="role-desc">Full System Oversight, Catalog, Stock Operations, Users & CSV Export</div>

                <form method="POST" action="login.php" class="mt-3">
                    <input type="hidden" name="username" value="admin">
                    <div class="input-group input-group-sm mb-2">
                        <span class="input-group-text bg-dark border-secondary text-info"><i class="bi bi-key-fill"></i></span>
                        <input type="password" name="password" id="phpPassAdmin" class="form-control form-control-custom" placeholder="Enter Admin Password" required autocomplete="current-password">
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="togglePhpPass('phpPassAdmin', this)" title="Show/Hide">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    <button type="submit" class="btn btn-sm btn-cyber-primary w-100 py-2">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Sign In as Admin
                    </button>
                </form>
            </div>

            <!-- 2. Audit Role Card -->
            <div class="role-select-card role-card-audit">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <div class="d-flex align-items-center gap-2">
                        <span class="role-icon-box bg-warning bg-opacity-25 text-warning">
                            <i class="bi bi-clipboard2-check-fill"></i>
                        </span>
                        <div>
                            <div class="fw-bold text-white">Auditor</div>
                            <div class="role-sub text-secondary">Prof. Noel Montecillo (Inspection)</div>
                        </div>
                    </div>
                    <span class="badge-role auditor">AUDIT</span>
                </div>
                <div class="role-desc">Read-Only Audit Trail, Stock Verification, Low-Stock Tracking & Reports</div>

                <form method="POST" action="login.php" class="mt-3">
                    <input type="hidden" name="username" value="audit">
                    <div class="input-group input-group-sm mb-2">
                        <span class="input-group-text bg-dark border-secondary text-warning"><i class="bi bi-key-fill"></i></span>
                        <input type="password" name="password" id="phpPassAudit" class="form-control form-control-custom" placeholder="Enter Audit Password" required autocomplete="current-password">
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="togglePhpPass('phpPassAudit', this)" title="Show/Hide">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    <button type="submit" class="btn btn-sm btn-outline-warning w-100 py-2">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Sign In as Audit
                    </button>
                </form>
            </div>

            <!-- 3. Staff Role Card -->
            <div class="role-select-card role-card-staff">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <div class="d-flex align-items-center gap-2">
                        <span class="role-icon-box bg-success bg-opacity-25 text-success">
                            <i class="bi bi-boxes"></i>
                        </span>
                        <div>
                            <div class="fw-bold text-white">Inventory Staff</div>
                            <div class="role-sub text-secondary">Alex Rivera (Warehouse Operations)</div>
                        </div>
                    </div>
                    <span class="badge-role staff">STAFF</span>
                </div>
                <div class="role-desc">Daily Restocking (Stock-In), Customer Sales (Stock-Out) & Hardware Catalog</div>

                <form method="POST" action="login.php" class="mt-3">
                    <input type="hidden" name="username" value="staff">
                    <div class="input-group input-group-sm mb-2">
                        <span class="input-group-text bg-dark border-secondary text-success"><i class="bi bi-key-fill"></i></span>
                        <input type="password" name="password" id="phpPassStaff" class="form-control form-control-custom" placeholder="Enter Staff Password" required autocomplete="current-password">
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="togglePhpPass('phpPassStaff', this)" title="Show/Hide">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    <button type="submit" class="btn btn-sm btn-cyber-success w-100 py-2">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Sign In as Staff
                    </button>
                </form>
            </div>
        </div>

        <div class="mt-3 text-center">
            <a href="demo.html" class="text-info small text-decoration-none fw-medium">
                <i class="bi bi-window-fullscreen me-1"></i> Open Standalone Browser Demo (No MySQL needed)
            </a>
        </div>
    </div>

    <div class="p-3 text-center border-top border-secondary border-opacity-25 small text-secondary" style="background: rgba(10, 14, 26, 0.85);">
        <div>Prepared by: <strong>Bryan Cahayag Cole</strong> &bull; 2026</div>
    </div>
</div>

<script>
function fillPhpPass(inputId, p) {
    const el = document.getElementById(inputId);
    if (el) {
        el.value = p;
        el.focus();
    }
}
function togglePhpPass(inputId, btn) {
    const el = document.getElementById(inputId);
    if (!el) return;
    if (el.type === 'password') {
        el.type = 'text';
        btn.innerHTML = '<i class="bi bi-eye-slash"></i>';
    } else {
        el.type = 'password';
        btn.innerHTML = '<i class="bi bi-eye"></i>';
    }
}
</script>

</body>
</html>
