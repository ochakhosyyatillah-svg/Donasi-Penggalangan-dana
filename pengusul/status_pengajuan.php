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

// Ambil semua data program milik pengusul berserta akumulasi dana donasi yang telah berhasil
try {
    $stmtAll = $pdo->prepare("
        SELECT p.*,
               COALESCE(SUM(CASE WHEN d.status_donasi = 'berhasil' THEN d.nominal ELSE 0 END), 0) AS dana_terkumpul
        FROM program_donasi p
        LEFT JOIN donasi d ON d.id_program = p.id_program
        WHERE p.id_pengusul = ?
        GROUP BY p.id_program
        ORDER BY p.dibuat_pada DESC
    ");
    $stmtAll->execute([$idUser]);
    $allPrograms = $stmtAll->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $allPrograms = [];
}

// Hitung total untuk stat card
$totalPengajuan = count($allPrograms);
$countMenunggu  = 0;
$countDisetujui = 0;
$countDitolak   = 0;

foreach ($allPrograms as $item) {
    $st = strtolower($item['status_program']);
    if ($st === 'menunggu') {
        $countMenunggu++;
    } elseif (in_array($st, ['disetujui', 'berlangsung', 'selesai'])) {
        $countDisetujui++;
    } elseif ($st === 'ditolak') {
        $countDitolak++;
    }
}

// Tangkap filter dari GET
$searchQuery  = trim($_GET['q'] ?? '');
$filterStatus = trim($_GET['status'] ?? 'semua');
$sortBy       = trim($_GET['sort'] ?? 'terbaru');

// Filtering
$filtered = $allPrograms;

if ($filterStatus !== '' && $filterStatus !== 'semua') {
    $filtered = array_filter($filtered, function($item) use ($filterStatus) {
        $st = strtolower($item['status_program']);
        if ($filterStatus === 'dalam_proses') return $st === 'menunggu';
        if ($filterStatus === 'disetujui')    return in_array($st, ['disetujui', 'berlangsung']);
        if ($filterStatus === 'ditolak')      return $st === 'ditolak';
        if ($filterStatus === 'selesai')      return $st === 'selesai';
        return true;
    });
}

if ($searchQuery !== '') {
    $filtered = array_filter($filtered, function($item) use ($searchQuery) {
        return stripos($item['judul'], $searchQuery) !== false ||
               stripos($item['kategori'] ?? '', $searchQuery) !== false;
    });
}

// Sorting
$filtered = array_values($filtered);
if ($sortBy === 'terlama') {
    usort($filtered, fn($a, $b) => strtotime($a['dibuat_pada']) <=> strtotime($b['dibuat_pada']));
} elseif ($sortBy === 'target_desc') {
    usort($filtered, fn($a, $b) => ($b['target_dana'] <=> $a['target_dana']));
} elseif ($sortBy === 'target_asc') {
    usort($filtered, fn($a, $b) => ($a['target_dana'] <=> $b['target_dana']));
} else {
    // terbaru
    usort($filtered, fn($a, $b) => strtotime($b['dibuat_pada']) <=> strtotime($a['dibuat_pada']));
}

// Pagination
$perPage     = 5;
$totalRows   = count($filtered);
$totalPages  = max(1, (int)ceil($totalRows / $perPage));
$page        = max(1, min($totalPages, (int)($_GET['page'] ?? 1)));
$offset      = ($page - 1) * $perPage;
$displayRows = array_slice($filtered, $offset, $perPage);

function rp($num) {
    return 'Rp ' . number_format((float)$num, 0, ',', '.');
}

function getPhotoUrl($filename) {
    if (empty($filename)) {
        return '/Donasi/assets/img/default_program.jpg';
    }
    $f = trim($filename);
    if (strpos($f, '/') === 0) {
        return $f;
    }
    if (strpos($f, 'assets/') === 0) {
        return '/Donasi/' . $f;
    }
    return '/Donasi/assets/img/' . $f;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Status Pengajuan – SIBERASA</title>
  <meta name="description" content="Pantau status dan perkembangan program donasi yang Anda ajukan.">

  <!-- Font Poppins -->
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
      --primary: #0284c7;
      --primary-light: #e0f2fe;
      --primary-hover: #0369a1;
      --text-main: #0f172a;
      --text-muted: #64748b;
      --text-light: #94a3b8;
      --bg-body: #f8fafc;
      --bg-page: #f8fafc;
      --border: #e2e8f0;
      --border-color: #e2e8f0;
      --border-blue: #38bdf8;
      --sidebar-width: 260px;
      --sidebar-w: 260px;
      --header-height: 64px;
      --header-h: 64px;
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
      background-color: var(--bg-page);
      font-family: 'Poppins', sans-serif;
      color: var(--text-main);
    }

    a {
      text-decoration: none;
      color: inherit;
    }

    /* Container 2 Kolom */
    .dashboard-container {
      display: flex;
      width: 100vw;
      height: 100vh;
      overflow: hidden;
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

    /* Main Area */
    .main-wrapper {
      flex: 1;
      min-width: 0;
      height: 100vh;
      display: flex;
      flex-direction: column;
      overflow: hidden;
    }

    .top-header {
      height: var(--header-h);
      background: #ffffff;
      border-bottom: 1px solid var(--border);
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
      font-size: 1.3rem;
      cursor: pointer;
      padding: 6px;
      border-radius: var(--radius-sm);
    }

    .header-right {
      display: flex;
      align-items: center;
      gap: 16px;
      margin-left: auto;
    }

    .notif-btn {
      width: 38px;
      height: 38px;
      border-radius: 50%;
      background: #ffffff;
      border: 1px solid var(--border);
      display: flex;
      align-items: center;
      justify-content: center;
      color: #334155;
      font-size: 1.1rem;
      position: relative;
      cursor: pointer;
      transition: var(--transition);
    }

    .notif-btn:hover {
      background: var(--primary-light);
      color: var(--primary);
    }

    .notif-badge {
      position: absolute;
      top: 6px;
      right: 6px;
      width: 8px;
      height: 8px;
      background: #ef4444;
      border-radius: 50%;
      border: 2px solid #fff;
    }

    /* Page Content */
    .page-scroll-content {
      flex: 1;
      min-width: 0;
      overflow-y: auto;
      padding: 28px 36px 60px;
    }

    /* Title */
    .content-header {
      margin-bottom: 24px;
    }

    .page-title {
      font-size: 1.85rem;
      font-weight: 800;
      color: #1e3a8a;
      letter-spacing: -0.5px;
      margin-bottom: 4px;
    }

    .page-subtitle {
      font-size: 0.95rem;
      color: var(--text-muted);
    }

    /* 4 Stat Cards */
    .stats-row {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 18px;
      margin-bottom: 26px;
    }

    .stat-card {
      background: #ffffff;
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      padding: 18px 20px;
      display: flex;
      align-items: flex-start;
      gap: 16px;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
      transition: var(--transition);
    }

    .stat-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 16px rgba(0, 0, 0, 0.06);
    }

    .stat-icon {
      width: 48px;
      height: 48px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.35rem;
      flex-shrink: 0;
    }

    .stat-icon.blue {
      background: #e0f2fe;
      color: #0284c7;
    }

    .stat-icon.yellow {
      background: #fef3c7;
      color: #d97706;
    }

    .stat-icon.green {
      background: #dcfce7;
      color: #16a34a;
    }

    .stat-icon.red {
      background: #fee2e2;
      color: #ef4444;
    }

    .stat-info {
      display: flex;
      flex-direction: column;
    }

    .stat-title {
      font-size: 0.8rem;
      font-weight: 600;
      color: var(--text-main);
      margin-bottom: 2px;
    }

    .stat-num {
      font-size: 1.85rem;
      font-weight: 800;
      color: #0f172a;
      line-height: 1.2;
    }

    .stat-desc {
      font-size: 0.73rem;
      color: var(--text-light);
      margin-top: 3px;
    }

    /* Outer Table Box */
    .table-card-box {
      background: #ffffff;
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      padding: 24px;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
    }

    /* Toolbar Filter & Search */
    .table-toolbar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      margin-bottom: 20px;
      flex-wrap: wrap;
    }

    .search-box-wrap {
      flex: 1;
      min-width: 240px;
      position: relative;
    }

    .search-box-wrap i {
      position: absolute;
      left: 14px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--text-light);
      font-size: 0.9rem;
    }

    .search-box-wrap input {
      width: 100%;
      padding: 10px 16px 10px 38px;
      border: 1px solid var(--border);
      border-radius: var(--radius-sm);
      font-family: 'Poppins', sans-serif;
      font-size: 0.86rem;
      color: var(--text-main);
      background: #ffffff;
      outline: none;
      transition: var(--transition);
    }

    .search-box-wrap input:focus {
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.12);
    }

    .filter-group {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .custom-select {
      padding: 10px 36px 10px 14px;
      border: 1px solid var(--border);
      border-radius: var(--radius-sm);
      font-family: 'Poppins', sans-serif;
      font-size: 0.86rem;
      color: var(--text-main);
      background: #ffffff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24'%3E%3Cpath fill='%2364748b' d='M7 10l5 5 5-5z'/%3E%3C/svg%3E") no-repeat right 12px center;
      appearance: none;
      outline: none;
      cursor: pointer;
      transition: var(--transition);
    }

    .custom-select:focus {
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.12);
    }

    /* Table */
    .table-responsive {
      width: 100%;
      overflow-x: auto;
    }

    .data-table {
      width: 100%;
      border-collapse: separate;
      border-spacing: 0;
      text-align: left;
    }

    .data-table thead tr th {
      padding: 14px 16px;
      font-size: 0.78rem;
      font-weight: 700;
      color: var(--text-muted);
      border-top: 1px solid var(--border);
      border-bottom: 1px solid var(--border);
      background: #f8fafc;
      white-space: nowrap;
    }

    .data-table tbody tr td {
      padding: 16px 16px;
      font-size: 0.85rem;
      border-bottom: 1px solid #f1f5f9;
      vertical-align: middle;
    }

    .data-table tbody tr:hover td {
      background: #f8fafc;
    }

    .col-no {
      text-align: center;
      width: 44px;
      color: var(--text-muted);
      font-weight: 600;
    }

    /* Program info with image thumbnail */
    .program-info-cell {
      display: flex;
      align-items: center;
      gap: 14px;
    }

    .program-thumb {
      width: 52px;
      height: 52px;
      border-radius: 10px;
      object-fit: cover;
      flex-shrink: 0;
      border: 1px solid var(--border);
      background: #f1f5f9;
    }

    .program-text {
      display: flex;
      flex-direction: column;
    }

    .program-name {
      font-size: 0.88rem;
      font-weight: 700;
      color: #0f172a;
      line-height: 1.35;
    }

    .program-category {
      font-size: 0.75rem;
      color: var(--text-muted);
      margin-top: 3px;
    }

    .col-date {
      color: var(--text-muted);
      font-size: 0.82rem;
      white-space: nowrap;
    }

    .col-target {
      font-weight: 600;
      color: var(--text-main);
      white-space: nowrap;
      font-size: 0.85rem;
    }

    /* Dana Terkumpul & Progress Bar */
    .dana-cell {
      min-width: 150px;
    }

    .dana-nominal {
      font-size: 0.86rem;
      font-weight: 700;
      color: #0284c7;
      margin-bottom: 5px;
    }

    .progress-bar-container {
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .progress-track {
      flex: 1;
      height: 7px;
      background: #e2e8f0;
      border-radius: 99px;
      overflow: hidden;
    }

    .progress-fill {
      height: 100%;
      border-radius: 99px;
      background: #0284c7;
      transition: width 0.4s ease;
    }

    .progress-fill.full {
      background: #10b981;
    }

    .progress-percent {
      font-size: 0.72rem;
      font-weight: 600;
      color: var(--text-muted);
      min-width: 28px;
    }

    /* Badges Status */
    .status-badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 5px 12px;
      border-radius: var(--radius-pill);
      font-size: 0.75rem;
      font-weight: 600;
      white-space: nowrap;
    }

    .badge-proses {
      background: #fef3c7;
      color: #d97706;
    }

    .badge-disetujui {
      background: #dcfce7;
      color: #16a34a;
    }

    .badge-ditolak {
      background: #fee2e2;
      color: #dc2626;
    }

    .badge-selesai {
      background: #e0f2fe;
      color: #0284c7;
    }

    /* Action Buttons */
    .action-cell {
      display: flex;
      align-items: center;
      gap: 8px;
      white-space: nowrap;
    }

    .btn-lihat-detail {
      padding: 6px 14px;
      background: #ffffff;
      border: 1px solid #bae6fd;
      border-radius: var(--radius-sm);
      font-family: 'Poppins', sans-serif;
      font-size: 0.78rem;
      font-weight: 600;
      color: #0284c7;
      cursor: pointer;
      transition: var(--transition);
      display: inline-flex;
      align-items: center;
      justify-content: center;
    }

    .btn-lihat-detail:hover {
      background: #0284c7;
      color: #ffffff;
      border-color: #0284c7;
    }

    .btn-chevron {
      color: var(--text-light);
      font-size: 0.85rem;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      transition: var(--transition);
    }

    .action-cell:hover .btn-chevron {
      color: #0284c7;
      transform: translateX(2px);
    }

    /* Empty State */
    .empty-state {
      text-align: center;
      padding: 40px 20px;
    }

    .empty-state i {
      font-size: 2.8rem;
      color: #cbd5e1;
      margin-bottom: 12px;
    }

    .empty-state-title {
      font-size: 1rem;
      font-weight: 700;
      color: var(--text-main);
      margin-bottom: 4px;
    }

    .empty-state-desc {
      font-size: 0.84rem;
      color: var(--text-muted);
      margin-bottom: 16px;
    }

    /* Table Footer & Pagination */
    .table-footer {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      margin-top: 18px;
      padding-top: 16px;
      border-top: 1px solid var(--border);
      flex-wrap: wrap;
    }

    .footer-info {
      font-size: 0.82rem;
      color: var(--text-muted);
    }

    .pagination {
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .page-link-btn {
      min-width: 32px;
      height: 32px;
      border-radius: var(--radius-sm);
      border: 1px solid var(--border);
      background: #ffffff;
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: 'Poppins', sans-serif;
      font-size: 0.8rem;
      font-weight: 600;
      color: var(--text-muted);
      cursor: pointer;
      transition: var(--transition);
      padding: 0 10px;
    }

    .page-link-btn:hover {
      border-color: var(--primary);
      color: var(--primary);
      background: var(--primary-light);
    }

    .page-link-btn.active {
      background: #0284c7;
      color: #ffffff;
      border-color: #0284c7;
    }

    .page-link-btn.disabled {
      opacity: 0.45;
      pointer-events: none;
      cursor: not-allowed;
      background: #f8fafc;
    }

    /* Overlay Mobile */
    .sidebar-overlay {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(15, 23, 42, 0.45);
      backdrop-filter: blur(2px);
      z-index: 95;
    }

    .sidebar-overlay.show {
      display: block;
    }

    /* Responsive */
    @media (max-width: 1100px) {
      .stats-row {
        grid-template-columns: repeat(2, 1fr);
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
      .mobile-toggle-btn {
        display: flex;
      }
      .top-header {
        padding: 0 20px;
      }
      .page-scroll-content {
        padding: 20px 18px 40px;
      }
      .table-toolbar {
        flex-direction: column;
        align-items: stretch;
      }
      .filter-group {
        width: 100%;
        justify-content: space-between;
      }
      .custom-select {
        flex: 1;
      }
    }

    @media (max-width: 480px) {
      .stats-row {
        grid-template-columns: 1fr;
      }
      .page-title {
        font-size: 1.45rem;
      }
    }
  </style>
</head>
<body>

  <div class="sidebar-overlay" id="sidebarOverlay"></div>

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
          <li class="nav-item active">
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

        <!-- Title Section -->
        <div class="content-header">
          <h1 class="page-title">Status Pengajuan</h1>
          <p class="page-subtitle">Pantau status dan perkembangan program donasi yang Anda ajukan.</p>
        </div>

        <!-- 4 Stat Cards -->
        <div class="stats-row">
          <div class="stat-card">
            <div class="stat-icon blue">
              <i class="fa-regular fa-file-lines"></i>
            </div>
            <div class="stat-info">
              <span class="stat-title">Total Pengajuan</span>
              <span class="stat-num"><?= $totalPengajuan ?></span>
              <span class="stat-desc">Program yang Anda ajukan</span>
            </div>
          </div>

          <div class="stat-card">
            <div class="stat-icon yellow">
              <i class="fa-regular fa-clock"></i>
            </div>
            <div class="stat-info">
              <span class="stat-title">Dalam Proses</span>
              <span class="stat-num"><?= $countMenunggu ?></span>
              <span class="stat-desc">Menunggu verifikasi admin</span>
            </div>
          </div>

          <div class="stat-card">
            <div class="stat-icon green">
              <i class="fa-regular fa-circle-check"></i>
            </div>
            <div class="stat-info">
              <span class="stat-title">Disetujui</span>
              <span class="stat-num"><?= $countDisetujui ?></span>
              <span class="stat-desc">Program telah disetujui</span>
            </div>
          </div>

          <div class="stat-card">
            <div class="stat-icon red">
              <i class="fa-regular fa-circle-xmark"></i>
            </div>
            <div class="stat-info">
              <span class="stat-title">Ditolak</span>
              <span class="stat-num"><?= $countDitolak ?></span>
              <span class="stat-desc">Program ditolak admin</span>
            </div>
          </div>
        </div>

        <!-- Table Box with Blue Dashed Outline -->
        <div class="table-card-box">

          <!-- Search & Filter Controls -->
          <form method="GET" action="" id="searchFilterForm">
            <div class="table-toolbar">
              <div class="search-box-wrap">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" name="q" placeholder="Cari nama program...."
                       value="<?= htmlspecialchars($searchQuery) ?>" id="searchProgramInput">
              </div>

              <div class="filter-group">
                <select name="status" class="custom-select" onchange="document.getElementById('searchFilterForm').submit()">
                  <option value="semua" <?= ($filterStatus === 'semua' || $filterStatus === '') ? 'selected' : '' ?>>Semua Status</option>
                  <option value="dalam_proses" <?= ($filterStatus === 'dalam_proses') ? 'selected' : '' ?>>Dalam Proses</option>
                  <option value="disetujui" <?= ($filterStatus === 'disetujui') ? 'selected' : '' ?>>Disetujui</option>
                  <option value="ditolak" <?= ($filterStatus === 'ditolak') ? 'selected' : '' ?>>Ditolak</option>
                  <option value="selesai" <?= ($filterStatus === 'selesai') ? 'selected' : '' ?>>Selesai</option>
                </select>

                <select name="sort" class="custom-select" onchange="document.getElementById('searchFilterForm').submit()">
                  <option value="terbaru" <?= ($sortBy === 'terbaru') ? 'selected' : '' ?>>Terbaru</option>
                  <option value="terlama" <?= ($sortBy === 'terlama') ? 'selected' : '' ?>>Terlama</option>
                  <option value="target_desc" <?= ($sortBy === 'target_desc') ? 'selected' : '' ?>>Target Tertinggi</option>
                  <option value="target_asc" <?= ($sortBy === 'target_asc') ? 'selected' : '' ?>>Target Terendah</option>
                </select>
              </div>
            </div>
          </form>

          <!-- Table Content -->
          <div class="table-responsive">
            <table class="data-table">
              <thead>
                <tr>
                  <th class="col-no">No</th>
                  <th>Nama Program</th>
                  <th>Tanggal Pengajuan</th>
                  <th>Target Dana</th>
                  <th>Dana Terkumpul</th>
                  <th>Status</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($displayRows)): ?>
                  <tr>
                    <td colspan="7">
                      <div class="empty-state">
                        <i class="fa-regular fa-folder-open"></i>
                        <div class="empty-state-title">Tidak Ada Pengajuan Ditemukan</div>
                        <p class="empty-state-desc">Belum ada data program yang sesuai dengan kriteria pencarian atau filter.</p>
                      </div>
                    </td>
                  </tr>
                <?php else: ?>
                  <?php 
                    $noIndex = $offset + 1;
                    foreach ($displayRows as $row): 
                      $targetVal    = (float)($row['target_dana'] ?? 0);
                      $terkumpulVal = (float)($row['dana_terkumpul'] ?? 0);
                      $percent      = $targetVal > 0 ? min(100, round(($terkumpulVal / $targetVal) * 100)) : 0;
                      $isCompleted  = $percent >= 100;
                      $stLower      = strtolower($row['status_program']);
                  ?>
                  <tr>
                    <td class="col-no"><?= $noIndex++ ?></td>
                    <td>
                      <div class="program-info-cell">
                        <img src="<?= getPhotoUrl($row['foto_program'] ?? '') ?>"
                             alt="<?= htmlspecialchars($row['judul']) ?>"
                             class="program-thumb"
                             onerror="this.src='/Donasi/assets/img/default_program.jpg'">
                        <div class="program-text">
                          <span class="program-name"><?= htmlspecialchars($row['judul']) ?></span>
                          <span class="program-category"><?= htmlspecialchars($row['kategori'] ?? 'Sosial & Kemanusiaan') ?></span>
                        </div>
                      </div>
                    </td>
                    <td class="col-date">
                      <?= date('d M Y', strtotime($row['dibuat_pada'])) ?>
                    </td>
                    <td class="col-target">
                      <?= rp($targetVal) ?>
                    </td>
                    <td>
                      <div class="dana-cell">
                        <div class="dana-nominal"><?= rp($terkumpulVal) ?></div>
                        <div class="progress-bar-container">
                          <div class="progress-track">
                            <div class="progress-fill <?= $isCompleted ? 'full' : '' ?>" style="width: <?= $percent ?>%;"></div>
                          </div>
                          <span class="progress-percent"><?= $percent ?>%</span>
                        </div>
                      </div>
                    </td>
                    <td>
                      <?php if ($stLower === 'menunggu'): ?>
                        <span class="status-badge badge-proses">
                          <i class="fa-regular fa-clock"></i> Dalam Proses
                        </span>
                      <?php elseif (in_array($stLower, ['disetujui', 'berlangsung'])): ?>
                        <span class="status-badge badge-disetujui">
                          <i class="fa-regular fa-circle-check"></i> Disetujui
                        </span>
                      <?php elseif ($stLower === 'ditolak'): ?>
                        <span class="status-badge badge-ditolak">
                          <i class="fa-regular fa-circle-xmark"></i> Ditolak
                        </span>
                      <?php elseif ($stLower === 'selesai'): ?>
                        <span class="status-badge badge-selesai">
                          <i class="fa-solid fa-flag-checkered"></i> Selesai
                        </span>
                      <?php else: ?>
                        <span class="status-badge badge-proses">
                          <?= htmlspecialchars(ucfirst($stLower)) ?>
                        </span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <div class="action-cell">
                        <a href="/Donasi/pengusul/detail_program.php?id=<?= (int)$row['id_program'] ?>" class="btn-lihat-detail">
                          Lihat Detail
                        </a>
                        <span class="btn-chevron"><i class="fa-solid fa-chevron-right"></i></span>
                      </div>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>

          <!-- Footer Information & Pagination -->
          <div class="table-footer">
            <div class="footer-info">
              <?php if ($totalRows > 0): ?>
                Menampilkan <?= $offset + 1 ?>–<?= min($offset + $perPage, $totalRows) ?> dari <?= $totalRows ?> data
              <?php else: ?>
                Menampilkan 0 data
              <?php endif; ?>
            </div>

            <?php if ($totalPages > 1): ?>
            <div class="pagination">
              <!-- Previous Button -->
              <?php 
                $prevUrl = '?' . http_build_query(array_merge($_GET, ['page' => $page - 1]));
              ?>
              <a href="<?= ($page > 1) ? $prevUrl : 'javascript:void(0)' ?>"
                 class="page-link-btn <?= ($page <= 1) ? 'disabled' : '' ?>"
                 title="Halaman Sebelumnya">
                <i class="fa-solid fa-chevron-left"></i>
              </a>

              <!-- Page Numbers -->
              <?php for ($p = 1; $p <= $totalPages; $p++): 
                $pUrl = '?' . http_build_query(array_merge($_GET, ['page' => $p]));
              ?>
                <a href="<?= $pUrl ?>"
                   class="page-link-btn <?= ($p === $page) ? 'active' : '' ?>">
                  <?= $p ?>
                </a>
              <?php endfor; ?>

              <!-- Next Button -->
              <?php 
                $nextUrl = '?' . http_build_query(array_merge($_GET, ['page' => $page + 1]));
              ?>
              <a href="<?= ($page < $totalPages) ? $nextUrl : 'javascript:void(0)' ?>"
                 class="page-link-btn <?= ($page >= $totalPages) ? 'disabled' : '' ?>"
                 title="Halaman Berikutnya">
                <i class="fa-solid fa-chevron-right"></i>
              </a>
            </div>
            <?php endif; ?>
          </div>

        </div>

      </main>

    </div>

  </div>

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

    // Filter input search auto submit on Enter
    const searchInput = document.getElementById('searchProgramInput');
    if (searchInput) {
      searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
          document.getElementById('searchFilterForm').submit();
        }
      });
    }
  </script>
</body>
</html>
