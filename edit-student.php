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

// Enforce permissions based on action: creating requires PERM_CERT_CREATE, editing requires PERM_CERT_EDIT
$idForPerm = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($idForPerm > 0) {
    requirePermission(PERM_CERT_EDIT);
} else {
    requirePermission(PERM_CERT_CREATE);
}

$dbh = getPDO();
$id  = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Creation requires PERM_CERT_CREATE permission
if ($id === 0) {
    requirePermission(PERM_CERT_CREATE);
}

$student = [
    'RollId'          => '',
    'StudentName'     => '',
    'Occupation'      => '',
    'Level'           => '',
    'activation_date' => '',
    'expiry_date'     => '',
    'Venue'           => '',
    'ControlNo'       => '',
    'status'          => 'pending',
];

if ($id > 0) {
    $stmt = $dbh->prepare("SELECT * FROM tblstudents WHERE StudentId = :id");
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    if ($row) {
        // merge fetched row with defaults so missing columns keep default values
        $student = array_merge($student, $row);
        // Permission check: editing requires PERM_CERT_EDIT for both pending and non-pending
        $status = $student['status'] ?? 'pending';
        requirePermission(PERM_CERT_EDIT);
    }
}

// use flash messages for user-visible notices

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid request.';
    } else {
        $student['RollId']          = trim($_POST['RollId'] ?? '');
        $student['StudentName']     = trim($_POST['StudentName'] ?? '');
        $student['Occupation']      = trim($_POST['Occupation'] ?? '');
        $student['Level']           = trim($_POST['Level'] ?? '');
        $student['activation_date'] = trim($_POST['activation_date'] ?? '');
        $student['expiry_date']     = trim($_POST['expiry_date'] ?? '');
        $student['Venue']           = trim($_POST['Venue'] ?? '');
        $student['ControlNo']       = trim($_POST['ControlNo'] ?? '');

        if ($student['RollId'] === '' || $student['StudentName'] === '') {
            setFlash('error', 'Certificate number and candidate name are required.');
        } else {
            if ($id > 0) {
                // Permission enforcement for POST update
                requirePermission(PERM_CERT_EDIT);
                $sql = "UPDATE tblstudents SET
                        RollId=:RollId, StudentName=:StudentName, Occupation=:Occupation,
                        Level=:Level, activation_date=:activation_date, expiry_date=:expiry_date,
                        Venue=:Venue, ControlNo=:ControlNo
                        WHERE StudentId=:id";
            } else {
                $sql = "INSERT INTO tblstudents
                        (RollId, StudentName, Occupation, Level, activation_date, expiry_date, Venue, ControlNo)
                        VALUES
                        (:RollId, :StudentName, :Occupation, :Level, :activation_date, :expiry_date, :Venue, :ControlNo)";
            }

            $stmt = $dbh->prepare($sql);
            $params = [
                ':RollId'          => $student['RollId'],
                ':StudentName'     => $student['StudentName'],
                ':Occupation'      => $student['Occupation'],
                ':Level'           => $student['Level'],
                ':activation_date' => $student['activation_date'] ?: null,
                ':expiry_date'     => $student['expiry_date'] ?: null,
                ':Venue'           => $student['Venue'],
                ':ControlNo'       => $student['ControlNo'],
            ];
            if ($id > 0) {
                $params[':id'] = $id;
            }
                if (empty($error)) {
                $stmt->execute($params);

                if ($id === 0) {
                    $id = (int)$dbh->lastInsertId();
                    // log create
                    $details = 'RollId:' . $student['RollId'] . ';Name:' . $student['StudentName'];
                    logAudit('CREATE', 'CERTIFICATE', $id, $details);
                } else {
                    // log update
                    $details = 'RollId:' . $student['RollId'] . ';Name:' . $student['StudentName'];
                    logAudit('UPDATE', 'CERTIFICATE', $id, $details);
                }

                setFlash('success', 'Certificate saved successfully.');
            }
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card shadow-sm border-0 mt-4">
            <div class="card-body">
                <h4 class="card-title mb-3">
                    <?= $id > 0 ? 'Edit Certificate' : 'Add Certificate' ?>
                </h4>

                <?php renderFlash(); ?>

                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">

                    <div class="mb-3">
                        <label class="form-label">Certificate Number (RollId)</label>
                        <input type="text" name="RollId" class="form-control"
                               value="<?= htmlspecialchars($student['RollId']) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Candidate Name</label>
                        <input type="text" name="StudentName" class="form-control"
                               value="<?= htmlspecialchars($student['StudentName']) ?>" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Occupation</label>
                            <input type="text" name="Occupation" class="form-control"
                                   value="<?= htmlspecialchars($student['Occupation']) ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Level</label>
                            <input type="text" name="Level" class="form-control"
                                   value="<?= htmlspecialchars($student['Level']) ?>">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Activation Date</label>
                            <input type="date" name="activation_date" class="form-control"
                                   value="<?= htmlspecialchars($student['activation_date']) ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Expiry Date</label>
                            <input type="date" name="expiry_date" class="form-control"
                                   value="<?= htmlspecialchars($student['expiry_date']) ?>">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Venue</label>
                            <input type="text" name="Venue" class="form-control"
                                   value="<?= htmlspecialchars($student['Venue']) ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Control Number</label>
                            <input type="text" name="ControlNo" class="form-control"
                                   value="<?= htmlspecialchars($student['ControlNo']) ?>">
                        </div>
                    </div>

                    <button class="btn btn-primary" type="submit">Save</button>
                    <a href="manage-students.php" class="btn btn-link">Back to list</a>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
