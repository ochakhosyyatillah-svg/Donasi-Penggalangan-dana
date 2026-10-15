

<?php
session_start();

require_once __DIR__ . '/assets/config/database.php';

$error = $_GET['error'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    try {
        $stmt = $pdo->prepare(
            "SELECT id_user, nama, email, password, role, status_verifikasi
             FROM users
             WHERE email = ?
             LIMIT 1"
        );
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Periksa email dan password
        if (!$user || !password_verify($password, $user['password'])) {
            header('Location: login.php?error=invalid');
            exit;
        }

        // Periksa status akun
        if ($user['status_verifikasi'] === 'menunggu') {
            header('Location: login.php?error=pending');
            exit;
        }

        if ($user['status_verifikasi'] !== 'diterima') {
            header('Location: login.php?error=rejected');
            exit;
        }

        // Login berhasil
        session_regenerate_id(true);

        $_SESSION['id_user'] = $user['id_user'];
        $_SESSION['nama'] = $user['nama'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];

        // Arahkan sesuai role akun
        if ($user['role'] === 'admin') {
            header('Location: admin/dashboard.php');
            exit;
        }

        if ($user['role'] === 'pengusul') {
            header('Location: pengusul/dashboard.php');
            exit;
        }

        // Role selain admin dan pengusul tidak diizinkan
        session_unset();
        session_destroy();

        header('Location: login.php?error=role');
        exit;

    } catch (PDOException $e) {
        error_log($e->getMessage());
        $error = 'system';
    }
}

$pesanError = [
    'invalid'  => 'Email atau password salah. Silakan coba lagi.',
    'role'     => 'Jenis akun tidak dikenali.',
    'pending'  => 'Akun kamu masih menunggu verifikasi admin.',
    'rejected' => 'Pendaftaran akun kamu ditolak. Silakan hubungi admin.',
    'system'   => 'Terjadi kesalahan sistem. Silakan coba kembali.'
];

