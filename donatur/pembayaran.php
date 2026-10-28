<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Handle AJAX update metode jika pengguna ganti metode pembayaran
if (isset($_POST['action']) && $_POST['action'] === 'update_metode') {
    if (isset($_POST['metode'])) {
        if (!isset($_SESSION['pembayaran_data'])) {
            $_SESSION['pembayaran_data'] = [];
        }
        $_SESSION['pembayaran_data']['metode'] = trim($_POST['metode']);
    }
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success']);
    exit;
}

require_once __DIR__ . '/../assets/config/database.php';

// Ambil data dari GET atau dari SESSION
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $nominal = isset($_GET['nominal']) ? (int)$_GET['nominal'] : 10000;
    $rawMetode = isset($_GET['metode']) ? strtolower(trim($_GET['metode'])) : 'qris';
    $nama = isset($_GET['nama']) ? trim($_GET['nama']) : '';
    $email = isset($_GET['email']) ? trim($_GET['email']) : '';
    $telepon = isset($_GET['telepon']) ? trim($_GET['telepon']) : '';
    $pesan = isset($_GET['pesan']) ? trim($_GET['pesan']) : '';
    $anonim = isset($_GET['anonim']) ? (int)$_GET['anonim'] : 0;

    $metodeFormatted = 'QRIS';
    if (strpos($rawMetode, 'transfer') !== false || strpos($rawMetode, 'bank') !== false) {
        $metodeFormatted = 'Transfer Bank';
    } elseif (strpos($rawMetode, 'ewallet') !== false || strpos($rawMetode, 'wallet') !== false) {
        $metodeFormatted = 'E-Wallet';
    }

    $_SESSION['pembayaran_data'] = [
        'id' => $id,
        'nominal' => $nominal,
        'metode' => $metodeFormatted,
        'nama' => $nama,
        'email' => $email,
        'telepon' => $telepon,
        'pesan' => $pesan,
        'anonim' => $anonim
    ];
} elseif (isset($_SESSION['pembayaran_data'])) {
    $data = $_SESSION['pembayaran_data'];
    $id = (int)($data['id'] ?? 1);
    $nominal = (int)($data['nominal'] ?? 10000);
    $rawMetode = strtolower(trim($data['metode'] ?? 'qris'));
    $nama = $data['nama'] ?? '';
    $email = $data['email'] ?? '';
    $telepon = $data['telepon'] ?? '';
    $pesan = $data['pesan'] ?? '';
    $anonim = (int)($data['anonim'] ?? 0);
} else {
    $id = isset($_SESSION['donasi_program_id']) ? (int)$_SESSION['donasi_program_id'] : 1;
    $nominal = 10000;
    $rawMetode = 'qris';
    $nama = '';
    $email = '';
    $telepon = '';
    $pesan = '';
    $anonim = 0;
}

// Normalisasi metode pembayaran
if (strpos($rawMetode, 'transfer') !== false || strpos($rawMetode, 'bank') !== false) {
    $metodeAktif = 'transfer';
} elseif (strpos($rawMetode, 'ewallet') !== false || strpos($rawMetode, 'wallet') !== false) {
    $metodeAktif = 'ewallet';
} else {
    $metodeAktif = 'qris';
}

// Cari program yang dipilih
$prog = null;
foreach ($programs as $p) {
    if ($p['id'] === $id) {
        $prog = $p;
        break;
    }
}
if (!$prog && !empty($programs)) {
    $prog = $programs[0];
}
if (!$prog) {
    header('Location: program.php');
    exit;
}

// ── Ambil Akun Tujuan Pembayaran yang Didaftarkan Pengusul dari Database ──
$daftarAkun = [];
if (isset($pdo)) {
    try {
        $stmtAkun = $pdo->prepare("SELECT * FROM akun_tujuan_pembayaran WHERE id_program = ? AND status_aktif = 1 ORDER BY id_akun ASC");
        $stmtAkun->execute([$prog['id']]);
        $daftarAkun = $stmtAkun->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $daftarAkun = [];
    }
}

// Pisahkan akun pengusul ke dalam kategori Bank, E-Wallet, dan QRIS
$akunBank = [];
$akunEwallet = [];
$akunQris = [];
$qrisPath = '';
$qrisOwner = '';

foreach ($daftarAkun as $ak) {
    $m = strtolower(trim($ak['metode']));
    
    // Cek apakah ada file foto QRIS yang diunggah pengusul
    if (!empty($ak['gambar_qris']) && empty($qrisPath)) {
        $f = trim($ak['gambar_qris']);
        $qrisPath = (strpos($f, '/') === 0) ? $f : ((strpos($f, 'assets/') === 0) ? '/Donasi/' . $f : '/Donasi/assets/img/' . $f);
        $qrisOwner = $ak['nama_pemilik'];
    }

    if ($m === 'bank' || strpos($m, 'transfer') !== false) {
        $akunBank[] = $ak;
    } elseif ($m === 'ewallet' || $m === 'e-wallet' || strpos($m, 'wallet') !== false) {
        $akunEwallet[] = $ak;
    } elseif ($m === 'qris') {
        $akunQris[] = $ak;
        if (empty($qrisOwner)) {
            $qrisOwner = $ak['nama_pemilik'];
        }
    } else {
        // Fallback penentuan kategori dari nama penyedia
        if (preg_match('/bca|mandiri|bni|bri|bsi|cimb|btn|bank/i', $ak['nama_penyedia'])) {
            $akunBank[] = $ak;
        } else {
            $akunEwallet[] = $ak;
        }
    }
}

// Fallback nama pemilik QRIS jika belum ada
if (empty($qrisOwner)) {
    $qrisOwner = !empty($daftarAkun) ? $daftarAkun[0]['nama_pemilik'] : $prog['penyelenggara'];
}

// Cek ketersediaan masing-masing metode pada akun pengusul
$hasQrisAkun = (!empty($akunQris) || !empty($qrisPath));
$hasBankAkun = !empty($akunBank);
$hasEwalletAkun = !empty($akunEwallet);

// Jika pengusul belum daftarkan akun apa pun di database, izinkan semua (fallback)
if (!$hasQrisAkun && !$hasBankAkun && !$hasEwalletAkun) {
    $hasQrisAkun = true;
    $hasBankAkun = true;
    $hasEwalletAkun = true;
}

