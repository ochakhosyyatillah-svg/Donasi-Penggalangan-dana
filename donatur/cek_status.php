<?php
require_once __DIR__ . '/../assets/config/database.php';

$page_title   = 'Cek Status Pengajuan & Donasi - SIBERASA';
$page_desc    = 'Cek status verifikasi pengajuan program atau donasi Anda secara real-time di SIBERASA.';
$current_page = 'cek_status';

// Fungsi pencarian status donasi & pengajuan langsung dari database
function cariStatusByKode($kode, $pdo) {
    $kode = trim($kode);
    if (empty($kode) || !isset($pdo)) {
        return null;
    }

    // 1. Cek di tabel donasi terlebih dahulu berdasarkan kode tracking
    try {
        $stmtDon = $pdo->prepare("
            SELECT d.*, p.judul AS nama_program, pb.status_verifikasi, pb.tanggal_pembayaran
            FROM donasi d
            JOIN program_donasi p ON d.id_program = p.id_program
            LEFT JOIN pembayaran pb ON d.id_donasi = pb.id_donasi
            WHERE d.kode_tracking = ?
            ORDER BY d.id_donasi DESC
            LIMIT 1
        ");
        $stmtDon->execute([$kode]);
        $donRow = $stmtDon->fetch(PDO::FETCH_ASSOC);

        if ($donRow) {
            $stVerif = $donRow['status_verifikasi'] ?? 'menunggu';
            if ($donRow['status_donasi'] === 'berhasil') {
                $stVerif = 'diterima';
            } elseif ($donRow['status_donasi'] === 'ditolak') {
                $stVerif = 'ditolak';
            }

            $statusText = 'Menunggu Verifikasi';
            $statusType = 'warning';
            $tglDonasi = date('d F Y', strtotime($donRow['tanggal_donasi']));
            $tglBayar = !empty($donRow['tanggal_pembayaran']) ? date('d M Y', strtotime($donRow['tanggal_pembayaran'])) : date('d M Y', strtotime($donRow['tanggal_donasi']));

            $steps = [
                ['name' => 'Donasi Dibuat', 'date' => date('d M Y', strtotime($donRow['tanggal_donasi'])), 'status' => 'done'],
                ['name' => 'Bukti Pembayaran Diunggah', 'date' => $tglBayar, 'status' => 'done'],
                ['name' => 'Verifikasi Admin', 'date' => ($stVerif === 'diterima' ? 'Terverifikasi' : ($stVerif === 'ditolak' ? 'Ditolak' : 'Proses')), 'status' => ($stVerif === 'diterima' ? 'done' : 'active')],
                ['name' => 'Donasi Disalurkan', 'date' => ($stVerif === 'diterima' ? 'Siap Disalurkan' : '-'), 'status' => ($stVerif === 'diterima' ? 'done' : 'pending')]
            ];

            if ($stVerif === 'diterima') {
                $statusText = 'Terverifikasi & Selesai';
                $statusType = 'success';
            } elseif ($stVerif === 'ditolak') {
                $statusText = 'Ditolak';
                $statusType = 'danger';
            }

            return [
                'type' => 'donasi',
                'kode' => $donRow['kode_tracking'],
                'title' => 'Status Donasi Anda',
                'program' => $donRow['nama_program'],
                'pengusul' => $donRow['nama_donatur'] ?: 'Donatur SIBERASA',
                'nominal' => function_exists('formatRupiah') ? formatRupiah($donRow['nominal']) : ('Rp ' . number_format($donRow['nominal'], 0, ',', '.')),
                'tanggal' => $tglDonasi,
                'statusText' => $statusText,
                'statusType' => $statusType,
                'infoMessage' => ($stVerif === 'diterima') 
                    ? 'Donasi Anda telah berhasil diverifikasi oleh admin dan berhasil tercatat. Terima kasih atas kepedulian Anda!' 
                    : (($stVerif === 'ditolak') 
                        ? 'Bukti donasi tidak dapat diverifikasi oleh admin. Silakan periksa kembali atau unggah ulang bukti yang sesuai.' 
                        : 'Bukti pembayaran donasi Anda telah diterima dan sedang dalam tahap verifikasi oleh admin keuangan kami.'),
                'stepperTitle' => 'Proses Donasi',
                'steps' => $steps
            ];
        }
    } catch (Exception $e) {}

    // 2. Jika tidak ditemukan di donasi, cek tabel program_donasi (pengajuan program)
    try {
        $progId = null;
        if (is_numeric($kode)) {
            $progId = (int)$kode;
        } elseif (preg_match('/(?:PGN[ -]?)(\d+)/i', $kode, $m)) {
            $progId = (int)$m[1];
        }

        $progRow = null;
        if ($progId !== null) {
            $stmtProg = $pdo->prepare("
                SELECT p.*, u.nama AS nama_pengusul, u.nama_organisasi
                FROM program_donasi p
                LEFT JOIN users u ON p.id_pengusul = u.id_user
                WHERE p.id_program = ?
                LIMIT 1
            ");
            $stmtProg->execute([$progId]);
            $progRow = $stmtProg->fetch(PDO::FETCH_ASSOC);
        }

        if (!$progRow && strlen($kode) >= 4) {
            $stmtProg2 = $pdo->prepare("
                SELECT p.*, u.nama AS nama_pengusul, u.nama_organisasi
                FROM program_donasi p
                LEFT JOIN users u ON p.id_pengusul = u.id_user
                WHERE p.judul LIKE ?
                LIMIT 1
            ");
            $stmtProg2->execute(['%' . $kode . '%']);
            $progRow = $stmtProg2->fetch(PDO::FETCH_ASSOC);
        }

        if ($progRow) {
            $stProg = strtolower($progRow['status_program']);
            $kodeFormatted = 'PGN-' . str_pad($progRow['id_program'], 5, '0', STR_PAD_LEFT);
            $namaOrg = $progRow['nama_organisasi'] ?: ($progRow['nama_pengusul'] ?: 'Pengusul Terdaftar');
            $tglAjukan = date('d F Y', strtotime($progRow['dibuat_pada']));

            $step1 = ['name' => 'Pengajuan Dikirim', 'date' => date('d M Y', strtotime($progRow['dibuat_pada'])), 'status' => 'done'];
            $step2 = ['name' => 'Sedang Diverifikasi', 'date' => '-', 'status' => 'pending'];
            $step3 = ['name' => 'Disetujui', 'date' => '-', 'status' => 'pending'];
            $step4 = ['name' => 'Program Dipublikasikan', 'date' => '-', 'status' => 'pending'];

            $statusText = 'Menunggu Verifikasi';
            $statusType = 'warning';
            $infoMsg = 'Pengajuan Anda sedang dalam proses verifikasi oleh admin.<br>Silakan cek kembali secara berkala atau hubungi kontak kami jika ada pertanyaan.';

            if ($stProg === 'menunggu') {
                $step2['status'] = 'active';
            } elseif ($stProg === 'disetujui' || $stProg === 'berlangsung') {
                $statusText = 'Disetujui';
                $statusType = 'success';
                $tglVerif = !empty($progRow['diverifikasi_pada']) ? date('d M Y', strtotime($progRow['diverifikasi_pada'])) : date('d M Y');
                $step2 = ['name' => 'Sedang Diverifikasi', 'date' => $tglVerif, 'status' => 'done'];
                $step3 = ['name' => 'Disetujui', 'date' => $tglVerif, 'status' => 'done'];
                $step4 = ['name' => 'Program Dipublikasikan', 'date' => ($stProg === 'berlangsung' ? 'Aktif' : 'Persiapan'), 'status' => ($stProg === 'berlangsung' ? 'done' : 'active')];
                $infoMsg = 'Pengajuan program donasi Anda telah disetujui oleh admin! Program telah aktif dan dapat menerima donasi.';
            } elseif ($stProg === 'selesai') {
                $statusText = 'Selesai';
                $statusType = 'success';
                $step2['status'] = 'done';
                $step3['status'] = 'done';
                $step4 = ['name' => 'Program Selesai', 'date' => 'Selesai', 'status' => 'done'];
                $infoMsg = 'Program penggalangan dana telah selesai dilaksanakan. Terima kasih atas partisipasi Anda.';
            } elseif ($stProg === 'ditolak') {
                $statusText = 'Ditolak';
                $statusType = 'danger';
                $step2 = ['name' => 'Diverifikasi Admin', 'date' => 'Ditolak', 'status' => 'done'];
                $step3 = ['name' => 'Ditolak', 'date' => 'Tidak Disetujui', 'status' => 'active'];
                $step4 = ['name' => 'Program Dibatalkan', 'date' => '-', 'status' => 'pending'];
                $catatan = !empty($progRow['catatan_admin']) ? '<br><strong>Catatan Admin:</strong> ' . htmlspecialchars($progRow['catatan_admin']) : '';
                $infoMsg = 'Pengajuan program belum dapat disetujui.' . $catatan;
            }

            return [
                'type' => 'pengajuan',
                'kode' => $kodeFormatted,
                'title' => 'Status Pengajuan Donasi',
                'program' => $progRow['judul'],
                'pengusul' => $namaOrg,
                'nominal' => function_exists('formatRupiah') ? formatRupiah($progRow['target_dana']) : ('Rp ' . number_format($progRow['target_dana'], 0, ',', '.')),
                'tanggal' => $tglAjukan,
                'statusText' => $statusText,
                'statusType' => $statusType,
                'infoMessage' => $infoMsg,
                'stepperTitle' => 'Proses Pengajuan',
                'steps' => [$step1, $step2, $step3, $step4]
            ];
        }
    } catch (Exception $e) {}

    return null;
}

// Handler AJAX untuk pencarian real-time tanpa reload dan tanpa mengubah URL
if (isset($_GET['ajax']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || (isset($_POST['action']) && $_POST['action'] === 'search')) {
    header('Content-Type: application/json');
    $q = trim($_REQUEST['kode'] ?? '');
    $data = cariStatusByKode($q, $pdo);
    if ($data) {
        echo json_encode(['success' => true, 'data' => $data]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Kode transaksi atau nomor pengajuan tidak ditemukan dalam database.']);
    }
    exit;
}

// Ambil kode dari GET parameter jika ada saat page load awal
$kode = isset($_GET['kode']) ? trim($_GET['kode']) : (isset($_GET['trx']) ? trim($_GET['trx']) : '');
$dbFound = null;
if (!empty($kode) && isset($pdo)) {
    $dbFound = cariStatusByKode($kode, $pdo);
}

require_once '../includes/header.php';
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

                <!-- Alert jika kode tidak ditemukan -->
                <div id="csAlertNotFound" class="cs-alert-notfound" style="display: none;">
                    <i class="fas fa-circle-exclamation"></i>
                    <span id="csAlertMsg">Kode transaksi atau nomor pengajuan tidak ditemukan dalam database.</span>
                </div>

                <!-- Helpful Quick Hints -->
                <div class="cs-hints">
                    <span>Contoh kode: </span>
                    <span class="cs-hint-code">PGN-20261008-001</span>
                    <span class="cs-hint-sep">•</span>
                    <span class="cs-hint-code">DON20260912143001</span>
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
                            <span class="cs-label" id="lblNominal">Nominal Donasi</span>
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
.cs-hints .cs-hint-code {
    color: #64748b;
    font-weight: 500;
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

/* Alert Kode Tidak Ditemukan */
.cs-alert-notfound {
    margin-top: 18px;
    padding: 12px 18px;
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #b91c1c;
    border-radius: 12px;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 10px;
    text-align: left;
    animation: fadeIn 0.25s ease-in-out;
}
.cs-alert-notfound i {
    font-size: 1.15rem;
    color: #ef4444;
    flex-shrink: 0;
}
</style>

<!-- ═══════════════════════════════════════════════════════════════════
     JAVASCRIPT LOGIC INTERAKTIF (TERHUBUNG KE DATABASE & URL BERSIH)
════════════════════════════════════════════════════════════════════ -->
<script>
// Bersihkan ?kode=... atau parameter query dari address bar browser
// agar URL selalu tampil bersih: http://localhost/Donasi/donatur/cek_status.php
if (window.location.search) {
    window.history.replaceState(null, '', window.location.pathname);
}

// Data awal jika di-load dari URL parameter PHP
window.csInitialData = <?= $dbFound ? json_encode($dbFound) : 'null' ?>;

// Handler Submit Form Pencarian (Terhubung langsung ke database via AJAX)
async function handleCekStatusSubmit(e) {
    if (e && e.preventDefault) e.preventDefault();

    const inputEl = document.getElementById('csInputKode');
    const alertEl = document.getElementById('csAlertNotFound');
    const btnSubmit = document.querySelector('.cs-btn-submit');
    
    if (alertEl) alertEl.style.display = 'none';

    let query = inputEl.value.trim();
    if (!query) {
        if (alertEl) {
            document.getElementById('csAlertMsg').textContent = 'Silakan masukkan kode transaksi donasi atau nomor pengajuan.';
            alertEl.style.display = 'flex';
        }
        inputEl.focus();
        return;
    }

    // Jika data awal sudah ada dan kodenya cocok dengan input
    if (window.csInitialData && (window.csInitialData.kode.toUpperCase() === query.toUpperCase())) {
        renderResult(window.csInitialData);
        if (window.history && window.history.replaceState) {
            window.history.replaceState(null, '', window.location.pathname);
        }
        return;
    }

    // Ubah tombol jadi loading
    const originalBtnHtml = btnSubmit.innerHTML;
    btnSubmit.disabled = true;
    btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>Mencari...</span>';

    try {
        const response = await fetch('cek_status.php?ajax=1&kode=' + encodeURIComponent(query));
        const res = await response.json();

        btnSubmit.disabled = false;
        btnSubmit.innerHTML = originalBtnHtml;

        if (res.success && res.data) {
            renderResult(res.data);
            // Pastikan URL di address bar browser tetap bersih tanpa query parameter
            if (window.history && window.history.replaceState) {
                window.history.replaceState(null, '', window.location.pathname);
            }
        } else {
            if (alertEl) {
                document.getElementById('csAlertMsg').textContent = res.message || 'Kode transaksi atau nomor pengajuan tidak ditemukan di database.';
                alertEl.style.display = 'flex';
            } else {
                alert(res.message || 'Data tidak ditemukan.');
            }
        }
    } catch (err) {
        btnSubmit.disabled = false;
        btnSubmit.innerHTML = originalBtnHtml;
        console.error(err);
        if (alertEl) {
            document.getElementById('csAlertMsg').textContent = 'Terjadi kendala saat menghubungi database. Silakan coba lagi.';
            alertEl.style.display = 'flex';
        }
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
            document.getElementById('lblNominal').textContent = 'Nominal Donasi';
            document.getElementById('valNominal').textContent = data.nominal;
        } else {
            document.getElementById('rowNominal').style.display = 'none';
        }
    } else {
        document.getElementById('lblPengusul').textContent = 'Pengusul';
        document.getElementById('valPengusul').textContent = data.pengusul;
        document.getElementById('lblTanggal').textContent = 'Tanggal Pengajuan';
        if (data.nominal) {
            document.getElementById('rowNominal').style.display = 'grid';
            document.getElementById('lblNominal').textContent = 'Target Dana';
            document.getElementById('valNominal').textContent = data.nominal;
        } else {
            document.getElementById('rowNominal').style.display = 'none';
        }
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

    if (window.history && window.history.replaceState) {
        window.history.replaceState(null, '', window.location.pathname);
    }

    const alertEl = document.getElementById('csAlertNotFound');
    if (alertEl) alertEl.style.display = 'none';

    const input = document.getElementById('csInputKode');
    input.focus();
    input.select();
}

// Auto render jika data ditemukan saat halaman dimuat
document.addEventListener('DOMContentLoaded', function() {
    if (window.csInitialData) {
        renderResult(window.csInitialData);
    } else {
        <?php if (!empty($kode)): ?>
        const alertEl = document.getElementById('csAlertNotFound');
        if (alertEl) {
            document.getElementById('csAlertMsg').textContent = 'Kode "<?= htmlspecialchars($kode) ?>" tidak ditemukan di database.';
            alertEl.style.display = 'flex';
        }
        <?php endif; ?>
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>
