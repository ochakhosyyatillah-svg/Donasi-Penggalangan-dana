<?php
$page_title   = 'Cek Status Pengajuan & Donasi - SIBERASA';
$page_desc    = 'Cek status verifikasi pengajuan program atau donasi Anda secara real-time di SIBERASA.';
$current_page = 'cek_status';

// Ambil kode dari GET parameter jika ada
$kode = isset($_GET['kode']) ? trim($_GET['kode']) : (isset($_GET['trx']) ? trim($_GET['trx']) : '');

require_once 'includes/header.php';
?>

<!-- ═══════════════════════════════════════════════════════════════════
     HALAMAN CEK STATUS PENGAJUAN / DONASI - SESUAI DESAIN REFERENSI
════════════════════════════════════════════════════════════════════ -->
<div class="cs-wrapper">
    <div class="container cs-container">

        <!-- ═══════════════════════════════════════════════════════════
             TAMPILAN 1: FORM PENCARIAN (SEARCH VIEW)
             ═══════════════════════════════════════════════════════════ -->
        <div id="csSearchView" class="cs-search-section" style="<?= !empty($kode) ? 'display: none;' : '' ?>">
            <div class="cs-search-card">
                <!-- Top Circular Search Icon -->
                <div class="cs-search-icon-circle">
                    <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="#1d70e2" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="7"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </div>

                <!-- Title & Subtitle -->
                <h1 class="cs-search-title">Cek Status Pengajuan / Donasi</h1>
                <p class="cs-search-subtitle">
                    Masukkan kode transaksi atau nomor pengajuan untuk melihat status pengajuan atau donasi Anda.
                </p>

                <!-- Search Form -->
                <form id="csFormCek" onsubmit="handleCekStatusSubmit(event)" class="cs-form">
                    <div class="cs-input-group">
                        <svg class="cs-input-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#718096" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="7"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                        <input 
                            type="text" 
                            id="csInputKode" 
                            class="cs-input-field" 
                            placeholder="Contoh: PGN-20261008-001" 
                            value="<?= htmlspecialchars($kode) ?>"
                            autocomplete="off"
                        >
                    </div>

                    <button type="submit" class="cs-btn-submit">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="7"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                        <span>Cek Status</span>
                    </button>
                </form>

                <!-- Helpful Quick Hints -->
                <div class="cs-hints">
                    <span>Contoh kode: </span>
                    <a href="javascript:void(0)" onclick="quickFill('PGN-20261008-001')">PGN-20261008-001</a>
                    <span class="cs-hint-sep">•</span>
                    <a href="javascript:void(0)" onclick="quickFill('DON20260912143001')">DON20260912143001</a>
                </div>
            </div>
        </div>


        <!-- ═══════════════════════════════════════════════════════════
             TAMPILAN 2: HASIL STATUS (RESULT VIEW)
             ═══════════════════════════════════════════════════════════ -->
        <div id="csResultView" class="cs-result-section" style="<?= empty($kode) ? 'display: none;' : '' ?>">
            
            <!-- Tombol Kembali -->
            <div class="cs-back-bar">
                <a href="javascript:void(0)" onclick="kembaliKePencarian()" class="cs-back-link">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
            </div>

            <!-- Judul Halaman Hasil -->
            <h1 class="cs-result-heading" id="resHeaderTitle">Status Pengajuan Donasi</h1>

            <!-- Grid 2 Kolom -->
            <div class="cs-result-grid">
                
                <!-- ── KOLOM KIRI: DETAIL & ALERT INFO ── -->
                <div class="cs-left-panel">
                    
                    <!-- Card Informasi Detail -->
                    <div class="cs-card cs-detail-card">
                        <div class="cs-detail-row">
                            <span class="cs-label" id="lblKode">Kode Pengajuan</span>
                            <span class="cs-value" id="valKode">PGN-20261008-001</span>
                        </div>

                        <div class="cs-detail-row">
                            <span class="cs-label" id="lblProgram">Nama Program</span>
                            <span class="cs-value" id="valProgram">Bantuan Pendidikan Anak Desa</span>
                        </div>

                        <div class="cs-detail-row" id="rowPengusul">
                            <span class="cs-label" id="lblPengusul">Pengusul</span>
                            <span class="cs-value" id="valPengusul">HIMA Informatika</span>
                        </div>

                        <div class="cs-detail-row" id="rowNominal" style="display: none;">
                            <span class="cs-label">Nominal Donasi</span>
                            <span class="cs-value" id="valNominal">Rp 100.000</span>
                        </div>

                        <div class="cs-detail-row">
                            <span class="cs-label" id="lblTanggal">Tanggal Pengajuan</span>
                            <span class="cs-value" id="valTanggal">08 Oktober 2026</span>
                        </div>

                        <div class="cs-detail-row">
                            <span class="cs-label">Status</span>
                            <div class="cs-value">
                                <span class="cs-badge cs-badge-warning" id="valBadgeStatus">Menunggu Verifikasi</span>
                            </div>
                        </div>
                    </div>

                    <!-- Card Alert Info -->
                    <div class="cs-info-banner">
                        <div class="cs-info-circle-icon">
                            <i class="fas fa-info"></i>
                        </div>
                        <div class="cs-info-text">
                            <p id="resInfoMessage">
                                Pengajuan Anda sedang dalam proses verifikasi oleh admin.<br>
                                Silakan cek kembali secara berkala atau hubungi kontak kami jika ada pertanyaan.
                            </p>
                        </div>
                    </div>

                </div>

                <!-- ── KOLOM KANAN: STEPPER PROSES PENGAJUAN ── -->
                <div class="cs-right-panel">
                    <div class="cs-card cs-stepper-card">
                        <h3 class="cs-stepper-title" id="resStepperTitle">Proses Pengajuan</h3>

                        <div class="cs-timeline" id="resTimelineContainer">
                            <!-- Step 1 -->
                            <div class="cs-step cs-step-done" id="step1">
                                <div class="cs-step-icon-wrap">
                                    <div class="cs-step-icon">
                                        <i class="fas fa-check"></i>
                                    </div>
                                    <div class="cs-step-line done" id="stepLine1"></div>
                                </div>
                                <div class="cs-step-content">
                                    <h4 class="cs-step-name" id="step1Title">Pengajuan Dikirim</h4>
                                    <span class="cs-step-date" id="step1Date">08 Okt 2026</span>
                                </div>
                            </div>

                            <!-- Step 2 -->
                            <div class="cs-step cs-step-active" id="step2">
                                <div class="cs-step-icon-wrap">
                                    <div class="cs-step-icon"></div>
                                    <div class="cs-step-line" id="stepLine2"></div>
                                </div>
                                <div class="cs-step-content">
                                    <h4 class="cs-step-name" id="step2Title">Sedang Diverifikasi</h4>
                                    <span class="cs-step-date" id="step2Date">-</span>
                                </div>
                            </div>

                            <!-- Step 3 -->
                            <div class="cs-step cs-step-pending" id="step3">
                                <div class="cs-step-icon-wrap">
                                    <div class="cs-step-icon"></div>
                                    <div class="cs-step-line" id="stepLine3"></div>
                                </div>
                                <div class="cs-step-content">
                                    <h4 class="cs-step-name" id="step3Title">Disetujui</h4>
                                    <span class="cs-step-date" id="step3Date">-</span>
                                </div>
                            </div>

                            <!-- Step 4 -->
                            <div class="cs-step cs-step-pending" id="step4">
                                <div class="cs-step-icon-wrap">
                                    <div class="cs-step-icon"></div>
                                </div>
                                <div class="cs-step-content">
                                    <h4 class="cs-step-name" id="step4Title">Program Dipublikasikan</h4>
                                    <span class="cs-step-date" id="step4Date">-</span>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

            </div>

        </div>

    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════
     CSS STYLING - PERSIS SESUAI GAMBAR REFERENSI
