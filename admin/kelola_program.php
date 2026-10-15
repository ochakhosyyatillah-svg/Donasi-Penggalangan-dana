<?php
session_start();
require_once __DIR__ . '/../assets/config/database.php';

// Pastikan pengguna sudah login sebagai admin
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    header('Location: /Donasi/donatur/login.php');
    exit;
}

$namaAdmin = $_SESSION['nama'] ?? 'Admin';

// ── Konfigurasi Pagination ────────────────────────────────────────
$perPage   = 10;
$page      = max(1, (int)($_GET['page'] ?? 1));
$offset    = ($page - 1) * $perPage;
$search    = trim($_GET['q'] ?? '');
$filterStatus = $_GET['status'] ?? '';

// ── Handle Aksi (Hapus, Ubah Status) ─────────────────────────────
$alertMsg  = '';
$alertType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    // Hapus Program
    if ($aksi === 'hapus') {
        $id = (int)($_POST['id_program'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare("DELETE FROM program_donasi WHERE id_program = ?");
            $stmt->execute([$id]);
            $alertMsg  = 'Program berhasil dihapus.';
            $alertType = 'success';
        }
    }

    // Ubah Status
    if ($aksi === 'ubah_status') {
        $id     = (int)($_POST['id_program'] ?? 0);
        $status = $_POST['status_baru'] ?? '';
        $validStatus = ['menunggu','disetujui','ditolak','berlangsung','selesai','dibatalkan'];
        if ($id > 0 && in_array($status, $validStatus)) {
            $stmt = $pdo->prepare("UPDATE program_donasi SET status_program = ? WHERE id_program = ?");
            $stmt->execute([$status, $id]);
            $alertMsg  = 'Status program berhasil diubah.';
            $alertType = 'success';
        }
    }

    // Tambah Program
    if ($aksi === 'tambah') {
        $judul       = trim($_POST['judul'] ?? '');
        $kategori    = trim($_POST['kategori'] ?? '');
        $deskripsi   = trim($_POST['deskripsi'] ?? '');
        $target      = (float)str_replace(['.', ','], ['', '.'], $_POST['target_dana'] ?? 0);
        $tgl_mulai   = $_POST['tanggal_mulai'] ?? '';
        $tgl_selesai = $_POST['tanggal_selesai'] ?? '';
        $status_p    = $_POST['status_program'] ?? 'menunggu';

        // Upload foto
        $foto = null;
        if (!empty($_FILES['foto_program']['name'])) {
            $ext   = strtolower(pathinfo($_FILES['foto_program']['name'], PATHINFO_EXTENSION));
            $allow = ['jpg','jpeg','png','webp'];
            if (in_array($ext, $allow)) {
                $newName = 'prog_' . time() . '_' . rand(100,999) . '.' . $ext;
                $uploadDir = __DIR__ . '/../assets/img/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                move_uploaded_file($_FILES['foto_program']['tmp_name'], $uploadDir . $newName);
                $foto = '../assets/img/' . $newName;
            }
        }

        if ($judul && $kategori && $tgl_mulai) {
            $stmt = $pdo->prepare("
                INSERT INTO program_donasi
                    (judul, kategori, deskripsi, target_dana, tanggal_mulai, tanggal_selesai, foto_program, status_program, id_pengusul)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$judul, $kategori, $deskripsi, $target, $tgl_mulai, $tgl_selesai ?: null, $foto, $status_p, $_SESSION['id_user']]);
            $alertMsg  = 'Program baru berhasil ditambahkan.';
            $alertType = 'success';
        } else {
            $alertMsg  = 'Judul, kategori, dan tanggal mulai wajib diisi.';
            $alertType = 'error';
        }
    }

    // Edit Program
    if ($aksi === 'edit') {
        $id          = (int)($_POST['id_program'] ?? 0);
        $judul       = trim($_POST['judul'] ?? '');
        $kategori    = trim($_POST['kategori'] ?? '');
        $deskripsi   = trim($_POST['deskripsi'] ?? '');
        $target      = (float)str_replace(['.', ','], ['', '.'], $_POST['target_dana'] ?? 0);
        $tgl_mulai   = $_POST['tanggal_mulai'] ?? '';
        $tgl_selesai = $_POST['tanggal_selesai'] ?? '';
        $status_p    = $_POST['status_program'] ?? 'menunggu';

        if ($id > 0 && $judul && $kategori && $tgl_mulai) {
            $stmt = $pdo->prepare("
                UPDATE program_donasi
                SET judul=?, kategori=?, deskripsi=?, target_dana=?,
                    tanggal_mulai=?, tanggal_selesai=?, status_program=?
                WHERE id_program=?
            ");
            $stmt->execute([$judul, $kategori, $deskripsi, $target, $tgl_mulai, $tgl_selesai ?: null, $status_p, $id]);
            $alertMsg  = 'Program berhasil diperbarui.';
            $alertType = 'success';
        } else {
            $alertMsg  = 'Judul, kategori, dan tanggal mulai wajib diisi.';
            $alertType = 'error';
        }
    }
}

