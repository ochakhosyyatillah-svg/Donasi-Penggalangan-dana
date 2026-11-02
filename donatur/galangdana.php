<?php
$page_title   = 'Galang Dana - SIBERASA';
$page_desc    = 'Mulai galang dana untuk program sosial, pendidikan, kemanusiaan, dan lainnya bersama SIBERASA.';
$current_page = 'galangdana';

require_once '../includes/header.php';
?>

<!-- Google Fonts: Caveat for cursive accent text -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Caveat:wght@600;700&display=swap" rel="stylesheet">

<!-- ═══════════════════════════════════════════════════════════════════
     HALAMAN GALANG DANA - SIBERASA
════════════════════════════════════════════════════════════════════ -->
<div class="gd-page-wrapper">
    <div class="container gd-main-container">
        
        <!-- ── TOP HERO SECTION ── -->
        <section class="gd-top-section">
            <!-- Left Side: Text & Actions -->
            <div class="gd-top-text">
                <div class="gd-pill">
                    <i class="fas fa-bullhorn"></i> Galang Dana
                </div>
                
                <h1 class="gd-main-title">
                    Wujudkan Program<br>
                    <span>Penggalangan Dana Anda</span>
                </h1>
                
                <p class="gd-main-desc">
                    Ajukan program penggalangan dana untuk mendukung kegiatan sosial, kemanusiaan, pendidikan, dan berbagai inisiatif positif lainnya di lingkungan kampus dan sekitarnya.
                </p>

                <div class="gd-actions">
                    <a href="/Donasi/auth/register.php" class="btn-ajukan-hero">
                        <i class="fas fa-plus-circle"></i> Ajukan Program
                    </a>
                    <a href="#mengapaSection" class="link-pelajari">
                        Pelajari lebih lanjut &nbsp;&rarr;
                    </a>
                </div>
            </div>
            
            <!-- Right Side: Exact Illustration & Script Accent -->
            <div class="gd-top-illustration">
                <div class="gd-illu-container">
                    <img src="/Donasi/assets/img/galang_dana_box.jpg" alt="Ilustrasi Galang Dana" class="gd-illu-img">
                    <div class="gd-script-note">
                        <span>Bersama</span>
                        <span>kita bisa</span>
                        <span>lebih banyak</span>
                    </div>
                </div>
            </div>
        </section>


        <!-- ── MIDDLE SECTION ── -->
        <section class="gd-bottom-section" id="mengapaSection">
            <!-- Left Side: 4 Features -->
            <div class="gd-features-area">
                <h2 class="gd-features-title">Mengapa Menggalang Dana di SIBERASA?</h2>
                <div class="gd-features-grid">
                    
                    <div class="gd-feature-item">
                        <div class="gd-feature-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h3>Mudah &amp; Cepat</h3>
                        <p>Proses pengajuan program sederhana dan praktis.</p>
                    </div>

                    <div class="gd-feature-item">
                        <div class="gd-feature-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h3>Transparan</h3>
                        <p>Setiap dana yang terkumpul dapat dipantau dengan jelas.</p>
                    </div>

                    <div class="gd-feature-item">
                        <div class="gd-feature-icon">
                            <i class="fas fa-building-columns"></i>
                        </div>
                        <h3>Dukungan Kampus</h3>
                        <p>Bersama wujudkan program bermanfaat untuk kampus dan masyarakat.</p>
                    </div>

                    <div class="gd-feature-item">
                        <div class="gd-feature-icon">
                            <i class="far fa-heart"></i>
                        </div>
                        <h3>Dampak Nyata</h3>
                        <p>Bersama wujudkan perubahan positif untuk sesama.</p>
                    </div>

                </div>
            </div>

            <!-- Right Side: Action Card -->
            <div class="gd-action-area">
                <div class="gd-action-card">
                    <div class="gd-action-header">
                        <div class="gd-action-icon">
                            <i class="fas fa-file-circle-plus"></i>
                        </div>
                        <div class="gd-action-titles">
                            <h3>Ajukan Program<br>Galang Dana</h3>
                        </div>
                    </div>

                    <p class="gd-action-desc">
                        Ingin menggalang dana untuk kegiatan organisasi, UKM, unit, lembaga, atau program lainnya?
                    </p>

                    <a href="/Donasi/auth/register.php" class="btn-ajukan-card">
                        Buat Akun &amp; Ajukan &nbsp;&rarr;
                    </a>
                </div>
            </div>
        </section>

    </div>
