<?php
require_once 'includes/data.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 1;
$prog = null;
foreach ($programs as $p) {
    if ($p['id'] === $id) { $prog = $p; break; }
}

if (!$prog) {
    header('Location: program.php');
    exit;
}

$page_title = htmlspecialchars($prog['judul']) . ' - SIBERASA';
$page_desc  = 'Dukung program ' . $prog['judul'] . ' oleh ' . $prog['penyelenggara'];
$current_page = 'program';

require_once 'includes/header.php';
?>

<div style="background: #f0f6ff; min-height: 100vh; padding: 40px 0;">
    <div class="container">
        
        <!-- TOP CARD -->
        <div style="background: #fff; border-radius: 12px; border: 2.5px solid #4caf50; padding: 24px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-bottom: 24px;">
            <!-- Back Button -->
            <a href="program.php" style="display: inline-block; font-size: 1.5rem; color: #1a73e8; margin-bottom: 16px;">
                <i class="fas fa-arrow-left"></i>
            </a>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 32px;">
                <!-- Left: Image -->
                <div style="border-radius: 8px; overflow: hidden; height: 300px;">
                    <img src="<?= $prog['gambar'] ?>" alt="<?= htmlspecialchars($prog['judul']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                </div>
                
                <!-- Right: Info -->
                <div style="display: flex; flex-direction: column; justify-content: center;">
                    <h1 style="font-size: 1.6rem; font-weight: 700; color: #1a202c; margin-bottom: 12px; line-height: 1.3;">
                        <?= htmlspecialchars($prog['judul']) ?>
                    </h1>
                    
                    <span style="background: #eef2ff; color: #1a73e8; padding: 6px 16px; border-radius: 50px; font-size: 0.85rem; font-weight: 600; display: inline-block; width: fit-content; margin-bottom: 20px;">
                        <?= $prog['kategori'] ?>
                    </span>
                    
                    <div style="margin-bottom: 16px;">
                        <div style="font-size: 1.1rem; color: #1a202c; margin-bottom: 8px;">
                            <span style="color: #34c759; font-weight: 700;"><?= formatRupiah($prog['terkumpul']) ?></span> terkumpul dari <?= formatRupiah($prog['target']) ?>
                        </div>
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="flex: 1; height: 12px; background: #e2e8f0; border-radius: 50px; overflow: hidden;">
                                <div style="width: <?= $prog['persen'] ?>%; height: 100%; background: #34c759; border-radius: 50px;"></div>
                            </div>
                            <span style="color: #34c759; font-weight: 700; font-size: 0.9rem;"><?= $prog['persen'] ?>%</span>
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <i class="fas fa-stopwatch" style="font-size: 2rem; color: #1a73e8;"></i>
                            <div>
                                <div style="font-size: 0.9rem; color: #4a5568;">Batas Waktu</div>
                                <div style="font-size: 0.9rem; color: #4a5568; font-weight: 500;"><?= date('d F Y', strtotime('+' . $prog['sisa_hari'] . ' days')) ?></div>
                            </div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <i class="fas fa-user-circle" style="font-size: 2.2rem; color: #94a3b8;"></i>
                            <div>
                                <div style="font-size: 0.9rem; color: #4a5568;">Penggalang Dana</div>
                                <div style="font-size: 0.9rem; color: #4a5568; font-weight: 500;"><?= htmlspecialchars($prog['penyelenggara']) ?></div>
                            </div>
                        </div>
                    </div>
                    
                    <a href="donasi.php?id=<?= $prog['id'] ?>" style="display: block; text-align: center; width: 100%; background: #4caf50; color: #fff; border: none; padding: 14px; border-radius: 8px; font-size: 1.1rem; font-weight: 600; cursor: pointer; transition: background 0.2s; text-decoration: none;">
                        Donasi Sekarang
                    </a>
                </div>
            </div>
        </div>
        
        <!-- BOTTOM CARDS -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
            <!-- Diskripsi -->
            <div style="background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.03);">
                <h2 style="font-size: 1.4rem; font-weight: 700; color: #4a5568; margin-bottom: 16px;">Diskripsi Program</h2>
                <p style="font-size: 1rem; color: #4a5568; line-height: 1.6;">
                    <?= nl2br(htmlspecialchars($prog['deskripsi'])) ?>
                </p>
            </div>
            
            <!-- Rincian -->
            <div style="background: #f8fafc; border-radius: 12px; border: 1px solid #e2e8f0; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.03);">
                <h2 style="font-size: 1.2rem; font-weight: 600; color: #1a202c; text-align: center; margin-bottom: 20px;">Rincian Penggunaan Dana</h2>
                <div style="background: #fff; border-radius: 8px; overflow: hidden; border: 1px solid #e2e8f0;">
                    <?php foreach ($prog['rincian'] as $index => $item): ?>
                    <div style="display: flex; justify-content: space-between; padding: 16px; border-bottom: <?= $index < count($prog['rincian']) - 1 ? '1px solid #eef2ff' : 'none' ?>;">
                        <span style="color: #4a5568; font-weight: 500;"><?= htmlspecialchars($item['item']) ?></span>
                        <span style="color: #4a5568; font-weight: 500;"><?= formatRupiah($item['biaya']) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
