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
// Profile page — visible only to admin and super_admin via permission `profile.settings`.
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/permissions.php';
require_once __DIR__ . '/includes/error-handler.php';

requireLogin();
requirePermission(PERM_PROFILE_SETTINGS);

$user = currentUser();
if (!$user) {
    // Shouldn't happen because requireLogin() exits, but be safe.
    renderError(403, '403 — Forbidden', 'You must be signed in to view this page.', 'index.php', 'Sign in');
}

include __DIR__ . '/includes/header.php';
?>

<div class="row">
  <div class="col-md-8">
    <div class="card shadow-sm">
      <div class="card-body">
        <h4 class="card-title">Profile</h4>
        <p><strong>Username:</strong> <?= htmlspecialchars($user['username'] ?? '') ?></p>
        <p><strong>Role:</strong> <?= htmlspecialchars(str_replace('_',' ', $user['role_name'] ?? '')) ?></p>
        <p class="text-muted">Use this page to review account details. To change your password, use the Change Password page.</p>
        <a href="change-password.php" class="btn btn-sm btn-outline-primary">Change Password</a>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php';
