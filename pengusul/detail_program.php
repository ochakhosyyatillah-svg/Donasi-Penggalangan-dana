<?php
session_start();
require_once __DIR__ . '/../assets/config/database.php';

if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'pengusul') {
    header('Location: /Donasi/auth/login.php');
    exit;
}

$idUser    = (int) $_SESSION['id_user'];
$idProgram = (int) ($_GET['id'] ?? 0);

if ($idProgram <= 0) {
    header('Location: /Donasi/pengusul/status_pengajuan.php');
    exit;
}

// Ambil profil user
try {
    $stmtUser = $pdo->prepare("SELECT nama, nama_organisasi FROM users WHERE id_user = ? LIMIT 1");
    $stmtUser->execute([$idUser]);
    $currentUser = $stmtUser->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $currentUser = []; }
$namaPengusul = $currentUser['nama'] ?? $_SESSION['nama'] ?? 'Pengusul';

// Ambil program (pastikan milik pengusul ini)
try {
    $stmtProg = $pdo->prepare("
        SELECT p.*,
               COALESCE(SUM(CASE WHEN d.status_donasi = 'berhasil' THEN d.nominal ELSE 0 END), 0) AS dana_terkumpul,
               COUNT(CASE WHEN d.status_donasi = 'berhasil' THEN 1 END) AS total_donatur
        FROM program_donasi p
        LEFT JOIN donasi d ON d.id_program = p.id_program
        WHERE p.id_program = ? AND p.id_pengusul = ?
        GROUP BY p.id_program
    ");
    $stmtProg->execute([$idProgram, $idUser]);
    $prog = $stmtProg->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $prog = null; }

if (!$prog) {
    header('Location: /Donasi/pengusul/status_pengajuan.php');
    exit;
}

// Ambil 5 donatur terakhir
try {
    $stmtDon = $pdo->prepare("
        SELECT d.id_donasi, d.nominal, d.created_at,
               COALESCE(u.nama, d.nama_donatur, 'Anonim') AS nama_donatur
        FROM donasi d
        LEFT JOIN users u ON u.id_user = d.id_user
        WHERE d.id_program = ? AND d.status_donasi = 'berhasil'
        ORDER BY d.created_at DESC
        LIMIT 5
    ");
    $stmtDon->execute([$idProgram]);
    $donaturList = $stmtDon->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $donaturList = []; }

// Helper
function rp($n) { return 'Rp ' . number_format((float)$n, 0, ',', '.'); }
function imgUrl($foto) {
    if (empty($foto)) return '/Donasi/assets/img/program_banjir.jpg';
    $foto = trim($foto);
    if (str_starts_with($foto, '/'))       return $foto;
    if (str_starts_with($foto, 'assets/')) return '/Donasi/' . $foto;
    return '/Donasi/assets/img/' . $foto;
}
function statusInfo($st): array {
    $st = strtolower(trim((string)$st));
    return match(true) {
        $st === 'menunggu'                          => ['Dalam Proses',  'badge-proses',   'fa-regular fa-clock'],
        in_array($st, ['disetujui','berlangsung'])  => ['Disetujui',     'badge-disetujui','fa-regular fa-circle-check'],
        $st === 'selesai'                           => ['Selesai',       'badge-selesai',  'fa-solid fa-flag-checkered'],
        $st === 'ditolak'                           => ['Ditolak',       'badge-ditolak',  'fa-regular fa-circle-xmark'],
        default                                     => [ucfirst($st),    'badge-proses',   'fa-solid fa-circle-info'],
    };
}

$target    = (float)$prog['target_dana'];
$terkumpul = (float)$prog['dana_terkumpul'];
$pct       = ($target > 0) ? min(100, round(($terkumpul / $target) * 100)) : 0;
[$stLabel, $stClass, $stIcon] = statusInfo($prog['status_program']);

$tujuanDana = [];
if (!empty($prog['tujuan_dana'])) {
    $decoded = json_decode($prog['tujuan_dana'], true);
    if (is_array($decoded)) {
        foreach ($decoded as $item) {
            $tujuanDana[] = [
                'kebutuhan' => $item['kebutuhan'] ?? ($item['item'] ?? 'Kebutuhan'),
                'jumlah'    => (float)($item['jumlah'] ?? ($item['biaya'] ?? 0)),
            ];
        }
    }
}

$tglMulai   = !empty($prog['tanggal_mulai'])   ? date('d M Y', strtotime($prog['tanggal_mulai']))   : '-';
$tglSelesai = !empty($prog['tanggal_selesai']) ? date('d M Y', strtotime($prog['tanggal_selesai'])) : '-';
$tglBuat    = !empty($prog['dibuat_pada'])      ? date('d M Y', strtotime($prog['dibuat_pada']))     : '-';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($prog['judul']) ?> – Detail Program</title>
  <meta name="description" content="Detail program donasi <?= htmlspecialchars($prog['judul']) ?> di SIBERASA.">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --primary:       #0284c7;
      --primary-light: #e0f2fe;
      --primary-hover: #0369a1;
      --text-main:     #0f172a;
      --text-muted:    #64748b;
      --text-light:    #94a3b8;
      --bg-body:       #f1f5f9;
      --border-color:  #e2e8f0;
      --sidebar-width: 260px;
      --header-height: 64px;
      --radius-sm:     8px;
      --radius-md:     12px;
      --radius-lg:     16px;
      --transition:    all 0.25s ease;
    }
    html, body { height: 100%; width: 100%; overflow: hidden; background: var(--bg-body); font-family: 'Poppins', sans-serif; color: var(--text-main); }
    a { text-decoration: none; color: inherit; }
    .dashboard-container { display: flex; width: 100vw; height: 100vh; overflow: hidden; }

    /* ── Sidebar ── */
    .app-sidebar {
      width: var(--sidebar-width); min-width: var(--sidebar-width); max-width: var(--sidebar-width);
      height: 100vh; background: #fff; border-right: 1px solid var(--border-color);
      display: flex; flex-direction: column; justify-content: space-between;
      padding: 20px 18px; overflow-y: auto; overflow-x: hidden;
      flex-shrink: 0; z-index: 100; transition: transform 0.3s cubic-bezier(0.4,0,0.2,1);
    }
    .sidebar-brand { display: flex; align-items: center; justify-content: space-between; margin-bottom: 22px; padding: 0 4px; }
    .brand-logo { display: flex; align-items: center; gap: 8px; }
    .brand-logo img { height: 48px; width: auto; max-width: 180px; object-fit: contain; filter: drop-shadow(0 2px 6px rgba(0,0,0,0.08)); transition: transform 0.2s ease; }
    .brand-logo img:hover { transform: scale(1.03); }
    .sidebar-toggle-btn { background: none; border: none; color: var(--primary); font-size: 1.35rem; cursor: pointer; display: flex; align-items: center; justify-content: center; padding: 6px; border-radius: var(--radius-sm); transition: var(--transition); }
    .sidebar-toggle-btn:hover { background: var(--primary-light); }
    .user-profile-badge { display: flex; align-items: center; gap: 12px; background: #fff; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 12px 14px; margin-bottom: 20px; box-shadow: 0 1px 2px rgba(0,0,0,0.02); }
    .user-profile-avatar { width: 42px; height: 42px; border-radius: 50%; background: #0284c7; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 1.6rem; flex-shrink: 0; }
    .user-profile-info { display: flex; flex-direction: column; overflow: hidden; }
    .user-profile-role { font-size: .95rem; font-weight: 700; color: var(--text-main); line-height: 1.2; display: block; }
    .user-profile-name { font-size: .8rem; color: var(--text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-top: 2px; display: block; }
    .nav-list { list-style: none; display: flex; flex-direction: column; gap: 4px; }
    .nav-item a { display: flex; align-items: center; gap: 12px; padding: 11px 16px; border-radius: 10px; font-size: .875rem; font-weight: 500; color: var(--text-muted); transition: var(--transition); white-space: nowrap; }
    .nav-item a i { font-size: 1.05rem; width: 20px; text-align: center; color: #64748b; transition: var(--transition); }
    .nav-item a:hover { background: #f1f5f9; color: var(--text-main); }
    .nav-item a:hover i { color: var(--primary); }
    .nav-item.active a { background: var(--primary-light); color: var(--primary); font-weight: 600; }
    .nav-item.active a i { color: var(--primary); }
    .sidebar-footer { padding-top: 16px; border-top: 1px solid var(--border-color); margin-top: 16px; }
    .logout-link { display: flex; align-items: center; gap: 12px; padding: 10px 14px; border-radius: var(--radius-sm); font-size: .88rem; font-weight: 500; color: var(--text-muted); transition: var(--transition); }
    .logout-link:hover { background: #fee2e2; color: #dc2626; }
    .logout-link i { font-size: 1.1rem; }

    /* ── Main Wrapper ── */
    .main-wrapper { flex: 1; min-width: 0; height: 100vh; display: flex; flex-direction: column; overflow: hidden; background: var(--bg-body); }
    .top-header { height: var(--header-height); background: #fff; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; justify-content: space-between; padding: 0 32px; flex-shrink: 0; }
    .mobile-toggle-btn { display: none; background: none; border: none; color: var(--primary); font-size: 1.35rem; cursor: pointer; padding: 6px; border-radius: var(--radius-sm); transition: var(--transition); }
    .mobile-toggle-btn:hover { background: var(--primary-light); }
    .header-right { display: flex; align-items: center; gap: 16px; margin-left: auto; }
    .notif-btn { width: 40px; height: 40px; border-radius: 50%; background: #fff; border: 1px solid var(--border-color); display: flex; align-items: center; justify-content: center; color: #334155; font-size: 1.15rem; position: relative; cursor: pointer; transition: var(--transition); }
    .notif-btn:hover { background: var(--primary-light); color: var(--primary); border-color: #bae6fd; }
    .notif-badge { position: absolute; top: 7px; right: 7px; width: 8px; height: 8px; background: #ef4444; border-radius: 50%; border: 2px solid #fff; }
    .page-scroll-content { flex: 1; min-width: 0; overflow-y: auto; overflow-x: hidden; padding: 28px 32px 60px; }

    /* ── Breadcrumb ── */
    .breadcrumb { display: flex; align-items: center; gap: 8px; font-size: .8rem; color: var(--text-muted); margin-bottom: 16px; flex-wrap: wrap; }
    .breadcrumb a { color: var(--primary); font-weight: 600; }
    .breadcrumb a:hover { text-decoration: underline; }
    .breadcrumb i { font-size: .65rem; color: var(--text-light); }

    /* ── Back Button ── */
    .btn-back {
      display: inline-flex; align-items: center; gap: 7px;
      padding: 8px 16px; background: #fff; border: 1px solid var(--border-color);
      border-radius: var(--radius-sm); font-size: .82rem; font-weight: 600;
      color: var(--text-muted); transition: var(--transition); margin-bottom: 20px;
      cursor: pointer; font-family: inherit;
    }
    .btn-back:hover { border-color: var(--primary); color: var(--primary); background: var(--primary-light); }
    .btn-back i { font-size: .75rem; }

    /* ── Content Grid ── */
    .detail-grid {
      display: grid;
      grid-template-columns: 1fr 340px;
      gap: 20px;
      align-items: start;
    }

    /* ── Card Base ── */
    .card {
      background: #fff; border: 1px solid var(--border-color);
      border-radius: var(--radius-lg); padding: 24px;
      box-shadow: 0 1px 3px rgba(0,0,0,.04);
    }
    .card + .card { margin-top: 16px; }
    .card-title {
      font-size: .92rem; font-weight: 700; color: var(--text-main);
      margin-bottom: 16px; display: flex; align-items: center; gap: 8px;
      padding-bottom: 12px; border-bottom: 1px solid var(--border-color);
    }
    .card-title i { color: var(--primary); font-size: .95rem; }

    /* ── Hero Card (Program Banner) ── */
    .hero-card {
      background: #fff; border: 1px solid var(--border-color);
      border-radius: var(--radius-lg); overflow: hidden;
      box-shadow: 0 1px 3px rgba(0,0,0,.04);
      margin-bottom: 16px;
    }
    .hero-img {
      width: 100%; height: 220px; object-fit: cover;
      display: block; background: #e0f2fe;
    }
    .hero-img-fallback {
      width: 100%; height: 220px;
      background: linear-gradient(135deg, #0284c7 0%, #38bdf8 100%);
      display: flex; align-items: center; justify-content: center;
    }
    .hero-img-fallback i { font-size: 4rem; color: rgba(255,255,255,.6); }
    .hero-body { padding: 20px 24px 22px; }

    .prog-judul { font-size: 1.2rem; font-weight: 800; color: var(--text-main); margin-bottom: 6px; line-height: 1.3; }

    .meta-row { display: flex; align-items: center; gap: 6px; font-size: .78rem; color: var(--text-muted); margin-bottom: 12px; flex-wrap: wrap; }
    .meta-row .sep { color: var(--border-color); }

    /* Status badges */
    .status-badge { display: inline-flex; align-items: center; gap: 5px; padding: 4px 12px; border-radius: 999px; font-size: .72rem; font-weight: 600; }
    .badge-proses   { background: #fef9c3; color: #854d0e; }
    .badge-disetujui{ background: #dcfce7; color: #15803d; }
    .badge-selesai  { background: #dbeafe; color: #1d4ed8; }
    .badge-ditolak  { background: #fee2e2; color: #dc2626; }

    /* Progress */
    .progress-section { margin-top: 12px; }
    .progress-label-row { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 6px; }
    .progress-label-row .pct-big { font-size: 1.1rem; font-weight: 800; color: var(--primary); }
    .progress-label-row .dana-label { font-size: .75rem; color: var(--text-muted); }
    .progress-track { width: 100%; height: 8px; background: #e2e8f0; border-radius: 999px; overflow: hidden; }
    .progress-fill  { height: 100%; border-radius: 999px; background: var(--primary); transition: width .5s ease; }
    .progress-fill.full { background: #059669; }
    .dana-stats-row { display: flex; gap: 20px; margin-top: 10px; flex-wrap: wrap; }
    .dana-stat { display: flex; flex-direction: column; }
    .dana-stat-label { font-size: .7rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: .4px; }
    .dana-stat-val { font-size: 1rem; font-weight: 700; color: var(--text-main); margin-top: 2px; }
    .dana-stat-val.primary { color: var(--primary); }

    /* ── Info rows ── */
    .info-row { display: flex; gap: 10px; padding: 10px 0; border-bottom: 1px solid #f1f5f9; align-items: flex-start; }
    .info-row:last-child { border-bottom: none; }
    .info-icon { width: 32px; height: 32px; border-radius: 8px; background: var(--primary-light); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: .85rem; flex-shrink: 0; margin-top: 1px; }
    .info-label { font-size: .72rem; color: var(--text-muted); font-weight: 600; display: block; margin-bottom: 2px; }
    .info-val { font-size: .85rem; color: var(--text-main); font-weight: 600; line-height: 1.4; }

    /* ── Deskripsi ── */
    .deskripsi-text { font-size: .84rem; color: var(--text-muted); line-height: 1.7; }

    /* ── Tujuan Dana List ── */
    .tujuan-item { display: flex; justify-content: space-between; align-items: center; padding: 9px 12px; background: #f8fafc; border-radius: var(--radius-sm); margin-bottom: 8px; }
    .tujuan-item:last-child { margin-bottom: 0; }
    .tujuan-name { font-size: .82rem; color: var(--text-main); font-weight: 500; }
    .tujuan-val  { font-size: .82rem; color: var(--primary); font-weight: 700; }

    /* ── Donatur list ── */
    .donatur-item { display: flex; align-items: center; gap: 10px; padding: 10px 0; border-bottom: 1px solid #f1f5f9; }
    .donatur-item:last-child { border-bottom: none; }
    .donatur-avatar { width: 34px; height: 34px; border-radius: 50%; background: var(--primary-light); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: .85rem; flex-shrink: 0; }
    .donatur-name { font-size: .82rem; font-weight: 600; color: var(--text-main); }
    .donatur-date { font-size: .7rem; color: var(--text-light); margin-top: 1px; }
    .donatur-nominal { font-size: .82rem; font-weight: 700; color: #059669; margin-left: auto; white-space: nowrap; }

    /* ── Alur Status ── */
    .timeline { display: flex; flex-direction: column; gap: 0; }
    .tl-item { display: flex; gap: 14px; align-items: flex-start; position: relative; }
    .tl-item:not(:last-child)::after { content: ''; position: absolute; left: 14px; top: 28px; bottom: -12px; width: 2px; background: #e2e8f0; }
    .tl-dot { width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: .75rem; flex-shrink: 0; margin-top: 2px; }
    .tl-dot.done { background: #dcfce7; color: #15803d; }
    .tl-dot.active{ background: var(--primary-light); color: var(--primary); }
    .tl-dot.pending{ background: #f1f5f9; color: var(--text-light); }
    .tl-body { flex: 1; padding-bottom: 20px; }
    .tl-label { font-size: .82rem; font-weight: 700; color: var(--text-main); }
    .tl-desc  { font-size: .74rem; color: var(--text-muted); margin-top: 2px; }

    /* ── Sidebar Overlay ── */
    .sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(15,23,42,.45); backdrop-filter: blur(2px); z-index: 95; }
    .sidebar-overlay.show { display: block; }

    /* ── Responsive ── */
    @media (max-width: 960px) {
      .detail-grid { grid-template-columns: 1fr; }
    }
    @media (max-width: 768px) {
      .app-sidebar { position: fixed; top: 0; left: 0; bottom: 0; transform: translateX(-100%); }
      .app-sidebar.show { transform: translateX(0); }
      .mobile-toggle-btn { display: flex; }
      .top-header { padding: 0 20px; }
      .page-scroll-content { padding: 20px 16px 50px; }
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

    <!-- Main -->
    <div class="main-wrapper">
      <header class="top-header">
        <button id="mobileSidebarToggle" class="mobile-toggle-btn"><i class="fa-solid fa-bars"></i></button>
        <div class="header-right">
          <div class="notif-btn"><i class="fa-regular fa-bell"></i><span class="notif-badge"></span></div>
        </div>
      </header>

      <main class="page-scroll-content">

        <!-- Breadcrumb -->
        <div class="breadcrumb">
          <a href="/Donasi/pengusul/status_pengajuan.php">Status Pengajuan</a>
          <i class="fa-solid fa-chevron-right"></i>
          <span><?= htmlspecialchars($prog['judul']) ?></span>
        </div>

        <!-- Back button -->
        <a href="/Donasi/pengusul/status_pengajuan.php" class="btn-back">
          <i class="fa-solid fa-arrow-left"></i> Kembali ke Daftar
        </a>

        <!-- 2-col grid -->
        <div class="detail-grid">

          <!-- ── Kolom Kiri ── -->
          <div>

            <!-- Hero: Gambar + Progress -->
            <div class="hero-card">
              <img
                src="<?= htmlspecialchars(imgUrl($prog['foto_program'])) ?>"
                alt="<?= htmlspecialchars($prog['judul']) ?>"
                class="hero-img"
                onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
              >
              <div class="hero-img-fallback" style="display:none;">
                <i class="fa-solid fa-hand-holding-heart"></i>
              </div>

              <div class="hero-body">
                <div class="prog-judul"><?= htmlspecialchars($prog['judul']) ?></div>
                <div class="meta-row">
                  <span class="status-badge <?= $stClass ?>"><i class="<?= $stIcon ?>"></i> <?= $stLabel ?></span>
                  <span class="sep">•</span>
                  <span><?= htmlspecialchars($prog['kategori'] ?? '–') ?></span>
                  <span class="sep">•</span>
                  <span>Diajukan: <?= $tglBuat ?></span>
                </div>

                <!-- Progress -->
                <div class="progress-section">
                  <div class="progress-label-row">
                    <span class="pct-big"><?= $pct ?>% tercapai</span>
                    <span class="dana-label">Target: <?= rp($target) ?></span>
                  </div>
                  <div class="progress-track">
                    <div class="progress-fill <?= $pct >= 100 ? 'full' : '' ?>" style="width:<?= $pct ?>%;"></div>
                  </div>
                  <div class="dana-stats-row">
                    <div class="dana-stat">
                      <span class="dana-stat-label">Dana Terkumpul</span>
                      <span class="dana-stat-val primary"><?= rp($terkumpul) ?></span>
                    </div>
                    <div class="dana-stat">
                      <span class="dana-stat-label">Total Donatur</span>
                      <span class="dana-stat-val"><?= number_format((int)$prog['total_donatur']) ?> orang</span>
                    </div>
                    <div class="dana-stat">
                      <span class="dana-stat-label">Sisa Target</span>
                      <span class="dana-stat-val"><?= rp(max(0, $target - $terkumpul)) ?></span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Deskripsi -->
            <div class="card">
              <div class="card-title"><i class="fa-regular fa-file-lines"></i> Deskripsi Program</div>
              <p class="deskripsi-text"><?= nl2br(htmlspecialchars($prog['deskripsi'] ?? 'Belum ada deskripsi.')) ?></p>
            </div>

            <!-- Tujuan Dana -->
            <?php if (!empty($tujuanDana)): ?>
            <div class="card">
              <div class="card-title"><i class="fa-solid fa-list-check"></i> Rencana Penggunaan Dana</div>
              <?php foreach ($tujuanDana as $t): ?>
                <div class="tujuan-item">
                  <span class="tujuan-name"><i class="fa-solid fa-circle-dot" style="color:#0284c7;font-size:.6rem;margin-right:8px;"></i><?= htmlspecialchars($t['kebutuhan']) ?></span>
                  <span class="tujuan-val"><?= rp($t['jumlah']) ?></span>
                </div>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>

          </div>

          <!-- ── Kolom Kanan ── -->
          <div>

            <!-- Info Program -->
            <div class="card">
              <div class="card-title"><i class="fa-solid fa-circle-info"></i> Informasi Program</div>

              <div class="info-row">
                <div class="info-icon"><i class="fa-regular fa-calendar"></i></div>
                <div>
                  <span class="info-label">Tanggal Mulai</span>
                  <span class="info-val"><?= $tglMulai ?></span>
                </div>
              </div>

              <div class="info-row">
                <div class="info-icon"><i class="fa-regular fa-calendar-check"></i></div>
                <div>
                  <span class="info-label">Tanggal Selesai</span>
                  <span class="info-val"><?= $tglSelesai ?></span>
                </div>
              </div>

              <div class="info-row">
                <div class="info-icon"><i class="fa-solid fa-tag"></i></div>
                <div>
                  <span class="info-label">Kategori</span>
                  <span class="info-val"><?= htmlspecialchars($prog['kategori'] ?? '–') ?></span>
                </div>
              </div>

              <?php if (!empty($prog['penerima_dana'])): ?>
              <div class="info-row">
                <div class="info-icon"><i class="fa-solid fa-users"></i></div>
                <div>
                  <span class="info-label">Penerima Dana</span>
                  <span class="info-val"><?= htmlspecialchars($prog['penerima_dana']) ?></span>
                </div>
              </div>
              <?php endif; ?>

              <div class="info-row">
                <div class="info-icon"><i class="fa-solid fa-coins"></i></div>
                <div>
                  <span class="info-label">Target Dana</span>
                  <span class="info-val"><?= rp($target) ?></span>
                </div>
              </div>

              <?php if (!empty($prog['nomor_rekening'])): ?>
              <div class="info-row">
                <div class="info-icon"><i class="fa-solid fa-building-columns"></i></div>
                <div>
                  <span class="info-label">No. Rekening</span>
                  <span class="info-val"><?= htmlspecialchars($prog['nomor_rekening']) ?></span>
                </div>
              </div>
              <?php endif; ?>
            </div>

            <!-- Alur Status -->
            <div class="card">
              <div class="card-title"><i class="fa-solid fa-arrows-turn-right"></i> Alur Status Pengajuan</div>
              <?php
                $stLower = strtolower($prog['status_program']);
                $steps = [
                    ['label' => 'Anda Ajukan',       'desc' => 'Program berhasil dikirim',        'done' => true],
                    ['label' => 'Verifikasi Admin',   'desc' => 'Admin sedang memeriksa data',     'done' => in_array($stLower, ['disetujui','berlangsung','selesai','ditolak'])],
                    ['label' => 'Disetujui / Ditolak','desc' => 'Keputusan dari admin',            'done' => in_array($stLower, ['disetujui','berlangsung','selesai'])],
                    ['label' => 'Berlangsung',        'desc' => 'Program aktif menerima donasi',   'done' => in_array($stLower, ['berlangsung','selesai'])],
                    ['label' => 'Selesai',            'desc' => 'Program telah selesai',           'done' => $stLower === 'selesai'],
                ];
                $currentIdx = array_search(true, array_map(fn($s) => !$s['done'], $steps));
                if ($currentIdx === false) $currentIdx = count($steps);
              ?>
              <div class="timeline">
                <?php foreach ($steps as $i => $step): ?>
                  <?php
                    if ($step['done'])         $dotClass = 'done';
                    elseif ($i === $currentIdx) $dotClass = 'active';
                    else                        $dotClass = 'pending';
                  ?>
                  <div class="tl-item">
                    <div class="tl-dot <?= $dotClass ?>">
                      <i class="fa-solid <?= $step['done'] ? 'fa-check' : ($dotClass === 'active' ? 'fa-circle-dot' : 'fa-circle') ?>"></i>
                    </div>
                    <div class="tl-body">
                      <div class="tl-label" style="<?= $dotClass === 'active' ? 'color:var(--primary);' : '' ?>"><?= $step['label'] ?></div>
                      <div class="tl-desc"><?= $step['desc'] ?></div>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>

            <!-- Donatur Terakhir -->
            <div class="card">
              <div class="card-title"><i class="fa-solid fa-heart"></i> Donatur Terbaru</div>
              <?php if (empty($donaturList)): ?>
                <p style="font-size:.82rem; color:var(--text-light); text-align:center; padding:16px 0;">Belum ada donatur.</p>
              <?php else: ?>
                <?php foreach ($donaturList as $don): ?>
                  <div class="donatur-item">
                    <div class="donatur-avatar"><i class="fa-solid fa-user"></i></div>
                    <div>
                      <div class="donatur-name"><?= htmlspecialchars($don['nama_donatur']) ?></div>
                      <div class="donatur-date"><?= date('d M Y', strtotime($don['created_at'])) ?></div>
                    </div>
                    <div class="donatur-nominal"><?= rp($don['nominal']) ?></div>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>

          </div><!-- /kolom kanan -->

        </div><!-- /detail-grid -->
      </main>
    </div>
  </div>

  <script>
    const sidebar         = document.getElementById('appSidebar');
    const toggleBtn       = document.getElementById('sidebarToggle');
    const mobileToggleBtn = document.getElementById('mobileSidebarToggle');
    const overlay         = document.getElementById('sidebarOverlay');
    function toggleSidebar() { sidebar.classList.toggle('show'); if (overlay) overlay.classList.toggle('show'); }
    if (toggleBtn)       toggleBtn.addEventListener('click', toggleSidebar);
    if (mobileToggleBtn) mobileToggleBtn.addEventListener('click', toggleSidebar);
    if (overlay)         overlay.addEventListener('click', toggleSidebar);
  </script>
</body>
</html>
