<?php

/*
 * Project: COC Verification system for sidama region
 * Purpose: Master's Degree Thesis
 * Description: This software was developed to solve a real-world problem as part of
 * the graduation requirements for the Master's Program.
 * Author: Dawit Birru Hurisso
 * Institution: Czech University of Life Sciences Prague (CZU)
 * Graduate Year: 2026
 */

require_once __DIR__ . '/includes/error-handler.php';

// Hide the header login/link for this public lookup page
$hide_login = true;
include __DIR__ . '/includes/header.php';
?>

<style>
:root{
    --verify-ink:#173a68;
    --verify-heading:#123a73;
    --verify-text:#35516f;
    --verify-muted:#5d7188;
    --verify-primary:#0e63f4;
    --verify-primary-2:#2a84ff;
    --verify-card-bg:linear-gradient(180deg, rgba(255,255,255,.94), rgba(246,250,255,.84));
    --verify-glass:linear-gradient(180deg, rgba(255,255,255,.22), rgba(255,255,255,.10));
    --verify-line:rgba(255,255,255,.46);
    --verify-shadow:0 24px 60px rgba(6,31,66,.14);
    --verify-soft-shadow:0 14px 32px rgba(9,39,79,.08);
    --verify-blue-shadow:0 18px 34px rgba(14,99,244,.20);
}

.verify-page{
    position:relative;
    padding:32px 0 40px;
    overflow:hidden;
}

.verify-page::before{
    content:"";
    position:absolute;
    inset:0;
    pointer-events:none;
    background:
        radial-gradient(circle at 14% 20%, rgba(255,255,255,.22) 0 2px, transparent 3px),
        radial-gradient(circle at 82% 14%, rgba(255,255,255,.18) 0 2px, transparent 3px),
        radial-gradient(circle at 76% 74%, rgba(255,255,255,.14) 0 2px, transparent 3px);
    background-size:260px 220px, 320px 220px, 260px 240px;
    opacity:.55;
}

.verify-page::after{
    content:"";
    position:absolute;
    left:-8%;
    right:-8%;
    bottom:-70px;
    height:160px;
    pointer-events:none;
    background:
        radial-gradient(ellipse at 50% 100%, rgba(255,255,255,.32), rgba(255,255,255,0) 60%);
    filter:blur(8px);
}

.verify-bg-flag,
.verify-bg-flag-2{
    position:absolute;
    pointer-events:none;
    z-index:0;
    opacity:.06;
    filter:blur(.4px);
}

.verify-bg-flag{
    right:4%;
    top:56px;
    width:520px;
    transform:rotate(-8deg);
}

.verify-bg-flag-2{
    left:3%;
    top:74px;
    width:300px;
    transform:rotate(5deg);
    opacity:.045;
}

.verify-wrap{
    position:relative;
    z-index:2;
}

.verify-shell{
    display:grid;
    gap:18px;
}

/* HERO */
.verify-hero-card{
    position:relative;
    border-radius:30px;
    padding:30px 30px 28px;
    background:var(--verify-glass);
    border:1px solid rgba(255,255,255,.34);
    box-shadow:var(--verify-shadow);
    backdrop-filter:blur(14px);
    -webkit-backdrop-filter:blur(14px);
    overflow:hidden;
}

.verify-hero-card::before{
    content:"";
    position:absolute;
    inset:0;
    background:linear-gradient(90deg, rgba(255,255,255,.08), rgba(255,255,255,0));
    pointer-events:none;
}

.verify-hero-content{
    position:relative;
    z-index:2;
    max-width:790px;
}

.verify-hero-top{
    display:flex;
    align-items:center;
    gap:14px;
    margin-bottom:14px;
}

