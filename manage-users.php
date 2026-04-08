<?php
/*
 * Project: COC Verification system for sidama region
 * Purpose: Master's Degree Thesis 
 * * Description: This software was developed to solve a real-world problem as part of 
 * the graduation requirements for the Master's Program.
 * * Author: Dawit Birru Hurisso
 * Institution: Czech University of Life Sciences Prague (CZU)
 * Graduate Year: 2026
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/error-handler.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

requirePermission(PERM_USER_MANAGE);

$dbh = getPDO();
$message = '';

// handle actions: save_role, create_user, delete_user
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $message = 'Invalid request.';
    } else {
        $action = $_POST['action'] ?? 'save_role';

        if ($action === 'save_role') {
            $userId = (int)($_POST['user_id'] ?? 0);
            $roleId = (int)($_POST['role_id'] ?? 0);
            if ($userId > 0 && $roleId > 0) {
                $stmt = $dbh->prepare("UPDATE admin SET role_id = :role_id WHERE id = :id");
                $stmt->execute([':role_id' => $roleId, ':id' => $userId]);
                $message = 'Role updated successfully.';
            }
        } elseif ($action === 'create_user') {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $roleId = (int)($_POST['role_id'] ?? 0);
            if ($username === '' || $password === '') {
                $message = 'Please enter username and password.';
            } else {
                // insert user; use password_hash for modern storage
                try {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $dbh->prepare("INSERT INTO admin (UserName, Password, role_id) VALUES (:u, :p, :r)");
                    $stmt->execute([':u' => $username, ':p' => $hash, ':r' => $roleId ?: null]);
                    $message = 'User created.';
                } catch (Exception $e) {
                    $message = 'Error creating user: ' . $e->getMessage();
                }
            }
        } elseif ($action === 'delete_user') {
            $userId = (int)($_POST['user_id'] ?? 0);
            if ($userId > 0) {
                if ($userId === ($_SESSION['user']['id'] ?? 0)) {
                    $message = 'You cannot delete your own account.';
                } else {
                    $stmt = $dbh->prepare("DELETE FROM admin WHERE id = :id");
                    $stmt->execute([':id' => $userId]);
                    $message = 'User deleted.';
                }
            }
        }
    }
}

$roles = $dbh->query("SELECT id, name FROM roles ORDER BY name")->fetchAll();

$q = trim($_GET['q'] ?? '');
$params = [];
$sql = "SELECT a.id, a.UserName, a.role_id, r.name AS role_name
    FROM admin a
    LEFT JOIN roles r ON a.role_id = r.id";
if ($q !== '') {
    $sql .= " WHERE a.UserName LIKE :q";
    $params[':q'] = "%$q%";
}
$sql .= " ORDER BY a.UserName LIMIT 500";
$stmt = $dbh->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Users &amp; Roles</h4>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <form class="d-flex" method="get">
        <input type="text" name="q" class="form-control form-control-sm me-2" placeholder="Search username" value="<?= htmlspecialchars($q) ?>">
        <button class="btn btn-outline-secondary btn-sm" type="submit">Search</button>
    </form>

    <div>
        <button id="toggleNewUser" class="btn btn-primary btn-sm">Add User</button>
    </div>
</div>

<div id="newUserBox" class="card mb-3" style="display:none;">
    <div class="card-body">
        <form method="post" class="row g-2">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
            <input type="hidden" name="action" value="create_user">
            <div class="col-md-3">
                <input name="username" class="form-control form-control-sm" placeholder="Username" required>
            </div>
            <div class="col-md-3">
                <input name="password" type="password" class="form-control form-control-sm" placeholder="Password" required>
            </div>
            <div class="col-md-3">
                <select name="role_id" class="form-select form-select-sm">
                    <option value="">(No role)</option>
                    <?php foreach ($roles as $role): ?>
                        <option value="<?= (int)$role['id'] ?>"><?= htmlspecialchars($role['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <button class="btn btn-success btn-sm" type="submit">Create</button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Username</th>
                        <th>Role</th>
                        <th style="width: 160px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?= htmlspecialchars($u['UserName']) ?></td>
                        <td>
                            <form method="post" class="d-flex align-items-center gap-2">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
                                <input type="hidden" name="action" value="save_role">
                                <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                <select name="role_id" class="form-select form-select-sm">
                                    <option value="">Select role...</option>
                                    <?php foreach ($roles as $role): ?>
                                        <option value="<?= (int)$role['id'] ?>"
                                            <?= $u['role_id'] == $role['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($role['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="btn btn-sm btn-outline-primary" type="submit">Save</button>
                            </form>
                        </td>
                        <td>
                            <form method="post" class="d-inline confirm-form" data-confirm-title="Delete User" data-confirm-message="Are you sure you want to delete this user? This action cannot be undone." data-confirm-class="danger">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
                                <input type="hidden" name="action" value="delete_user">
                                <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$users): ?>
                    <tr>
                        <td colspan="3" class="text-center py-4 text-muted">
                            No users found.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script>
// toggle new user box
document.addEventListener('DOMContentLoaded', function () {
    var btn = document.getElementById('toggleNewUser');
    var box = document.getElementById('newUserBox');
    if (btn && box) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            box.style.display = box.style.display === 'none' ? 'block' : 'none';
            if (box.style.display === 'block') {
                var input = box.querySelector('input[name="username"]');
                if (input) input.focus();
            }
        });
    }
});
</script>
