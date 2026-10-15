<?php
// Forward to upload_bukti.php preserving query string
$qs = $_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : '';
header('Location: upload_bukti.php' . $qs);
exit;


