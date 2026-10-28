<?php
$page_title   = 'Hubungi Kami - SIBERASA';
$page_desc    = 'Kami siap membantu Anda. Jika memiliki pertanyaan, saran, atau ingin berdiskusi lebih lanjut, jangan ragu untuk menghubungi kami.';
$current_page = 'kontak';
require_once __DIR__ . '/../assets/config/database.php';

$success = false;
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama   = trim($_POST['nama'] ?? '');
    $email  = trim($_POST['email'] ?? '');
    $subjek = trim($_POST['subjek'] ?? '');
    $pesan  = trim($_POST['pesan'] ?? '');

    if (empty($nama)) {
        $error_msg = 'Silakan masukkan nama lengkap Anda.';
    } elseif (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = 'Format alamat email tidak valid.';
    } elseif (empty($pesan)) {
        $error_msg = 'Silakan tuliskan pesan yang ingin Anda sampaikan.';
    } else {
        // Simpan log pesan masuk ke file
        $log = date('Y-m-d H:i:s') . " | $nama | $email | $subjek | " . str_replace(["\r", "\n"], ' ', $pesan) . "\n";
        @file_put_contents(__DIR__ . '/pesan_masuk.txt', $log, FILE_APPEND);
        $success = true;
    }
}

require_once '../includes/header.php';
?>

<style>
/* ═══════════════════════════════════════════════════════════════════
   HALAMAN KONTAK - SESUAI DESAIN REFERENSI
════════════════════════════════════════════════════════════════════ */
.kontak-page-wrap {
    font-family: 'Poppins', sans-serif;
    background-color: #ffffff;
    color: #1e293b;
    overflow-x: hidden;
}

/* ── Hero Section ── */
.kontak-hero {
    position: relative;
    background: radial-gradient(circle at 85% 25%, rgba(219, 234, 254, 0.45) 0%, transparent 45%),
                radial-gradient(circle at 10% 20%, rgba(224, 242, 254, 0.45) 0%, transparent 40%),
                linear-gradient(180deg, #edf5fd 0%, #f6faff 70%, #ffffff 100%);
    padding: 50px 24px 44px 24px;
    border-bottom: 1px solid #f0f4f9;
}

.kontak-hero-container {
    max-width: 1140px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 40px;
}

.kontak-hero-content {
    flex: 1;
    max-width: 580px;
    z-index: 2;
}

.kontak-badge-row {
    display: inline-flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 18px;
}

.kontak-badge-pill {
    background: #dbeafe;
    color: #1d4ed8;
    font-size: 0.82rem;
    font-weight: 600;
    padding: 5px 16px;
    border-radius: 50px;
    display: inline-block;
    letter-spacing: 0.1px;
}

.kontak-badge-line {
    width: 26px;
    height: 2px;
    background: #93c5fd;
    border-radius: 2px;
}

.kontak-badge-dot {
    width: 7px;
    height: 7px;
    background: #93c5fd;
    border-radius: 50%;
}

.kontak-hero-title {
    font-size: clamp(2.3rem, 4.5vw, 3.4rem);
    font-weight: 800;
    color: #0b326f;
    line-height: 1.15;
    margin: 0 0 16px 0;
    letter-spacing: -0.5px;
}

.kontak-hero-desc {
    font-size: 0.96rem;
    color: #4b5e7e;
    line-height: 1.65;
    margin: 0;
    font-weight: 400;
}

.kontak-hero-image-wrap {
    flex-shrink: 0;
    width: 360px;
    max-width: 44%;
    display: flex;
    justify-content: flex-end;
    align-items: center;
    z-index: 2;
}

.kontak-hero-image-wrap img {
    width: 100%;
    max-height: 290px;
    object-fit: contain;
    mix-blend-mode: multiply;
}

/* ── Main Content Section ── */
.kontak-main-section {
    padding: 44px 24px 80px 24px;
    background: #ffffff;
}

.kontak-main-grid {
    max-width: 1140px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: 1.15fr 0.95fr;
    gap: 36px;
    align-items: start;
}

/* ── Kolom Kiri: Informasi Kontak ── */
.kontak-info-col {
    display: flex;
    flex-direction: column;
}

.kontak-section-heading {
    font-size: 1.45rem;
    font-weight: 700;
    color: #0f2851;
    margin: 0 0 8px 0;
}

.kontak-section-subtitle {
    font-size: 0.88rem;
    color: #5f718d;
    line-height: 1.55;
    margin: 0 0 28px 0;
}

.kontak-cards-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 18px;
}

