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

$search = trim($_GET['q'] ?? '');
$params = [];

// Pagination settings
$page = max(1, (int)($_GET['p'] ?? 1));
$allowedPer = [10,15,25,50,100];
$perPage = (int)($_GET['per'] ?? 15);
if (!in_array($perPage, $allowedPer, true)) { $perPage = 15; }
$offset = ($page - 1) * $perPage;

// Build base WHERE clause if searching
$where = '';
if ($search !== '') {
    $where = " WHERE certificate_number LIKE :q1 OR name_of_candidate LIKE :q2";
    $params['q1'] = '%' . $search . '%';
    $params['q2'] = '%' . $search . '%';
}

// Get total count for pagination
$countSql = "SELECT COUNT(*) FROM students_excel" . $where;
$countStmt = $dbh->prepare($countSql);
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));

// Fetch current page
$sql = "SELECT * FROM students_excel" . $where . " ORDER BY student_id DESC LIMIT :limit OFFSET :offset";
$stmt = $dbh->prepare($sql);
// bind search params first
foreach ($params as $k => $v) {
    $stmt->bindValue(":$k", $v, PDO::PARAM_STR);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$students = $stmt->fetchAll();

include __DIR__ . '/includes/header.php';
// render any flash messages from recent actions (delete/approve/revoke/import)
renderFlash();
?>

<div class="d-flex justify-content-between align-items-center mb-3 page-header">
    <div>
        <h4 class="mb-0 d-flex align-items-center gap-2">
            <i class="bi bi-award-fill text-primary fs-4" aria-hidden="true"></i>
            COC Certificate Management
        </h4>
        <div class="small text-muted">Manage certificates, renewals and candidate records</div>
    </div>
    <div class="d-flex align-items-center gap-2">
        <?php ui_action_button(PERM_CERT_IMPORT, '<a href="loadexcel.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-upload me-1"></i> Import</a>'); ?>
        <?php ui_action_button(PERM_CERT_CREATE, '<a href="edit-student.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i> Add Certificate</a>'); ?>
    </div>
</div>

<form class="row g-2 mb-3" method="get">
    <div class="col-md-4">
        <input type="text" name="q" class="form-control" placeholder="Search by certificate number or name"
               value="<?= htmlspecialchars($search) ?>">
    </div>
    <div class="col-md-2">
        <button class="btn btn-outline-secondary w-100" type="submit">Search</button>
    </div>
</form>


<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                        <tr>
                        <th>#</th>
                        <th>CERTIFICATE NUMBER</th>
                        <th>NAME OF CANDIDATE</th>
                        <th class="d-none d-md-table-cell">OCCUPATION</th>
                        <th class="d-none d-md-table-cell">LEVEL</th>
                        <th>ISSUED ON</th>
                        <th>VALID UNTIL</th>
                        <th class="d-none d-md-table-cell">ASSESSMENT CENTER</th>
                        <th class="d-none d-md-table-cell">CONTROL NO.</th>
                        <th style="width: 120px;">STATUS</th>
                        <th style="width: 120px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($students): ?>
                    <?php foreach ($students as $i => $row): 
                        // compute certificate status using available fields from the view/table
                        $validUntil = $row['valid_until'] ?? $row['expiry_date'] ?? null;
                        if (!empty($validUntil)) {
                            $status = getCertificateStatus($validUntil);
                        } else {
                            // try status flag (view may expose 'status' lowercase or 'Status')
                            $flag = $row['status'] ?? $row['Status'] ?? 0;
                            $status = ((int)$flag === 1) ? 'active' : 'expired';
                        }
                    ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= htmlspecialchars($row['certificate_number'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['name_of_candidate'] ?? '') ?></td>
                            <td class="d-none d-md-table-cell"><?= htmlspecialchars($row['occupation'] ?? '') ?></td>
                            <td class="d-none d-md-table-cell"><?= htmlspecialchars($row['level'] ?? '') ?></td>
                            <td><?= htmlspecialchars(formatDateSafe($row['issued_on'] ?? null)) ?></td>
                            <td><?= htmlspecialchars(formatDateSafe($row['valid_until'] ?? null)) ?></td>
                            <td class="d-none d-md-table-cell"><?= htmlspecialchars($row['assessment_center'] ?? '') ?></td>
                            <td class="d-none d-md-table-cell"><?= htmlspecialchars($row['control_no'] ?? '') ?></td>
                            <td><?= renderStatusBadge($status) ?></td>
                            <td class="text-center">
                                <div class="action-compact" role="group" aria-label="Actions">
                                    <a href="view-student.php?id=<?= (int)$row['student_id'] ?>" class="btn btn-outline-secondary btn-sm me-1 action-icon" title="View" aria-label="View">
                                        <i class="bi bi-eye" aria-hidden="true"></i>
                                    </a>

                                    <?php
                                        ui_action_button(PERM_CERT_EDIT, '<a href="edit-student.php?id=' . (int)$row['student_id'] . '" class="btn btn-outline-primary btn-sm me-1 action-icon" title="Edit" aria-label="Edit"><i class="bi bi-pencil-square" aria-hidden="true"></i></a>');
                                        ui_action_button(PERM_CERT_DELETE, '<form method="post" action="delete-student.php" class="d-inline confirm-form" data-confirm-title="Delete Certificate" data-confirm-message="Are you sure you want to delete this certificate? This action cannot be undone." data-confirm-class="danger"><input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrfToken()) . '"><input type="hidden" name="id" value="' . (int)$row['student_id'] . '"><button type="submit" class="btn btn-outline-danger btn-sm action-icon" title="Delete" aria-label="Delete"><i class="bi bi-trash" aria-hidden="true"></i></button></form>');
                                    ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="11" class="text-center py-4 text-muted">
                            No certificates found.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Pagination controls -->
<?php if ($total > $perPage): ?>
    <?php
    // helper to preserve query params (q and per)
    function pageUrl($p) {
        $qs = [];
        if (isset($_GET['q']) && $_GET['q'] !== '') { $qs['q'] = $_GET['q']; }
        if (isset($_GET['per']) && $_GET['per'] !== '') { $qs['per'] = $_GET['per']; }
        if ($p > 1) { $qs['p'] = $p; }
        return basename($_SERVER['PHP_SELF']) . ($qs ? ('?' . http_build_query($qs)) : '');
    }

    // smart window with ellipses
    $window = 2; // pages either side
    $start = max(1, $page - $window);
    $end = min($totalPages, $page + $window);
    ?>
    <nav class="my-3">
        <div class="d-flex flex-column flex-md-row align-items-center justify-content-between gap-2">
            <div class="d-flex align-items-center gap-2">
                <ul class="pagination pagination-app mb-0">
                    <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= ($page <= 1) ? '#' : htmlspecialchars(pageUrl(1)) ?>" aria-label="First">«</a>
                    </li>
                    <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= ($page <= 1) ? '#' : htmlspecialchars(pageUrl($page - 1)) ?>" aria-label="Previous">‹</a>
                    </li>

                    <?php if ($start > 1): ?>
                        <li class="page-item"><a class="page-link" href="<?= htmlspecialchars(pageUrl(1)) ?>">1</a></li>
                        <?php if ($start > 2): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
                    <?php endif; ?>

                    <?php for ($i = $start; $i <= $end; $i++): ?>
                        <li class="page-item <?= ($i === $page) ? 'active' : '' ?>">
                            <a class="page-link" href="<?= htmlspecialchars(pageUrl($i)) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($end < $totalPages): ?>
                        <?php if ($end < $totalPages - 1): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
                        <li class="page-item"><a class="page-link" href="<?= htmlspecialchars(pageUrl($totalPages)) ?>"><?= $totalPages ?></a></li>
                    <?php endif; ?>

                    <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= ($page >= $totalPages) ? '#' : htmlspecialchars(pageUrl($page + 1)) ?>" aria-label="Next">›</a>
                    </li>
                    <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= ($page >= $totalPages) ? '#' : htmlspecialchars(pageUrl($totalPages)) ?>" aria-label="Last">»</a>
                    </li>
                </ul>

                <div class="d-none d-sm-flex align-items-center ms-2 small text-muted">Showing <strong><?= min($total, $offset + 1) ?></strong>–<strong><?= min($total, $offset + count($students)) ?></strong> of <strong><?= $total ?></strong></div>
            </div>

            <div class="d-flex align-items-center gap-2">
                <form id="perPageForm" method="get" class="d-flex align-items-center gap-2">
                    <input type="hidden" name="q" value="<?= htmlspecialchars($search) ?>">
                    <label class="small text-muted mb-0">Per page</label>
                    <select name="per" class="form-select form-select-sm" onchange="document.getElementById('perPageForm').submit()">
                        <?php foreach ([10,15,25,50,100] as $opt): ?>
                            <option value="<?= $opt ?>" <?= ($perPage == $opt) ? 'selected' : '' ?>><?= $opt ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>

                <form id="jumpForm" class="d-flex align-items-center gap-2" onsubmit="return jumpToPage(event)">
                    <label class="small text-muted mb-0">Go to</label>
                    <input id="jumpPage" type="number" min="1" max="<?= $totalPages ?>" value="<?= $page ?>" class="form-control form-control-sm" style="width:90px;" aria-label="Go to page">
                    <button class="btn btn-sm btn-outline-secondary" type="submit">Go</button>
                </form>
            </div>
        </div>
    </nav>

    <script>
    function getQueryParams() {
        const search = new URLSearchParams(window.location.search);
        return search;
    }
    function jumpToPage(e) {
        e.preventDefault();
        const p = parseInt(document.getElementById('jumpPage').value || '1', 10);
        const max = <?= $totalPages ?>;
        const page = Math.min(Math.max(1, p), max);
        const qs = getQueryParams();
        qs.set('p', page);
        // preserve per and q
        window.location.search = qs.toString();
        return false;
    }
    </script>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
