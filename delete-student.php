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

// Backend enforcement for delete action
requirePermission(PERM_CERT_DELETE);

// Must be POST — no deletes on GET
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Method not allowed.';
    exit;
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    http_response_code(400);
    echo 'Invalid CSRF token.';
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) {
    header('Location: manage-students.php');
    exit;
}

$dbh = getPDO();
// Transaction: delete student + insert audit log
try {
    $dbh->beginTransaction();

    // Delete student
    $del = $dbh->prepare('DELETE FROM tblstudents WHERE StudentId = :id');
    $del->execute([':id' => $id]);

    // Insert audit log
    $user = currentUser();
    $userId = $user['id'] ?? null;
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    // normalize ip (if multiple forwarded addresses, take first)
    if (strpos($ip, ',') !== false) {
        $ip = trim(explode(',', $ip)[0]);
    }

    // Audit via helper
    $details = null;
    logAudit('DELETE', 'CERTIFICATE', $id, $details);

    $dbh->commit();

    header('Location: manage-students.php?deleted=1');
    exit;
} catch (Exception $e) {
    if ($dbh->inTransaction()) {
        $dbh->rollBack();
    }
    // log error to error log but don't expose internals to user
    error_log('delete-student error: ' . $e->getMessage());
    setFlash('error', 'Unable to delete certificate.');
    header('Location: manage-students.php?deleted=0&error=1');
    exit;
}
