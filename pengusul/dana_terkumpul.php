<?php
session_start();
require_once __DIR__ . '/../assets/config/database.php';

if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'pengusul') {
    header('Location: /Donasi/auth/login.php');
    exit;
}

$idUser = (int) $_SESSION['id_user'];

// ── Profil Pengusul ─────────────────────────────────────────────────────
try {
    $stmtUser = $pdo->prepare("SELECT id_user, nama, email, jenis_organisasi, nama_organisasi FROM users WHERE id_user = ? LIMIT 1");
    $stmtUser->execute([$idUser]);
    $currentUser = $stmtUser->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $currentUser = [];
}
$namaPengusul = $currentUser['nama'] ?? $_SESSION['nama'] ?? 'Pengusul';

// ── Filter Periode ─────────────────────────────────────────────────────
$now          = new DateTime();
$periodeStart = trim($_GET['periode_start'] ?? $now->format('Y-09-01'));
$periodeEnd   = trim($_GET['periode_end']   ?? $now->format('Y-09-30'));

$dtStart = DateTime::createFromFormat('Y-m-d', $periodeStart) ?: new DateTime($now->format('Y-m-01'));
$dtEnd   = DateTime::createFromFormat('Y-m-d', $periodeEnd)   ?: new DateTime($now->format('Y-m-t'));

$periodeLabel = $dtStart->format('j M Y') . ' - ' . $dtEnd->format('j M Y');