// Sesuaikan metode aktif jika metode yang diminta tidak tersedia pada pengusul
if ($metodeAktif === 'qris' && !$hasQrisAkun) {
    $metodeAktif = $hasBankAkun ? 'transfer' : ($hasEwalletAkun ? 'ewallet' : 'qris');
} elseif ($metodeAktif === 'transfer' && !$hasBankAkun) {
    $metodeAktif = $hasEwalletAkun ? 'ewallet' : ($hasQrisAkun ? 'qris' : 'transfer');
} elseif ($metodeAktif === 'ewallet' && !$hasEwalletAkun) {
    $metodeAktif = $hasBankAkun ? 'transfer' : ($hasQrisAkun ? 'qris' : 'ewallet');
}

// Tentukan teks banner awal berdasarkan metodeAktif
if ($metodeAktif === 'transfer') {
    $bannerTitleAwal = 'Pembayaran dengan Transfer Bank';
    $bannerSubtitleAwal = 'Transfer ke rekening bank tujuan pengusul di bawah ini.';
    $bannerIconAwal = 'fa-university';
} elseif ($metodeAktif === 'ewallet') {
    $bannerTitleAwal = 'Pembayaran dengan E-Wallet';
    $bannerSubtitleAwal = 'Transfer saldo ke akun e-wallet tujuan pengusul di bawah ini.';
    $bannerIconAwal = 'fa-wallet';
} else {
    $bannerTitleAwal = 'Pembayaran dengan QRIS';
    $bannerSubtitleAwal = 'Scan kode QR menggunakan aplikasi mobile banking atau e-wallet yang mendukung QRIS.';
    $bannerIconAwal = 'fa-qrcode';
}

$page_title   = 'Detail Pembayaran - SIBERASA';
$page_desc    = 'Lakukan pembayaran donasi sesuai metode yang Anda pilih.';
$current_page = 'program';

require_once __DIR__ . '/../includes/header.php';
?>

<style>
/* ── BASE WRAPPER DETAIL PEMBAYARAN ── */
.pembayaran-wrapper {
  background-color: #f8fafc;
  min-height: calc(100vh - 70px);
  padding: 32px 0 80px;
  font-family: 'Poppins', -apple-system, BlinkMacSystemFont, sans-serif;
  color: #0f172a;
}

.pembayaran-container {
  max-width: 1140px;
  margin: 0 auto;
  padding: 0 20px;
}

/* ── HEADER HALAMAN ── */
.pembayaran-header {
  display: flex;
  align-items: flex-start;
  gap: 16px;
  margin-bottom: 28px;
}

.pembayaran-btn-back {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #0284c7;
  font-size: 1.1rem;
  transition: all 0.2s ease;
  flex-shrink: 0;
  margin-top: 2px;
  text-decoration: none;
}

.pembayaran-btn-back:hover {
  background: #e0f2fe;
  color: #0369a1;
  border-color: #bae6fd;
  transform: translateX(-3px);
}

.pembayaran-title-wrap h1 {
  font-size: 1.6rem;
  font-weight: 800;
  color: #0f172a;
  line-height: 1.25;
  margin: 0 0 4px 0;
}

.pembayaran-title-wrap p {
  font-size: 0.88rem;
  color: #64748b;
  margin: 0;
}

/* ── 2 KOLOM GRID LAYOUT ── */
.pembayaran-grid {
  display: grid;
  grid-template-columns: 1.45fr 1fr;
  gap: 28px;
  align-items: start;
}

/* ── KIRI: KARTU PROGRAM ── */
.pembayaran-card {
  background: #ffffff;
  border-radius: 16px;
  border: 1px solid #e2e8f0;
  padding: 20px 24px;
  box-shadow: 0 2px 10px rgba(15, 23, 42, 0.03);
}

.pembayaran-program-card {
  display: flex;
  gap: 18px;
  align-items: center;
}

.pembayaran-prog-thumb {
  width: 140px;
  height: 90px;
  border-radius: 12px;
  overflow: hidden;
  flex-shrink: 0;
  background: #f1f5f9;
}

.pembayaran-prog-thumb img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.pembayaran-prog-info {
  flex: 1;
  min-width: 0;
}

