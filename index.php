<?php
// ── Data statis program donasi ──────────────────────────────────────
$programs = [
    [
        'id'           => 1,
        'kategori'     => 'Pendidikan',
        'slug_kat'     => 'pendidikan',
        'judul'        => 'Bantu Renovasi Sekolah Pelosok Negeri 1',
        'org'          => 'Himpunan Mahasiswa Informatika (HIMAFOR)',
        'target'       => 50000000,
        'terkumpul'    => 32500000,
        'persen'       => 65,
        'sisa_hari'    => 12,
        'img'          => 'https://images.unsplash.com/photo-1580582932707-520aed937b7b?w=600&q=80',
    ],
    [
        'id'           => 2,
        'kategori'     => 'Sosial',
        'slug_kat'     => 'sosial',
        'judul'        => 'Paket alat tulis untuk panti asuhan',
        'org'          => 'UKM Forum Kajian Kitab Kuning',
        'target'       => 70000000,
        'terkumpul'    => 45000000,
        'persen'       => 75,
        'sisa_hari'    => 9,
        'img'          => 'https://images.unsplash.com/photo-1488521787991-ed7bbaae773c?w=600&q=80',
    ],
    [
        'id'           => 3,
        'kategori'     => 'Kemanusiaan',
        'slug_kat'     => 'kemanusiaan',
        'judul'        => 'Bantuan untuk korban kebanjiran',
        'org'          => 'Lembaga Dakwah Mahasiswa (LDM)',
        'target'       => 80000000,
        'terkumpul'    => 25600000,
        'persen'       => 35,
        'sisa_hari'    => 30,
        'img'          => 'https://images.unsplash.com/photo-1547683905-f686c993aae5?w=600&q=80',
    ],
];

function rp($n){ return 'Rp '.number_format($n,0,',','.'); }
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SIBERASA – Sistem Donasi dan Penggalangan Dana</title>
<meta name="description" content="Platform donasi online terpercaya. Berbagi Rasa, Hadirkan Manfaat.">

<!-- Google Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<!-- Main CSS (for footer, modal, etc.) -->
<link rel="stylesheet" href="/Donasi/assets/css/style.css?v=<?= time() ?>">

