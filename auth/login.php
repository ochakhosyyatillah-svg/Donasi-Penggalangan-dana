

<?php
session_start();

require_once __DIR__ . '/../assets/config/database.php';

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
            header('Location: /Donasi/auth/login.php?error=invalid');
            exit;
        }

        // Periksa status akun
        if ($user['status_verifikasi'] === 'menunggu') {
            header('Location: /Donasi/auth/login.php?error=pending');
            exit;
        }

        if ($user['status_verifikasi'] !== 'diterima') {
            header('Location: /Donasi/auth/login.php?error=rejected');
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
            header('Location: /Donasi/admin/dashboard.php');
            exit;
        }

        if ($user['role'] === 'pengusul') {
            header('Location: /Donasi/pengusul/dashboard.php');
            exit;
        }

        // Role selain admin dan pengusul tidak diizinkan
        session_unset();
        session_destroy();

        header('Location: /Donasi/auth/login.php?error=role');
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
*,*::before,*::after{
  box-sizing:border-box;
  margin:0;
  padding:0;
}

/* Overlay gradient with subtle blur for depth & heartwarming ambient atmosphere */
body{
  font-family:'Poppins',sans-serif;
  min-height:100vh;
  display:flex;
  align-items:center;
  justify-content:center;
  padding:20px 16px;
  position:relative;
  background-color:#0f172a;
  background-image:url('/Donasi/assets/img/bg_login.jpg');
  background-size:cover;
  background-position:center;
  background-repeat:no-repeat;
  background-attachment:fixed;
  overflow-x:hidden;
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

.login-container{
  position:relative;
  z-index:1;
  width:100%;
  max-width:430px;
  animation:fadeInUp 0.5s ease-out;
}

.back-home{
  display:inline-flex;
  align-items:center;
  gap:7px;
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

.card{
  width:100%;
  background:rgba(255,255,255,0.98);
  backdrop-filter:blur(16px);
  -webkit-backdrop-filter:blur(16px);
  border-radius:20px;
  padding:26px 30px 24px;
  box-shadow:0 20px 45px rgba(0,0,0,0.28), 0 0 0 1px rgba(255,255,255,0.7);
}

.logo-header{
  text-align:center;
  margin-bottom:18px;
}

.logo-header a{
  display:inline-block;
  text-decoration:none;
}

.logo-header img{
  height:76px;
  width:auto;
  margin:0 auto 6px;
  display:block;
  filter:drop-shadow(0 4px 10px rgba(26,115,232,0.14));
  transition:transform 0.3s ease;
}

.logo-header img:hover{
  transform:scale(1.03);
}

.logo-header h1{
  font-size:1.24rem;
  font-weight:700;
  color:#1e293b;
  margin-bottom:2px;
}

.logo-header p{
  font-size:0.8rem;
  color:#64748b;
  line-height:1.35;
}

/* Alert Error */
.alert-error{
  background:#fef2f2;
  border:1px solid #fecaca;
  border-left:4px solid #ef4444;
  border-radius:10px;
  padding:10px 12px;
  color:#b91c1c;
  font-size:0.8rem;
  display:flex;
  align-items:center;
  gap:8px;
  margin-bottom:14px;
  animation:shake 0.4s ease-in-out;
}

.form-group{
  margin-bottom:14px;
}

.form-group label{
  display:block;
  font-size:0.82rem;
  font-weight:500;
  color:#334155;
  margin-bottom:5px;
}

.input-wrapper{
  position:relative;
  display:flex;
  align-items:center;
}

.input-icon{
  position:absolute;
  left:13px;
  color:#94a3b8;
  font-size:0.88rem;
  pointer-events:none;
  transition:color 0.2s ease;
}

.form-group input[type="email"],
.form-group input[type="password"],
.form-group input[type="text"]{
  width:100%;
  padding:11px 14px 11px 40px;
  border:1.5px solid #e2e8f0;
  border-radius:10px;
  font-family:'Poppins',sans-serif;
  font-size:0.86rem;
  color:#0f172a;
  background:#f8fafc;
  outline:none;
  transition:all 0.25s ease;
}

.form-group input:focus{
  background:#fff;
  border-color:#1a73e8;
  box-shadow:0 0 0 3px rgba(26,115,232,0.15);
}

.input-wrapper:focus-within .input-icon{
  color:#1a73e8;
}

.input-wrapper input[type="password"]{
  padding-right:42px;
}

.toggle-pw{
  position:absolute;
  right:10px;
  top:50%;
  transform:translateY(-50%);
  background:none;
  border:none;
  cursor:pointer;
  color:#94a3b8;
  font-size:0.92rem;
  padding:5px;
  border-radius:6px;
  transition:color 0.2s, background 0.2s;
}

.toggle-pw:hover{
  color:#1a73e8;
  background:rgba(26,115,232,0.08);
}

.row-check{
  display:flex;
  align-items:center;
  justify-content:space-between;
  margin-top:2px;
  margin-bottom:18px;
}

.remember{
  display:flex;
  align-items:center;
  gap:8px;
  cursor:pointer;
  font-size:0.8rem;
  color:#475569;
  user-select:none;
}

.remember input[type="checkbox"]{
  width:15px;
  height:15px;
  accent-color:#1a73e8;
  cursor:pointer;
  border-radius:4px;
}

.forgot{
  font-size:0.8rem;
  color:#1a73e8;
  font-weight:500;
  text-decoration:none;
  transition:color 0.2s;
}

.forgot:hover{
  color:#1557b0;
  text-decoration:underline;
}

.btn-login{
  display:flex;
  align-items:center;
  justify-content:center;
  gap:8px;
  width:100%;
  padding:12px;
  background:linear-gradient(135deg, #1a73e8 0%, #1557b0 100%);
  color:#fff;
  border:none;
  border-radius:10px;
  font-family:'Poppins',sans-serif;
  font-size:0.94rem;
  font-weight:600;
  cursor:pointer;
  transition:all 0.25s ease;
  box-shadow:0 6px 18px rgba(26,115,232,0.32);
  letter-spacing:0.2px;
}

.btn-login:hover{
  background:linear-gradient(135deg, #1557b0 0%, #0d47a1 100%);
  transform:translateY(-1px);
  box-shadow:0 8px 22px rgba(26,115,232,0.42);
}

.btn-login:active{
  transform:translateY(0);
}

.btn-login:disabled{
  opacity:0.75;
  cursor:not-allowed;
  transform:none;
}

.foot-text{
  text-align:center;
  margin-top:18px;
  padding-top:14px;
  border-top:1px solid #f1f5f9;
  font-size:0.82rem;
  color:#64748b;
}

.foot-text a{
  color:#1a73e8;
  font-weight:600;
  text-decoration:none;
  transition:color 0.2s;
}

.foot-text a:hover{
  color:#1557b0;
  text-decoration:underline;
}

@keyframes fadeInUp{
  from{
    opacity:0;
    transform:translateY(16px);
  }
  to{
    opacity:1;
    transform:translateY(0);
  }
}

@keyframes shake{
  0%, 100%{transform:translateX(0)}
  25%{transform:translateX(-4px)}
  75%{transform:translateX(4px)}
}

@media(max-width:480px){
  body{
    padding:20px 14px;
  }
  .card{
    padding:26px 20px 22px;
    border-radius:18px;
  }
  .logo-header img{
    height:80px;
  }
  .logo-header h1{
    font-size:1.2rem;
  }
}
</style>
</head>
<body>

<div class="login-container">

  <!-- LINK KEMBALI KE BERANDA -->
  <a href="/Donasi/donatur/index.php" class="back-home">
    <i class="fas fa-arrow-left"></i> Kembali ke Beranda
  </a>

  <!-- FORM LOGIN -->
  <div class="card">

    <div class="logo-header">
      <a href="/Donasi/donatur/index.php" title="Kembali ke Beranda">
        <img src="/Donasi/assets/img/logo.png"
             alt="SIBERASA Logo"
             onerror="this.src='/Logo%20Donasi%20login.png'">
      </a>
      <h1>Masuk ke Akun</h1>
      <p>Lanjutkan kebaikan Anda bersama SIBERASA</p>
    </div>

    <?php if ($pesan !== ''): ?>
      <div class="alert-error" role="alert">
        <i class="fas fa-exclamation-circle"></i>
        <span><?= htmlspecialchars($pesan, ENT_QUOTES, 'UTF-8') ?></span>
      </div>
    <?php endif; ?>

    <form method="POST" action="login.php" id="loginForm">

      <div class="form-group">
        <label for="email">Email</label>
        <div class="input-wrapper">
          <i class="fas fa-envelope input-icon"></i>
          <input
            type="email"
            id="email"
            name="email"
            placeholder="Masukkan Email Anda"
            value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
            required
            autocomplete="email">
        </div>
      </div>

      <div class="form-group">
        <label for="password">Password</label>
        <div class="input-wrapper">
          <i class="fas fa-lock input-icon"></i>
          <input
            type="password"
            id="password"
            name="password"
            placeholder="Masukkan Password"
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
        <i class="fas fa-sign-in-alt"></i> Masuk
      </button>

    </form>

    <p class="foot-text">
      Belum punya akun?
      <a href="/Donasi/auth/register.php">Daftar sekarang</a>
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
