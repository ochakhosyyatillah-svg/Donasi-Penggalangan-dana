<?php
session_start();
require_once __DIR__ . '/../assets/config/database.php';

if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'pengusul') {
    header('Location: /Donasi/auth/login.php');
    exit;
}

$idUser = (int) $_SESSION['id_user'];
$successMsg = '';
$errorMsg = '';

// Ambil profil pengusul
try {
    $stmtUser = $pdo->prepare("SELECT id_user, nama, email, no_hp, jenis_organisasi, nama_organisasi FROM users WHERE id_user = ? LIMIT 1");
    $stmtUser->execute([$idUser]);
    $currentUser = $stmtUser->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $currentUser = [];
}

$namaPengusul   = $currentUser['nama'] ?? $_SESSION['nama'] ?? 'Pengusul';
$namaOrganisasi = $currentUser['nama_organisasi'] ?? 'Komunitas Peduli';
$noHpUser       = $currentUser['no_hp'] ?? '';
$emailUser      = $currentUser['email'] ?? '';

// Proses simpan program baru
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Informasi Program
    $judul          = trim($_POST['judul'] ?? '');
    $kategori       = trim($_POST['kategori'] ?? 'Sosial');
    $targetDana     = (float) ($_POST['target_dana'] ?? 0);
    $tanggalSelesai = $_POST['tanggal_selesai'] ?? date('Y-m-d', strtotime('+30 days'));
    $tanggalMulai   = date('Y-m-d');
    $deskripsi      = trim($_POST['deskripsi'] ?? '');

    // 2. Informasi Pengusul
    $orgForm   = trim($_POST['nama_organisasi'] ?? $namaOrganisasi);
    $namaForm  = trim($_POST['nama_pengusul'] ?? $namaPengusul);
    $noHpForm  = trim($_POST['no_hp'] ?? $noHpUser);
    $emailForm = trim($_POST['email'] ?? $emailUser);

    // 3. Rincian Penggunaan Dana
    $kebutuhanArr  = $_POST['rincian_kebutuhan'] ?? [];
    $jumlahArr     = $_POST['rincian_jumlah'] ?? [];
    $keteranganArr = $_POST['rincian_keterangan'] ?? [];

    $rincianList = [];
    for ($i = 0; $i < count($kebutuhanArr); $i++) {
        $keb = trim($kebutuhanArr[$i] ?? '');
        if ($keb !== '') {
            $rincianList[] = [
                'no'         => $i + 1,
                'kebutuhan'  => $keb,
                'jumlah'     => (float) ($jumlahArr[$i] ?? 0),
                'keterangan' => trim($keteranganArr[$i] ?? '')
            ];
        }
    }
    $tujuanDanaJson = json_encode($rincianList, JSON_UNESCAPED_UNICODE);

    // 4. Metode Penerimaan Donasi
    $metodePenerimaan    = $_POST['metode_penerimaan'] ?? 'bank'; // 'bank', 'e-wallet', 'qris'
    $nomorRekening       = trim($_POST['nomor_rekening'] ?? '');
    $pilihBank           = trim($_POST['pilih_bank'] ?? '');
    $namaPemilikRekening = trim($_POST['nama_pemilik_rekening'] ?? '');

    // Penerima dana
    $penerimaDana = !empty($orgForm) ? $orgForm : $namaForm;

    // Validasi dasar
    if (empty($judul) || $targetDana <= 0) {
        $errorMsg = 'Nama program dan target dana wajib diisi dengan benar.';
    } elseif (empty($_POST['persetujuan'])) {
        $errorMsg = 'Anda harus mencentang persetujuan pernyataan sebelum mengirimkan pengajuan.';
    } else {
        // 5. Upload Foto Program
        $fotoPath = 'assets/img/program_banjir.jpg'; // default fallback
        if (!empty($_FILES['foto']['name']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $newFileName  = 'prog_' . time() . '_' . uniqid() . '.' . $ext;
                $uploadTarget = __DIR__ . '/../assets/img/' . $newFileName;
                if (move_uploaded_file($_FILES['foto']['tmp_name'], $uploadTarget)) {
                    $fotoPath = 'assets/img/' . $newFileName;
                }
            }
        }

        // 5. Upload Dokumen Pendukung
        $dokumenPath = null;
        if (!empty($_FILES['dokumen']['name']) && $_FILES['dokumen']['error'] === UPLOAD_ERR_OK) {
            $extDoc = strtolower(pathinfo($_FILES['dokumen']['name'], PATHINFO_EXTENSION));
            if (in_array($extDoc, ['pdf', 'doc', 'docx'])) {
                $docsDir = __DIR__ . '/../assets/docs';
                if (!is_dir($docsDir)) {
                    mkdir($docsDir, 0777, true);
                }
                $newDocName      = 'doc_' . time() . '_' . uniqid() . '.' . $extDoc;
                $uploadDocTarget = $docsDir . '/' . $newDocName;
                if (move_uploaded_file($_FILES['dokumen']['tmp_name'], $uploadDocTarget)) {
                    $dokumenPath = 'assets/docs/' . $newDocName;
                }
            }
        }

        try {
            $pdo->beginTransaction();

            // Insert ke tabel program_donasi
            $stmtInsert = $pdo->prepare("
                INSERT INTO program_donasi 
                (id_pengusul, judul, kategori, deskripsi, target_dana, tujuan_dana, penerima_dana, tanggal_mulai, tanggal_selesai, foto_program, dokumen_pendukung, status_program)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'menunggu')
            ");
            $stmtInsert->execute([
                $idUser, $judul, $kategori, $deskripsi, $targetDana, $tujuanDanaJson, $penerimaDana, $tanggalMulai, $tanggalSelesai, $fotoPath, $dokumenPath
            ]);
            $newProgramId = $pdo->lastInsertId();

            // Insert ke tabel akun_tujuan_pembayaran (Mendukung Multiple Rekening / E-Wallet / QRIS)
            $akunMetodeArr   = $_POST['akun_metode'] ?? [];
            $akunPenyediaArr = $_POST['akun_penyedia'] ?? [];
            $akunNomorArr    = $_POST['akun_nomor'] ?? [];
            $akunPemilikArr  = $_POST['akun_pemilik'] ?? [];

            // Fallback jika single input legacy digunakan
            if (empty($akunNomorArr) && !empty($nomorRekening)) {
                $akunMetodeArr   = [$metodePenerimaan];
                $akunPenyediaArr = [$pilihBank ?: 'Bank Transfer'];
                $akunNomorArr    = [$nomorRekening];
                $akunPemilikArr  = [$namaPemilikRekening ?: $namaForm];
            }

            if (!empty($akunNomorArr) && is_array($akunNomorArr)) {
                $stmtAkun = $pdo->prepare("
                    INSERT INTO akun_tujuan_pembayaran
                    (id_program, metode, nama_penyedia, nama_pemilik, nomor_tujuan, gambar_qris, status_aktif)
                    VALUES (?, ?, ?, ?, ?, ?, 1)
                ");

                for ($k = 0; $k < count($akunNomorArr); $k++) {
                    $mMetode   = $akunMetodeArr[$k] ?? 'bank';
                    $mPenyedia = trim($akunPenyediaArr[$k] ?? '');
                    $mNomor    = trim($akunNomorArr[$k] ?? '');
                    $mPemilik  = trim($akunPemilikArr[$k] ?? '');

                    if ($mMetode === 'qris' || !empty($mNomor)) {
                        // Cek upload foto QRIS jika ada
                        $qrisFilePath = null;
                        if (isset($_FILES['akun_qris_file']['name'][$k]) && $_FILES['akun_qris_file']['error'][$k] === UPLOAD_ERR_OK) {
                            $extQ = strtolower(pathinfo($_FILES['akun_qris_file']['name'][$k], PATHINFO_EXTENSION));
                            if (in_array($extQ, ['jpg', 'jpeg', 'png', 'webp'])) {
                                $qName = 'qris_' . time() . '_' . uniqid() . '.' . $extQ;
                                if (move_uploaded_file($_FILES['akun_qris_file']['tmp_name'][$k], __DIR__ . '/../assets/img/' . $qName)) {
                                    $qrisFilePath = 'assets/img/' . $qName;
                                }
                            }
                        }

                        $stmtAkun->execute([
                            $newProgramId,
                            $mMetode,
                            $mPenyedia ?: ($mMetode === 'bank' ? 'Bank Transfer' : ($mMetode === 'qris' ? 'QRIS' : 'E-Wallet')),
                            $mPemilik ?: ($namaPemilikRekening ?: $namaForm),
                            $mNomor,
                            $qrisFilePath
                        ]);
                    }
                }
            }

            // Update info kontak user jika belum lengkap
            $stmtUpdateUser = $pdo->prepare("
                UPDATE users 
                SET no_hp = COALESCE(NULLIF(no_hp, ''), ?), 
                    nama_organisasi = COALESCE(NULLIF(nama_organisasi, ''), ?)
                WHERE id_user = ?
            ");
            $stmtUpdateUser->execute([$noHpForm, $orgForm, $idUser]);

            $pdo->commit();
            $successMsg = 'Program donasi berhasil diajukan! Data dan dokumen telah tersimpan dan saat ini menunggu verifikasi admin.';
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errorMsg = 'Gagal menyimpan program donasi: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Ajukan Program Donasi – SIBERASA</title>
  <meta name="description" content="Formulir pengajuan program donasi pengusul SIBERASA lengkap dengan rincian dana dan metode pembayaran.">

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

    /* ── Header Halaman ─────────────────────────────────────────── */
    .page-header {
      margin-bottom: 20px;
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

    .alert {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 13px 18px;
      border-radius: var(--radius-md);
      font-size: 0.88rem;
      font-weight: 500;
      margin-bottom: 22px;
    }

    .alert i {
      font-size: 1.15rem;
      flex-shrink: 0;
    }

    .alert-success {
      background: #dcfce7;
      color: #15803d;
      border: 1px solid #bbf7d0;
    }

    .alert-danger {
      background: #fee2e2;
      color: #b91c1c;
      border: 1px solid #fecaca;
    }

    /* ── Layout Grid 2 Kolom Sesuai Desain Mockup ────────────────── */
    .form-grid-layout {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 22px;
      align-items: start;
    }

    .grid-column {
      display: flex;
      flex-direction: column;
      gap: 22px;
    }

    /* ── Desain Kartu (Step Cards 1 s/d 6) ───────────────────────── */
    .step-card {
      background: #ffffff;
      border: 1.5px solid var(--border-color);
      border-radius: var(--radius-lg);
      padding: 22px 24px;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
      transition: box-shadow 0.2s ease;
    }

    .step-card:hover {
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
    }

    .step-header {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 18px;
    }

    .step-num {
      width: 32px;
      height: 32px;
      border-radius: 50%;
      border: 2px solid var(--primary);
      color: var(--primary);
      background: var(--primary-light);
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      font-size: 0.95rem;
      flex-shrink: 0;
    }

    .step-title {
      font-size: 1.05rem;
      font-weight: 700;
      color: #1e293b;
    }

    /* Field Form & Input */
    .form-field {
      margin-bottom: 16px;
    }

    .form-field:last-child {
      margin-bottom: 0;
    }

    .form-field label {
      display: block;
      font-size: 0.82rem;
      font-weight: 600;
      color: #334155;
      margin-bottom: 6px;
    }

    .form-field label .req {
      color: #ef4444;
      margin-left: 2px;
    }

    .input-control {
      width: 100%;
      padding: 10px 14px;
      border: 1.5px solid var(--border-color);
      border-radius: 10px;
      font-family: inherit;
      font-size: 0.86rem;
      color: var(--text-main);
      background: #ffffff;
      outline: none;
      transition: var(--transition);
    }

    .input-control:focus {
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15);
    }

    .input-control::placeholder {
      color: #94a3b8;
      font-size: 0.82rem;
    }

    select.input-control {
      cursor: pointer;
      appearance: auto;
    }

    textarea.input-control {
      resize: vertical;
      min-height: 90px;
      line-height: 1.5;
    }

    .field-row-2 {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 14px;
    }

    /* Target Dana dengan Prefix "Rp" */
    .input-group-rp {
      display: flex;
      align-items: stretch;
      border: 1.5px solid var(--border-color);
      border-radius: 10px;
      overflow: hidden;
      background: #ffffff;
      transition: var(--transition);
    }

    .input-group-rp:focus-within {
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15);
    }

    .rp-addon {
      padding: 9px 14px;
      background: #f8fafc;
      border-right: 1.5px solid var(--border-color);
      font-weight: 700;
      font-size: 0.85rem;
      color: #475569;
      display: flex;
      align-items: center;
      user-select: none;
    }

    .input-group-rp input {
      border: none;
      outline: none;
      flex: 1;
      padding: 9px 12px;
      font-family: inherit;
      font-size: 0.86rem;
      color: var(--text-main);
      width: 100%;
    }

    .input-group-rp input::placeholder {
      color: #94a3b8;
    }

    /* ── Card 3: Rincian Penggunaan Dana (Tabel Dinamis) ────────── */
    .table-responsive-wrap {
      width: 100%;
      overflow-x: auto;
      margin-bottom: 12px;
    }

    .rincian-table {
      width: 100%;
      border-collapse: collapse;
      border: 1px solid var(--border-color);
      border-radius: 10px;
      overflow: hidden;
    }

    .rincian-table th {
      background: #f1f5f9;
      padding: 9px 10px;
      font-size: 0.78rem;
      font-weight: 700;
      color: #334155;
      text-align: center;
      border-bottom: 1px solid var(--border-color);
      border-right: 1px solid var(--border-color);
    }

    .rincian-table th:last-child {
      border-right: none;
    }

    .rincian-table td {
      padding: 6px 8px;
      border-bottom: 1px solid var(--border-color);
      border-right: 1px solid var(--border-color);
      vertical-align: middle;
      background: #ffffff;
    }

    .rincian-table td:last-child {
      border-right: none;
    }

    .rincian-table td.col-no {
      text-align: center;
      font-weight: 600;
      color: #64748b;
      font-size: 0.85rem;
      width: 36px;
    }

    .rincian-input {
      width: 100%;
      padding: 8px 10px;
      border: 1px solid #cbd5e1;
      border-radius: 6px;
      font-family: inherit;
      font-size: 0.82rem;
      outline: none;
      transition: var(--transition);
    }

    .rincian-input:focus {
      border-color: var(--primary);
      box-shadow: 0 0 0 2px rgba(2, 132, 199, 0.15);
    }

    .btn-del-row {
      background: none;
      border: none;
      color: #94a3b8;
      cursor: pointer;
      padding: 4px 6px;
      border-radius: 4px;
      transition: var(--transition);
    }

    .btn-del-row:hover {
      color: #ef4444;
      background: #fee2e2;
    }

    .btn-add-rincian {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: #e0f2fe;
      color: var(--primary);
      font-weight: 600;
      font-size: 0.82rem;
      padding: 8px 16px;
      border-radius: 8px;
      border: none;
      cursor: pointer;
      transition: var(--transition);
    }

    .btn-add-rincian:hover {
      background: #bae6fd;
      color: var(--primary-hover);
    }

    /* ── Card 4: Multi Metode Pembayaran ────────────────────────── */
    .payment-items-list {
      display: flex;
      flex-direction: column;
      gap: 16px;
      margin-bottom: 16px;
    }

    .payment-item-card {
      background: #f8fafc;
      border: 1px solid var(--border-color);
      border-radius: var(--radius-md);
      padding: 16px 18px;
      position: relative;
      transition: var(--transition);
    }

    .payment-item-card:hover {
      border-color: #cbd5e1;
      background: #ffffff;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    }

    .payment-item-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 14px;
      padding-bottom: 10px;
      border-bottom: 1px solid #e2e8f0;
    }

    .payment-item-title {
      font-size: 0.9rem;
      font-weight: 700;
      color: #1e3a8a;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .btn-del-payment {
      background: none;
      border: none;
      color: #94a3b8;
      font-size: 0.8rem;
      font-weight: 600;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 5px;
      padding: 4px 8px;
      border-radius: 6px;
      transition: var(--transition);
    }

    .btn-del-payment:hover {
      color: #ef4444;
      background: #fee2e2;
    }

    .btn-add-payment {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: #f0fdf4;
      color: #16a34a;
      border: 1.5px dashed #86efac;
      padding: 11px 18px;
      border-radius: 10px;
      font-family: inherit;
      font-size: 0.86rem;
      font-weight: 600;
      cursor: pointer;
      transition: var(--transition);
      width: 100%;
      justify-content: center;
    }

    .btn-add-payment:hover {
      background: #dcfce7;
      border-color: #4ade80;
      color: #15803d;
      transform: translateY(-1px);
    }

    /* ── Card 5: Dokumen Pendukung (Upload Box) ─────────────────── */
    .upload-drop-zone {
      border: 1.5px solid var(--border-color);
      border-radius: 12px;
      padding: 22px 16px;
      text-align: center;
      cursor: pointer;
      background: #ffffff;
      transition: var(--transition);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      gap: 6px;
      margin-top: 6px;
    }

    .upload-drop-zone:hover, .upload-drop-zone.dragover {
      border-color: var(--primary);
      background: #f0f9ff;
    }

    .upload-icon-circle {
      width: 44px;
      height: 44px;
      border-radius: 10px;
      background: #f1f5f9;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.25rem;
      color: var(--primary);
      margin-bottom: 2px;
      transition: var(--transition);
    }

    .upload-drop-zone:hover .upload-icon-circle {
      background: var(--primary-light);
    }

    .upload-main-text {
      font-size: 0.84rem;
      font-weight: 600;
      color: #334155;
    }

    .upload-sub-text {
      font-size: 0.72rem;
      color: #94a3b8;
    }

    .upload-file-chosen {
      display: none;
      font-size: 0.8rem;
      font-weight: 600;
      color: #0284c7;
      margin-top: 4px;
      background: #e0f2fe;
      padding: 4px 10px;
      border-radius: 6px;
    }

    /* ── Card 6: Persetujuan & Tombol Submit ──────────────────────── */
    .agreement-check-wrapper {
      display: flex;
      align-items: flex-start;
      gap: 12px;
      margin-bottom: 20px;
      cursor: pointer;
    }

    .agreement-checkbox {
      width: 19px;
      height: 19px;
      accent-color: var(--primary);
      margin-top: 2px;
      cursor: pointer;
      flex-shrink: 0;
    }

    .agreement-text {
      font-size: 0.84rem;
      color: #334155;
      line-height: 1.45;
      cursor: pointer;
      user-select: none;
    }

    .btn-submit-full {
      width: 100%;
      background: var(--primary);
      color: #ffffff;
      padding: 13px 20px;
      border-radius: 10px;
      font-weight: 700;
      font-size: 0.95rem;
      border: none;
      cursor: pointer;
      transition: var(--transition);
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      box-shadow: 0 3px 10px rgba(2, 132, 199, 0.25);
    }

    .btn-submit-full:hover {
      background: var(--primary-hover);
      box-shadow: 0 6px 16px rgba(2, 132, 199, 0.35);
      transform: translateY(-1px);
    }

    /* Overlay untuk Mobile Sidebar */
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

    /* ── Responsive Mobile & Tablet ──────────────────────────────── */
    @media (max-width: 1024px) {
      .form-grid-layout {
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
      .mobile-toggle-btn {
        display: flex;
      }
      .top-header {
        padding: 0 20px;
      }
      .page-scroll-content {
        padding: 20px 18px 40px;
      }
      .field-row-2 {
        grid-template-columns: 1fr;
        gap: 12px;
      }
      .payment-methods-grid {
        grid-template-columns: 1fr;
      }
      .step-card {
        padding: 18px 16px;
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
          <li class="nav-item active">
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

        <!-- Header Halaman -->
        <div class="page-header">
          <h1 class="page-title">Ajukan Program Donasi</h1>
          <p class="page-subtitle">Lengkapi formulir di bawah ini untuk mengajukan penggalangan dana baru.</p>
        </div>

        <?php if (!empty($successMsg)): ?>
          <div class="alert alert-success">
            <i class="fa-solid fa-circle-check"></i>
            <div><?= htmlspecialchars($successMsg) ?></div>
          </div>
        <?php endif; ?>

        <?php if (!empty($errorMsg)): ?>
          <div class="alert alert-danger">
            <i class="fa-solid fa-circle-exclamation"></i>
            <div><?= htmlspecialchars($errorMsg) ?></div>
          </div>
        <?php endif; ?>

        <!-- Form 6 Bagian Sesuai Desain Mockup -->
        <form method="POST" enctype="multipart/form-data" id="formAjukanProgram">
          <div class="form-grid-layout">

            <!-- ═══════ KOLOM KIRI (Kartu 1, 2, 3) ═══════ -->
            <div class="grid-column">

              <!-- ① KARTU 1: Informasi Program -->
              <div class="step-card">
                <div class="step-header">
                  <div class="step-num">1</div>
                  <h3 class="step-title">Informasi Program</h3>
                </div>

                <div class="field-row-2 form-field">
                  <div>
                    <label for="judul">Nama Program <span class="req">*</span></label>
                    <input type="text" id="judul" name="judul" class="input-control" placeholder="Masukkan Nama Program Donasi" required>
                  </div>
                  <div>
                    <label for="kategori">Kategori <span class="req">*</span></label>
                    <select id="kategori" name="kategori" class="input-control" required>
                      <option value="" disabled selected>Pilih Kategori</option>
                      <option value="Sosial">Sosial</option>
                      <option value="Pendidikan">Pendidikan</option>
                      <option value="Kemanusiaan">Kemanusiaan</option>
                      <option value="Kesehatan">Kesehatan</option>
                      <option value="Keagamaan">Keagamaan</option>
                      <option value="Lingkungan">Lingkungan</option>
                    </select>
                  </div>
                </div>

                <div class="field-row-2 form-field">
                  <div>
                    <label for="target_dana">Target Dana <span class="req">*</span></label>
                    <div class="input-group-rp">
                      <span class="rp-addon">Rp</span>
                      <input type="number" id="target_dana" name="target_dana" placeholder="Masukkan Target Dana" min="10000" required>
                    </div>
                  </div>
                  <div>
                    <label for="tanggal_selesai">Batas Waktu <span class="req">*</span></label>
                    <input type="date" id="tanggal_selesai" name="tanggal_selesai" class="input-control" value="<?= date('Y-m-d', strtotime('+30 days')) ?>" required>
                  </div>
                </div>

                <div class="form-field">
                  <label for="deskripsi">Diskripsi Program <span class="req">*</span></label>
                  <textarea id="deskripsi" name="deskripsi" class="input-control" rows="4" placeholder="jelaskan tujuan, manfaat, dan rencana pengguna dana secara jelas...." required></textarea>
                </div>
              </div>

              <!-- ② KARTU 2: Informasi Pengusul -->
              <div class="step-card">
                <div class="step-header">
                  <div class="step-num">2</div>
                  <h3 class="step-title">Informasi Pengusul</h3>
                </div>

                <div class="form-field">
                  <label for="nama_organisasi">Nama Organisasi / Komunitas <span class="req">*</span></label>
                  <input type="text" id="nama_organisasi" name="nama_organisasi" class="input-control" value="<?= htmlspecialchars($namaOrganisasi) ?>" placeholder="Masukkan Nama Organisasi / Komunitas" required>
                </div>

                <div class="form-field">
                  <label for="nama_pengusul">Nama lengkap <span class="req">*</span></label>
                  <input type="text" id="nama_pengusul" name="nama_pengusul" class="input-control" value="<?= htmlspecialchars($namaPengusul) ?>" placeholder="Masukkan Nama Lengkap" required>
                </div>

                <div class="field-row-2 form-field">
                  <div>
                    <label for="no_hp">Nomer HP / WhatsApp <span class="req">*</span></label>
                    <input type="text" id="no_hp" name="no_hp" class="input-control" value="<?= htmlspecialchars($noHpUser) ?>" placeholder="Masukkan Nomer Aktif" required>
                  </div>
                  <div>
                    <label for="email">Email <span class="req">*</span></label>
                    <input type="email" id="email" name="email" class="input-control" value="<?= htmlspecialchars($emailUser) ?>" placeholder="Masukkan Email Aktif" required>
                  </div>
                </div>
              </div>

              <!-- ③ KARTU 3: Rincian Penggunaan Dana -->
              <div class="step-card">
                <div class="step-header">
                  <div class="step-num">3</div>
                  <h3 class="step-title">Rincian Penggunaan Dana</h3>
                </div>

                <div class="table-responsive-wrap">
                  <table class="rincian-table" id="rincianTable">
                    <thead>
                      <tr>
                        <th style="width: 40px;">No</th>
                        <th>Kebutuhan</th>
                        <th style="width: 140px;">Jumlah</th>
                        <th>Keterangan</th>
                        <th style="width: 32px;"></th>
                      </tr>
                    </thead>
                    <tbody id="rincianTbody">
                      <tr>
                        <td class="col-no">1</td>
                        <td>
                          <input type="text" name="rincian_kebutuhan[]" class="rincian-input" placeholder="Contoh: Paket Sembako">
                        </td>
                        <td>
                          <input type="number" name="rincian_jumlah[]" class="rincian-input" placeholder="Rp 0">
                        </td>
                        <td>
                          <input type="text" name="rincian_keterangan[]" class="rincian-input" placeholder="100 paket untuk korban">
                        </td>
                        <td style="text-align: center;">
                          <button type="button" class="btn-del-row" title="Hapus baris" onclick="hapusBarisRincian(this)">
                            <i class="fa-solid fa-trash-can"></i>
                          </button>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>

                <button type="button" class="btn-add-rincian" id="btnAddRincian">
                  <i class="fa-solid fa-plus"></i>
                  <span>Tambah Rincian</span>
                </button>
              </div>

            </div>

            <!-- ═══════ KOLOM KANAN (Kartu 4, 5, 6) ═══════ -->
            <div class="grid-column">

              <!-- ④ KARTU 4: Metode Penerimaan Donasi (Multiple Akun) -->
              <div class="step-card">
                <div class="step-header">
                  <div class="step-num">4</div>
                  <div>
                    <h3 class="step-title">Metode Penerimaan Donasi</h3>
                    <p style="font-size: 0.78rem; color: var(--text-muted); margin-top: 2px;">
                      Daftarkan rekening bank, dompet digital, atau QRIS untuk menampung donasi. Anda dapat menambahkan lebih dari 1 metode.
                    </p>
                  </div>
                </div>

                <!-- Container Multiple Payment Items -->
                <div class="payment-items-list" id="paymentItemsContainer">
                  
                  <!-- Item #1 (Default) -->
                  <div class="payment-item-card" data-idx="1">
                    <div class="payment-item-header">
                      <span class="payment-item-title">
                        <i class="fa-solid fa-building-columns"></i>
                        <span>Metode Pembayaran #1</span>
                      </span>
                      <button type="button" class="btn-del-payment" onclick="hapusMetodePembayaran(this)" style="display: none;">
                        <i class="fa-solid fa-trash-can"></i> Hapus
                      </button>
                    </div>

                    <div class="field-row-2 form-field">
                      <div>
                        <label>Jenis Metode <span class="req">*</span></label>
                        <select name="akun_metode[]" class="input-control select-metode-type" onchange="ubahTipeMetode(this)">
                          <option value="bank" selected>Transfer Bank</option>
                          <option value="e-wallet">E-Wallet (DANA, GoPay, OVO, dll)</option>
                          <option value="qris">QRIS (Scan Barcode)</option>
                        </select>
                      </div>
                      <div>
                        <label class="label-penyedia">Pilih Bank <span class="req">*</span></label>
                        <select name="akun_penyedia[]" class="input-control select-penyedia" required>
                          <option value="" disabled selected>Pilih Bank</option>
                          <option value="BCA">Bank BCA</option>
                          <option value="BRI">Bank BRI</option>
                          <option value="BNI">Bank BNI</option>
                          <option value="Mandiri">Bank Mandiri</option>
                          <option value="BSI">Bank Syariah Indonesia (BSI)</option>
                          <option value="CIMB Niaga">Bank CIMB Niaga</option>
                          <option value="Permata">Bank Permata</option>
                          <option value="Lainnya">Bank Lainnya</option>
                        </select>
                      </div>
                    </div>

                    <div class="field-row-2 form-field">
                      <div>
                        <label class="label-nomor">Nomor Rekening <span class="req">*</span></label>
                        <input type="text" name="akun_nomor[]" class="input-control input-nomor" placeholder="Contoh: 1234567890" required>
                      </div>
                      <div>
                        <label class="label-pemilik">Nama Pemilik Rekening <span class="req">*</span></label>
                        <input type="text" name="akun_pemilik[]" class="input-control input-pemilik" placeholder="Nama pemilik sesuai rekening" required>
                      </div>
                    </div>

                    <!-- Input Gambar QRIS (Khusus jika pilih QRIS) -->
                    <div class="form-field qris-upload-field" style="display: none;">
                      <label>Upload Barcode QRIS (Opsional)</label>
                      <input type="file" name="akun_qris_file[]" accept="image/jpeg,image/png,image/webp" class="input-control">
                      <small style="color: #64748b; font-size: 0.74rem;">Format: JPG, PNG, WEBP (maks. 3MB)</small>
                    </div>
                  </div>

                </div>

                <!-- Tombol Tambah Metode Pembayaran Lain -->
                <button type="button" class="btn-add-payment" id="btnAddPaymentMethod">
                  <i class="fa-solid fa-plus-circle"></i> Tambah Metode Pembayaran Lain
                </button>
              </div>

              <!-- ⑤ KARTU 5: Dokumen Pendukung -->
              <div class="step-card">
                <div class="step-header">
                  <div class="step-num">5</div>
                  <h3 class="step-title">Dokumen Pendukung</h3>
                </div>

                <!-- Upload 1: Foto Program -->
                <div class="form-field">
                  <label>Foto Program <span class="req">*</span></label>
                  <div class="upload-drop-zone" id="dropFoto">
                    <div class="upload-icon-circle">
                      <i class="fa-regular fa-image"></i>
                    </div>
                    <div class="upload-main-text">Klik untuk mengunggah foto</div>
                    <div class="upload-sub-text">format: JPG, PNG (maks 5MB)</div>
                    <div class="upload-file-chosen" id="chosenFotoText"></div>
                    <input type="file" id="fotoInput" name="foto" accept="image/jpeg,image/png,image/webp" style="display: none;">
                  </div>
                </div>

                <!-- Upload 2: Dokumen Pendukung -->
                <div class="form-field">
                  <label>Dokumen Pendukung <span class="req">*</span></label>
                  <div class="upload-drop-zone" id="dropDokumen">
                    <div class="upload-icon-circle">
                      <i class="fa-regular fa-file-lines"></i>
                    </div>
                    <div class="upload-main-text">Klik untuk mengunggah file</div>
                    <div class="upload-sub-text">format: PDF, DOC, DOCX (maks 10MB)</div>
                    <div class="upload-file-chosen" id="chosenDocText"></div>
                    <input type="file" id="dokumenInput" name="dokumen" accept=".pdf,.doc,.docx" style="display: none;">
                  </div>
                </div>
              </div>

              <!-- ⑥ KARTU 6: Persetujuan & Tombol Submit -->
              <div class="step-card">
                <div class="step-header">
                  <div class="step-num">6</div>
                  <h3 class="step-title">Persetujuan</h3>
                </div>

                <label class="agreement-check-wrapper" for="persetujuan">
                  <input type="checkbox" id="persetujuan" name="persetujuan" class="agreement-checkbox" required>
                  <span class="agreement-text">
                    Saya menyatakan bahwa informasi yang diberikan benar dan dapat dipertanggungjawabkan
                  </span>
                </label>

                <button type="submit" class="btn-submit-full">
                  <i class="fa-regular fa-paper-plane"></i>
                  <span>Ajukan Program</span>
                </button>
              </div>

            </div>

          </div>
        </form>

      </main>

    </div>

  </div>

  <!-- ═══ Interaktivitas Form & Sidebar Toggle ═════════════════════ -->
  <script>
    // ── Sidebar Toggle
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

    // ── Card 3: Tambah / Hapus Baris Rincian Dana
    const rincianTbody = document.getElementById('rincianTbody');
    const btnAddRincian = document.getElementById('btnAddRincian');

    function updateNomorRincian() {
      const rows = rincianTbody.querySelectorAll('tr');
      rows.forEach((row, idx) => {
        const noCell = row.querySelector('.col-no');
        if (noCell) noCell.textContent = idx + 1;
      });
    }

    function hapusBarisRincian(btn) {
      const rows = rincianTbody.querySelectorAll('tr');
      if (rows.length > 1) {
        btn.closest('tr').remove();
        updateNomorRincian();
      } else {
        // Kosongkan input baris pertama jika tinggal 1 baris
        const inputs = rows[0].querySelectorAll('.rincian-input');
        inputs.forEach(input => input.value = '');
      }
    }

    btnAddRincian.addEventListener('click', () => {
      const rowCount = rincianTbody.querySelectorAll('tr').length + 1;
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td class="col-no">${rowCount}</td>
        <td>
          <input type="text" name="rincian_kebutuhan[]" class="rincian-input" placeholder="Kebutuhan barang/dana">
        </td>
        <td>
          <input type="number" name="rincian_jumlah[]" class="rincian-input" placeholder="Rp 0">
        </td>
        <td>
          <input type="text" name="rincian_keterangan[]" class="rincian-input" placeholder="Keterangan alokasi">
        </td>
        <td style="text-align: center;">
          <button type="button" class="btn-del-row" title="Hapus baris" onclick="hapusBarisRincian(this)">
            <i class="fa-solid fa-trash-can"></i>
          </button>
        </td>
      `;
      rincianTbody.appendChild(tr);
    });

    // ── Card 4: Multi Metode Penerimaan Donasi Dinamis
    const providerOptionsMap = {
      bank: [
        { val: 'BCA', label: 'Bank BCA' },
        { val: 'BRI', label: 'Bank BRI' },
        { val: 'BNI', label: 'Bank BNI' },
        { val: 'Mandiri', label: 'Bank Mandiri' },
        { val: 'BSI', label: 'Bank Syariah Indonesia (BSI)' },
        { val: 'CIMB Niaga', label: 'Bank CIMB Niaga' },
        { val: 'Permata', label: 'Bank Permata' },
        { val: 'Lainnya', label: 'Bank Lainnya' }
      ],
      'e-wallet': [
        { val: 'DANA', label: 'DANA' },
        { val: 'GoPay', label: 'GoPay' },
        { val: 'OVO', label: 'OVO' },
        { val: 'ShopeePay', label: 'ShopeePay' },
        { val: 'LinkAja', label: 'LinkAja' },
        { val: 'Lainnya', label: 'E-Wallet Lainnya' }
      ],
      qris: [
        { val: 'QRIS All Payment', label: 'QRIS All Payment (Semua Bank & E-Wallet)' },
        { val: 'QRIS BCA', label: 'QRIS BCA' },
        { val: 'QRIS Mandiri', label: 'QRIS Mandiri' },
        { val: 'QRIS BRI', label: 'QRIS BRI' },
        { val: 'QRIS BNI', label: 'QRIS BNI' },
        { val: 'QRIS DANA', label: 'QRIS DANA Bisnis' },
        { val: 'QRIS Lainnya', label: 'QRIS Lainnya' }
      ]
    };

    function ubahTipeMetode(selectEl) {
      const card = selectEl.closest('.payment-item-card');
      const tipe = selectEl.value;
      const selectPenyedia = card.querySelector('.select-penyedia');
      const labelPenyedia  = card.querySelector('.label-penyedia');
      const labelNomor     = card.querySelector('.label-nomor');
      const inputNomor     = card.querySelector('.input-nomor');
      const labelPemilik   = card.querySelector('.label-pemilik');
      const inputPemilik   = card.querySelector('.input-pemilik');
      const qrisUpload     = card.querySelector('.qris-upload-field');
      const iconTitle      = card.querySelector('.payment-item-title i');

      // Populate opsi penyedia
      selectPenyedia.innerHTML = `<option value="" disabled selected>Pilih ${tipe === 'bank' ? 'Bank' : (tipe === 'qris' ? 'Penyedia QRIS' : 'E-Wallet')}</option>`;
      const list = providerOptionsMap[tipe] || [];
      list.forEach(opt => {
        const o = document.createElement('option');
        o.value = opt.val;
        o.textContent = opt.label;
        selectPenyedia.appendChild(o);
      });

      if (tipe === 'bank') {
        labelPenyedia.innerHTML = 'Pilih Bank <span class="req">*</span>';
        labelNomor.innerHTML = 'Nomor Rekening <span class="req">*</span>';
        inputNomor.placeholder = 'Contoh: 1234567890';
        inputNomor.required = true;
        labelPemilik.innerHTML = 'Nama Pemilik Rekening <span class="req">*</span>';
        inputPemilik.placeholder = 'Nama pemilik sesuai buku rekening';
        qrisUpload.style.display = 'none';
        iconTitle.className = 'fa-solid fa-building-columns';
      } else if (tipe === 'e-wallet') {
        labelPenyedia.innerHTML = 'Penyedia E-Wallet <span class="req">*</span>';
        labelNomor.innerHTML = 'Nomor HP / Akun E-Wallet <span class="req">*</span>';
        inputNomor.placeholder = 'Contoh: 081234567890';
        inputNomor.required = true;
        labelPemilik.innerHTML = 'Nama Pemilik Akun <span class="req">*</span>';
        inputPemilik.placeholder = 'Nama terdaftar di akun e-wallet';
        qrisUpload.style.display = 'none';
        iconTitle.className = 'fa-solid fa-wallet';
      } else if (tipe === 'qris') {
        labelPenyedia.innerHTML = 'Penyedia QRIS <span class="req">*</span>';
        labelNomor.innerHTML = 'NMID / Nomor Merchant <span style="color:#94a3b8;font-weight:400;">(Opsional)</span>';
        inputNomor.placeholder = 'Contoh: ID1020000000000';
        inputNomor.required = false;
        labelPemilik.innerHTML = 'Nama Merchant / Organisasi <span class="req">*</span>';
        inputPemilik.placeholder = 'Nama usaha / organisasi pada QRIS';
        qrisUpload.style.display = 'block';
        iconTitle.className = 'fa-solid fa-qrcode';
      }
    }

    function perbaruiNomorMetodePembayaran() {
      const container = document.getElementById('paymentItemsContainer');
      const cards = container.querySelectorAll('.payment-item-card');
      cards.forEach((card, idx) => {
        const titleSpan = card.querySelector('.payment-item-title span');
        titleSpan.textContent = `Metode Pembayaran #${idx + 1}`;
        const btnDel = card.querySelector('.btn-del-payment');
        // Sembunyikan tombol hapus jika cuma ada 1 kartu
        if (cards.length === 1) {
          btnDel.style.display = 'none';
        } else {
          btnDel.style.display = 'inline-flex';
        }
      });
    }

    function hapusMetodePembayaran(btn) {
      const card = btn.closest('.payment-item-card');
      card.remove();
      perbaruiNomorMetodePembayaran();
    }

    const btnAddPaymentMethod = document.getElementById('btnAddPaymentMethod');
    if (btnAddPaymentMethod) {
      btnAddPaymentMethod.addEventListener('click', () => {
        const container = document.getElementById('paymentItemsContainer');
        const count = container.querySelectorAll('.payment-item-card').length + 1;

        const newCard = document.createElement('div');
        newCard.className = 'payment-item-card';
        newCard.innerHTML = `
          <div class="payment-item-header">
            <span class="payment-item-title">
              <i class="fa-solid fa-building-columns"></i>
              <span>Metode Pembayaran #${count}</span>
            </span>
            <button type="button" class="btn-del-payment" onclick="hapusMetodePembayaran(this)">
              <i class="fa-solid fa-trash-can"></i> Hapus
            </button>
          </div>

          <div class="field-row-2 form-field">
            <div>
              <label>Jenis Metode <span class="req">*</span></label>
              <select name="akun_metode[]" class="input-control select-metode-type" onchange="ubahTipeMetode(this)">
                <option value="bank" selected>Transfer Bank</option>
                <option value="e-wallet">E-Wallet (DANA, GoPay, OVO, dll)</option>
                <option value="qris">QRIS (Scan Barcode)</option>
              </select>
            </div>
            <div>
              <label class="label-penyedia">Pilih Bank <span class="req">*</span></label>
              <select name="akun_penyedia[]" class="input-control select-penyedia" required>
                <option value="" disabled selected>Pilih Bank</option>
                <option value="BCA">Bank BCA</option>
                <option value="BRI">Bank BRI</option>
                <option value="BNI">Bank BNI</option>
                <option value="Mandiri">Bank Mandiri</option>
                <option value="BSI">Bank Syariah Indonesia (BSI)</option>
                <option value="CIMB Niaga">Bank CIMB Niaga</option>
                <option value="Permata">Bank Permata</option>
                <option value="Lainnya">Bank Lainnya</option>
              </select>
            </div>
          </div>

          <div class="field-row-2 form-field">
            <div>
              <label class="label-nomor">Nomor Rekening <span class="req">*</span></label>
              <input type="text" name="akun_nomor[]" class="input-control input-nomor" placeholder="Contoh: 1234567890" required>
            </div>
            <div>
              <label class="label-pemilik">Nama Pemilik Rekening <span class="req">*</span></label>
              <input type="text" name="akun_pemilik[]" class="input-control input-pemilik" placeholder="Nama pemilik sesuai rekening" required>
            </div>
          </div>

          <div class="form-field qris-upload-field" style="display: none;">
            <label>Upload Barcode QRIS (Opsional)</label>
            <input type="file" name="akun_qris_file[]" accept="image/jpeg,image/png,image/webp" class="input-control">
            <small style="color: #64748b; font-size: 0.74rem;">Format: JPG, PNG, WEBP (maks. 3MB)</small>
          </div>
        `;
        container.appendChild(newCard);
        perbaruiNomorMetodePembayaran();
      });
    }

    // ── Card 5: Trigger Upload File pada Drop Zone
    const dropFoto = document.getElementById('dropFoto');
    const fotoInput = document.getElementById('fotoInput');
    const chosenFotoText = document.getElementById('chosenFotoText');

    dropFoto.addEventListener('click', () => fotoInput.click());
    fotoInput.addEventListener('change', () => {
      if (fotoInput.files && fotoInput.files[0]) {
        chosenFotoText.style.display = 'inline-block';
        chosenFotoText.innerHTML = `<i class="fa-solid fa-check"></i> ${fotoInput.files[0].name}`;
      }
    });

    const dropDokumen = document.getElementById('dropDokumen');
    const dokumenInput = document.getElementById('dokumenInput');
    const chosenDocText = document.getElementById('chosenDocText');

    dropDokumen.addEventListener('click', () => dokumenInput.click());
    dokumenInput.addEventListener('change', () => {
      if (dokumenInput.files && dokumenInput.files[0]) {
        chosenDocText.style.display = 'inline-block';
        chosenDocText.innerHTML = `<i class="fa-solid fa-check"></i> ${dokumenInput.files[0].name}`;
      }
    });
  </script>

</body>
</html>