.kontak-card {
    background: #ffffff;
    border: 1px solid #e5edf6;
    border-radius: 16px;
    padding: 20px 18px;
    display: flex;
    align-items: flex-start;
    gap: 14px;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.02);
    transition: all 0.25s ease;
}

.kontak-card:hover {
    border-color: #93c5fd;
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(26, 115, 232, 0.08);
}

.kontak-card-icon {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: #e0f0fe;
    color: #1a73e8;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    flex-shrink: 0;
}

.kontak-card-content {
    flex: 1;
    min-width: 0;
}

.kontak-card-title {
    font-size: 0.96rem;
    font-weight: 700;
    color: #0f2851;
    margin: 0 0 4px 0;
}

.kontak-card-primary {
    font-size: 0.82rem;
    color: #334155;
    font-weight: 500;
    line-height: 1.45;
    margin: 0 0 3px 0;
    word-break: break-word;
}

.kontak-card-secondary {
    font-size: 0.77rem;
    color: #64748b;
    line-height: 1.4;
    margin: 0;
}

/* ── Kolom Kanan: Form Kirim Pesan ── */
.kontak-form-card {
    background: #f1f7fd;
    border: 1px solid #e1ecf8;
    border-radius: 20px;
    padding: 32px 32px 34px 32px;
    box-shadow: 0 4px 18px rgba(15, 23, 42, 0.03);
}

.kontak-form-title {
    font-size: 1.35rem;
    font-weight: 700;
    color: #0f2851;
    margin: 0 0 6px 0;
}

.kontak-form-desc {
    font-size: 0.85rem;
    color: #64748b;
    margin: 0 0 24px 0;
}

.kontak-input-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
    margin-bottom: 14px;
}

.kontak-field-single {
    margin-bottom: 14px;
}

.kontak-input-box {
    background: #ffffff;
    border: 1px solid #dce6f1;
    border-radius: 10px;
    display: flex;
    align-items: center;
    padding: 0 14px;
    height: 48px;
    transition: all 0.2s ease;
}

.kontak-input-box.textarea-box {
    height: auto;
    align-items: flex-start;
    padding: 12px 14px;
    margin-bottom: 20px;
}

.kontak-input-box:focus-within {
    border-color: #1a73e8;
    box-shadow: 0 0 0 3.5px rgba(26, 115, 232, 0.12);
}

.kontak-icon-inside {
    color: #94a3b8;
    font-size: 0.95rem;
    margin-right: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    width: 18px;
}

.kontak-input-box.textarea-box .kontak-icon-inside {
    margin-top: 3px;
}

.kontak-input-box input,
.kontak-input-box textarea {
    width: 100%;
    border: none;
    outline: none;
    background: transparent;
    font-family: inherit;
    font-size: 0.88rem;
    color: #1e293b;
}

.kontak-input-box textarea {
    min-height: 125px;
    resize: vertical;
    line-height: 1.55;
}

.kontak-input-box input::placeholder,
.kontak-input-box textarea::placeholder {
    color: #94a3b8;
    font-size: 0.88rem;
}

.kontak-btn-submit {
    width: 100%;
    background: #1871e8;
    color: #ffffff;
    border: none;
    border-radius: 10px;
    padding: 13px 20px;
    font-size: 0.95rem;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.25s ease;
    box-shadow: 0 4px 12px rgba(24, 113, 232, 0.25);
}

