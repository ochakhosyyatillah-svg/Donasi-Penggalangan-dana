<?php
require_once __DIR__ . '/../assets/config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['name'] ?? '');
    $nama_org = trim($_POST['nama_organisasi'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $no_hp = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $konfirmasi = $_POST['password_confirm'] ?? '';
    $pilihan = $_POST['role'] ?? '';

    // Mengubah pilihan tampilan menjadi nilai enum database
    $jenisOrganisasi = [
        'ormawa' => 'Ormawa',
        'unit'   => 'Lembaga/Unit',
        'ukm'    => 'UKM'
    ];

    if (
        $nama === '' ||
        $nama_org === '' ||
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        $no_hp === '' ||
        strlen($password) < 8 ||
        $password !== $konfirmasi ||
        !isset($jenisOrganisasi[$pilihan])
    ) {
        echo "<script>
            alert('Data tidak valid. Periksa kembali formulir kamu.');
            history.back();
        </script>";
        exit;
    }

    try {
        // Periksa apakah email sudah digunakan
        $cek = $pdo->prepare(
            "SELECT id_user FROM users WHERE email = ?"
        );
        $cek->execute([$email]);

        if ($cek->fetch()) {
            echo "<script>
                alert('Email sudah terdaftar. Silakan gunakan email lain.');
                history.back();
            </script>";
            exit;
        }

        // Hash password sebelum disimpan
        $hashPassword = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        $jenis = $jenisOrganisasi[$pilihan];

        // Simpan akun pengusul ke database
        $sql = "INSERT INTO users (
                    nama,
                    email,
                    password,
                    no_hp,
                    jenis_organisasi,
                    nama_organisasi,
                    role,
                    status_verifikasi
                ) VALUES (?, ?, ?, ?, ?, ?, 'pengusul', 'menunggu')";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $nama,
            $email,
            $hashPassword,
            $no_hp,
            $jenis,
            $nama_org
        ]);

        echo "<script>
            alert('Pendaftaran berhasil! Akun kamu menunggu verifikasi admin.');
            window.location.href = 'login.php';
        </script>";
        exit;

    } catch (PDOException $e) {
        error_log($e->getMessage());

        echo "<script>
            alert('Pendaftaran gagal. Silakan coba kembali.');
            history.back();
        </script>";
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Registrasi – SIBERASA</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
/* ── RESET ───────────────────── */
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{
  font-family:'Poppins',sans-serif;
  min-height:100vh;
  display:flex;
  align-items:center;
  justify-content:center;
  padding:30px 16px;
  position:relative;
  background-color:#0f172a;
  background-image:url('/Donasi/assets/img/bg_login.jpg');
  background-size:cover;
  background-position:center;
  background-repeat:no-repeat;
  background-attachment:fixed;
}

body::before{
  content:'';
  position:fixed;
  inset:0;
  background:linear-gradient(
    135deg,
    rgba(15, 23, 42, 0.58) 0%,
    rgba(26, 54, 93, 0.50) 50%,
    rgba(13, 79, 92, 0.55) 100%
  );
  backdrop-filter:blur(2px);
  -webkit-backdrop-filter:blur(2px);
  z-index:0;
}

/* ── WRAPPER ─────────────────── */
.register-wrap{
  position:relative;
  z-index:1;
  display:flex;
  flex-direction:column;
  align-items:center;
  width:100%;
  max-width:500px;
}

.back-home{
  display:inline-flex;
  align-items:center;
  gap:7px;
  align-self:flex-start;
  color:rgba(255,255,255,0.92);
  text-decoration:none;
  font-size:0.8rem;
  font-weight:500;
  margin-bottom:12px;
  padding:7px 14px;
  border-radius:999px;
  background:rgba(255,255,255,0.15);
  backdrop-filter:blur(8px);
  -webkit-backdrop-filter:blur(8px);
  border:1px solid rgba(255,255,255,0.25);
  transition:all 0.25s ease;
}

.back-home:hover{
  background:rgba(255,255,255,0.28);
  color:#fff;
  transform:translateX(-3px);
}

/* ── CARD ────────────────────── */
.card{
  width:100%;
  background:rgba(255,255,255,0.98);
  backdrop-filter:blur(16px);
  -webkit-backdrop-filter:blur(16px);
  border-radius:20px;
  padding:32px 36px 28px;
  box-shadow:0 20px 45px rgba(0,0,0,0.28), 0 0 0 1px rgba(255,255,255,0.7);
}

.card-title {
  font-size: 1.45rem;
  font-weight: 700;
  color: #1a202c;
  margin-bottom: 24px;
}

/* ── ROLE SELECTION ──────────── */
.role-selection {
  display: flex;
  gap: 12px;
  margin-bottom: 20px;
}

.role-card {
  flex: 1;
  border: 1.5px solid #e2e8f0;
  border-radius: 8px;
  padding: 16px 8px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
}

.role-card:hover {
  border-color: #1a73e8;
  background: #f8fafc;
}

.role-card.active {
  border-color: #1a73e8;
  background: #eff6ff;
  box-shadow: 0 0 0 1px #1a73e8;
}

.role-icon {
  font-size: 1.5rem;
  color: #1a202c;
  margin-bottom: 8px;
}

.role-text {
  font-size: 0.8rem;
  font-weight: 500;
  color: #4a5568;
  text-align: center;
}

/* ── ALERT INFO ──────────────── */
.alert-info {
  background: #eff6ff;
  border: 1px solid #93c5fd;
  border-radius: 8px;
  padding: 12px 16px;
  display: flex;
  gap: 12px;
  align-items: flex-start;
  margin-bottom: 24px;
}

.alert-info i {
  color: #2563eb;
  font-size: 1.2rem;
  margin-top: 2px;
}

.alert-info p {
  font-size: 0.8rem;
  color: #1e3a8a;
  line-height: 1.4;
}

/* ── FORM GROUP ──────────────── */
.form-group{
  margin-bottom: 18px;
}
.form-group label{
  display:block;
  font-size:.85rem;
  font-weight:600;
  color:#333;
  margin-bottom:8px;
}
.form-group label span.req {
  color: #dc2626;
}
.form-group input[type="email"],
.form-group input[type="password"],
.form-group input[type="text"],
.form-group input[type="tel"]{
  width:100%;
  padding:14px 16px;
  border:1.5px solid #d1d5db;
  border-radius:8px;
  font-family:'Poppins',sans-serif;
  font-size:.88rem;
  color:#1a202c;
  background:#fff;
  outline:none;
  transition:border-color .2s, box-shadow .2s;
}
.form-group input:focus{
  border-color:#1a73e8;
  box-shadow:0 0 0 3px rgba(26,115,232,.12);
}
.form-group input::placeholder{color:#a0aec0}

/* ── BTN SUBMIT ──────────────── */
.btn-submit{
  display:block;
  width:100%;
  padding:14px;
  background:#1a73e8;
  color:#fff;
  border:none;
  border-radius:8px;
  font-family:'Poppins',sans-serif;
  font-size:1rem;
  font-weight:600;
  cursor:pointer;
  transition:background .25s, transform .2s, box-shadow .25s;
  box-shadow:0 4px 14px rgba(26,115,232,.35);
  margin-top: 24px;
}
.btn-submit:hover{
  background:#1557b0;
  transform:translateY(-1px);
  box-shadow:0 6px 20px rgba(26,115,232,.45);
}
.btn-submit:active{transform:translateY(0)}

/* ── FOOTER TEXT ─────────────── */
.foot-text{
  text-align:center;
  margin-top:20px;
  font-size:.85rem;
  color:#4a5568;
  font-weight: 500;
}
.foot-text a{
  color:#3b82f6;
  font-weight:600;
  text-decoration:none;
}
.foot-text a:hover{text-decoration:underline}

/* ── RESPONSIVE ──────────────── */
@media(max-width:480px){
  .card{padding:28px 20px 24px}
  .role-selection { flex-direction: column; }
}
</style>
</head>
<body>

<div class="register-wrap">
  <!-- Link Kembali ke Beranda -->
  <a href="/Donasi/" class="back-home">
    <i class="fas fa-arrow-left"></i> Kembali ke Beranda
  </a>

  <div class="card">
    <h1 class="card-title">Registrasi</h1>

    <form method="POST" action="register.php" id="registerForm">
      
      <!-- Role Selection -->
      <div class="role-selection">
        <label class="role-card active" onclick="selectRole(this, 'ormawa')">
          <input type="radio" name="role" value="ormawa" checked style="display:none;">
          <i class="fas fa-users role-icon"></i>
          <span class="role-text">ORMAWA</span>
        </label>
        <label class="role-card" onclick="selectRole(this, 'unit')">
          <input type="radio" name="role" value="unit" style="display:none;">
          <i class="far fa-building role-icon"></i>
          <span class="role-text">Unit/lembaga</span>
        </label>
        <label class="role-card" onclick="selectRole(this, 'ukm')">
          <input type="radio" name="role" value="ukm" style="display:none;">
          <i class="far fa-star role-icon"></i>
          <span class="role-text">UKM</span>
        </label>
      </div>

      <!-- Info Alert -->
      <div class="alert-info">
        <i class="fas fa-exclamation-circle"></i>
        <p>sebagai pengusul donasi, Anda dapat mengajukan program donasi dan mengelola kegiatan penggalangan dana</p>
      </div>

      <!-- Nama Lengkap -->
      <div class="form-group">
        <label for="name">Nama Lengkap <span class="req">*</span></label>
        <input type="text" id="name" name="name" placeholder="Masukkan Nama Lengkap" required>
      </div>

      <!-- Nama Organisasi -->
      <div class="form-group">
        <label for="nama_organisasi">Nama Organisasi <span class="req">*</span></label>
        <input type="text" id="nama_organisasi" name="nama_organisasi" placeholder="Contoh: BEM Fakultas Teknik" required>
      </div>

      <!-- Email -->
      <div class="form-group">
        <label for="email">Email <span class="req">*</span></label>
        <input type="email" id="email" name="email" placeholder="Masukkan Email Anda" required>
      </div>

      <!-- Nomer Telepon -->
      <div class="form-group">
        <label for="phone">Nomer Telepon <span class="req">*</span></label>
        <input type="tel" id="phone" name="phone" placeholder="Contoh: 085xxxx" required>
      </div>

      <!-- Password -->
      <div class="form-group">
        <label for="password">Password <span class="req">*</span></label>
        <input type="password" id="password" name="password" placeholder="Minimal 8 Karakter" required minlength="8">
      </div>

      <!-- Konfirmasi Password -->
      <div class="form-group">
        <label for="password_confirm">Konfirmasi Password <span class="req">*</span></label>
        <input type="password" id="password_confirm" name="password_confirm" placeholder="Ulangi Password" required minlength="8">
      </div>

      <!-- Tombol Submit -->
      <button type="submit" class="btn-submit" id="btnSubmit">Daftar Sekarang</button>
    </form>

    <!-- Sudah punya akun -->
    <p class="foot-text">
      Sudah punya akun? <a href="/Donasi/auth/login.php">Login disini</a>
    </p>

  </div>
</div>

<script>
function selectRole(element, role) {
  // Remove active class from all
  document.querySelectorAll('.role-card').forEach(card => {
    card.classList.remove('active');
  });
  // Add active class to clicked
  element.classList.add('active');
  // Check the radio input
  element.querySelector('input').checked = true;
}

document.getElementById('registerForm').addEventListener('submit', function(e) {
  const pw = document.getElementById('password').value;
  const pwConf = document.getElementById('password_confirm').value;

  if (pw !== pwConf) {
    e.preventDefault();
    alert('Password dan Konfirmasi Password tidak cocok!');
  }
});
</script>

</body>
</html>