════════════════════════════════════════════════════════════════════ -->
<style>
/* Reset & Layout Container */
.cs-wrapper {
    background: #f4f8fc;
    min-height: calc(100vh - 120px);
    font-family: 'Poppins', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    padding: 40px 16px 80px;
    box-sizing: border-box;
}

.cs-container {
    max-width: 1080px;
    margin: 0 auto;
}

/* ═══════════════════════════════════════════════════════════════════
   1. TAMPILAN PENCARIAN (IMAGE 1)
   ════════════════════════════════════════════════════════════════ */
.cs-search-section {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 30px 0 60px;
    animation: fadeIn 0.3s ease-in-out;
}

.cs-search-card {
    background: #ffffff;
    border-radius: 24px;
    border: 1px solid #e2edf8;
    box-shadow: 0 10px 40px -10px rgba(15, 41, 74, 0.06), 0 1px 3px rgba(0, 0, 0, 0.02);
    width: 100%;
    max-width: 620px;
    padding: 48px 44px 44px;
    text-align: center;
    box-sizing: border-box;
}

/* Circular Top Search Icon */
.cs-search-icon-circle {
    width: 72px;
    height: 72px;
    border-radius: 50%;
    background-color: #e5f0fe;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 22px auto;
}

/* Title & Subtitle */
.cs-search-title {
    font-size: 1.65rem;
    font-weight: 700;
    color: #0d2b4f;
    margin: 0 0 12px 0;
    line-height: 1.3;
}

