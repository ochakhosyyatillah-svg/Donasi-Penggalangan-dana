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

// ── Filter & Search Parameters ──────────────────────────────────────────
$searchQuery  = trim($_GET['q'] ?? '');
$filterStatus = trim($_GET['status'] ?? 'semua');
$page         = max(1, (int)($_GET['page'] ?? 1));
$perPage      = 5;

// ── Ambil Seluruh Program Pengusul dari Database ────────────────────────
try {
    $stmtAll = $pdo->prepare("
        SELECT 
            p.id_program,
            p.judul,
            p.kategori,
            p.target_dana,
            p.status_program,
            p.foto_program,
            p.dibuat_pada,
            p.tanggal_mulai,
            p.tanggal_selesai,
            COALESCE(SUM(CASE WHEN d.status_donasi = 'berhasil' THEN d.nominal ELSE 0 END), 0) AS dana_terkumpul,
            COUNT(CASE WHEN d.status_donasi = 'berhasil' THEN 1 END) AS total_donatur
        FROM program_donasi p
        LEFT JOIN donasi d ON p.id_program = d.id_program
        WHERE p.id_pengusul = ?
        GROUP BY p.id_program
        ORDER BY p.dibuat_pada DESC, p.id_program DESC
    ");
    $stmtAll->execute([$idUser]);
    $allPrograms = $stmtAll->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $allPrograms = [];
}

// ── Hitung Statistik 4 Card Atas ─────────────────────────────────────────
$totalProgram = count($allPrograms);
$totalDana    = 0;
$cntAktif     = 0;
$cntSelesai   = 0;

foreach ($allPrograms as $p) {
    $totalDana += (float)$p['dana_terkumpul'];
    $st = strtolower($p['status_program']);
    if (in_array($st, ['disetujui', 'berlangsung', 'aktif'])) {
        $cntAktif++;
    } elseif ($st === 'selesai') {
        $cntSelesai++;
    }
}

// ── Filter Sesuai Request User ──────────────────────────────────────────
$filtered = $allPrograms;

if ($filterStatus !== '' && $filterStatus !== 'semua') {
    $filtered = array_values(array_filter($filtered, function($r) use ($filterStatus) {
        $st = strtolower($r['status_program']);
        if ($filterStatus === 'aktif') {
            return in_array($st, ['disetujui', 'berlangsung', 'aktif']);
        } elseif ($filterStatus === 'selesai') {
            return $st === 'selesai';
        } elseif ($filterStatus === 'menunggu') {
            return $st === 'menunggu';
        }
        return true;
    }));
}

if ($searchQuery !== '') {
    $filtered = array_values(array_filter($filtered, function($r) use ($searchQuery) {
        return stripos($r['judul'], $searchQuery) !== false ||
               stripos($r['kategori'] ?? '', $searchQuery) !== false;
    }));
}

$totalRows  = count($filtered);
$totalPages = max(1, (int)ceil($totalRows / $perPage));
$page       = min($page, $totalPages);
$offset     = ($page - 1) * $perPage;
$displayRows = array_slice($filtered, $offset, $perPage);

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

function statusBadge($st): array {
    $st = strtolower(trim((string)$st));
    return match(true) {
        in_array($st, ['disetujui', 'berlangsung', 'aktif']) => ['Aktif',   'badge-aktif'],
        $st === 'selesai'                                    => ['Selesai', 'badge-selesai'],
        $st === 'ditolak'                                    => ['Ditolak', 'badge-ditolak'],
        default                                              => ['Menunggu','badge-menunggu'],
    };
}

function kategoriIcon($kat): string {
    $k = strtolower(trim((string)$kat));
    if (str_contains($k, 'pendidikan')) return 'fa-solid fa-graduation-cap';
    if (str_contains($k, 'agama') || str_contains($k, 'ibadah')) return 'fa-solid fa-mosque';
    if (str_contains($k, 'bencana') || str_contains($k, 'kemanusiaan')) return 'fa-solid fa-hand-holding-heart';
    return 'fa-regular fa-heart';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Data Penggalangan Dana – SIBERASA</title>
  <meta name="description" content="Kelola program penggalangan dana yang Anda ajukan, mulai dari pengajuan hingga penyaluran dana.">

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

    /* ── Page Header ── */
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
      width: 48px;
      height: 48px;
      background: #0284c7;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #ffffff;
      font-size: 1.3rem;
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

    .btn-ajukan-program {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: #0284c7;
      color: #ffffff;
      padding: 10px 22px;
      border-radius: 10px;
      font-size: 0.86rem;
      font-weight: 600;
      box-shadow: 0 3px 8px rgba(2, 132, 199, 0.28);
      transition: var(--transition);
    }
    .btn-ajukan-program:hover {
      background: #0369a1;
      box-shadow: 0 6px 14px rgba(2, 132, 199, 0.35);
      transform: translateY(-1px);
    }

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
      align-items: flex-start;
      gap: 16px;
      box-shadow: 0 1px 3px rgba(0,0,0,.02);
      transition: var(--transition);
    }
    .stat-card:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(0,0,0,.04); }

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

    .stat-icon-wrap.card-blue   { background: #e0f2fe; color: #0284c7; }
    .stat-icon-wrap.card-green  { background: #dcfce7; color: #15803d; }
    .stat-icon-wrap.card-purple { background: #f3e8ff; color: #9333ea; }
    .stat-icon-wrap.card-orange { background: #ffedd5; color: #f97316; }

    .stat-content { display: flex; flex-direction: column; }
    .stat-label { font-size: 0.8rem; font-weight: 600; color: #475569; margin-bottom: 4px; }
    .stat-value { font-size: 1.55rem; font-weight: 800; color: #1e3a8a; line-height: 1.2; }
    .stat-value.large-nominal { font-size: 1.35rem; }
    .stat-desc { font-size: 0.74rem; color: #94a3b8; margin-top: 4px; line-height: 1.3; }

    /* ── Main Table Card Box ── */
    .table-card-box {
      background: #ffffff;
      border: 1px solid var(--border-color);
      border-radius: 16px;
      padding: 24px;
      box-shadow: 0 1px 3px rgba(0,0,0,.02);
    }

    .table-toolbar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      margin-bottom: 22px;
      flex-wrap: wrap;
    }

    .table-title {
      font-size: 1.15rem;
      font-weight: 800;
      color: #1e3a8a;
    }

    .toolbar-actions {
      display: flex;
      align-items: center;
      gap: 12px;
      flex-wrap: wrap;
    }

    .search-input-box {
      position: relative;
      width: 260px;
    }

    .search-input-box i {
      position: absolute;
      left: 14px;
      top: 50%;
      transform: translateY(-50%);
      color: #94a3b8;
      font-size: 0.88rem;
    }

    .search-input-box input {
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
    .search-input-box input:focus {
      outline: none;
      border-color: #38bdf8;
      box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.15);
    }

    .custom-select {
      height: 40px;
      padding: 0 36px 0 16px;
      border: 1px solid #dde3ea;
      border-radius: 10px;
      font-size: 0.82rem;
      font-weight: 500;
      font-family: inherit;
      color: #475569;
      background: #ffffff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E") no-repeat calc(100% - 14px) center;
      appearance: none;
      cursor: pointer;
      transition: var(--transition);
    }
    .custom-select:focus {
      outline: none;
      border-color: #38bdf8;
    }

    /* ── Table Elements ── */
    .table-container {
      width: 100%;
      overflow-x: auto;
    }

    .program-table {
      width: 100%;
      border-collapse: separate;
      border-spacing: 0;
      text-align: left;
    }

    /* Soft Blue Header Background */
    .program-table thead th {
      background-color: #edf5fd;
      color: #485c74;
      font-size: 0.82rem;
      font-weight: 700;
      padding: 14px 16px;
      white-space: nowrap;
      border: none;
    }

    .program-table thead th:first-child {
      border-top-left-radius: 10px;
      border-bottom-left-radius: 10px;
      width: 45px;
    }

    .program-table thead th:last-child {
      border-top-right-radius: 10px;
      border-bottom-right-radius: 10px;
      text-align: center;
      width: 120px;
    }

    .program-table tbody td {
      padding: 18px 16px;
      font-size: 0.86rem;
      color: #334155;
      border-bottom: 1px solid #f1f5f9;
      vertical-align: middle;
      line-height: 1.4;
    }

    .program-table tbody tr:last-child td {
      border-bottom: none;
    }

    .program-table tbody tr:hover td {
      background-color: #fafbfc;
    }

    .col-no {
      font-weight: 600;
      color: #64748b;
    }

    /* Program Cell (Thumb + Info) */
    .prog-cell {
      display: flex;
      align-items: center;
      gap: 14px;
      min-width: 240px;
    }

    .prog-thumb {
      width: 52px;
      height: 44px;
      border-radius: 8px;
      object-fit: cover;
      background: #e2e8f0;
      flex-shrink: 0;
    }

    .prog-title {
      font-weight: 700;
      color: #1e3a8a;
      font-size: 0.86rem;
      line-height: 1.3;
      display: block;
    }

    .prog-cat {
      font-size: 0.74rem;
      color: #94a3b8;
      display: inline-flex;
      align-items: center;
      gap: 5px;
      margin-top: 3px;
    }

    /* Date Cell */
    .date-cell .main-date {
      font-size: 0.84rem;
      font-weight: 600;
      color: #334155;
      white-space: nowrap;
    }
    .date-cell .sub-date {
      font-size: 0.72rem;
      color: #94a3b8;
      margin-top: 2px;
    }

    .col-nominal {
      font-weight: 600;
      color: #334155;
      white-space: nowrap;
    }

    .col-terkumpul {
      font-weight: 600;
      color: #1e3a8a;
      white-space: nowrap;
    }

    /* Pencapaian / Progress bar */
    .progress-wrap {
      min-width: 110px;
    }
    .progress-val {
      font-size: 0.8rem;
      font-weight: 700;
      color: #1e3a8a;
      margin-bottom: 5px;
    }
    .progress-track {
      width: 100%;
      height: 6px;
      background: #e2e8f0;
      border-radius: 999px;
      overflow: hidden;
    }
    .progress-fill {
      height: 100%;
      background: #0284c7;
      border-radius: 999px;
      transition: width 0.3s ease;
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

    .badge-aktif {
      background: #dcfce7;
      color: #15803d;
    }
    .badge-aktif::before {
      background: #15803d;
    }

    .badge-selesai {
      background: #dbeafe;
      color: #1d4ed8;
    }
    .badge-selesai::before {
      background: #1d4ed8;
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

    /* Action button */
    .btn-detail {
      display: inline-flex;
      align-items: center;
      gap: 6px;
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
    .btn-detail:hover {
      background: var(--primary-light);
      border-color: var(--primary);
    }
    .btn-detail i {
      font-size: 0.85rem;
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
      min-width: 32px;
      height: 32px;
      padding: 0 6px;
      border-radius: 8px;
      border: 1px solid #dde3ea;
      background: #ffffff;
      color: #64748b;
      font-size: 0.8rem;
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
      .table-toolbar { flex-direction: column; align-items: stretch; }
      .toolbar-actions { width: 100%; flex-direction: column; }
      .search-input-box { width: 100%; }
      .custom-select { width: 100%; }
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
          <li class="nav-item">
            <a href="/Donasi/pengusul/dana_terkumpul.php">
              <i class="fa-solid fa-building-columns"></i><span>Dana Terkumpul</span>
            </a>
          </li>
          <li class="nav-item active">
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
              <i class="fa-solid fa-bullhorn"></i>
            </div>
            <div>
              <h1 class="page-title">Data Penggalangan Dana</h1>
              <p class="page-subtitle">Kelola program penggalangan dana yang Anda ajukan, mulai dari pengajuan hingga penyaluran dana.</p>
            </div>
          </div>

          <a href="/Donasi/pengusul/ajukan_program.php" class="btn-ajukan-program">
            <i class="fa-solid fa-plus"></i>
            <span>Ajukan Program Penggalangan Dana</span>
          </a>
        </div>

        <!-- ── Top 4 Stat Cards ── -->
        <div class="stats-grid">
          
          <!-- Card 1: Total Program -->
          <div class="stat-card">
            <div class="stat-icon-wrap card-blue">
              <i class="fa-regular fa-clipboard"></i>
            </div>
            <div class="stat-content">
              <span class="stat-label">Total Program</span>
              <span class="stat-value"><?= number_format($totalProgram) ?></span>
              <span class="stat-desc">Program penggalangan dana yang Anda ajukan</span>
            </div>
          </div>

          <!-- Card 2: Total Dana Terkumpul -->
          <div class="stat-card">
            <div class="stat-icon-wrap card-green">
              <i class="fa-solid fa-hand-holding-dollar"></i>
            </div>
            <div class="stat-content">
              <span class="stat-label">Total Dana Terkumpul</span>
              <span class="stat-value large-nominal"><?= rp($totalDana) ?></span>
              <span class="stat-desc">Dari semua program penggalangan dana</span>
            </div>
          </div>

          <!-- Card 3: Program Aktif -->
          <div class="stat-card">
            <div class="stat-icon-wrap card-purple">
              <i class="fa-solid fa-user-group"></i>
            </div>
            <div class="stat-content">
              <span class="stat-label">Program Aktif</span>
              <span class="stat-value"><?= number_format($cntAktif) ?></span>
              <span class="stat-desc">Sedang berjalan dan menerima donasi</span>
            </div>
          </div>

          <!-- Card 4: Program Selesai -->
          <div class="stat-card">
            <div class="stat-icon-wrap card-orange">
              <i class="fa-regular fa-circle-check"></i>
            </div>
            <div class="stat-content">
              <span class="stat-label">Program Selesai</span>
              <span class="stat-value"><?= number_format($cntSelesai) ?></span>
              <span class="stat-desc">Telah mencapai target atau ditutup</span>
            </div>
          </div>

        </div>

        <!-- ── Table Card Box ── -->
        <div class="table-card-box">

          <!-- Header Toolbar: Search & Status Filter -->
          <form method="GET" action="" id="filterForm">
            <div class="table-toolbar">
              <h2 class="table-title">Daftar Program Penggalangan Dana</h2>

              <div class="toolbar-actions">
                <div class="search-input-box">
                  <i class="fa-solid fa-magnifying-glass"></i>
                  <input type="text" name="q" id="searchInput" placeholder="Cari nama program..." value="<?= htmlspecialchars($searchQuery) ?>">
                </div>

                <select name="status" class="custom-select" onchange="document.getElementById('filterForm').submit()">
                  <option value="semua" <?= ($filterStatus === 'semua' || $filterStatus === '') ? 'selected' : '' ?>>Semua Status</option>
                  <option value="aktif" <?= ($filterStatus === 'aktif') ? 'selected' : '' ?>>Aktif</option>
                  <option value="selesai" <?= ($filterStatus === 'selesai') ? 'selected' : '' ?>>Selesai</option>
                  <option value="menunggu" <?= ($filterStatus === 'menunggu') ? 'selected' : '' ?>>Menunggu</option>
                </select>
              </div>
            </div>
          </form>

          <!-- Table Content -->
          <div class="table-container">
            <table class="program-table">
              <thead>
                <tr>
                  <th>No</th>
                  <th>Nama Program</th>
                  <th>Tanggal Pengajuan</th>
                  <th>Target Dana</th>
                  <th>Dana Terkumpul</th>
                  <th>Pencapaian</th>
                  <th>Status</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($displayRows)): ?>
                  <tr>
                    <td colspan="8">
                      <div class="empty-state">
                        <i class="fa-regular fa-folder-open"></i>
                        <p>Belum ada program penggalangan dana yang sesuai dengan pencarian atau filter.</p>
                      </div>
                    </td>
                  </tr>
                <?php else: ?>
                  <?php 
                    $noIndex = $offset + 1;
                    foreach ($displayRows as $row): 
                      $target    = (float)$row['target_dana'];
                      $terkumpul = (float)$row['dana_terkumpul'];
                      $pct       = ($target > 0) ? min(100, round(($terkumpul / $target) * 100)) : 0;
                      $foto      = imgPath($row['foto_program'] ?? '');
                      $tglAjukan = !empty($row['dibuat_pada']) ? date('d M Y', strtotime($row['dibuat_pada'])) : '-';
                      [$badgeLabel, $badgeClass] = statusBadge($row['status_program']);
                      $iconKat = kategoriIcon($row['kategori'] ?? '');
                  ?>
                  <tr>
                    <td class="col-no"><?= $noIndex++ ?></td>
                    <td>
                      <div class="prog-cell">
                        <img src="<?= htmlspecialchars($foto) ?>" class="prog-thumb" alt="<?= htmlspecialchars($row['judul']) ?>" onerror="this.src='/Donasi/assets/img/default_program.jpg'">
                        <div>
                          <span class="prog-title"><?= htmlspecialchars($row['judul']) ?></span>
                          <span class="prog-cat">
                            <i class="<?= $iconKat ?>"></i>
                            <?= htmlspecialchars($row['kategori'] ?? 'Sosial & Kemanusiaan') ?>
                          </span>
                        </div>
                      </div>
                    </td>
                    <td class="date-cell">
                      <div class="main-date"><?= $tglAjukan ?></div>
                      <div class="sub-date">Diajukan</div>
                    </td>
                    <td class="col-nominal"><?= rp($target) ?></td>
                    <td class="col-terkumpul"><?= rp($terkumpul) ?></td>
                    <td>
                      <div class="progress-wrap">
                        <div class="progress-val"><?= $pct ?>%</div>
                        <div class="progress-track">
                          <div class="progress-fill" style="width: <?= $pct ?>%;"></div>
                        </div>
                      </div>
                    </td>
                    <td>
                      <span class="badge-status <?= $badgeClass ?>"><?= $badgeLabel ?></span>
                    </td>
                    <td style="text-align: center;">
                      <a href="/Donasi/pengusul/detail_program.php?id=<?= (int)$row['id_program'] ?>" class="btn-detail">
                        <i class="fa-regular fa-eye"></i>
                        <span>Lihat Detail</span>
                      </a>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>

          <!-- Table Footer: Pagination & Information -->
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
              <!-- Previous Button -->
              <?php 
                $prevUrl = '?' . http_build_query(array_merge($_GET, ['page' => $page - 1]));
              ?>
              <a href="<?= ($page > 1) ? $prevUrl : 'javascript:void(0)' ?>" class="pg-btn <?= ($page <= 1) ? 'disabled' : '' ?>">
                <i class="fa-solid fa-chevron-left" style="font-size: 0.65rem;"></i>
              </a>

              <!-- Number Buttons -->
              <?php for ($p = 1; $p <= $totalPages; $p++): 
                $pUrl = '?' . http_build_query(array_merge($_GET, ['page' => $p]));
              ?>
                <a href="<?= $pUrl ?>" class="pg-btn <?= ($p === $page) ? 'active' : '' ?>">
                  <?= $p ?>
                </a>
              <?php endfor; ?>

              <!-- Next Button -->
              <?php 
                $nextUrl = '?' . http_build_query(array_merge($_GET, ['page' => $page + 1]));
              ?>
              <a href="<?= ($page < $totalPages) ? $nextUrl : 'javascript:void(0)' ?>" class="pg-btn <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                <i class="fa-solid fa-chevron-right" style="font-size: 0.65rem;"></i>
              </a>
            </div>
            <?php endif; ?>
          </div>

        </div><!-- /table-card-box -->

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

    // ── Live Client-side Search on Typing ──
    const searchInput = document.getElementById('searchInput');
    const table = document.querySelector('.program-table');
    if (searchInput && table) {
      const rows = table.querySelectorAll('tbody tr');
      searchInput.addEventListener('input', () => {
        const val = searchInput.value.toLowerCase().trim();
        rows.forEach(r => {
          const text = r.textContent.toLowerCase();
          r.style.display = text.includes(val) ? '' : 'none';
        });
      });
      searchInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
          document.getElementById('filterForm').submit();
        }
      });
    }
  </script>
</body>
</html>
