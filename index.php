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
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/error-handler.php';
require_once __DIR__ . '/includes/functions.php';

// legacy local error variable removed — use flash messages instead
// Handle login POST (authenticate against `admin` table)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        setFlash('error', 'Invalid request.');
        header('Location: index.php');
        exit;
    }

    $username = trim((string)($_POST['username'] ?? ''));
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        setFlash('warning', 'Please enter username and password.');
        header('Location: index.php');
        exit;
    }

    try {
        $dbh = getPDO();
        $stmt = $dbh->prepare('SELECT id, UserName AS username, Password AS password, role_id FROM admin WHERE UserName = :u LIMIT 1');
        $stmt->execute([':u' => $username]);
        $row = $stmt->fetch();
    } catch (Exception $e) {
        setFlash('error', 'Server error.');
        header('Location: index.php');
        exit;
    }

    if (!$row) {
        setFlash('error', 'Invalid username or password.');
        header('Location: index.php');
        exit;
    }

    $dbHash = $row['password'];
    $valid = false;

    // Support legacy MD5 hashes and modern password_hash() values
    if (is_string($dbHash) && strlen($dbHash) === 32 && md5($password) === $dbHash) {
        $valid = true;
    } elseif (is_string($dbHash) && password_verify($password, $dbHash)) {
        $valid = true;
    }

    if (!$valid) {
        setFlash('error', 'Invalid username or password.');
        header('Location: index.php');
        exit;
    }

    // Authentication successful — store minimal user info in session
    $_SESSION['user'] = [
        'id' => $row['id'],
        'username' => $row['username'],
        'role_id' => $row['role_id'] ?? null,
    ];
    // Resolve and cache the role name so header can display it immediately
    try {
        $roleName = getUserRoleName();
    } catch (Exception $e) {
        $roleName = null;
    }
    if ($roleName) {
        $_SESSION['role_name'] = $roleName;
        $_SESSION['user']['role_name'] = $roleName;
    }

    logAudit('LOGIN', 'USER', $row['id'], null);
    header('Location: dashboard.php');
    exit;
}

