<?php
if (!function_exists('waktuLaluNotif')) {
    function waktuLaluNotif($datetime) {
        if (empty($datetime)) return 'Baru saja';
        $time = strtotime($datetime);
        $diff = time() - $time;
        if ($diff < 60) return 'Baru saja';
        if ($diff < 3600) return floor($diff / 60) . ' mnt lalu';
        if ($diff < 86400) return floor($diff / 3600) . ' jam lalu';
        if ($diff < 172800) return 'Kemarin';
        if ($diff < 604800) return floor($diff / 86400) . ' hari lalu';
        return date('d M Y', $time);
    }
}

$notifUser = (int)($_SESSION['id_user'] ?? 0);
$notifikasiList = [];

if ($notifUser > 0 && isset($pdo)) {
    try {
        // 1. Donasi yang masuk ke program milik pengusul
        $stmtNotifDon = $pdo->prepare("
            SELECT 
                d.id_donasi,
                d.nominal,
                d.nama_donatur,
                d.status_donasi,
                d.tanggal_donasi AS waktu,
                p.id_program,
                p.judul AS judul_program
            FROM donasi d
            JOIN program_donasi p ON d.id_program = p.id_program
            WHERE p.id_pengusul = ?
            ORDER BY d.tanggal_donasi DESC
            LIMIT 5
        ");
        $stmtNotifDon->execute([$notifUser]);
        $donasiItems = $stmtNotifDon->fetchAll(PDO::FETCH_ASSOC);

        foreach ($donasiItems as $nd) {
            $isSuccess = ($nd['status_donasi'] === 'berhasil');
            $notifikasiList[] = [
                'tipe'    => 'donasi',
                'judul'   => $isSuccess ? 'Donasi Berhasil Diterima' : 'Donasi Menunggu Verifikasi',
                'pesan'   => 'Rp ' . number_format($nd['nominal'], 0, ',', '.') . ' dari <strong>' . htmlspecialchars($nd['nama_donatur'] ?: 'Anonim') . '</strong> untuk program <em>' . htmlspecialchars($nd['judul_program']) . '</em>',
                'waktu'   => $nd['waktu'],
                'link'    => '/Donasi/pengusul/dana_terkumpul.php',
                'icon'    => $isSuccess ? 'fa-solid fa-hand-holding-dollar' : 'fa-solid fa-receipt',
                'color'   => $isSuccess ? '#16a34a' : '#d97706',
                'bg'      => $isSuccess ? '#dcfce7' : '#fef3c7'
            ];
        }

        // 2. Status Program Milik Pengusul
        $stmtNotifProg = $pdo->prepare("
            SELECT 
                id_program,
                judul,
                status_program,
                catatan_admin,
                dibuat_pada AS waktu
            FROM program_donasi
            WHERE id_pengusul = ?
            ORDER BY dibuat_pada DESC
            LIMIT 4
        ");
        $stmtNotifProg->execute([$notifUser]);
        $progItems = $stmtNotifProg->fetchAll(PDO::FETCH_ASSOC);

        foreach ($progItems as $np) {
            $st = $np['status_program'];
            if ($st === 'disetujui' || $st === 'berlangsung') {
                $j = 'Program Disetujui';
                $msg = 'Program <em>' . htmlspecialchars($np['judul']) . '</em> telah disetujui & aktif menerima donasi.';
                $icon = 'fa-solid fa-circle-check';
                $color = '#0284c7';
                $bg = '#e0f2fe';
            } elseif ($st === 'ditolak') {
                $j = 'Pengajuan Program Ditolak';
                $msg = 'Program <em>' . htmlspecialchars($np['judul']) . '</em> ditolak.' . ($np['catatan_admin'] ? ' Catatan: ' . htmlspecialchars($np['catatan_admin']) : '');
                $icon = 'fa-solid fa-circle-xmark';
                $color = '#dc2626';
                $bg = '#fee2e2';
            } else {
                $j = 'Program Menunggu Verifikasi';
                $msg = 'Program <em>' . htmlspecialchars($np['judul']) . '</em> sedang dalam peninjauan admin.';
                $icon = 'fa-solid fa-clock';
                $color = '#d97706';
                $bg = '#fef3c7';
            }

            $notifikasiList[] = [
                'tipe'    => 'program',
                'judul'   => $j,
                'pesan'   => $msg,
                'waktu'   => $np['waktu'],
                'link'    => '/Donasi/pengusul/status_pengajuan.php',
                'icon'    => $icon,
                'color'   => $color,
                'bg'      => $bg
            ];
        }

        // Urutkan waktu terbaru
        usort($notifikasiList, function($a, $b) {
            return strtotime($b['waktu']) - strtotime($a['waktu']);
        });

    } catch (PDOException $e) {
        $notifikasiList = [];
    }
}

$totalNotif = count($notifikasiList);
?>

<!-- ═══ KOMPONEN NOTIFIKASI PENGUSUL ═══ -->
<style>
.notif-wrapper {
  position: relative;
  display: inline-block;
}

.notif-dropdown {
  display: none;
  position: absolute;
  top: calc(100% + 10px);
  right: 0;
  width: 360px;
  max-width: calc(100vw - 32px);
  background: #ffffff;
  border-radius: 14px;
  border: 1px solid #e2e8f0;
  box-shadow: 0 14px 35px rgba(15, 23, 42, 0.12), 0 2px 6px rgba(0, 0, 0, 0.04);
  z-index: 1000;
  overflow: hidden;
  animation: notifFadeIn 0.2s cubic-bezier(0.16, 1, 0.3, 1);
  text-align: left;
}

.notif-dropdown.show {
  display: flex;
  flex-direction: column;
}

@keyframes notifFadeIn {
  from {
    opacity: 0;
    transform: translateY(-8px) scale(0.98);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

.notif-header {
  padding: 14px 18px;
  background: #ffffff;
  border-bottom: 1px solid #f1f5f9;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.notif-title-wrap {
  display: flex;
  align-items: center;
  gap: 8px;
}

.notif-title-text {
  font-size: 0.95rem;
  font-weight: 700;
  color: #0f172a;
}

.notif-pill-count {
  background: #e0f2fe;
  color: #0284c7;
  font-size: 0.72rem;
  font-weight: 700;
  padding: 2px 8px;
  border-radius: 9999px;
}

.notif-btn-read {
  background: none;
  border: none;
  font-size: 0.76rem;
  font-weight: 600;
  color: #0284c7;
  cursor: pointer;
  padding: 4px 6px;
  border-radius: 4px;
  transition: all 0.2s;
}

.notif-btn-read:hover {
  background: #f0f9ff;
  color: #0369a1;
}

.notif-body {
  max-height: 360px;
  overflow-y: auto;
  overscroll-behavior: contain;
}

.notif-body::-webkit-scrollbar {
  width: 5px;
}
.notif-body::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 10px;
}

.notif-item {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 13px 18px;
  border-bottom: 1px solid #f8fafc;
  text-decoration: none;
  color: inherit;
  transition: background-color 0.15s ease;
}

.notif-item:last-child {
  border-bottom: none;
}

.notif-item:hover {
  background-color: #f8fafc;
}

.notif-icon-box {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.95rem;
  flex-shrink: 0;
  margin-top: 2px;
}

.notif-content {
  flex: 1;
  min-width: 0;
}

.notif-item-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 6px;
  margin-bottom: 2px;
}

.notif-item-title {
  font-size: 0.82rem;
  font-weight: 700;
  color: #1e293b;
  line-height: 1.3;
}

.notif-item-time {
  font-size: 0.68rem;
  color: #94a3b8;
  white-space: nowrap;
}

.notif-item-desc {
  font-size: 0.76rem;
  color: #64748b;
  line-height: 1.4;
  margin-top: 3px;
  word-break: break-word;
}

.notif-item-desc strong {
  color: #334155;
}

.notif-empty {
  padding: 36px 20px;
  text-align: center;
  color: #94a3b8;
}

.notif-empty i {
  font-size: 2.2rem;
  color: #cbd5e1;
  margin-bottom: 8px;
}

.notif-empty p {
  font-size: 0.82rem;
  font-weight: 500;
}

.notif-footer {
  padding: 10px 18px;
  background: #f8fafc;
  border-top: 1px solid #f1f5f9;
  text-align: center;
}

.notif-footer a {
  font-size: 0.78rem;
  font-weight: 600;
  color: #0284c7;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.notif-footer a:hover {
  text-decoration: underline;
}
</style>

<div class="notif-wrapper" id="notifWrapper">
  <div class="notif-btn" id="notifBtn" title="Pemberitahuan" role="button" tabindex="0">
    <i class="fa-regular fa-bell"></i>
    <span class="notif-badge" id="notifBadge" <?= ($totalNotif > 0 ? '' : 'style="display:none;"') ?>></span>
  </div>

  <!-- Panel Dropdown Notifikasi -->
  <div class="notif-dropdown" id="notifDropdown">
    <div class="notif-header">
      <div class="notif-title-wrap">
        <span class="notif-title-text">Notifikasi</span>
        <?php if ($totalNotif > 0): ?>
          <span class="notif-pill-count" id="notifCountPill"><?= $totalNotif ?></span>
        <?php endif; ?>
      </div>
      <?php if ($totalNotif > 0): ?>
        <button type="button" class="notif-btn-read" id="btnMarkAllRead">Tandai dibaca</button>
      <?php endif; ?>
    </div>

    <div class="notif-body">
      <?php if (empty($notifikasiList)): ?>
        <div class="notif-empty">
          <i class="fa-regular fa-bell-slash"></i>
          <p>Belum ada notifikasi baru</p>
        </div>
      <?php else: ?>
        <?php foreach ($notifikasiList as $item): ?>
          <a href="<?= $item['link'] ?>" class="notif-item">
            <div class="notif-icon-box" style="background: <?= $item['bg'] ?>; color: <?= $item['color'] ?>;">
              <i class="<?= $item['icon'] ?>"></i>
            </div>
            <div class="notif-content">
              <div class="notif-item-top">
                <span class="notif-item-title"><?= $item['judul'] ?></span>
                <span class="notif-item-time"><?= waktuLaluNotif($item['waktu']) ?></span>
              </div>
              <div class="notif-item-desc"><?= $item['pesan'] ?></div>
            </div>
          </a>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <div class="notif-footer">
      <a href="/Donasi/pengusul/dana_terkumpul.php">
        <span>Lihat Semua Riwayat Donasi</span>
        <i class="fa-solid fa-arrow-right" style="font-size: 0.7rem;"></i>
      </a>
    </div>
  </div>
</div>

<script>
(function() {
  const notifBtn      = document.getElementById('notifBtn');
  const notifDropdown = document.getElementById('notifDropdown');
  const notifBadge    = document.getElementById('notifBadge');
  const btnMarkRead   = document.getElementById('btnMarkAllRead');

  if (!notifBtn || !notifDropdown) return;

  // Cek apakah user pernah menandai semua dibaca sebelumnya
  const lastRead = localStorage.getItem('pengusul_notif_read');
  if (lastRead === '1' && notifBadge) {
    notifBadge.style.display = 'none';
  }

  // Toggle dropdown saat lonceng diklik
  notifBtn.addEventListener('click', function(e) {
    e.stopPropagation();
    const isOpen = notifDropdown.classList.toggle('show');
    if (isOpen) {
      // Hilangkan titik merah ketika dibuka
      if (notifBadge) {
        notifBadge.style.display = 'none';
        localStorage.setItem('pengusul_notif_read', '1');
      }
    }
  });

  // Tombol tandai dibaca
  if (btnMarkRead) {
    btnMarkRead.addEventListener('click', function(e) {
      e.stopPropagation();
      if (notifBadge) notifBadge.style.display = 'none';
      localStorage.setItem('pengusul_notif_read', '1');
      const countPill = document.getElementById('notifCountPill');
      if (countPill) countPill.style.display = 'none';
      this.style.display = 'none';
    });
  }

  // Tutup dropdown jika klik di luar area
  document.addEventListener('click', function(e) {
    if (!notifDropdown.contains(e.target) && !notifBtn.contains(e.target)) {
      notifDropdown.classList.remove('show');
    }
  });

  // Tutup dengan tombol Escape
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      notifDropdown.classList.remove('show');
    }
  });
})();
</script>

