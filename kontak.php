<?php
$page_title   = 'Kontak - SIBERASA';
$page_desc    = 'Hubungi tim SIBERASA untuk pertanyaan, saran, atau kolaborasi program donasi.';
$current_page = 'kontak';
require_once 'includes/data.php';
require_once 'includes/header.php';

$success = false;
$errors  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama   = trim($_POST['nama'] ?? '');
    $email  = trim($_POST['email'] ?? '');
    $subjek = trim($_POST['subjek'] ?? '');
    $pesan  = trim($_POST['pesan'] ?? '');

    if (empty($nama))   $errors[] = 'Nama wajib diisi';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email tidak valid';
    if (empty($pesan))  $errors[] = 'Pesan wajib diisi';

    if (empty($errors)) {
        // Simpan ke file (karena tanpa DB)
        $log = date('Y-m-d H:i:s') . " | $nama | $email | $subjek | $pesan\n";
        file_put_contents(__DIR__ . '/pesan_masuk.txt', $log, FILE_APPEND);
        $success = true;
    }
}
?>

<!-- BANNER -->
<div style="background: linear-gradient(135deg, #EBF4FF 0%, #DBEAFE 60%, #E0F2FE 100%); padding: 64px 24px; text-align:center;">
    <div class="container">
        <div class="hero-badge" style="display:inline-flex;margin-bottom:16px;"><i class="fas fa-envelope"></i> Hubungi Kami</div>
        <h1 style="font-size: clamp(1.8rem,4vw,2.6rem); font-weight:800; color:var(--primary); margin-bottom:14px;">Kontak SIBERASA</h1>
        <p style="color:var(--text-medium); font-size:0.95rem; max-width:500px; margin:0 auto;">
            Ada pertanyaan atau ingin berkolaborasi? Tim kami siap membantu Anda dengan senang hati
        </p>
    </div>
</div>