.pembayaran-prog-title {
  font-size: 1.05rem;
  font-weight: 700;
  color: #0f172a;
  margin: 0 0 6px 0;
  line-height: 1.35;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.pembayaran-prog-badge {
  display: inline-block;
  background: #e0f2fe;
  color: #0284c7;
  font-size: 0.72rem;
  font-weight: 600;
  padding: 3px 12px;
  border-radius: 6px;
  margin-bottom: 8px;
}

.pembayaran-prog-amounts {
  font-size: 0.82rem;
  color: #64748b;
  margin-bottom: 6px;
}

.pembayaran-prog-amounts strong {
  color: #0f172a;
  font-weight: 700;
}

.pembayaran-progress-row {
  display: flex;
  align-items: center;
  gap: 12px;
}

.pembayaran-progress-track {
  flex: 1;
  height: 8px;
  background: #e2e8f0;
  border-radius: 9999px;
  overflow: hidden;
}

.pembayaran-progress-fill {
  height: 100%;
  background: #10b981;
  border-radius: 9999px;
}

.pembayaran-progress-percent {
  font-size: 0.82rem;
  font-weight: 700;
  color: #10b981;
}

/* ── KIRI: PILIHAN METODE PEMBAYARAN ── */
.pembayaran-metode-section {
  margin-top: 24px;
}

.pembayaran-section-label {
  font-size: 0.95rem;
  font-weight: 700;
  color: #0f172a;
  margin-bottom: 12px;
}

.pembayaran-metode-tabs {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
  gap: 12px;
  margin-bottom: 20px;
}

.pembayaran-tab-card {
  background: #ffffff;
  border: 1.5px solid #e2e8f0;
  border-radius: 12px;
  padding: 14px 16px;
  display: flex;
  align-items: center;
  gap: 12px;
  cursor: pointer;
  transition: all 0.2s ease;
  user-select: none;
}

.pembayaran-tab-card:hover {
  border-color: #0284c7;
  background: #f8fafc;
}

.pembayaran-tab-card.active {
  border-color: #0284c7;
  background: #f0f7ff;
  box-shadow: 0 0 0 2px rgba(2, 132, 199, 0.12);
}

.tab-radio {
  width: 18px;
  height: 18px;
  border-radius: 50%;
  border: 2px solid #cbd5e1;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  transition: all 0.2s ease;
}

.pembayaran-tab-card.active .tab-radio {
  border-color: #0284c7;
}

.tab-radio-dot {
  width: 9px;
  height: 9px;
  border-radius: 50%;
  background: #0284c7;
  opacity: 0;
  transform: scale(0.6);
  transition: all 0.2s ease;
}

.pembayaran-tab-card.active .tab-radio-dot {
  opacity: 1;
  transform: scale(1);
}

.tab-icon {
  font-size: 1.25rem;
  color: #0284c7;
  width: 24px;
  text-align: center;
  flex-shrink: 0;
}

.tab-label {
  font-size: 0.9rem;
  font-weight: 600;
  color: #1e293b;
}

.pembayaran-tab-card.active .tab-label {
  color: #0284c7;
}

/* ── KIRI: DETAIL INTRUKSI PEMBAYARAN CARD ── */
.pembayaran-detail-box {
  background: #ffffff;
  border-radius: 16px;
  border: 1px solid #e2e8f0;
  overflow: hidden;
  box-shadow: 0 2px 10px rgba(15, 23, 42, 0.03);
}

.pembayaran-banner {
  background: #f0f7ff;
  border-bottom: 1px solid #e0f2fe;
  padding: 18px 24px;
  display: flex;
  align-items: center;
  gap: 16px;
}

.pembayaran-banner-icon {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  background: #ffffff;
  border: 1px solid #bae6fd;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #0284c7;
  font-size: 1.35rem;
  flex-shrink: 0;
}

.pembayaran-banner-text h3 {
  font-size: 1rem;
  font-weight: 700;
  color: #0284c7;
  margin: 0 0 2px 0;
}

.pembayaran-banner-text p {
  font-size: 0.8rem;
  color: #64748b;
  margin: 0;
}

.pembayaran-inner-content {
  padding: 24px;
}

/* TAB 1: QRIS CONTENT */
.qris-layout {
  display: grid;
  grid-template-columns: 240px 1fr;
  gap: 24px;
  align-items: start;
}

.qris-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  padding: 16px;
  text-align: center;
  box-shadow: 0 2px 8px rgba(0,0,0,0.02);
}

.qris-logo-header {
  font-weight: 800;
  font-size: 1.15rem;
  letter-spacing: -0.5px;
  color: #0f172a;
  margin-bottom: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 2px;
}

.qris-logo-header span {
  color: #ef4444;
}

.qris-image-wrap {
  background: #ffffff;
  border: 1px solid #f1f5f9;
  border-radius: 8px;
  padding: 12px;
  display: inline-block;
  margin-bottom: 10px;
}

.qris-image-wrap img, .qris-image-wrap svg {
  display: block;
  width: 170px;
  height: 170px;
  margin: 0 auto;
}

.qris-caption {
  font-size: 0.72rem;
  color: #64748b;
  margin: 0;
}

/* TAB 1: QRIS CONTENT */
.qris-layout {
  display: grid;
  grid-template-columns: 240px 1fr;
  gap: 24px;
  align-items: start;
}

.qris-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  padding: 18px 16px;
  text-align: center;
  box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
}

.qris-logo-header {
  font-weight: 800;
  font-size: 1.2rem;
  letter-spacing: -0.5px;
  color: #0f172a;
  margin-bottom: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 2px;
}

.qris-logo-header span {
  color: #ef4444;
}

.qris-image-wrap {
  background: #ffffff;
  border: 1px solid #f1f5f9;
  border-radius: 10px;
  padding: 12px;
  display: inline-block;
  margin-bottom: 10px;
}

.qris-image-wrap img, .qris-image-wrap svg {
  display: block;
  width: 170px;
  height: 170px;
  margin: 0 auto;
}

.qris-caption {
  font-size: 0.74rem;
  color: #64748b;
  margin: 0;
  line-height: 1.4;
}

.qris-right-info {
  display: flex;
  flex-direction: column;
}

/* ── TAB 2 & 3: MODERN TRANSFER & E-WALLET LAYOUT ── */
.transfer-flow-layout {
  display: flex;
  flex-direction: column;
  gap: 18px;
}

.bank-list-wrap {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.bank-card-modern {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  padding: 18px 20px;
  box-shadow: 0 2px 8px rgba(15, 23, 42, 0.03);
  transition: all 0.2s ease;
  border-left: 4px solid #0284c7;
}

.bank-card-modern:hover {
  border-color: #cbd5e1;
  border-left-color: #0369a1;
  box-shadow: 0 4px 14px rgba(2, 132, 199, 0.08);
}

.bank-card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 10px;
  margin-bottom: 12px;
  padding-bottom: 12px;
  border-bottom: 1px solid #f1f5f9;
}

.bank-brand-info {
  display: flex;
  align-items: center;
  gap: 10px;
}

.bank-badge {
  background: #0284c7;
  color: #ffffff;
  font-weight: 800;
  font-size: 0.82rem;
  padding: 4px 12px;
  border-radius: 6px;
  letter-spacing: 0.5px;
  text-transform: uppercase;
  display: inline-block;
}

.bank-type-pill {
  font-size: 0.75rem;
  color: #64748b;
  font-weight: 500;
  display: inline-flex;
  align-items: center;
  gap: 5px;
}

.bank-owner-pill {
  font-size: 0.8rem;
  color: #475569;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  padding: 4px 12px;
  border-radius: 20px;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.bank-owner-pill i {
  color: #0284c7;
}

.bank-card-body {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 14px;
}

.bank-number-info {
  display: flex;
  flex-direction: column;
  gap: 3px;
}

.bank-number-label {
  font-size: 0.74rem;
  color: #64748b;
  font-weight: 500;
}

.bank-number-val {
  font-size: 1.35rem;
  font-weight: 800;
  color: #0f172a;
  letter-spacing: 1.5px;
  font-family: 'Consolas', 'Menlo', 'Courier New', monospace;
  line-height: 1.2;
}

.btn-copy-num {
  background: #f0f9ff;
  border: 1px solid #bae6fd;
  color: #0284c7;
  padding: 8px 16px;
  border-radius: 8px;
  font-size: 0.82rem;
  font-weight: 600;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  transition: all 0.2s ease;
  white-space: nowrap;
}

.btn-copy-num:hover {
  background: #0284c7;
  border-color: #0284c7;
  color: #ffffff;
  transform: translateY(-1px);
  box-shadow: 0 4px 10px rgba(2, 132, 199, 0.2);
}

/* NOMINAL HIGHLIGHT BOX */
.nominal-highlight-box {
  background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%);
  border: 1px solid #bbf7d0;
  border-radius: 14px;
  padding: 16px 20px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 14px;
}

