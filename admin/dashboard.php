<?php
session_start();
require_once __DIR__ . '/../assets/config/database.php';

// Pastikan pengguna sudah login sebagai admin
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    header('Location: /Donasi/auth/login.php');
    exit;
}

$namaAdmin = $_SESSION['nama'] ?? 'Admin';

// ── Ambil data statistik dashboard ──────────────────────────────────
try {
    // Total Program Donasi
    $stmtProgram = $pdo->query("SELECT COUNT(*) AS total FROM program_donasi");
    $totalProgram = $stmtProgram->fetch()['total'] ?? 0;

    // Total Donatur (unik)
    $stmtDonatur = $pdo->query("SELECT COUNT(DISTINCT id_user) AS total FROM donasi");
    $totalDonatur = $stmtDonatur->fetch()['total'] ?? 0;

    // Total Dana Terkumpul
    $stmtDana = $pdo->query("SELECT COALESCE(SUM(jumlah), 0) AS total FROM donasi WHERE status = 'diterima'");
    $totalDana = $stmtDana->fetch()['total'] ?? 0;

    // Menunggu Verifikasi
    $stmtVerif = $pdo->query("SELECT COUNT(*) AS total FROM users WHERE status_verifikasi = 'menunggu'");
    $menungguVerif = $stmtVerif->fetch()['total'] ?? 0;

    // Donasi Terbaru (5 terakhir)
    $stmtRecent = $pdo->query("
        SELECT d.*, p.judul AS nama_program
        FROM donasi d
        LEFT JOIN program_donasi p ON d.id_program = p.id_program
        ORDER BY d.created_at DESC
        LIMIT 5
    ");
    $donasiTerbaru = $stmtRecent->fetchAll();

    // Data Chart - 6 bulan terakhir
    $stmtChart = $pdo->query("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') AS bulan,
            SUM(jumlah) AS total
        FROM donasi
        WHERE status = 'diterima'
          AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY bulan ASC
    ");
    $chartData = $stmtChart->fetchAll();

} catch (PDOException $e) {
    error_log($e->getMessage());
    $totalProgram = $totalDonatur = $totalDana = $menungguVerif = 0;
    $donasiTerbaru = [];
    $chartData = [];
}

// Format rupiah
function rpAdmin($n) {
    return 'Rp ' . number_format($n, 0, ',', '.');
}

// Format label bulan Indonesia
function bulanIndo($ym) {
    $bulanNames = [
        '01' => 'Jan', '02' => 'Feb', '03' => 'Mar', '04' => 'Apr',
        '05' => 'Mei', '06' => 'Jun', '07' => 'Jul', '08' => 'Agu',
        '09' => 'Sep', '10' => 'Okt', '11' => 'Nov', '12' => 'Des'
    ];
    $parts = explode('-', $ym);
    return $bulanNames[$parts[1]] ?? $parts[1];
}

// Siapkan data chart untuk JS
$chartLabels = [];
$chartValues = [];
foreach ($chartData as $row) {
    $chartLabels[] = bulanIndo($row['bulan']);
    $chartValues[] = (int) $row['total'];
}

// Jika data chart kosong, beri data dummy agar chart terlihat
if (empty($chartLabels)) {
    $chartLabels = ['Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep'];
    $chartValues = [2500000, 4200000, 3800000, 5100000, 8500000, 12450000];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Admin – SIBERASA</title>
<meta name="description" content="Dashboard admin SIBERASA - Sistem Donasi dan Penggalangan Dana">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
/* ── Reset & Base ─────────────────────────────────────────────── */
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth}
body{
  font-family:'Poppins',sans-serif;
  background:#f0f4f8;
  color:#1e293b;
  min-height:100vh;
  display:flex;
  overflow-x:hidden;
}
a{text-decoration:none;color:inherit}
ul{list-style:none}

/* ── Sidebar ──────────────────────────────────────────────────── */
.sidebar{
  width:260px;
  min-height:100vh;
  background:linear-gradient(180deg, #0f4c75 0%, #1b6ca8 50%, #3282b8 100%);
  display:flex;
  flex-direction:column;
  padding:0;
  position:fixed;
  top:0;left:0;bottom:0;
  z-index:100;
  transition:transform .3s cubic-bezier(.4,0,.2,1);
  box-shadow:4px 0 24px rgba(0,0,0,.12);
}
.sidebar-header{
  display:flex;
  align-items:center;
  justify-content:space-between;
  padding:20px 20px 0;
}
.sidebar-logo{
  display:flex;
  align-items:center;
  gap:10px;
}
.sidebar-logo .logo-icon{
  width:38px;height:38px;
  background:rgba(255,255,255,.2);
  border-radius:10px;
  display:flex;align-items:center;justify-content:center;
  font-size:20px;
  color:#fff;
}
.sidebar-logo .logo-text{
  font-size:1.1rem;
  font-weight:700;
  color:#fff;
  letter-spacing:.5px;
}
.sidebar-logo .logo-sub{
  font-size:.6rem;
  color:rgba(255,255,255,.65);
  font-weight:400;
  display:block;
  margin-top:-2px;
}
.sidebar-logo-img{
  height:54px;
  width:auto;
  max-width:200px;
  object-fit:contain;
  filter:drop-shadow(0 2px 8px rgba(0,0,0,.25)) brightness(1.05);
  transition:transform .2s ease;
}
.sidebar-logo-img:hover{
  transform:scale(1.04);
}
.hamburger-btn{
  display:none;
  background:none;border:none;
  color:rgba(255,255,255,.85);font-size:1.2rem;
  cursor:pointer;padding:4px;
}

/* User card */
.sidebar-user{
  display:flex;
  align-items:center;
  gap:12px;
  margin:24px 20px 8px;
  padding:14px 16px;
  background:rgba(255,255,255,.12);
  border-radius:12px;
  backdrop-filter:blur(8px);
}
.sidebar-user .avatar{
  width:42px;height:42px;
  border-radius:50%;
  background:linear-gradient(135deg,#4fc3f7,#0288d1);
  display:flex;align-items:center;justify-content:center;
  font-weight:700;font-size:1rem;color:#fff;
  flex-shrink:0;
  box-shadow:0 2px 8px rgba(0,0,0,.15);
}
.sidebar-user .user-info{
  overflow:hidden;
}
.sidebar-user .user-role{
  font-size:.72rem;
  font-weight:600;
  color:rgba(255,255,255,.9);
  text-transform:uppercase;
  letter-spacing:1px;
}
.sidebar-user .user-name{
  font-size:.82rem;
  color:rgba(255,255,255,.65);
  white-space:nowrap;
  overflow:hidden;
  text-overflow:ellipsis;
}

/* Nav */
.sidebar-nav{
  flex:1;
  padding:16px 12px 0;
}
.sidebar-nav .nav-item{
  margin-bottom:4px;
}
.sidebar-nav .nav-link{
  display:flex;
  align-items:center;
  gap:12px;
  padding:12px 16px;
  border-radius:10px;
  font-size:.88rem;
  font-weight:500;
  color:rgba(255,255,255,.75);
  transition:all .25s ease;
  cursor:pointer;
  position:relative;
}
.sidebar-nav .nav-link i{
  width:20px;text-align:center;
  font-size:1rem;
  transition:transform .2s;
}
.sidebar-nav .nav-link:hover{
  background:rgba(255,255,255,.12);
  color:#fff;
  transform:translateX(4px);
}
.sidebar-nav .nav-link:hover i{
  transform:scale(1.15);
}
.sidebar-nav .nav-link.active{
  background:rgba(255,255,255,.2);
  color:#fff;
  font-weight:600;
  box-shadow:0 2px 8px rgba(0,0,0,.1);
}
.sidebar-nav .nav-link.active::before{
  content:'';
  position:absolute;
  left:0;top:50%;
  transform:translateY(-50%);
  width:4px;height:60%;
  background:#4fc3f7;
  border-radius:0 4px 4px 0;
}

/* Logout */
.sidebar-footer{
  padding:16px 12px 24px;
  border-top:1px solid rgba(255,255,255,.1);
  margin:0 12px;
}
.sidebar-footer .nav-link{
  display:flex;
  align-items:center;
  gap:12px;
  padding:12px 16px;
  border-radius:10px;
  font-size:.88rem;
  font-weight:500;
  color:rgba(255,255,255,.65);
  transition:all .25s ease;
  cursor:pointer;
}
.sidebar-footer .nav-link:hover{
  background:rgba(239,68,68,.15);
  color:#fca5a5;
}

/* ── Main Content ────────────────────────────────────────────── */
.main-content{
  flex:1;
  margin-left:260px;
  min-height:100vh;
  display:flex;
  flex-direction:column;
}

/* Top bar */
.topbar{
  display:flex;
  align-items:center;
  justify-content:space-between;
  padding:16px 32px;
  background:#fff;
  border-bottom:1px solid #e2e8f0;
  position:sticky;top:0;z-index:50;
  box-shadow:0 1px 4px rgba(0,0,0,.04);
}
.topbar-left{
  display:flex;align-items:center;gap:12px;
}
.mobile-toggle{
  display:none;
  background:none;border:none;
  font-size:1.3rem;color:#475569;
  cursor:pointer;padding:4px;
}
.topbar-search{
  position:relative;
}
.topbar-search input{
  padding:10px 16px 10px 40px;
  border:1.5px solid #e2e8f0;
  border-radius:10px;
  font-family:'Poppins',sans-serif;
  font-size:.85rem;
  width:280px;
  background:#f8fafc;
  outline:none;
  transition:border-color .2s,box-shadow .2s;
}
.topbar-search input:focus{
  border-color:#3282b8;
  box-shadow:0 0 0 3px rgba(50,130,184,.12);
  background:#fff;
}
.topbar-search i{
  position:absolute;left:14px;top:50%;
  transform:translateY(-50%);
  color:#94a3b8;font-size:.85rem;
}
.topbar-right{
  display:flex;align-items:center;gap:16px;
}
.notif-btn{
  position:relative;
  width:40px;height:40px;
  border:none;border-radius:10px;
  background:#f1f5f9;
  cursor:pointer;
  display:flex;align-items:center;justify-content:center;
  font-size:1.1rem;color:#475569;
  transition:all .2s;
}
.notif-btn:hover{
  background:#e2e8f0;
  transform:translateY(-1px);
}
.notif-btn .badge{
  position:absolute;top:6px;right:6px;
  width:8px;height:8px;
  background:#ef4444;
  border-radius:50%;
  border:2px solid #fff;
}

/* Page content */
.page-content{
  flex:1;
  padding:28px 32px 40px;
}
.page-title{
  margin-bottom:6px;
}
.page-title h1{
  font-size:1.6rem;
  font-weight:700;
  color:#0f172a;
}
.page-title p{
  font-size:.88rem;
  color:#64748b;
  margin-top:2px;
}

/* ── Stat Cards ──────────────────────────────────────────────── */
.stats-grid{
  display:grid;
  grid-template-columns:repeat(4,1fr);
  gap:20px;
  margin-top:24px;
}
.stat-card{
  background:#fff;
  border-radius:16px;
  padding:22px 24px;
  box-shadow:0 1px 8px rgba(0,0,0,.05);
  display:flex;
  flex-direction:column;
  gap:12px;
  transition:transform .25s ease, box-shadow .25s ease;
  position:relative;
  overflow:hidden;
}
.stat-card::after{
  content:'';
  position:absolute;
  top:0;left:0;right:0;
  height:3px;
  border-radius:16px 16px 0 0;
  transition:height .3s;
}
.stat-card:hover{
  transform:translateY(-4px);
  box-shadow:0 8px 25px rgba(0,0,0,.1);
}
.stat-card:hover::after{
  height:4px;
}
.stat-card:nth-child(1)::after{background:linear-gradient(90deg,#3b82f6,#60a5fa)}
.stat-card:nth-child(2)::after{background:linear-gradient(90deg,#f59e0b,#fbbf24)}
.stat-card:nth-child(3)::after{background:linear-gradient(90deg,#10b981,#34d399)}
.stat-card:nth-child(4)::after{background:linear-gradient(90deg,#8b5cf6,#a78bfa)}

.stat-icon{
  width:48px;height:48px;
  border-radius:12px;
  display:flex;align-items:center;justify-content:center;
  font-size:1.2rem;
}
.stat-card:nth-child(1) .stat-icon{
  background:rgba(59,130,246,.1);color:#3b82f6;
}
.stat-card:nth-child(2) .stat-icon{
  background:rgba(245,158,11,.1);color:#f59e0b;
}
.stat-card:nth-child(3) .stat-icon{
  background:rgba(16,185,129,.1);color:#10b981;
}
.stat-card:nth-child(4) .stat-icon{
  background:rgba(139,92,246,.1);color:#8b5cf6;
}

.stat-label{
  font-size:.78rem;
  font-weight:500;
  color:#64748b;
  letter-spacing:.3px;
}
.stat-value{
  font-size:1.65rem;
  font-weight:700;
  color:#0f172a;
  line-height:1;
}
.stat-change{
  display:flex;
  align-items:center;
  gap:4px;
  font-size:.72rem;
  font-weight:600;
}
.stat-change.up{color:#10b981}
.stat-change.down{color:#ef4444}
.stat-change.info{color:#8b5cf6}
.stat-change i{font-size:.65rem}

/* ── Bottom Grid (Chart + Donasi) ────────────────────────────── */
.bottom-grid{
  display:grid;
  grid-template-columns:1.2fr 1fr;
  gap:20px;
  margin-top:24px;
}
.panel{
  background:#fff;
  border-radius:16px;
  padding:24px;
  box-shadow:0 1px 8px rgba(0,0,0,.05);
}
.panel-header{
  display:flex;
  align-items:center;
  justify-content:space-between;
  margin-bottom:20px;
}
.panel-header h2{
  font-size:1rem;
  font-weight:700;
  color:#0f172a;
}
.panel-header h2 span{
  font-weight:400;
  color:#94a3b8;
  font-size:.85rem;
}
.panel-header .view-all{
  font-size:.8rem;
  font-weight:600;
  color:#3282b8;
  display:flex;align-items:center;gap:4px;
  transition:color .2s;
}
.panel-header .view-all:hover{color:#0f4c75}

/* Chart */
.chart-container{
  position:relative;
  width:100%;
  height:300px;
}
.chart-container canvas{
  width:100%!important;
  height:100%!important;
}

/* Donasi list */
.donasi-list{
  display:flex;
  flex-direction:column;
  gap:16px;
}
.donasi-item{
  display:flex;
  align-items:flex-start;
  gap:14px;
  padding:14px;
  border-radius:12px;
  background:#f8fafc;
  transition:all .25s ease;
}
.donasi-item:hover{
  background:#eff6ff;
  transform:translateX(4px);
}
.donasi-icon{
  width:44px;height:44px;
  border-radius:12px;
  display:flex;align-items:center;justify-content:center;
  flex-shrink:0;
  font-size:1.1rem;
}
.donasi-icon.blue{background:rgba(59,130,246,.1);color:#3b82f6}
.donasi-icon.green{background:rgba(16,185,129,.1);color:#10b981}
.donasi-icon.orange{background:rgba(245,158,11,.1);color:#f59e0b}
.donasi-icon.purple{background:rgba(139,92,246,.1);color:#8b5cf6}

.donasi-info{
  flex:1;
  min-width:0;
}
.donasi-info .program-name{
  font-size:.84rem;
  font-weight:600;
  color:#1e293b;
  white-space:nowrap;
  overflow:hidden;
  text-overflow:ellipsis;
}
.donasi-info .amount{
  font-size:.78rem;
  color:#64748b;
  margin-top:2px;
}
.donasi-info .date{
  font-size:.7rem;
  color:#94a3b8;
  margin-top:2px;
}
.donasi-status{
  flex-shrink:0;
  padding:4px 12px;
  border-radius:20px;
  font-size:.68rem;
  font-weight:600;
  text-transform:capitalize;
  align-self:center;
}
.donasi-status.menunggu{
  background:#fef3c7;color:#d97706;
}
.donasi-status.diterima{
  background:#d1fae5;color:#059669;
}
.donasi-status.ditolak{
  background:#fee2e2;color:#dc2626;
}

/* ── Mobile Overlay ──────────────────────────────────────────── */
.sidebar-overlay{
  display:none;
  position:fixed;
  top:0;left:0;right:0;bottom:0;
  background:rgba(0,0,0,.4);
  z-index:90;
  backdrop-filter:blur(2px);
}
.sidebar-overlay.active{display:block}

/* ── Animations ──────────────────────────────────────────────── */
@keyframes fadeInUp{
  from{opacity:0;transform:translateY(20px)}
  to{opacity:1;transform:translateY(0)}
}
@keyframes slideInLeft{
  from{opacity:0;transform:translateX(-20px)}
  to{opacity:1;transform:translateX(0)}
}
.stat-card{animation:fadeInUp .5s ease both}
.stat-card:nth-child(1){animation-delay:.1s}
.stat-card:nth-child(2){animation-delay:.2s}
.stat-card:nth-child(3){animation-delay:.3s}
.stat-card:nth-child(4){animation-delay:.4s}
.panel{animation:fadeInUp .5s ease .5s both}
.donasi-item{animation:slideInLeft .4s ease both}
.donasi-item:nth-child(1){animation-delay:.6s}
.donasi-item:nth-child(2){animation-delay:.7s}
.donasi-item:nth-child(3){animation-delay:.75s}
.donasi-item:nth-child(4){animation-delay:.8s}
.donasi-item:nth-child(5){animation-delay:.85s}

/* ── Responsive ──────────────────────────────────────────────── */
@media(max-width:1200px){
  .stats-grid{grid-template-columns:repeat(2,1fr)}
  .bottom-grid{grid-template-columns:1fr}
}
@media(max-width:768px){
  .sidebar{transform:translateX(-100%)}
  .sidebar.open{transform:translateX(0)}
  .main-content{margin-left:0}
  .mobile-toggle{display:block}
  .topbar{padding:12px 16px}
  .topbar-search input{width:180px}
  .page-content{padding:20px 16px 32px}
  .stats-grid{grid-template-columns:1fr 1fr;gap:12px}
  .page-title h1{font-size:1.3rem}
}
@media(max-width:480px){
  .stats-grid{grid-template-columns:1fr}
  .topbar-search{display:none}
}
</style>
</head>
<body>

<!-- ═══ SIDEBAR ═══════════════════════════════════════════════ -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-header">
    <div class="sidebar-logo">
      <img src="/logo%20donasi.png"
           alt="SIBERASA Logo"
           class="sidebar-logo-img"
           onerror="this.style.display='none'">
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
        <a href="dashboard.php" class="nav-link active" id="nav-dashboard">
          <i class="fas fa-th-large"></i>
          <span>Dashboard</span>
        </a>
      </li>
      <li class="nav-item">
        <a href="kelola_program.php" class="nav-link" id="nav-program">
          <i class="fas fa-hand-holding-usd"></i>
          <span>Kelola Program Donasi</span>
        </a>
      </li>
      <li class="nav-item">
        <a href="kelola_pengguna.php" class="nav-link" id="nav-pengguna">
          <i class="fas fa-users"></i>
          <span>Kelola Pengguna</span>
        </a>
      </li>
      <li class="nav-item">
        <a href="verifikasi_donasi.php" class="nav-link" id="nav-verifikasi">
          <i class="fas fa-clipboard-check"></i>
          <span>Verifikasi Donasi</span>
        </a>
      </li>
    </ul>
  </nav>

  <div class="sidebar-footer">
    <a href="logout.php" class="nav-link" id="nav-logout">
      <i class="fas fa-sign-out-alt"></i>
      <span>Logout</span>
    </a>
  </div>
</aside>

<!-- Overlay for mobile -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- ═══ MAIN CONTENT ══════════════════════════════════════════ -->
<div class="main-content">

  <!-- Top Bar -->
  <header class="topbar">
    <div class="topbar-left">
      <button class="mobile-toggle" id="mobileToggle" aria-label="Toggle sidebar">
        <i class="fas fa-bars"></i>
      </button>
      <div class="topbar-search">
        <i class="fas fa-search"></i>
        <input type="text" placeholder="Cari program, donatur..." id="searchInput">
      </div>
    </div>
    <div class="topbar-right">
      <button class="notif-btn" id="notifBtn" aria-label="Notifikasi">
        <i class="far fa-bell"></i>
        <?php if ($menungguVerif > 0): ?>
          <span class="badge"></span>
        <?php endif; ?>
      </button>
    </div>
  </header>

  <!-- Page Content -->
  <main class="page-content">

    <div class="page-title">
      <h1>Dashboard</h1>
      <p>Ringkasan aktivitas sistem donasi hari ini</p>
    </div>

    <!-- ── Stat Cards ───────────────────────────────────────── -->
    <div class="stats-grid">

      <div class="stat-card">
        <div class="stat-icon">
          <i class="fas fa-hands-helping"></i>
        </div>
        <div class="stat-label">Total Program Donasi</div>
        <div class="stat-value"><?= $totalProgram ?></div>
        <div class="stat-change up">
          <i class="fas fa-arrow-up"></i> +2 dari bulan lalu
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon">
          <i class="fas fa-user-friends"></i>
        </div>
        <div class="stat-label">Total Donatur</div>
        <div class="stat-value"><?= $totalDonatur ?></div>
        <div class="stat-change up">
          <i class="fas fa-arrow-up"></i> +12 dari bulan lalu
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon">
          <i class="fas fa-wallet"></i>
        </div>
        <div class="stat-label">Total Dana Terkumpul</div>
        <div class="stat-value"><?= rpAdmin($totalDana) ?></div>
        <div class="stat-change up">
          <i class="fas fa-arrow-up"></i> +16% dari bulan lalu
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon">
          <i class="fas fa-clock"></i>
        </div>
        <div class="stat-label">Menunggu Verifikasi</div>
        <div class="stat-value"><?= $menungguVerif ?></div>
        <div class="stat-change info">
          <i class="fas fa-arrow-up"></i> perlu dicek
        </div>
      </div>

    </div>

    <!-- ── Bottom Grid: Chart + Donasi Terbaru ──────────────── -->
    <div class="bottom-grid">

      <!-- Chart Panel -->
      <div class="panel">
        <div class="panel-header">
          <h2>Tren Dana Terkumpul <span>(6 Bulan Terakhir)</span></h2>
        </div>
        <div class="chart-container">
          <canvas id="trendChart"></canvas>
        </div>
      </div>

      <!-- Donasi Terbaru Panel -->
      <div class="panel">
        <div class="panel-header">
          <h2>Donasi Terbaru</h2>
          <a href="#" class="view-all">
            Lihat Semua <i class="fas fa-arrow-right"></i>
          </a>
        </div>
        <div class="donasi-list">
          <?php if (empty($donasiTerbaru)): ?>
            <!-- Dummy data when no real data -->
            <div class="donasi-item">
              <div class="donasi-icon blue">
                <i class="fas fa-graduation-cap"></i>
              </div>
              <div class="donasi-info">
                <div class="program-name">Bantuan Pendidikan Anak Desa</div>
                <div class="amount">Rp 500.000</div>
                <div class="date">30 Sep 2025 10:24</div>
              </div>
              <span class="donasi-status menunggu">Menunggu</span>
            </div>
            <div class="donasi-item">
              <div class="donasi-icon green">
                <i class="fas fa-mosque"></i>
              </div>
              <div class="donasi-info">
                <div class="program-name">Pembangunan Mushola</div>
                <div class="amount">Rp 1.000.000</div>
                <div class="date">29 Sep 2025 16:12</div>
              </div>
              <span class="donasi-status diterima">Diterima</span>
            </div>
            <div class="donasi-item">
              <div class="donasi-icon orange">
                <i class="fas fa-globe-asia"></i>
              </div>
              <div class="donasi-info">
                <div class="program-name">Bantuan Korban Bencana</div>
                <div class="amount">Rp 750.000</div>
                <div class="date">28 Sep 2025 09:45</div>
              </div>
              <span class="donasi-status diterima">Diterima</span>
            </div>
          <?php else: ?>
            <?php
            $icons = ['fas fa-graduation-cap', 'fas fa-mosque', 'fas fa-globe-asia', 'fas fa-heart', 'fas fa-hand-holding-heart'];
            $colors = ['blue', 'green', 'orange', 'purple', 'blue'];
            foreach ($donasiTerbaru as $i => $d):
              $iconClass = $icons[$i % count($icons)];
              $colorClass = $colors[$i % count($colors)];
              $status = $d['status'] ?? 'menunggu';
              $statusLabel = ucfirst($status);
              $tanggal = date('d M Y H:i', strtotime($d['created_at']));
            ?>
            <div class="donasi-item">
              <div class="donasi-icon <?= $colorClass ?>">
                <i class="<?= $iconClass ?>"></i>
              </div>
              <div class="donasi-info">
                <div class="program-name"><?= htmlspecialchars($d['nama_program'] ?? 'Program Donasi', ENT_QUOTES, 'UTF-8') ?></div>
                <div class="amount"><?= rpAdmin($d['jumlah'] ?? 0) ?></div>
                <div class="date"><?= $tanggal ?></div>
              </div>
              <span class="donasi-status <?= $status ?>"><?= $statusLabel ?></span>
            </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

    </div>

  </main>

</div>

<!-- ═══ Chart.js ═══════════════════════════════════════════════ -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
// ── Chart ──────────────────────────────────────────────────────
const ctx = document.getElementById('trendChart').getContext('2d');
const gradient = ctx.createLinearGradient(0, 0, 0, 300);
gradient.addColorStop(0, 'rgba(50, 130, 184, 0.25)');
gradient.addColorStop(1, 'rgba(50, 130, 184, 0.02)');

new Chart(ctx, {
  type: 'line',
  data: {
    labels: <?= json_encode($chartLabels) ?>,
    datasets: [{
      label: 'Dana Terkumpul',
      data: <?= json_encode($chartValues) ?>,
      borderColor: '#3282b8',
      backgroundColor: gradient,
      borderWidth: 2.5,
      fill: true,
      tension: 0.4,
      pointBackgroundColor: '#fff',
      pointBorderColor: '#3282b8',
      pointBorderWidth: 2,
      pointRadius: 4,
      pointHoverRadius: 7,
      pointHoverBackgroundColor: '#3282b8',
      pointHoverBorderColor: '#fff',
      pointHoverBorderWidth: 3,
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    interaction: {
      intersect: false,
      mode: 'index'
    },
    plugins: {
      legend: { display: false },
      tooltip: {
        backgroundColor: 'rgba(15, 76, 117, 0.95)',
        titleFont: { family: 'Poppins', size: 12 },
        bodyFont: { family: 'Poppins', size: 12 },
        padding: 12,
        cornerRadius: 10,
        displayColors: false,
        callbacks: {
          label: function(ctx) {
            return 'Rp ' + ctx.parsed.y.toLocaleString('id-ID');
          }
        }
      }
    },
    scales: {
      y: {
        beginAtZero: true,
        grid: {
          color: 'rgba(0,0,0,0.04)',
          drawBorder: false
        },
        ticks: {
          font: { family: 'Poppins', size: 11 },
          color: '#94a3b8',
          callback: function(val) {
            if (val >= 1000000) return (val / 1000000).toFixed(0) + ' jt';
            if (val >= 1000) return (val / 1000).toFixed(0) + ' rb';
            return val;
          },
          maxTicksLimit: 6
        }
      },
      x: {
        grid: { display: false },
        ticks: {
          font: { family: 'Poppins', size: 11 },
          color: '#94a3b8'
        }
      }
    }
  }
});

// ── Sidebar Toggle (Mobile) ───────────────────────────────────
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('sidebarOverlay');
const toggle  = document.getElementById('mobileToggle');

function openSidebar() {
  sidebar.classList.add('open');
  overlay.classList.add('active');
  document.body.style.overflow = 'hidden';
}
function closeSidebar() {
  sidebar.classList.remove('open');
  overlay.classList.remove('active');
  document.body.style.overflow = '';
}

toggle.addEventListener('click', openSidebar);
overlay.addEventListener('click', closeSidebar);

// ── Counter animation ─────────────────────────────────────────
function animateValue(el, start, end, duration) {
  const range = end - start;
  const startTime = performance.now();
  const isRupiah = el.textContent.startsWith('Rp');

  function update(currentTime) {
    const elapsed = currentTime - startTime;
    const progress = Math.min(elapsed / duration, 1);
    const eased = 1 - Math.pow(1 - progress, 3);
    const current = Math.floor(start + range * eased);

    if (isRupiah) {
      el.textContent = 'Rp ' + current.toLocaleString('id-ID');
    } else {
      el.textContent = current.toLocaleString('id-ID');
    }

    if (progress < 1) requestAnimationFrame(update);
  }
  requestAnimationFrame(update);
}

// Animate stat values on load
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.stat-value').forEach(el => {
    const text = el.textContent.trim();
    const numericVal = parseInt(text.replace(/[^\d]/g, ''), 10);
    if (!isNaN(numericVal) && numericVal > 0) {
      animateValue(el, 0, numericVal, 1200);
    }
  });
});
</script>

</body>
</html>