<section style="padding:60px 0; background:#F8FAFF;">
    <div class="container">
        <div style="display:grid; grid-template-columns:1fr 1.4fr; gap:40px; align-items:start;">

            <!-- Info Kontak -->
            <div>
                <h2 style="font-size:1.3rem;font-weight:700;color:var(--text-dark);margin-bottom:24px;">Informasi Kontak</h2>

                <?php
                $contacts = [
                    ['icon'=>'fas fa-map-marker-alt','color'=>'#1A73E8','label'=>'Alamat','val'=>'Jl. Contoh No. 123, Kelurahan Maju, Jakarta Selatan, DKI Jakarta 12345'],
                    ['icon'=>'fas fa-envelope','color'=>'#34C759','label'=>'Email','val'=>'info@siberasa.id'],
                    ['icon'=>'fas fa-phone','color'=>'#8B5CF6','label'=>'Telepon','val'=>'+62 21 1234 5678'],
                    ['icon'=>'fab fa-whatsapp','color'=>'#25D366','label'=>'WhatsApp','val'=>'+62 812 3456 7890'],
                    ['icon'=>'fas fa-clock','color'=>'#F59E0B','label'=>'Jam Operasional','val'=>'Senin–Jumat: 08.00–17.00 WIB'],
                ];
                foreach ($contacts as $c):
                ?>
                <div style="display:flex;gap:16px;padding:18px;background:white;border-radius:var(--radius-md);margin-bottom:12px;box-shadow:var(--shadow-sm);border:1px solid var(--border);transition:all 0.2s;"
                     onmouseover="this.style.borderColor='var(--primary)';this.style.transform='translateX(4px)'"
                     onmouseout="this.style.borderColor='var(--border)';this.style.transform=''">
                    <div style="width:46px;height:46px;border-radius:12px;background:<?= $c['color'] ?>20;display:flex;align-items:center;justify-content:center;color:<?= $c['color'] ?>;font-size:1.1rem;flex-shrink:0;">
                        <i class="<?= $c['icon'] ?>"></i>
                    </div>
                    <div>
                        <small style="color:var(--text-light);font-size:0.75rem;font-weight:500;text-transform:uppercase;letter-spacing:0.5px;"><?= $c['label'] ?></small>
                        <p style="font-size:0.88rem;color:var(--text-dark);font-weight:500;margin-top:2px;"><?= $c['val'] ?></p>
                    </div>
                </div>
                <?php endforeach; ?>

                <!-- Social Media -->
                <div style="background:white;border-radius:var(--radius-md);padding:20px;box-shadow:var(--shadow-sm);border:1px solid var(--border);margin-top:8px;">
                    <p style="font-size:0.85rem;font-weight:600;color:var(--text-dark);margin-bottom:14px;">Ikuti Kami:</p>
                    <div style="display:flex;gap:10px;flex-wrap:wrap;">
                        <?php
                        $socials = [
                            ['icon'=>'fab fa-instagram','color'=>'#E1306C','label'=>'Instagram'],
                            ['icon'=>'fab fa-facebook-f','color'=>'#1877F2','label'=>'Facebook'],
                            ['icon'=>'fab fa-x-twitter','color'=>'#000000','label'=>'X (Twitter)'],
                            ['icon'=>'fab fa-youtube','color'=>'#FF0000','label'=>'YouTube'],
                        ];
                        foreach ($socials as $s):
                        ?>
                        <a href="#" style="display:flex;align-items:center;gap:8px;padding:8px 14px;border:1.5px solid var(--border);border-radius:50px;font-size:0.8rem;color:var(--text-medium);font-weight:500;transition:all 0.2s;"
                           onmouseover="this.style.borderColor='<?= $s['color'] ?>';this.style.color='<?= $s['color'] ?>'"
                           onmouseout="this.style.borderColor='var(--border)';this.style.color='var(--text-medium)'">
                            <i class="<?= $s['icon'] ?>"></i><?= $s['label'] ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Form Kontak -->
            <div style="background:white;border-radius:var(--radius-xl);padding:36px;box-shadow:var(--shadow-lg);border:1px solid var(--border);">
                <h2 style="font-size:1.3rem;font-weight:700;color:var(--text-dark);margin-bottom:6px;">Kirim Pesan</h2>
                <p style="font-size:0.85rem;color:var(--text-light);margin-bottom:28px;">Kami akan membalas pesan Anda dalam 1×24 jam</p>

                <?php if ($success): ?>
                <div style="background:#F0FDF4;border:2px solid #34C759;border-radius:var(--radius-md);padding:20px;margin-bottom:24px;display:flex;gap:12px;align-items:center;">
                    <i class="fas fa-check-circle" style="color:#34C759;font-size:1.5rem;flex-shrink:0;"></i>
                    <div>
                        <strong style="color:#166534;">Pesan Terkirim!</strong>
                        <p style="font-size:0.85rem;color:#166534;margin-top:4px;">Terima kasih telah menghubungi kami. Tim kami akan segera merespons pesan Anda.</p>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                <div style="background:#FEF2F2;border:2px solid #EF4444;border-radius:var(--radius-md);padding:16px;margin-bottom:20px;">
                    <?php foreach ($errors as $e): ?>
                    <p style="color:#DC2626;font-size:0.85rem;"><i class="fas fa-exclamation-circle"></i> <?= $e ?></p>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <form method="POST" action="kontak.php">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                        <div class="form-group">
                            <label>Nama Lengkap *</label>
                            <div class="input-wrap">
                                <i class="fas fa-user"></i>
                                <input type="text" name="nama" value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>" placeholder="Nama Anda" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Email *</label>
                            <div class="input-wrap">
                                <i class="fas fa-envelope"></i>
                                <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="email@contoh.com" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>No. Telepon</label>
                        <div class="input-wrap">
                            <i class="fas fa-phone"></i>
                            <input type="tel" name="telepon" value="<?= htmlspecialchars($_POST['telepon'] ?? '') ?>" placeholder="08xxxxxxxxxx">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Subjek</label>
                        <div class="input-wrap">
                            <i class="fas fa-tag"></i>
                            <input type="text" name="subjek" value="<?= htmlspecialchars($_POST['subjek'] ?? '') ?>" placeholder="Topik pesan Anda">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Pesan *</label>
                        <textarea name="pesan" rows="5" 
                            placeholder="Tuliskan pesan, pertanyaan, atau saran Anda di sini..."
                            style="width:100%;padding:12px 14px;border:2px solid var(--border);border-radius:var(--radius-sm);font-family:'Poppins',sans-serif;font-size:0.88rem;resize:vertical;outline:none;min-height:130px;transition:border-color 0.2s;"
                            onfocus="this.style.borderColor='var(--primary)'"
                            onblur="this.style.borderColor='var(--border)'"
                            required><?= htmlspecialchars($_POST['pesan'] ?? '') ?></textarea>
                    </div>

                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:20px;">
                        <input type="checkbox" id="agreeCheck" required style="width:16px;height:16px;accent-color:var(--primary);">
                        <label for="agreeCheck" style="font-size:0.82rem;color:var(--text-medium);">
                            Saya setuju dengan <a href="#" style="color:var(--primary);">Kebijakan Privasi</a> SIBERASA
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width:100%;padding:14px;font-size:0.95rem;">
                        <i class="fas fa-paper-plane"></i> Kirim Pesan
                    </button>
                </form>
            </div>
        </div>

        <!-- FAQ -->
        <div style="margin-top:60px;">
            <div style="text-align:center;margin-bottom:36px;">
                <div class="section-title" style="text-align:center;">
                    <span>Pertanyaan Umum</span>
                    FAQ
                </div>
            </div>
            <div style="max-width:700px;margin:0 auto;">
                <?php
                $faqs = [
                    ['q'=>'Bagaimana cara memastikan donasi saya aman?','a'=>'Setiap donasi di SIBERASA dijamin keamanannya melalui enkripsi data dan sistem audit keuangan yang transparan. Anda dapat memantau penggunaan dana secara real-time.'],
                    ['q'=>'Berapa minimal donasi yang bisa dilakukan?','a'=>'Anda dapat berdonasi mulai dari Rp 10.000 saja. Tidak ada batas maksimal donasi.'],
                    ['q'=>'Bagaimana cara menjadi penyelenggara program?','a'=>'Anda dapat mendaftar sebagai penyelenggara program dengan mengisi formulir pendaftaran dan melengkapi dokumen legalitas organisasi Anda.'],
                    ['q'=>'Apakah ada biaya administrasi?','a'=>'SIBERASA mengenakan biaya administrasi minimal untuk mendukung operasional platform. Detail biaya dapat dilihat di halaman syarat dan ketentuan.'],
                ];
                foreach ($faqs as $i => $faq):
                ?>
                <div style="background:white;border-radius:var(--radius-md);margin-bottom:12px;box-shadow:var(--shadow-sm);border:1px solid var(--border);overflow:hidden;">
                    <button onclick="toggleFaq(<?= $i ?>)" 
                            style="width:100%;padding:18px 20px;text-align:left;background:none;border:none;font-family:'Poppins',sans-serif;font-size:0.9rem;font-weight:600;color:var(--text-dark);cursor:pointer;display:flex;justify-content:space-between;align-items:center;gap:12px;">
                        <?= $faq['q'] ?>
                        <i id="faq-icon-<?= $i ?>" class="fas fa-plus" style="color:var(--primary);font-size:0.85rem;flex-shrink:0;transition:transform 0.3s;"></i>
                    </button>
                    <div id="faq-<?= $i ?>" style="display:none;padding:0 20px 18px;">
                        <p style="color:var(--text-medium);font-size:0.87rem;line-height:1.7;"><?= $faq['a'] ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<!-- FOOTER -->
