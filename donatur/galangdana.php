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
                    <button type="button" class="btn-ajukan-hero" onclick="bukaModalPengajuan()">
                        <i class="fas fa-plus-circle"></i> Ajukan Program
                    </button>
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

                    <button type="button" class="btn-ajukan-card" onclick="bukaModalPengajuan()">
                        Ajukan Program &nbsp;&rarr;
                    </button>
                </div>
            </div>
        </section>


        <!-- ── BOTTOM STATUS BANNER ── -->
        <section class="gd-bottom-banner">
            <div class="gd-banner-left">
                <div class="gd-banner-icon">
                    <i class="fas fa-info"></i>
                </div>
                <div class="gd-banner-text">
                    <h4>Sudah punya program yang berjalan?</h4>
                    <p>Pantau status penggalangan dana Anda, mulai dari proses verifikasi hingga penyaluran dana.</p>
                </div>
            </div>
            <a href="#" class="gd-banner-link" onclick="bukaModalStatus(event)">
                Lihat Status &nbsp;&rarr;
            </a>
        </section>

    </div>
</div>


<!-- ═══════════════════════════════════════════════════════════════════
     MODAL PENGAJUAN PROGRAM
════════════════════════════════════════════════════════════════════ -->
<div class="modal-overlay" id="modalPengajuan" style="display:none;" onclick="tutupModalPengajuanOnBg(event)">
    <div class="gd-modal-box" onclick="event.stopPropagation()">
        <button type="button" class="gd-modal-close" onclick="tutupModalPengajuan()" aria-label="Tutup">
            <i class="fas fa-times"></i>
        </button>

        <div class="gd-modal-header">
            <div class="gd-modal-icon-wrap">
                <i class="fas fa-bullhorn"></i>
            </div>
            <h3>Formulir Pengajuan Galang Dana</h3>
            <p>Lengkapi formulir di bawah ini untuk mengajukan inisiatif program baru di SIBERASA.</p>
        </div>

        <form id="formPengajuanProgram" onsubmit="submitPengajuan(event)">
            <div class="gd-form-group">
                <label class="gd-label">Nama Organisasi / Komunitas / Pemohon <span class="req">*</span></label>
                <input type="text" id="progOrg" class="gd-input" placeholder="Contoh: Himpunan Mahasiswa Informatika / UKM Peduli Sesama" required>
            </div>

            <div class="gd-form-group">
                <label class="gd-label">Judul Program Penggalangan Dana <span class="req">*</span></label>
                <input type="text" id="progJudul" class="gd-input" placeholder="Contoh: Bantuan Pengadaan Buku & Sarana Belajar Pelosok" required>
            </div>

            <div class="gd-form-row">
                <div class="gd-form-group">
                    <label class="gd-label">Kategori <span class="req">*</span></label>
                    <select id="progKat" class="gd-select" required>
                        <option value="Pendidikan">Pendidikan</option>
                        <option value="Sosial">Sosial</option>
                        <option value="Kemanusiaan">Kemanusiaan / Bencana</option>
                        <option value="Kesehatan">Kesehatan</option>
                        <option value="Keagamaan">Keagamaan</option>
                        <option value="Lingkungan">Lingkungan</option>
                    </select>
                </div>
                <div class="gd-form-group">
                    <label class="gd-label">Target Donasi (Rp) <span class="req">*</span></label>
                    <input type="number" id="progTarget" class="gd-input" placeholder="50000000" min="500000" step="50000" required>
                </div>
            </div>

            <div class="gd-form-group">
                <label class="gd-label">Nomor WhatsApp Aktif <span class="req">*</span></label>
                <input type="tel" id="progWa" class="gd-input" placeholder="08xxxxxxxxxx (untuk konfirmasi verifikasi)" required>
            </div>

            <div class="gd-form-group">
                <label class="gd-label">Ringkasan Tujuan &amp; Urgensi Program <span class="req">*</span></label>
                <textarea id="progDesc" class="gd-textarea" rows="3" placeholder="Jelaskan secara singkat latar belakang dan tujuan penggalangan dana..." required></textarea>
            </div>

            <div class="gd-modal-actions">
                <button type="button" class="btn-cancel" onclick="tutupModalPengajuan()">Batal</button>
                <button type="submit" class="btn-submit">Kirim Pengajuan</button>
            </div>
        </form>
    </div>
</div>


<!-- ═══════════════════════════════════════════════════════════════════
     MODAL LIHAT STATUS
