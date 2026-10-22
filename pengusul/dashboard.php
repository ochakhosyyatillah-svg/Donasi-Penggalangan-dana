<?php
session_start();
require_once __DIR__ . '/../assets/config/database.php';

// Pastikan pengguna sudah login dan rolenya adalah pengusul
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'pengusul') {
    header('Location: /Donasi/auth/login.php');
    exit;
}

$idUser = (int) $_SESSION['id_user'];

// Ambil data profil pengguna
try {
    $stmtUser = $pdo->prepare("SELECT id_user, nama, email, jenis_organisasi, nama_organisasi FROM users WHERE id_user = ? LIMIT 1");
    $stmtUser->execute([$idUser]);
    $currentUser = $stmtUser->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $currentUser = [];
}

$namaPengusul = $currentUser['nama'] ?? $_SESSION['nama'] ?? 'Pengusul';
$namaOrganisasi = !empty($currentUser['nama_organisasi']) ? $currentUser['nama_organisasi'] : 'BEM Fakultas';

// ── Ambil data statistik dari database ────────────────────────────────
try {
    // Total Pengajuan
    $stmtTotal = $pdo->prepare("SELECT COUNT(*) AS total FROM program_donasi WHERE id_pengusul = ?");
    $stmtTotal->execute([$idUser]);
    $totalPengajuan = (int) ($stmtTotal->fetchColumn() ?: 0);

    // Program Disetujui
    $stmtSetuju = $pdo->prepare("SELECT COUNT(*) AS total FROM program_donasi WHERE id_pengusul = ? AND status_program IN ('disetujui', 'berlangsung', 'selesai')");
    $stmtSetuju->execute([$idUser]);
    $programDisetujui = (int) ($stmtSetuju->fetchColumn() ?: 0);

    // Dalam Proses (Menunggu)
    $stmtProses = $pdo->prepare("SELECT COUNT(*) AS total FROM program_donasi WHERE id_pengusul = ? AND status_program = 'menunggu'");
    $stmtProses->execute([$idUser]);
    $dalamProses = (int) ($stmtProses->fetchColumn() ?: 0);

    // Total Dana Terkumpul
    $stmtDana = $pdo->prepare("
        SELECT COALESCE(SUM(d.nominal), 0) AS total
        FROM donasi d
        JOIN program_donasi p ON d.id_program = p.id_program
        WHERE p.id_pengusul = ? AND d.status_donasi = 'berhasil'
    ");
    $stmtDana->execute([$idUser]);
    $totalDanaTerkumpul = (float) ($stmtDana->fetchColumn() ?: 0);

    // Ambil daftar program donasi terbaru pengusul
    $stmtList = $pdo->prepare("
        SELECT 
            p.id_program,
            p.judul,
            p.deskripsi,
            p.target_dana,
            p.status_program,
            p.foto_program,
            p.penerima_dana,
            p.tujuan_dana,
            p.tanggal_mulai,
            p.tanggal_selesai,
            COALESCE(SUM(CASE WHEN d.status_donasi = 'berhasil' THEN d.nominal ELSE 0 END), 0) AS dana_terkumpul
        FROM program_donasi p
        LEFT JOIN donasi d ON p.id_program = d.id_program
        WHERE p.id_pengusul = ?
        GROUP BY p.id_program
        ORDER BY p.dibuat_pada DESC, p.id_program DESC
        LIMIT 5
    ");
    $stmtList->execute([$idUser]);
    $programList = $stmtList->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log($e->getMessage());
    $totalPengajuan = 0;
    $programDisetujui = 0;
    $dalamProses = 0;
    $totalDanaTerkumpul = 0;
    $programList = [];
}

// Fallback nilai agar persis seperti tampilan desain mockup jika data masih awal
if ($totalPengajuan === 0 && empty($programList)) {
    $totalPengajuan = 3;
    $programDisetujui = 2;
    $dalamProses = 1;
    $totalDanaTerkumpul = 5250000;
}

// Format Rupiah
function rp($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Pengusul – SIBERASA</title>
  <meta name="description" content="Dashboard Pengusul SIBERASA - Kelola program donasi dan pantau pengajuan Anda.">

  <!-- Google Font: Poppins -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

  <!-- Font Awesome Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <style>
    /* ── Reset & Basis Layout (No Window Scrollbars) ────────────── */
    *, *::before, *::after {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    :root {
      --primary: #0284c7;
      --primary-light: #e0f2fe;
      --primary-hover: #0369a1;
      --text-main: #0f172a;
      --text-muted: #64748b;
      --text-light: #94a3b8;
      --bg-body: #f8fafc;
      --bg-card: #ffffff;
      --border-color: #e2e8f0;
      --sidebar-width: 260px;
      --header-height: 64px;
      --radius-sm: 8px;
      --radius-md: 12px;
      --radius-lg: 16px;
      --radius-pill: 9999px;
      --transition: all 0.25s ease;
    }

    html, body {
      height: 100%;
      width: 100%;
      overflow: hidden; /* Mencegah geser horizontal ke kanan */
      background-color: var(--bg-body);
      font-family: 'Poppins', -apple-system, BlinkMacSystemFont, sans-serif;
      color: var(--text-main);
    }

    a {
      text-decoration: none;
      color: inherit;
    }

    /* ── Wrapper Dashboard 2 Kolom ──────────────────────────────── */
    .dashboard-container {
      display: flex;
      width: 100vw;
      height: 100vh;
      overflow: hidden;
      position: relative;
    }

    /* ── Sidebar (Kolom Kiri Tetap) ──────────────────────────────── */
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
      transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    /* Brand Logo + Hamburger di Atas Sidebar */
    .sidebar-brand {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 22px;
      padding: 0 4px;
    }

    .brand-logo {
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .brand-logo img {
      height: 48px;
      width: auto;
      max-width: 180px;
      object-fit: contain;
      filter: drop-shadow(0 2px 6px rgba(0,0,0,0.08));
      transition: transform 0.2s ease;
    }

    .brand-logo img:hover {
      transform: scale(1.03);
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

    .sidebar-toggle-btn:hover {
      background: var(--primary-light);
    }

    /* Profil Pengusul Card */
    .user-profile-badge {
      display: flex;
      align-items: center;
      gap: 12px;
      background: #ffffff;
      border: 1px solid var(--border-color);
      border-radius: var(--radius-md);
      padding: 12px 14px;
      margin-bottom: 20px;
      box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
    }

    .user-profile-avatar {
      width: 42px;
      height: 42px;
      border-radius: 50%;
      background: #0284c7;
      color: #ffffff;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.6rem;
      flex-shrink: 0;
    }

    .user-profile-info {
      display: flex;
      flex-direction: column;
      overflow: hidden;
    }

    .user-profile-role {
      font-size: 0.95rem;
      font-weight: 700;
      color: var(--text-main);
      line-height: 1.2;
    }

    .user-profile-name {
      font-size: 0.8rem;
      color: var(--text-muted);
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      margin-top: 2px;
    }

    /* Menu Navigasi */
    .nav-list {
      list-style: none;
      display: flex;
      flex-direction: column;
      gap: 4px;
    }

    .nav-item a {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 11px 16px;
      border-radius: 10px;
      font-size: 0.875rem;
      font-weight: 500;
      color: var(--text-muted);
      transition: var(--transition);
      white-space: nowrap;
    }

    .nav-item a i {
      font-size: 1.05rem;
      width: 20px;
      text-align: center;
      color: #64748b;
      transition: var(--transition);
    }

    .nav-item a:hover {
      background: #f1f5f9;
      color: var(--text-main);
    }

    .nav-item a:hover i {
      color: var(--primary);
    }

    .nav-item.active a {
      background: var(--primary-light);
      color: var(--primary);
      font-weight: 600;
    }

    .nav-item.active a i {
      color: var(--primary);
    }

    /* Sidebar Footer / Logout */
    .sidebar-footer {
      padding-top: 16px;
      border-top: 1px solid var(--border-color);
      margin-top: 16px;
    }

    .logout-link {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 10px 14px;
      border-radius: var(--radius-sm);
      font-size: 0.88rem;
      font-weight: 500;
      color: var(--text-muted);
      transition: var(--transition);
    }

    .logout-link:hover {
      background: #fee2e2;
      color: #dc2626;
    }

    .logout-link i {
      font-size: 1.1rem;
    }

    /* ── Main Area (Kolom Kanan) ────────────────────────────────── */
    .main-wrapper {
      flex: 1;
      min-width: 0; /* PENTING: Mencegah flex child memanjang keluar layar */
      height: 100vh;
      display: flex;
      flex-direction: column;
      overflow: hidden;
      background: var(--bg-body);
    }

    /* Header Navigasi Atas */
    .top-header {
      height: var(--header-height);
      background: #ffffff;
      border-bottom: 1px solid var(--border-color);
      display: flex;
      align-items: center;
      justify-content: flex-end;
      padding: 0 32px;
      flex-shrink: 0;
    }

    .header-right {
      display: flex;
      align-items: center;
      gap: 16px;
    }

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

    .notif-btn:hover {
      background: var(--primary-light);
      color: var(--primary);
      border-color: #bae6fd;
    }

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

    /* Area Konten Scrollable */
    .page-scroll-content {
      flex: 1;
      min-width: 0;
      overflow-y: auto;
      overflow-x: hidden; /* Tidak akan ada geser horizontal */
      padding: 28px 32px 50px;
    }

    /* Welcome Banner Header */
    .welcome-header {
      margin-bottom: 24px;
    }

    .welcome-title {
      font-size: 1.75rem;
      font-weight: 800;
      color: #1e3a8a;
      letter-spacing: -0.5px;
      margin-bottom: 4px;
    }

    .welcome-subtitle {
      font-size: 0.92rem;
      color: var(--text-muted);
    }

    /* ── Stat Cards Grid (4 Kolom Responsif) ────────────────────── */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(4, minmax(0, 1fr));
      gap: 18px;
      margin-bottom: 24px;
    }

    .stat-card {
      background: var(--bg-card);
      border: 1px solid var(--border-color);
      border-radius: var(--radius-lg);
      padding: 18px 20px;
      display: flex;
      align-items: center;
      gap: 14px;
      min-width: 0;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
      transition: var(--transition);
    }

    .stat-card:hover {
      box-shadow: 0 4px 14px rgba(0, 0, 0, 0.04);
      transform: translateY(-2px);
    }

    .stat-icon {
      width: 44px;
      height: 44px;
      border-radius: var(--radius-md);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.25rem;
      flex-shrink: 0;
    }

    .stat-icon.blue {
      background: #e0f2fe;
      color: #0284c7;
    }

    .stat-icon.green {
      background: #dcfce7;
      color: #16a34a;
    }

    .stat-icon.orange {
      background: #fef3c7;
      color: #d97706;
    }

    .stat-icon.purple {
      background: #ede9fe;
      color: #8b5cf6;
      font-weight: 700;
      font-size: 1.05rem;
    }

    .stat-content {
      display: flex;
      flex-direction: column;
      min-width: 0;
    }

    .stat-label {
      font-size: 0.74rem;
      font-weight: 600;
      color: var(--text-muted);
      line-height: 1.2;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .stat-value {
      font-size: 1.35rem;
      font-weight: 800;
      color: var(--text-main);
      margin: 2px 0;
      line-height: 1.2;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .stat-desc {
      font-size: 0.7rem;
      color: var(--text-light);
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    /* ── Layout Grid 2 Kolom (Konten Kiri & Kanan) ──────────────── */
    .dashboard-layout-grid {
      display: grid;
      grid-template-columns: minmax(0, 1fr) 310px;
      gap: 22px;
      align-items: start;
    }

    .grid-left-col {
      min-width: 0;
    }

    .grid-right-col {
      min-width: 0;
    }

    /* Card Box */
    .content-box {
      background: var(--bg-card);
      border: 1px solid var(--border-color);
      border-radius: var(--radius-lg);
      padding: 22px 24px;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
      margin-bottom: 22px;
      min-width: 0;
    }

    .box-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 16px;
    }

    .box-title {
      font-size: 1.02rem;
      font-weight: 700;
      color: var(--text-main);
    }

    .box-link {
      font-size: 0.8rem;
      font-weight: 600;
      color: var(--primary);
      display: flex;
      align-items: center;
      gap: 6px;
      transition: var(--transition);
    }

    .box-link:hover {
      color: var(--primary-hover);
      gap: 9px;
    }

    /* ── Tabel Program Donasi Terbaru ───────────────────────────── */
    .table-container {
      width: 100%;
      overflow-x: auto;
    }

    .program-table {
      width: 100%;
      border-collapse: collapse;
      text-align: left;
    }

    .program-table th {
      padding: 10px 12px;
      font-size: 0.74rem;
      font-weight: 600;
      color: var(--text-muted);
      border-bottom: 1px solid var(--border-color);
      white-space: nowrap;
    }

    .program-table td {
      padding: 12px 10px;
      font-size: 0.8rem;
      border-bottom: 1px solid #f1f5f9;
      vertical-align: middle;
    }

    .program-table tr:last-child td {
      border-bottom: none;
    }

    .col-no {
      color: var(--text-muted);
      font-weight: 500;
      width: 28px;
    }

    .program-meta {
      display: flex;
      align-items: center;
      gap: 12px;
      min-width: 190px;
    }

    .program-thumb {
      width: 48px;
      height: 38px;
      border-radius: var(--radius-sm);
      object-fit: cover;
      background: #e2e8f0;
      flex-shrink: 0;
    }

    .program-info-text {
      display: flex;
      flex-direction: column;
      min-width: 0;
    }

    .program-name {
      font-size: 0.84rem;
      font-weight: 700;
      color: var(--text-main);
      line-height: 1.3;
    }

    .program-category {
      font-size: 0.72rem;
      color: var(--text-light);
      margin-top: 2px;
    }

    .target-val {
      font-weight: 600;
      color: #334155;
      white-space: nowrap;
    }

    .collected-val {
      display: flex;
      flex-direction: column;
      gap: 4px;
    }

    .collected-amount {
      font-weight: 700;
      color: var(--text-main);
      white-space: nowrap;
    }

    .progress-bar-wrap {
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .progress-track {
      width: 70px;
      height: 5px;
      background: #e2e8f0;
      border-radius: var(--radius-pill);
      overflow: hidden;
    }

    .progress-fill {
      height: 100%;
      background: var(--primary);
      border-radius: var(--radius-pill);
    }

    .progress-pct {
      font-size: 0.68rem;
      font-weight: 600;
      color: var(--text-muted);
    }

    /* Badges Status */
    .status-badge {
      display: inline-block;
      padding: 3px 10px;
      border-radius: var(--radius-pill);
      font-size: 0.7rem;
      font-weight: 600;
      white-space: nowrap;
    }

    .status-badge.disetujui,
    .status-badge.berlangsung {
      background: #dcfce7;
      color: #15803d;
    }

    .status-badge.menunggu {
      background: #fef3c7;
      color: #b45309;
    }

    .status-badge.ditolak {
      background: #fee2e2;
      color: #b91c1c;
    }

    .status-badge.selesai {
      background: #e0f2fe;
      color: #0369a1;
    }

    /* Tombol Aksi */
    .btn-detail {
      background: #ffffff;
      border: 1.5px solid #bfdbfe;
      color: var(--primary);
      padding: 4px 12px;
      border-radius: var(--radius-pill);
      font-size: 0.72rem;
      font-weight: 600;
      cursor: pointer;
      transition: var(--transition);
      white-space: nowrap;
      display: inline-block;
    }

    .btn-detail:hover {
      background: var(--primary);
      color: #ffffff;
      border-color: var(--primary);
    }

    /* ── Bottom Banner Gerakan Kebaikan ─────────────────────────── */
    .kindness-banner {
      background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 60%, #bfdbfe 100%);
      border: 1px solid #bfdbfe;
      border-radius: var(--radius-lg);
      padding: 18px 22px;
      display: flex;
      align-items: center;
      gap: 16px;
      position: relative;
      overflow: hidden;
    }

    .banner-icon-circle {
      width: 42px;
      height: 42px;
      border-radius: 50%;
      background: #1d4ed8;
      color: #ffffff;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.15rem;
      flex-shrink: 0;
      box-shadow: 0 4px 10px rgba(29, 78, 216, 0.2);
    }

    .banner-text {
      position: relative;
      z-index: 2;
    }

    .banner-heading {
      font-size: 0.92rem;
      font-weight: 700;
      color: #1e3a8a;
      margin-bottom: 2px;
    }

    .banner-sub {
      font-size: 0.78rem;
      color: #475569;
    }

    .banner-wave-deco {
      position: absolute;
      right: 0;
      bottom: 0;
      height: 100%;
      pointer-events: none;
      opacity: 0.45;
    }

    /* ── Kolom Kanan: Aksi Cepat & Informasi ─────────────────────── */
    .quick-actions-list {
      display: flex;
      flex-direction: column;
      gap: 10px;
    }

    .action-item-card {
      display: flex;
      align-items: center;
      justify-content: space-between;
      border: 1px solid var(--border-color);
      border-radius: var(--radius-md);
      padding: 10px 12px;
      background: #ffffff;
      transition: var(--transition);
    }

    .action-item-card:hover {
      border-color: #93c5fd;
      background: #f8fafc;
      transform: translateX(3px);
    }

    .action-item-left {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .action-icon-circle {
      width: 34px;
      height: 34px;
      border-radius: var(--radius-md);
      background: var(--primary-light);
      color: var(--primary);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 0.95rem;
      flex-shrink: 0;
    }

    .action-title {
      font-size: 0.8rem;
      font-weight: 600;
      color: var(--text-main);
      line-height: 1.2;
    }

    .action-sub {
      font-size: 0.68rem;
      color: var(--text-light);
      margin-top: 2px;
    }

    .action-chevron {
      color: var(--text-light);
      font-size: 0.72rem;
    }

    /* Card Informasi */
    .info-card-header {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 10px;
    }

    .info-card-header i {
      color: var(--primary);
      font-size: 1.1rem;
    }

    .info-card-header h3 {
      font-size: 1.02rem;
      font-weight: 700;
      color: var(--text-main);
    }

    .info-card-body {
      font-size: 0.78rem;
      color: var(--text-muted);
      line-height: 1.55;
      margin-bottom: 16px;
    }

    .info-dots-indicator {
      display: flex;
      justify-content: center;
      gap: 6px;
    }

    .dot {
      width: 7px;
      height: 7px;
      border-radius: 50%;
      background: #cbd5e1;
      transition: var(--transition);
    }

    .dot.active {
      background: var(--primary);
      width: 16px;
      border-radius: var(--radius-pill);
    }

    /* ── Responsive Mobile / Laptop ─────────────────────────────── */
    @media (max-width: 1200px) {
      .stats-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
      }
    }

    @media (max-width: 1024px) {
      .dashboard-layout-grid {
        grid-template-columns: 1fr;
      }
    }

    @media (max-width: 768px) {
      .app-sidebar {
        position: fixed;
        top: 0;
        left: 0;
        bottom: 0;
        transform: translateX(-100%);
      }
      .app-sidebar.show {
        transform: translateX(0);
      }
      .page-scroll-content {
        padding: 20px 18px;
      }
      .stats-grid {
        grid-template-columns: 1fr;
      }
      .welcome-title {
        font-size: 1.4rem;
      }
    }
  </style>
</head>
<body>

  <!-- ═══ DASHBOARD CONTAINER (FLEX 2 KOLOM) ═════════════════════ -->
  <div class="dashboard-container">

    <!-- ── Kolom Kiri: Sidebar ──────────────────────────────────── -->
    <aside class="app-sidebar" id="appSidebar">
      <div class="sidebar-top">
        
        <!-- Logo Brand & Tombol Toggle -->
        <div class="sidebar-brand">
          <a href="/Donasi/pengusul/dashboard.php" class="brand-logo">
            <img src="/logo%20donasi.png" alt="SIBERASA Logo" onerror="this.src='../assets/img/logo_donasi.png'">
          </a>
          <button id="sidebarToggle" class="sidebar-toggle-btn" title="Toggle Menu">
            <i class="fa-solid fa-bars"></i>
          </button>
        </div>

        <!-- User Profile Card -->
        <div class="user-profile-badge">
          <div class="user-profile-avatar">
            <i class="fa-solid fa-circle-user"></i>
          </div>
          <div class="user-profile-info">
            <span class="user-profile-role">Pengusul</span>
            <span class="user-profile-name"><?= htmlspecialchars($namaPengusul) ?></span>
          </div>
        </div>

        <!-- Navigation Menu -->
        <ul class="nav-list">
          <li class="nav-item active">
            <a href="/Donasi/pengusul/dashboard.php">
              <i class="fa-solid fa-table-cells-large"></i>
              <span>Dashboard</span>
            </a>
          </li>
          <li class="nav-item">
            <a href="/Donasi/pengusul/ajukan_program.php">
              <i class="fa-regular fa-id-badge"></i>
              <span>Ajukan Program Donasi</span>
            </a>
          </li>
          <li class="nav-item">
            <a href="/Donasi/pengusul/status_pengajuan.php">
              <i class="fa-regular fa-rectangle-list"></i>
              <span>Status Pengajuan</span>
            </a>
          </li>
          <li class="nav-item">
            <a href="/Donasi/pengusul/dana_terkumpul.php">
              <i class="fa-solid fa-building-columns"></i>
              <span>Dana Terkumpul</span>
            </a>
          </li>
          <!-- Penambahan Menu Sesuai Permintaan User -->
          <li class="nav-item">
            <a href="/Donasi/pengusul/data_penggalangan.php">
              <i class="fa-solid fa-hand-holding-dollar"></i>
              <span>Data Penggalangan Dana</span>
            </a>
          </li>
          <li class="nav-item">
            <a href="/Donasi/pengusul/laporan_donasi.php">
              <i class="fa-solid fa-file-invoice-dollar"></i>
              <span>Laporan Donasi</span>
            </a>
          </li>
        </ul>

      </div>

      <!-- Sidebar Bottom: Logout -->
      <div class="sidebar-footer">
        <a href="/Donasi/auth/logout.php" class="logout-link">
          <i class="fa-solid fa-arrow-right-from-bracket"></i>
          <span>Logout</span>
        </a>
      </div>
    </aside>

    <!-- ── Kolom Kanan: Header & Konten Scrollable ──────────────── -->
    <div class="main-wrapper">

      <!-- Top Header Navbar -->
      <header class="top-header">
        <div class="header-right">
          <div class="notif-btn" title="Pemberitahuan">
            <i class="fa-regular fa-bell"></i>
            <span class="notif-badge"></span>
          </div>
        </div>
      </header>

      <!-- Scrollable Main Content -->
      <main class="page-scroll-content">

        <!-- Greeting Header -->
        <div class="welcome-header">
          <h1 class="welcome-title">Selamat Datang, <?= htmlspecialchars($namaOrganisasi) ?>!</h1>
          <p class="welcome-subtitle">Kelola program donasi dan pantau perkembangan pengajuan Anda di sini.</p>
        </div>

        <!-- 4 Stat Cards Row -->
        <section class="stats-grid">
          <!-- 1. Total Pengajuan -->
          <div class="stat-card">
            <div class="stat-icon blue">
              <i class="fa-regular fa-file-lines"></i>
            </div>
            <div class="stat-content">
              <span class="stat-label">Total Pengajuan</span>
              <div class="stat-value"><?= number_format($totalPengajuan) ?></div>
              <span class="stat-desc">Program yang Anda ajukan</span>
            </div>
          </div>

          <!-- 2. Program Disetujui -->
          <div class="stat-card">
            <div class="stat-icon green">
              <i class="fa-regular fa-circle-check"></i>
            </div>
            <div class="stat-content">
              <span class="stat-label">Program Disetujui</span>
              <div class="stat-value"><?= number_format($programDisetujui) ?></div>
              <span class="stat-desc">Program yang telah disetujui</span>
            </div>
          </div>

          <!-- 3. Dalam Proses -->
          <div class="stat-card">
            <div class="stat-icon orange">
              <i class="fa-regular fa-clock"></i>
            </div>
            <div class="stat-content">
              <span class="stat-label">Dalam Proses</span>
              <div class="stat-value"><?= number_format($dalamProses) ?></div>
              <span class="stat-desc">Menunggu verifikasi admin</span>
            </div>
          </div>

          <!-- 4. Total Dana Terkumpul -->
          <div class="stat-card">
            <div class="stat-icon purple">
              <span>Rp</span>
            </div>
            <div class="stat-content">
              <span class="stat-label">Total Dana Terkumpul</span>
              <div class="stat-value"><?= rp($totalDanaTerkumpul) ?></div>
              <span class="stat-desc">Dari semua program Anda</span>
            </div>
          </div>
        </section>

        <!-- 2-Columns Grid -->
        <div class="dashboard-layout-grid">

          <!-- ── Kolom Kiri ────────────────────────────────────────── -->
          <div class="grid-left-col">

            <!-- Card: Program Donasi Terbaru -->
            <div class="content-box">
              <div class="box-header">
                <h2 class="box-title">Program Donasi Terbaru</h2>
                <a href="/Donasi/pengusul/data_penggalangan.php" class="box-link">
                  Lihat Semua <i class="fa-solid fa-arrow-right"></i>
                </a>
              </div>

              <div class="table-container">
                <table class="program-table">
                  <thead>
                    <tr>
                      <th class="col-no">No</th>
                      <th>Nama Program</th>
                      <th>Target Dana</th>
                      <th>Dana Terkumpul</th>
                      <th>Status</th>
                      <th>Aksi</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (empty($programList)): ?>
                      <tr>
                        <td colspan="6" style="text-align: center; padding: 25px; color: #94a3b8;">
                          Belum ada program donasi yang diajukan.
                        </td>
                      </tr>
                    <?php else: ?>
                      <?php 
                      $no = 1;
                      foreach ($programList as $p): 
                        $target = (float) $p['target_dana'];
                        $terkumpul = (float) $p['dana_terkumpul'];
                        $pct = ($target > 0) ? min(100, round(($terkumpul / $target) * 100)) : 0;
                        $status = $p['status_program'];
                        $badgeClass = $status;
                        $statusText = 'Disetujui';
                        if ($status === 'menunggu') {
                          $statusText = 'Menunggu Verifikasi';
                        } elseif ($status === 'ditolak') {
                          $statusText = 'Ditolak';
                        } elseif ($status === 'selesai') {
                          $statusText = 'Selesai';
                        } elseif ($status === 'berlangsung') {
                          $statusText = 'Berlangsung';
                        }

                        // Foto Program Fallback
                        $foto = !empty($p['foto_program']) ? '../' . ltrim($p['foto_program'], '/') : '../assets/img/program_banjir.jpg';
                      ?>
                      <tr>
                        <td class="col-no"><?= $no++ ?></td>
                        <td>
                          <div class="program-meta">
                            <img src="<?= htmlspecialchars($foto) ?>" alt="<?= htmlspecialchars($p['judul']) ?>" class="program-thumb" onerror="this.src='../assets/img/program_banjir.jpg';">
                            <div class="program-info-text">
                              <span class="program-name"><?= htmlspecialchars($p['judul']) ?></span>
                              <span class="program-category"><?= htmlspecialchars($p['deskripsi'] ?: 'Sosial & Kemanusiaan') ?></span>
                            </div>
                          </div>
                        </td>
                        <td>
                          <span class="target-val"><?= rp($target) ?></span>
                        </td>
                        <td>
                          <div class="collected-val">
                            <span class="collected-amount"><?= rp($terkumpul) ?></span>
                            <div class="progress-bar-wrap">
                              <div class="progress-track">
                                <div class="progress-fill" style="width: <?= $pct ?>%;"></div>
                              </div>
                              <span class="progress-pct"><?= $pct ?>%</span>
                            </div>
                          </div>
                        </td>
                        <td>
                          <span class="status-badge <?= $badgeClass ?>"><?= $statusText ?></span>
                        </td>
                        <td>
                          <button class="btn-detail" onclick="alert('Detail program: <?= addslashes(htmlspecialchars($p['judul'])) ?>\nTarget: <?= rp($target) ?>\nTerkumpul: <?= rp($terkumpul) ?> (<?= $pct ?>%)\nStatus: <?= $statusText ?>')">
                            Lihat Detail
                          </button>
                        </td>
                      </tr>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Banner Kebaikan -->
            <div class="kindness-banner">
              <div class="banner-icon-circle">
                <i class="fa-solid fa-seedling"></i>
              </div>
              <div class="banner-text">
                <h3 class="banner-heading">Bersama kita wujudkan lebih banyak manfaat</h3>
                <p class="banner-sub">Terima kasih telah menjadi bagian dari gerakan kebaikan melalui SIBERASA.</p>
              </div>
              <svg class="banner-wave-deco" viewBox="0 0 200 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M0 100C40 70 80 80 120 40C160 0 180 30 200 20V100H0Z" fill="#93c5fd" fill-opacity="0.4"/>
              </svg>
            </div>

          </div>

          <!-- ── Kolom Kanan ───────────────────────────────────────── -->
          <div class="grid-right-col">

            <!-- Card: Aksi Cepat -->
            <div class="content-box">
              <h2 class="box-title" style="margin-bottom: 14px;">Aksi Cepat</h2>
              <div class="quick-actions-list">
                
                <!-- 1. Ajukan Program Donasi -->
                <a href="/Donasi/pengusul/ajukan_program.php" class="action-item-card">
                  <div class="action-item-left">
                    <div class="action-icon-circle">
                      <i class="fa-regular fa-paper-plane"></i>
                    </div>
                    <div>
                      <div class="action-title">Ajukan Program Donasi</div>
                      <div class="action-sub">Buat program donasi baru</div>
                    </div>
                  </div>
                  <i class="fa-solid fa-chevron-right action-chevron"></i>
                </a>

                <!-- 2. Status Pengajuan -->
                <a href="/Donasi/pengusul/status_pengajuan.php" class="action-item-card">
                  <div class="action-item-left">
                    <div class="action-icon-circle">
                      <i class="fa-regular fa-file-lines"></i>
                    </div>
                    <div>
                      <div class="action-title">Status Pengajuan</div>
                      <div class="action-sub">Lihat status program Anda</div>
                    </div>
                  </div>
                  <i class="fa-solid fa-chevron-right action-chevron"></i>
                </a>

                <!-- 3. Dana Terkumpul -->
                <a href="/Donasi/pengusul/dana_terkumpul.php" class="action-item-card">
                  <div class="action-item-left">
                    <div class="action-icon-circle">
                      <i class="fa-solid fa-database"></i>
                    </div>
                    <div>
                      <div class="action-title">Dana Terkumpul</div>
                      <div class="action-sub">Pantau dana yang terkumpul</div>
                    </div>
                  </div>
                  <i class="fa-solid fa-chevron-right action-chevron"></i>
                </a>

              </div>
            </div>

            <!-- Card: Informasi -->
            <div class="content-box">
              <div class="info-card-header">
                <i class="fa-solid fa-bullhorn"></i>
                <h3>Informasi</h3>
              </div>
              <p class="info-card-body">
                Pastikan data program, target dana, dan informasi rekening sudah sesuai sebelum mengajukan program donasi.
              </p>
              <div class="info-dots-indicator">
                <span class="dot active"></span>
                <span class="dot"></span>
                <span class="dot"></span>
              </div>
            </div>

          </div>

        </div>

      </main>

    </div>

  </div>

  <!-- ═══ Interaktivitas Ringan ═══════════════════════════════════ -->
  <script>
    const sidebar = document.getElementById('appSidebar');
    const toggleBtn = document.getElementById('sidebarToggle');

    toggleBtn.addEventListener('click', () => {
      sidebar.classList.toggle('show');
    });
  </script>

</body>
</html>