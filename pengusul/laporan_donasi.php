<?php
session_start();
require_once __DIR__ . '/../assets/config/database.php';

if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'pengusul') {
    header('Location: /Donasi/auth/login.php');
    exit;
}

$idUser = (int) $_SESSION['id_user'];

try {
    $stmtUser = $pdo->prepare("SELECT id_user, nama, email, jenis_organisasi, nama_organisasi FROM users WHERE id_user = ? LIMIT 1");
    $stmtUser->execute([$idUser]);
    $currentUser = $stmtUser->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $currentUser = [];
}

$namaPengusul = $currentUser['nama'] ?? $_SESSION['nama'] ?? 'Pengusul';

try {
    $stmtDonasi = $pdo->prepare("
        SELECT 
            d.*,
            p.judul AS nama_program
        FROM donasi d
        JOIN program_donasi p ON d.id_program = p.id_program
        WHERE p.id_pengusul = ?
        ORDER BY d.tanggal_donasi DESC
    ");
    $stmtDonasi->execute([$idUser]);
    $riwayatDonasi = $stmtDonasi->fetchAll(PDO::FETCH_ASSOC);

    $totalLaporanDana = 0;
    foreach ($riwayatDonasi as $r) {
        if ($r['status_donasi'] === 'berhasil') {
            $totalLaporanDana += (float) $r['nominal'];
        }
    }
} catch (PDOException $e) {
    $riwayatDonasi = [];
    $totalLaporanDana = 0;
}

function rp($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Laporan Donasi – SIBERASA</title>
  <meta name="description" content="Rekapitulasi dan laporan transaksi donasi yang masuk ke program pengusul SIBERASA.">

  <!-- Google Font: Poppins -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

  <!-- Font Awesome Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <style>
    /* ── Reset & Basis Layout (Identik dengan Dashboard Pengusul) ── */
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
      overflow: hidden;
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
      min-width: 0;
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
      align-items: center;
      justify-content: center;
      padding: 6px;
      border-radius: var(--radius-sm);
      transition: var(--transition);
    }

    .mobile-toggle-btn:hover {
      background: var(--primary-light);
    }

    .header-right {
      display: flex;
      align-items: center;
      gap: 16px;
      margin-left: auto;
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
      overflow-x: hidden;
      padding: 24px 32px 60px;
    }

    /* ── Header Halaman & Summary ───────────────────────────────── */
    .page-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 22px;
      flex-wrap: wrap;
      gap: 16px;
    }

    .page-title {
      font-size: 1.65rem;
      font-weight: 800;
      color: #1e3a8a;
      letter-spacing: -0.5px;
      margin-bottom: 4px;
    }

    .page-subtitle {
      font-size: 0.9rem;
      color: var(--text-muted);
    }

    .btn-export {
      background: #16a34a;
      color: #ffffff;
      padding: 10px 18px;
      border-radius: 10px;
      font-weight: 600;
      font-size: 0.88rem;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      border: none;
      cursor: pointer;
      box-shadow: 0 2px 6px rgba(22, 163, 74, 0.25);
      transition: var(--transition);
    }

    .btn-export:hover {
      background: #15803d;
      box-shadow: 0 4px 12px rgba(22, 163, 74, 0.35);
      transform: translateY(-1px);
    }

    .summary-card {
      background: #ffffff;
      border: 1px solid var(--border-color);
      border-radius: var(--radius-lg);
      padding: 20px 24px;
      margin-bottom: 22px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 16px;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
    }

    .summary-label {
      font-size: 0.74rem;
      color: #64748b;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .summary-value-blue {
      font-size: 1.7rem;
      font-weight: 800;
      color: var(--primary);
      margin-top: 2px;
    }

    .summary-value-dark {
      font-size: 1.7rem;
      font-weight: 800;
      color: #0f172a;
      margin-top: 2px;
    }

    /* ── Tabel Konten ───────────────────────────────────────────── */
    .content-box {
      background: #ffffff;
      border: 1px solid var(--border-color);
      border-radius: var(--radius-lg);
      padding: 22px 24px;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
    }

    .table-container {
      width: 100%;
      overflow-x: auto;
    }

    .report-table {
      width: 100%;
      border-collapse: collapse;
      text-align: left;
    }

    .report-table th {
      padding: 12px 14px;
      font-size: 0.78rem;
      font-weight: 700;
      color: var(--text-muted);
      border-bottom: 1.5px solid var(--border-color);
      white-space: nowrap;
      background: #f8fafc;
    }

    .report-table th:first-child {
      border-radius: 8px 0 0 8px;
    }
    .report-table th:last-child {
      border-radius: 0 8px 8px 0;
    }

    .report-table td {
      padding: 14px 14px;
      font-size: 0.84rem;
      border-bottom: 1px solid #f1f5f9;
      vertical-align: middle;
    }

    .report-table tr:last-child td {
      border-bottom: none;
    }

    .report-table tr:hover td {
      background-color: #f8fafc;
    }

    .status-badge {
      display: inline-block;
      padding: 4px 12px;
      border-radius: var(--radius-pill);
      font-size: 0.74rem;
      font-weight: 600;
      white-space: nowrap;
    }

    .status-badge.berhasil {
      background: #dcfce7;
      color: #15803d;
    }

    .status-badge.menunggu_verifikasi,
    .status-badge.menunggu_pembayaran {
      background: #fef3c7;
      color: #b45309;
    }

    .status-badge.ditolak,
    .status-badge.dibatalkan {
      background: #fee2e2;
      color: #b91c1c;
    }

    /* Overlay Mobile */
    .sidebar-overlay {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(15, 23, 42, 0.45);
      backdrop-filter: blur(2px);
      z-index: 95;
    }

    .sidebar-overlay.show {
      display: block;
    }

    /* Print Styles */
    @media print {
      .app-sidebar, .top-header, .btn-export, .sidebar-overlay {
        display: none !important;
      }
      .main-wrapper {
        height: auto !important;
        overflow: visible !important;
      }
      .page-scroll-content {
        padding: 0 !important;
        overflow: visible !important;
      }
      body {
        background: #fff !important;
        overflow: visible !important;
      }
    }

    /* ── Responsive ─────────────────────────────────────────────── */
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
      .mobile-toggle-btn {
        display: flex;
      }
      .top-header {
        padding: 0 20px;
      }
      .page-scroll-content {
        padding: 20px 18px 40px;
      }
      .content-box {
        padding: 16px 14px;
      }
      .summary-card {
        flex-direction: column;
        align-items: flex-start;
      }
      .page-title {
        font-size: 1.4rem;
      }
    }
  </style>