</div>


<!-- ═══════════════════════════════════════════════════════════════════
     CSS STYLING KHUSUS HALAMAN GALANG DANA
════════════════════════════════════════════════════════════════════ -->
<style>
/* Base Styles */
.gd-page-wrapper {
    background-color: #ffffff;
    min-height: calc(100vh - 70px);
    font-family: 'Poppins', sans-serif;
    padding-top: 50px;
    padding-bottom: 60px;
}

.gd-main-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 24px;
}

/* ── 1. TOP HERO SECTION ── */
.gd-top-section {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 50px;
    margin-bottom: 70px;
}

.gd-top-text {
    flex: 1.1;
    max-width: 580px;
}

.gd-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background-color: #eef5ff;
    color: #1a73e8;
    padding: 7px 18px;
    border-radius: 50px;
    font-size: 0.88rem;
    font-weight: 600;
    margin-bottom: 22px;
}

.gd-main-title {
    font-size: clamp(2.2rem, 3.8vw, 3rem);
    font-weight: 800;
    color: #1a202c;
    line-height: 1.22;
    margin-bottom: 18px;
    letter-spacing: -0.5px;
}

.gd-main-title span {
    color: #1a73e8;
}

.gd-main-desc {
    font-size: 1rem;
    color: #4a5568;
    line-height: 1.65;
    margin-bottom: 28px;
}

.gd-actions {
    display: flex;
    align-items: center;
    gap: 24px;
}

.btn-ajukan-hero {
    background-color: #1a73e8;
    color: #ffffff;
    border: none;
    border-radius: 50px;
    padding: 13px 28px;
    font-size: 0.95rem;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 4px 16px rgba(26,115,232,0.32);
    transition: all 0.25s ease;
    text-decoration: none;
}

.btn-ajukan-hero:hover {
    background-color: #1557b0;
    transform: translateY(-2px);
    box-shadow: 0 8px 22px rgba(26,115,232,0.4);
    color: #ffffff;
}

.link-pelajari {
    color: #1a73e8;
    font-size: 0.92rem;
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    transition: transform 0.2s;
}

.link-pelajari:hover {
    transform: translateX(3px);
}

/* Right Illustration */
.gd-top-illustration {
    flex: 0.9;
    display: flex;
    justify-content: center;
    align-items: center;
}

.gd-illu-container {
    position: relative;
    width: 100%;
    max-width: 440px;
    display: flex;
    justify-content: center;
    align-items: center;
}

.gd-illu-img {
    width: 100%;
    max-width: 380px;
    height: auto;
    object-fit: contain;
    border-radius: 50%;
    filter: drop-shadow(0 14px 28px rgba(26,115,232,0.12));
    transition: transform 0.3s ease;
}

.gd-illu-img:hover {
    transform: scale(1.02);
}

/* Handwritten Cursive Note */
.gd-script-note {
    position: absolute;
    right: -15px;
    top: 22%;
    display: flex;
    flex-direction: column;
    font-family: 'Caveat', cursive, 'Comic Sans MS', sans-serif;
    font-size: 1.95rem;
    font-weight: 700;
    color: #1a73e8;
    line-height: 1.08;
    transform: rotate(-6deg);
    pointer-events: none;
    text-shadow: 0 2px 8px rgba(255,255,255,0.8);
}