.nominal-highlight-left {
  display: flex;
  flex-direction: column;
  gap: 3px;
}

.nominal-highlight-label {
  font-size: 0.74rem;
  font-weight: 600;
  color: #166534;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.nominal-highlight-val {
  font-size: 1.35rem;
  font-weight: 800;
  color: #15803d;
  letter-spacing: -0.5px;
  line-height: 1.2;
}

.nominal-highlight-hint {
  font-size: 0.75rem;
  color: #166534;
  opacity: 0.9;
  display: flex;
  align-items: center;
  gap: 5px;
  margin-top: 2px;
}

.btn-copy-nominal {
  background: #ffffff;
  border: 1px solid #86efac;
  color: #166534;
  padding: 8px 16px;
  border-radius: 8px;
  font-size: 0.82rem;
  font-weight: 600;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  transition: all 0.2s ease;
  white-space: nowrap;
}

.btn-copy-nominal:hover {
  background: #15803d;
  border-color: #15803d;
  color: #ffffff;
  transform: translateY(-1px);
  box-shadow: 0 4px 10px rgba(21, 128, 61, 0.2);
}

/* PETUNJUK PEMBAYARAN BOX */
.petunjuk-box {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  padding: 18px 20px;
}

.petunjuk-header {
  font-size: 0.88rem;
  font-weight: 700;
  color: #0284c7;
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 12px;
}

.petunjuk-list {
  margin: 0;
  padding-left: 20px;
  font-size: 0.82rem;
  color: #475569;
  line-height: 1.75;
}

.petunjuk-list li {
  margin-bottom: 4px;
}

.petunjuk-list li:last-child {
  margin-bottom: 0;
}

.petunjuk-list strong {
  color: #0f172a;
}

.pembayaran-footer-note {
  margin-top: 14px;
  font-size: 0.75rem;
  color: #94a3b8;
  display: flex;
  align-items: center;
  gap: 6px;
  padding-left: 4px;
}

/* ── KANAN: RINGKASAN DONASI CARD ── */
.ringkasan-card {
  background: #ffffff;
  border-radius: 16px;
  border: 1px solid #e2e8f0;
  padding: 24px;
  box-shadow: 0 2px 10px rgba(15, 23, 42, 0.03);
}

.ringkasan-title {
  font-size: 1.15rem;
  font-weight: 800;
  color: #0f172a;
  margin: 0 0 16px 0;
}

.ringkasan-prog-mini {
  display: flex;
  gap: 12px;
  align-items: center;
  margin-bottom: 20px;
  padding-bottom: 16px;
  border-bottom: 1px solid #f1f5f9;
}

.ringkasan-mini-thumb {
  width: 72px;
  height: 52px;
  border-radius: 8px;
  overflow: hidden;
  flex-shrink: 0;
}

.ringkasan-mini-thumb img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.ringkasan-mini-title {
  font-size: 0.85rem;
  font-weight: 700;
  color: #0f172a;
  line-height: 1.35;
  margin: 0 0 4px 0;
}

.ringkasan-mini-badge {
  display: inline-block;
  background: #e0f2fe;
  color: #0284c7;
  font-size: 0.7rem;
  font-weight: 600;
  padding: 2px 8px;
  border-radius: 4px;
}

.ringkasan-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-size: 0.88rem;
  padding: 8px 0;
}

.ringkasan-row span {
  color: #64748b;
}

.ringkasan-row strong {
  color: #0f172a;
  font-weight: 700;
}

.ringkasan-total-box {
  background: #f0f7ff;
  border: 1px solid #dbeafe;
  border-radius: 12px;
  padding: 16px 20px;
  margin: 18px 0;
  display: flex;
  align-items: center;
  gap: 14px;
}

.ringkasan-total-icon {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: #ffffff;
  border: 1px solid #bae6fd;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #0284c7;
  font-size: 1rem;
  flex-shrink: 0;
}

.ringkasan-total-text .label {
  font-size: 0.78rem;
  color: #0284c7;
  font-weight: 600;
}

.ringkasan-total-text .val {
  font-size: 1.45rem;
  font-weight: 800;
  color: #0284c7;
  line-height: 1.2;
}

.btn-upload-bukti-cta {
  width: 100%;
  background: #0284c7;
  color: #ffffff;
  border: none;
  border-radius: 12px;
  padding: 15px 20px;
  font-size: 0.95rem;
  font-weight: 700;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  box-shadow: 0 4px 14px rgba(2, 132, 199, 0.3);
  transition: all 0.25s ease;
  text-decoration: none;
}

.btn-upload-bukti-cta:hover {
  background: #0369a1;
  box-shadow: 0 6px 18px rgba(2, 132, 199, 0.4);
  transform: translateY(-2px);
  color: #ffffff;
}

.ringkasan-alert-box {
  background: #f0f7ff;
  border: 1px solid #dbeafe;
  border-radius: 10px;
  padding: 12px 14px;
  margin-top: 16px;
  display: flex;
  align-items: flex-start;
  gap: 10px;
}

.ringkasan-alert-box i {
  color: #0284c7;
  font-size: 0.95rem;
  margin-top: 2px;
}

.ringkasan-alert-box p {
  font-size: 0.78rem;
  color: #475569;
  line-height: 1.5;
  margin: 0;
}

.trust-badge-footer {
  text-align: center;
  margin-top: 16px;
  font-size: 0.8rem;
  color: #64748b;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
}

.trust-badge-footer i {
  color: #0284c7;
}

/* TOAST COPIED */
.toast-copied {
  position: fixed;
  bottom: 24px;
  right: 24px;
  background: #0f172a;
  color: #ffffff;
  padding: 10px 18px;
  border-radius: 8px;
  font-size: 0.85rem;
  display: flex;
  align-items: center;
  gap: 8px;
  opacity: 0;
  visibility: hidden;
  transform: translateY(10px);
  transition: all 0.2s ease;
  z-index: 9999;
}