════════════════════════════════════════════════════════════════════ -->
<div class="modal-overlay" id="modalStatus" style="display:none;" onclick="tutupModalStatusOnBg(event)">
    <div class="gd-modal-box" onclick="event.stopPropagation()">
        <button type="button" class="gd-modal-close" onclick="tutupModalStatus()" aria-label="Tutup">
            <i class="fas fa-times"></i>
        </button>

        <div class="gd-modal-header">
            <div class="gd-modal-icon-wrap" style="background:#eef5fc; color:#1a73e8;">
                <i class="fas fa-search"></i>
            </div>
            <h3>Cek Status Pengajuan Program</h3>
            <p>Masukkan kode pengajuan atau nomor WhatsApp yang Anda daftarkan saat mengajukan program.</p>
        </div>

        <form id="formCekStatus" onsubmit="submitCekStatus(event)">
            <div class="gd-form-group">
                <label class="gd-label">Kode Pengajuan / Nomor WhatsApp</label>
                <input type="text" id="inputKodeStatus" class="gd-input" placeholder="Contoh: GD-2026-0812 atau 08123456789" required>
            </div>

            <div id="statusResultBox" style="display:none; background:#f0f9ff; border:1px solid #bae6fd; border-radius:10px; padding:14px; margin-bottom:16px; font-size:0.86rem; color:#0369a1;">
                <strong><i class="fas fa-check-circle" style="color:#0284c7;"></i> Status Pengajuan Ditemukan:</strong><br>
                <div style="margin-top:6px; color:#334155;">
                    Kode: <span id="resKode" style="font-weight:700; color:#0284c7;"></span><br>
                    Status: <span style="background:#dcfce7; color:#166534; padding:2px 8px; border-radius:4px; font-weight:600;">Dalam Tahap Verifikasi</span><br>
                    <small style="color:#64748b; margin-top:4px; display:block;">Tim kami sedang meninjau kelengkapan dokumen pengajuan Anda (estimasi 1x24 jam).</small>
                </div>
            </div>

            <div class="gd-modal-actions">
                <button type="button" class="btn-cancel" onclick="tutupModalStatus()">Tutup</button>
                <button type="submit" class="btn-submit">Cek Status</button>
            </div>
        </form>
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
    margin-bottom: 55px;
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
    color: #ffffff;
    border: none;
    border-radius: 10px;
    padding: 13px 18px;
    font-size: 0.92rem;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    justify-content: center;
    align-items: center;
    transition: background-color 0.25s, transform 0.2s;
    box-shadow: 0 4px 12px rgba(26,115,232,0.22);
}

.btn-ajukan-card:hover {
    background-color: #1557b0;
    transform: translateY(-1px);
}


/* ── 3. BOTTOM BANNER ── */
.gd-bottom-banner {
    background-color: #edf5fd;
    border-radius: 14px;
    padding: 20px 28px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border: 1px solid rgba(26,115,232,0.08);
}

.gd-banner-left {
    display: flex;
    align-items: center;
    gap: 16px;
}

.gd-banner-icon {
    width: 34px;
    height: 34px;
    background-color: #1a73e8;
    color: #ffffff;
    border-radius: 50%;
    display: flex;
    justify-content: center;
    align-items: center;
    font-size: 0.95rem;
    flex-shrink: 0;
}

.gd-banner-text h4 {
    font-size: 0.98rem;
    font-weight: 700;
    color: #1a73e8;
    margin-bottom: 3px;
}

.gd-banner-text p {
    font-size: 0.83rem;
    color: #4a5568;
    margin: 0;
    line-height: 1.45;
}

.gd-banner-link {
    color: #1a73e8;
    font-weight: 600;
    font-size: 0.92rem;
    display: inline-flex;
    align-items: center;
    text-decoration: none;
    white-space: nowrap;
    padding: 6px 12px;
    border-radius: 6px;
    transition: background 0.2s, transform 0.2s;
}

.gd-banner-link:hover {
    background-color: rgba(26,115,232,0.08);
    transform: translateX(2px);
}


/* ── MODALS STYLING ── */
.gd-modal-box {
    background: #ffffff;
    border-radius: 18px;
    max-width: 560px;
    width: 92%;
    padding: 32px 28px;
    position: relative;
    box-shadow: 0 16px 48px rgba(0,0,0,0.18);
    animation: gdModalPop 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}
@keyframes gdModalPop {
    from { opacity: 0; transform: scale(0.92); }
    to { opacity: 1; transform: scale(1); }
}

