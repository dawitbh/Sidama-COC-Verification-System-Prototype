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
require_once __DIR__ . '/includes/functions.php';

// RBAC: only these roles may access upload/import pages
requireLogin();
requireRole(['super_admin','admin','data_entry']);

requirePermission(PERM_CERT_IMPORT);

include __DIR__ . '/includes/header.php';
// show inline flashes (upload errors/success)
renderFlash();
?>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card upload-card shadow-lg border-0 mt-5">
            <div class="card-body">
                <h4 class="card-title mb-3">Upload Certificates (Excel / CSV)</h4>
                <p class="text-muted">
                    Upload candidate certificates using an Excel (.xlsx, .xls) or CSV file.
                    Choose how the data should be applied to the existing records.
                </p>

                <form action="importData.php" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">

                    <div class="mb-3">
                        <label class="form-label">Upload Type</label>
                        <select name="upload_type" class="form-select" required>
                            <option value="">Select type...</option>
                            <option value="append">Append new certificates</option>
                            <option value="replace">Replace all certificates</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Excel or CSV File</label>
                        <input type="file" name="file" class="form-control" accept=".csv,.xls,.xlsx" required>
                    </div>

                    <div class="d-flex flex-column align-items-start">
                        <button class="btn btn-primary" type="submit">
                            <i class="bi bi-upload"></i> Upload File
                        </button>
                        <a href="dashboard.php" class="btn btn-home mt-3"><i class="bi bi-house-door-fill home-icon" aria-hidden="true"></i> Home</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