.toast-copied.show {
  opacity: 1;
  visibility: visible;
  transform: translateY(0);
}

/* ── RESPONSIVE ── */
@media (max-width: 960px) {
  .pembayaran-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 640px) {
  .pembayaran-metode-tabs {
    grid-template-columns: 1fr;
  }
  .qris-layout {
    grid-template-columns: 1fr;
  }
  .qris-card {
    max-width: 260px;
    margin: 0 auto;
  }
  .pembayaran-program-card {
    flex-direction: column;
    align-items: flex-start;
  }
  .pembayaran-prog-thumb {
    width: 100%;
    height: 150px;
  }
}
</style>

<div class="pembayaran-wrapper">
    <div class="pembayaran-container">
        
        <!-- HEADER HALAMAN -->
        <div class="pembayaran-header">
            <a href="donasi.php?id=<?= $prog['id'] ?>" class="pembayaran-btn-back" title="Kembali ke formulir donasi">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div class="pembayaran-title-wrap">
                <h1>Detail Pembayaran</h1>
                <p>Lakukan pembayaran sesuai metode yang telah Anda pilih.</p>
            </div>
        </div>

        <!-- 2 KOLOM GRID -->
        <div class="pembayaran-grid">
            
            <!-- ══════════════════════════════════════════════════════
                 KOLOM KIRI: PROGRAM, METODE TABS & INSTRUKSI BAYAR
                 ══════════════════════════════════════════════════════ -->
            <div class="pembayaran-left-col">
                
                <!-- KARTU PROGRAM -->
                <div class="pembayaran-card">
                    <div class="pembayaran-program-card">
                        <div class="pembayaran-prog-thumb">
                            <img src="<?= htmlspecialchars($prog['gambar']) ?>" alt="<?= htmlspecialchars($prog['judul']) ?>" onerror="this.src='/Donasi/assets/img/default_program.jpg'">
                        </div>
                        <div class="pembayaran-prog-info">
                            <h2 class="pembayaran-prog-title"><?= htmlspecialchars($prog['judul']) ?></h2>
                            <span class="pembayaran-prog-badge"><?= htmlspecialchars($prog['kategori']) ?></span>
                            <div class="pembayaran-prog-amounts">
                                <strong><?= formatRupiah($prog['terkumpul']) ?></strong> / <span><?= formatRupiah($prog['target']) ?></span>
                            </div>
                            <div class="pembayaran-progress-row">
                                <div class="pembayaran-progress-track">
                                    <div class="pembayaran-progress-fill" style="width: <?= min(100, (int)$prog['persen']) ?>%;"></div>
                                </div>
                                <span class="pembayaran-progress-percent"><?= (int)$prog['persen'] ?>%</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PILIHAN METODE PEMBAYARAN TABS -->
                <div class="pembayaran-metode-section">
                    <div class="pembayaran-section-label">Metode Pembayaran</div>
                    
                    <div class="pembayaran-metode-tabs">
                        <!-- TAB QRIS -->
                        <?php if ($hasQrisAkun): ?>
                        <div class="pembayaran-tab-card <?= ($metodeAktif === 'qris') ? 'active' : '' ?>" id="tabCardQris" onclick="switchMetode('qris')">
                            <div class="tab-radio">
                                <div class="tab-radio-dot"></div>
                            </div>
                            <i class="fas fa-qrcode tab-icon"></i>
                            <span class="tab-label">QRIS</span>
                        </div>
                        <?php endif; ?>

                        <!-- TAB TRANSFER BANK -->
                        <?php if ($hasBankAkun): ?>
                        <div class="pembayaran-tab-card <?= ($metodeAktif === 'transfer') ? 'active' : '' ?>" id="tabCardTransfer" onclick="switchMetode('transfer')">
                            <div class="tab-radio">
                                <div class="tab-radio-dot"></div>
                            </div>
                            <i class="fas fa-university tab-icon"></i>
                            <span class="tab-label">Transfer Bank</span>
                        </div>
                        <?php endif; ?>

                        <!-- TAB E-WALLET -->
                        <?php if ($hasEwalletAkun): ?>
                        <div class="pembayaran-tab-card <?= ($metodeAktif === 'ewallet') ? 'active' : '' ?>" id="tabCardEwallet" onclick="switchMetode('ewallet')">
                            <div class="tab-radio">
                                <div class="tab-radio-dot"></div>
                            </div>
                            <i class="fas fa-wallet tab-icon"></i>
                            <span class="tab-label">E-Wallet</span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- CARD DETAIL INSTRUKSI PEMBAYARAN -->
                <div class="pembayaran-detail-box">
                    
                    <!-- BANNER ATAS -->
                    <div class="pembayaran-banner" id="pembayaranBanner">
                        <div class="pembayaran-banner-icon" id="bannerIcon">
                            <i class="fas <?= $bannerIconAwal ?>"></i>
                        </div>
                        <div class="pembayaran-banner-text">
                            <h3 id="bannerTitle"><?= htmlspecialchars($bannerTitleAwal) ?></h3>
                            <p id="bannerSubtitle"><?= htmlspecialchars($bannerSubtitleAwal) ?></p>
                        </div>
                    </div>

                    <!-- INNER KONTEN -->
                    <div class="pembayaran-inner-content">
                        
                        <!-- ── 1. KONTEN QRIS ── -->
                        <div id="contentQris" style="<?= ($metodeAktif === 'qris') ? '' : 'display:none;' ?>">
                            <div class="qris-layout">
                                <div class="qris-card">
                                    <div class="qris-logo-header">
                                        QRIS<span>.</span>
                                    </div>
                                    <div class="qris-image-wrap">
                                        <?php if (!empty($qrisPath)): ?>
                                            <img src="<?= htmlspecialchars($qrisPath) ?>" alt="QR Code QRIS Pengusul" style="width:100%; height:100%; object-fit:contain;">
                                        <?php else: ?>
                                            <!-- QR Code Dinamis Mengacu ke Akun Pengusul -->
                                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=SIBERASA-PROGRAM-<?= $prog['id'] ?>-<?= urlencode($qrisOwner) ?>-<?= $nominal ?>" alt="QR Code QRIS" onerror="this.src='/Donasi/assets/img/logo_donasi.png'">
                                        <?php endif; ?>
                                    </div>
                                    <p class="qris-caption">Scan QR di atas menggunakan m-Banking atau E-Wallet yang mendukung QRIS</p>
                                </div>

                                <div class="qris-right-info">
                                    <div class="nominal-highlight-box" style="margin-bottom: 16px;">
                                        <div class="nominal-highlight-left">
                                            <span class="nominal-highlight-label">Jumlah Donasi</span>
                                            <div class="nominal-highlight-val"><?= formatRupiah($nominal) ?></div>
                                            <span class="nominal-highlight-hint"><i class="fas fa-user-check"></i> Penerima: <strong><?= htmlspecialchars($qrisOwner) ?></strong></span>
                                        </div>
                                        <button type="button" class="btn-copy-nominal" onclick="salinTeks('<?= $nominal ?>', 'Nominal Donasi')">
                                            <i class="far fa-copy"></i> Salin
                                        </button>
                                    </div>

                                    <div class="petunjuk-box">
                                        <div class="petunjuk-header">
                                            <i class="fas fa-info-circle"></i> Petunjuk Pembayaran QRIS
                                        </div>
                                        <ol class="petunjuk-list">
                                            <li>Buka aplikasi m-Banking atau e-wallet pilihan Anda (BCA, Mandiri, GoPay, OVO, DANA, dll).</li>
                                            <li>Pilih menu <strong>Scan / Bayar QRIS</strong> dan arahkan kamera ke kode QR di samping.</li>
                                            <li>Pastikan nama penerima donasi adalah <strong><?= htmlspecialchars($qrisOwner) ?></strong>.</li>
                                            <li>Periksa nominal dan selesaikan pembayaran.</li>
                                            <li>Kembali ke SIBERASA untuk mengunggah bukti pembayaran.</li>
                                        </ol>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ── 2. KONTEN TRANSFER BANK (DARI DATABASE PENGUSUL) ── -->
                        <div id="contentTransfer" style="<?= ($metodeAktif === 'transfer') ? '' : 'display:none;' ?>">
                            <div class="transfer-flow-layout">
                                <div class="bank-list-wrap">
                                    <?php if (!empty($akunBank)): ?>
                                        <?php foreach ($akunBank as $ak): 
                                            $penyedia = strtoupper(trim($ak['nama_penyedia']));
                                            $nomor = trim($ak['nomor_tujuan']);
                                            $pemilik = trim($ak['nama_pemilik'] ?: $prog['penyelenggara']);
                                        ?>
                                        <div class="bank-card-modern">
                                            <div class="bank-card-header">
                                                <div class="bank-brand-info">
                                                    <span class="bank-badge"><?= htmlspecialchars($penyedia) ?></span>
                                                    <span class="bank-type-pill"><i class="fas fa-university"></i> Transfer Bank</span>
                                                </div>
                                                <div class="bank-owner-pill">
                                                    <i class="fas fa-user-check"></i> a.n. <strong><?= htmlspecialchars($pemilik) ?></strong>
                                                </div>
                                            </div>
                                            <div class="bank-card-body">
                                                <div class="bank-number-info">
                                                    <span class="bank-number-label">Nomor Rekening Tujuan</span>
                                                    <div class="bank-number-val"><?= htmlspecialchars($nomor) ?></div>
                                                </div>
                                                <button type="button" class="btn-copy-num" onclick="salinTeks('<?= htmlspecialchars($nomor) ?>', 'No. Rekening <?= htmlspecialchars($penyedia) ?>')">
                                                    <i class="far fa-copy"></i> Salin Nomor
                                                </button>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div style="background:#fef2f2; border:1px solid #fecaca; border-radius:12px; padding:12px 16px; font-size:0.84rem; color:#991b1b; line-height:1.5;">
                                            <i class="fas fa-info-circle"></i> Pengusul tidak mendaftarkan nomor rekening bank khusus. Anda dapat menggunakan akun <strong>E-Wallet</strong> pengusul atau rekening resmi SIBERASA:
                                        </div>
                                        <div class="bank-card-modern">
                                            <div class="bank-card-header">
                                                <div class="bank-brand-info">
                                                    <span class="bank-badge">BCA</span>
                                                    <span class="bank-type-pill"><i class="fas fa-university"></i> Rekening Resmi SIBERASA</span>
                                                </div>
                                                <div class="bank-owner-pill">
                                                    <i class="fas fa-user-check"></i> a.n. <strong>SIBERASA Peduli</strong>
                                                </div>
                                            </div>
                                            <div class="bank-card-body">
                                                <div class="bank-number-info">
                                                    <span class="bank-number-label">Nomor Rekening Tujuan</span>
                                                    <div class="bank-number-val">8930 1289 4410</div>
                                                </div>
                                                <button type="button" class="btn-copy-num" onclick="salinTeks('893012894410', 'No. Rekening BCA')">
                                                    <i class="far fa-copy"></i> Salin Nomor
                                                </button>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- KARTU NOMINAL TRANSFER -->
                                <div class="nominal-highlight-box">
                                    <div class="nominal-highlight-left">
                                        <span class="nominal-highlight-label">Jumlah Yang Harus Ditransfer</span>
                                        <div class="nominal-highlight-val"><?= formatRupiah($nominal) ?></div>
                                        <span class="nominal-highlight-hint"><i class="fas fa-shield-alt"></i> Pastikan nominal transfer sesuai hingga digit terakhir</span>
                                    </div>
                                    <button type="button" class="btn-copy-nominal" onclick="salinTeks('<?= $nominal ?>', 'Nominal Donasi')">
                                        <i class="far fa-copy"></i> Salin Nominal
                                    </button>
                                </div>

                                <!-- PETUNJUK PEMBAYARAN -->
                                <div class="petunjuk-box">
                                    <div class="petunjuk-header">
                                        <i class="fas fa-list-ol"></i> Petunjuk Pembayaran Transfer Bank
                                    </div>
                                    <ol class="petunjuk-list">
                                        <li>Buka aplikasi m-Banking atau kunjungi ATM bank pilihan Anda.</li>
                                        <li>Pilih menu <strong>Transfer</strong> &gt; Masukkan nomor rekening tujuan di atas.</li>
                                        <li>Pastikan nama pemilik rekening adalah <strong><?= htmlspecialchars(!empty($akunBank) ? ($akunBank[0]['nama_pemilik'] ?: $prog['penyelenggara']) : 'SIBERASA Peduli') ?></strong>.</li>
                                        <li>Masukkan nominal transfer tepat sebesar <strong><?= formatRupiah($nominal) ?></strong>.</li>
                                        <li>Simpan bukti transfer untuk diunggah pada halaman berikutnya.</li>
                                    </ol>
                                </div>
                            </div>
                        </div>

                        <!-- ── 3. KONTEN E-WALLET (DARI DATABASE PENGUSUL) ── -->
                        <div id="contentEwallet" style="<?= ($metodeAktif === 'ewallet') ? '' : 'display:none;' ?>">
                            <div class="transfer-flow-layout">
                                <div class="bank-list-wrap">
                                    <?php if (!empty($akunEwallet)): ?>
                                        <?php foreach ($akunEwallet as $ak): 
                                            $penyedia = strtoupper(trim($ak['nama_penyedia']));
                                            $nomor = trim($ak['nomor_tujuan']);
                                            $pemilik = trim($ak['nama_pemilik'] ?: $prog['penyelenggara']);
                                        ?>
                                        <div class="bank-card-modern">
                                            <div class="bank-card-header">
                                                <div class="bank-brand-info">
                                                    <span class="bank-badge"><?= htmlspecialchars($penyedia) ?></span>
                                                    <span class="bank-type-pill"><i class="fas fa-wallet"></i> E-Wallet</span>
                                                </div>
                                                <div class="bank-owner-pill">
                                                    <i class="fas fa-user-check"></i> a.n. <strong><?= htmlspecialchars($pemilik) ?></strong>
                                                </div>
                                            </div>
                                            <div class="bank-card-body">
                                                <div class="bank-number-info">
                                                    <span class="bank-number-label">Nomor Akun / Handphone</span>
                                                    <div class="bank-number-val"><?= htmlspecialchars($nomor) ?></div>
                                                </div>
                                                <button type="button" class="btn-copy-num" onclick="salinTeks('<?= htmlspecialchars($nomor) ?>', 'No. <?= htmlspecialchars($penyedia) ?>')">
                                                    <i class="far fa-copy"></i> Salin Nomor
                                                </button>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div style="background:#fef2f2; border:1px solid #fecaca; border-radius:12px; padding:12px 16px; font-size:0.84rem; color:#991b1b; line-height:1.5;">
                                            <i class="fas fa-info-circle"></i> Pengusul tidak mendaftarkan akun E-Wallet khusus. Anda dapat menggunakan metode <strong>Transfer Bank</strong> pengusul atau akun resmi SIBERASA:
                                        </div>
                                        <div class="bank-card-modern">
                                            <div class="bank-card-header">
                                                <div class="bank-brand-info">
                                                    <span class="bank-badge">DANA</span>
                                                    <span class="bank-type-pill"><i class="fas fa-wallet"></i> E-Wallet Resmi</span>
                                                </div>
                                                <div class="bank-owner-pill">
                                                    <i class="fas fa-user-check"></i> a.n. <strong>SIBERASA Peduli</strong>
                                                </div>
                                            </div>
                                            <div class="bank-card-body">
                                                <div class="bank-number-info">
                                                    <span class="bank-number-label">Nomor Akun / Handphone</span>
                                                    <div class="bank-number-val">0812 3456 7890</div>
                                                </div>
                                                <button type="button" class="btn-copy-num" onclick="salinTeks('081234567890', 'No. DANA')">
                                                    <i class="far fa-copy"></i> Salin Nomor
                                                </button>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- KARTU NOMINAL TRANSFER -->
                                <div class="nominal-highlight-box">
                                    <div class="nominal-highlight-left">
                                        <span class="nominal-highlight-label">Jumlah Yang Harus Ditransfer</span>
                                        <div class="nominal-highlight-val"><?= formatRupiah($nominal) ?></div>
                                        <span class="nominal-highlight-hint"><i class="fas fa-shield-alt"></i> Pastikan nominal transfer sesuai persis</span>
                                    </div>
                                    <button type="button" class="btn-copy-nominal" onclick="salinTeks('<?= $nominal ?>', 'Nominal Donasi')">
                                        <i class="far fa-copy"></i> Salin Nominal
                                    </button>
                                </div>

                                <!-- PETUNJUK PEMBAYARAN -->
                                <div class="petunjuk-box">
                                    <div class="petunjuk-header">
                                        <i class="fas fa-list-ol"></i> Petunjuk Pembayaran E-Wallet
                                    </div>
                                    <ol class="petunjuk-list">
                                        <li>Buka aplikasi e-wallet pilihan Anda (GoPay, OVO, DANA, ShopeePay, dll).</li>
                                        <li>Kirim saldo ke nomor tujuan e-wallet di atas.</li>
                                        <li>Pastikan nama akun penerima adalah <strong><?= htmlspecialchars(!empty($akunEwallet) ? ($akunEwallet[0]['nama_pemilik'] ?: $prog['penyelenggara']) : 'SIBERASA Peduli') ?></strong>.</li>
                                        <li>Masukkan nominal tepat sebesar <strong><?= formatRupiah($nominal) ?></strong>.</li>
                                        <li>Simpan tangkapan layar (screenshot) bukti transfer untuk diunggah di tahap berikutnya.</li>
                                    </ol>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="pembayaran-footer-note">
                    <i class="fas fa-info-circle"></i> Contoh QRIS di atas hanya ilustrasi. QRIS asli harus berasal dari penerima dana yang valid.
                </div>

            </div>

            <!-- ══════════════════════════════════════════════════════
                 KOLOM KANAN: RINGKASAN DONASI & TOMBOL UPLOAD BUKTI
                 ══════════════════════════════════════════════════════ -->
            <div class="pembayaran-right-col">
                <div class="ringkasan-card">
                    <h2 class="ringkasan-title">Ringkasan Donasi</h2>
                    
                    <div class="ringkasan-prog-mini">
                        <div class="ringkasan-mini-thumb">
                            <img src="<?= htmlspecialchars($prog['gambar']) ?>" alt="<?= htmlspecialchars($prog['judul']) ?>" onerror="this.src='/Donasi/assets/img/default_program.jpg'">
                        </div>
                        <div>
                            <div class="ringkasan-mini-title"><?= htmlspecialchars($prog['judul']) ?></div>
                            <span class="ringkasan-mini-badge"><?= htmlspecialchars($prog['kategori']) ?></span>
                        </div>
                    </div>

                    <div class="ringkasan-row">
                        <span>Nominal Donasi</span>
                        <strong id="labelNominal"><?= formatRupiah($nominal) ?></strong>
                    </div>

                    <div class="ringkasan-row">
                        <span>Metode Pembayaran</span>
                        <strong id="labelMetode">
                            <?= ($metodeAktif === 'transfer') ? 'Transfer Bank' : (($metodeAktif === 'ewallet') ? 'E-Wallet' : 'QRIS') ?>
                        </strong>
                    </div>

                    <div class="ringkasan-total-box">
                        <div class="ringkasan-total-icon">
                            <i class="fas fa-coins"></i>
                        </div>
                        <div class="ringkasan-total-text">
                            <div class="label">Total Pembayaran</div>
                            <div class="val"><?= formatRupiah($nominal) ?></div>
                        </div>
                    </div>

                    <a href="upload_bukti.php" id="btnUploadBukti" class="btn-upload-bukti-cta">
                        <i class="fas fa-cloud-arrow-up"></i> Upload Bukti Pembayaran <i class="fas fa-arrow-right" style="font-size:0.85rem;"></i>
                    </a>

                    <div class="ringkasan-alert-box">
                        <i class="fas fa-info-circle"></i>
                        <p>Setelah melakukan pembayaran, silakan unggah bukti pembayaran Anda. Bukti akan diverifikasi oleh admin kami.</p>
                    </div>

                    <div class="trust-badge-footer">
                        <i class="fas fa-shield-halved"></i> Transaksi Anda aman dan terpercaya
                    </div>
                </div>
            </div>

        </div>

    </div>
