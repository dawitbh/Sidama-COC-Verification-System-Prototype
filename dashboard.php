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

$dbh = getPDO();

// Total certificates
$total = 0;
$active = 0;
$expiring = 0;
$expired = 0;

try {
    $stmtTotal = $dbh->prepare("SELECT COUNT(*) FROM tblstudents");
    $stmtTotal->execute();
    $total = (int)$stmtTotal->fetchColumn();

    // discover existing columns so we can use the right date fields
    $existingColsMap = [];
    foreach ($dbh->query("DESCRIBE tblstudents") as $c) {
        $field = isset($c['Field']) ? $c['Field'] : (isset($c[0]) ? $c[0] : null);
        if ($field) {
            $existingColsMap[strtolower($field)] = $field;
        }
    }

    // Pick expiry and activation columns from known candidates
    $expiryCandidates = ['expiry_date','valid_until','expiry','validuntil','expirydate','validuntil'];
    $activationCandidates = ['activation_date','regdate','reg_date','registration_date','issued_on','regdate'];

    $expiryCol = null;
    foreach ($expiryCandidates as $c) {
        if (isset($existingColsMap[$c])) { $expiryCol = $existingColsMap[$c]; break; }
    }
    $activationCol = null;
    foreach ($activationCandidates as $c) {
        if (isset($existingColsMap[$c])) { $activationCol = $existingColsMap[$c]; break; }
    }

    if ($expiryCol && $activationCol) {
        // Use DATE(...) comparisons. If the columns are stored as VARCHAR in 'YYYY-MM-DD' form, DATE() will work.
        // If your DB stores dates in another string format, consider migrating to DATE or adjusting STR_TO_DATE formats.

        // Active: activation_date <= CURDATE() AND expiry_date >= CURDATE()
        $sqlActive = "SELECT COUNT(*) FROM tblstudents WHERE DATE(`" . str_replace('`','', $activationCol) . "`) <= CURDATE() AND DATE(`" . str_replace('`','', $expiryCol) . "`) >= CURDATE()";
        $st = $dbh->prepare($sqlActive);
        $st->execute();
        $active = (int)$st->fetchColumn();

        // Expiring (next 30 days): expiry_date >= CURDATE() AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
        $sqlExp = "SELECT COUNT(*) FROM tblstudents WHERE DATE(`" . str_replace('`','', $expiryCol) . "`) >= CURDATE() AND DATE(`" . str_replace('`','', $expiryCol) . "`) <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)";
        $st2 = $dbh->prepare($sqlExp);
        $st2->execute();
        $expiring = (int)$st2->fetchColumn();

        // Expired: expiry_date < CURDATE()
        $sqlExpired = "SELECT COUNT(*) FROM tblstudents WHERE DATE(`" . str_replace('`','', $expiryCol) . "`) < CURDATE()";
        $st3 = $dbh->prepare($sqlExpired);
        $st3->execute();
        $expired = (int)$st3->fetchColumn();
    } elseif (isset($existingColsMap['status'])) {
        // Fallback: use Status column if present (legacy behavior)
        $st = $dbh->prepare("SELECT COUNT(*) FROM tblstudents WHERE Status = 1");
        $st->execute();
        $active = (int)$st->fetchColumn();

        $st2 = $dbh->prepare("SELECT COUNT(*) FROM tblstudents WHERE Status != 1");
        $st2->execute();
        $expired = (int)$st2->fetchColumn();
        $expiring = 0;
    } else {
        // Could not find suitable columns; leave counts at zero and let the page render
        $active = 0; $expiring = 0; $expired = 0;
    }

} catch (\Throwable $e) {
    // keep defaults on error
    $total = $total ?? 0;
    $active = $active ?? 0;
    $expiring = $expiring ?? 0;
    $expired = $expired ?? 0;
}

// week-over-week registrations (for a small activity indicator)
$newThisWeek = 0;
$prevWeek = 0;
try {
    $newThisWeek = (int)$dbh->query(
        "SELECT COUNT(*) FROM tblstudents WHERE RegDate >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)"
    )->fetchColumn();
    $prevWeek = (int)$dbh->query(
        "SELECT COUNT(*) FROM tblstudents WHERE RegDate >= DATE_SUB(CURDATE(), INTERVAL 14 DAY) AND RegDate < DATE_SUB(CURDATE(), INTERVAL 7 DAY)"
    )->fetchColumn();
} catch (\Throwable $e) {
    $newThisWeek = 0; $prevWeek = 0;
}

// top assessment centers
$topCenters = [];
try {
    $stmtCenters = $dbh->query("SELECT Venue, COUNT(*) AS c FROM tblstudents GROUP BY Venue ORDER BY c DESC LIMIT 5");
    $topCenters = $stmtCenters->fetchAll(PDO::FETCH_ASSOC);
} catch (\Throwable $e) {
    $topCenters = [];
}

include __DIR__ . '/includes/header.php';
?>

