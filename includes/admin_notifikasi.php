<?php
/**
 * Komponen Notifikasi Khusus Panel Admin SIBERASA
 * Menampilkan pemberitahuan real-time untuk:
 * 1. Donasi yang menunggu verifikasi bukti pembayaran
 * 2. Pengajuan program donasi baru dari pengusul
 * 3. Pendaftaran akun pengusul baru yang menunggu persetujuan
 * 4. Transaksi donasi yang berhasil
 */

if (!function_exists('waktuLaluAdminNotif')) {
    function waktuLaluAdminNotif($datetime) {
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

$adminNotifikasiList = [];

if (isset($pdo)) {
    try {
        // 1. Donasi Menunggu Verifikasi
        $stmtNotifDon = $pdo->query("
            SELECT 
                d.id_donasi,
                d.nominal,
                d.nama_donatur,
                d.tanggal_donasi AS waktu,
                p.judul AS judul_program,
                pb.id_pembayaran
            FROM donasi d
            JOIN program_donasi p ON d.id_program = p.id_program
            LEFT JOIN pembayaran pb ON d.id_donasi = pb.id_donasi
            WHERE d.status_donasi = 'menunggu_verifikasi' OR pb.status_verifikasi = 'menunggu'
            ORDER BY d.tanggal_donasi DESC
            LIMIT 5
        ");
        $donItems = $stmtNotifDon->fetchAll(PDO::FETCH_ASSOC);

        foreach ($donItems as $nd) {
            $adminNotifikasiList[] = [
                'tipe'    => 'verifikasi_donasi',
                'judul'   => 'Verifikasi Donasi Diperlukan',
                'pesan'   => 'Donasi <strong>Rp ' . number_format($nd['nominal'], 0, ',', '.') . '</strong> dari ' . htmlspecialchars($nd['nama_donatur'] ?: 'Anonim') . ' untuk program <em>' . htmlspecialchars($nd['judul_program']) . '</em>',
                'waktu'   => $nd['waktu'],
                'link'    => '/Donasi/admin/verifikasi_donasi.php?tab=menunggu',
                'icon'    => 'fas fa-receipt',
                'color'   => '#d97706',
                'bg'      => '#fef3c7'
            ];
        }

        // 2. Program Baru yang Menunggu Verifikasi
        $stmtNotifProg = $pdo->query("
            SELECT 
                p.id_program,
                p.judul,
                p.dibuat_pada AS waktu,
                COALESCE(u.nama_organisasi, u.nama, 'Pengusul') AS nama_pengusul
            FROM program_donasi p
            LEFT JOIN users u ON p.id_pengusul = u.id_user
            WHERE p.status_program = 'menunggu'
            ORDER BY p.dibuat_pada DESC
            LIMIT 4
        ");
        $progItems = $stmtNotifProg->fetchAll(PDO::FETCH_ASSOC);

        foreach ($progItems as $np) {
            $adminNotifikasiList[] = [
                'tipe'    => 'program_baru',
                'judul'   => 'Pengajuan Program Baru',
                'pesan'   => 'Program <em>' . htmlspecialchars($np['judul']) . '</em> diajukan oleh <strong>' . htmlspecialchars($np['nama_pengusul']) . '</strong>',
                'waktu'   => $np['waktu'],
                'link'    => '/Donasi/admin/kelola_program.php',
                'icon'    => 'fas fa-hand-holding-heart',
                'color'   => '#0284c7',
                'bg'      => '#e0f2fe'
            ];
        }

        // 3. Pengusul Baru Menunggu Verifikasi Akun
        $stmtNotifUser = $pdo->query("
            SELECT 
                id_user,
                nama,
                nama_organisasi,
                email
            FROM users
            WHERE role = 'pengusul' AND status_verifikasi = 'menunggu'
            ORDER BY id_user DESC
            LIMIT 4
        ");
        $userItems = $stmtNotifUser->fetchAll(PDO::FETCH_ASSOC);

        foreach ($userItems as $nu) {
            $adminNotifikasiList[] = [
                'tipe'    => 'user_baru',
                'judul'   => 'Pendaftaran Pengusul Baru',
                'pesan'   => 'Akun <strong>' . htmlspecialchars($nu['nama_organisasi'] ?: $nu['nama']) . '</strong> (' . htmlspecialchars($nu['email']) . ') menunggu persetujuan.',
                'waktu'   => date('Y-m-d H:i:s'),
                'link'    => '/Donasi/admin/kelola_pengguna.php',
                'icon'    => 'fas fa-user-clock',
                'color'   => '#7c3aed',
                'bg'      => '#f3e8ff'
            ];
        }

        // 4. Donasi Berhasil Terbaru
        $stmtNotifSuccess = $pdo->query("
            SELECT 
                d.id_donasi,
                d.nominal,
                d.nama_donatur,
                d.tanggal_donasi AS waktu,
                p.judul AS judul_program
            FROM donasi d
            JOIN program_donasi p ON d.id_program = p.id_program
            WHERE d.status_donasi = 'berhasil'
            ORDER BY d.tanggal_donasi DESC
            LIMIT 3
        ");
        $succItems = $stmtNotifSuccess->fetchAll(PDO::FETCH_ASSOC);

        foreach ($succItems as $ns) {
            $adminNotifikasiList[] = [
                'tipe'    => 'donasi_berhasil',
                'judul'   => 'Donasi Berhasil Diverifikasi',
                'pesan'   => 'Donasi <strong>Rp ' . number_format($ns['nominal'], 0, ',', '.') . '</strong> dari ' . htmlspecialchars($ns['nama_donatur'] ?: 'Anonim') . ' telah selesai diverifikasi.',
                'waktu'   => $ns['waktu'],
                'link'    => '/Donasi/admin/verifikasi_donasi.php?tab=terverifikasi',
                'icon'    => 'fas fa-circle-check',
                'color'   => '#16a34a',
                'bg'      => '#dcfce7'
            ];
        }

        // Urutkan berdasarkan waktu paling baru
        usort($adminNotifikasiList, function($a, $b) {
            return strtotime($b['waktu']) - strtotime($a['waktu']);
        });

    } catch (PDOException $e) {
        $adminNotifikasiList = [];
    }
}

$totalAdminNotif = count($adminNotifikasiList);
?>

<!-- ═══ STYLES NOTIFIKASI ADMIN ═══ -->
<style>
.admin-notif-wrapper {
  position: relative;
  display: inline-block;
}

.admin-notif-btn {
  position: relative;
  width: 40px;
  height: 40px;
  border-radius: 10px;
  border: 1px solid #e2e8f0;
  background: #ffffff;
  color: #64748b;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s ease;
  font-size: 1.05rem;
}

.admin-notif-btn:hover {
  background: #f8fafc;
  color: #0284c7;
  border-color: #cbd5e1;
  transform: translateY(-1px);
}

.admin-notif-badge {
  position: absolute;
  top: 7px;
  right: 7px;
  width: 9px;
  height: 9px;
  border-radius: 50%;
  background: #ef4444;
  border: 2px solid #ffffff;
  animation: adminPulse 2s infinite;
}

@keyframes adminPulse {
  0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
  70% { transform: scale(1); box-shadow: 0 0 0 5px rgba(239, 68, 68, 0); }
  100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
}

.admin-notif-dropdown {
  display: none;
  position: absolute;
  top: calc(100% + 10px);
  right: 0;
  width: 370px;
  max-width: calc(100vw - 32px);
  background: #ffffff;
  border-radius: 14px;
  border: 1px solid #e2e8f0;
  box-shadow: 0 14px 35px rgba(15, 23, 42, 0.12), 0 2px 8px rgba(0, 0, 0, 0.04);
  z-index: 1050;
  overflow: hidden;
  text-align: left;
  animation: adminNotifFadeIn 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}

.admin-notif-dropdown.show {
  display: flex;
  flex-direction: column;
}

@keyframes adminNotifFadeIn {
  from { opacity: 0; transform: translateY(-8px) scale(0.98); }
  to { opacity: 1; transform: translateY(0) scale(1); }
}

.admin-notif-header {
  padding: 14px 18px;
  background: #ffffff;
  border-bottom: 1px solid #f1f5f9;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.admin-notif-title-wrap {
  display: flex;
  align-items: center;
  gap: 8px;
}

.admin-notif-title-text {
  font-size: 0.95rem;
  font-weight: 700;
  color: #0f172a;
}

.admin-notif-pill-count {
  background: #e0f2fe;
  color: #0284c7;
  font-size: 0.72rem;
  font-weight: 700;
  padding: 2px 8px;
  border-radius: 9999px;
}

.admin-notif-btn-read {
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

.admin-notif-btn-read:hover {
  background: #f0f9ff;
  color: #0369a1;
}

.admin-notif-body {
  max-height: 360px;
  overflow-y: auto;
  overscroll-behavior: contain;
}

.admin-notif-body::-webkit-scrollbar {
  width: 5px;
}
.admin-notif-body::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 10px;
}

.admin-notif-item {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 13px 18px;
  border-bottom: 1px solid #f8fafc;
  text-decoration: none;
  color: inherit;
  transition: background-color 0.15s ease;
}

.admin-notif-item:last-child {
  border-bottom: none;
}

.admin-notif-item:hover {
  background-color: #f8fafc;
}

.admin-notif-icon-box {
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

.admin-notif-content {
  flex: 1;
  min-width: 0;
}

.admin-notif-item-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 6px;
  margin-bottom: 2px;
}

.admin-notif-item-title {
  font-size: 0.82rem;
  font-weight: 700;
  color: #0f172a;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.admin-notif-item-time {
  font-size: 0.72rem;
  color: #94a3b8;
  white-space: nowrap;
  flex-shrink: 0;
}

.admin-notif-item-desc {
  font-size: 0.78rem;
  color: #475569;
  line-height: 1.45;
}

.admin-notif-empty {
  padding: 40px 20px;
  text-align: center;
  color: #94a3b8;
}

.admin-notif-empty i {
  font-size: 2.2rem;
  margin-bottom: 10px;
  color: #cbd5e1;
}

.admin-notif-empty p {
  font-size: 0.82rem;
  font-weight: 500;
  margin: 0;
}

.admin-notif-footer {
  padding: 11px 18px;
  background: #f8fafc;
  border-top: 1px solid #f1f5f9;
  text-align: center;
}

.admin-notif-footer a {
  font-size: 0.78rem;
  font-weight: 600;
  color: #0284c7;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.admin-notif-footer a:hover {
  text-decoration: underline;
}
</style>

<!-- ═══ HTML KOMPONEN NOTIFIKASI ADMIN ═══ -->
<div class="admin-notif-wrapper" id="adminNotifWrapper">
  <button type="button" class="admin-notif-btn" id="adminNotifBtn" title="Pemberitahuan Admin" aria-label="Notifikasi">
    <i class="far fa-bell"></i>
    <span class="admin-notif-badge" id="adminNotifBadge" <?= ($totalAdminNotif > 0 ? '' : 'style="display:none;"') ?>></span>
  </button>

  <!-- Panel Dropdown Notifikasi Admin -->
  <div class="admin-notif-dropdown" id="adminNotifDropdown">
    <div class="admin-notif-header">
      <div class="admin-notif-title-wrap">
        <span class="admin-notif-title-text">Notifikasi Admin</span>
        <?php if ($totalAdminNotif > 0): ?>
          <span class="admin-notif-pill-count" id="adminNotifCountPill"><?= $totalAdminNotif ?></span>
        <?php endif; ?>
      </div>
      <?php if ($totalAdminNotif > 0): ?>
        <button type="button" class="admin-notif-btn-read" id="btnAdminMarkAllRead">Tandai dibaca</button>
      <?php endif; ?>
    </div>

    <div class="admin-notif-body">
      <?php if (empty($adminNotifikasiList)): ?>
        <div class="admin-notif-empty">
          <i class="far fa-bell-slash"></i>
          <p>Belum ada notifikasi baru untuk admin</p>
        </div>
      <?php else: ?>
        <?php foreach ($adminNotifikasiList as $item): ?>
          <a href="<?= $item['link'] ?>" class="admin-notif-item">
            <div class="admin-notif-icon-box" style="background: <?= $item['bg'] ?>; color: <?= $item['color'] ?>;">
              <i class="<?= $item['icon'] ?>"></i>
            </div>
            <div class="admin-notif-content">
              <div class="admin-notif-item-top">
                <span class="admin-notif-item-title"><?= $item['judul'] ?></span>
                <span class="admin-notif-item-time"><?= waktuLaluAdminNotif($item['waktu']) ?></span>
              </div>
              <div class="admin-notif-item-desc"><?= $item['pesan'] ?></div>
            </div>
          </a>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <div class="admin-notif-footer">
      <a href="/Donasi/admin/verifikasi_donasi.php">
        <span>Lihat Semua Verifikasi Donasi</span>
        <i class="fas fa-arrow-right" style="font-size: 0.7rem;"></i>
      </a>
    </div>
  </div>
</div>

<!-- ═══ SCRIPT NOTIFIKASI ADMIN ═══ -->
<script>
(function() {
  const notifBtn      = document.getElementById('adminNotifBtn');
  const notifDropdown = document.getElementById('adminNotifDropdown');
  const notifBadge    = document.getElementById('adminNotifBadge');
  const btnMarkRead   = document.getElementById('btnAdminMarkAllRead');

  if (!notifBtn || !notifDropdown) return;

  // Cek apakah admin pernah menandai semua dibaca sebelumnya
  const lastRead = localStorage.getItem('admin_notif_read');
  if (lastRead === '1' && notifBadge) {
    notifBadge.style.display = 'none';
  }

  // Toggle dropdown saat lonceng diklik
  notifBtn.addEventListener('click', function(e) {
    e.stopPropagation();
    const isOpen = notifDropdown.classList.toggle('show');
    if (isOpen) {
      if (notifBadge) {
        notifBadge.style.display = 'none';
        localStorage.setItem('admin_notif_read', '1');
      }
    }
  });

  // Tombol tandai dibaca
  if (btnMarkRead) {
    btnMarkRead.addEventListener('click', function(e) {
      e.stopPropagation();
      if (notifBadge) notifBadge.style.display = 'none';
      localStorage.setItem('admin_notif_read', '1');
      const countPill = document.getElementById('adminNotifCountPill');
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

