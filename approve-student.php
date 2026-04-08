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

requirePermission(PERM_CERT_APPROVE);

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
    setFlash('error', 'Invalid record id.');
    header('Location: manage-students.php');
    exit;
}

$dbh = getPDO();
try {
    $dbh->beginTransaction();

    // lock row
    $stmt = $dbh->prepare('SELECT status FROM tblstudents WHERE StudentId = :id FOR UPDATE');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    if (!$row) {
        $dbh->rollBack();
        setFlash('error', 'Record not found.');
        header('Location: manage-students.php');
        exit;
    }

    $now = date('Y-m-d H:i:s');
    $user = currentUser();
    $userId = $user['id'] ?? null;
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    if (strpos($ip, ',') !== false) { $ip = trim(explode(',', $ip)[0]); }

    $upd = $dbh->prepare('UPDATE tblstudents SET status = :status, approved_by = :approved_by, approved_at = :approved_at, revoked_by = NULL, revoked_at = NULL, revoke_reason = NULL WHERE StudentId = :id');
    $upd->execute([
        ':status' => 'active',
        ':approved_by' => $userId,
        ':approved_at' => $now,
        ':id' => $id,
    ]);

    // Audit via helper
    $details = json_encode(['file' => null, 'note' => 'approved via UI']);
    logAudit('APPROVE', 'CERTIFICATE', $id, $details);

    $dbh->commit();
    setFlash('success', 'Certificate approved.');
    header('Location: manage-students.php?approved=1');
    exit;
} catch (Exception $e) {
    if ($dbh->inTransaction()) $dbh->rollBack();
    error_log('approve-student error: ' . $e->getMessage());
    setFlash('error', 'Unable to approve certificate.');
    header('Location: manage-students.php?approved=0');
    exit;
}