?><!-- custom login UI styles and layout -->
<style>
/* Inserted compact styles for modern login (kept concise, mobile-friendly) */
.page-center{flex:1;display:flex;align-items:center;justify-content:center}
:root{--bg-1:#0b5ed7;--bg-2:#67b3ff;--card-radius:28px;--accent:#0b66e3;--accent-2:#0b5ed7;--muted:#334155}
body{background:linear-gradient(160deg,var(--bg-1),var(--bg-2));min-height:100vh;display:flex;flex-direction:column;padding:32px}
.card{width:min(1100px,calc(100% - 48px));height:540px;border-radius:var(--card-radius);background:linear-gradient(180deg,rgba(255,255,255,0.92),rgba(255,255,255,0.96));box-shadow:0 40px 110px rgba(4,25,55,0.14);display:grid;grid-template-columns:0.9fr 0.06fr 1.1fr;position:relative;overflow:visible;backdrop-filter:blur(10px);align-items:stretch}

/* Sidama flag branding + verify CTA styles */
.sidama-flag-brand{display:flex;align-items:center;gap:12px}
.sidama-flag-wrap{position:relative;width:72px;height:48px;flex:0 0 auto;border-radius:12px;overflow:hidden;box-shadow:0 12px 36px rgba(3,37,76,0.12);border:1px solid rgba(0,0,0,0.06);background:#fff}
.sidama-flag{width:100%;height:100%;object-fit:cover;display:block;transform-origin:center;animation:sidama-wave 3.6s ease-in-out infinite}
.sidama-flag-wrap::before{content:'';position:absolute;left:-60%;top:0;width:60%;height:100%;background:linear-gradient(120deg, rgba(255,255,255,0.28), rgba(255,255,255,0.02));transform:skewX(-20deg) translateX(-100%);animation:sidama-sheen 3.8s linear infinite}
.sidama-corner{position:absolute;left:-18px;top:-18px;width:86px;height:54px;border-radius:12px;overflow:hidden;box-shadow:0 18px 44px rgba(3,37,76,0.18);border:1px solid rgba(0,0,0,0.06);background:#fff}
@media (max-width:920px){.sidama-corner{left:12px;top:8px;width:72px;height:44px}}
@keyframes sidama-sheen{0%{transform:skewX(-20deg) translateX(-100%)}50%{transform:skewX(-20deg) translateX(120%)}100%{transform:skewX(-20deg) translateX(-100%)}}
@keyframes sidama-wave{0%{transform:rotateY(0) skewY(0) translateY(0)}25%{transform:rotateY(4deg) skewY(-0.6deg) translateY(-1px)}50%{transform:rotateY(0) skewY(0) translateY(0)}75%{transform:rotateY(-4deg) skewY(0.6deg) translateY(-1px)}100%{transform:rotateY(0) skewY(0) translateY(0)}}
.sidama-flag-wrap:hover .sidama-flag{transform:scale(1.03) rotateY(8deg);transition:transform .28s}
.sidama-flag-wrap:hover::before{animation-duration:2.2s}

.sidama-flag-text .title{font-weight:800;color:var(--muted);font-size:1rem}
.sidama-flag-text .subtitle{font-size:0.85rem;color:rgba(51,65,85,0.6);margin-top:2px}

.verify{margin-top:auto;display:flex;align-items:center}
.verify-btn{display:inline-block;padding:12px 20px;border-radius:12px;background:linear-gradient(90deg,var(--accent-2),var(--accent));color:#fff;text-decoration:none;font-weight:800;box-shadow:0 18px 44px rgba(11,92,215,0.18)}
.verify-btn:hover{transform:translateY(-3px);box-shadow:0 28px 64px rgba(11,92,215,0.22)}
@media (max-width:920px){.verify-btn{width:100%;display:block;text-align:center}}
.panel{padding:32px;display:flex;flex-direction:column;gap:16px}
.left{background:linear-gradient(180deg,rgba(235,250,255,0.96),rgba(245,252,255,0.99))}
.right{padding:44px}
.flag-divider{width:28px;display:flex;align-items:stretch;position:relative}
.divider-line{width:8px;margin:0 auto;border-radius:6px;background:linear-gradient(180deg,#0bb14e 0%,#f2d22a 30%,#e03d2b 60%,#0fbf5b 100%);box-shadow:inset 0 0 18px rgba(0,0,0,0.06)}
.divider-dots{position:absolute;left:50%;transform:translateX(-50%);top:22%;height:56%;display:flex;flex-direction:column;align-items:center;justify-content:space-between}
.divider-dots .dot{width:8px;height:8px;border-radius:999px;background:rgba(255,255,255,0.85);box-shadow:0 6px 18px rgba(3,37,76,0.06)}
.brand{display:flex;gap:16px;align-items:center}
.logo{width:72px;height:72px;border-radius:999px;background:linear-gradient(180deg,#fff,#f3fbff);display:flex;align-items:center;justify-content:center;box-shadow:0 10px 30px rgba(3,37,76,0.08);border:1px solid rgba(255,255,255,0.6);overflow:hidden}
.brand-title{font-weight:900;font-size:1.05rem;color:var(--muted)}
.brand-sub{font-size:0.93rem;color:rgba(51,65,85,0.65);margin-top:4px}
.badge-row{display:flex;gap:8px;margin-left:auto}
.features{display:flex;flex-direction:column;gap:12px;margin-top:6px}
.feature{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:12px;background:#fff;border-radius:14px;box-shadow:0 10px 24px rgba(3,37,76,0.04);border:1px solid rgba(3,37,76,0.03)}

/* Toast / alert inside card */
.toast-container{position:relative;width:100%;display:flex;justify-content:flex-end;padding-bottom:6px}
.alert-toast{min-width:260px;max-width:420px;background:#fff;border-radius:12px;box-shadow:0 18px 44px rgba(3,37,76,0.12);border:1px solid rgba(200,30,30,0.12);padding:10px;animation:toast-in .28s ease both;display:block;margin:6px}
.alert-toast .toast-inner{display:flex;gap:10px;align-items:center}
.alert-toast .toast-icon{font-size:18px}
.alert-toast .toast-message{flex:1;color:#1f2937;font-weight:700}
.alert-toast .toast-close{background:transparent;border:0;font-size:18px;line-height:1;cursor:pointer;color:rgba(17,24,39,0.6)}
.alert-toast.alert-error{border-color:rgba(220,38,38,0.16);background:linear-gradient(180deg,rgba(255,245,246,0.98),#fff)}
.alert-toast.alert-warning{border-color:rgba(245,158,11,0.14)}
.alert-toast.alert-success{border-color:rgba(34,197,94,0.12)}

@keyframes toast-in{from{opacity:0;transform:translateY(-6px) scale(.98)}to{opacity:1;transform:none}}
.icon-circle{width:48px;height:48px;border-radius:12px;display:inline-flex;align-items:center;justify-content:center;background:linear-gradient(180deg,#eef7ff,#e8f3ff);color:var(--accent-2);font-weight:700}
.feature-text strong{display:block;font-size:1rem;color:var(--muted)}
.feature-text small{display:block;color:rgba(51,65,85,0.65);margin-top:4px}
.feature-action{width:40px;height:40px;border-radius:10px;display:inline-flex;align-items:center;justify-content:center;background:linear-gradient(180deg,#fff,#f5fbff);border:1px solid rgba(3,37,76,0.04);font-weight:700;color:var(--accent-2)}
.verify{margin-top:auto}
.verify label{display:block;margin-bottom:8px;color:rgba(51,65,85,0.7);font-weight:700}
.verify-row{display:flex;gap:10px;align-items:center}
.verify-input{position:relative;flex:1}
.verify-input input[type=search]{width:100%;padding:12px 64px 12px 14px;border-radius:12px;border:1px solid rgba(3,37,76,0.06);height:48px;background:#fff}
.mini-flags{position:absolute;right:10px;top:50%;transform:translateY(-50%);display:flex;gap:6px}
.mini-flags img{height:22px;width:auto;border-radius:6px;box-shadow:0 6px 16px rgba(3,37,76,0.06)}
.right h2{margin:0;font-size:1.6rem;color:var(--muted);font-weight:900}
.right p{margin:6px 0 0 0;color:rgba(51,65,85,0.72);font-size:0.95rem}
.form{margin-top:18px;display:flex;flex-direction:column;gap:12px}
.field{position:relative}
.field label{display:block;margin-bottom:8px;color:rgba(51,65,85,0.7);font-weight:700}
.field input{width:100%;padding:12px 14px 12px 48px;height:52px;border-radius:12px;border:1px solid rgba(3,37,76,0.06);background:#fff}
.field .field-icon{position:absolute;left:12px;top:50%;transform:translateY(-50%);width:30px;height:30px;display:flex;align-items:center;justify-content:center;color:var(--accent-2)}
.btn-primary{display:inline-flex;align-items:center;justify-content:center;padding:14px 18px;border-radius:12px;border:0;color:#fff;font-weight:800;background:linear-gradient(90deg,var(--accent-2),var(--accent));box-shadow:0 22px 54px rgba(11,92,215,0.16);margin-top:6px}
.forgot{display:flex;justify-content:flex-end;margin-top:6px}
.forgot a{color:var(--accent-2);text-decoration:none;font-weight:800}
.help{margin-top:auto;color:rgba(51,65,85,0.6);font-size:0.9rem}
@media (max-width:920px){.card{display:block;height:auto;padding:12px;width:calc(100% - 36px)}.left,.right{width:100%;padding:18px}.flag-divider{width:100%;height:18px;order:2;display:flex;margin:6px 0;align-items:center}.divider-line{width:100%;height:8px;border-radius:8px}.divider-dots{top:auto;left:6%;transform:none;flex-direction:row;height:auto;width:88%;justify-content:space-between}.mini-flags img{height:16px}.logo{width:64px;height:64px}.sidama-flag-brand{order:-1;margin-bottom:12px}}
</style>

<!-- flash toasts will be rendered inside the login card -->

<!-- Main card -->
<div class="page-center">
<main class="card" role="main" aria-label="Login">
    <?php if (file_exists(__DIR__ . '/assets/img/sidama.svg')): ?>
        <div class="sidama-corner">
            <img src="assets/img/sidama.svg" class="sidama-flag" alt="Sidama Flag" onerror="this.style.display='none'" />
        </div>
    <?php endif; ?>
    <section class="panel left" aria-label="Information panel">
        <div style="display:flex;align-items:center;gap:12px;">
            <div class="brand" style="flex:1">
                <div class="logo">
                    <?php if (file_exists(__DIR__ . '/assets/logo.png') || file_exists(__DIR__ . '/assets/img/logo.png')): ?>
                        <?php $logoPath = file_exists(__DIR__ . '/assets/logo.png') ? 'assets/logo.png' : 'assets/img/logo.png'; ?>
                        <img src="<?= $logoPath ?>" alt="Logo" style="width:100%;height:100%;object-fit:cover;" />
                    <?php else: ?>
                        <svg width="64" height="64" viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg"><rect width="64" height="64" rx="12" fill="#0b66e3"/></svg>
                    <?php endif; ?>
                </div>
                <div>
                    <div class="brand-title">Sidaama Region COC</div>
                    <div class="brand-sub">Certificate Verification System</div>
                </div>
            </div>
            
        </div>

        <div class="features">
            <div class="feature">
                <div class="feature-left">
                    <div class="icon-circle">✓</div>
                    <div class="feature-text"><strong>Trusted records</strong><small>Authentic certificate verification</small></div>
                </div>
                <div class="feature-action">✓</div>
            </div>
            <div class="feature">
                <div class="feature-left">
                    <div class="icon-circle">🔎</div>
                    <div class="feature-text"><strong>Fast certificate lookup</strong><small>Quick public verification</small></div>
                </div>
                <div class="feature-action">→</div>
            </div>
            <div class="feature">
                <div class="feature-left">
                    <div class="icon-circle">🔒</div>
                    <div class="feature-text"><strong>Secure admin access</strong><small>Role-based controls & audits</small></div>
                </div>
                <div class="feature-action">⚑</div>
            </div>
        </div>

        <div class="verify" aria-label="Verify certificate">
            <a class="verify-btn" href="find-result.php">Verify</a>
        </div>
    </section>

    <div class="flag-divider" aria-hidden="true" title="Sidama / Ethiopia flag divider">
        <div class="divider-line" aria-hidden="true"></div>
        <div class="divider-dots" aria-hidden="true" aria-hidden="true">
            <span class="dot"></span>
            <span class="dot"></span>
            <span class="dot"></span>
            <span class="dot"></span>
            <span class="dot"></span>
            <span class="dot"></span>
            <span class="dot"></span>
        </div>
    </div>

    <section class="panel right" aria-label="Login panel">
        <?php
        // Render flash messages as toast-style alerts inside the card
        $flashes = getFlash();
        if (!empty($flashes)):
        ?>
        <div class="toast-container">
            <?php foreach ($flashes as $type => $msg):
                $safe = htmlspecialchars((string)$msg, ENT_QUOTES, 'UTF-8');
                $role = 'info';
                if ($type === 'error' || $type === 'danger') $role = 'error';
                if ($type === 'warning') $role = 'warning';
                if ($type === 'success') $role = 'success';
            ?>
            <div class="alert-toast alert-<?= $role ?>" role="alert" aria-live="polite">
                <div class="toast-inner">
                    <span class="toast-icon">⚠️</span>
                    <div class="toast-message"><?= $safe ?></div>
                    <button type="button" class="toast-close" aria-label="Close">×</button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <h2>Welcome back</h2>
        <p class="muted">Sign in to manage certificates, users and imports.</p>

        <form class="form" method="post" action="index.php" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken()) ?>">
            <div class="field">
                <label for="username">Username or email</label>
                <span class="field-icon" aria-hidden="true">👤</span>
                <input id="username" name="username" type="text" placeholder="Username or email" required autofocus autocomplete="username">
            </div>
            <div class="field">
                <label for="password">Password</label>
                <span class="field-icon" aria-hidden="true">🔒</span>
                <input id="password" name="password" type="password" placeholder="Password" required autocomplete="current-password">
            </div>
            <button type="submit" class="btn-primary">Log In</button>
              <div class="admin-notice">
        <span class="notice-icon">🛡️</span>
        <div>
            <strong>Restricted Access</strong>
            <p>This page is intended only for authorized personnel of the Sidaama Regional COC Agency.</p>
        </div>
        <br>
            <div class="help">
        <span class="help-icon">❓</span>
        <span>Need help? <span class="muted">Contact your administrator</span></span>
    </div>
    </div>
        </form>
    </section>
</main>

</div>
<?php include __DIR__ . '/includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function(){
    // language toggle removed

    // verify input removed — only single Verify CTA remains
});
</script>

<script>
// Toast behavior: close button and auto-hide
document.addEventListener('DOMContentLoaded', function(){
    var toasts = document.querySelectorAll('.alert-toast');
    toasts.forEach(function(t){
        var close = t.querySelector('.toast-close');
        function hideToast(){
            t.style.transition = 'opacity .25s ease, transform .25s ease';
            t.style.opacity = '0';
            t.style.transform = 'translateY(-6px)';
            setTimeout(function(){ if (t && t.parentNode) t.parentNode.removeChild(t); }, 300);
        }
        if (close) {
            close.addEventListener('click', hideToast);
        }
        // auto hide after 5 seconds
        setTimeout(hideToast, 5000);
    });
});
</script>

