<?php
$_GET['id'] = 5;
ob_start();
include __DIR__ . '/../donatur/pembayaran.php';
$html = ob_get_clean();

echo "=== ACTIVE TAB CARDS ===\n";
if (preg_match('/<div class="pembayaran-metode-tabs">([\s\S]*?)<\/div>/', $html, $m)) {
    echo trim($m[0]) . "\n\n";
}

echo "=== BANNER ===\n";
if (preg_match('/<div class="pembayaran-banner"[\s\S]*?<\/div>\s*<\/div>/', $html, $m)) {
    echo trim($m[0]) . "\n\n";
}