$pesan = $pesanError[$error] ?? '';
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login – SIBERASA</title>
<meta name="description" content="Masuk ke akun SIBERASA Anda.">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{
  font-family:'Poppins',sans-serif;
  min-height:100vh;
  background:#e8e8e8;
  display:flex;
  align-items:center;
  justify-content:center;
  padding:24px;
}
.login-wrap{
  display:flex;
  flex-direction:column;
  align-items:center;
  width:100%;
  max-width:480px;
}
.logo-area{
  text-align:center;
  margin-bottom:24px;
}
.logo-area img{
  height:150px;
  width:auto;
  margin:0 auto 6px;
  filter:drop-shadow(0 4px 16px rgba(26,115,232,.18));
}
.logo-area .brand-sub{
  font-size:.8rem;
  color:#888;
  font-weight:400;
  margin-top:2px;
}
.card{
  width:100%;
  background:#fff;
  border-radius:16px;
  padding:36px 40px 32px;
  box-shadow:0 4px 32px rgba(0,0,0,.08);
}
.form-group{margin-bottom:18px}
.form-group label{
  display:block;
  font-size:.85rem;
  font-weight:500;
  color:#333;
  margin-bottom:8px;
}
.form-group input[type="email"],
.form-group input[type="password"],
.form-group input[type="text"]{
  width:100%;
  padding:14px 16px;
  border:1.5px solid #d1d5db;
  border-radius:10px;
  font-family:'Poppins',sans-serif;
  font-size:.88rem;
  color:#1a202c;
  background:#fff;
  outline:none;
  transition:border-color .2s,box-shadow .2s;
}
.form-group input:focus{
  border-color:#1a73e8;
  box-shadow:0 0 0 3px rgba(26,115,232,.12);
}
.form-group input::placeholder{color:#b0bec5}
.row-check{
  display:flex;
  align-items:center;
  justify-content:space-between;
  margin-bottom:22px;
}
.remember{
  display:flex;
  align-items:center;
  gap:8px;
  cursor:pointer;
  font-size:.82rem;
  color:#555;
  user-select:none;
}
.remember input[type="checkbox"]{
  width:15px;height:15px;
  accent-color:#1a73e8;
  cursor:pointer;
  flex-shrink:0;
}
.forgot{
  font-size:.82rem;
  color:#555;
  text-decoration:none;
  transition:color .2s;
}
.forgot:hover{color:#1a73e8}
.btn-login{
  display:block;
  width:100%;
  padding:14px;
  background:#1a73e8;
  color:#fff;
  border:none;
  border-radius:10px;
  font-family:'Poppins',sans-serif;
  font-size:1rem;
  font-weight:600;
  cursor:pointer;
  transition:background .25s,transform .2s,box-shadow .25s;
  box-shadow:0 4px 14px rgba(26,115,232,.35);
  letter-spacing:.3px;
}
.btn-login:hover{
  background:#1557b0;
  transform:translateY(-1px);
  box-shadow:0 6px 20px rgba(26,115,232,.45);
}
.btn-login:active{transform:translateY(0)}
.divider{
  display:flex;
  align-items:center;
  gap:12px;
  margin:22px 0;
  color:#b0bec5;
  font-size:.8rem;
}
.divider::before,.divider::after{
  content:'';flex:1;height:1px;background:#e2e8f0;
}
.btn-google{
  display:flex;
  align-items:center;
  justify-content:center;
  gap:12px;
  width:100%;
  padding:13px;
  background:#fff;
  border:1.5px solid #d1d5db;
  border-radius:10px;
  font-family:'Poppins',sans-serif;
  font-size:.92rem;
  font-weight:600;
  color:#333;
  cursor:pointer;
  transition:background .2s,border-color .2s,box-shadow .2s;
}
.btn-google:hover{
  background:#f8fafc;
  border-color:#b0bec5;
  box-shadow:0 2px 10px rgba(0,0,0,.08);
}
.google-icon{width:20px;height:20px;flex-shrink:0}
.foot-text{
  text-align:center;
  margin-top:22px;
  font-size:.82rem;
  color:#666;
}
.foot-text a{
  color:#1a73e8;
  font-weight:600;
  text-decoration:none;
}
.foot-text a:hover{text-decoration:underline}
.input-pw{position:relative}
.input-pw input{padding-right:44px}
.toggle-pw{
  position:absolute;right:14px;top:50%;
  transform:translateY(-50%);
  background:none;border:none;cursor:pointer;
  color:#a0aec0;font-size:.9rem;
  transition:color .2s;padding:0;
}
.toggle-pw:hover{color:#1a73e8}
.alert-error{
  background:#fef2f2;
  border:1.5px solid #fca5a5;
  border-radius:10px;
  padding:12px 16px;
  color:#dc2626;
  font-size:.83rem;
  display:flex;
  align-items:center;
  gap:8px;
  margin-bottom:18px;
}
@media(max-width:480px){
  .card{padding:28px 20px 24px}
  .logo-area img{height:90px}
  .logo-area .brand-name{font-size:1.6rem}
}
</style>
</head>
<body>

<div class="login-wrap">

  <!-- LOGO -->
  <div class="logo-area">
    <img src="/Logo%20Donasi%20login.png"
         alt="SIBERASA Logo"
         onerror="this.style.display='none'">
  </div>

  <!-- FORM LOGIN -->
  <div class="card">

    <?php if ($pesan !== ''): ?>
      <div class="alert-error" role="alert">
        <i class="fas fa-exclamation-circle"></i>
        <?= htmlspecialchars($pesan, ENT_QUOTES, 'UTF-8') ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="login.php" id="loginForm">

      <div class="form-group">
        <label for="email">Email</label>
        <input
          type="email"
          id="email"
          name="email"
          placeholder="Masukan Email Anda"
          value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
          required
          autocomplete="email">
      </div>

      <div class="form-group">
        <label for="password">Password</label>
        <div class="input-pw">
          <input
            type="password"
            id="password"
            name="password"
            placeholder="Masukan Password"
            required
            autocomplete="current-password">
          <button
            type="button"
            class="toggle-pw"
            id="togglePw"
            onclick="togglePassword()"
            aria-label="Tampilkan password">
            <i class="far fa-eye" id="eyeIcon"></i>
          </button>
        </div>
      </div>

      <div class="row-check">
        <label class="remember">
          <input type="checkbox" name="remember" id="remember">
          Ingat Saya
        </label>
        <a href="#" class="forgot"
           onclick="alert('Fitur lupa password belum tersedia.'); return false;">
          Lupa Password?
        </a>
      </div>

      <button type="submit" class="btn-login" id="btnLogin">
        Login
      </button>

    </form>

    <div class="divider">atau</div>

    <button class="btn-google" type="button"
            onclick="alert('Login dengan Google belum tersedia.')">
      <svg class="google-icon" viewBox="0 0 48 48" aria-hidden="true">
        <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
        <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
        <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
        <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
      </svg>
      Login dengan Google
    </button>

    <p class="foot-text">
      Belum punya akun?
      <a href="/Donasi/register.php">Daftar sekarang</a>
    </p>

  </div>
</div>

<script>
function togglePassword() {
  const input = document.getElementById('password');
  const icon = document.getElementById('eyeIcon');
  const isHidden = input.type === 'password';

  input.type = isHidden ? 'text' : 'password';
  icon.className = isHidden ? 'far fa-eye-slash' : 'far fa-eye';
}

document.getElementById('loginForm').addEventListener('submit', function() {
  const btn = document.getElementById('btnLogin');
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
  btn.disabled = true;
});
</script>

</body>
</html>
