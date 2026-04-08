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

// RBAC: only these roles may perform imports
requireLogin();
requireRole(['super_admin','admin','data_entry']);
requirePermission(PERM_CERT_IMPORT);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    setFlash('error', 'Invalid request or session expired. Please try again.');
    header('Location: loadexcel.php');
    exit;
}

$uploadType = $_POST['upload_type'] ?? '';
if (!in_array($uploadType, ['append','replace'], true)) {
    setFlash('error', 'Invalid upload type selected. Choose "Append" or "Replace".');
    header('Location: loadexcel.php');
    exit;
}

if (empty($_FILES['file']['name']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    $msg = 'File upload failed.';
    // Provide more friendly messages for common PHP upload errors
    $err = $_FILES['file']['error'] ?? UPLOAD_ERR_OK;
    if ($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE) {
        $msg = 'Uploaded file is too large. Please upload a smaller file.';
    } elseif ($err === UPLOAD_ERR_NO_FILE) {
        $msg = 'No file selected. Please choose a CSV or Excel file to upload.';
    }
    setFlash('error', $msg);
    header('Location: loadexcel.php');
    exit;
}

$tmpName  = $_FILES['file']['tmp_name'];
$origName = $_FILES['file']['name'];
$extension = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

if (!in_array($extension, ['csv','xls','xlsx'], true)) {
    setFlash('error', 'Unsupported file type. Please upload a CSV or Excel file (.csv, .xls, .xlsx).');
    header('Location: loadexcel.php');
    exit;
}

$dbh = getPDO();

$totalRows = 0;
$inserted  = 0;
$updated   = 0;
$skipped   = 0;
$errors    = [];

// If replace wipe table before importing
if ($uploadType === 'replace') {
    $dbh->exec("TRUNCATE TABLE tblstudents");
}

// For now handle CSV; Excel can be added via PhpSpreadsheet.
if ($extension === 'csv') {
    if (($handle = fopen($tmpName, 'r')) === false) {
            setFlash('error', 'Cannot open uploaded file. Please ensure the file is readable.');
            header('Location: loadexcel.php');
        exit;
    }

    $header = fgetcsv($handle);
    if ($header === false) {
            setFlash('error', 'Empty file. Please provide a CSV with a header row and data.');
            header('Location: loadexcel.php');
        exit;
    }

    // Normalize header names to a lookup map (lowercased, single-spaced)
    $map = [];
    $mapNorm = []; // normalized (stripped) form => original index
    foreach ($header as $i => $col) {
        $colTrim = trim($col);
        $colNorm = strtolower(preg_replace('/\s+/', ' ', $colTrim));
        $map[$colNorm] = $i;
        // stripped normalization for fuzzy matching: remove non-alphanum
        $stripped = preg_replace('/[^a-z0-9]/', '', strtolower($colTrim));
        $mapNorm[$stripped] = $i;
    }

    // Aliases for common header variants so imports are more forgiving
    $aliases = [
        'rollid' => ['rollid','roll_id','roll id','rollno','roll no','roll_no','certificate no','certificateno','certno','certificate_no','certifcate number','certificate number'],
        'studentname' => ['studentname','student name','name','candidate','fullname','full name','name of candidate','name of the candidate'],
        'occupation' => ['occupation','job','profession'],
        'level' => ['level','certificate level','certificatelevel','level (one)','i(one)'],
        'activation_date' => ['activation_date','activation date','activationdate','issue_date','issuedate','date issued','date_issued','issued on','issuedon','assessment date','assessment_date','assessment dt','assessmentd','assessmentdt'],
        'expiry_date' => ['expiry_date','expiry date','expirydate','expiry','valid until','validuntil','valid_until'],
        'venue' => ['venue','location','centre','center','venu','assessment center','assessment centre','assessment_center','assessmentcentre','assessmentcenter','assessment centre','y/alem agri'],
        'controlno' => [
            'controlno','control no','control_number','control number','co.no','co.no','cono','control number','control no.','control.no',
            'co.numeber','co numeber','co number','conumber','co.number','co num','co.num','controlno.','control_no.','controlno','control'
        ]
    ];

    // Resolve which header index corresponds to each logical column
    $colIndex = [];
    $missing = [];
    foreach ($aliases as $logical => $variants) {
        $found = false;
        // first try exact normalized matches
        foreach ($variants as $v) {
            $vNorm = strtolower($v);
            if (isset($map[$vNorm])) {
                $colIndex[$logical] = $map[$vNorm];
                $found = true;
                break;
            }
            $vStrip = preg_replace('/[^a-z0-9]/', '', $vNorm);
            if (isset($mapNorm[$vStrip])) {
                $colIndex[$logical] = $mapNorm[$vStrip];
                $found = true;
                break;
            }
        }

        if ($found) {
            continue;
        }

        // fuzzy match: compare stripped header names using levenshtein
        $best = ['dist' => PHP_INT_MAX, 'idx' => null, 'h' => ''];
        foreach ($mapNorm as $hStr => $idx) {
            foreach ($variants as $v) {
                $vStripLocal = preg_replace('/[^a-z0-9]/', '', strtolower($v));
                if ($vStripLocal === '') continue;
                $d = levenshtein($vStripLocal, $hStr);
                if ($d < $best['dist']) {
                    $best = ['dist' => $d, 'idx' => $idx, 'h' => $hStr];
                }
            }
        }

        // Accept fuzzy match if distance is small relative to length
        if ($best['idx'] !== null) {
            $len = max(1, strlen($best['h']));
            $threshold = max(1, (int)ceil($len * 0.35));
            if ($best['dist'] <= $threshold) {
                $colIndex[$logical] = $best['idx'];
                $found = true;
            }
        }

        if (!$found) {
            $missing[] = $logical;
        }
    }

    if (!empty($missing)) {
        $present = implode(', ', array_keys($map));
        $missingList = implode(', ', $missing);
        setFlash('error', "Missing required column(s): {$missingList}. Found headers: {$present}");
        header('Location: loadexcel.php');
        exit;
    }

    $dbh->beginTransaction();

    // discover existing columns in tblstudents to avoid writing to missing columns
    $existingColsMap = [];
    foreach ($dbh->query("DESCRIBE tblstudents") as $c) {
        if (isset($c['Field'])) {
            $existingColsMap[strtolower($c['Field'])] = $c['Field'];
        } elseif (isset($c[0])) {
            $existingColsMap[strtolower($c[0])] = $c[0];
        }
    }

    // determine actual RollId column name in the DB (fallback to RollId)
    $rollColName = $existingColsMap['rollid'] ?? 'RollId';

    try {
        while (($row = fgetcsv($handle)) !== false) {
            $totalRows++;

            $rollId = trim($row[$colIndex['rollid']] ?? '');
            if ($rollId === '') {
                $skipped++;
                $errors[] = "Row {$totalRows}: empty RollId.";
                continue;
            }

            $data = [
                'RollId'          => $rollId,
                'StudentName'     => trim($row[$colIndex['studentname']] ?? ''),
                'Occupation'      => trim($row[$colIndex['occupation']] ?? ''),
                'Level'           => trim($row[$colIndex['level']] ?? ''),
                'activation_date' => trim($row[$colIndex['activation_date']] ?? '') ?: null,
                'expiry_date'     => trim($row[$colIndex['expiry_date']] ?? '') ?: null,
                'Venue'           => trim($row[$colIndex['venue']] ?? ''),
                'ControlNo'       => trim($row[$colIndex['controlno']] ?? ''),
            ];

            // Map Level -> tblclasses (create class if missing) and set ClassId for import
            if (!empty($data['Level'])) {
                $levelName = $data['Level'];
                try {
                    $stmtC = $dbh->prepare("SELECT id FROM tblclasses WHERE ClassName = :cn LIMIT 1");
                    $stmtC->execute([':cn' => $levelName]);
                    $classId = $stmtC->fetchColumn();
                    if (!$classId) {
                        $insC = $dbh->prepare("INSERT INTO tblclasses (ClassName, ClassNameNumeric, Section) VALUES (:cn, 0, '')");
                        $insC->execute([':cn' => $levelName]);
                        $classId = $dbh->lastInsertId();
                    }
                    $data['ClassId'] = (int)$classId;
                } catch (Exception $e) {
                    // ignore class mapping errors and continue
                }
            }

            // Map activation_date -> RegDate if present (store as-is; MySQL will parse common formats)
            if (!empty($data['activation_date'])) {
                $data['RegDate'] = $data['activation_date'];
            }
            // Parse dates into MySQL datetime format and set Status based on expiry
            if (!empty($data['activation_date'])) {
                $ts = strtotime($data['activation_date']);
                if ($ts !== false) {
                    $data['activation_date'] = date('Y-m-d H:i:s', $ts);
                    // prefer RegDate as activation datetime
                    $data['RegDate'] = $data['activation_date'];
                }
            }
            if (!empty($data['expiry_date'])) {
                $ts = strtotime($data['expiry_date']);
                if ($ts !== false) {
                    $data['expiry_date'] = date('Y-m-d H:i:s', $ts);
                }
            }

            // Determine Status: 1 = active (no expiry or expiry >= today), 0 = expired
            $status = 1;
            if (!empty($data['expiry_date'])) {
                $expTs = strtotime($data['expiry_date']);
                if ($expTs !== false && $expTs < strtotime('today')) {
                    $status = 0;
                }
            }
            $data['Status'] = $status;
            // filter $data to only include columns that exist in the DB
            $filtered = [];
            foreach ($data as $k => $v) {
                $lk = strtolower($k);
                if (isset($existingColsMap[$lk])) {
                    // use actual DB column name as key
                    $filtered[$existingColsMap[$lk]] = $v;
                }
            }

            // ensure RollId is available in DB column names for selects/where
            $stmt = $dbh->prepare("SELECT StudentId FROM tblstudents WHERE `$rollColName` = :rollid");
            $stmt->execute([':rollid' => $rollId]);
            $existing = $stmt->fetch();

            if ($existing) {
                if ($uploadType === 'append' || $uploadType === 'replace') {
                    $skipped++;
                    continue;
                }

                if ($uploadType === 'update') {
                    $updated++;
                    if ($uploadType !== 'test') {
                        // prepare dynamic UPDATE
                        // remove roll column from update set if present
                        $updData = $filtered;
                        if (isset($updData[$rollColName])) {
                            unset($updData[$rollColName]);
                        }
                        if (!empty($updData)) {
                            $sets = [];
                            $params = [];
                            foreach ($updData as $col => $val) {
                                $ph = ':' . preg_replace('/[^a-z0-9_]/i', '_', strtolower($col));
                                $sets[] = "`$col` = $ph";
                                $params[$ph] = $val;
                            }
                            $params[':rollid'] = $rollId;
                            $sql = "UPDATE tblstudents SET " . implode(', ', $sets) . " WHERE `$rollColName` = :rollid";
                            $upd = $dbh->prepare($sql);
                            $upd->execute($params);
                        }
                    }
                }
            } else {
                $inserted++;
                if ($uploadType !== 'test') {
                    // prepare dynamic INSERT
                    if (!empty($filtered)) {
                        $cols = array_keys($filtered);
                        $placeholders = [];
                        $params = [];
                        foreach ($cols as $col) {
                            $ph = ':' . preg_replace('/[^a-z0-9_]/i', '_', strtolower($col));
                            $placeholders[] = $ph;
                            $params[$ph] = $filtered[$col];
                        }
                        $colList = '`' . implode('`,`', $cols) . '`';
                        $phList  = implode(',', $placeholders);
                        $sql = "INSERT INTO tblstudents ($colList) VALUES ($phList)";
                        $ins = $dbh->prepare($sql);
                        $ins->execute($params);
                    }
                }
            }
        }

        $dbh->commit();
        // Audit import (store filename and summary counts)
        $details = json_encode([
            'file' => $origName,
            'inserted' => $inserted,
            'updated' => $updated,
            'skipped' => $skipped,
            'total' => $totalRows,
        ]);
        logAudit('IMPORT', 'CERTIFICATE', null, $details);
    } catch (Exception $e) {
        $dbh->rollBack();
        fclose($handle);
        setFlash('error', 'Import failed: ' . $e->getMessage());
        header('Location: loadexcel.php');
        exit;
    }

    fclose($handle);
} else {
    setFlash('error', 'Excel (.xls/.xlsx) support not yet implemented. Please convert to CSV or upload a CSV file.');
    header('Location: loadexcel.php');
    exit;
}
// If there are many warnings, write them to a downloadable file and only show a compact list inline
$warnings_file_url = null;
$inline_errors = $errors;
if (!empty($errors)) {
    $warningsDir = __DIR__ . '/uploads/warnings';
    if (!is_dir($warningsDir)) {
        @mkdir($warningsDir, 0755, true);
    }
    $fname = 'warnings_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.txt';
    $filePath = $warningsDir . '/' . $fname;
    $fh = @fopen($filePath, 'w');
    if ($fh) {
        foreach ($errors as $e) {
            fwrite($fh, $e . PHP_EOL);
        }
        fclose($fh);
        // build a web-accessible URL using BASE_URL if defined
        $base = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
        $warnings_file_url = $base . '/uploads/warnings/' . $fname;
    }
    // only show up to 10 inline warnings to keep the page compact
    $inline_errors = array_slice($errors, 0, 10);
}

include __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card shadow-sm border-0 mt-4">
            <div class="card-body">
                <h4 class="card-title mb-3">Upload Summary</h4>
                <p><strong>File:</strong> <?= htmlspecialchars($origName) ?></p>
                <p><strong>Upload Type:</strong> <?= htmlspecialchars($uploadType) ?></p>
                <ul>
                    <li>Total rows processed: <?= $totalRows ?></li>
                    <li>Inserted: <?= $inserted ?></li>
                    <li>Updated: <?= $updated ?></li>
                    <li>Skipped: <?= $skipped ?></li>
                </ul>

                <?php if (!empty($inline_errors)): ?>
                    <div class="alert alert-warning">
                        <strong>Warnings:</strong>
                        <ul class="mb-0">
                            <?php foreach ($inline_errors as $err): ?>
                                <li><?= htmlspecialchars($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <?php if (!empty($warnings_file_url) && count($errors) > count($inline_errors)): ?>
                            <hr>
                            <div class="small text-muted">Only the first <?= count($inline_errors) ?> warnings are shown. <a href="<?= htmlspecialchars($warnings_file_url) ?>" class="fw-bold">Download full warnings (<?= count($errors) ?>)</a></div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <a href="manage-students.php" class="btn btn-primary">Back to Certificates</a>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