.kontak-btn-submit:hover {
    background: #1360cc;
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(24, 113, 232, 0.35);
}

.kontak-btn-submit:active {
    transform: translateY(0);
}

/* Alert Notification */
.kontak-alert-box {
    border-radius: 10px;
    padding: 12px 16px;
    margin-bottom: 20px;
    font-size: 0.84rem;
    display: flex;
    align-items: center;
    gap: 10px;
}

.kontak-alert-success {
    background: #ecfdf5;
    border: 1px solid #a7f3d0;
    color: #065f46;
}

.kontak-alert-error {
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #991b1b;
}

/* ── Section FAQ Ringkas di Bawah ── */
.kontak-faq-wrap {
    max-width: 1140px;
    margin: 50px auto 0 auto;
    padding-top: 40px;
    border-top: 1px solid #edf2f7;
}

.kontak-faq-header {
    text-align: center;
    margin-bottom: 30px;
}

.kontak-faq-header h3 {
    font-size: 1.35rem;
    font-weight: 700;
    color: #0f2851;
    margin: 0 0 6px 0;
}

.kontak-faq-header p {
    font-size: 0.86rem;
    color: #64748b;
    margin: 0;
}

.kontak-faq-list {
    max-width: 760px;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.kontak-faq-item {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.2s ease;
}

.kontak-faq-item:hover {
    border-color: #cbd5e1;
}

.kontak-faq-btn {
    width: 100%;
    padding: 16px 20px;
    text-align: left;
    background: none;
    border: none;
    font-family: inherit;
    font-size: 0.9rem;
    font-weight: 600;
    color: #1e293b;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
}

.kontak-faq-body {
    display: none;
    padding: 0 20px 16px 20px;
    color: #64748b;
    font-size: 0.85rem;
    line-height: 1.6;
}

/* ── Responsive Styling ── */
@media (max-width: 992px) {
    .kontak-hero-container {
        flex-direction: column;
        text-align: center;
        gap: 32px;
    }
    .kontak-hero-content {
        max-width: 100%;
    }
    .kontak-badge-row {
        justify-content: center;
    }
    .kontak-hero-image-wrap {
        max-width: 300px;
        width: 100%;
        justify-content: center;
    }
    .kontak-main-grid {
        grid-template-columns: 1fr;
        gap: 40px;
    }
}

@media (max-width: 640px) {
    .kontak-hero {
        padding: 36px 18px 40px 18px;
    }
    .kontak-cards-grid {
        grid-template-columns: 1fr;
    }
    .kontak-input-row {
        grid-template-columns: 1fr;
    }
    .kontak-form-card {
        padding: 24px 20px;
    }
}
</style>

<div class="kontak-page-wrap">
    
    <!-- ═══════════════════════════════════════════════════════════════════
         1. HERO HEADER SECTION
         ═══════════════════════════════════════════════════════════════════ -->
    <section class="kontak-hero">
        <div class="kontak-hero-container">
            
            <div class="kontak-hero-content">
                <div class="kontak-badge-row">
                    <span class="kontak-badge-pill">Kontak Kami</span>
                    <span class="kontak-badge-line"></span>
                    <span class="kontak-badge-dot"></span>
                </div>
                
                <h1 class="kontak-hero-title">Hubungi Kami</h1>
                
                <p class="kontak-hero-desc">
                    Kami siap membantu Anda. Jika memiliki pertanyaan, saran,<br>
                    atau ingin berdiskusi lebih lanjut, jangan ragu untuk menghubungi kami.
                </p>
            </div>

            <div class="kontak-hero-image-wrap">
                <img src="/Donasi/assets/img/kontak_hero.jpg" alt="Ilustrasi Hubungi Kami">
            </div>

        </div>
    </section>

    <!-- ═══════════════════════════════════════════════════════════════════
         2. MAIN CONTENT (INFORMASI KONTAK & FORM KIRIM PESAN)
         ═══════════════════════════════════════════════════════════════════ -->
    <section class="kontak-main-section">
        <div class="kontak-main-grid">

            <!-- ── KOLOM KIRI: INFORMASI KONTAK ── -->
            <div class="kontak-info-col">
                <h2 class="kontak-section-heading">Informasi Kontak</h2>
                <p class="kontak-section-subtitle">
                    Anda dapat menghubungi kami melalui berbagai saluran berikut<br>
                    atau mengisi formulir yang tersedia di samping.
                </p>

                <div class="kontak-cards-grid">
                    
                    <!-- Card 1: Alamat -->
                    <div class="kontak-card">
                        <div class="kontak-card-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div class="kontak-card-content">
                            <h4 class="kontak-card-title">Alamat</h4>
                            <p class="kontak-card-primary">UIN Siber Syekh Nurjati Cirebon</p>
                            <p class="kontak-card-secondary">
                                Jl. Perjuangan, Sunyaragi, Kec. Kesambi,<br>
                                Kota Cirebon, Jawa Barat 45132
                            </p>
                        </div>
                    </div>

                    <!-- Card 2: Email -->
                    <div class="kontak-card">
                        <div class="kontak-card-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="kontak-card-content">
                            <h4 class="kontak-card-title">Email</h4>
                            <p class="kontak-card-primary">
                                <a href="mailto:siberasa@uinsus.ac.id" style="color:inherit; text-decoration:none;">siberasa@uinsus.ac.id</a>
                            </p>
                            <p class="kontak-card-secondary">
                                Kami akan membalas email Anda secepatnya.
                            </p>
                        </div>
                    </div>

                    <!-- Card 3: Telepon -->
                    <div class="kontak-card">
                        <div class="kontak-card-icon">
                            <i class="fas fa-phone-alt"></i>
                        </div>
                        <div class="kontak-card-content">
                            <h4 class="kontak-card-title">Telepon</h4>
                            <p class="kontak-card-primary">
                                <a href="tel:+6281234567890" style="color:inherit; text-decoration:none;">+62 812 3456 7890</a>
                            </p>
                            <p class="kontak-card-secondary">
                                (Senin - Jumat, 08.00 - 16.00 WIB)
                            </p>
                        </div>
                    </div>

                    <!-- Card 4: Media Sosial -->
                    <div class="kontak-card">
                        <div class="kontak-card-icon">
                            <i class="fab fa-instagram"></i>
                        </div>
                        <div class="kontak-card-content">
                            <h4 class="kontak-card-title">Media Sosial</h4>
                            <p class="kontak-card-primary">
                                <a href="https://instagram.com/siberasa_uin" target="_blank" rel="noopener noreferrer" style="color:inherit; text-decoration:none;">@siberasa_uin</a>
                            </p>
                            <p class="kontak-card-secondary">
                                Ikuti kami untuk mendapatkan informasi terbaru.
                            </p>
                        </div>
                    </div>

                </div>
            </div>

            <!-- ── KOLOM KANAN: FORM KIRIM PESAN ── -->
            <div class="kontak-form-card">
                <h3 class="kontak-form-title">Kirim Pesan</h3>
                <p class="kontak-form-desc">Isi form di bawah ini untuk mengirim pesan kepada kami.</p>

                <?php if ($success): ?>
                    <div class="kontak-alert-box kontak-alert-success">
                        <i class="fas fa-check-circle" style="font-size:1.1rem; flex-shrink:0;"></i>
                        <span>Pesan Anda berhasil dikirim! Tim SIBERASA akan segera menghubungi Anda.</span>
                    </div>
                <?php endif; ?>

                <?php if (!empty($error_msg)): ?>
                    <div class="kontak-alert-box kontak-alert-error">
                        <i class="fas fa-exclamation-circle" style="font-size:1.1rem; flex-shrink:0;"></i>
                        <span><?= htmlspecialchars($error_msg) ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" action="kontak.php">
                    
                    <!-- Row 1: Nama Lengkap & Email -->
                    <div class="kontak-input-row">
                        <div class="kontak-input-box">
                            <span class="kontak-icon-inside">
                                <i class="far fa-user"></i>
                            </span>
                            <input type="text" name="nama" value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>" placeholder="Nama Lengkap" required>
                        </div>

                        <div class="kontak-input-box">
                            <span class="kontak-icon-inside">
                                <i class="far fa-envelope"></i>
                            </span>
                            <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="Email" required>
                        </div>
                    </div>

                    <!-- Row 2: Subjek -->
                    <div class="kontak-field-single">
                        <div class="kontak-input-box">
                            <span class="kontak-icon-inside">
                                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path>
                                    <line x1="7" y1="7" x2="7.01" y2="7"></line>
                                </svg>
                            </span>
                            <input type="text" name="subjek" value="<?= htmlspecialchars($_POST['subjek'] ?? '') ?>" placeholder="Subjek">
                        </div>
                    </div>

                    <!-- Row 3: Pesan Anda -->
                    <div class="kontak-input-box textarea-box">
                        <span class="kontak-icon-inside">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                            </svg>
                        </span>
                        <textarea name="pesan" rows="4" placeholder="Pesan Anda..." required><?= htmlspecialchars($_POST['pesan'] ?? '') ?></textarea>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="kontak-btn-submit">
                        <i class="fas fa-paper-plane"></i> Kirim Pesan
                    </button>

                </form>
            </div>

        </div>

        <!-- ═══════════════════════════════════════════════════════════════════
             3. SECTION FAQ RINGKAS (PILIHAN OPSIONAL DI BAWAH)
             ═══════════════════════════════════════════════════════════════════ -->
        <div class="kontak-faq-wrap">
            <div class="kontak-faq-header">
                <h3>Pertanyaan yang Sering Diajukan</h3>
                <p>Temukan jawaban cepat atas pertanyaan umum seputar platform donasi SIBERASA.</p>
            </div>

            <div class="kontak-faq-list">
                <?php
                $faqs = [
                    ['q' => 'Bagaimana cara berdonasi di SIBERASA?', 'a' => 'Pilih program donasi yang ingin Anda bantu di halaman Program, tentukan nominal dan metode pembayaran (QRIS, Transfer Bank, atau E-Wallet), lalu unggah bukti pembayaran.'],
                    ['q' => 'Berapa batas minimal donasi?', 'a' => 'Anda dapat berdonasi mulai dari nominal Rp 10.000 melalui berbagai metode pembayaran yang tersedia.'],
                    ['q' => 'Bagaimana jika saya ingin mengajukan program penggalangan dana?', 'a' => 'Anda dapat mendaftar sebagai pengusul program melalui menu "Daftar", kemudian mengisi formulir pengajuan program serta melampirkan berkas verifikasi.']
                ];
                foreach ($faqs as $i => $faq):
                ?>
                <div class="kontak-faq-item">
                    <button type="button" class="kontak-faq-btn" onclick="toggleFaqItem(<?= $i ?>)">
                        <span><?= htmlspecialchars($faq['q']) ?></span>
                        <i id="faq-icon-<?= $i ?>" class="fas fa-chevron-down" style="color:#1a73e8; font-size:0.85rem; transition:transform 0.25s;"></i>
                    </button>
                    <div id="faq-body-<?= $i ?>" class="kontak-faq-body">
                        <p style="margin:0;"><?= htmlspecialchars($faq['a']) ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

    </section>

</div>

<script>
function toggleFaqItem(i) {
    const body = document.getElementById('faq-body-' + i);
    const icon = document.getElementById('faq-icon-' + i);
    const isOpen = body.style.display === 'block';
    
    body.style.display = isOpen ? 'none' : 'block';
    icon.style.transform = isOpen ? 'rotate(0deg)' : 'rotate(180deg)';
}
</script>

<?php require_once '../includes/footer.php'; ?>