.cs-search-subtitle {
    font-size: 0.92rem;
    color: #556987;
    line-height: 1.55;
    margin: 0 auto 30px auto;
    max-width: 440px;
}

/* Form Styles */
.cs-form {
    width: 100%;
}

.cs-input-group {
    position: relative;
    width: 100%;
    margin-bottom: 16px;
}

.cs-input-icon {
    position: absolute;
    left: 18px;
    top: 50%;
    transform: translateY(-50%);
    pointer-events: none;
    color: #718096;
}

.cs-input-field {
    width: 100%;
    height: 52px;
    border: 1.5px solid #dce8f6;
    border-radius: 12px;
    padding: 12px 18px 12px 50px;
    font-size: 0.95rem;
    color: #1a202c;
    background-color: #ffffff;
    box-sizing: border-box;
    transition: all 0.2s ease;
    font-family: inherit;
}

.cs-input-field::placeholder {
    color: #94a3b8;
}

.cs-input-field:focus {
    border-color: #1d70e2;
    outline: none;
    box-shadow: 0 0 0 4px rgba(29, 112, 226, 0.12);
}

.cs-btn-submit {
    width: 100%;
    height: 50px;
    background: linear-gradient(180deg, #1d70e2 0%, #155bbd 100%);
    border: none;
    border-radius: 12px;
    color: #ffffff;
    font-size: 0.98rem;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    box-shadow: 0 4px 14px rgba(29, 112, 226, 0.32);
    transition: all 0.2s ease;
    font-family: inherit;
}

.cs-btn-submit:hover {
    background: linear-gradient(180deg, #1864cb 0%, #114e9f 100%);
    transform: translateY(-1px);
    box-shadow: 0 6px 18px rgba(29, 112, 226, 0.4);
}

.cs-btn-submit:active {
    transform: translateY(0);
}

/* Quick Hints */
.cs-hints {
    margin-top: 20px;
    font-size: 0.84rem;
    color: #8c9eb5;
}
.cs-hints a {
    color: #1d70e2;
    text-decoration: none;
    font-weight: 500;
}
.cs-hints a:hover {
    text-decoration: underline;
}
.cs-hint-sep {
    margin: 0 6px;
    color: #cbd5e1;
}


/* ═══════════════════════════════════════════════════════════════════
   2. TAMPILAN HASIL (IMAGE 2)
   ════════════════════════════════════════════════════════════════ */
.cs-result-section {
    animation: fadeIn 0.3s ease-in-out;
}

/* Tombol Kembali */
.cs-back-bar {
    margin-bottom: 16px;
}

.cs-back-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 0.88rem;
    font-weight: 600;
    color: #1d70e2;
    text-decoration: none;
    transition: transform 0.2s, color 0.2s;
}

.cs-back-link:hover {
    color: #155bbd;
    transform: translateX(-3px);
}

/* Judul Hasil */
.cs-result-heading {
    font-size: 1.7rem;
    font-weight: 700;
    color: #0d2b4f;
    margin: 0 0 24px 0;
    letter-spacing: -0.3px;
}

/* Grid Layout 2 Kolom */
.cs-result-grid {
    display: grid;
    grid-template-columns: 1.55fr 1fr;
    gap: 24px;
    align-items: start;
}

/* Common Card */
.cs-card {
    background: #ffffff;
    border-radius: 20px;
    border: 1px solid #e5edf6;
    box-shadow: 0 4px 18px rgba(15, 41, 74, 0.03);
    box-sizing: border-box;
}

/* ── Panel Kiri: Detail Card ── */
.cs-detail-card {
    padding: 34px 36px;
    margin-bottom: 20px;
}

.cs-detail-row {
    display: grid;
    grid-template-columns: 200px 1fr;
    gap: 16px;
    align-items: center;
    margin-bottom: 22px;
}

.cs-detail-row:last-child {
    margin-bottom: 0;
}

.cs-label {
    font-size: 0.95rem;
    color: #556987;
    font-weight: 500;
}

.cs-value {
    font-size: 0.95rem;
    color: #0d2b4f;
    font-weight: 700;
}

/* Badge Status */
.cs-badge {
    display: inline-block;
    padding: 6px 18px;
    border-radius: 8px;
    font-size: 0.86rem;
    font-weight: 600;
    text-align: center;
}

/* Status Menunggu Verifikasi (Orange/Amber) */
.cs-badge-warning {
    background-color: #fff8eb;
    border: 1.5px solid #fed7aa;
    color: #f59e0b;
}

/* Status Disetujui / Selesai (Hijau) */
.cs-badge-success {
    background-color: #effcf4;
    border: 1.5px solid #bbf7d0;
    color: #16a34a;
}

/* Status Ditolak (Merah) */
.cs-badge-danger {
    background-color: #fef2f2;
    border: 1.5px solid #fecaca;
    color: #dc2626;
}

/* ── Panel Kiri: Alert Info Banner ── */
.cs-info-banner {
    background: #edf5fe;
    border: 1px solid #d9e8fb;
    border-radius: 16px;
    padding: 20px 24px;
    display: flex;
    align-items: center;
    gap: 16px;
}

.cs-info-circle-icon {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background-color: #1d70e2;
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 14px;
}

.cs-info-text p {
    margin: 0;
    font-size: 0.88rem;
    color: #3b526e;
    line-height: 1.55;
}


/* ── Panel Kanan: Timeline Stepper Card ── */
.cs-stepper-card {
    padding: 34px 32px 36px;
}

.cs-stepper-title {
    font-size: 1.15rem;
    font-weight: 700;
    color: #0d2b4f;
    margin: 0 0 28px 0;
}

.cs-timeline {
    display: flex;
    flex-direction: column;
}

.cs-step {
    display: flex;
    align-items: flex-start;
    gap: 16px;
}

.cs-step-icon-wrap {
    display: flex;
    flex-direction: column;
    align-items: center;
    width: 28px;
    flex-shrink: 0;
}

/* Lingkaran Stepper */
.cs-step-icon {
    width: 26px;
    height: 26px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    box-sizing: border-box;
    transition: all 0.25s ease;
}

/* Garis Penghubung Vertikal */
.cs-step-line {
    width: 2px;
    height: 38px;
    background-color: #dbe4ef;
    margin: 4px 0;
    transition: background-color 0.25s ease;
}

.cs-step-line.done {
    background-color: #1d70e2;
}

/* State 1: Selesai / Checked */
.cs-step-done .cs-step-icon {
    background-color: #0c438c;
    color: #ffffff;
    font-size: 11px;
}

/* State 2: Sedang Berjalan (Active Solid Blue) */
.cs-step-active .cs-step-icon {
    background-color: #1d70e2;
    color: #ffffff;
    box-shadow: 0 0 0 4px rgba(29, 112, 226, 0.15);
}

/* State 3: Pending (Outline Abu-abu) */
.cs-step-pending .cs-step-icon {
    background-color: #ffffff;
    border: 2px solid #cbd5e1;
    color: transparent;
}

/* Konten Teks Step */
.cs-step-content {
    padding-top: 2px;
    padding-bottom: 24px;
}

.cs-step:last-child .cs-step-content {
    padding-bottom: 0;
}

.cs-step-name {
    font-size: 0.94rem;
    font-weight: 700;
    color: #0d2b4f;
    margin: 0;
    line-height: 1.3;
}

.cs-step-pending .cs-step-name {
    font-weight: 500;
    color: #556987;
}

.cs-step-date {
    display: block;
    font-size: 0.8rem;
    color: #8c9eb5;
    margin-top: 3px;
}

/* Animasi Fade */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(6px); }
    to { opacity: 1; transform: translateY(0); }
}