<div class="row g-3">
    <div class="col-md-3">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="text-muted text-uppercase">Total Certificates</h6>
                        <h2 class="fw-bold mb-0"><?= $total ?></h2>
                    </div>
                    <div class="text-end">
                        <i class="bi bi-card-checklist fs-3 text-primary"></i>
                    </div>
                </div>
                <small class="text-muted">All time</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="text-muted text-uppercase">Active</h6>
                        <h2 class="fw-bold text-success mb-0"><?= $active ?></h2>
                    </div>
                    <div class="text-end">
                        <i class="bi bi-check-circle-fill fs-3 text-success"></i>
                    </div>
                </div>
                <small class="text-muted">Currently valid</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="text-muted text-uppercase">Expiring (30 days)</h6>
                        <h2 class="fw-bold text-warning mb-0"><?= $expiring ?></h2>
                    </div>
                    <div class="text-end">
                        <i class="bi bi-exclamation-circle-fill fs-3 text-warning"></i>
                    </div>
                </div>
                <small class="text-muted">Renewals due soon</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="text-muted text-uppercase">Expired</h6>
                        <h2 class="fw-bold text-danger mb-0"><?= $expired ?></h2>
                    </div>
                    <div class="text-end">
                        <i class="bi bi-slash-circle fs-3 text-danger"></i>
                    </div>
                </div>
                <small class="text-muted">Needs attention</small>
            </div>
        </div>
    </div>
</div>

<div class="dashboard-hero">
    <div class="d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-3">
            <div>
                <h1 class="hero-title h4 mb-1">Welcome back</h1>
                <div class="hero-sub">Certificates overview · Renewals due · Recent activity</div>
            </div>
        </div>
        <div class="text-end">
            <small class="text-muted">Updated <?= date('F j, Y') ?> • <span class="text-success">Live</span></small>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Registrations</h5>
                    <div class="small text-muted">
                        New this week: <strong><?= $newThisWeek ?></strong>
                        <?php
                        $delta = ($prevWeek === 0) ? ($newThisWeek ? 100 : 0) : round((($newThisWeek - $prevWeek) / max(1,$prevWeek)) * 100);
                        $deltaSign = ($delta > 0) ? '+' : '';
                        ?>
                        <span class="ms-2 <?= $delta >= 0 ? 'text-success' : 'text-danger' ?>">(<?= $deltaSign . $delta ?>%)</span>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle recent-table">
                        <thead class="table-light">
                            <tr>
                                <th>Certificate No.</th>
                                <th>Candidate</th>
                                <th>_______</th>
                                <th>Registered</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        $stmt = $dbh->query(
                                "SELECT RollId, StudentName, StudentEmail, RegDate
                                    FROM tblstudents
                                    ORDER BY RegDate DESC
                                    LIMIT 5"
                        );
                        foreach ($stmt as $row): ?>
                            <tr>
                                <td style="max-width:260px;">
                                    <div class="cert-no text-truncate" title="<?= htmlspecialchars($row['RollId']) ?>"><?= htmlspecialchars($row['RollId']) ?></div>
                                </td>
                                <td>
                                    <div class="candidate-name"><?= htmlspecialchars($row['StudentName']) ?>
                                        <small class="email"><?= htmlspecialchars($row['StudentEmail'] ?: '') ?></small>
                                    </div>
                                </td>
                                <td class="d-none d-md-table-cell email"><?= htmlspecialchars($row['StudentEmail']) ?></td>
                                <td>
                                    <span class="reg-badge"><?= htmlspecialchars(formatDateSafe($row['RegDate'] ?? null, 'Y-m-d H:i')) ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if ($expiring === 0): ?>
                            <tr>
                                <td colspan="4" class="text-center py-3 text-muted">
                                    No certificates expiring in the next 30 days or expiry date not available.
                                </td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white text-end py-2">
                <a href="manage-students.php" class="btn btn-sm btn-link">View all registrations</a>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white">
                <h5 class="mb-0">Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="manage-students.php" class="btn btn-primary btn-lg">
                        <i class="bi bi-people-fill me-2"></i> Manage Certificates
                    </a>
                    <?php ui_action_button(PERM_CERT_IMPORT, '<a href="loadexcel.php" class="btn btn-outline-secondary btn-lg"><i class="bi bi-file-earmark-excel me-2"></i> Upload (Excel)</a>'); ?>
                    <a href="find-result.php" class="btn btn-outline-dark btn-lg">
                        <i class="bi bi-search me-2"></i> Public Verification
                    </a>
                    <?php ui_action_button(PERM_USER_MANAGE, '<a href="manage-users.php" class="btn btn-outline-info btn-sm mt-2"><i class="bi bi-shield-lock me-1"></i> Users &amp; Roles</a>'); ?>
                </div>
                <hr>
                <h6 class="mb-2">Top Assessment Centers</h6>
                <ul class="list-unstyled">
                    <?php if (empty($topCenters)): ?>
                        <li class="text-muted">No data available.</li>
                    <?php else: ?>
                        <?php foreach ($topCenters as $c): ?>
                            <li class="mb-1"><strong><?= htmlspecialchars($c['Venue'] ?: '—') ?></strong>
                                <small class="text-muted"> — <?= (int)$c['c'] ?> certificates</small>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
