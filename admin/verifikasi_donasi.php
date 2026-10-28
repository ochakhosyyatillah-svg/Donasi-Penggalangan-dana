<?php
session_start();
require_once __DIR__ . '/../assets/config/database.php';

// Pastikan pengguna sudah login sebagai admin
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    header('Location: /Donasi/auth/login.php');
    exit;
}

$idAdmin   = (int)$_SESSION['id_user'];
$namaAdmin = $_SESSION['nama'] ?? 'Admin';

// ── Handle Aksi Verifikasi / Tolak ──────────────────────────────────
$alertMsg  = '';
$alertType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';
    $idPembayaran = (int)($_POST['id_pembayaran'] ?? 0);
    $idDonasi     = (int)($_POST['id_donasi'] ?? 0);

    if ($idPembayaran > 0 && $idDonasi > 0) {
        if ($aksi === 'verifikasi') {
            try {
                $pdo->beginTransaction();

                // Update status pembayaran
                $stmtP = $pdo->prepare("
                    UPDATE pembayaran 
                    SET status_verifikasi = 'diterima',
                        diverifikasi_oleh = ?,
                        diverifikasi_pada = NOW()
                    WHERE id_pembayaran = ?
                ");
                $stmtP->execute([$idAdmin, $idPembayaran]);

                // Update status donasi
                $stmtD = $pdo->prepare("
                    UPDATE donasi 
                    SET status_donasi = 'berhasil' 
                    WHERE id_donasi = ?
                ");
                $stmtD->execute([$idDonasi]);

                // Catat ke riwayat verifikasi jika tabel ada
                try {
                    $stmtV = $pdo->prepare("
                        INSERT INTO verifikasi (id_admin, id_pembayaran, jenis_verifikasi, hasil, tanggal_verifikasi)
                        VALUES (?, ?, 'pembayaran', 'diterima', NOW())
                    ");
                    $stmtV->execute([$idAdmin, $idPembayaran]);
                } catch (Exception $ev) {
                    // Abaikan jika struktur verifikasi berbeda
                }

                $pdo->commit();
                $alertMsg  = 'Donasi berhasil diverifikasi dan disetujui.';
                $alertType = 'success';
            } catch (Exception $e) {
                $pdo->rollBack();
                $alertMsg  = 'Gagal memverifikasi donasi: ' . $e->getMessage();
                $alertType = 'error';
            }
        } elseif ($aksi === 'tolak') {
            $catatan = trim($_POST['catatan_admin'] ?? 'Bukti pembayaran tidak valid atau tidak sesuai.');
            try {
                $pdo->beginTransaction();

                // Update status pembayaran
                $stmtP = $pdo->prepare("
                    UPDATE pembayaran 
                    SET status_verifikasi = 'ditolak',
                        catatan_admin = ?,
                        diverifikasi_oleh = ?,
                        diverifikasi_pada = NOW()
                    WHERE id_pembayaran = ?
                ");
                $stmtP->execute([$catatan, $idAdmin, $idPembayaran]);

                // Update status donasi
                $stmtD = $pdo->prepare("
                    UPDATE donasi 
                    SET status_donasi = 'ditolak' 
                    WHERE id_donasi = ?
                ");
                $stmtD->execute([$idDonasi]);

                // Catat riwayat
                try {
                    $stmtV = $pdo->prepare("
                        INSERT INTO verifikasi (id_admin, id_pembayaran, jenis_verifikasi, hasil, catatan, tanggal_verifikasi)
                        VALUES (?, ?, 'pembayaran', 'ditolak', ?, NOW())
                    ");
                    $stmtV->execute([$idAdmin, $idPembayaran, $catatan]);
                } catch (Exception $ev) {}

                $pdo->commit();
                $alertMsg  = 'Donasi telah ditolak.';
                $alertType = 'warning';
            } catch (Exception $e) {
                $pdo->rollBack();
                $alertMsg  = 'Gagal menolak donasi: ' . $e->getMessage();
                $alertType = 'error';
            }
        }
    }
}

// ── Filter & Tab Aktif ──────────────────────────────────────────────
$tab = $_GET['tab'] ?? 'menunggu';
if (!in_array($tab, ['menunggu', 'terverifikasi', 'ditolak'])) {
    $tab = 'menunggu';
}

$tabDbStatus = [
    'menunggu'      => 'menunggu',
    'terverifikasi' => 'diterima',
    'ditolak'       => 'ditolak'
][$tab];

$search        = trim($_GET['q'] ?? '');
$filterTanggal = trim($_GET['tanggal'] ?? '');

// Hitung jumlah untuk tiap tab (badge counts)
$countMenunggu = (int)$pdo->query("SELECT COUNT(*) FROM pembayaran WHERE status_verifikasi = 'menunggu'")->fetchColumn();
$countDiterima = (int)$pdo->query("SELECT COUNT(*) FROM pembayaran WHERE status_verifikasi = 'diterima'")->fetchColumn();
$countDitolak  = (int)$pdo->query("SELECT COUNT(*) FROM pembayaran WHERE status_verifikasi = 'ditolak'")->fetchColumn();

// ── Query Data Donasi Sesuai Tab & Filter ────────────────────────────
$where = "p.status_verifikasi = ?";
$params = [$tabDbStatus];

if ($search !== '') {
    $where .= " AND (d.nama_donatur LIKE ? OR pr.judul LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($filterTanggal !== '') {
    $where .= " AND DATE(p.tanggal_pembayaran) = ?";
    $params[] = $filterTanggal;
}

$querySql = "
    SELECT 
        p.id_pembayaran,
        p.id_donasi,
        p.metode_pembayaran,
        p.tanggal_pembayaran,
        p.bukti_pembayaran,
        p.status_verifikasi,
        p.catatan_admin,
        d.kode_tracking,
        d.nama_donatur,
        d.email_donatur,
        d.no_hp_donatur,
        d.nominal,
        d.pesan_donatur,
        d.tanggal_donasi,
        pr.id_program,
        pr.judul AS nama_program,
        pr.kategori AS kategori_program,
        ak.nama_penyedia AS bank_tujuan,
        ak.nomor_tujuan AS rekening_tujuan
    FROM pembayaran p
    JOIN donasi d ON p.id_donasi = d.id_donasi
    JOIN program_donasi pr ON d.id_program = pr.id_program
    LEFT JOIN akun_tujuan_pembayaran ak ON p.id_akun = ak.id_akun
    WHERE $where
    ORDER BY p.tanggal_pembayaran DESC, p.id_pembayaran DESC
";

$stmt = $pdo->prepare($querySql);
$stmt->execute($params);
$listDonasi = $stmt->fetchAll();

// Format Rupiah
function rp($nominal) {
    return 'Rp ' . number_format($nominal, 0, ',', '.');
}

// Format Tanggal Indonesia (e.g. 29 Sep 2025)
function tglIndo($datetime) {
    if (!$datetime) return '-';
    $time = strtotime($datetime);
    $bulanNames = [
        1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
        'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'
    ];
    $d = date('j', $time);
    $m = $bulanNames[(int)date('n', $time)] ?? date('M', $time);
    $y = date('Y', $time);
    return "$d $m $y";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Verifikasi Donasi – SIBERASA Admin</title>
<meta name="description" content="Periksa dan verifikasi bukti pembayaran donasi masuk.">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
/* ── Reset & Base ─────────────────────────────────────────────── */
*, *::before, *::after {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
}
html { scroll-behavior: smooth; }
body {
  font-family: 'Poppins', sans-serif;
  background: #f0f4f8;
  color: #1e293b;
  min-height: 100vh;
  display: flex;
  overflow-x: hidden;
}
a { text-decoration: none; color: inherit; }
ul { list-style: none; }

/* ── SIDEBAR (IDENTIK DENGAN HALAMAN ADMIN LAINNYA) ──────────── */
.sidebar {
  width: 260px;
  min-height: 100vh;
  background: linear-gradient(180deg, #0f4c75 0%, #1b6ca8 50%, #3282b8 100%);
  display: flex;
  flex-direction: column;
  padding: 0;
  position: fixed;
  top: 0;
  left: 0;
  bottom: 0;
  z-index: 100;
  transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 4px 0 24px rgba(0, 0, 0, 0.12);
}

.sidebar-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20px 20px 0;
}

.sidebar-logo {
  display: flex;
  align-items: center;
  gap: 10px;
}

.sidebar-logo-img {
  height: 54px;
  width: auto;
  max-width: 200px;
  object-fit: contain;
  filter: drop-shadow(0 2px 8px rgba(0, 0, 0, 0.25)) brightness(1.05);
  transition: transform 0.2s ease;
}

.sidebar-logo-img:hover {
  transform: scale(1.04);
}

/* User Card */
.sidebar-user {
  display: flex;
  align-items: center;
  gap: 12px;
  margin: 24px 20px 8px;
  padding: 14px 16px;
  background: rgba(255, 255, 255, 0.12);
  border-radius: 12px;
  backdrop-filter: blur(8px);
}

.sidebar-user .avatar {
  width: 42px;
  height: 42px;
  border-radius: 50%;
  background: linear-gradient(135deg, #4fc3f7, #0288d1);
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 1rem;
  color: #fff;
  flex-shrink: 0;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

.sidebar-user .user-info {
  overflow: hidden;
}

.sidebar-user .user-role {
  font-size: 0.72rem;
  font-weight: 600;
  color: rgba(255, 255, 255, 0.9);
  text-transform: uppercase;
  letter-spacing: 1px;
}

.sidebar-user .user-name {
  font-size: 0.82rem;
  color: rgba(255, 255, 255, 0.75);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Nav Menu */
.sidebar-nav {
  flex: 1;
  padding: 16px 12px 0;
}

.sidebar-nav .nav-item {
  margin-bottom: 4px;
}

.sidebar-nav .nav-link {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  border-radius: 10px;
  font-size: 0.88rem;
  font-weight: 500;
  color: rgba(255, 255, 255, 0.75);
  transition: all 0.25s ease;
  cursor: pointer;
  position: relative;
}

.sidebar-nav .nav-link i {
  width: 20px;
  text-align: center;
  font-size: 1rem;
  transition: transform 0.2s;
}

.sidebar-nav .nav-link:hover {
  background: rgba(255, 255, 255, 0.12);
  color: #fff;
  transform: translateX(4px);
}

.sidebar-nav .nav-link:hover i {
  transform: scale(1.15);
}

.sidebar-nav .nav-link.active {
  background: rgba(255, 255, 255, 0.2);
  color: #fff;
  font-weight: 600;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.sidebar-nav .nav-link.active::before {
  content: '';
  position: absolute;
  left: 0;
  top: 50%;
  transform: translateY(-50%);
  width: 4px;
  height: 60%;
  background: #4fc3f7;
  border-radius: 0 4px 4px 0;
}

/* Footer / Logout */
.sidebar-footer {
  padding: 16px 12px 24px;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
  margin: 0 12px;
}

.sidebar-footer .nav-link {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  border-radius: 10px;
  font-size: 0.88rem;
  font-weight: 500;
  color: rgba(255, 255, 255, 0.65);
  transition: all 0.25s ease;
  cursor: pointer;
}

.sidebar-footer .nav-link:hover {
  background: rgba(239, 68, 68, 0.18);
  color: #fca5a5;
}

/* ── MAIN CONTENT ─────────────────────────────────────────────── */
.main-wrapper {
  margin-left: 260px;
  flex: 1;
  display: flex;
  flex-direction: column;
  min-height: 100vh;
  background: #f0f4f8;
}

/* Topbar */
.topbar {
  height: 64px;
  background: #ffffff;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 32px;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04);
}

.mobile-toggle {
  display: none;
  background: none;
  border: none;
  font-size: 1.25rem;
  color: #64748b;
  cursor: pointer;
  padding: 6px;
}

.notif-btn {
  background: none;
  border: none;
  cursor: pointer;
  font-size: 1.15rem;
  color: #64748b;
  padding: 8px;
  border-radius: 50%;
  transition: background 0.2s, color 0.2s;
}

.notif-btn:hover {
  background: #f1f5f9;
  color: #1b6ca8;
}

/* Content Area */
.content-area {
  padding: 32px;
  flex: 1;
}

/* Page Header */
.page-title-box {
  margin-bottom: 24px;
}

.page-title-box h1 {
  font-size: 1.75rem;
  font-weight: 700;
  color: #0f4c75;
  margin-bottom: 4px;
}

.page-title-box p {
  font-size: 0.92rem;
  color: #64748b;
  font-weight: 400;
}

/* Alert Box */
.alert-box {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 18px;
  border-radius: 12px;
  font-size: 0.88rem;
  font-weight: 500;
  margin-bottom: 20px;
  animation: fadeInDown 0.3s ease;
}

@keyframes fadeInDown {
  from { opacity: 0; transform: translateY(-8px); }
  to { opacity: 1; transform: translateY(0); }
}

.alert-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
.alert-warning { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
.alert-error   { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }

.alert-close-btn {
  background: none;
  border: none;
  cursor: pointer;
  color: inherit;
  font-size: 1.1rem;
  opacity: 0.7;
}
.alert-close-btn:hover { opacity: 1; }

/* ── TABS (SEPERTI DI DESAIN) ─────────────────────────────────── */
.tabs-container {
  display: flex;
  align-items: center;
  background: #e8eff5;
  border-radius: 10px;
  margin-bottom: 20px;
  padding: 0;
  border-bottom: 2px solid #cbd5e1;
  overflow: hidden;
}

.tab-btn {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 13px 26px;
  font-size: 0.92rem;
  font-weight: 600;
  color: #475569;
  border-bottom: 3px solid transparent;
  transition: all 0.2s ease;
}

.tab-btn:hover {
  color: #1b6ca8;
  background: rgba(255, 255, 255, 0.4);
}

.tab-btn.active {
  color: #1b6ca8;
  border-bottom-color: #1b6ca8;
  background: #ffffff;
}

/* ── FILTER BAR ───────────────────────────────────────────────── */
.filter-bar {
  display: grid;
  grid-template-columns: 1fr 300px;
  gap: 16px;
  margin-bottom: 22px;
}

.filter-input {
  width: 100%;
  padding: 12px 16px;
  border: 1.5px solid #d1d5db;
  border-radius: 10px;
  font-family: 'Poppins', sans-serif;
  font-size: 0.88rem;
  color: #1e293b;
  background: #ffffff;
  outline: none;
  transition: border-color 0.2s, box-shadow 0.2s;
}

.filter-input:focus {
  border-color: #1b6ca8;
  box-shadow: 0 0 0 3px rgba(27, 108, 168, 0.12);
}

.filter-input::placeholder {
  color: #94a3b8;
}

/* ── VERIFICATION TABLE ───────────────────────────────────────── */
.table-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  overflow: hidden;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.04);
}

.verif-table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
}

.verif-table th {
  background: #eef4f9;
  color: #0f4c75;
  font-size: 0.86rem;
  font-weight: 600;
  padding: 16px 20px;
  border-bottom: 1.5px solid #dbeafe;
  white-space: nowrap;
}

.verif-table td {
  padding: 20px;
  border-bottom: 1px solid #f1f5f9;
  font-size: 0.9rem;
  color: #334155;
  vertical-align: middle;
}

.verif-table tr:last-child td {
  border-bottom: none;
}

.verif-table tbody tr:hover {
  background: #f8fafc;
}

.col-nama {
  font-weight: 600;
  color: #1e293b;
}

.col-program {
  color: #475569;
  max-width: 280px;
  line-height: 1.45;
}

.col-nominal {
  font-weight: 600;
  color: #0f4c75;
  white-space: nowrap;
}

.col-tanggal {
  color: #64748b;
  white-space: nowrap;
}

/* Action Buttons */
.btn-outline-lihat {
  display: inline-block;
  padding: 6px 22px;
  border: 1.5px solid #1b6ca8;
  color: #1b6ca8;
  background: #ffffff;
  border-radius: 8px;
  font-family: 'Poppins', sans-serif;
  font-size: 0.86rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-outline-lihat:hover {
  background: #1b6ca8;
  color: #ffffff;
  box-shadow: 0 4px 12px rgba(27, 108, 168, 0.25);
}

.actions-cell {
  width: 135px;
}

.btn-action-verif {
  display: block;
  width: 100%;
  padding: 7px 16px;
  background: #1b6ca8;
  color: #ffffff;
  border: none;
  border-radius: 8px;
  font-family: 'Poppins', sans-serif;
  font-size: 0.86rem;
  font-weight: 600;
  cursor: pointer;
  text-align: center;
  margin-bottom: 8px;
  transition: all 0.2s ease;
}

.btn-action-verif:hover {
  background: #0f4c75;
  box-shadow: 0 4px 12px rgba(15, 76, 117, 0.3);
}

.btn-action-tolak {
  display: block;
  width: 100%;
  padding: 6px 16px;
  background: #ffffff;
  color: #ef4444;
  border: 1.5px solid #fca5a5;
  border-radius: 8px;
  font-family: 'Poppins', sans-serif;
  font-size: 0.86rem;
  font-weight: 600;
  cursor: pointer;
  text-align: center;
  transition: all 0.2s ease;
}

.btn-action-tolak:hover {
  background: #fef2f2;
  border-color: #ef4444;
}

/* Badges */
.badge-status {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 14px;
  border-radius: 999px;
  font-size: 0.82rem;
  font-weight: 600;
}

.badge-success {
  background: #ecfdf5;
  color: #059669;
  border: 1px solid #a7f3d0;
}

.badge-danger {
  background: #fef2f2;
  color: #dc2626;
  border: 1px solid #fecaca;
}

.empty-state {
  text-align: center;
  padding: 60px 20px;
  color: #94a3b8;
}

.empty-state i {
  font-size: 3rem;
  margin-bottom: 12px;
  color: #cbd5e1;
}

.empty-state h3 {
  font-size: 1.05rem;
  color: #475569;
  margin-bottom: 4px;
}

/* ── MODALS ───────────────────────────────────────────────────── */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.6);
  backdrop-filter: blur(4px);
  -webkit-backdrop-filter: blur(4px);
  display: none;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 20px;
}

.modal-overlay.show { display: flex; }

.modal-card {
  background: #ffffff;
  border-radius: 18px;
  width: 100%;
  max-width: 580px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
  animation: modalIn 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes modalIn {
  from { opacity: 0; transform: scale(0.95) translateY(10px); }
  to { opacity: 1; transform: scale(1) translateY(0); }
}

.modal-header {
  padding: 20px 24px;
  border-bottom: 1px solid #f1f5f9;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.modal-header h3 {
  font-size: 1.15rem;
  font-weight: 700;
  color: #0f4c75;
}

.modal-close-btn {
  background: none;
  border: none;
  font-size: 1.25rem;
  color: #94a3b8;
  cursor: pointer;
  padding: 4px;
  border-radius: 6px;
  transition: color 0.2s;
}

.modal-close-btn:hover { color: #ef4444; }

.modal-body { padding: 24px; }

.modal-info-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
  margin-bottom: 20px;
  background: #f8fafc;
  padding: 16px;
  border-radius: 12px;
  border: 1px solid #f1f5f9;
}

.modal-info-item .lbl {
  font-size: 0.74rem;
  color: #64748b;
  text-transform: uppercase;
  font-weight: 600;
  letter-spacing: 0.3px;
}

.modal-info-item .val {
  font-size: 0.9rem;
  color: #0f172a;
  font-weight: 600;
  margin-top: 2px;
}

.proof-img-container {
  border-radius: 12px;
  overflow: hidden;
  border: 1.5px solid #e2e8f0;
  background: #0f172a;
  text-align: center;
  max-height: 400px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.proof-img-container img {
  max-width: 100%;
  max-height: 400px;
  object-fit: contain;
  display: block;
}

.modal-footer {
  padding: 18px 24px;
  border-top: 1px solid #f1f5f9;
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 10px;
}

.form-textarea {
  width: 100%;
  padding: 12px 14px;
  border: 1.5px solid #e2e8f0;
  border-radius: 10px;
  font-family: 'Poppins', sans-serif;
  font-size: 0.88rem;
  color: #1e293b;
  outline: none;
  resize: vertical;
  min-height: 90px;
}

.form-textarea:focus {
  border-color: #ef4444;
  box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.12);
}

/* ── RESPONSIVE ───────────────────────────────────────────────── */
@media(max-width: 768px) {
  .sidebar { transform: translateX(-100%); }
  .sidebar.open { transform: translateX(0); }
  .main-wrapper { margin-left: 0; }
  .mobile-toggle { display: block; }
  .content-area { padding: 20px 16px; }
  .filter-bar { grid-template-columns: 1fr; }
}
</style>
</head>
<body>

<!-- ═══ SIDEBAR (IDENTIK DENGAN ADMIN LAINNYA) ═════════════════ -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-header">
    <div class="sidebar-logo">
      <img src="/logo%20donasi.png" 
           alt="SIBERASA Logo" 
           class="sidebar-logo-img" 
           onerror="this.src='/Donasi/assets/img/logo.png'">
    </div>
  </div>

  <div class="sidebar-user">
    <div class="avatar">
      <?= strtoupper(mb_substr($namaAdmin, 0, 1)) ?>
    </div>
    <div class="user-info">
      <div class="user-role">Admin</div>
      <div class="user-name"><?= htmlspecialchars($namaAdmin, ENT_QUOTES, 'UTF-8') ?></div>
    </div>
  </div>

  <nav class="sidebar-nav">
    <ul>
      <li class="nav-item">
        <a href="dashboard.php" class="nav-link" id="nav-dashboard">
          <i class="fas fa-th-large"></i><span>Dashboard</span>
        </a>
      </li>
      <li class="nav-item">
        <a href="kelola_program.php" class="nav-link" id="nav-program">
          <i class="fas fa-hand-holding-usd"></i><span>Kelola Program Donasi</span>
        </a>
      </li>
      <li class="nav-item">
        <a href="kelola_pengguna.php" class="nav-link" id="nav-pengguna">
          <i class="fas fa-users"></i><span>Kelola Pengguna</span>
        </a>
      </li>
      <li class="nav-item">
        <a href="verifikasi_donasi.php" class="nav-link active" id="nav-verifikasi">
          <i class="fas fa-clipboard-check"></i><span>Verifikasi Donasi</span>
        </a>
      </li>
    </ul>
  </nav>

  <div class="sidebar-footer">
    <a href="logout.php" class="nav-link" id="nav-logout">
      <i class="fas fa-sign-out-alt"></i><span>Logout</span>
    </a>
  </div>
</aside>

<!-- ═══ MAIN CONTENT WRAPPER ═══════════════════════════════════════ -->
<div class="main-wrapper">

  <!-- Topbar -->
  <header class="topbar">
    <div class="topbar-left">
      <button class="mobile-toggle" id="mobileToggle" aria-label="Toggle sidebar">
        <i class="fas fa-bars"></i>
      </button>
    </div>
    <div>
      <button class="notif-btn" aria-label="Notifikasi">
        <i class="far fa-bell"></i>
      </button>
    </div>
  </header>

  <!-- Content Area -->
  <main class="content-area">

    <!-- Page Title -->
    <div class="page-title-box">
      <h1>Verifikasi Donasi</h1>
      <p>periksa dan verifikasi bukti pembayaran donasi</p>
    </div>

    <!-- Feedback Alert -->
    <?php if ($alertMsg !== ''): ?>
      <div class="alert-box alert-<?= $alertType ?>" id="alertBox">
        <div style="display:flex;align-items:center;gap:10px;">
          <i class="fas fa-<?= $alertType === 'success' ? 'check-circle' : ($alertType === 'warning' ? 'exclamation-triangle' : 'exclamation-circle') ?>"></i>
          <span><?= htmlspecialchars($alertMsg, ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <button class="alert-close-btn" onclick="document.getElementById('alertBox').remove()">&times;</button>
      </div>
    <?php endif; ?>

    <!-- ── Filter Tabs ────────────────────────────────────────────── -->
    <div class="tabs-container">
      <a href="verifikasi_donasi.php?tab=menunggu<?= $search ? '&q='.urlencode($search) : '' ?><?= $filterTanggal ? '&tanggal='.urlencode($filterTanggal) : '' ?>" 
         class="tab-btn <?= $tab === 'menunggu' ? 'active' : '' ?>">
        Menunggu Verifikasi (<?= $countMenunggu ?>)
      </a>
      <a href="verifikasi_donasi.php?tab=terverifikasi<?= $search ? '&q='.urlencode($search) : '' ?><?= $filterTanggal ? '&tanggal='.urlencode($filterTanggal) : '' ?>" 
         class="tab-btn <?= $tab === 'terverifikasi' ? 'active' : '' ?>">
        Terverifikasi (<?= $countDiterima ?>)
      </a>
      <a href="verifikasi_donasi.php?tab=ditolak<?= $search ? '&q='.urlencode($search) : '' ?><?= $filterTanggal ? '&tanggal='.urlencode($filterTanggal) : '' ?>" 
         class="tab-btn <?= $tab === 'ditolak' ? 'active' : '' ?>">
        Ditolak (<?= $countDitolak ?>)
      </a>
    </div>

    <!-- ── Search & Date Filters ──────────────────────────────────── -->
    <form method="GET" action="verifikasi_donasi.php" id="filterForm" class="filter-bar">
      <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
      
      <div class="search-field">
        <input 
          type="text" 
          name="q" 
          id="searchInput" 
          class="filter-input" 
          placeholder="Cari nama donatur atau program..."
          value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
      </div>

      <div class="date-field">
        <input 
          type="date" 
          name="tanggal" 
          id="dateInput" 
          class="filter-input" 
          placeholder="Tanggal"
          value="<?= htmlspecialchars($filterTanggal, ENT_QUOTES, 'UTF-8') ?>"
          onchange="document.getElementById('filterForm').submit()">
      </div>
    </form>

    <!-- ── Verification Table ────────────────────────────────────── -->
    <div class="table-card">
      <table class="verif-table" id="verifTable">
        <thead>
          <tr>
            <th>Nama Donatur</th>
            <th>Program Donasi</th>
            <th>Jumlah</th>
            <th>Bukti Pembayaran</th>
            <th>Tanggal</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($listDonasi)): ?>
            <tr>
              <td colspan="6">
                <div class="empty-state">
                  <i class="far fa-folder-open"></i>
                  <h3>Tidak ada data donasi</h3>
                  <p>Tidak ada pembayaran donasi pada kategori ini.</p>
                </div>
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($listDonasi as $row): 
              $buktiImg = !empty($row['bukti_pembayaran']) ? '/Donasi/assets/img/' . $row['bukti_pembayaran'] : '/Donasi/assets/img/bukti_sample.jpg';
            ?>
              <tr data-nama="<?= strtolower(htmlspecialchars($row['nama_donatur'])) ?>" data-prog="<?= strtolower(htmlspecialchars($row['nama_program'])) ?>">
                
                <!-- Nama Donatur -->
                <td class="col-nama">
                  <?= htmlspecialchars($row['nama_donatur']) ?>
                </td>

                <!-- Program Donasi -->
                <td class="col-program">
                  <?= htmlspecialchars($row['nama_program']) ?>
                </td>

                <!-- Jumlah -->
                <td class="col-nominal">
                  <?= rp($row['nominal']) ?>
                </td>

                <!-- Bukti Pembayaran -->
                <td>
                  <button 
                    type="button" 
                    class="btn-outline-lihat"
                    onclick='openProofModal(<?= json_encode([
                      "id_pembayaran" => $row["id_pembayaran"],
                      "id_donasi"     => $row["id_donasi"],
                      "nama"          => $row["nama_donatur"],
                      "program"       => $row["nama_program"],
                      "nominal"       => rp($row["nominal"]),
                      "tanggal"       => tglIndo($row["tanggal_pembayaran"] ?? $row["tanggal_donasi"]),
                      "metode"        => strtoupper($row["metode_pembayaran"] ?? "BANK"),
                      "bank"          => $row["bank_tujuan"] ?? "BSI",
                      "gambar"        => $buktiImg,
                      "status"        => $row["status_verifikasi"],
                      "catatan"       => $row["catatan_admin"] ?? ""
                    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                    Lihat
                  </button>
                </td>

                <!-- Tanggal -->
                <td class="col-tanggal">
                  <?= tglIndo($row['tanggal_pembayaran'] ?? $row['tanggal_donasi']) ?>
                </td>

                <!-- Aksi -->
                <td class="actions-cell">
                  <?php if ($row['status_verifikasi'] === 'menunggu'): ?>
                    <button 
                      type="button" 
                      class="btn-action-verif"
                      onclick="openVerifModal(<?= $row['id_pembayaran'] ?>, <?= $row['id_donasi'] ?>, '<?= htmlspecialchars(addslashes($row['nama_donatur'])) ?>', '<?= rp($row['nominal']) ?>', '<?= htmlspecialchars(addslashes($row['nama_program'])) ?>')">
                      Verifikasi
                    </button>

                    <button 
                      type="button" 
                      class="btn-action-tolak"
                      onclick="openRejectModal(<?= $row['id_pembayaran'] ?>, <?= $row['id_donasi'] ?>, '<?= htmlspecialchars(addslashes($row['nama_donatur'])) ?>', '<?= rp($row['nominal']) ?>', '<?= htmlspecialchars(addslashes($row['nama_program'])) ?>')">
                      Tolak
                    </button>

                  <?php elseif ($row['status_verifikasi'] === 'diterima'): ?>
                    <span class="badge-status badge-success">
                      <i class="fas fa-check-circle"></i> Terverifikasi
                    </span>

                  <?php elseif ($row['status_verifikasi'] === 'ditolak'): ?>
                    <span class="badge-status badge-danger" title="<?= htmlspecialchars($row['catatan_admin'] ?? 'Ditolak') ?>">
                      <i class="fas fa-times-circle"></i> Ditolak
                    </span>
                  <?php endif; ?>
                </td>

              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

  </main>
</div>

<!-- ═══════════════════════════════════════════════════════════════════
     MODAL LIHAT BUKTI PEMBAYARAN
════════════════════════════════════════════════════════════════════ -->
<div class="modal-overlay" id="proofModal" onclick="closeOnOutside(event, 'proofModal')">
  <div class="modal-card">
    <div class="modal-header">
      <h3>Bukti Pembayaran Donasi</h3>
      <button class="modal-close-btn" onclick="closeModal('proofModal')">&times;</button>
    </div>
    
    <div class="modal-body">
      <div class="modal-info-grid">
        <div class="modal-info-item">
          <div class="lbl">Nama Donatur</div>
          <div class="val" id="mDonorName">-</div>
        </div>
        <div class="modal-info-item">
          <div class="lbl">Program Donasi</div>
          <div class="val" id="mProgramName">-</div>
        </div>
        <div class="modal-info-item">
          <div class="lbl">Nominal Transfer</div>
          <div class="val" id="mNominal" style="color:#0f4c75;">-</div>
        </div>
        <div class="modal-info-item">
          <div class="lbl">Tanggal & Metode</div>
          <div class="val" id="mDateMethod">-</div>
        </div>
      </div>

      <div class="proof-img-container">
        <img id="mProofImg" src="/Donasi/assets/img/bukti_sample.jpg" alt="Bukti Transfer">
      </div>
    </div>

    <div class="modal-footer" id="modalProofFooter">
      <button type="button" class="btn-action-tolak" style="width:auto;padding:8px 20px;margin-bottom:0;" onclick="closeModal('proofModal')">Tutup</button>
      <div id="mActionButtons" style="display:inline-flex;gap:8px;"></div>
    </div>
  </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════
     MODAL KONFIRMASI VERIFIKASI DONASI
════════════════════════════════════════════════════════════════════ -->
<div class="modal-overlay" id="confirmVerifModal" onclick="closeOnOutside(event, 'confirmVerifModal')">
  <div class="modal-card" style="max-width: 480px;">
    <div class="modal-header" style="border-bottom: 1px solid #f1f5f9; padding: 18px 24px;">
      <div style="display:flex; align-items:center; gap:12px;">
        <div style="width:40px; height:40px; border-radius:50%; background:#ecfdf5; display:flex; align-items:center; justify-content:center; color:#10b981; font-size:1.25rem;">
          <i class="fas fa-check-circle"></i>
        </div>
        <div>
          <h3 style="font-size:1.1rem; color:#0f4c75; margin:0;">Konfirmasi Verifikasi Donasi</h3>
          <p style="font-size:0.78rem; color:#64748b; margin:2px 0 0 0;">Periksa dan setujui transaksi pembayaran donatur</p>
        </div>
      </div>
      <button class="modal-close-btn" onclick="closeModal('confirmVerifModal')">&times;</button>
    </div>

    <form method="POST" action="verifikasi_donasi.php?tab=<?= $tab ?>">
      <input type="hidden" name="aksi" value="verifikasi">
      <input type="hidden" name="id_pembayaran" id="verifIdPembayaran" value="">
      <input type="hidden" name="id_donasi" id="verifIdDonasi" value="">

      <div class="modal-body" style="padding: 22px 24px;">
        <p style="font-size:0.9rem; color:#334155; margin-bottom:16px; line-height:1.5;">
          Apakah Anda yakin ingin memverifikasi dan menyetujui transaksi donasi berikut?
        </p>

        <!-- Detail Box -->
        <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:16px; margin-bottom:16px;">
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; padding-bottom:8px; border-bottom:1px dashed #e2e8f0;">
            <span style="font-size:0.82rem; color:#64748b;">Nama Donatur</span>
            <strong id="verifDonorName" style="font-size:0.92rem; color:#0f172a;">-</strong>
          </div>
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; padding-bottom:8px; border-bottom:1px dashed #e2e8f0;">
            <span style="font-size:0.82rem; color:#64748b;">Nominal Donasi</span>
            <strong id="verifNominal" style="font-size:1.05rem; color:#059669; font-weight:700;">-</strong>
          </div>
          <div style="display:flex; justify-content:space-between; align-items:flex-start;">
            <span style="font-size:0.82rem; color:#64748b;">Program Donasi</span>
            <span id="verifProgramName" style="font-size:0.85rem; color:#334155; font-weight:600; text-align:right; max-width:62%;">-</span>
          </div>
        </div>

        <div style="display:flex; gap:10px; align-items:flex-start; background:#f0fdf4; border:1px solid #bbf7d0; border-radius:10px; padding:12px 14px;">
          <i class="fas fa-info-circle" style="color:#16a34a; font-size:1rem; margin-top:2px;"></i>
          <p style="font-size:0.8rem; color:#166534; margin:0; line-height:1.45;">
            Setelah diverifikasi, donasi akan berstatus <strong>Terverifikasi & Selesai</strong> dan tercatat ke total perolehan program.
          </p>
        </div>
      </div>

      <div class="modal-footer" style="padding:16px 24px; background:#f8fafc; border-top:1px solid #f1f5f9;">
        <button type="button" class="btn-outline-lihat" style="border-color:#cbd5e1; color:#64748b; padding:9px 18px;" onclick="closeModal('confirmVerifModal')">
          Batal
        </button>
        <button type="submit" class="btn-action-verif" style="background:#059669; border-color:#059669; width:auto; padding:9px 24px; margin:0; display:inline-flex; align-items:center; gap:8px;">
          <i class="fas fa-check"></i> Ya, Verifikasi Donasi
        </button>
      </div>
    </form>
  </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════
     MODAL TOLAK DONASI
════════════════════════════════════════════════════════════════════ -->
<div class="modal-overlay" id="rejectModal" onclick="closeOnOutside(event, 'rejectModal')">
  <div class="modal-card" style="max-width:480px;">
    <div class="modal-header" style="border-bottom: 1px solid #f1f5f9; padding: 18px 24px;">
      <div style="display:flex; align-items:center; gap:12px;">
        <div style="width:40px; height:40px; border-radius:50%; background:#fee2e2; display:flex; align-items:center; justify-content:center; color:#dc2626; font-size:1.2rem;">
          <i class="fas fa-times-circle"></i>
        </div>
        <div>
          <h3 style="font-size:1.1rem; color:#dc2626; margin:0;">Tolak Pembayaran Donasi</h3>
          <p style="font-size:0.78rem; color:#64748b; margin:2px 0 0 0;">Berikan alasan penolakan bukti donasi</p>
        </div>
      </div>
      <button class="modal-close-btn" onclick="closeModal('rejectModal')">&times;</button>
    </div>

    <form method="POST" action="verifikasi_donasi.php?tab=<?= $tab ?>">
      <input type="hidden" name="aksi" value="tolak">
      <input type="hidden" name="id_pembayaran" id="rejIdPembayaran" value="">
      <input type="hidden" name="id_donasi" id="rejIdDonasi" value="">

      <div class="modal-body" style="padding: 22px 24px;">
        <!-- Detail Box -->
        <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:14px; margin-bottom:16px;">
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
            <span style="font-size:0.82rem; color:#64748b;">Nama Donatur</span>
            <strong id="rejDonorName" style="font-size:0.9rem; color:#0f172a;">-</strong>
          </div>
          <div style="display:flex; justify-content:space-between; align-items:center;">
            <span style="font-size:0.82rem; color:#64748b;">Nominal Donasi</span>
            <strong id="rejNominal" style="font-size:0.95rem; color:#dc2626; font-weight:700;">-</strong>
          </div>
        </div>

        <label for="catatanAdmin" style="display:block;font-size:0.84rem;font-weight:600;margin-bottom:6px;color:#334155;">
          Alasan Penolakan <span style="color:#ef4444;">*</span>
        </label>
        <textarea 
          name="catatan_admin" 
          id="catatanAdmin" 
          class="form-textarea" 
          placeholder="Contoh: Bukti transfer buram, nominal tidak sesuai, atau rekening tidak valid..."
          required></textarea>
      </div>

      <div class="modal-footer" style="padding:16px 24px; background:#f8fafc; border-top:1px solid #f1f5f9;">
        <button type="button" class="btn-outline-lihat" style="border-color:#cbd5e1;color:#64748b; padding:9px 18px;" onclick="closeModal('rejectModal')">Batal</button>
        <button type="submit" class="btn-action-verif" style="background:#dc2626;border-color:#dc2626;width:auto;padding:9px 24px;margin:0; display:inline-flex; align-items:center; gap:8px;">
          <i class="fas fa-times"></i> Tolak Donasi
        </button>
      </div>
    </form>
  </div>
</div>

<!-- ── JAVASCRIPT LOGIC ─────────────────────────────────────────── -->
<script>
function openModal(id) {
  document.getElementById(id).classList.add('show');
}
function closeModal(id) {
  document.getElementById(id).classList.remove('show');
}
function closeOnOutside(e, id) {
  if (e.target.id === id) {
    closeModal(id);
  }
}

function openProofModal(data) {
  document.getElementById('mDonorName').textContent = data.nama;
  document.getElementById('mProgramName').textContent = data.program;
  document.getElementById('mNominal').textContent = data.nominal;
  document.getElementById('mDateMethod').textContent = data.tanggal + ' (' + data.metode + ')';
  document.getElementById('mProofImg').src = data.gambar;

  const btnContainer = document.getElementById('mActionButtons');
  btnContainer.innerHTML = '';

  if (data.status === 'menunggu') {
    btnContainer.innerHTML = `
      <button type="button" class="btn-action-tolak" style="width:auto;padding:8px 18px;margin-bottom:0;" onclick="closeModal('proofModal'); openRejectModal(${data.id_pembayaran}, ${data.id_donasi}, '${data.nama}', '${data.nominal}', '${data.program}')">
        Tolak Donasi
      </button>
      <button type="button" class="btn-action-verif" style="width:auto;padding:8px 22px;margin-bottom:0;background:#059669;border-color:#059669;display:inline-flex;align-items:center;gap:6px;" onclick="closeModal('proofModal'); openVerifModal(${data.id_pembayaran}, ${data.id_donasi}, '${data.nama}', '${data.nominal}', '${data.program}')">
        <i class="fas fa-check"></i> Verifikasi Sekarang
      </button>
    `;
  }

  openModal('proofModal');
}

function openVerifModal(idPembayaran, idDonasi, namaDonatur, nominal, namaProgram) {
  document.getElementById('verifIdPembayaran').value = idPembayaran;
  document.getElementById('verifIdDonasi').value = idDonasi;
  document.getElementById('verifDonorName').textContent = namaDonatur;
  document.getElementById('verifNominal').textContent = nominal;
  document.getElementById('verifProgramName').textContent = namaProgram || '-';
  openModal('confirmVerifModal');
}

function openRejectModal(idPembayaran, idDonasi, namaDonatur, nominal, namaProgram) {
  document.getElementById('rejIdPembayaran').value = idPembayaran;
  document.getElementById('rejIdDonasi').value = idDonasi;
  document.getElementById('rejDonorName').textContent = namaDonatur;
  if (document.getElementById('rejNominal')) document.getElementById('rejNominal').textContent = nominal || '-';
  document.getElementById('catatanAdmin').value = '';
  openModal('rejectModal');
}

// Client-side quick search
document.getElementById('searchInput').addEventListener('input', function() {
  const q = this.value.toLowerCase().trim();
  const rows = document.querySelectorAll('#verifTable tbody tr');

  rows.forEach(row => {
    const nama = row.getAttribute('data-nama') || '';
    const prog = row.getAttribute('data-prog') || '';
    if (nama.includes(q) || prog.includes(q)) {
      row.style.display = '';
    } else {
      row.style.display = 'none';
    }
  });
});

// Mobile Sidebar Toggle
const mobileBtn = document.getElementById('mobileToggle');
const sidebar = document.getElementById('sidebar');
if (mobileBtn) {
  mobileBtn.addEventListener('click', () => {
    sidebar.classList.toggle('open');
  });
}
</script>

</body>
</html>
