<?php
$page_title   = 'Tentang Kami - SIBERASA';
$page_desc    = 'Pelajari tentang SIBERASA, misi kami, dan tim yang berdedikasi untuk menghubungkan donatur dengan program kemanusiaan.';
$current_page = 'tentang';
require_once '../includes/data.php';
require_once '../includes/header.php';
?>

<!-- BANNER -->
<div style="background: linear-gradient(135deg, #EBF4FF 0%, #DBEAFE 60%, #E0F2FE 100%); padding: 64px 24px; text-align:center;">
    <div class="container">
        <div class="hero-badge" style="display:inline-flex;margin-bottom:16px;"><i class="fas fa-info-circle"></i> Tentang Kami</div>
        <h1 style="font-size: clamp(1.8rem,4vw,2.8rem); font-weight:800; color:var(--primary); margin-bottom: 14px;">
            Mengenal SIBERASA
        </h1>
        <p style="color:var(--text-medium); font-size:1rem; max-width: 600px; margin: 0 auto; line-height:1.8;">
            Kami adalah platform donasi digital yang lahir dari kepedulian terhadap sesama. 
            SIBERASA hadir untuk menjadi jembatan antara donatur dan mereka yang membutuhkan.
        </p>
    </div>
</div>

<!-- VISI MISI -->
<section style="padding:60px 0;">
    <div class="container">
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:32px; margin-bottom:60px;">
            <div style="background:linear-gradient(135deg,#EBF4FF,#DBEAFE); border-radius:var(--radius-xl); padding:36px;">
                <div style="width:60px;height:60px;background:var(--primary);border-radius:50%;display:flex;align-items:center;justify-content:center;color:white;font-size:1.4rem;margin-bottom:20px;">
                    <i class="fas fa-eye"></i>
                </div>
                <h2 style="font-size:1.3rem;font-weight:700;color:var(--primary);margin-bottom:12px;">Visi Kami</h2>
                <p style="color:var(--text-medium);line-height:1.8;font-size:0.92rem;">
                    Menjadi platform donasi digital terpercaya dan berdampak terbesar di Indonesia, 
                    yang mampu menghubungkan jutaan donatur dengan program-program kemanusiaan 
                    yang nyata dan terverifikasi.
                </p>
            </div>
            <div style="background:linear-gradient(135deg,#F0FDF4,#DCFCE7); border-radius:var(--radius-xl); padding:36px;">
                <div style="width:60px;height:60px;background:var(--secondary);border-radius:50%;display:flex;align-items:center;justify-content:center;color:white;font-size:1.4rem;margin-bottom:20px;">
                    <i class="fas fa-bullseye"></i>
                </div>
                <h2 style="font-size:1.3rem;font-weight:700;color:var(--secondary);margin-bottom:12px;">Misi Kami</h2>
                <ul style="color:var(--text-medium);line-height:2;font-size:0.92rem;">
                    <li>✅ Memudahkan proses donasi secara digital</li>
                    <li>✅ Memastikan transparansi penggunaan dana</li>
                    <li>✅ Mendukung program sosial yang berdampak nyata</li>
                    <li>✅ Membangun ekosistem filantropi yang sehat</li>
                </ul>
            </div>
        </div>

        <!-- NILAI -->
        <div style="text-align:center; margin-bottom:40px;">
            <div class="section-title" style="text-align:center;">
                <span>Yang Kami Pegang</span>
                Nilai-Nilai SIBERASA
            </div>
        </div>

        <div style="display:grid; grid-template-columns:repeat(4,1fr); gap:20px; margin-bottom:60px;">
            <?php
            $values = [
                ['icon'=>'fas fa-shield-alt','title'=>'Kepercayaan','desc'=>'Kami menjaga kepercayaan donatur dengan pengelolaan dana yang amanah','color'=>'#1A73E8','bg'=>'#EBF4FF'],
                ['icon'=>'fas fa-eye','title'=>'Transparansi','desc'=>'Setiap donasi dilaporkan secara terbuka dan dapat dipantau','color'=>'#34C759','bg'=>'#F0FDF4'],
                ['icon'=>'fas fa-heart','title'=>'Kepedulian','desc'=>'Kami peduli terhadap setiap penerima manfaat dari program donasi','color'=>'#EF4444','bg'=>'#FEF2F2'],
                ['icon'=>'fas fa-bolt','title'=>'Efisiensi','desc'=>'Proses donasi yang cepat dan mudah untuk pengalaman terbaik','color'=>'#F59E0B','bg'=>'#FFFBEB'],
            ];
            foreach ($values as $v):
            ?>
            <div style="background:white;border-radius:var(--radius-lg);padding:28px 20px;text-align:center;box-shadow:var(--shadow-sm);border:1px solid var(--border);transition:all 0.3s;"
                 onmouseover="this.style.transform='translateY(-5px)';this.style.boxShadow='var(--shadow-md)'"
                 onmouseout="this.style.transform='';this.style.boxShadow='var(--shadow-sm)'">
                <div style="width:60px;height:60px;background:<?= $v['bg'] ?>;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:1.4rem;color:<?= $v['color'] ?>;">
                    <i class="<?= $v['icon'] ?>"></i>
                </div>
                <h4 style="font-size:0.95rem;font-weight:700;color:var(--text-dark);margin-bottom:8px;"><?= $v['title'] ?></h4>
                <p style="font-size:0.8rem;color:var(--text-light);line-height:1.5;"><?= $v['desc'] ?></p>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- TIM -->
        <div style="text-align:center; margin-bottom:40px;">
            <div class="section-title" style="text-align:center;">
                <span>Orang-Orang Hebat</span>
                Tim SIBERASA
            </div>
        </div>

        <div style="display:grid; grid-template-columns:repeat(4,1fr); gap:24px;">
            <?php
            $team = [
                ['nama'=>'Arif Wahyudi','jabatan'=>'Founder & CEO','inisial'=>'AW','warna'=>'linear-gradient(135deg,#1A73E8,#22D3EE)'],
                ['nama'=>'Siti Nurhaliza','jabatan'=>'Program Manager','inisial'=>'SN','warna'=>'linear-gradient(135deg,#34C759,#10B981)'],
                ['nama'=>'Budi Hermawan','jabatan'=>'Tech Lead','inisial'=>'BH','warna'=>'linear-gradient(135deg,#8B5CF6,#EC4899)'],
                ['nama'=>'Dewi Rahayu','jabatan'=>'Finance & Audit','inisial'=>'DR','warna'=>'linear-gradient(135deg,#F59E0B,#EF4444)'],
            ];
            foreach ($team as $t):
            ?>
            <div style="background:white;border-radius:var(--radius-lg);padding:28px 20px;text-align:center;box-shadow:var(--shadow-md);border:1px solid var(--border);transition:all 0.3s;"
                 onmouseover="this.style.transform='translateY(-5px)'"
                 onmouseout="this.style.transform=''">
                <div style="width:80px;height:80px;border-radius:50%;background:<?= $t['warna'] ?>;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;color:white;font-size:1.6rem;font-weight:700;">
                    <?= $t['inisial'] ?>
                </div>
                <h4 style="font-size:0.95rem;font-weight:700;color:var(--text-dark);margin-bottom:4px;"><?= $t['nama'] ?></h4>
                <p style="font-size:0.8rem;color:var(--text-light);"><?= $t['jabatan'] ?></p>
                <div style="display:flex;justify-content:center;gap:8px;margin-top:14px;">
                    <a href="#" style="width:32px;height:32px;border-radius:50%;background:#F1F5F9;display:flex;align-items:center;justify-content:center;color:var(--text-light);font-size:0.8rem;transition:all 0.2s;"
                       onmouseover="this.style.background='var(--primary)';this.style.color='white'"
                       onmouseout="this.style.background='#F1F5F9';this.style.color='var(--text-light)'">
                        <i class="fab fa-instagram"></i>
                    </a>
                    <a href="#" style="width:32px;height:32px;border-radius:50%;background:#F1F5F9;display:flex;align-items:center;justify-content:center;color:var(--text-light);font-size:0.8rem;transition:all 0.2s;"
                       onmouseover="this.style.background='var(--primary)';this.style.color='white'"
                       onmouseout="this.style.background='#F1F5F9';this.style.color='var(--text-light)'">
                        <i class="fab fa-linkedin-in"></i>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
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

<?php require_once '../includes/footer.php'; ?>