// ── Ambil Data Program ────────────────────────────────────────────
try {
    $where  = '1=1';
    $params = [];

    if ($search !== '') {
        $where   .= ' AND (p.judul LIKE ? OR p.kategori LIKE ?)';
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    if ($filterStatus !== '') {
        $where   .= ' AND p.status_program = ?';
        $params[] = $filterStatus;
    }

    // Total rows untuk pagination
    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM program_donasi p WHERE $where");
    $stmtCount->execute($params);
    $totalRows = (int)$stmtCount->fetchColumn();
    $totalPages = max(1, (int)ceil($totalRows / $perPage));

    // Ambil data dengan LIMIT
    $perPageInt    = (int)$perPage;
    $offsetInt     = (int)$offset;
    $stmtProgram   = $pdo->prepare("
        SELECT p.*, u.nama AS nama_pengusul
        FROM program_donasi p
        LEFT JOIN users u ON p.id_pengusul = u.id_user
        WHERE $where
        ORDER BY p.dibuat_pada DESC
        LIMIT $perPageInt OFFSET $offsetInt
    ");
    $stmtProgram->execute($params);
    $programs = $stmtProgram->fetchAll();

} catch (PDOException $e) {
    error_log($e->getMessage());
    $programs   = [];
    $totalRows  = 0;
    $totalPages = 1;
}

// ── Helper ────────────────────────────────────────────────────────
function rpK($n) {
    return 'Rp ' . number_format($n, 0, ',', '.');
}
function statusBadge($s) {
    $map = [
        'menunggu'    => ['label' => 'Menunggu',    'class' => 'badge-warning'],
        'disetujui'   => ['label' => 'Disetujui',   'class' => 'badge-info'],
        'berlangsung' => ['label' => 'Aktif',        'class' => 'badge-success'],
        'selesai'     => ['label' => 'Selesai',      'class' => 'badge-secondary'],
        'ditolak'     => ['label' => 'Ditolak',      'class' => 'badge-danger'],
        'dibatalkan'  => ['label' => 'Dibatalkan',   'class' => 'badge-danger'],
    ];
    $d = $map[$s] ?? ['label' => ucfirst($s), 'class' => 'badge-secondary'];
    return '<span class="badge-status ' . $d['class'] . '">' . $d['label'] . '</span>';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kelola Program Donasi – SIBERASA Admin</title>
<meta name="description" content="Halaman kelola program donasi admin SIBERASA">

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
  background:linear-gradient(180deg,#0f4c75 0%,#1b6ca8 50%,#3282b8 100%);
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
.sidebar-logo-img{
  height:54px;
  width:auto;
  max-width:200px;
  object-fit:contain;
  filter:drop-shadow(0 2px 8px rgba(0,0,0,.25)) brightness(1.05);
  transition:transform .2s ease;
}
.sidebar-logo-img:hover{transform:scale(1.04)}

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
.sidebar-user .user-role{
  font-size:.72rem;font-weight:600;
  color:rgba(255,255,255,.9);
  text-transform:uppercase;letter-spacing:1px;
}
.sidebar-user .user-name{
  font-size:.82rem;
  color:rgba(255,255,255,.65);
  white-space:nowrap;overflow:hidden;text-overflow:ellipsis;
}

/* Nav */
.sidebar-nav{flex:1;padding:16px 12px 0}
.sidebar-nav .nav-item{margin-bottom:4px}
.sidebar-nav .nav-link{
  display:flex;align-items:center;gap:12px;
  padding:12px 16px;border-radius:10px;
  font-size:.88rem;font-weight:500;
  color:rgba(255,255,255,.75);
  transition:all .25s ease;
  cursor:pointer;position:relative;
}
.sidebar-nav .nav-link i{
  width:20px;text-align:center;font-size:1rem;transition:transform .2s;
}
.sidebar-nav .nav-link:hover{
  background:rgba(255,255,255,.12);color:#fff;transform:translateX(4px);
}
.sidebar-nav .nav-link:hover i{transform:scale(1.15)}
.sidebar-nav .nav-link.active{
  background:rgba(255,255,255,.2);color:#fff;font-weight:600;
  box-shadow:0 2px 8px rgba(0,0,0,.1);
}
.sidebar-nav .nav-link.active::before{
  content:'';position:absolute;
  left:0;top:50%;transform:translateY(-50%);
  width:4px;height:60%;
  background:#4fc3f7;border-radius:0 4px 4px 0;
}

/* Logout */
.sidebar-footer{
  padding:16px 12px 24px;
  border-top:1px solid rgba(255,255,255,.1);
  margin:0 12px;
}
.sidebar-footer .nav-link{
  display:flex;align-items:center;gap:12px;
  padding:12px 16px;border-radius:10px;
  font-size:.88rem;font-weight:500;
  color:rgba(255,255,255,.65);transition:all .25s ease;cursor:pointer;
}
.sidebar-footer .nav-link:hover{
  background:rgba(239,68,68,.15);color:#fca5a5;
}

/* Overlay mobile */
.sidebar-overlay{
  display:none;position:fixed;top:0;left:0;right:0;bottom:0;
  background:rgba(0,0,0,.4);z-index:90;backdrop-filter:blur(2px);
}
.sidebar-overlay.active{display:block}

/* ── Main Content ─────────────────────────────────────────────── */
.main-content{
  flex:1;
  margin-left:260px;
  min-height:100vh;
  display:flex;flex-direction:column;
}

/* Topbar */
.topbar{
  display:flex;align-items:center;justify-content:space-between;
  padding:16px 32px;
  background:#fff;border-bottom:1px solid #e2e8f0;
  position:sticky;top:0;z-index:50;
  box-shadow:0 1px 4px rgba(0,0,0,.04);
}
.topbar-left{display:flex;align-items:center;gap:12px}
.mobile-toggle{
  display:none;background:none;border:none;
  font-size:1.3rem;color:#475569;cursor:pointer;padding:4px;
}
.notif-btn{
  position:relative;width:40px;height:40px;
  border:none;border-radius:10px;background:#f1f5f9;
  cursor:pointer;display:flex;align-items:center;justify-content:center;
  font-size:1.1rem;color:#475569;transition:all .2s;
}
.notif-btn:hover{background:#e2e8f0;transform:translateY(-1px)}

/* Page content */
.page-content{flex:1;padding:28px 32px 40px}
.page-title{margin-bottom:20px}
.page-title h1{font-size:1.6rem;font-weight:700;color:#0f172a}
.page-title p{font-size:.88rem;color:#64748b;margin-top:2px}

/* ── Toolbar (search + tambah) ──────────────────────────────── */
.toolbar{
  display:flex;align-items:center;gap:12px;
  margin-bottom:20px;flex-wrap:wrap;
}
.search-box{
  position:relative;flex:1;min-width:200px;max-width:400px;
}
.search-box input{
  width:100%;
  padding:11px 16px 11px 42px;
  border:1.5px solid #e2e8f0;border-radius:10px;
  font-family:'Poppins',sans-serif;font-size:.85rem;
  background:#fff;outline:none;
  transition:border-color .2s,box-shadow .2s;
}
.search-box input:focus{
  border-color:#3282b8;
  box-shadow:0 0 0 3px rgba(50,130,184,.12);
}
.search-box i{
  position:absolute;left:14px;top:50%;transform:translateY(-50%);
  color:#94a3b8;font-size:.85rem;
}
.filter-select{
  padding:10px 14px;
  border:1.5px solid #e2e8f0;border-radius:10px;
  font-family:'Poppins',sans-serif;font-size:.85rem;
  background:#fff;color:#475569;outline:none;cursor:pointer;
  transition:border-color .2s;
}
.filter-select:focus{border-color:#3282b8}
.btn-tambah{
  display:inline-flex;align-items:center;gap:8px;
  padding:11px 20px;
  background:linear-gradient(135deg,#1b6ca8,#3282b8);
  color:#fff;border:none;border-radius:10px;
  font-family:'Poppins',sans-serif;font-size:.88rem;font-weight:600;
  cursor:pointer;transition:all .25s ease;
  box-shadow:0 4px 14px rgba(27,108,168,.35);
  white-space:nowrap;
}
.btn-tambah:hover{
  background:linear-gradient(135deg,#0f4c75,#1b6ca8);
  transform:translateY(-2px);
  box-shadow:0 6px 20px rgba(27,108,168,.45);
}

/* ── Table Panel ─────────────────────────────────────────────── */
.table-panel{
  background:#fff;
  border-radius:16px;
  box-shadow:0 1px 8px rgba(0,0,0,.06);
  border:2px solid #3282b8;
  overflow:hidden;
  animation:fadeInUp .5s ease both;
}
@keyframes fadeInUp{
  from{opacity:0;transform:translateY(20px)}
  to{opacity:1;transform:translateY(0)}
}
.table-wrap{overflow-x:auto}
table{width:100%;border-collapse:collapse}
thead tr{
  background:linear-gradient(90deg,#f0f7ff,#e8f2fb);
  border-bottom:2px solid #d0e8f8;
}
thead th{
  padding:14px 16px;
  font-size:.78rem;font-weight:700;
  color:#1b6ca8;text-align:left;
  letter-spacing:.4px;text-transform:uppercase;
  white-space:nowrap;
}
tbody tr{
  border-bottom:1.5px dashed #d0e8f8;
  transition:background .2s;
}
tbody tr:last-child{border-bottom:none}
tbody tr:hover{background:#f5faff}
tbody td{
  padding:16px;
  font-size:.85rem;color:#334155;
  vertical-align:middle;
}
td.no-data{
  text-align:center;padding:48px 16px;
  color:#94a3b8;font-size:.9rem;
}
.col-no{width:52px;text-align:center;font-weight:600;color:#94a3b8}
.col-judul{min-width:180px;font-weight:600;color:#1e293b}
.col-judul small{display:block;font-size:.72rem;font-weight:400;color:#94a3b8;margin-top:2px}
.col-aksi{width:100px;white-space:nowrap;text-align:center}

/* Badge status */
.badge-status{
  display:inline-flex;align-items:center;
  padding:4px 12px;border-radius:20px;
  font-size:.7rem;font-weight:700;
  letter-spacing:.3px;white-space:nowrap;
}
.badge-success{background:#d1fae5;color:#059669}
.badge-warning{background:#fef3c7;color:#b45309}
.badge-danger {background:#fee2e2;color:#dc2626}
.badge-info   {background:#dbeafe;color:#1d4ed8}
.badge-secondary{background:#f1f5f9;color:#64748b}

/* Action buttons */
.btn-action{
  display:inline-flex;align-items:center;justify-content:center;
  width:34px;height:34px;border-radius:8px;
  border:none;cursor:pointer;font-size:.85rem;
  transition:all .2s ease;
}
.btn-edit{background:#eff6ff;color:#3b82f6}
.btn-edit:hover{background:#3b82f6;color:#fff;transform:scale(1.1)}
.btn-hapus{background:#fef2f2;color:#ef4444}
.btn-hapus:hover{background:#ef4444;color:#fff;transform:scale(1.1)}
.btn-detail-view{background:#f0fdf4;color:#22c55e;margin-right:2px}
.btn-detail-view:hover{background:#22c55e;color:#fff;transform:scale(1.1)}

/* ── Pagination ──────────────────────────────────────────────── */
.pagination-wrap{
  display:flex;align-items:center;justify-content:space-between;
  padding:16px 20px;
  border-top:1px solid #e8f2fb;
  background:#fafcff;
  flex-wrap:wrap;gap:8px;
}
.pagination-info{font-size:.78rem;color:#64748b}
.pagination{display:flex;align-items:center;gap:4px}
.page-btn{
  display:inline-flex;align-items:center;justify-content:center;
  width:34px;height:34px;border-radius:8px;
  border:1.5px solid #e2e8f0;
  background:#fff;color:#475569;
  font-family:'Poppins',sans-serif;font-size:.82rem;font-weight:500;
  cursor:pointer;text-decoration:none;
  transition:all .2s;
}
.page-btn:hover{border-color:#3282b8;color:#3282b8;background:#eff6ff}
.page-btn.active{
  background:#3282b8;border-color:#3282b8;color:#fff;font-weight:700;
}
.page-btn.disabled{opacity:.4;pointer-events:none}

/* ── Modal ───────────────────────────────────────────────────── */
.modal-overlay{
  display:none;position:fixed;inset:0;
  background:rgba(0,0,0,.45);
  z-index:200;
  align-items:center;justify-content:center;
  backdrop-filter:blur(4px);
}
.modal-overlay.open{display:flex}
.modal{
  background:#fff;border-radius:20px;
  width:100%;max-width:600px;
  max-height:90vh;overflow-y:auto;
  padding:32px;
  box-shadow:0 24px 60px rgba(0,0,0,.18);
  animation:modalIn .25s ease;
}
@keyframes modalIn{
  from{opacity:0;transform:translateY(-20px) scale(.97)}
  to{opacity:1;transform:translateY(0) scale(1)}
}
.modal-header{
  display:flex;align-items:center;justify-content:space-between;
  margin-bottom:24px;
}
.modal-header h3{font-size:1.1rem;font-weight:700;color:#0f172a}
.modal-close{
  background:none;border:none;cursor:pointer;
  font-size:1.2rem;color:#94a3b8;transition:color .2s;
  display:flex;align-items:center;justify-content:center;
  width:32px;height:32px;border-radius:8px;
}
.modal-close:hover{color:#ef4444;background:#fef2f2}
.form-group{margin-bottom:16px}
.form-group label{
  display:block;font-size:.82rem;font-weight:600;
  color:#374151;margin-bottom:6px;
}
.form-group label span{color:#ef4444}
.form-control{
  width:100%;
  padding:11px 14px;
  border:1.5px solid #e2e8f0;border-radius:10px;
  font-family:'Poppins',sans-serif;font-size:.85rem;
  color:#1e293b;background:#fff;outline:none;
  transition:border-color .2s,box-shadow .2s;
}
.form-control:focus{
  border-color:#3282b8;
  box-shadow:0 0 0 3px rgba(50,130,184,.12);
}
textarea.form-control{resize:vertical;min-height:90px}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.form-footer{
  display:flex;gap:10px;justify-content:flex-end;
  margin-top:24px;padding-top:20px;
  border-top:1px solid #f1f5f9;
}
.btn-cancel{
  padding:10px 22px;border:1.5px solid #e2e8f0;border-radius:10px;
  background:#fff;color:#64748b;
  font-family:'Poppins',sans-serif;font-size:.88rem;font-weight:500;
  cursor:pointer;transition:all .2s;
}
.btn-cancel:hover{border-color:#94a3b8;color:#334155}
.btn-save{
  padding:10px 26px;
  background:linear-gradient(135deg,#1b6ca8,#3282b8);
  color:#fff;border:none;border-radius:10px;
  font-family:'Poppins',sans-serif;font-size:.88rem;font-weight:600;
  cursor:pointer;transition:all .25s;
  box-shadow:0 4px 14px rgba(27,108,168,.3);
}
.btn-save:hover{
  background:linear-gradient(135deg,#0f4c75,#1b6ca8);
  transform:translateY(-1px);
  box-shadow:0 6px 20px rgba(27,108,168,.4);
}

/* Confirm modal */
.modal-sm{max-width:420px}
.confirm-icon{
  width:60px;height:60px;border-radius:50%;
  background:#fef2f2;display:flex;align-items:center;justify-content:center;
  font-size:1.5rem;color:#ef4444;margin:0 auto 16px;
}
.confirm-title{font-size:1.05rem;font-weight:700;color:#0f172a;text-align:center;margin-bottom:8px}
.confirm-text{font-size:.85rem;color:#64748b;text-align:center;margin-bottom:24px;line-height:1.6}
.confirm-footer{display:flex;gap:10px;justify-content:center}
.btn-confirm-delete{
  padding:10px 26px;
  background:#ef4444;color:#fff;
  border:none;border-radius:10px;
  font-family:'Poppins',sans-serif;font-size:.88rem;font-weight:600;
  cursor:pointer;transition:all .2s;
}
.btn-confirm-delete:hover{background:#dc2626;transform:translateY(-1px)}

/* Alert */
.alert{
  display:flex;align-items:center;gap:12px;
  padding:14px 18px;border-radius:12px;
  font-size:.85rem;font-weight:500;
  margin-bottom:20px;animation:fadeInUp .4s ease;
}
.alert-success{background:#d1fae5;color:#065f46;border:1px solid #a7f3d0}
.alert-error  {background:#fee2e2;color:#991b1b;border:1px solid #fca5a5}
.alert i{font-size:1rem;flex-shrink:0}
.alert-close{
  margin-left:auto;background:none;border:none;cursor:pointer;
  color:inherit;opacity:.6;font-size:.9rem;
}
.alert-close:hover{opacity:1}

/* ── Responsive ──────────────────────────────────────────────── */
@media(max-width:768px){
  .sidebar{transform:translateX(-100%)}
  .sidebar.open{transform:translateX(0)}
  .main-content{margin-left:0}
  .mobile-toggle{display:block}
  .topbar{padding:12px 16px}
  .page-content{padding:20px 16px 32px}
  .form-row{grid-template-columns:1fr}
  .modal{padding:24px 18px;margin:16px}
}
@media(max-width:480px){
  .toolbar{flex-direction:column;align-items:stretch}
  .search-box{max-width:100%}
  .btn-tambah{justify-content:center}
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
        <a href="dashboard.php" class="nav-link" id="nav-dashboard">
          <i class="fas fa-th-large"></i>
          <span>Dashboard</span>
        </a>
      </li>
      <li class="nav-item">
        <a href="kelola_program.php" class="nav-link active" id="nav-program">
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
        <a href="#" class="nav-link" id="nav-verifikasi">
          <i class="fas fa-clipboard-check"></i>
          <span>Verifikasi Donasi</span>
        </a>
      </li>
    </ul>
  </nav>

  <div class="sidebar-footer">
    <a href="../Backend/Auth/logout.php" class="nav-link" id="nav-logout">
      <i class="fas fa-sign-out-alt"></i>
      <span>Logout</span>
    </a>
  </div>
</aside>

<!-- Overlay mobile -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- ═══ MAIN CONTENT ══════════════════════════════════════════ -->
<div class="main-content">

  <!-- Top Bar -->
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

  <!-- Page Content -->
  <main class="page-content">

    <!-- Page Title -->
    <div class="page-title">
      <h1>Kelola Program Donasi</h1>
      <p>Kelola informasi program donasi yang tersedia</p>
    </div>

    <!-- Alert -->
    <?php if ($alertMsg): ?>
    <div class="alert alert-<?= $alertType === 'success' ? 'success' : 'error' ?>" id="alertBox">
      <i class="fas fa-<?= $alertType === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
      <?= htmlspecialchars($alertMsg) ?>
      <button class="alert-close" onclick="document.getElementById('alertBox').remove()">
        <i class="fas fa-times"></i>
      </button>
    </div>
    <?php endif; ?>

    <!-- Toolbar -->
    <form method="GET" id="filterForm">
      <div class="toolbar">
        <div class="search-box">
          <i class="fas fa-search"></i>
          <input type="text" name="q" id="searchInput"
                 placeholder="Cari Program...."
                 value="<?= htmlspecialchars($search) ?>"
                 autocomplete="off">
        </div>
        <select name="status" class="filter-select" onchange="document.getElementById('filterForm').submit()">
          <option value="">Semua Status</option>
          <?php foreach(['menunggu'=>'Menunggu','disetujui'=>'Disetujui','berlangsung'=>'Aktif','selesai'=>'Selesai','ditolak'=>'Ditolak','dibatalkan'=>'Dibatalkan'] as $val=>$lbl): ?>
          <option value="<?= $val ?>" <?= $filterStatus===$val?'selected':'' ?>><?= $lbl ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit" style="display:none"></button>
      </div>
    </form>

    <!-- Tambah Button (outside form) -->
    <div style="display:flex;justify-content:flex-end;margin-top:-52px;margin-bottom:20px">
      <button class="btn-tambah" id="btnTambah">
        <i class="fas fa-plus"></i> Tambah Program
      </button>
    </div>

    <!-- Table Panel -->
    <div class="table-panel">
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th class="col-no">No</th>
              <th>Nama Program</th>
              <th>Kategori</th>
              <th>Tanggal Mulai</th>
              <th>Target Dana</th>
              <th>Status</th>
              <th class="col-aksi">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($programs)): ?>
            <tr>
              <td colspan="7" class="no-data">
                <i class="fas fa-inbox" style="font-size:2rem;display:block;margin-bottom:8px;opacity:.3"></i>
                Tidak ada program yang ditemukan
              </td>
            </tr>
            <?php else: ?>
            <?php foreach ($programs as $i => $prog): ?>
            <tr>
              <td class="col-no"><?= $offset + $i + 1 ?></td>
              <td class="col-judul">
                <?= htmlspecialchars($prog['judul']) ?>
                <?php if ($prog['nama_pengusul']): ?>
                <small><i class="fas fa-user" style="font-size:.6rem"></i> <?= htmlspecialchars($prog['nama_pengusul']) ?></small>
                <?php endif; ?>
              </td>
              <td><?= htmlspecialchars($prog['kategori']) ?></td>
              <td><?= $prog['tanggal_mulai'] ? date('d M Y', strtotime($prog['tanggal_mulai'])) : '-' ?></td>
              <td><?= rpK($prog['target_dana']) ?></td>
              <td><?= statusBadge($prog['status_program']) ?></td>
              <td class="col-aksi">
                <!-- Edit -->
                <button class="btn-action btn-edit"
                  title="Edit"
                  onclick="openEditModal(<?= htmlspecialchars(json_encode($prog), ENT_QUOTES) ?>)">
                  <i class="fas fa-pen"></i>
                </button>
                <!-- Hapus -->
                <button class="btn-action btn-hapus"
                  title="Hapus"
                  onclick="openDeleteModal(<?= $prog['id_program'] ?>, '<?= htmlspecialchars(addslashes($prog['judul'])) ?>')">
                  <i class="fas fa-trash"></i>
                </button>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="pagination-wrap">
        <div class="pagination-info">
          Menampilkan <?= min($offset+1,$totalRows) ?>–<?= min($offset+$perPage,$totalRows) ?> dari <?= $totalRows ?> program
        </div>
        <div class="pagination">
          <!-- Prev -->
          <a href="?q=<?= urlencode($search) ?>&status=<?= $filterStatus ?>&page=<?= $page-1 ?>"
             class="page-btn <?= $page<=1?'disabled':'' ?>">
            <i class="fas fa-chevron-left"></i>
          </a>
          <!-- Pages -->
          <?php
            $start = max(1, $page-2);
            $end   = min($totalPages, $page+2);
            for ($p=$start; $p<=$end; $p++):
          ?>
          <a href="?q=<?= urlencode($search) ?>&status=<?= $filterStatus ?>&page=<?= $p ?>"
             class="page-btn <?= $p===$page?'active':'' ?>"><?= $p ?></a>
          <?php endfor; ?>
          <!-- Next -->
          <a href="?q=<?= urlencode($search) ?>&status=<?= $filterStatus ?>&page=<?= $page+1 ?>"
             class="page-btn <?= $page>=$totalPages?'disabled':'' ?>">
            <i class="fas fa-chevron-right"></i>
          </a>
        </div>
      </div>
    </div><!-- /table-panel -->

  </main>
</div><!-- /main-content -->

<!-- ═══ MODAL: TAMBAH PROGRAM ════════════════════════════════ -->
<div class="modal-overlay" id="modalTambah">
  <div class="modal">
    <div class="modal-header">
      <h3><i class="fas fa-plus-circle" style="color:#3282b8;margin-right:8px"></i>Tambah Program Donasi</h3>
      <button class="modal-close" onclick="closeModal('modalTambah')">
        <i class="fas fa-times"></i>
      </button>
    </div>
    <form method="POST" enctype="multipart/form-data" id="formTambah">
      <input type="hidden" name="aksi" value="tambah">
      <div class="form-group">
        <label>Judul Program <span>*</span></label>
        <input type="text" name="judul" class="form-control" placeholder="Masukkan judul program" required>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Kategori <span>*</span></label>
          <select name="kategori" class="form-control" required>
            <option value="">-- Pilih Kategori --</option>
            <option>Pendidikan</option>
            <option>Kesehatan</option>
            <option>Keagamaan</option>
            <option>Sosial</option>
            <option>Bencana Alam</option>
            <option>Lingkungan</option>
            <option>Lainnya</option>
          </select>
        </div>
        <div class="form-group">
          <label>Target Dana (Rp)</label>
          <input type="number" name="target_dana" class="form-control" placeholder="0" min="0">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Tanggal Mulai <span>*</span></label>
          <input type="date" name="tanggal_mulai" class="form-control" required>
        </div>
        <div class="form-group">
          <label>Tanggal Selesai</label>
          <input type="date" name="tanggal_selesai" class="form-control">
        </div>
      </div>
      <div class="form-group">
        <label>Deskripsi</label>
        <textarea name="deskripsi" class="form-control" placeholder="Deskripsi program donasi..."></textarea>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Foto Program</label>
          <input type="file" name="foto_program" class="form-control" accept="image/*">
        </div>
        <div class="form-group">
          <label>Status</label>
          <select name="status_program" class="form-control">
            <option value="menunggu">Menunggu</option>
            <option value="disetujui">Disetujui</option>
            <option value="berlangsung" selected>Aktif</option>
          </select>
        </div>
      </div>
      <div class="form-footer">
        <button type="button" class="btn-cancel" onclick="closeModal('modalTambah')">Batal</button>
        <button type="submit" class="btn-save">
          <i class="fas fa-save" style="margin-right:6px"></i>Simpan Program
        </button>
      </div>
    </form>
  </div>
</div>

<!-- ═══ MODAL: EDIT PROGRAM ══════════════════════════════════ -->
<div class="modal-overlay" id="modalEdit">
  <div class="modal">
    <div class="modal-header">
      <h3><i class="fas fa-pen" style="color:#3b82f6;margin-right:8px"></i>Edit Program Donasi</h3>
      <button class="modal-close" onclick="closeModal('modalEdit')">
        <i class="fas fa-times"></i>
      </button>
    </div>
    <form method="POST" id="formEdit">
      <input type="hidden" name="aksi" value="edit">
      <input type="hidden" name="id_program" id="edit_id">
      <div class="form-group">
        <label>Judul Program <span>*</span></label>
        <input type="text" name="judul" id="edit_judul" class="form-control" required>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Kategori <span>*</span></label>
          <select name="kategori" id="edit_kategori" class="form-control" required>
            <option value="">-- Pilih Kategori --</option>
            <option>Pendidikan</option>
            <option>Kesehatan</option>
            <option>Keagamaan</option>
            <option>Sosial</option>
            <option>Bencana Alam</option>
            <option>Lingkungan</option>
            <option>Lainnya</option>
          </select>
        </div>
        <div class="form-group">
          <label>Target Dana (Rp)</label>
          <input type="number" name="target_dana" id="edit_target" class="form-control" min="0">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Tanggal Mulai <span>*</span></label>
          <input type="date" name="tanggal_mulai" id="edit_tgl_mulai" class="form-control" required>
        </div>
        <div class="form-group">
          <label>Tanggal Selesai</label>
          <input type="date" name="tanggal_selesai" id="edit_tgl_selesai" class="form-control">
        </div>
      </div>
      <div class="form-group">
        <label>Deskripsi</label>
        <textarea name="deskripsi" id="edit_deskripsi" class="form-control"></textarea>
      </div>
      <div class="form-group">
        <label>Status</label>
        <select name="status_program" id="edit_status" class="form-control">
          <option value="menunggu">Menunggu</option>
          <option value="disetujui">Disetujui</option>
          <option value="berlangsung">Aktif</option>
          <option value="selesai">Selesai</option>
          <option value="ditolak">Ditolak</option>
          <option value="dibatalkan">Dibatalkan</option>
        </select>
      </div>
      <div class="form-footer">
        <button type="button" class="btn-cancel" onclick="closeModal('modalEdit')">Batal</button>
        <button type="submit" class="btn-save">
          <i class="fas fa-save" style="margin-right:6px"></i>Perbarui Program
        </button>
      </div>
    </form>
  </div>
</div>

<!-- ═══ MODAL: KONFIRMASI HAPUS ══════════════════════════════ -->
<div class="modal-overlay" id="modalHapus">
  <div class="modal modal-sm">
    <form method="POST" id="formHapus">
      <input type="hidden" name="aksi" value="hapus">
      <input type="hidden" name="id_program" id="hapus_id">
      <div class="confirm-icon">
        <i class="fas fa-trash-alt"></i>
      </div>
      <div class="confirm-title">Hapus Program?</div>
      <div class="confirm-text">
        Anda akan menghapus program <strong id="hapus_nama"></strong>.<br>
        Tindakan ini tidak dapat dibatalkan.
      </div>
      <div class="confirm-footer">
        <button type="button" class="btn-cancel" onclick="closeModal('modalHapus')">Batal</button>
        <button type="submit" class="btn-confirm-delete">
          <i class="fas fa-trash-alt" style="margin-right:6px"></i>Ya, Hapus
        </button>
      </div>
    </form>
  </div>
</div>

<script>
// ── Sidebar mobile toggle ──
const sidebar      = document.getElementById('sidebar');
const overlay      = document.getElementById('sidebarOverlay');
const mobileToggle = document.getElementById('mobileToggle');

if (mobileToggle) {
  mobileToggle.addEventListener('click', () => {
    sidebar.classList.toggle('open');
    overlay.classList.toggle('active');
  });
}
overlay.addEventListener('click', () => {
  sidebar.classList.remove('open');
  overlay.classList.remove('active');
});

// ── Modal helpers ──
function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

// Close modal on overlay click
document.querySelectorAll('.modal-overlay').forEach(el => {
  el.addEventListener('click', e => { if(e.target===el) el.classList.remove('open'); });
});

// ── Tambah ──
document.getElementById('btnTambah').addEventListener('click', () => openModal('modalTambah'));

// ── Edit ──
function openEditModal(data) {
  document.getElementById('edit_id').value        = data.id_program;
  document.getElementById('edit_judul').value     = data.judul;
  document.getElementById('edit_target').value    = data.target_dana;
  document.getElementById('edit_deskripsi').value = data.deskripsi || '';
  document.getElementById('edit_tgl_mulai').value = data.tanggal_mulai || '';
  document.getElementById('edit_tgl_selesai').value = data.tanggal_selesai || '';
  document.getElementById('edit_status').value    = data.status_program;

  // Set kategori
  const katSel = document.getElementById('edit_kategori');
  for (let opt of katSel.options) {
    if (opt.value === data.kategori) { opt.selected = true; break; }
  }

  openModal('modalEdit');
}

// ── Hapus ──
function openDeleteModal(id, nama) {
  document.getElementById('hapus_id').value = id;
  document.getElementById('hapus_nama').textContent = nama;
  openModal('modalHapus');
}

// ── Live search (debounce) ──
let searchTimer;
document.getElementById('searchInput').addEventListener('input', function() {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => document.getElementById('filterForm').submit(), 500);
});

// ── Auto dismiss alert ──
setTimeout(() => {
  const a = document.getElementById('alertBox');
  if (a) a.style.opacity = '0', setTimeout(()=>a.remove(), 400);
  const el = document.getElementById('alertBox');
  if (el) el.style.transition = 'opacity .4s';
}, 4000);
</script>

</body>
</html>