/* ═══════════════════════════════════════════════════════════════════
   RESPONSIF MOBILE / TABLET
   ════════════════════════════════════════════════════════════════ */
@media (max-width: 900px) {
    .cs-result-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 600px) {
    .cs-wrapper {
        padding: 24px 12px 60px;
    }
    .cs-search-card {
        padding: 34px 20px;
        border-radius: 18px;
    }
    .cs-search-title {
        font-size: 1.4rem;
    }
    .cs-detail-card {
        padding: 24px 20px;
    }
    .cs-detail-row {
        grid-template-columns: 1fr;
        gap: 4px;
        margin-bottom: 18px;
    }
    .cs-stepper-card {
        padding: 24px 20px;
    }
}
</style>

<!-- ═══════════════════════════════════════════════════════════════════
     JAVASCRIPT LOGIC INTERAKTIF
════════════════════════════════════════════════════════════════════ -->
<script>
// Mock data untuk pengujian & pencarian real-time
const dbStatus = {
    // 1. Data sesuai screenshot contoh user
    'PGN-20261008-001': {
        type: 'pengajuan',
        kode: 'PGN-20261008-001',
        title: 'Status Pengajuan Donasi',
        program: 'Bantuan Pendidikan Anak Desa',
        pengusul: 'HIMA Informatika',
        tanggal: '08 Oktober 2026',
        statusText: 'Menunggu Verifikasi',
        statusType: 'warning',
        infoMessage: 'Pengajuan Anda sedang dalam proses verifikasi oleh admin.<br>Silakan cek kembali secara berkala atau hubungi kontak kami jika ada pertanyaan.',
        stepperTitle: 'Proses Pengajuan',
        steps: [
            { name: 'Pengajuan Dikirim', date: '08 Okt 2026', status: 'done' },
            { name: 'Sedang Diverifikasi', date: '-', status: 'active' },
            { name: 'Disetujui', date: '-', status: 'pending' },
            { name: 'Program Dipublikasikan', date: '-', status: 'pending' }
        ]
    },
    // 2. Data pengajuan kedua: sudah disetujui
    'PGN-20261005-002': {
        type: 'pengajuan',
        kode: 'PGN-20261005-002',
        title: 'Status Pengajuan Donasi',
        program: 'Paket Alat Tulis untuk Panti Asuhan',
        pengusul: 'UKM Forum Kajian Kitab Kuning',
        tanggal: '05 Oktober 2026',
        statusText: 'Disetujui',
        statusType: 'success',
        infoMessage: 'Pengajuan Anda telah disetujui! Tim kami sedang mempersiapkan publikasi program ke halaman utama donasi.',
        stepperTitle: 'Proses Pengajuan',
        steps: [
            { name: 'Pengajuan Dikirim', date: '05 Okt 2026', status: 'done' },
            { name: 'Sedang Diverifikasi', date: '06 Okt 2026', status: 'done' },
            { name: 'Disetujui', date: '07 Okt 2026', status: 'active' },
            { name: 'Program Dipublikasikan', date: '-', status: 'pending' }
        ]
    },
    // 3. Data donasi: donatur mengecek status transfer donasi
    'DON20260912143001': {
        type: 'donasi',
        kode: 'DON20260912143001',
        title: 'Status Donasi Anda',
        program: 'Bantu Renovasi Sekolah Pelosok Negeri 1',
        pengusul: 'Ahmad Fauzi',
        nominal: 'Rp 100.000',
        tanggal: '12 September 2026',
        statusText: 'Terverifikasi & Selesai',
        statusType: 'success',
        infoMessage: 'Donasi Anda telah terverifikasi oleh admin dan berhasil disalurkan ke penerima manfaat. Terima kasih atas kepedulian Anda!',
        stepperTitle: 'Proses Donasi',
        steps: [
            { name: 'Donasi Dibuat', date: '12 Sep 2026', status: 'done' },
            { name: 'Bukti Pembayaran Diunggah', date: '12 Sep 2026', status: 'done' },
            { name: 'Verifikasi Admin Selesai', date: '12 Sep 2026', status: 'done' },
            { name: 'Donasi Disalurkan', date: '13 Sep 2026', status: 'done' }
        ]
    }
};