// ── Hitung 4 Kartu Statistik ───────────────────────────────────────────
try {
    // Total Dana & Total Transaksi Donasi Berhasil
    $stmtStats = $pdo->prepare("
        SELECT 
            COALESCE(SUM(d.nominal), 0) AS total_dana,
            COUNT(d.id_donasi) AS total_trx
        FROM donasi d
        JOIN program_donasi p ON d.id_program = p.id_program
        WHERE p.id_pengusul = ? AND d.status_donasi = 'berhasil'
    ");
    $stmtStats->execute([$idUser]);
    $statData = $stmtStats->fetch(PDO::FETCH_ASSOC);

    $totalDana      = (float)($statData['total_dana'] ?? 0);
    $totalTransaksi = (int)($statData['total_trx'] ?? 0);

    // Hitung Program Aktif & Program Selesai
    $stmtProgCount = $pdo->prepare("
        SELECT 
            SUM(CASE WHEN status_program IN ('disetujui', 'berlangsung', 'aktif') THEN 1 ELSE 0 END) AS cnt_aktif,
            SUM(CASE WHEN status_program = 'selesai' THEN 1 ELSE 0 END) AS cnt_selesai
        FROM program_donasi
        WHERE id_pengusul = ?
    ");
    $stmtProgCount->execute([$idUser]);
    $progStats = $stmtProgCount->fetch(PDO::FETCH_ASSOC);

    $cntAktif   = (int)($progStats['cnt_aktif'] ?? 0);
    $cntSelesai = (int)($progStats['cnt_selesai'] ?? 0);
} catch (PDOException $e) {
    $totalDana      = 0;
    $totalTransaksi = 0;
    $cntAktif       = 0;
    $cntSelesai     = 0;
}

// ── Query Riwayat Donasi (Tabel Utama) ──────────────────────────────────
$searchQ   = trim($_GET['q'] ?? '');
$filterTgl = trim($_GET['tgl'] ?? '');
$page      = max(1, (int)($_GET['page'] ?? 1));
$perPage   = 5;

try {
    $whereSql = "WHERE p.id_pengusul = ?";
    $params = [$idUser];

    if ($searchQ !== '') {
        $whereSql .= " AND (d.nama_donatur LIKE ? OR p.judul LIKE ?)";
        $params[] = "%$searchQ%";
        $params[] = "%$searchQ%";
    }

    if ($filterTgl !== '') {
        $whereSql .= " AND DATE(d.tanggal_donasi) = ?";
        $params[] = $filterTgl;
    }

    // Hitung total row
    $stmtCount = $pdo->prepare("
        SELECT COUNT(d.id_donasi)
        FROM donasi d
        JOIN program_donasi p ON d.id_program = p.id_program
        $whereSql
    ");
    $stmtCount->execute($params);
    $totalRows = (int)$stmtCount->fetchColumn();

    $totalPages = max(1, (int)ceil($totalRows / $perPage));
    $page = min($page, $totalPages);
    $offset = ($page - 1) * $perPage;

    // Ambil data halaman ini
    $sqlList = "
        SELECT 
            d.id_donasi,
            d.nama_donatur,
            d.nominal,
            d.status_donasi,
            d.tanggal_donasi,
            p.id_program,
            p.judul AS nama_program,
            p.foto_program,
            COALESCE(pb.metode_pembayaran, 'Transfer Bank') AS metode_pembayaran
        FROM donasi d
        JOIN program_donasi p ON d.id_program = p.id_program
        LEFT JOIN pembayaran pb ON d.id_donasi = pb.id_donasi
        $whereSql
        ORDER BY d.tanggal_donasi DESC, d.id_donasi DESC
        LIMIT $perPage OFFSET $offset
    ";
    $stmtList = $pdo->prepare($sqlList);
    $stmtList->execute($params);
    $riwayatDonasi = $stmtList->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $riwayatDonasi = [];
    $totalRows = 0;
    $totalPages = 1;
    $offset = 0;
}

// ── Helpers ─────────────────────────────────────────────────────────────
function rp($n) { 
    return 'Rp ' . number_format((float)$n, 0, ',', '.'); 
}

function imgPath($foto): string {
    if (empty($foto)) return '/Donasi/assets/img/default_program.jpg';
    $foto = trim($foto);
    if (str_starts_with($foto, '/'))       return $foto;
    if (str_starts_with($foto, 'assets/')) return '/Donasi/' . $foto;
    return '/Donasi/assets/img/' . $foto;
}

function formatMetode($metode): string {
    $m = strtolower(trim((string)$metode));
    if ($m === 'bank' || $m === 'transfer bank') return 'Transfer Bank';
    if ($m === 'e-wallet') return 'E-Wallet';
    if ($m === 'qris') return 'QRIS';
    return ucfirst($m);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dana Terkumpul – SIBERASA</title>
  <meta name="description" content="Pantau seluruh dana yang berhasil dihimpun dari program penggalangan dana Anda di SIBERASA.">

  <!-- Google Font: Poppins -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

  <!-- Font Awesome Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <style>
    *, *::before, *::after {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    :root {
      --primary:       #0284c7;
      --primary-light: #e0f2fe;
      --primary-hover: #0369a1;
      --text-main:     #1e293b;
      --text-muted:    #64748b;
      --text-light:    #94a3b8;
      --bg-body:       #f8fafc;
      --border-color:  #e2e8f0;
      --sidebar-width: 260px;
      --header-height: 64px;
      --radius-sm:     8px;
      --radius-md:     12px;
      --radius-lg:     16px;
      --transition:    all 0.25s ease;
    }

    html, body {
      height: 100%;
      width: 100%;
      overflow: hidden;
      background: var(--bg-body);
      font-family: 'Poppins', -apple-system, BlinkMacSystemFont, sans-serif;
      color: var(--text-main);
    }

    a {
      text-decoration: none;
      color: inherit;
    }

    /* ── Layout ── */
    .dashboard-container {
      display: flex;
      width: 100vw;
      height: 100vh;
      overflow: hidden;
    }

    /* ── Sidebar ── */
    .app-sidebar {
      width: var(--sidebar-width);
      min-width: var(--sidebar-width);
      max-width: var(--sidebar-width);
      height: 100vh;
      background: #ffffff;
      border-right: 1px solid var(--border-color);
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      padding: 20px 18px;
      overflow-y: auto;
      overflow-x: hidden;
      flex-shrink: 0;
      z-index: 100;
      transition: transform 0.3s cubic-bezier(0.4,0,0.2,1);
    }

    .sidebar-brand {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 22px;
      padding: 0 4px;
    }

    .brand-logo img {
      height: 48px;
      width: auto;
      max-width: 180px;
      object-fit: contain;
      filter: drop-shadow(0 2px 6px rgba(0,0,0,.08));
    }

    .sidebar-toggle-btn {
      background: none;
      border: none;
      color: var(--primary);
      font-size: 1.35rem;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 6px;
      border-radius: var(--radius-sm);
      transition: var(--transition);
    }
    .sidebar-toggle-btn:hover { background: var(--primary-light); }

    .user-profile-badge {
      display: flex;
      align-items: center;
      gap: 12px;
      background: #ffffff;
      border: 1px solid var(--border-color);
      border-radius: var(--radius-md);
      padding: 12px 14px;
      margin-bottom: 20px;
    }

    .user-profile-avatar {
      width: 42px;
      height: 42px;
      border-radius: 50%;
      background: var(--primary);
      color: #ffffff;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.6rem;
      flex-shrink: 0;
    }

    .user-profile-info { display: flex; flex-direction: column; overflow: hidden; }
    .user-profile-role { font-size: .95rem; font-weight: 700; color: var(--text-main); line-height: 1.2; }
    .user-profile-name { font-size: .8rem; color: var(--text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-top: 2px; }

    .nav-list { list-style: none; display: flex; flex-direction: column; gap: 4px; }
    .nav-item a {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 11px 16px;
      border-radius: 10px;
      font-size: .875rem;
      font-weight: 500;
      color: var(--text-muted);
      transition: var(--transition);
      white-space: nowrap;
    }
    .nav-item a i { font-size: 1.05rem; width: 20px; text-align: center; color: #64748b; transition: var(--transition); }
    .nav-item a:hover { background: #f1f5f9; color: var(--text-main); }
    .nav-item a:hover i { color: var(--primary); }
    .nav-item.active a { background: var(--primary-light); color: var(--primary); font-weight: 600; }
    .nav-item.active a i { color: var(--primary); }

    .sidebar-footer { padding-top: 16px; border-top: 1px solid var(--border-color); margin-top: 16px; }
    .logout-link {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 10px 14px;
      border-radius: var(--radius-sm);
      font-size: .88rem;
      font-weight: 500;
      color: var(--text-muted);
      transition: var(--transition);
    }
    .logout-link:hover { background: #fee2e2; color: #dc2626; }

    /* ── Main Wrapper ── */
    .main-wrapper {
      flex: 1;
      min-width: 0;
      height: 100vh;
      display: flex;
      flex-direction: column;
      overflow: hidden;
      background: var(--bg-body);
    }

    .top-header {
      height: var(--header-height);
      background: #ffffff;
      border-bottom: 1px solid var(--border-color);
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 32px;
      flex-shrink: 0;
    }

    .mobile-toggle-btn {
      display: none;
      background: none;
      border: none;
      color: var(--primary);
      font-size: 1.35rem;
      cursor: pointer;
      padding: 6px;
      border-radius: var(--radius-sm);
    }

    .header-right { display: flex; align-items: center; gap: 16px; margin-left: auto; }
    .notif-btn {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background: #ffffff;
      border: 1px solid var(--border-color);
      display: flex;
      align-items: center;
      justify-content: center;
      color: #334155;
      font-size: 1.15rem;
      position: relative;
      cursor: pointer;
      transition: var(--transition);
    }
    .notif-btn:hover { background: var(--primary-light); color: var(--primary); border-color: #bae6fd; }
    .notif-badge {
      position: absolute;
      top: 7px;
      right: 7px;
      width: 8px;
      height: 8px;
      background: #ef4444;
      border-radius: 50%;
      border: 2px solid #fff;
    }

    /* ── Scrollable Page Content ── */
    .page-scroll-content {
      flex: 1;
      min-width: 0;
      overflow-y: auto;
      overflow-x: hidden;
      padding: 24px 32px 60px;
    }

    /* ── Header Title & Filter ── */
    .page-header-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      margin-bottom: 22px;
      flex-wrap: wrap;
    }

    .title-left {
      display: flex;
      align-items: center;
      gap: 14px;
    }

    .title-icon-box {
      width: 46px;
      height: 46px;
      background: #0284c7;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #ffffff;
      font-size: 1.25rem;
      box-shadow: 0 4px 10px rgba(2, 132, 199, 0.25);
      flex-shrink: 0;
    }

    .page-title {
      font-size: 1.55rem;
      font-weight: 800;
      color: #1e3a8a;
      line-height: 1.2;
    }

    .page-subtitle {
      font-size: 0.85rem;
      color: var(--text-muted);
      margin-top: 3px;
    }

    /* Filter Periode Box */
    .periode-box {
      display: inline-flex;
      align-items: center;
      gap: 12px;
      background: #ffffff;
      border: 1px solid #dde3ea;
      border-radius: 12px;
      padding: 10px 18px;
      font-size: 0.86rem;
      font-weight: 600;
      color: #334155;
      cursor: pointer;
      position: relative;
      user-select: none;
      box-shadow: 0 1px 2px rgba(0,0,0,.03);
    }
    .periode-box i.cal-icon { color: #64748b; font-size: 0.95rem; }
    .periode-box i.chev-icon { color: #64748b; font-size: 0.75rem; margin-left: 6px; }

    /* ── Top 4 Stat Cards ── */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 18px;
      margin-bottom: 24px;
    }

    .stat-card {
      background: #ffffff;
      border: 1px solid var(--border-color);
      border-radius: 16px;
      padding: 20px 22px;
      display: flex;
      align-items: center;
      gap: 16px;
      box-shadow: 0 1px 3px rgba(0,0,0,.02);
      transition: var(--transition);
    }
    .stat-card:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(0,0,0,.04); }

    /* Leftmost Blue Hero Card */
    .stat-card.blue-hero {
      background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
      border: none;
      color: #ffffff;
      box-shadow: 0 6px 18px rgba(2, 132, 199, 0.3);
    }

    .stat-card.blue-hero .stat-icon-wrap {
      background: rgba(255, 255, 255, 0.2);
      color: #ffffff;
    }

    .stat-card.blue-hero .stat-label { color: rgba(255, 255, 255, 0.85); }
    .stat-card.blue-hero .stat-value { color: #ffffff; }
    .stat-card.blue-hero .stat-desc { color: rgba(255, 255, 255, 0.8); font-size: 0.75rem; }

    /* White Stat Cards */
    .stat-icon-wrap {
      width: 48px;
      height: 48px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.25rem;
      flex-shrink: 0;
    }

    .stat-icon-wrap.user-blue   { background: #e0f2fe; color: #0284c7; }
    .stat-icon-wrap.prog-purple { background: #f3e8ff; color: #9333ea; }
    .stat-icon-wrap.prog-orange { background: #ffedd5; color: #f97316; }

    .stat-content { display: flex; flex-direction: column; }
    .stat-label { font-size: 0.8rem; font-weight: 500; color: #64748b; margin-bottom: 2px; }
    .stat-value { font-size: 1.55rem; font-weight: 800; color: #0f172a; line-height: 1.2; }
    .stat-desc { font-size: 0.76rem; color: #94a3b8; margin-top: 3px; }

    /* ── Main Riwayat Donasi Card Box (Full Width) ── */
    .riwayat-card-box {
      background: #ffffff;
      border: 1px solid var(--border-color);
      border-radius: 16px;
      padding: 24px;
      box-shadow: 0 1px 3px rgba(0,0,0,.02);
    }

    .riwayat-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      margin-bottom: 22px;
      flex-wrap: wrap;
    }

    .riwayat-title-wrap {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .riwayat-icon-box {
      width: 42px;
      height: 42px;
      background: #0284c7;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #ffffff;
      font-size: 1.15rem;
      flex-shrink: 0;
    }

    .riwayat-title {
      font-size: 1.15rem;
      font-weight: 800;
      color: #1e3a8a;
      line-height: 1.2;
    }

    .riwayat-subtitle {
      font-size: 0.82rem;
      color: var(--text-muted);
      margin-top: 2px;
    }

    /* Controls (Search & Filter Tanggal) */
    .riwayat-controls {
      display: flex;
      align-items: center;
      gap: 12px;
      flex-wrap: wrap;
    }

    .search-box-wrap {
      position: relative;
      width: 270px;
    }

    .search-box-wrap i {
      position: absolute;
      left: 14px;
      top: 50%;
      transform: translateY(-50%);
      color: #94a3b8;
      font-size: 0.9rem;
    }

    .search-box-wrap input {
      width: 100%;
      height: 40px;
      padding: 0 14px 0 38px;
      border: 1px solid #dde3ea;
      border-radius: 10px;
      font-size: 0.82rem;
      font-family: inherit;
      color: var(--text-main);
      background: #ffffff;
      transition: var(--transition);
    }
    .search-box-wrap input:focus {
      outline: none;
      border-color: #38bdf8;
      box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.15);
    }

    .btn-filter-tanggal {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      height: 40px;
      padding: 0 16px;
      border: 1px solid #dde3ea;
      border-radius: 10px;
      background: #ffffff;
      color: #64748b;
      font-size: 0.82rem;
      font-weight: 500;
      font-family: inherit;
      cursor: pointer;
      transition: var(--transition);
    }
    .btn-filter-tanggal:hover {
      border-color: #94a3b8;
      color: var(--text-main);
    }
    .btn-filter-tanggal i.chev-icon {
      font-size: 0.72rem;
      color: #94a3b8;
    }

    /* ── Table Styling ── */
    .table-container {
      width: 100%;
      overflow-x: auto;
    }

    .riwayat-table {
      width: 100%;
      border-collapse: separate;
      border-spacing: 0;
      text-align: left;
    }

    /* Table Header: Light Blue Background */
    .riwayat-table thead th {
      background-color: #edf5fd;
      color: #485c74;
      font-size: 0.84rem;
      font-weight: 700;
      padding: 14px 16px;
      white-space: nowrap;
      border: none;
    }

    .riwayat-table thead th:first-child {
      border-top-left-radius: 10px;
      border-bottom-left-radius: 10px;
      width: 50px;
    }

    .riwayat-table thead th:last-child {
      border-top-right-radius: 10px;
      border-bottom-right-radius: 10px;
      text-align: center;
      width: 140px;
    }

    /* Table Body */
    .riwayat-table tbody td {
      padding: 18px 16px;
      font-size: 0.86rem;
      color: #334155;
      border-bottom: 1px solid #f1f5f9;
      vertical-align: middle;
      line-height: 1.4;
    }

    .riwayat-table tbody tr:last-child td {
      border-bottom: none;
    }

    .riwayat-table tbody tr:hover td {
      background-color: #fafbfc;
    }

    .col-no {
      font-weight: 600;
      color: #64748b;
    }

    .col-tgl {
      color: #475569;
      white-space: nowrap;
    }

    .col-donatur {
      font-weight: 600;
      color: #1e293b;
      white-space: nowrap;
    }

    /* Program Cell (Thumb + Judul) */
    .program-info-cell {
      display: flex;
      align-items: center;
      gap: 12px;
      min-width: 200px;
    }

    .prog-thumb {
      width: 44px;
      height: 38px;
      border-radius: 8px;
      object-fit: cover;
      background: #e2e8f0;
      flex-shrink: 0;
    }

    .prog-title {
      font-weight: 600;
      color: #1e40af;
      font-size: 0.84rem;
      line-height: 1.3;
    }

    .col-nominal {
      font-weight: 600;
      color: #334155;
      white-space: nowrap;
    }

    .col-metode {
      color: #475569;
      white-space: nowrap;
    }

    /* Status Badges */
    .badge-status {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 5px 12px;
      border-radius: 999px;
      font-size: 0.74rem;
      font-weight: 600;
      white-space: nowrap;
    }
    .badge-status::before {
      content: '';
      width: 6px;
      height: 6px;
      border-radius: 50%;
    }

    .badge-terverifikasi {
      background: #dcfce7;
      color: #15803d;
    }
    .badge-terverifikasi::before {
      background: #15803d;
    }

    .badge-menunggu {
      background: #fef3c7;
      color: #b45309;
    }
    .badge-menunggu::before {
      background: #b45309;
    }

    .badge-ditolak {
      background: #fee2e2;
      color: #b91c1c;
    }
    .badge-ditolak::before {
      background: #b91c1c;
    }

    /* Actions */
    .action-cell {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      justify-content: center;
      width: 100%;
    }

    .btn-lihat-detail {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      padding: 6px 14px;
      border: 1px solid #bfdbfe;
      border-radius: 8px;
      background: #ffffff;
      color: #0284c7;
      font-size: 0.78rem;
      font-weight: 600;
      cursor: pointer;
      transition: var(--transition);
      white-space: nowrap;
    }
    .btn-lihat-detail:hover {
      background: var(--primary-light);
      border-color: var(--primary);
    }

    .btn-more-icon {
      background: none;
      border: none;
      color: #94a3b8;
      font-size: 0.95rem;
      cursor: pointer;
      padding: 4px;
      transition: color 0.15s ease;
    }
    .btn-more-icon:hover {
      color: #334155;
    }

    /* Empty state */
    .empty-state {
      text-align: center;
      padding: 48px 16px;
      color: #94a3b8;
    }
    .empty-state i {
      font-size: 2.8rem;
      color: #cbd5e1;
      margin-bottom: 12px;
      display: block;
    }
    .empty-state p {
      font-size: 0.88rem;
    }

    /* ── Table Footer & Pagination ── */
    .table-footer-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-top: 20px;
      flex-wrap: wrap;
      gap: 12px;
      font-size: 0.8rem;
      color: #64748b;
    }

    .pagination-wrap {
      display: flex;
      align-items: center;
      gap: 4px;
    }

    .pg-btn {
      min-width: 30px;
      height: 30px;
      padding: 0 6px;
      border-radius: 8px;
      border: 1px solid #dde3ea;
      background: #ffffff;
      color: #64748b;
      font-size: 0.78rem;
      font-weight: 600;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: var(--transition);
    }
    .pg-btn:hover {
      border-color: #94a3b8;
      color: var(--text-main);
    }
    .pg-btn.active {
      background: #0284c7;
      color: #ffffff;
      border-color: #0284c7;
    }
    .pg-btn.disabled {
      opacity: 0.4;
      pointer-events: none;
    }

    /* ── Mobile Sidebar Overlay ── */
    .sidebar-overlay {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(15, 23, 42, 0.45);
      backdrop-filter: blur(2px);
      z-index: 95;
    }
    .sidebar-overlay.show { display: block; }

    /* ── Responsive ── */
    @media (max-width: 1100px) {
      .stats-grid { grid-template-columns: repeat(2, 1fr); }
    }

    @media (max-width: 768px) {
      .app-sidebar {
        position: fixed;
        top: 0; left: 0; bottom: 0;
        transform: translateX(-100%);
      }
      .app-sidebar.show { transform: translateX(0); }
      .mobile-toggle-btn { display: flex; }
      .top-header { padding: 0 20px; }
      .page-scroll-content { padding: 20px 16px 40px; }
      .stats-grid { grid-template-columns: 1fr; }
      .riwayat-header { flex-direction: column; align-items: stretch; }
      .riwayat-controls { width: 100%; }
      .search-box-wrap { width: 100%; }
      .btn-filter-tanggal { width: 100%; justify-content: space-between; }
    }
  </style>
</head>
<body>

  <!-- Overlay Mobile -->
  <div class="sidebar-overlay" id="sidebarOverlay"></div>

  <div class="dashboard-container">

    <!-- ── Sidebar ── -->
    <aside class="app-sidebar" id="appSidebar">
      <div>
        <div class="sidebar-brand">
          <a href="/Donasi/pengusul/dashboard.php" class="brand-logo">
            <img src="/logo%20donasi.png" alt="SIBERASA Logo" onerror="this.src='../assets/img/logo_donasi.png'">
          </a>
          <button id="sidebarToggle" class="sidebar-toggle-btn" title="Toggle Menu">
            <i class="fa-solid fa-bars"></i>
          </button>
        </div>

        <div class="user-profile-badge">
          <div class="user-profile-avatar">
            <i class="fa-solid fa-circle-user"></i>
          </div>
          <div class="user-profile-info">
            <span class="user-profile-role">Pengusul</span>
            <span class="user-profile-name"><?= htmlspecialchars($namaPengusul) ?></span>
          </div>
        </div>

        <ul class="nav-list">
          <li class="nav-item">
            <a href="/Donasi/pengusul/dashboard.php">
              <i class="fa-solid fa-table-cells-large"></i><span>Dashboard</span>
            </a>
          </li>
          <li class="nav-item">
            <a href="/Donasi/pengusul/ajukan_program.php">
              <i class="fa-regular fa-id-badge"></i><span>Ajukan Program Donasi</span>
            </a>
          </li>
          <li class="nav-item">
            <a href="/Donasi/pengusul/status_pengajuan.php">
              <i class="fa-regular fa-rectangle-list"></i><span>Status Pengajuan</span>
            </a>
          </li>
          <li class="nav-item active">
            <a href="/Donasi/pengusul/dana_terkumpul.php">
              <i class="fa-solid fa-building-columns"></i><span>Dana Terkumpul</span>
            </a>
          </li>
          <li class="nav-item">
            <a href="/Donasi/pengusul/data_penggalangan.php">
              <i class="fa-solid fa-hand-holding-dollar"></i><span>Data Penggalangan Dana</span>
            </a>
          </li>
          <li class="nav-item">
            <a href="/Donasi/pengusul/laporan_donasi.php">
              <i class="fa-solid fa-file-invoice-dollar"></i><span>Laporan Donasi</span>
            </a>
          </li>
        </ul>
      </div>

      <div class="sidebar-footer">
        <a href="/Donasi/auth/logout.php" class="logout-link">
          <i class="fa-solid fa-arrow-right-from-bracket"></i><span>Logout</span>
        </a>
      </div>
    </aside>

    <!-- ── Main Area ── -->
    <div class="main-wrapper">

      <header class="top-header">
        <button id="mobileSidebarToggle" class="mobile-toggle-btn" title="Buka Menu">
          <i class="fa-solid fa-bars"></i>
        </button>
        <div class="header-right">
          <div class="notif-btn" title="Pemberitahuan">
            <i class="fa-regular fa-bell"></i>
            <span class="notif-badge"></span>
          </div>
        </div>
      </header>

      <main class="page-scroll-content">

        <!-- ── Page Header ── -->
        <div class="page-header-row">
          <div class="title-left">
            <div class="title-icon-box">
              <i class="fa-solid fa-wallet"></i>
            </div>
            <div>
              <h1 class="page-title">Dana Terkumpul</h1>
              <p class="page-subtitle">Pantau seluruh dana yang berhasil dihimpun dari program penggalangan dana Anda.</p>
            </div>
          </div>

          <div class="periode-box">
            <i class="fa-regular fa-calendar cal-icon"></i>
            <span><?= htmlspecialchars($periodeLabel) ?></span>
            <i class="fa-solid fa-chevron-down chev-icon"></i>
          </div>
        </div>

        <!-- ── Top 4 Stat Cards ── -->
        <div class="stats-grid">
          
          <!-- Card 1: Total Dana Terkumpul (Blue Hero Card) -->
          <div class="stat-card blue-hero">
            <div class="stat-icon-wrap">
              <i class="fa-solid fa-wallet"></i>
            </div>
            <div class="stat-content">
              <span class="stat-label">Total Dana Terkumpul</span>
              <span class="stat-value"><?= rp($totalDana) ?></span>
              <span class="stat-desc">↑ 12% dari periode sebelumnya</span>
            </div>
          </div>

          <!-- Card 2: Total Donasi -->
          <div class="stat-card">
            <div class="stat-icon-wrap user-blue">
              <i class="fa-solid fa-users"></i>
            </div>
            <div class="stat-content">
              <span class="stat-label">Total Donasi</span>
              <span class="stat-value"><?= number_format($totalTransaksi) ?></span>
              <span class="stat-desc">transaksi</span>
            </div>
          </div>

          <!-- Card 3: Program Aktif -->
          <div class="stat-card">
            <div class="stat-icon-wrap prog-purple">
              <i class="fa-solid fa-user-group"></i>
            </div>
            <div class="stat-content">
              <span class="stat-label">Program Aktif</span>
              <span class="stat-value"><?= number_format($cntAktif) ?></span>
              <span class="stat-desc">program</span>
            </div>
          </div>

          <!-- Card 4: Program Selesai -->
          <div class="stat-card">
            <div class="stat-icon-wrap prog-orange">
              <i class="fa-solid fa-circle-check"></i>
            </div>
            <div class="stat-content">
              <span class="stat-label">Program Selesai</span>
              <span class="stat-value"><?= number_format($cntSelesai) ?></span>
              <span class="stat-desc">program</span>
            </div>
          </div>

        </div>

        <!-- ── Riwayat Donasi Card Box (Full Width) ── -->
        <div class="riwayat-card-box">
          
          <!-- Header Riwayat Donasi -->
          <div class="riwayat-header">
            <div class="riwayat-title-wrap">
              <div class="riwayat-icon-box">
                <i class="fa-regular fa-clock"></i>
              </div>
              <div>
                <h2 class="riwayat-title">Riwayat Donasi</h2>
                <p class="riwayat-subtitle">Menampilkan transaksi donasi yang masuk ke program milik Anda.</p>
              </div>
            </div>

            <!-- Toolbar Search & Filter Tanggal -->
            <form method="GET" action="" class="riwayat-controls" id="filterForm">
              <div class="search-box-wrap">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" name="q" id="searchInput" placeholder="Cari nama donatur / program..." value="<?= htmlspecialchars($searchQ) ?>">
              </div>

              <div style="position:relative;">
                <button type="button" class="btn-filter-tanggal" id="btnFilterTgl">
                  <span><?= !empty($filterTgl) ? date('d M Y', strtotime($filterTgl)) : 'Filter Tanggal' ?></span>
                  <i class="fa-solid fa-chevron-down chev-icon"></i>
                </button>
                <input type="date" name="tgl" id="hiddenDateInput" value="<?= htmlspecialchars($filterTgl) ?>" style="display:none;" onchange="document.getElementById('filterForm').submit()">
              </div>
            </form>
          </div>

          <!-- Table Content -->
          <div class="table-container">
            <table class="riwayat-table" id="riwayatTable">
              <thead>
                <tr>
                  <th>No</th>
                  <th>Tanggal</th>
                  <th>Nama Donatur</th>
                  <th>Program</th>
                  <th>Nominal</th>
                  <th>Metode Pembayaran</th>
                  <th>Status</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($riwayatDonasi)): ?>
                  <tr>
                    <td colspan="8">
                      <div class="empty-state">
                        <i class="fa-regular fa-folder-open"></i>
                        <p>Belum ada data riwayat transaksi donasi yang ditemukan.</p>
                      </div>
                    </td>
                  </tr>
                <?php else: ?>
                  <?php 
                    $noIndex = $offset + 1;
                    foreach ($riwayatDonasi as $row): 
                      $st = strtolower($row['status_donasi']);
                      $tgl = date('d M Y', strtotime($row['tanggal_donasi']));
                      $foto = imgPath($row['foto_program'] ?? '');
                  ?>
                  <tr>
                    <td class="col-no"><?= $noIndex++ ?></td>
                    <td class="col-tgl"><?= $tgl ?></td>
                    <td class="col-donatur"><?= htmlspecialchars($row['nama_donatur'] ?: 'Anonim') ?></td>
                    <td>
                      <div class="program-info-cell">
                        <img src="<?= htmlspecialchars($foto) ?>" class="prog-thumb" onerror="this.src='/Donasi/assets/img/default_program.jpg'">
                        <span class="prog-title"><?= htmlspecialchars($row['nama_program']) ?></span>
                      </div>
                    </td>
                    <td class="col-nominal"><?= rp($row['nominal']) ?></td>
                    <td class="col-metode"><?= htmlspecialchars(formatMetode($row['metode_pembayaran'])) ?></td>
                    <td>
                      <?php if ($st === 'berhasil'): ?>
                        <span class="badge-status badge-terverifikasi">Terverifikasi</span>
                      <?php elseif (in_array($st, ['menunggu_verifikasi', 'menunggu_pembayaran'])): ?>
                        <span class="badge-status badge-menunggu">Menunggu Verifikasi</span>
                      <?php else: ?>
                        <span class="badge-status badge-ditolak"><?= ucfirst($st) ?></span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <div class="action-cell">
                        <a href="/Donasi/pengusul/detail_program.php?id=<?= (int)$row['id_program'] ?>" class="btn-lihat-detail">
                          Lihat Detail
                        </a>
                        <button type="button" class="btn-more-icon" title="Opsi Lainnya">
                          <i class="fa-solid fa-ellipsis-vertical"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>

          <!-- Footer Information & Pagination -->
          <div class="table-footer-row">
            <div>
              <?php if ($totalRows > 0): ?>
                Menampilkan <?= $offset + 1 ?>–<?= min($offset + $perPage, $totalRows) ?> dari <?= $totalRows ?> data
              <?php else: ?>
                Menampilkan 0 data
              <?php endif; ?>
            </div>

            <?php if ($totalPages > 1): ?>
            <div class="pagination-wrap">
              <!-- Prev -->
              <?php 
                $prevUrl = '?' . http_build_query(array_merge($_GET, ['page' => $page - 1]));
              ?>
              <a href="<?= ($page > 1) ? $prevUrl : 'javascript:void(0)' ?>" class="pg-btn <?= ($page <= 1) ? 'disabled' : '' ?>">
                <i class="fa-solid fa-chevron-left" style="font-size:0.65rem;"></i>
              </a>

              <!-- Pages -->
              <?php for ($p = 1; $p <= $totalPages; $p++): 
                $pUrl = '?' . http_build_query(array_merge($_GET, ['page' => $p]));
              ?>
                <a href="<?= $pUrl ?>" class="pg-btn <?= ($p === $page) ? 'active' : '' ?>">
                  <?= $p ?>
                </a>
              <?php endfor; ?>

              <!-- Next -->
              <?php 
                $nextUrl = '?' . http_build_query(array_merge($_GET, ['page' => $page + 1]));
              ?>
              <a href="<?= ($page < $totalPages) ? $nextUrl : 'javascript:void(0)' ?>" class="pg-btn <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                <i class="fa-solid fa-chevron-right" style="font-size:0.65rem;"></i>
              </a>
            </div>
            <?php endif; ?>
          </div>

        </div><!-- /riwayat-card-box -->

      </main>

    </div>

  </div>

  <!-- ── Scripts ── -->
  <script>
    // ── Sidebar Toggle ──
    const sidebar = document.getElementById('appSidebar');
    const toggleBtn = document.getElementById('sidebarToggle');
    const mobileToggleBtn = document.getElementById('mobileSidebarToggle');
    const overlay = document.getElementById('sidebarOverlay');

    function toggleSidebar() {
      sidebar.classList.toggle('show');
      if (overlay) overlay.classList.toggle('show');
    }

    if (toggleBtn) toggleBtn.addEventListener('click', toggleSidebar);
    if (mobileToggleBtn) mobileToggleBtn.addEventListener('click', toggleSidebar);
    if (overlay) overlay.addEventListener('click', toggleSidebar);

    // ── Filter Tanggal Picker ──
    const btnFilterTgl = document.getElementById('btnFilterTgl');
    const hiddenDateInput = document.getElementById('hiddenDateInput');

    if (btnFilterTgl && hiddenDateInput) {
      btnFilterTgl.addEventListener('click', () => {
        if (hiddenDateInput.showPicker) {
          hiddenDateInput.showPicker();
        } else {
          hiddenDateInput.click();
        }
      });
    }

    // ── Search on Enter ──
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
      searchInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
          document.getElementById('filterForm').submit();
        }
      });
    }
  </script>
</body>
</html>