<style>
/* ── RESET ───────────────────────────────────── */
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth}
body{font-family:'Poppins',sans-serif;background:#fff;color:#1a202c;overflow-x:hidden}
a{text-decoration:none;color:inherit}
img{display:block;max-width:100%}

/* ── NAVBAR ──────────────────────────────────── */
.navbar{
  position:sticky;top:0;z-index:999;
  background:rgba(255,255,255,.97);
  backdrop-filter:blur(10px);
  border-bottom:1px solid #e8edf3;
  box-shadow:0 1px 8px rgba(0,0,0,.06);
}
.nav-inner{
  max-width:1200px;margin:0 auto;
  padding:0 28px;
  display:flex;align-items:center;
  gap:28px;height:66px;
}
/* Logo */
.nav-logo{display:flex;align-items:center;gap:10px;flex-shrink:0}
.nav-logo img{height:54px;width:auto;object-fit:contain}

/* Links */
.nav-links{display:flex;align-items:center;gap:2px}
.nav-links a{
  padding:8px 14px;border-radius:8px;
  font-size:.875rem;font-weight:500;color:#4a5568;
  transition:color .2s,background .2s;white-space:nowrap;
}
.nav-links a:hover,.nav-links a.active{color:#1a73e8;background:#ebf4ff}

/* Search */
.nav-search{flex:1;max-width:260px;position:relative;margin-left:auto}
.nav-search i{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#a0aec0;font-size:.8rem;pointer-events:none}
.nav-search input{
  width:100%;padding:9px 14px 9px 36px;
  border:1.5px solid #e2e8f0;border-radius:50px;
  font-family:'Poppins',sans-serif;font-size:.82rem;color:#4a5568;
  background:#f7fafc;outline:none;transition:border .2s,box-shadow .2s;
}
.nav-search input:focus{border-color:#1a73e8;box-shadow:0 0 0 3px rgba(26,115,232,.12);background:#fff}
.nav-search input::placeholder{color:#b0bec5}

/* Buttons */
.nav-actions{display:flex;align-items:center;gap:10px;flex-shrink:0}
.btn{
  display:inline-flex;align-items:center;justify-content:center;gap:6px;
  padding:9px 22px;border-radius:50px;
  font-family:'Poppins',sans-serif;font-size:.85rem;font-weight:600;
  cursor:pointer;transition:all .25s;border:none;white-space:nowrap;
}
.btn-primary{background:#1a73e8;color:#fff;box-shadow:0 3px 12px rgba(26,115,232,.35)}
.btn-primary:hover{background:#1557b0;transform:translateY(-1px);box-shadow:0 5px 18px rgba(26,115,232,.4)}
.btn-outline{background:#fff;color:#1a202c;border:1.5px solid #cbd5e0}
.btn-outline:hover{border-color:#1a73e8;color:#1a73e8;background:#ebf4ff}

/* ── HERO ────────────────────────────────────── */
.hero{
  position:relative;overflow:hidden;
  background:linear-gradient(160deg,#dbeafe 0%,#e0f2fe 45%,#f0fdf4 100%);
  min-height:320px;display:flex;align-items:center;
  padding:56px 28px 70px;
}

/* Blob kiri bawah */
.hero-blob-l{
  position:absolute;bottom:-30px;left:-50px;
  width:280px;height:220px;
  background:rgba(26,115,232,.1);
  border-radius:60% 40% 30% 70%/50% 30% 70% 50%;
  animation:morph 9s ease-in-out infinite;
}
/* Blob tengah atas */
.hero-blob-c{
  position:absolute;top:-10px;left:42%;
  width:180px;height:140px;
  background:rgba(52,199,89,.09);
  border-radius:40% 60% 60% 40%/40% 50% 50% 60%;
  animation:morph 11s ease-in-out infinite reverse;
}
@keyframes morph{
  0%,100%{border-radius:60% 40% 30% 70%/50% 30% 70% 50%}
  50%{border-radius:30% 60% 70% 40%/50% 60% 30% 60%}
}

/* Daun kanan */
.hero-leaf{
  position:absolute;
  animation:float 7s ease-in-out infinite;
}
.hero-leaf-big{right:48px;top:20px;width:110px;opacity:.55}
.hero-leaf-sm{right:180px;bottom:24px;width:60px;opacity:.4;animation-direction:reverse;animation-duration:9s}
@keyframes float{0%,100%{transform:translateY(0) rotate(0)}50%{transform:translateY(-14px) rotate(6deg)}}

.hero-content{
  max-width:1200px;margin:0 auto;width:100%;
  text-align:center;position:relative;z-index:2;
}
.hero-content h1{
  font-size:clamp(1.9rem,4.5vw,3rem);
  font-weight:800;color:#1a73e8;
  line-height:1.15;margin-bottom:10px;
  animation:fadeUp .6s ease both;
}
.hero-content h2{
  font-size:clamp(1rem,2.2vw,1.35rem);
  font-weight:600;color:#1a202c;
  margin-bottom:0;
  animation:fadeUp .6s .15s ease both;
}
.hero-content h2 span{color:#34c759}
@keyframes fadeUp{from{opacity:0;transform:translateY(18px)}to{opacity:1;transform:none}}

/* ── CEK STATUS BANNER ───────────────────────── */
.cek-status-banner{
  display:flex;align-items:center;gap:16px;
  background:#e8f2fe;border-radius:12px;
  padding:16px 24px;margin-bottom:32px;
}
.cek-icon-wrap{
  width:46px;height:46px;border-radius:50%;
  background:#d5e6fb;color:#1a73e8;
  display:flex;align-items:center;justify-content:center;
  font-size:1.2rem;flex-shrink:0;
}
.cek-text{flex:1;}
.cek-text h4{font-size:.95rem;font-weight:700;color:#1e487a;margin-bottom:2px;}
.cek-text p{font-size:.8rem;color:#5a7698;}
.btn-cek{
  background:transparent;color:#1a73e8;
  border:1.5px solid #a8cbf4;border-radius:8px;
  padding:8px 20px;font-weight:600;font-size:.85rem;
  transition:all .25s;
}
.btn-cek:hover{background:#1a73e8;color:#fff;border-color:#1a73e8;}

@media(max-width:600px){
  .cek-status-banner{flex-direction:column;text-align:center;padding:20px;}
  .btn-cek{width:100%;}
}

/* ── SECTION PROGRAM ─────────────────────────── */
.section{padding:48px 0 60px;background:#f0f6ff}
.wrap{max-width:1200px;margin:0 auto;padding:0 28px}

.sec-header{
  display:flex;align-items:center;
  justify-content:space-between;
  margin-bottom:26px;flex-wrap:wrap;gap:12px;
}
.sec-title{font-size:1.25rem;font-weight:700;color:#4a5568}

.btn-see-all{
  display:inline-flex;align-items:center;gap:8px;
  padding:10px 22px;border:1.8px solid #1a73e8;
  border-radius:50px;color:#1a73e8;
  font-size:.85rem;font-weight:600;
  transition:all .25s;
}
.btn-see-all:hover{background:#1a73e8;color:#fff}
.btn-see-all i{transition:transform .25s}
.btn-see-all:hover i{transform:translateX(4px)}

/* Green border wrapper — sesuai desain */
.cards-wrap{
  border:2.5px solid #4caf50;
  border-radius:18px;padding:24px;
  background:#fff;
  box-shadow:0 0 0 5px rgba(76,175,80,.07);
}

/* Grid 3 kolom */
.cards-grid{
  display:grid;
  grid-template-columns:repeat(3,1fr);
  gap:22px;
}

/* ── CARD ────────────────────────────────────── */
.card{
  background:#fff;border-radius:14px;overflow:hidden;
  box-shadow:0 3px 16px rgba(0,0,0,.09);
  border:1px solid rgba(26,115,232,.1);
  transition:transform .3s,box-shadow .3s;
}
.card:hover{transform:translateY(-6px);box-shadow:0 10px 32px rgba(0,0,0,.13)}

.card-img{position:relative;height:190px;overflow:hidden;background:#e2e8f0}
.card-img img{width:100%;height:100%;object-fit:cover;transition:transform .5s}
.card:hover .card-img img{transform:scale(1.05)}

.badge{
  position:absolute;top:12px;left:12px;
  padding:4px 12px;border-radius:50px;
  font-size:.72rem;font-weight:600;color:#fff;
  backdrop-filter:blur(6px);
}
.badge.pendidikan{background:rgba(26,115,232,.88)}
.badge.sosial{background:rgba(52,199,89,.88)}
.badge.kemanusiaan{background:rgba(14,165,233,.88)}

.card-body{padding:16px 18px 18px}

.card-title{
  font-size:.95rem;font-weight:700;color:#1a202c;
  margin-bottom:5px;line-height:1.4;
  display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;
}
.card-org{
  font-size:.75rem;font-weight:500;color:#1a73e8;
  margin-bottom:14px;
  display:flex;align-items:center;gap:4px;
}

/* Progress */
.prog-row{display:flex;justify-content:space-between;margin-bottom:5px}
.prog-pct{font-size:.78rem;font-weight:700;color:#34c759}
.prog-bg{height:7px;background:#e2e8f0;border-radius:50px;overflow:hidden;margin-bottom:14px}
.prog-fill{
  height:100%;border-radius:50px;
  background:linear-gradient(90deg,#34c759,#22d3ee);
  position:relative;overflow:hidden;
}
.prog-fill::after{
  content:'';position:absolute;top:0;left:-100%;
  width:100%;height:100%;
  background:linear-gradient(90deg,transparent,rgba(255,255,255,.4),transparent);
  animation:shimmer 2s infinite;
}
@keyframes shimmer{0%{left:-100%}100%{left:200%}}

/* Amounts */
.amounts{
  display:flex;justify-content:space-between;
  background:#f8fafc;border-radius:8px;
  padding:10px 12px;margin-bottom:14px;
}
.amt small{display:block;font-size:.68rem;color:#718096;margin-bottom:2px}
.amt strong{font-size:.85rem;font-weight:700;color:#1a202c}
.amt.target strong{color:#1a73e8}

/* Footer card */
.card-foot{
  display:flex;align-items:center;
  justify-content:space-between;
  padding-top:12px;border-top:1px solid #f1f5f9;
}
.deadline{display:flex;align-items:center;gap:5px;font-size:.75rem;color:#a0aec0}

.btn-detail{
  display:inline-flex;align-items:center;gap:6px;
  padding:7px 15px;
  background:#ebf4ff;color:#1a73e8;
  border-radius:50px;font-size:.78rem;font-weight:600;
  transition:all .25s;
}
.btn-detail:hover{background:#1a73e8;color:#fff}
.btn-detail .circle-icon{
  width:18px;height:18px;border-radius:50%;
  background:#1a73e8;color:#fff;
  display:flex;align-items:center;justify-content:center;
  font-size:.58rem;transition:background .25s;
}
.btn-detail:hover .circle-icon{background:rgba(255,255,255,.25)}

/* ── RESPONSIVE ──────────────────────────────── */
@media(max-width:900px){.cards-grid{grid-template-columns:1fr 1fr}}
@media(max-width:600px){
  .cards-grid{grid-template-columns:1fr}
  .nav-links,.nav-search{display:none}
  .hero{padding:40px 20px 56px}
}
</style>
</head>
<body>

<!-- ══════════════════════════════════════════════
     NAVBAR
════════════════════════════════════════════════ -->
<nav class="navbar">
  <div class="nav-inner">
    <!-- Logo -->
    <a href="/Donasi/" class="nav-logo">
      <img src="/logo%20donasi.png" alt="SIBERASA">
    </a>

    <!-- Nav Links -->
    <ul class="nav-links" style="list-style:none;display:flex">
      <li><a href="/Donasi/" class="active">Beranda</a></li>
      <li><a href="/Donasi/program.php">Program Donasi</a></li>
      <li><a href="/Donasi/galangdana.php">Galang Dana</a></li>
      <li><a href="/Donasi/tentang.php">Tentang kami</a></li>
      <li><a href="/Donasi/kontak.php">Kontak</a></li>
    </ul>

    <!-- Search -->
    <div class="nav-search">
      <i class="fas fa-search"></i>
      <input type="text" placeholder="Cari Program Donasi...">
    </div>

    <!-- Actions -->
    <div class="nav-actions">
      <button class="btn btn-primary" onclick="window.location.href='login.php'">Masuk</button>
      <button class="btn btn-outline" onclick="window.location.href='register.php'">Daftar</button>
    </div>
  </div>
</nav>


<!-- ══════════════════════════════════════════════
     HERO
════════════════════════════════════════════════ -->
<section class="hero">
  <!-- Dekorasi blob -->
  <div class="hero-blob-l"></div>
  <div class="hero-blob-c"></div>

  <!-- Daun SVG kanan -->
  <svg class="hero-leaf hero-leaf-big" viewBox="0 0 120 190" fill="none">
    <path d="M60 4C60 4 112 52 112 104C112 156 60 186 60 186C60 186 8 156 8 104C8 52 60 4 60 4Z" fill="#34c759" opacity=".28"/>
    <path d="M60 18C60 18 102 60 102 104C102 148 60 172 60 172" stroke="#34c759" stroke-width="1.8" opacity=".45"/>
    <path d="M60 4C60 4 42 44 44 84C46 124 60 186 60 186" stroke="#28a745" stroke-width="1.4" opacity=".35"/>
  </svg>
  <svg class="hero-leaf hero-leaf-sm" viewBox="0 0 80 126" fill="none">
    <path d="M40 3C40 3 76 36 76 70C76 104 40 123 40 123C40 123 4 104 4 70C4 36 40 3 40 3Z" fill="#1a73e8" opacity=".18"/>
    <path d="M40 3C40 3 28 30 30 60C32 90 40 123 40 123" stroke="#1a73e8" stroke-width="1.4" opacity=".28"/>
  </svg>

  <div class="hero-content">
    <h1>Selamat Datang di SIBERASA</h1>
    <h2>Berbagi Rasa, <span>Hadirkan Manfaat</span></h2>
  </div>
</section>


<!-- ══════════════════════════════════════════════
     PROGRAM DONASI TERBARU
════════════════════════════════════════════════ -->
<section class="section">
  <div class="wrap">



    <!-- Header baris -->
    <div class="sec-header">
      <h2 class="sec-title">Program Donasi Terbaru</h2>
      <a href="/Donasi/program.php" class="btn-see-all">Lihat semua Program <i class="fas fa-arrow-right"></i></a>
    </div>

    <!-- Wrapper border hijau -->
    <div class="cards-wrap">
      <div class="cards-grid">

        <?php foreach($programs as $p): ?>
        <div class="card">
          <!-- Gambar -->
          <div class="card-img">
            <img src="<?= $p['img'] ?>" alt="<?= htmlspecialchars($p['judul']) ?>"
                 onerror="this.style.display='none'">
            <span class="badge <?= $p['slug_kat'] ?>"><?= $p['kategori'] ?></span>
          </div>

          <!-- Body -->
          <div class="card-body">
            <h3 class="card-title"><?= htmlspecialchars($p['judul']) ?></h3>
            <p class="card-org"><i class="fas fa-circle" style="font-size:.45rem;margin-top:1px"></i><?= htmlspecialchars($p['org']) ?></p>

            <!-- Progress -->
            <div class="prog-row">
              <span class="prog-pct"><?= $p['persen'] ?>% terkumpul</span>
            </div>
            <div class="prog-bg">
              <div class="prog-fill" style="width:<?= $p['persen'] ?>%"></div>
            </div>

            <!-- Jumlah -->
            <div class="amounts">
              <div class="amt">
                <small>terkumpul</small>
                <strong><?= rp($p['terkumpul']) ?></strong>
              </div>
              <div class="amt target">
                <small>target</small>
                <strong><?= rp($p['target']) ?></strong>
              </div>
            </div>

            <!-- Footer -->
            <div class="card-foot">
              <span class="deadline">
                <i class="far fa-calendar-alt"></i>
                berakhir dalam <?= $p['sisa_hari'] ?> hari
              </span>
              <a href="detail.php?id=<?= $p['id'] ?>" class="btn-detail">
                Lihat Detail
                <span class="circle-icon"><i class="fas fa-chevron-right"></i></span>
              </a>
            </div>
          </div>
        </div>
        <?php endforeach; ?>

      </div><!-- /cards-grid -->
    </div><!-- /cards-wrap -->

    <!-- Cek Status Banner (Dipindah ke bawah) -->
    <div class="cek-status-banner" style="margin-top: 40px; margin-bottom: 0;">
      <div class="cek-icon-wrap">
        <i class="fas fa-search"></i>
      </div>
      <div class="cek-text">
        <h4>Sudah melakukan pengajuan atau donasi?</h4>
        <p>Cek status pengajuan atau donasi Anda dengan memasukkan kode transaksi.</p>
      </div>
      <button class="btn btn-cek" onclick="window.location.href='cek_status.php'">Cek Status</button>
    </div>

  </div>
</section>


<?php require_once 'includes/footer.php'; ?>