// Quick Fill helper
function quickFill(kode) {
    document.getElementById('csInputKode').value = kode;
    handleCekStatusSubmit(new Event('submit'));
}

// Handler Submit Form Pencarian
function handleCekStatusSubmit(e) {
    if (e && e.preventDefault) e.preventDefault();

    let query = document.getElementById('csInputKode').value.trim();
    // Default jika kosong ke kode di screenshot user
    if (!query) {
        query = 'PGN-20261008-001';
        document.getElementById('csInputKode').value = query;
    }

    // Ambil data dari database mock atau generate data dinamis
    let data = dbStatus[query.toUpperCase()] || dbStatus[query];

    if (!data) {
        // Jika kode kustom dimasukkan, sesuaikan secara cerdas
        const isDonasi = query.toUpperCase().startsWith('DON');
        data = {
            type: isDonasi ? 'donasi' : 'pengajuan',
            kode: query.toUpperCase(),
            title: isDonasi ? 'Status Donasi Anda' : 'Status Pengajuan Donasi',
            program: isDonasi ? 'Program Kebaikan Sesama' : 'Bantuan Sosial & Pendidikan Mahasiswa',
            pengusul: isDonasi ? 'Donatur Terdaftar' : 'Pengusul Program',
            tanggal: '<?= date('d F Y') ?>',
            statusText: 'Menunggu Verifikasi',
            statusType: 'warning',
            infoMessage: isDonasi 
                ? 'Bukti donasi Anda sedang dalam tahap verifikasi oleh admin keuangan kami. Mohon tunggu maksimal 1x24 jam.'
                : 'Pengajuan Anda sedang dalam proses verifikasi oleh admin.<br>Silakan cek kembali secara berkala atau hubungi kontak kami jika ada pertanyaan.',
            stepperTitle: isDonasi ? 'Proses Donasi' : 'Proses Pengajuan',
            steps: isDonasi ? [
                { name: 'Donasi Dibuat', date: '<?= date('d M Y') ?>', status: 'done' },
                { name: 'Bukti Pembayaran Diunggah', date: '<?= date('d M Y') ?>', status: 'active' },
                { name: 'Verifikasi Admin Selesai', date: '-', status: 'pending' },
                { name: 'Donasi Disalurkan', date: '-', status: 'pending' }
            ] : [
                { name: 'Pengajuan Dikirim', date: '<?= date('d M Y') ?>', status: 'done' },
                { name: 'Sedang Diverifikasi', date: '-', status: 'active' },
                { name: 'Disetujui', date: '-', status: 'pending' },
                { name: 'Program Dipublikasikan', date: '-', status: 'pending' }
            ]
        };
    }

    renderResult(data);

    // Update URL history tanpa refresh penuh
    if (window.history && window.history.pushState) {
        window.history.pushState({ kode: data.kode }, '', '?kode=' + encodeURIComponent(data.kode));
    }
}

