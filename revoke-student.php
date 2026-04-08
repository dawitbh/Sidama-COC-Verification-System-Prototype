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

requirePermission(PERM_CERT_REVOKE);

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
$reason = trim($_POST['reason'] ?? '');
if ($id <= 0) {
    setFlash('error', 'Invalid record id.');
    header('Location: manage-students.php');
    exit;
}

if ($reason === '') {
    setFlash('error', 'Please provide a reason for revocation.');
    header('Location: manage-students.php');
    exit;
}
if ($id <= 0) {
    setFlash('error', 'Invalid record id.');
    header('Location: manage-students.php');
    exit;
}

if ($reason === '') {
    setFlash('error', 'Please provide a reason for revocation.');
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

    $upd = $dbh->prepare('UPDATE tblstudents SET status = :status, revoked_by = :revoked_by, revoked_at = :revoked_at, revoke_reason = :revoke_reason WHERE StudentId = :id');
    $upd->execute([
        ':status' => 'revoked',
        ':revoked_by' => $userId,
        ':revoked_at' => $now,
        ':revoke_reason' => $reason,
        ':id' => $id,
    ]);

    // Audit via helper
    $details = json_encode(['reason' => $reason]);
    logAudit('REVOKE', 'CERTIFICATE', $id, $details);

    $dbh->commit();
    setFlash('success', 'Certificate revoked.');
    header('Location: manage-students.php?revoked=1');
    exit;
} catch (Exception $e) {
    if ($dbh->inTransaction()) $dbh->rollBack();
    error_log('revoke-student error: ' . $e->getMessage());
    setFlash('error', 'Unable to revoke certificate.');
    header('Location: manage-students.php?revoked=0');
    exit;
}