</div>

<!-- TOAST SALIN TEKS -->
<div class="toast-copied" id="toastCopied">
    <i class="fas fa-check-circle" style="color: #38bdf8;"></i>
    <span id="toastMsg">Teks berhasil disalin!</span>
</div>

<script>
let currentMetode = '<?= $metodeAktif ?>';
const programId = <?= (int)$prog['id'] ?>;
const nominalDonasi = <?= (int)$nominal ?>;

function switchMetode(metode) {
    currentMetode = metode;
    
    // Toggle tab active safely
    const tabQ = document.getElementById('tabCardQris');
    const tabT = document.getElementById('tabCardTransfer');
    const tabE = document.getElementById('tabCardEwallet');
    if (tabQ) tabQ.classList.toggle('active', metode === 'qris');
    if (tabT) tabT.classList.toggle('active', metode === 'transfer');
    if (tabE) tabE.classList.toggle('active', metode === 'ewallet');

    // Toggle content view safely
    const cQ = document.getElementById('contentQris');
    const cT = document.getElementById('contentTransfer');
    const cE = document.getElementById('contentEwallet');
    if (cQ) cQ.style.display = (metode === 'qris') ? 'block' : 'none';
    if (cT) cT.style.display = (metode === 'transfer') ? 'block' : 'none';
    if (cE) cE.style.display = (metode === 'ewallet') ? 'block' : 'none';

    // Update banner text & icon
    const bannerTitle = document.getElementById('bannerTitle');
    const bannerSubtitle = document.getElementById('bannerSubtitle');
    const bannerIcon = document.getElementById('bannerIcon');
    const labelMetode = document.getElementById('labelMetode');
    const btnUploadBukti = document.getElementById('btnUploadBukti');

    let metodeName = 'QRIS';
    if (metode === 'qris') {
        metodeName = 'QRIS';
        if (bannerIcon) bannerIcon.innerHTML = '<i class="fas fa-qrcode"></i>';
        if (bannerTitle) bannerTitle.textContent = 'Pembayaran dengan QRIS';
        if (bannerSubtitle) bannerSubtitle.textContent = 'Scan kode QR menggunakan aplikasi mobile banking atau e-wallet yang mendukung QRIS.';
    } else if (metode === 'transfer') {
        metodeName = 'Transfer Bank';
        if (bannerIcon) bannerIcon.innerHTML = '<i class="fas fa-university"></i>';
        if (bannerTitle) bannerTitle.textContent = 'Pembayaran dengan Transfer Bank';
        if (bannerSubtitle) bannerSubtitle.textContent = 'Transfer ke rekening bank tujuan pengusul di bawah ini.';
    } else if (metode === 'ewallet') {
        metodeName = 'E-Wallet';
        if (bannerIcon) bannerIcon.innerHTML = '<i class="fas fa-wallet"></i>';
        if (bannerTitle) bannerTitle.textContent = 'Pembayaran dengan E-Wallet';
        if (bannerSubtitle) bannerSubtitle.textContent = 'Transfer saldo ke akun e-wallet tujuan pengusul di bawah ini.';
    }

    if (labelMetode) labelMetode.textContent = metodeName;
    if (btnUploadBukti) btnUploadBukti.href = 'upload_bukti.php';

    // Update metode ke session secara asynchronous
    if (navigator.sendBeacon) {
        navigator.sendBeacon('pembayaran.php', new URLSearchParams({ action: 'update_metode', metode: metodeName }));
    } else {
        fetch('pembayaran.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=update_metode&metode=' + encodeURIComponent(metodeName)
        }).catch(() => {});
    }
}

function salinTeks(text, label) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(() => {
            showToast(`${label} disalin!`);
        });
    } else {
        const input = document.createElement('input');
        input.value = text;
        document.body.appendChild(input);
        input.select();
        document.execCommand('copy');
        document.body.removeChild(input);
        showToast(`${label} disalin!`);
    }
}

function showToast(msg) {
    const toast = document.getElementById('toastCopied');
    const toastMsg = document.getElementById('toastMsg');
    if (toast && toastMsg) {
        toastMsg.textContent = msg;
        toast.classList.add('show');
        setTimeout(() => {
            toast.classList.remove('show');
        }, 2500);
    }
}

// Bersihkan ?id=... dari address bar browser agar URL tampil bersih: http://localhost/Donasi/donatur/pembayaran.php
if (window.location.search) {
    window.history.replaceState(null, '', window.location.pathname);
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