/* ── 2. MIDDLE SECTION ── */
.gd-bottom-section {
    display: flex;
    gap: 40px;
    margin-bottom: 80px;
    align-items: stretch;
}

.gd-features-area {
    flex: 2.1;
}

.gd-features-title {
    font-size: 1.35rem;
    font-weight: 700;
    color: #15325e;
    margin-bottom: 26px;
    letter-spacing: -0.3px;
}

.gd-features-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
}

.gd-feature-item {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
}

.gd-feature-icon {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background-color: #eef5fc;
    display: flex;
    justify-content: center;
    align-items: center;
    color: #1a73e8;
    font-size: 1.25rem;
    margin-bottom: 16px;
    transition: transform 0.25s, background-color 0.25s;
}

.gd-feature-item:hover .gd-feature-icon {
    background-color: #e1edfb;
    transform: scale(1.08);
}

.gd-feature-item h3 {
    font-size: 0.95rem;
    font-weight: 700;
    color: #1a202c;
    margin-bottom: 8px;
    line-height: 1.3;
}

.gd-feature-item p {
    font-size: 0.8rem;
    color: #718096;
    line-height: 1.5;
    margin: 0;
}

/* Right Action Card */
.gd-action-area {
    flex: 1;
    min-width: 320px;
    display: flex;
}

.gd-action-card {
    background-color: #f3f8fd;
    border-radius: 18px;
    padding: 30px 26px;
    width: 100%;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    border: 1px solid rgba(226, 232, 240, 0.7);
    box-shadow: 0 4px 18px rgba(0, 0, 0, 0.02);
}

.gd-action-header {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 14px;
}

.gd-action-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background-color: #e1edfb;
    color: #1a73e8;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.45rem;
    flex-shrink: 0;
}

.gd-action-titles h3 {
    font-size: 1.15rem;
    font-weight: 700;
    color: #15325e;
    line-height: 1.25;
    margin: 0;
}

.gd-action-desc {
    font-size: 0.85rem;
    color: #4a5568;
    line-height: 1.55;
    margin-bottom: 24px;
}

.btn-ajukan-card {
    width: 100%;
    background-color: #1a73e8;
    color: #ffffff !important;
    border: none;
    border-radius: 10px;
    padding: 13px 18px;
    font-size: 0.92rem;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    justify-content: center;
    align-items: center;
    text-decoration: none !important;
    transition: background-color 0.25s, transform 0.2s;
    box-shadow: 0 4px 12px rgba(26,115,232,0.22);
}

.btn-ajukan-card:hover {
    background-color: #1557b0;
    color: #ffffff !important;
    transform: translateY(-1px);
}



/* ── RESPONSIVE MEDIA QUERIES ── */
@media (max-width: 992px) {
    .gd-top-section {
        flex-direction: column;
        text-align: center;
        gap: 36px;
    }
    .gd-top-text {
        max-width: 100%;
    }
    .gd-actions {
        justify-content: center;
    }
    .gd-script-note {
        right: 0px;
        top: 15%;
        font-size: 1.7rem;
    }
    .gd-bottom-section {
        flex-direction: column;
        gap: 32px;
    }
    .gd-features-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 26px;
    }
    .gd-feature-item {
        align-items: center;
        text-align: center;
    }
    .gd-action-area {
        width: 100%;
        min-width: 0;
    }
    .gd-bottom-banner {
        flex-direction: column;
        text-align: center;
        gap: 18px;
    }
    .gd-banner-left {
        flex-direction: column;
    }
}

@media (max-width: 576px) {
    .gd-main-title {
        font-size: 2.1rem;
    }
    .gd-features-grid {
        grid-template-columns: 1fr;
    }
    .gd-actions {
        flex-direction: column;
        width: 100%;
    }
    .btn-ajukan-hero {
        width: 100%;
        justify-content: center;
    }
    .gd-form-row {
        grid-template-columns: 1fr;
    }
}
</style>




<?php require_once '../includes/footer.php'; ?>
