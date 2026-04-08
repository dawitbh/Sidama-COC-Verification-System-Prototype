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

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$record = null;
$status = '';
if ($id > 0) {
    $dbh = getPDO();
    $stmt = $dbh->prepare('SELECT * FROM tblstudents WHERE StudentId = :id');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    if ($row) {
        $defaults = [
            'RollId' => '',
            'StudentName' => '',
            'Occupation' => '',
            'Level' => '',
            'activation_date' => '',
            'expiry_date' => null,
            'Venue' => '',
            'ControlNo' => '',
        ];
        $record = array_merge($defaults, $row);
        $status = getCertificateStatus($record['expiry_date'] ?? null);
    }
}

include __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm border-0 mt-5">
            <div class="card-body">
                <h3 class="card-title mb-3 text-center">Certificate Details</h3>

                <?php if ($record): ?>
                    <table class="table table-bordered">
                        <tr>
                            <th>Certificate Number</th>
                            <td><?= htmlspecialchars($record['RollId']) ?></td>
                        </tr>
                        <tr>
                            <th>Candidate Name</th>
                            <td><?= htmlspecialchars($record['StudentName']) ?></td>
                        </tr>
                        <tr>
                            <th>Occupation</th>
                            <td><?= htmlspecialchars($record['Occupation']) ?></td>
                        </tr>
                        <tr>
                            <th>Level</th>
                            <td><?= htmlspecialchars($record['Level']) ?></td>
                        </tr>
                        <tr>
                            <th>Activation Date</th>
                            <td><?= htmlspecialchars($record['activation_date']) ?></td>
                        </tr>
                        <tr>
                            <th>Expiry Date</th>
                            <td><?= htmlspecialchars($record['expiry_date']) ?></td>
                        </tr>
                        <tr>
                            <th>Venue</th>
                            <td><?= htmlspecialchars($record['Venue']) ?></td>
                        </tr>
                        <tr>
                            <th>Control Number</th>
                            <td><?= htmlspecialchars($record['ControlNo']) ?></td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td><?= renderStatusBadge($status, $record['expiry_date'] ?? null) ?></td>
                        </tr>
                    </table>
                    <div class="mt-3">
                        <?php ui_action_button(PERM_CERT_EDIT, '<a href="edit-student.php?id=' . (int)$id . '" class="btn btn-primary">Edit</a>'); ?>
                        <a href="manage-students.php" class="btn btn-link">Back to list</a>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning">Certificate not found.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php';