.verify-badge{
    width:56px;
    height:56px;
    border-radius:18px;
    display:flex;
    align-items:center;
    justify-content:center;
    background:linear-gradient(180deg, #58a8ff, #0f63f4);
    color:#fff;
    font-size:1.35rem;
    box-shadow:0 14px 24px rgba(14,99,244,.20);
    flex:0 0 auto;
}

.verify-title-block h1{
    margin:0;
    color:var(--verify-heading);
    font-size:2.5rem;
    line-height:1.04;
    font-weight:900;
    letter-spacing:-.6px;
}

.verify-title-block p{
    margin:6px 0 0;
    color:var(--verify-text);
    font-size:1rem;
    font-weight:700;
}

.verify-hero-text{
    display:grid;
    gap:6px;
    color:#234665;
    font-size:1.05rem;
    line-height:1.65;
}

.verify-hero-text .lang-alt{
    color:#385a7b;
}

.verify-pills{
    display:flex;
    flex-wrap:wrap;
    gap:10px;
    margin-top:18px;
}

.verify-pill{
    display:inline-flex;
    align-items:center;
    gap:9px;
    padding:10px 14px;
    border-radius:999px;
    background:rgba(255,255,255,.38);
    border:1px solid rgba(255,255,255,.42);
    box-shadow:0 8px 18px rgba(15,23,42,.06);
    color:#234665;
    font-size:.92rem;
    font-weight:800;
}

.verify-pill-icon{
    width:24px;
    height:24px;
    border-radius:50%;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    background:#fff;
    color:var(--verify-primary);
    font-size:.86rem;
    box-shadow:0 4px 10px rgba(14,99,244,.08);
}

.verify-hero-flag{
    position:absolute;
    right:24px;
    top:14px;
    width:122px;
    opacity:.15;
    filter:drop-shadow(0 12px 30px rgba(3,37,76,0.06));
    transform:rotate(-5deg);
    pointer-events:none;
}

/* FORM CARD */
.verify-main-card{
    border-radius:30px;
    padding:26px;
    background:var(--verify-card-bg);
    border:1px solid rgba(255,255,255,.64);
    box-shadow:var(--verify-shadow);
}

.verify-card-header{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:16px;
    margin-bottom:18px;
}

.verify-card-header h2{
    margin:0;
    color:var(--verify-heading);
    font-size:1.45rem;
    font-weight:900;
    letter-spacing:-.2px;
}

.verify-card-header p{
    margin:4px 0 0;
    color:var(--verify-muted);
    font-size:.96rem;
}

.verify-status{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:10px 14px;
    border-radius:999px;
    background:linear-gradient(180deg, rgba(236,248,255,.95), rgba(255,255,255,.88));
    border:1px solid rgba(190,214,245,.90);
    color:#2f577f;
    font-size:.88rem;
    font-weight:800;
    white-space:nowrap;
}

.verify-status-dot{
    width:10px;
    height:10px;
    border-radius:50%;
    background:#15c56d;
    box-shadow:0 0 0 0 rgba(21,197,109,.6);
    animation:statusPulse 1.8s infinite;
}

@keyframes statusPulse{
    70%{box-shadow:0 0 0 10px rgba(21,197,109,0)}
    100%{box-shadow:0 0 0 0 rgba(21,197,109,0)}
}

.verify-form{
    display:grid;
    grid-template-columns:minmax(0,1fr) 230px;
    gap:16px;
    align-items:start;
}

.verify-left{
    display:grid;
    gap:14px;
}

.verify-input-wrap{
    position:relative;
}

.verify-input-icon{
    position:absolute;
    left:18px;
    top:50%;
    transform:translateY(-50%);
    width:30px;
    height:30px;
    border-radius:10px;
    display:flex;
    align-items:center;
    justify-content:center;
    background:linear-gradient(180deg,#eef6ff,#fff);
    color:var(--verify-primary);
    box-shadow:0 6px 14px rgba(14,99,244,.08);
    font-size:1rem;
    pointer-events:none;
}

.verify-input{
    width:100%;
    height:66px;
    padding:16px 18px 16px 58px !important;
    border-radius:18px !important;
    border:2px solid #bdd2f5 !important;
    background:rgba(255,255,255,.98) !important;
    font-size:1.05rem !important;
    color:#20324b !important;
    box-shadow:inset 0 1px 0 rgba(255,255,255,.7), 0 10px 20px rgba(15,23,42,.05);
    transition:border-color .2s ease, box-shadow .2s ease, transform .2s ease;
}

.verify-input:focus{
    border-color:var(--verify-primary) !important;
    box-shadow:0 0 0 4px rgba(14,99,244,.12), 0 14px 24px rgba(14,99,244,.10) !important;
    transform:translateY(-1px);
}

.verify-input::placeholder{
    color:#97a7be;
}

.verify-btn-wrap{
    display:grid;
}

.verify-btn{
    width:100%;
    height:66px;
    border:none;
    border-radius:18px;
    background:linear-gradient(135deg,var(--verify-primary),var(--verify-primary-2) 60%, #105fe0);
    color:#fff;
    font-size:1.18rem;
    font-weight:900;
    letter-spacing:.2px;
    box-shadow:var(--verify-blue-shadow), inset 0 -4px 0 rgba(0,0,0,.08);
    transition:transform .2s ease, box-shadow .2s ease;
}

.verify-btn:hover{
    transform:translateY(-2px);
    box-shadow:0 22px 40px rgba(14,99,244,.28), inset 0 -4px 0 rgba(0,0,0,.08);
}

/* HELP AREA */
.verify-help-grid{
    display:grid;
    grid-template-columns:repeat(2, minmax(0,1fr));
    gap:14px;
}

.verify-help-box{
    border-radius:20px;
    padding:16px 18px;
    background:linear-gradient(180deg, rgba(255,255,255,.70), rgba(255,255,255,.42));
    border:1px solid rgba(255,255,255,.52);
    box-shadow:var(--verify-soft-shadow);
}

.verify-help-box strong{
    display:block;
    margin-bottom:6px;
    color:var(--verify-ink);
    font-size:1rem;
}

.verify-help-box span,
.verify-help-box small{
    margin:0;
    display:block;
    color:var(--verify-muted);
    font-size:.95rem;
    line-height:1.55;
}

.verify-help-box .lang-alt{
    margin-top:4px;
}

.verify-note{
    display:flex;
    align-items:flex-start;
    gap:12px;
    border-radius:18px;
    padding:14px 16px;
    background:linear-gradient(180deg, rgba(235,244,255,.86), rgba(255,255,255,.65));
    border:1px solid rgba(190,214,245,.85);
    color:#3e5f80;
    font-size:.95rem;
    line-height:1.6;
    box-shadow:var(--verify-soft-shadow);
}

.verify-note-icon{
    width:28px;
    height:28px;
    border-radius:10px;
    display:flex;
    align-items:center;
    justify-content:center;
    background:#fff;
    color:var(--verify-primary);
    flex:0 0 auto;
    box-shadow:0 4px 10px rgba(14,99,244,.08);
    font-size:.95rem;
}

@media (max-width: 991px){
    .verify-form{
        grid-template-columns:1fr;
    }

    .verify-btn-wrap{
        width:100%;
    }

    .verify-help-grid{
        grid-template-columns:1fr;
    }

    .verify-card-header{
        flex-direction:column;
        align-items:flex-start;
    }

    .verify-hero-flag{
        display:none;
    }
}

@media (max-width: 768px){
    .verify-page{
        padding:18px 0 26px;
    }

    .verify-bg-flag,
    .verify-bg-flag-2{
        display:none;
    }

    .verify-hero-card,
    .verify-main-card{
        border-radius:22px;
        padding:18px;
    }

    .verify-hero-top{
        align-items:flex-start;
    }

    .verify-badge{
        width:46px;
        height:46px;
        border-radius:14px;
        font-size:1.15rem;
    }

    .verify-title-block h1{
        font-size:1.95rem;
    }

    .verify-title-block p{
        font-size:.92rem;
    }

    .verify-hero-text{
        font-size:.97rem;
    }

    .verify-pill{
        font-size:.84rem;
        padding:8px 12px;
    }

    .verify-card-header h2{
        font-size:1.22rem;
    }

    .verify-input{
        height:58px;
        font-size:.98rem !important;
    }

    .verify-btn{
        height:58px;
        font-size:1.05rem;
    }

    .verify-help-box{
        border-radius:16px;
        padding:14px;
    }

    .verify-note{
        border-radius:16px;
        padding:12px 14px;
    }
}
</style>

<section class="verify-page">
    <img src="assets/img/sidama.svg" alt="" class="verify-bg-flag" aria-hidden="true">
    <img src="assets/img/sidama.svg" alt="" class="verify-bg-flag-2" aria-hidden="true">

    <div class="container verify-wrap">
        <div class="row justify-content-center">
            <div class="col-12 col-xl-10">
                <div class="verify-shell">
                    <div class="verify-hero-card">
                        <img src="assets/img/sidama.svg" alt="" class="verify-hero-flag" aria-hidden="true">

                        <div class="verify-hero-content">
                            <div class="verify-hero-top">
                                <div class="verify-badge">🪪</div>
                                <div class="verify-title-block">
                                    <h1>Verify a Certificate</h1>
                                    <p>Public verification portal</p>
                                </div>
                            </div>

                            <div class="verify-hero-text">
                                <span>Enter the certificate number shown on the document. Verification is instant and secure.</span>
                                <span class="lang-alt">በሰነዱ ላይ ያለውን የምስክር ወረቀት ቁጥር ያስገቡ።</span>
                                <span class="lang-alt">Misikiru woraqati aana noo kiiro wori.</span>
                            </div>
                        </div>
                    </div>

                    <div class="verify-main-card">
                        <div class="verify-card-header">
                            <div>
                                <h2>Certificate Number Lookup</h2>
                                <p>Search using the exact certificate number from the document.</p>
                            </div>

                            <div class="verify-status">
                                <span class="verify-status-dot"></span>
                                Verification service active
                            </div>
                        </div>

                        <form method="post" action="result.php" class="verify-form">
                            <div class="verify-left">
                                <div class="verify-input-wrap">
                                    <label class="visually-hidden" for="rollid">Certificate Number</label>
                                    <span class="verify-input-icon">🔎</span>
                                    <input
                                        id="rollid"
                                        name="rollid"
                                        type="search"
                                        class="form-control verify-input"
                                        placeholder="SN-OCAA-CZUA-2025-2029-00-0000"
                                        required
                                        aria-label="Certificate number"
                                    >
                                </div>

                                <div class="verify-help-grid">
                                    <div class="verify-help-box">
                                        <strong>Tip</strong>
                                        <span>Copy the number without spaces or dashes.</span>
                                        <small class="lang-alt">ፍንጭ፦ ቁጥሩን ያለ ክፍተት ወይም ያለ ሰረዝ ይቅዱ።</small>
                                        <small class="lang-alt">Mashalaqqe: Kiiro fulaanchu woy xorshu (dash) nookkihunni wori.</small>
                                    </div>

                                    <div class="verify-help-box">
                                        <strong>Need assistance?</strong>
                                        <span>If you don't have the full number, contact the issuing center for assistance.</span>
                                        <small class="lang-alt">ሙሉው ቁጥር ከሌለዎት፣ ለእርዳታ ሰጪውን ማዕከል ያነጋግሩ።</small>
                                        <small class="lang-alt">Woyyado kiiro nookkiheha ikkiro, kaa'lo afi'rate borro tana qixxeessitino kifile ledo xaadi.</small>
                                    </div>
                                </div>

                                <div class="verify-note">
                                    <span class="verify-note-icon">ℹ️</span>
                                    <div>Enter the certificate number exactly as written on the document for the most accurate result.</div>
                                </div>
                            </div>

                            <div class="verify-btn-wrap">
                                <button class="btn verify-btn" type="submit">Verify Now</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>