// Render data ke tampilan hasil
function renderResult(data) {
    // Header
    document.getElementById('resHeaderTitle').textContent = data.title;
    
    // Labels & Values
    document.getElementById('lblKode').textContent = data.type === 'donasi' ? 'Nomor Donasi' : 'Kode Pengajuan';
    document.getElementById('valKode').textContent = data.kode;
    document.getElementById('valProgram').textContent = data.program;

    if (data.type === 'donasi') {
        document.getElementById('lblPengusul').textContent = 'Nama Donatur';
        document.getElementById('valPengusul').textContent = data.pengusul;
        document.getElementById('lblTanggal').textContent = 'Tanggal Donasi';
        if (data.nominal) {
            document.getElementById('rowNominal').style.display = 'grid';
            document.getElementById('valNominal').textContent = data.nominal;
        } else {
            document.getElementById('rowNominal').style.display = 'none';
        }
    } else {
        document.getElementById('lblPengusul').textContent = 'Pengusul';
        document.getElementById('valPengusul').textContent = data.pengusul;
        document.getElementById('lblTanggal').textContent = 'Tanggal Pengajuan';
        document.getElementById('rowNominal').style.display = 'none';
    }

    document.getElementById('valTanggal').textContent = data.tanggal;

    // Badge Status
    const badgeEl = document.getElementById('valBadgeStatus');
    badgeEl.textContent = data.statusText;
    badgeEl.className = 'cs-badge ' + (
        data.statusType === 'success' ? 'cs-badge-success' :
        data.statusType === 'danger' ? 'cs-badge-danger' : 'cs-badge-warning'
    );

    // Info Message
    document.getElementById('resInfoMessage').innerHTML = data.infoMessage;

    // Stepper
    document.getElementById('resStepperTitle').textContent = data.stepperTitle;

    data.steps.forEach((step, idx) => {
        const i = idx + 1;
        const stepEl = document.getElementById('step' + i);
        const nameEl = document.getElementById('step' + i + 'Title');
        const dateEl = document.getElementById('step' + i + 'Date');
        const lineEl = document.getElementById('stepLine' + i);

        if (nameEl) nameEl.textContent = step.name;
        if (dateEl) dateEl.textContent = step.date;

        if (stepEl) {
            stepEl.className = 'cs-step cs-step-' + step.status;
            const iconEl = stepEl.querySelector('.cs-step-icon');
            if (iconEl) {
                if (step.status === 'done') {
                    iconEl.innerHTML = '<i class="fas fa-check"></i>';
                } else {
                    iconEl.innerHTML = '';
                }
            }
        }

        if (lineEl) {
            lineEl.className = 'cs-step-line' + (step.status === 'done' ? ' done' : '');
        }
    });

    // Tampilkan panel hasil & sembunyikan pencarian
    document.getElementById('csSearchView').style.display = 'none';
    document.getElementById('csResultView').style.display = 'block';

    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// Kembali ke Tampilan Pencarian
function kembaliKePencarian() {
    document.getElementById('csResultView').style.display = 'none';
    document.getElementById('csSearchView').style.display = 'flex';

    if (window.history && window.history.pushState) {
        window.history.pushState({}, '', window.location.pathname);
    }

    const input = document.getElementById('csInputKode');
    input.focus();
    input.select();
}

// Handle Browser Back / Forward Button
window.addEventListener('popstate', function(e) {
    const params = new URLSearchParams(window.location.search);
    const kodeParam = params.get('kode') || params.get('trx');
    if (kodeParam) {
        document.getElementById('csInputKode').value = kodeParam;
        handleCekStatusSubmit(new Event('submit'));
    } else {
        document.getElementById('csResultView').style.display = 'none';
        document.getElementById('csSearchView').style.display = 'flex';
    }
});

// Auto run jika kode ada saat page load
<?php if (!empty($kode)): ?>
document.addEventListener('DOMContentLoaded', function() {
    handleCekStatusSubmit(new Event('submit'));
});
<?php endif; ?>
</script>

<?php require_once 'includes/footer.php'; ?>