</head>
<body>

  <!-- Overlay untuk Mobile Sidebar -->
  <div class="sidebar-overlay" id="sidebarOverlay"></div>

  <!-- ═══ DASHBOARD CONTAINER (FLEX 2 KOLOM) ═════════════════════ -->
  <div class="dashboard-container">

    <!-- ── Kolom Kiri: Sidebar (Identik dengan Dashboard Pengusul) ── -->
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
          <li class="nav-item">
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
          <li class="nav-item">
            <a href="/Donasi/pengusul/data_penggalangan.php">
              <i class="fa-solid fa-hand-holding-dollar"></i>
              <span>Data Penggalangan Dana</span>
            </a>
          </li>
          <li class="nav-item active">
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

      <!-- Scrollable Main Content -->
      <main class="page-scroll-content">

        <div class="page-header">
          <div>
            <h1 class="page-title">Laporan Donasi Masuk</h1>
            <p class="page-subtitle">Rekapitulasi transaksi donasi yang masuk ke seluruh program Anda.</p>
          </div>
          <button onclick="window.print()" class="btn-export">
            <i class="fa-solid fa-print"></i>
            <span>Cetak / Simpan PDF</span>
          </button>
        </div>

        <div class="summary-card">
          <div>
            <div class="summary-label">Total Dana Donasi Terverifikasi</div>
            <div class="summary-value-blue"><?= rp($totalLaporanDana) ?></div>
          </div>
          <div>
            <div class="summary-label">Total Transaksi Masuk</div>
            <div class="summary-value-dark"><?= count($riwayatDonasi) ?> Transaksi</div>
          </div>
        </div>

        <div class="content-box">
          <div class="table-container">
            <table class="report-table">
              <thead>
                <tr>
                  <th style="width: 45px; text-align: center;">No</th>
                  <th>Kode Tracking</th>
                  <th>Program Donasi</th>
                  <th>Nama Donatur</th>
                  <th>Nominal</th>
                  <th>Tanggal</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($riwayatDonasi)): ?>
                  <tr>
                    <td colspan="7" style="text-align: center; padding: 40px; color: #94a3b8;">
                      <i class="fa-regular fa-file-lines" style="font-size: 2.2rem; color: #cbd5e1; margin-bottom: 8px; display: block;"></i>
                      Belum ada riwayat transaksi donasi yang masuk.
                    </td>
                  </tr>
                <?php else: ?>
                  <?php $no = 1; foreach ($riwayatDonasi as $r): ?>
                  <tr>
                    <td style="text-align: center; color: var(--text-muted); font-weight: 600;"><?= $no++ ?></td>
                    <td style="font-family: monospace; font-weight: 700; color: var(--primary);">
                      <?= htmlspecialchars($r['kode_tracking']) ?>
                    </td>
                    <td style="font-weight: 600; color: #0f172a;">
                      <?= htmlspecialchars($r['nama_program']) ?>
                    </td>
                    <td>
                      <i class="fa-solid fa-user-circle" style="color: #94a3b8; margin-right: 4px;"></i>
                      <?= htmlspecialchars($r['nama_donatur'] ?: 'Hamba Allah') ?>
                    </td>
                    <td style="font-weight: 700; color: #16a34a; white-space: nowrap;">
                      <?= rp($r['nominal']) ?>
                    </td>
                    <td style="font-size: 0.78rem; color: #64748b; white-space: nowrap;">
                      <?= date('d M Y, H:i', strtotime($r['tanggal_donasi'])) ?>
                    </td>
                    <td>
                      <span class="status-badge <?= htmlspecialchars($r['status_donasi']) ?>">
                        <?= ucfirst(str_replace('_', ' ', $r['status_donasi'])) ?>
                      </span>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

      </main>

    </div>

  </div>

  <!-- ═══ Interaktivitas Ringan (Sidebar Toggle) ═══════════════════ -->
  <script>
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
  </script>

</body>
</html>