.gd-modal-close {
    position: absolute;
    top: 16px;
    right: 16px;
    background: transparent;
    border: none;
    font-size: 1.2rem;
    color: #94a3b8;
    cursor: pointer;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.2s, color 0.2s;
}
.gd-modal-close:hover {
    background: #f1f5f9;
    color: #1e293b;
}

.gd-modal-header {
    text-align: center;
    margin-bottom: 24px;
}
.gd-modal-icon-wrap {
    width: 54px;
    height: 54px;
    border-radius: 50%;
    background: #eef5ff;
    color: #1a73e8;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    margin-bottom: 12px;
}
.gd-modal-header h3 {
    font-size: 1.25rem;
    font-weight: 700;
    color: #15325e;
    margin-bottom: 6px;
}
.gd-modal-header p {
    font-size: 0.83rem;
    color: #64748b;
    margin: 0;
    line-height: 1.5;
}

.gd-form-group {
    margin-bottom: 16px;
}
.gd-form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
}
.gd-label {
    display: block;
    font-size: 0.82rem;
    font-weight: 600;
    color: #334155;
    margin-bottom: 6px;
}
.gd-label .req {
    color: #ef4444;
}

.gd-input, .gd-select, .gd-textarea {
    width: 100%;
    padding: 10px 14px;
    border-radius: 8px;
    border: 1.5px solid #cbd5e1;
    font-family: inherit;
    font-size: 0.88rem;
    color: #1e293b;
    background: #f8fafc;
    transition: all 0.2s;
    outline: none;
}
.gd-input:focus, .gd-select:focus, .gd-textarea:focus {
    border-color: #1a73e8;
    background: #ffffff;
    box-shadow: 0 0 0 3px rgba(26,115,232,0.12);
}

.gd-modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    margin-top: 24px;
    padding-top: 16px;
    border-top: 1px solid #f1f5f9;
}
.btn-cancel {
    background: #f1f5f9;
    color: #475569;
    border: none;
    padding: 10px 20px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.88rem;
    cursor: pointer;
    transition: background 0.2s;
}
.btn-cancel:hover {
    background: #e2e8f0;
}
.btn-submit {
    background: #1a73e8;
    color: #ffffff;
    border: none;
    padding: 10px 22px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.88rem;
    cursor: pointer;
    transition: background 0.2s;
}
.btn-submit:hover {
    background: #1557b0;
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


<!-- ═══════════════════════════════════════════════════════════════════
     JAVASCRIPT INTERAKTIF
════════════════════════════════════════════════════════════════════ -->
<script>
// Modal Pengajuan Handler
function bukaModalPengajuan() {
    document.getElementById('modalPengajuan').style.display = 'flex';
}
function tutupModalPengajuan() {
    document.getElementById('modalPengajuan').style.display = 'none';
}
function tutupModalPengajuanOnBg(e) {
    if (e.target.id === 'modalPengajuan') {
        tutupModalPengajuan();
    }
}

function submitPengajuan(e) {
    e.preventDefault();
    const judul = document.getElementById('progJudul').value;
    const kode = 'GD-' + Math.floor(1000 + Math.random() * 9000);
    
    tutupModalPengajuan();
    document.getElementById('formPengajuanProgram').reset();

    // Show nice alert/toast
    if (typeof showToast === 'function') {
        showToast('Pengajuan program "' + judul + '" berhasil dikirim! Kode Tiket: ' + kode, 'success');
    } else {
        alert('Pengajuan program berhasil dikirim!\n\nKode Tiket Anda: ' + kode + '\nTim kami akan menghubungi Anda via WhatsApp dalam 1x24 jam.');
    }
}

// Modal Cek Status Handler
function bukaModalStatus(e) {
    if (e) e.preventDefault();
    document.getElementById('statusResultBox').style.display = 'none';
    document.getElementById('modalStatus').style.display = 'flex';
}
function tutupModalStatus() {
    document.getElementById('modalStatus').style.display = 'none';
}
function tutupModalStatusOnBg(e) {
    if (e.target.id === 'modalStatus') {
        tutupModalStatus();
    }
}

function submitCekStatus(e) {
    e.preventDefault();
    const kode = document.getElementById('inputKodeStatus').value.trim();
    if (!kode) return;

    document.getElementById('resKode').textContent = kode;
    document.getElementById('statusResultBox').style.display = 'block';
}
</script>

<?php require_once '../includes/footer.php'; ?>
