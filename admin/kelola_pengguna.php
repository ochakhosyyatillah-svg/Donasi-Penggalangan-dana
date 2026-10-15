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
$perPage = 10;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;
$search  = trim($_GET['q'] ?? '');
$filterRole   = $_GET['role']   ?? '';
$filterStatus = $_GET['status'] ?? '';

// ── Handle Aksi POST ──────────────────────────────────────────────
$alertMsg  = '';
$alertType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    // ── Hapus Pengguna ──
    if ($aksi === 'hapus') {
        $id = (int)($_POST['id_user'] ?? 0);
        if ($id > 0 && $id !== (int)$_SESSION['id_user']) {
            $pdo->prepare("DELETE FROM users WHERE id_user = ?")->execute([$id]);
            $alertMsg  = 'Pengguna berhasil dihapus.';
            $alertType = 'success';
        } else {
            $alertMsg  = 'Tidak dapat menghapus akun sendiri.';
            $alertType = 'error';
        }
    }

    // ── Ubah Status Verifikasi ──
    if ($aksi === 'ubah_status') {
        $id     = (int)($_POST['id_user'] ?? 0);
        $status = $_POST['status_baru'] ?? '';
        $valid  = ['menunggu', 'diterima', 'ditolak'];
        if ($id > 0 && in_array($status, $valid)) {
            $pdo->prepare("UPDATE users SET status_verifikasi = ? WHERE id_user = ?")->execute([$status, $id]);
            $alertMsg  = 'Status pengguna berhasil diperbarui.';
            $alertType = 'success';
        }
    }

    // ── Tambah Pengguna ──
    if ($aksi === 'tambah') {
        $nama     = trim($_POST['nama'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role     = $_POST['role'] ?? 'pengusul';
        $no_hp    = trim($_POST['no_hp'] ?? '');
        $status   = $_POST['status_verifikasi'] ?? 'diterima';

        if ($nama && $email && $password) {
            // Cek duplikasi email
            $cek = $pdo->prepare("SELECT id_user FROM users WHERE email = ?");
            $cek->execute([$email]);
            if ($cek->fetch()) {
                $alertMsg  = 'Email sudah terdaftar. Gunakan email lain.';
                $alertType = 'error';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $pdo->prepare("
                    INSERT INTO users (nama, email, password, no_hp, role, status_verifikasi)
                    VALUES (?, ?, ?, ?, ?, ?)
                ")->execute([$nama, $email, $hash, $no_hp, $role, $status]);
                $alertMsg  = 'Pengguna baru berhasil ditambahkan.';
                $alertType = 'success';
            }
        } else {
            $alertMsg  = 'Nama, email, dan password wajib diisi.';
            $alertType = 'error';
        }
    }

    // ── Edit Pengguna ──
    if ($aksi === 'edit') {
        $id     = (int)($_POST['id_user'] ?? 0);
        $nama   = trim($_POST['nama'] ?? '');
        $email  = trim($_POST['email'] ?? '');
        $role   = $_POST['role'] ?? '';
        $no_hp  = trim($_POST['no_hp'] ?? '');
        $status = $_POST['status_verifikasi'] ?? '';
        $catatan = trim($_POST['catatan_verifikasi'] ?? '');

        if ($id > 0 && $nama && $email) {
            // Cek duplikasi email (selain diri sendiri)
            $cek = $pdo->prepare("SELECT id_user FROM users WHERE email = ? AND id_user != ?");
            $cek->execute([$email, $id]);
            if ($cek->fetch()) {
                $alertMsg  = 'Email sudah digunakan oleh pengguna lain.';
                $alertType = 'error';
            } else {
                // Update password jika diisi
                $newPass = trim($_POST['password_baru'] ?? '');
                if ($newPass !== '') {
                    $hash = password_hash($newPass, PASSWORD_DEFAULT);
                    $pdo->prepare("
                        UPDATE users SET nama=?, email=?, password=?, no_hp=?, role=?,
                               status_verifikasi=?, catatan_verifikasi=?
                        WHERE id_user=?
                    ")->execute([$nama, $email, $hash, $no_hp, $role, $status, $catatan, $id]);
                } else {
                    $pdo->prepare("
                        UPDATE users SET nama=?, email=?, no_hp=?, role=?,
                               status_verifikasi=?, catatan_verifikasi=?
                        WHERE id_user=?
                    ")->execute([$nama, $email, $no_hp, $role, $status, $catatan, $id]);
                }
                $alertMsg  = 'Data pengguna berhasil diperbarui.';
                $alertType = 'success';
            }
        } else {
            $alertMsg  = 'Nama dan email wajib diisi.';
            $alertType = 'error';
        }
    }
}

// ── Ambil Data Pengguna ───────────────────────────────────────────
try {
    $where  = '1=1';
    $params = [];

    if ($search !== '') {
        $where   .= ' AND (nama LIKE ? OR email LIKE ?)';
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    if ($filterRole !== '') {
        $where   .= ' AND role = ?';
        $params[] = $filterRole;
    }
    if ($filterStatus !== '') {
        $where   .= ' AND status_verifikasi = ?';
        $params[] = $filterStatus;
    }

    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM users WHERE $where");
    $stmtCount->execute($params);
    $totalRows  = (int)$stmtCount->fetchColumn();
    $totalPages = max(1, (int)ceil($totalRows / $perPage));

    $perPageInt = (int)$perPage;
    $offsetInt  = (int)$offset;
    $stmtUsers  = $pdo->prepare("
        SELECT * FROM users WHERE $where
        ORDER BY dibuat_pada DESC
        LIMIT $perPageInt OFFSET $offsetInt
    ");
    $stmtUsers->execute($params);
    $users = $stmtUsers->fetchAll();

} catch (PDOException $e) {
    error_log($e->getMessage());
    $users      = [];
    $totalRows  = 0;
    $totalPages = 1;
}

// ── Helpers ───────────────────────────────────────────────────────
function roleBadge($r) {
    $map = [
        'admin'    => ['Admin',    'badge-purple'],
        'pengusul' => ['Pengusul', 'badge-blue'],
        'donatur'  => ['Donatur',  'badge-teal'],
    ];
    $d = $map[$r] ?? [ucfirst($r), 'badge-secondary'];
    return '<span class="badge-status '.$d[1].'">'.$d[0].'</span>';
}
function statusVerifBadge($s) {
    $map = [
        'diterima' => ['Aktif',     'badge-success'],
        'menunggu' => ['Menunggu',  'badge-warning'],
        'ditolak'  => ['Ditolak',   'badge-danger'],
    ];
    $d = $map[$s] ?? [ucfirst($s), 'badge-secondary'];
    return '<span class="badge-status '.$d[1].'">'.$d[0].'</span>';
}
function avatarColor($name) {
    $colors = ['#3b82f6','#10b981','#f59e0b','#8b5cf6','#ef4444','#06b6d4','#f97316'];
    return $colors[crc32($name) % count($colors)];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kelola Pengguna – SIBERASA Admin</title>
<meta name="description" content="Halaman kelola pengguna admin SIBERASA">

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
  width:260px;min-height:100vh;
  background:linear-gradient(180deg,#0f4c75 0%,#1b6ca8 50%,#3282b8 100%);
  display:flex;flex-direction:column;padding:0;
  position:fixed;top:0;left:0;bottom:0;z-index:100;
  transition:transform .3s cubic-bezier(.4,0,.2,1);
  box-shadow:4px 0 24px rgba(0,0,0,.12);
}
.sidebar-header{
  display:flex;align-items:center;justify-content:space-between;
  padding:20px 20px 0;
}
.sidebar-logo{display:flex;align-items:center;gap:10px}
.sidebar-logo-img{
  height:54px;width:auto;max-width:200px;object-fit:contain;
  filter:drop-shadow(0 2px 8px rgba(0,0,0,.25)) brightness(1.05);
  transition:transform .2s ease;
}
.sidebar-logo-img:hover{transform:scale(1.04)}

/* User card */
.sidebar-user{
  display:flex;align-items:center;gap:12px;
  margin:24px 20px 8px;padding:14px 16px;
  background:rgba(255,255,255,.12);border-radius:12px;
  backdrop-filter:blur(8px);
}
.sidebar-user .avatar{
  width:42px;height:42px;border-radius:50%;
  background:linear-gradient(135deg,#4fc3f7,#0288d1);
  display:flex;align-items:center;justify-content:center;
  font-weight:700;font-size:1rem;color:#fff;flex-shrink:0;
  box-shadow:0 2px 8px rgba(0,0,0,.15);
}
.sidebar-user .user-role{
  font-size:.72rem;font-weight:600;color:rgba(255,255,255,.9);
  text-transform:uppercase;letter-spacing:1px;
}
.sidebar-user .user-name{
  font-size:.82rem;color:rgba(255,255,255,.65);
  white-space:nowrap;overflow:hidden;text-overflow:ellipsis;
}

/* Nav */
.sidebar-nav{flex:1;padding:16px 12px 0}
.sidebar-nav .nav-item{margin-bottom:4px}
.sidebar-nav .nav-link{
  display:flex;align-items:center;gap:12px;
  padding:12px 16px;border-radius:10px;
  font-size:.88rem;font-weight:500;color:rgba(255,255,255,.75);
  transition:all .25s ease;cursor:pointer;position:relative;
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
  content:'';position:absolute;left:0;top:50%;transform:translateY(-50%);
  width:4px;height:60%;background:#4fc3f7;border-radius:0 4px 4px 0;
}

/* Logout */
.sidebar-footer{
  padding:16px 12px 24px;border-top:1px solid rgba(255,255,255,.1);margin:0 12px;
}
.sidebar-footer .nav-link{
  display:flex;align-items:center;gap:12px;padding:12px 16px;border-radius:10px;
  font-size:.88rem;font-weight:500;color:rgba(255,255,255,.65);
  transition:all .25s ease;cursor:pointer;
}
.sidebar-footer .nav-link:hover{background:rgba(239,68,68,.15);color:#fca5a5}

/* Overlay mobile */
.sidebar-overlay{
  display:none;position:fixed;inset:0;background:rgba(0,0,0,.4);
  z-index:90;backdrop-filter:blur(2px);
}
.sidebar-overlay.active{display:block}

/* ── Main Content ─────────────────────────────────────────────── */
.main-content{flex:1;margin-left:260px;min-height:100vh;display:flex;flex-direction:column}

/* Topbar */
.topbar{
  display:flex;align-items:center;justify-content:space-between;
  padding:16px 32px;background:#fff;border-bottom:1px solid #e2e8f0;
  position:sticky;top:0;z-index:50;box-shadow:0 1px 4px rgba(0,0,0,.04);
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

/* ── Toolbar ─────────────────────────────────────────────────── */
.toolbar{
  display:flex;align-items:center;gap:12px;
  margin-bottom:20px;flex-wrap:wrap;
}
.search-box{
  position:relative;flex:1;min-width:200px;max-width:440px;
}
.search-box input{
  width:100%;padding:11px 16px 11px 42px;
  border:1.5px solid #e2e8f0;border-radius:10px;
  font-family:'Poppins',sans-serif;font-size:.85rem;
  background:#fff;outline:none;transition:border-color .2s,box-shadow .2s;
}
.search-box input:focus{
  border-color:#3282b8;box-shadow:0 0 0 3px rgba(50,130,184,.12);
}
.search-box i{
  position:absolute;left:14px;top:50%;transform:translateY(-50%);
  color:#94a3b8;font-size:.85rem;
}
.filter-select{
  padding:10px 14px;border:1.5px solid #e2e8f0;border-radius:10px;
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
  box-shadow:0 4px 14px rgba(27,108,168,.35);white-space:nowrap;
}
.btn-tambah:hover{
  background:linear-gradient(135deg,#0f4c75,#1b6ca8);
  transform:translateY(-2px);box-shadow:0 6px 20px rgba(27,108,168,.45);
}

/* ── Table Panel ─────────────────────────────────────────────── */
.table-panel{
  background:#fff;border-radius:16px;
  box-shadow:0 1px 8px rgba(0,0,0,.06);
  border:2px solid #3282b8;overflow:hidden;
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
  padding:14px 16px;font-size:.78rem;font-weight:700;
  color:#1b6ca8;text-align:left;letter-spacing:.4px;
  text-transform:uppercase;white-space:nowrap;
}
tbody tr{border-bottom:1.5px dashed #d0e8f8;transition:background .2s}
tbody tr:last-child{border-bottom:none}
tbody tr:hover{background:#f5faff}
tbody td{padding:14px 16px;font-size:.85rem;color:#334155;vertical-align:middle}
td.no-data{
  text-align:center;padding:48px 16px;color:#94a3b8;font-size:.9rem;
}
.col-no{width:52px;text-align:center;font-weight:600;color:#94a3b8}
.col-aksi{width:100px;white-space:nowrap;text-align:center}

/* User cell with avatar */
.user-cell{display:flex;align-items:center;gap:10px}
.user-avatar{
  width:36px;height:36px;border-radius:50%;
  display:flex;align-items:center;justify-content:center;
  font-weight:700;font-size:.85rem;color:#fff;flex-shrink:0;
}
.user-cell-info .name{font-weight:600;color:#1e293b;font-size:.86rem}
.user-cell-info .hp{font-size:.72rem;color:#94a3b8;margin-top:1px}

/* Badge status */
.badge-status{
  display:inline-flex;align-items:center;
  padding:4px 12px;border-radius:20px;
  font-size:.7rem;font-weight:700;letter-spacing:.3px;white-space:nowrap;
}
.badge-success  {background:#d1fae5;color:#059669}
.badge-warning  {background:#fef3c7;color:#b45309}
.badge-danger   {background:#fee2e2;color:#dc2626}
.badge-info     {background:#dbeafe;color:#1d4ed8}
.badge-secondary{background:#f1f5f9;color:#64748b}
.badge-purple   {background:#ede9fe;color:#7c3aed}
.badge-blue     {background:#dbeafe;color:#1d4ed8}
.badge-teal     {background:#ccfbf1;color:#0f766e}

/* Action buttons */
.btn-action{
  display:inline-flex;align-items:center;justify-content:center;
  width:34px;height:34px;border-radius:8px;
  border:none;cursor:pointer;font-size:.85rem;
  transition:all .2s ease;
}
.btn-edit {background:#eff6ff;color:#3b82f6}
.btn-edit:hover{background:#3b82f6;color:#fff;transform:scale(1.1)}
.btn-hapus{background:#fef2f2;color:#ef4444}
.btn-hapus:hover{background:#ef4444;color:#fff;transform:scale(1.1)}

/* ── Stat chips di atas tabel ────────────────────────────────── */
.stat-chips{
  display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px;
}
.stat-chip{
  display:flex;align-items:center;gap:6px;
  padding:8px 16px;border-radius:10px;background:#fff;
  border:1.5px solid #e2e8f0;
  font-size:.78rem;font-weight:600;color:#475569;
  box-shadow:0 1px 4px rgba(0,0,0,.04);
  transition:border-color .2s;
}
.stat-chip .chip-count{
  font-size:1.05rem;font-weight:700;color:#0f172a;
}
.stat-chip.total  {border-color:#3282b8;color:#1b6ca8}
.stat-chip.aktif  {border-color:#10b981;color:#059669}
.stat-chip.menunggu{border-color:#f59e0b;color:#b45309}
.stat-chip.ditolak{border-color:#ef4444;color:#dc2626}

/* ── Pagination ──────────────────────────────────────────────── */
.pagination-wrap{
  display:flex;align-items:center;justify-content:space-between;
  padding:16px 20px;border-top:1px solid #e8f2fb;background:#fafcff;
  flex-wrap:wrap;gap:8px;
}
.pagination-info{font-size:.78rem;color:#64748b}
.pagination{display:flex;align-items:center;gap:4px}
.page-btn{
  display:inline-flex;align-items:center;justify-content:center;
  width:34px;height:34px;border-radius:8px;
  border:1.5px solid #e2e8f0;background:#fff;color:#475569;
  font-family:'Poppins',sans-serif;font-size:.82rem;font-weight:500;
  cursor:pointer;text-decoration:none;transition:all .2s;
}
.page-btn:hover{border-color:#3282b8;color:#3282b8;background:#eff6ff}
.page-btn.active{background:#3282b8;border-color:#3282b8;color:#fff;font-weight:700}
.page-btn.disabled{opacity:.4;pointer-events:none}

/* ── Modal ───────────────────────────────────────────────────── */
.modal-overlay{
  display:none;position:fixed;inset:0;
  background:rgba(0,0,0,.45);z-index:200;
  align-items:center;justify-content:center;backdrop-filter:blur(4px);
}
.modal-overlay.open{display:flex}
.modal{
  background:#fff;border-radius:20px;width:100%;max-width:560px;
  max-height:90vh;overflow-y:auto;padding:32px;
  box-shadow:0 24px 60px rgba(0,0,0,.18);animation:modalIn .25s ease;
}
@keyframes modalIn{
  from{opacity:0;transform:translateY(-20px) scale(.97)}
  to{opacity:1;transform:translateY(0) scale(1)}
}
.modal-header{
  display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;
}
.modal-header h3{font-size:1.1rem;font-weight:700;color:#0f172a}
.modal-close{
  background:none;border:none;cursor:pointer;font-size:1.2rem;color:#94a3b8;
  display:flex;align-items:center;justify-content:center;
  width:32px;height:32px;border-radius:8px;transition:all .2s;
}
.modal-close:hover{color:#ef4444;background:#fef2f2}
.form-group{margin-bottom:16px}
.form-group label{
  display:block;font-size:.82rem;font-weight:600;color:#374151;margin-bottom:6px;
}
.form-group label span{color:#ef4444}
.form-control{
  width:100%;padding:11px 14px;
  border:1.5px solid #e2e8f0;border-radius:10px;
  font-family:'Poppins',sans-serif;font-size:.85rem;
  color:#1e293b;background:#fff;outline:none;transition:border-color .2s,box-shadow .2s;
}
.form-control:focus{border-color:#3282b8;box-shadow:0 0 0 3px rgba(50,130,184,.12)}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.form-hint{font-size:.72rem;color:#94a3b8;margin-top:4px}
.form-footer{
  display:flex;gap:10px;justify-content:flex-end;
  margin-top:24px;padding-top:20px;border-top:1px solid #f1f5f9;
}
.btn-cancel{
  padding:10px 22px;border:1.5px solid #e2e8f0;border-radius:10px;
  background:#fff;color:#64748b;
  font-family:'Poppins',sans-serif;font-size:.88rem;font-weight:500;cursor:pointer;
  transition:all .2s;
}
.btn-cancel:hover{border-color:#94a3b8;color:#334155}
.btn-save{
  padding:10px 26px;
  background:linear-gradient(135deg,#1b6ca8,#3282b8);
  color:#fff;border:none;border-radius:10px;
  font-family:'Poppins',sans-serif;font-size:.88rem;font-weight:600;
  cursor:pointer;transition:all .25s;box-shadow:0 4px 14px rgba(27,108,168,.3);
}
.btn-save:hover{
  background:linear-gradient(135deg,#0f4c75,#1b6ca8);
  transform:translateY(-1px);box-shadow:0 6px 20px rgba(27,108,168,.4);
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
  padding:10px 26px;background:#ef4444;color:#fff;
  border:none;border-radius:10px;
  font-family:'Poppins',sans-serif;font-size:.88rem;font-weight:600;
  cursor:pointer;transition:all .2s;
}
.btn-confirm-delete:hover{background:#dc2626;transform:translateY(-1px)}

/* Alert */
.alert{
  display:flex;align-items:center;gap:12px;
  padding:14px 18px;border-radius:12px;font-size:.85rem;font-weight:500;
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
  .stat-chips{gap:6px}
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
      <img src="/logo%20donasi.png" alt="SIBERASA Logo"
           class="sidebar-logo-img" onerror="this.style.display='none'">
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
        <a href="kelola_pengguna.php" class="nav-link active" id="nav-pengguna">
          <i class="fas fa-users"></i><span>Kelola Pengguna</span>
        </a>
      </li>
      <li class="nav-item">
        <a href="#" class="nav-link" id="nav-verifikasi">
          <i class="fas fa-clipboard-check"></i><span>Verifikasi Donasi</span>
        </a>
      </li>
    </ul>
  </nav>

  <div class="sidebar-footer">
    <a href="../Backend/Auth/logout.php" class="nav-link" id="nav-logout">
      <i class="fas fa-sign-out-alt"></i><span>Logout</span>
    </a>
  </div>
</aside>

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
      <h1>Kelola Pengguna</h1>
      <p>Kelola data pengguna yang terdaftar di sistem</p>
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

    <!-- Stat chips -->
    <?php
      $stTotal   = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
      $stAktif   = $pdo->query("SELECT COUNT(*) FROM users WHERE status_verifikasi='diterima'")->fetchColumn();
      $stMenunggu= $pdo->query("SELECT COUNT(*) FROM users WHERE status_verifikasi='menunggu'")->fetchColumn();
      $stDitolak = $pdo->query("SELECT COUNT(*) FROM users WHERE status_verifikasi='ditolak'")->fetchColumn();
    ?>
    <div class="stat-chips">
      <div class="stat-chip total">
        <i class="fas fa-users" style="color:#3282b8"></i>
        <span>Total</span>
        <span class="chip-count"><?= $stTotal ?></span>
      </div>
      <div class="stat-chip aktif">
        <i class="fas fa-check-circle" style="color:#059669"></i>
        <span>Aktif</span>
        <span class="chip-count"><?= $stAktif ?></span>
      </div>
      <div class="stat-chip menunggu">
        <i class="fas fa-clock" style="color:#b45309"></i>
        <span>Menunggu</span>
        <span class="chip-count"><?= $stMenunggu ?></span>
      </div>
      <div class="stat-chip ditolak">
        <i class="fas fa-times-circle" style="color:#dc2626"></i>
        <span>Ditolak</span>
        <span class="chip-count"><?= $stDitolak ?></span>
      </div>
    </div>

    <!-- Toolbar -->
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;flex-wrap:wrap;">
      <form method="GET" id="filterForm" style="display:flex;align-items:center;gap:12px;flex:1;flex-wrap:wrap;">
        <div class="search-box" style="max-width:380px">
          <i class="fas fa-search"></i>
          <input type="text" name="q" id="searchInput"
                 placeholder="Cari nama, email, atau role..."
                 value="<?= htmlspecialchars($search) ?>" autocomplete="off">
        </div>
        <select name="role" class="filter-select" onchange="document.getElementById('filterForm').submit()">
          <option value="">Semua Role</option>
          <option value="admin"    <?= $filterRole==='admin'   ?'selected':'' ?>>Admin</option>
          <option value="pengusul" <?= $filterRole==='pengusul'?'selected':'' ?>>Pengusul</option>
        </select>
        <select name="status" class="filter-select" onchange="document.getElementById('filterForm').submit()">
          <option value="">Semua Status</option>
          <option value="diterima" <?= $filterStatus==='diterima'?'selected':'' ?>>Aktif</option>
          <option value="menunggu" <?= $filterStatus==='menunggu'?'selected':'' ?>>Menunggu</option>
          <option value="ditolak"  <?= $filterStatus==='ditolak' ?'selected':'' ?>>Ditolak</option>
        </select>
        <button type="submit" style="display:none"></button>
      </form>
      <button class="btn-tambah" id="btnTambah">
        <i class="fas fa-plus"></i> Tambah Pengguna
      </button>
    </div>

    <!-- Table Panel -->
    <div class="table-panel">
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th class="col-no">No</th>
              <th>Nama</th>
              <th>Email</th>
              <th>Role</th>
              <th>Status</th>
              <th>Bergabung</th>
              <th class="col-aksi">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($users)): ?>
            <tr>
              <td colspan="7" class="no-data">
                <i class="fas fa-users" style="font-size:2rem;display:block;margin-bottom:8px;opacity:.3"></i>
                Tidak ada pengguna yang ditemukan
              </td>
            </tr>
            <?php else: ?>
            <?php foreach ($users as $i => $u): ?>
            <tr>
              <td class="col-no"><?= $offset + $i + 1 ?></td>
              <td>
                <div class="user-cell">
                  <div class="user-avatar"
                       style="background:<?= avatarColor($u['nama']) ?>">
                    <?= strtoupper(mb_substr($u['nama'], 0, 1)) ?>
                  </div>
                  <div class="user-cell-info">
                    <div class="name"><?= htmlspecialchars($u['nama']) ?></div>
                    <?php if ($u['no_hp']): ?>
                    <div class="hp"><i class="fas fa-phone" style="font-size:.6rem"></i> <?= htmlspecialchars($u['no_hp']) ?></div>
                    <?php endif; ?>
                  </div>
                </div>
              </td>
              <td><?= htmlspecialchars($u['email']) ?></td>
              <td><?= roleBadge($u['role']) ?></td>
              <td><?= statusVerifBadge($u['status_verifikasi']) ?></td>
              <td style="font-size:.78rem;color:#94a3b8">
                <?= date('d M Y', strtotime($u['dibuat_pada'])) ?>
              </td>
              <td class="col-aksi">
                <button class="btn-action btn-edit" title="Edit"
                  onclick="openEditModal(<?= htmlspecialchars(json_encode([
                    'id_user'          => $u['id_user'],
                    'nama'             => $u['nama'],
                    'email'            => $u['email'],
                    'no_hp'            => $u['no_hp'],
                    'role'             => $u['role'],
                    'status_verifikasi'=> $u['status_verifikasi'],
                    'catatan_verifikasi'=> $u['catatan_verifikasi'],
                  ]), ENT_QUOTES) ?>)">
                  <i class="fas fa-pen"></i>
                </button>
                <button class="btn-action btn-hapus" title="Hapus"
                  onclick="openDeleteModal(<?= $u['id_user'] ?>, '<?= htmlspecialchars(addslashes($u['nama'])) ?>')">
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
          Menampilkan <?= min($offset+1,$totalRows) ?>–<?= min($offset+$perPage,$totalRows) ?> dari <?= $totalRows ?> pengguna
        </div>
        <div class="pagination">
          <a href="?q=<?= urlencode($search) ?>&role=<?= $filterRole ?>&status=<?= $filterStatus ?>&page=<?= $page-1 ?>"
             class="page-btn <?= $page<=1?'disabled':'' ?>">
            <i class="fas fa-chevron-left"></i>
          </a>
          <?php
            $start = max(1, $page-2);
            $end   = min($totalPages, $page+2);
            for ($p=$start; $p<=$end; $p++):
          ?>
          <a href="?q=<?= urlencode($search) ?>&role=<?= $filterRole ?>&status=<?= $filterStatus ?>&page=<?= $p ?>"
             class="page-btn <?= $p===$page?'active':'' ?>"><?= $p ?></a>
          <?php endfor; ?>
          <a href="?q=<?= urlencode($search) ?>&role=<?= $filterRole ?>&status=<?= $filterStatus ?>&page=<?= $page+1 ?>"
             class="page-btn <?= $page>=$totalPages?'disabled':'' ?>">
            <i class="fas fa-chevron-right"></i>
          </a>
        </div>
      </div>
    </div><!-- /table-panel -->

  </main>
</div>

<!-- ═══ MODAL: TAMBAH PENGGUNA ═══════════════════════════════ -->
<div class="modal-overlay" id="modalTambah">
  <div class="modal">
    <div class="modal-header">
      <h3><i class="fas fa-user-plus" style="color:#3282b8;margin-right:8px"></i>Tambah Pengguna</h3>
      <button class="modal-close" onclick="closeModal('modalTambah')">
        <i class="fas fa-times"></i>
      </button>
    </div>
    <form method="POST" id="formTambah">
      <input type="hidden" name="aksi" value="tambah">
      <div class="form-row">
        <div class="form-group">
          <label>Nama Lengkap <span>*</span></label>
          <input type="text" name="nama" class="form-control" placeholder="Masukkan nama lengkap" required>
        </div>
        <div class="form-group">
          <label>Nomor HP</label>
          <input type="text" name="no_hp" class="form-control" placeholder="08xxxxxxxxxx">
        </div>
      </div>
      <div class="form-group">
        <label>Email <span>*</span></label>
        <input type="email" name="email" class="form-control" placeholder="email@example.com" required>
      </div>
      <div class="form-group">
        <label>Password <span>*</span></label>
        <input type="password" name="password" class="form-control" placeholder="Minimal 8 karakter" required minlength="8">
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Role <span>*</span></label>
          <select name="role" class="form-control" required>
            <option value="pengusul">Pengusul</option>
            <option value="admin">Admin</option>
          </select>
        </div>
        <div class="form-group">
          <label>Status Verifikasi</label>
          <select name="status_verifikasi" class="form-control">
            <option value="diterima">Aktif (Diterima)</option>
            <option value="menunggu">Menunggu</option>
            <option value="ditolak">Ditolak</option>
          </select>
        </div>
      </div>
      <div class="form-footer">
        <button type="button" class="btn-cancel" onclick="closeModal('modalTambah')">Batal</button>
        <button type="submit" class="btn-save">
          <i class="fas fa-save" style="margin-right:6px"></i>Simpan Pengguna
        </button>
      </div>
    </form>
  </div>
</div>

<!-- ═══ MODAL: EDIT PENGGUNA ════════════════════════════════ -->
<div class="modal-overlay" id="modalEdit">
  <div class="modal">
    <div class="modal-header">
      <h3><i class="fas fa-user-edit" style="color:#3b82f6;margin-right:8px"></i>Edit Pengguna</h3>
      <button class="modal-close" onclick="closeModal('modalEdit')">
        <i class="fas fa-times"></i>
      </button>
    </div>
    <form method="POST" id="formEdit">
      <input type="hidden" name="aksi" value="edit">
      <input type="hidden" name="id_user" id="edit_id">
      <div class="form-row">
        <div class="form-group">
          <label>Nama Lengkap <span>*</span></label>
          <input type="text" name="nama" id="edit_nama" class="form-control" required>
        </div>
        <div class="form-group">
          <label>Nomor HP</label>
          <input type="text" name="no_hp" id="edit_no_hp" class="form-control">
        </div>
      </div>
      <div class="form-group">
        <label>Email <span>*</span></label>
        <input type="email" name="email" id="edit_email" class="form-control" required>
      </div>
      <div class="form-group">
        <label>Password Baru</label>
        <input type="password" name="password_baru" id="edit_pw" class="form-control" placeholder="Kosongkan jika tidak diubah" minlength="8">
        <div class="form-hint">Biarkan kosong jika tidak ingin mengubah password</div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Role</label>
          <select name="role" id="edit_role" class="form-control">
            <option value="pengusul">Pengusul</option>
            <option value="admin">Admin</option>
          </select>
        </div>
        <div class="form-group">
          <label>Status Verifikasi</label>
          <select name="status_verifikasi" id="edit_status" class="form-control">
            <option value="diterima">Aktif (Diterima)</option>
            <option value="menunggu">Menunggu</option>
            <option value="ditolak">Ditolak</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label>Catatan Verifikasi</label>
        <textarea name="catatan_verifikasi" id="edit_catatan" class="form-control"
                  placeholder="Catatan untuk pengguna (opsional)..." rows="2"></textarea>
      </div>
      <div class="form-footer">
        <button type="button" class="btn-cancel" onclick="closeModal('modalEdit')">Batal</button>
        <button type="submit" class="btn-save">
          <i class="fas fa-save" style="margin-right:6px"></i>Perbarui Pengguna
        </button>
      </div>
    </form>
  </div>
</div>

<!-- ═══ MODAL: KONFIRMASI HAPUS ════════════════════════════ -->
<div class="modal-overlay" id="modalHapus">
  <div class="modal modal-sm">
    <form method="POST" id="formHapus">
      <input type="hidden" name="aksi" value="hapus">
      <input type="hidden" name="id_user" id="hapus_id">
      <div class="confirm-icon">
        <i class="fas fa-user-slash"></i>
      </div>
      <div class="confirm-title">Hapus Pengguna?</div>
      <div class="confirm-text">
        Anda akan menghapus pengguna <strong id="hapus_nama"></strong>.<br>
        Semua data terkait pengguna ini akan ikut terhapus.<br>
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
// ── Sidebar mobile ──
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
document.querySelectorAll('.modal-overlay').forEach(el => {
  el.addEventListener('click', e => { if (e.target === el) el.classList.remove('open'); });
});

// ── Tambah ──
document.getElementById('btnTambah').addEventListener('click', () => openModal('modalTambah'));

// ── Edit ──
function openEditModal(data) {
  document.getElementById('edit_id').value       = data.id_user;
  document.getElementById('edit_nama').value     = data.nama;
  document.getElementById('edit_email').value    = data.email;
  document.getElementById('edit_no_hp').value    = data.no_hp || '';
  document.getElementById('edit_pw').value       = '';
  document.getElementById('edit_catatan').value  = data.catatan_verifikasi || '';

  // Role
  const roleSel = document.getElementById('edit_role');
  for (let opt of roleSel.options) { opt.selected = opt.value === data.role; }

  // Status
  const stSel = document.getElementById('edit_status');
  for (let opt of stSel.options) { opt.selected = opt.value === data.status_verifikasi; }

  openModal('modalEdit');
}

// ── Hapus ──
function openDeleteModal(id, nama) {
  document.getElementById('hapus_id').value = id;
  document.getElementById('hapus_nama').textContent = nama;
  openModal('modalHapus');
}

// ── Live search ──
let searchTimer;
document.getElementById('searchInput').addEventListener('input', function () {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => document.getElementById('filterForm').submit(), 500);
});

// ── Auto dismiss alert ──
const alertBox = document.getElementById('alertBox');
if (alertBox) {
  alertBox.style.transition = 'opacity .4s';
  setTimeout(() => {
    alertBox.style.opacity = '0';
    setTimeout(() => alertBox.remove(), 400);
  }, 4000);
}
</script>

</body>
</html>