<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-brand">
                <a href="/Donasi/" class="nav-logo"><img src="/logo.donasi.png" alt="SIBERASA" style="filter:brightness(0)invert(1);"></a>
                <p>Platform donasi online terpercaya di Indonesia.</p>
                <div class="footer-socials">
                    <a href="#" class="social-btn"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="social-btn"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social-btn"><i class="fab fa-whatsapp"></i></a>
                </div>
            </div>
            <div class="footer-col"><h5>Navigasi</h5><ul><li><a href="/Donasi/">Beranda</a></li><li><a href="program.php">Program</a></li><li><a href="tentang.php">Tentang</a></li><li><a href="kontak.php">Kontak</a></li></ul></div>
            <div class="footer-col"><h5>Kategori</h5><ul><li><a href="#">Pendidikan</a></li><li><a href="#">Sosial</a></li><li><a href="#">Kemanusiaan</a></li><li><a href="#">Kesehatan</a></li></ul></div>
            <div class="footer-col"><h5>Kontak</h5><div class="footer-contact-item"><i class="fas fa-envelope"></i><span>info@siberasa.id</span></div><div class="footer-contact-item"><i class="fas fa-phone"></i><span>+62 21 1234 5678</span></div></div>
        </div>
    </div>
    <div class="container"><div class="footer-bottom"><p>&copy; <?= date('Y') ?> SIBERASA.</p></div></div>
</footer>

<script>
function toggleFaq(i) {
    const content = document.getElementById('faq-' + i);
    const icon = document.getElementById('faq-icon-' + i);
    const isOpen = content.style.display !== 'none';
    content.style.display = isOpen ? 'none' : 'block';
    icon.style.transform = isOpen ? '' : 'rotate(45deg)';
}
</script>

<?php require_once 'includes/footer.php'; ?>
