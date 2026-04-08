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

requireLogin();
requirePermission(PERM_PROFILE_SETTINGS);
$user = currentUser();
$dbh  = getPDO();
$message = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid request.';
    } else {
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if ($new === '' || $confirm === '' || $current === '') {
            $error = 'All fields are required.';
        } elseif ($new !== $confirm) {
            $error = 'New passwords do not match.';
        } else {
            $stmt = $dbh->prepare("SELECT Password FROM admin WHERE id = :id");
            $stmt->execute([':id' => $user['id']]);
            $row = $stmt->fetch();

            if ($row) {
                $dbHash = $row['Password'];
                $valid  = false;

                if (strlen($dbHash) === 32 && md5($current) === $dbHash) {
                    $valid = true;
                } elseif (password_verify($current, $dbHash)) {
                    $valid = true;
                }

                if ($valid) {
                    $newHash = password_hash($new, PASSWORD_DEFAULT);
                    $up = $dbh->prepare("UPDATE admin SET Password = :p WHERE id = :id");
                    $up->execute([':p' => $newHash, ':id' => $user['id']]);
                    $message = 'Password updated successfully.';
                } else {
                    $error = 'Current password is incorrect.';
                }
            }
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-sm border-0 mt-4">
            <div class="card-body">
                <h4 class="card-title mb-3">Change Password</h4>

                <?php if ($message): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" name="new_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                    <button class="btn btn-primary" type="submit">Update Password</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . "/includes/footer.php"; ?>
