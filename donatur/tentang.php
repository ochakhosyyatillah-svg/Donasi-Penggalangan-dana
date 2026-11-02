<?php
$page_title   = 'Tentang Kami - SIBERASA';
$page_desc    = 'Pelajari tentang SIBERASA, misi kami, dan komitmen kami untuk menghubungkan donatur dengan program kemanusiaan yang nyata.';
$current_page = 'tentang';
require_once __DIR__ . '/../assets/config/database.php';
require_once '../includes/header.php';
?>

<style>
/* ── TENTANG HERO ────────────────────────── */
.tentang-hero {
  background: linear-gradient(150deg, #dbeafe 0%, #e0f2fe 50%, #eff6ff 100%);
  padding: 64px 28px 56px;
  position: relative;
  overflow: hidden;
}
.tentang-hero::before {
  content: '';
  position: absolute;
  top: -60px; right: -60px;
  width: 340px; height: 340px;
  background: rgba(59,130,246,.08);
  border-radius: 50%;
}
.tentang-hero::after {
  content: '';
  position: absolute;
  bottom: -80px; left: -40px;
  width: 260px; height: 260px;
  background: rgba(34,211,238,.07);
  border-radius: 50%;
}
.tentang-hero-inner {
  max-width: 1160px;
  margin: 0 auto;
  display: grid;
  grid-template-columns: 1fr 1fr;
  align-items: center;
  gap: 56px;
  position: relative;
  z-index: 2;
}
.tentang-badge {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: rgba(255,255,255,.7);
  border: 1px solid rgba(59,130,246,.25);
  color: #1a73e8;
  font-size: .78rem;
  font-weight: 600;
  padding: 5px 14px;
  border-radius: 50px;
  margin-bottom: 18px;
  backdrop-filter: blur(6px);
}
.tentang-badge::before, .tentang-badge::after {
  content: '';
  display: inline-block;
  width: 28px; height: 1px;
  background: #1a73e8; opacity: .5;
}
.tentang-hero h1 {
  font-size: clamp(2rem, 4.5vw, 3rem);
  font-weight: 800;
  color: #1e293b;
  line-height: 1.15;
  margin-bottom: 16px;
}
.tentang-hero h1 span { color: #1a73e8; }
.tentang-hero-desc {
  color: #475569;
  font-size: .97rem;
  line-height: 1.8;
  max-width: 420px;
  margin-bottom: 28px;
}
.btn-tentang {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  background: #1a73e8;
  color: #fff;
  padding: 13px 28px;
  border-radius: 50px;
  font-size: .9rem;
  font-weight: 600;
  text-decoration: none;
  box-shadow: 0 6px 20px rgba(26,115,232,.35);
  transition: all .25s;
}
.btn-tentang:hover {
  background: #1557b0;
  transform: translateY(-2px);
  box-shadow: 0 10px 28px rgba(26,115,232,.4);
}
.tentang-hero-img {
  display: flex;
  justify-content: center;
  align-items: center;
}
.hero-illus-wrap {
  position: relative;
  width: 340px; height: 340px;
}
.hero-illus-circle-bg {
  position: absolute; inset: 0;
  background: radial-gradient(circle at 55% 45%, #bfdbfe 0%, #dbeafe 60%, transparent 80%);
  border-radius: 50%;
}
.hero-illus-deco {
  position: absolute;
  width: 48px; height: 48px;
  background: rgba(255,255,255,.75);
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.3rem;
  box-shadow: 0 4px 16px rgba(26,115,232,.15);
  animation: floatDeco 5s ease-in-out infinite;
}
.hero-illus-deco.d1 { top:18px; left:30px; animation-delay:0s; }
.hero-illus-deco.d2 { top:30px; right:24px; animation-delay:1.2s; }
.hero-illus-deco.d3 { bottom:44px; left:14px; animation-delay:2.5s; }
.hero-illus-deco.d4 { bottom:30px; right:32px; animation-delay:.8s; }
@keyframes floatDeco {
  0%,100%{transform:translateY(0)}50%{transform:translateY(-10px)}
}
.hero-illus-heart {
  position: absolute; inset: 0;
  display: flex; align-items: center; justify-content: center;
}
.hero-heart-svg {
  width: 190px; height: 190px;
  filter: drop-shadow(0 12px 32px rgba(26,115,232,.3));
  animation: heartPulse 3s ease-in-out infinite;
}
@keyframes heartPulse {
  0%,100%{transform:scale(1)}50%{transform:scale(1.06)}
}
.dot-float {
  position: absolute;
  border-radius: 50%; opacity: .5;
  animation: floatDot 8s ease-in-out infinite;
}
.dot-float.a { width:12px;height:12px;background:#93c5fd;top:12%;right:8%;animation-delay:0s; }
.dot-float.b { width:8px;height:8px;background:#60a5fa;bottom:20%;right:14%;animation-delay:1.5s; }
.dot-float.c { width:6px;height:6px;background:#bfdbfe;top:35%;left:4%;animation-delay:3s; }
@keyframes floatDot {
  0%,100%{transform:translateY(0) rotate(0)}50%{transform:translateY(-16px) rotate(10deg)}
}
/* ── NILAI SECTION ───────────────────────── */
.nilai-section { background:#f8faff; padding:72px 28px; }
.nilai-section-inner { max-width:1160px; margin:0 auto; }
.nilai-header { text-align:center; margin-bottom:52px; }
.nilai-label {
  display:inline-flex; align-items:center; gap:10px;
  color:#64748b; font-size:.8rem; font-weight:600;
  letter-spacing:.05em; margin-bottom:12px;
}
.nilai-label::before, .nilai-label::after {
  content:''; display:inline-block;
  width:36px; height:1px; background:#94a3b8;
}
.nilai-header h2 {
  font-size:clamp(1.6rem,3.5vw,2.2rem); font-weight:800;
  color:#1e293b; margin-bottom:14px; line-height:1.2;
}
.nilai-header p { color:#64748b; font-size:.95rem; line-height:1.7; max-width:520px; margin:0 auto; }
.nilai-cards {
  display:grid; grid-template-columns:repeat(4,1fr); gap:22px;
}
.nilai-card {
  background:#fff; border:1px solid #e2e8f0; border-radius:18px;
  padding:30px 22px 28px;
  box-shadow:0 2px 16px rgba(0,0,0,.05);
  transition:transform .28s,box-shadow .28s;
  position:relative; overflow:hidden;
}
.nilai-card::before {
  content:''; position:absolute; top:0; left:0; right:0;
  height:3px; border-radius:18px 18px 0 0;
  background:var(--card-accent,#1a73e8);
}
.nilai-card:hover { transform:translateY(-6px); box-shadow:0 12px 36px rgba(0,0,0,.1); }
.nilai-card-icon {
  width:52px; height:52px; border-radius:14px;
  display:flex; align-items:center; justify-content:center;
  font-size:1.3rem; margin-bottom:18px;
  background:var(--card-icon-bg,#eff6ff);
  color:var(--card-accent,#1a73e8);
}
.nilai-card h3 { font-size:1rem; font-weight:700; color:#1e293b; margin-bottom:12px; }
.nilai-card p, .nilai-card ul { font-size:.84rem; color:#64748b; line-height:1.75; }
.nilai-card ul { list-style:none; padding:0; margin:0; }
.nilai-card ul li { display:flex; align-items:flex-start; gap:7px; margin-bottom:5px; }
.nilai-card ul li::before { content:'\2022'; color:var(--card-accent,#1a73e8); font-weight:700; flex-shrink:0; }
.card-visi   { --card-accent:#1a73e8; --card-icon-bg:#eff6ff; }
.card-misi   { --card-accent:#16a34a; --card-icon-bg:#f0fdf4; }
.card-nilai  { --card-accent:#7c3aed; --card-icon-bg:#f5f3ff; }
.card-tujuan { --card-accent:#0891b2; --card-icon-bg:#ecfeff; }
@media(max-width:900px) {
  .tentang-hero-inner{grid-template-columns:1fr;text-align:center}
  .tentang-hero-desc{max-width:100%}
  .nilai-cards{grid-template-columns:1fr 1fr}
}
@media(max-width:560px){
  .nilai-cards{grid-template-columns:1fr}
  .hero-illus-wrap{width:260px;height:260px}
  .hero-heart-svg{width:130px;height:130px}
}
</style>

<!-- ══ HERO ══════════════════════════════════════ -->
<section class="tentang-hero">
  <div class="dot-float a"></div>
  <div class="dot-float b"></div>
  <div class="dot-float c"></div>
  <div class="tentang-hero-inner">
    <!-- TEXT -->
    <div>
      <div class="tentang-badge">Tentang Kami</div>
      <h1>Mengenal <span>SIBERASA</span></h1>
      <p class="tentang-hero-desc">
        Kami adalah platform donasi digital yang hadir dari kepedulian terhadap sesama.
        SIBERASA menjadi jembatan antara donatur dan mereka yang membutuhkan.
      </p>
      <a href="program.php" class="btn-tentang">
        Bersama, Kita Bisa Berbagi &nbsp;<i class="fas fa-arrow-right"></i>
      </a>
    </div>
    <!-- ILUSTRASI -->
    <div class="tentang-hero-img">
      <div class="hero-illus-wrap">
        <div class="hero-illus-circle-bg"></div>
        <div class="hero-illus-deco d1">&#x1F499;</div>
        <div class="hero-illus-deco d2">&#x2728;</div>
        <div class="hero-illus-deco d3">&#x1F33F;</div>
        <div class="hero-illus-deco d4">&#x1F91D;</div>
        <div class="hero-illus-heart">
          <svg class="hero-heart-svg" viewBox="0 0 200 200" fill="none" xmlns="http://www.w3.org/2000/svg">
            <!-- Tangan kiri -->
            <ellipse cx="68" cy="162" rx="42" ry="20" fill="#bfdbfe" opacity=".6"/>
            <path d="M36 158 Q55 128 84 140 Q96 145 100 155 Q76 164 36 158Z" fill="#93c5fd"/>
            <!-- Tangan kanan -->
            <ellipse cx="132" cy="162" rx="42" ry="20" fill="#bfdbfe" opacity=".6"/>
            <path d="M164 158 Q145 128 116 140 Q104 145 100 155 Q124 164 164 158Z" fill="#93c5fd"/>
            <!-- Hati biru -->
            <path d="M100 145 C100 145 52 112 52 74 C52 54 67 42 84 44 C93 45 99 52 100 59 C101 52 107 45 116 44 C133 42 148 54 148 74 C148 112 100 145 100 145Z" fill="url(#hg)"/>
            <!-- Icon tangan -->
            <path d="M87 90 C87 87 90 85 93 85 L97 85 L97 81 C97 79 99 78 100 78 C101 78 103 79 103 81 L103 85 L107 85 C110 85 113 87 113 90 C113 93 110 96 107 96 L103 96 L103 108 C103 110 101 111 100 111 C99 111 97 110 97 108 L97 96 L93 96 C90 96 87 93 87 90Z" fill="white" opacity=".85"/>
            <!-- Daun -->
            <ellipse cx="24" cy="108" rx="12" ry="26" fill="#93c5fd" opacity=".45" transform="rotate(-28 24 108)"/>
            <ellipse cx="176" cy="108" rx="12" ry="26" fill="#93c5fd" opacity=".45" transform="rotate(28 176 108)"/>
            <defs>
              <linearGradient id="hg" x1="52" y1="42" x2="148" y2="145" gradientUnits="userSpaceOnUse">
                <stop offset="0%" stop-color="#1a73e8"/>
                <stop offset="100%" stop-color="#0ea5e9"/>
              </linearGradient>
            </defs>
          </svg>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ══ NILAI & TUJUAN ════════════════════════════ -->
<section class="nilai-section">
  <div class="nilai-section-inner">
    <div class="nilai-header">
      <div class="nilai-label">Apa yang Membuat Kami Berbeda?</div>
      <h2>Nilai dan Tujuan Kami</h2>
      <p>SIBERASA hadir dengan semangat kepedulian, empati, dan kolaborasi untuk menciptakan dampak nyata bagi masyarakat.</p>
    </div>
    <div class="nilai-cards">
      <!-- VISI -->
      <div class="nilai-card card-visi">
        <div class="nilai-card-icon"><i class="fas fa-bullseye"></i></div>
        <h3>Visi</h3>
        <p>Menjadi platform donasi dan penggalangan dana yang terpercaya, transparan, dan bermanfaat bagi masyarakat luas.</p>
      </div>
      <!-- MISI -->
      <div class="nilai-card card-misi">
        <div class="nilai-card-icon"><i class="fas fa-mountain"></i></div>
        <h3>Misi</h3>
        <ul>
          <li>Memfasilitasi kegiatan donasi dan penggalangan dana secara digital.</li>
          <li>Membangun kepercayaan melalui transparansi dan akuntabilitas.</li>
          <li>Mendorong kepedulian sosial di lingkungan sekitar kampus dan masyarakat.</li>
        </ul>
      </div>
      <!-- NILAI KAMI -->
      <div class="nilai-card card-nilai">
        <div class="nilai-card-icon"><i class="fas fa-gem"></i></div>
        <h3>Nilai Kami</h3>
        <ul>
          <li>Kepedulian</li>
          <li>Transparansi</li>
          <li>Kolaborasi</li>
          <li>Dampak Nyata</li>
        </ul>
      </div>
      <!-- TUJUAN -->
      <div class="nilai-card card-tujuan">
        <div class="nilai-card-icon"><i class="fas fa-users"></i></div>
        <h3>Tujuan</h3>
        <p>Menghubungkan hati yang peduli dengan aksi nyata, agar setiap kebaikan dapat menjadi manfaat bagi lebih banyak orang.</p>
      </div>
    </div>
  </div>
</section>

<!-- CTA -->
<section class="cta-section">
    <div class="cta-content container">
        <h2>Bergabung dalam <span>Gerakan Kebaikan</span></h2>
        <p>Jadilah bagian dari komunitas donatur SIBERASA dan bersama kita ciptakan perubahan nyata</p>
        <div class="cta-buttons">
            <a href="program.php" class="btn btn-primary btn-lg"><i class="fas fa-hand-holding-heart"></i> Donasi Sekarang</a>
            <a href="kontak.php" class="btn btn-outline btn-lg"><i class="fas fa-envelope"></i> Hubungi Kami</a>
        </div>
    </div>
</section>

<?php require_once '../includes/footer.php'; ?>
