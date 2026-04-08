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
require_once __DIR__ . '/includes/error-handler.php';
$hide_login = true;
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$rollid = '';
$record = null;
$status = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rollid = trim($_POST['rollid'] ?? '');
    if ($rollid !== '') {
        $dbh = getPDO();
        $stmt = $dbh->prepare("SELECT * FROM tblstudents WHERE RollId = :r LIMIT 1");
        $stmt->execute([':r' => $rollid]);
        $record = $stmt->fetch();
        if ($record) {
            // merge defaults to avoid undefined indexes
            $defaults = [
                'RollId' => '',
                'StudentName' => '',
                'Occupation' => '',
                'Level' => '',
                'activation_date' => null,
                'expiry_date' => null,
                'Venue' => '',
                'ControlNo' => '',
                'status' => 'pending',
            ];
            $record = array_merge($defaults, $record);

            // Determine validity: prefer date-based checks, but respect explicit revocation flags
            $today = new DateTime();
            $activation = empty($record['activation_date']) ? null : new DateTime($record['activation_date']);
            $expiry = empty($record['expiry_date']) ? null : new DateTime($record['expiry_date']);

            // Date-based validity
            $notYetActive = $activation && $activation > $today;
            $expired = $expiry && $expiry < $today;
            $datesValid = !$notYetActive && !$expired;

            // Normalize status value and detect explicit revocation/inactive markers
            $rawStatus = $record['Status'] ?? $record['status'] ?? null;
            $statusStr = is_string($rawStatus) ? strtolower($rawStatus) : null;
            $isExplicitlyRevoked = false;
            if (is_string($rawStatus) && in_array($statusStr, ['revoked', 'inactive', 'deleted', 'rejected'], true)) {
                $isExplicitlyRevoked = true;
            }
            if (is_numeric($rawStatus) && (int)$rawStatus === 2) {
                // reserve numeric 2 as an explicit revoked-like marker if used
                $isExplicitlyRevoked = true;
            }

            // Compute final validity: dates must be valid and record must not be explicitly revoked
            $isValid = $datesValid && !$isExplicitlyRevoked;

            // set a normalized status label for UI
            if ($isValid) {
                $record['status'] = 'active';
            } elseif ($isExplicitlyRevoked) {
                $record['status'] = 'revoked';
            } else {
                $record['status'] = 'inactive';
            }

            // Compute a human status string for badges
            if ($isValid) {
                $status = 'valid';
            } else {
                $status = 'not_valid';
            }

            // Public record fields (never include internal IDs or user/session fields)
            $public = [
                'certificate_number' => $record['RollId'],
                'name' => $record['StudentName'],
                'issue_date' => formatDateSafe($record['activation_date']),
                'expiry_date' => formatDateSafe($record['expiry_date']),
                'level' => $record['Level'],
            ];

            // Provide a minimal public response for non-valid records (kept for compatibility)
            $public_minimal = [
                'certificate_number' => $record['RollId'],
                'status' => $status,
            ];

            // Always expose the full public payload for display/export, but keep `status` for UI
            $public_record = $public;
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-12 col-md-10 col-lg-8">
        <div class="card result-card mt-4">
            <div class="card-body">
                <h3 class="mb-3">Certificate Verification Result</h3>

                <?php if ($record): 
                    // determine a status class for styling
                    $statusClass = ($status === 'valid') ? 'status-valid' : 'status-expired';
                ?>
                    <div class="result-status mb-3">
                        <div class="status-badge <?= $statusClass ?>" id="statusBadge"><?= htmlspecialchars($isValid ? 'Valid certificate' : 'Not valid') ?></div>
                        <div>
                            <div class="text-muted">Certificate</div>
                            <div class="h5 mb-0"><?= htmlspecialchars($record['RollId']) ?></div>
                        </div>
                    </div>

                    <?php // Always show certificate details; mark validity clearly ?>
                        <?php if ($status !== 'valid'): ?>
                            <div class="alert alert-warning text-center">Not valid.</div>
                        <?php endif; ?>

                        <dl class="result-grid">
                            <div>
                                <dt>Candidate Name</dt>
                                <dd><?= htmlspecialchars($public['name']) ?></dd>
                            </div>
                            <div>
                                <dt>Certificate Number</dt>
                                <dd><?= htmlspecialchars($public['certificate_number']) ?></dd>
                            </div>
                            <div>
                                <dt>Issue Date</dt>
                                <dd><?= htmlspecialchars($public['issue_date']) ?></dd>
                            </div>
                            <div>
                                <dt>Expiry Date</dt>
                                <dd><?= htmlspecialchars($public['expiry_date']) ?></dd>
                            </div>
                            <div>
                                <dt>Level</dt>
                                <dd><?= htmlspecialchars($public['level']) ?></dd>
                            </div>
                        </dl>

                        <div class="result-actions no-export">
                            <button class="btn-export csv" id="downloadCsvBtn" title="Download CSV"><i class="bi bi-file-earmark-spreadsheet"></i> CSV</button>
                            <button class="btn-export pdf" id="downloadPdfBtn" title="Download PDF"><i class="bi bi-file-earmark-pdf"></i> PDF</button>
                            <button class="btn-export print" id="printBtn" title="Print"><i class="bi bi-printer"></i> Print</button>
                            <a href="find-result.php" class="btn btn-secondary ms-auto">Verify another</a>
                        </div>

                        <script>
                        // inline public record for client-side exports (full payload)
                        const __resultRecord = <?= json_encode(isset($public_record) ? $public_record : [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

                        function downloadJSON(filename='certificate.json'){
                            const blob = new Blob([JSON.stringify(__resultRecord, null, 2)], {type: 'application/json'});
                            const a = document.createElement('a'); a.href = URL.createObjectURL(blob); a.download = filename; a.click(); URL.revokeObjectURL(a.href);
                        }
                            function downloadCSV(filename='certificate.csv'){
                                const keys = Object.keys(__resultRecord);
                                const header = keys.join(',');
                                const values = keys.map(k => '"'+String(__resultRecord[k] ?? '').replace(/"/g,'""')+'"').join(',');
                                const csv = header + "\n" + values;
                                const blob = new Blob([csv], {type: 'text/csv'});
                                const a = document.createElement('a'); a.href = URL.createObjectURL(blob); a.download = filename; a.click(); URL.revokeObjectURL(a.href);
                            }
                            function downloadPDF(filename='certificate.pdf'){
                                const el = document.querySelector('.result-card');
                                if (!el) { return; }
                                if (typeof html2pdf === 'undefined') { window.print(); return; }

                                // Remove any previous tmp container
                                const prev = document.getElementById('__pdfTmp');
                                if (prev) prev.parentNode.removeChild(prev);

                                // Create a temporary container that is in-flow (position:relative)
                                const tmp = document.createElement('div');
                                tmp.id = '__pdfTmp';
                                tmp.style.background = '#fff';
                                tmp.style.boxSizing = 'border-box';
                                tmp.style.position = 'relative';
                                tmp.style.width = '100%';
                                tmp.style.maxWidth = '980px';
                                tmp.style.margin = '8px auto';
                                tmp.style.padding = '18px';
                                tmp.style.zIndex = 999999;

                                // Insert a simple printable header at the very top
                                const simpleHeader = document.createElement('div');
                                simpleHeader.style.textAlign = 'left';
                                simpleHeader.style.margin = '8px 0 12px 0';
                                simpleHeader.style.padding = '8px 12px';
                                simpleHeader.style.background = 'linear-gradient(90deg,#0b5ed7,#0a58ca)';
                                simpleHeader.style.borderRadius = '6px';
                                simpleHeader.style.display = 'flex';
                                simpleHeader.style.alignItems = 'center';
                                simpleHeader.style.gap = '12px';
                                // pick the site logo if available
                                let logoSrc = null;
                                const logoImg = document.querySelector('.site-logo') || document.querySelector('.site-logo img') || document.querySelector('.site-logo-placeholder img');
                                if (logoImg && logoImg.src) logoSrc = logoImg.src;
                                if (logoSrc) {
                                    const li = document.createElement('div');
                                    li.style.flex = '0 0 auto';
                                    li.innerHTML = '<img src="'+logoSrc+'" style="width:44px;height:44px;border-radius:6px;object-fit:cover;box-shadow:0 6px 18px rgba(11,92,215,0.12)" alt="logo">';
                                    simpleHeader.appendChild(li);
                                } else {
                                    // placeholder circle
                                    const ph = document.createElement('div');
                                    ph.style.width = '44px'; ph.style.height = '44px'; ph.style.borderRadius = '8px'; ph.style.background = '#fff'; ph.style.display='inline-block'; ph.style.boxShadow='0 6px 18px rgba(11,92,215,0.12)';
                                    simpleHeader.appendChild(ph);
                                }
                                const txt = document.createElement('div');
                                txt.innerHTML = '<div style="font-weight:700;font-size:16px;color:#fff;line-height:1">Sidaama Region COC exam</div><div style="font-size:12px;color:rgba(255,255,255,0.9);line-height:1">certificate verification system</div>';
                                simpleHeader.appendChild(txt);
                                tmp.appendChild(simpleHeader);

                                // (navbar clone removed to avoid overlapping header in printed output)

                                // Clone and sanitize the result card
                                const cardClone = el.cloneNode(true);
                                cardClone.querySelectorAll('.no-export').forEach(n => n.remove());
                                tmp.appendChild(cardClone);

                                // Insert tmp near the top of body so html2canvas can capture it easily
                                document.body.insertBefore(tmp, document.body.firstChild);

                                // scroll to top so the cloned area is in viewport
                                window.scrollTo(0, 0);

                                // ensure images inside tmp have loaded
                                const imgs = Array.from(tmp.querySelectorAll('img'));
                                const imgPromises = imgs.map(img => img.complete ? Promise.resolve() : new Promise(res => { img.onload = img.onerror = res; }));

                                // add class to body so CSS can hide elements if needed
                                document.body.classList.add('pdf-exporting');

                                Promise.all(imgPromises).then(() => {
                                    // small delay to allow layout reflow after scroll
                                    setTimeout(() => {
                                        const opt = {
                                            margin: 10,
                                            filename: filename,
                                            image: { type: 'jpeg', quality: 0.95 },
                                            html2canvas: { scale: 2, useCORS: true, logging: false, scrollY: 0 },
                                            jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
                                        };
                                        html2pdf().set(opt).from(tmp).save().then(() => {
                                            document.body.classList.remove('pdf-exporting');
                                            if (tmp.parentNode) tmp.parentNode.removeChild(tmp);
                                        }).catch((err) => {
                                            console.error('html2pdf error', err);
                                            document.body.classList.remove('pdf-exporting');
                                            if (tmp.parentNode) tmp.parentNode.removeChild(tmp);
                                        });
                                    }, 200);
                                }).catch(() => {
                                    document.body.classList.remove('pdf-exporting');
                                    if (tmp.parentNode) tmp.parentNode.removeChild(tmp);
                                });
                            }

                            document.getElementById('downloadCsvBtn')?.addEventListener('click', ()=> downloadCSV('certificate-<?= htmlspecialchars($public['certificate_number'] ?? $record['RollId']) ?>.csv'));
                            document.getElementById('downloadPdfBtn')?.addEventListener('click', ()=> downloadPDF('certificate-<?= htmlspecialchars($public['certificate_number'] ?? $record['RollId']) ?>.pdf'));
                            function printResult(){
                                const el = document.querySelector('.result-card');
                                if (!el) return;

                                // Create a print-only container and clone sanitized content into it
                                const existing = document.getElementById('__printTmp');
                                if (existing) existing.parentNode.removeChild(existing);
                                const tmp = document.createElement('div');
                                tmp.id = '__printTmp';
                                tmp.style.background = '#fff';
                                tmp.style.boxSizing = 'border-box';
                                tmp.style.padding = '18px';
                                tmp.style.zIndex = 999999;


                                // Insert a simple printable header at the very top
                                const simpleHeader = document.createElement('div');
                                simpleHeader.style.textAlign = 'left';
                                simpleHeader.style.margin = '8px 0 12px 0';
                                simpleHeader.style.padding = '8px 12px';
                                simpleHeader.style.background = 'linear-gradient(90deg,#0b5ed7,#0a58ca)';
                                simpleHeader.style.borderRadius = '6px';
                                simpleHeader.style.display = 'flex';
                                simpleHeader.style.alignItems = 'center';
                                simpleHeader.style.gap = '12px';
                                // pick the site logo if available
                                let logoSrc2 = null;
                                const logoImg2 = document.querySelector('.site-logo') || document.querySelector('.site-logo img') || document.querySelector('.site-logo-placeholder img');
                                if (logoImg2 && logoImg2.src) logoSrc2 = logoImg2.src;
                                if (logoSrc2) {
                                    const li2 = document.createElement('div');
                                    li2.style.flex = '0 0 auto';
                                    li2.innerHTML = '<img src="'+logoSrc2+'" style="width:44px;height:44px;border-radius:6px;object-fit:cover;box-shadow:0 6px 18px rgba(11,92,215,0.12)" alt="logo">';
                                    simpleHeader.appendChild(li2);
                                } else {
                                    const ph2 = document.createElement('div');
                                    ph2.style.width = '44px'; ph2.style.height = '44px'; ph2.style.borderRadius = '8px'; ph2.style.background = '#fff'; ph2.style.display='inline-block'; ph2.style.boxShadow='0 6px 18px rgba(11,92,215,0.12)';
                                    simpleHeader.appendChild(ph2);
                                }
                                const txt2 = document.createElement('div');
                                txt2.innerHTML = '<div style="font-weight:700;font-size:16px;color:#fff;line-height:1">Sidaama Region COC exam</div><div style="font-size:12px;color:rgba(255,255,255,0.9);line-height:1">certificate verification system</div>';
                                simpleHeader.appendChild(txt2);
                                tmp.appendChild(simpleHeader);

                                // (navbar clone removed to avoid overlapping header in printed output)

                                // Clone and sanitize result card
                                const cardClone = el.cloneNode(true);
                                cardClone.querySelectorAll('.no-export').forEach(n => n.remove());
                                tmp.appendChild(cardClone);

                                document.body.appendChild(tmp);

                                // Ensure images loaded
                                const imgs = Array.from(tmp.querySelectorAll('img'));
                                const imgPromises = imgs.map(img => img.complete ? Promise.resolve() : new Promise(res=>{img.onload=img.onerror=res;}));

                                Promise.all(imgPromises).then(()=>{
                                    // Use afterprint to cleanup; fallback to timeout
                                    function cleanup(){
                                        try{ if (tmp.parentNode) tmp.parentNode.removeChild(tmp); }catch(e){}
                                        window.removeEventListener('afterprint', cleanup);
                                    }
                                    window.addEventListener('afterprint', cleanup);
                                    // Trigger print (browser will use media=print rules which prefer #__printTmp)
                                    window.print();
                                    // Fallback cleanup in case afterprint is not supported
                                    setTimeout(cleanup, 1500);
                                }).catch(()=>{
                                    if (tmp.parentNode) tmp.parentNode.removeChild(tmp);
                                });
                            }
                            document.getElementById('printBtn')?.addEventListener('click', printResult);
                        </script>

                <?php elseif ($rollid !== ''): ?>
                    <div class="alert alert-danger text-center">No certificate found for number <strong><?= htmlspecialchars($rollid) ?></strong>.</div>
                    <div class="text-center mt-3"><a href="find-result.php" class="btn btn-secondary">Try again</a></div>
                <?php else: ?>
                    <div class="alert alert-info text-center">Please submit a certificate number from the verification page.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
