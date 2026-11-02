<?php
$_GET['id'] = 5;
ob_start();
include __DIR__ . '/../donatur/donasi.php';
$html = ob_get_clean();

echo "=== DONASI.PHP METODE PEMBAYARAN ===\n";
if (preg_match('/<div style=\"display: flex; flex-direction: column; gap: 12px; margin-bottom: 24px;\">([\s\S]*?)<\/div>/', $html, $m)) {
    echo trim($m[0]) . "\n";
}
