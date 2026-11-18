<?php
/**
 * Halaman utama aplikasi KPI.
 * Seluruh tampilan dijalankan di browser (assets/app.js) dan mengambil data lewat api.php.
 */

declare(strict_types=1);

require __DIR__ . '/lib/db.php';
require __DIR__ . '/lib/handlers.php';
require __DIR__ . '/lib/auth.php';

if (!kpi_is_installed()) {
    header('Location: install.php');
    exit;
}

try {
    $maintenance = kpi_maintenance_message();
    if ($maintenance !== null) {
        http_response_code(503);
        header('Content-Type: text/html; charset=utf-8');
        header('Retry-After: 600');
        header('Cache-Control: no-store');
        echo '<!doctype html><html lang="id"><meta charset="utf-8">'
           . '<meta name="viewport" content="width=device-width, initial-scale=1">'
           . '<meta name="robots" content="noindex, nofollow"><title>Sedang dalam pemeliharaan</title>'
           . '<style>:root{color-scheme:light dark}body{margin:0;min-height:100vh;display:grid;place-items:center;'
           . 'font:16px/1.6 system-ui,sans-serif;background:#F3F4F8;color:#151A2E;padding:24px;box-sizing:border-box}'
           . '@media (prefers-color-scheme:dark){body{background:#0E1120;color:#E7E9F3}div{background:#161A2C!important;border-color:#2B3150!important}}'
           . 'div{max-width:480px;background:#fff;border:1px solid #DCE0EB;border-radius:14px;padding:28px;text-align:center}'
           . 'img{width:56px;height:56px}h1{font-size:21px;margin:12px 0 8px}p{margin:0;opacity:.8}</style>'
           . '<div><img src="assets/logo.png" alt=""><h1>Sedang dalam pemeliharaan</h1><p>'
           . htmlspecialchars($maintenance, ENT_QUOTES) . '</p></div></html>';
        exit;
    }

    kpi_session_start();
    $me   = kpi_current_user();
    $boot = $me ? kpi_bootstrap($me) : null;
    $company  = (string)kpi_setting('company', 'Perusahaan');
    $demo     = kpi_setting('demo_mode', '0') === '1';
    $demoList = $demo ? json_decode((string)kpi_setting('demo_accounts', '[]'), true) : [];
} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><meta charset="utf-8"><title>Gagal terhubung</title>'
       . '<div style="font:16px/1.6 system-ui;max-width:640px;margin:60px auto;padding:0 20px">'
       . '<h1 style="font-size:22px">Aplikasi tidak dapat terhubung ke database</h1>'
       . '<p>Periksa kembali data koneksi di file <code>config.php</code>, lalu muat ulang halaman ini.</p>'
       . '<p style="color:#666;font-size:14px">Detail teknis dicatat di log error server.</p></div>';
    error_log('[KPI] ' . $e->getMessage());
    exit;
}

$csrf = kpi_csrf_token();
$ver  = (string)@filemtime(__DIR__ . '/assets/app.js');
header('Content-Type: text/html; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: same-origin');
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="robots" content="noindex, nofollow">
<title>Key Performance Indicator<?= $company !== '' ? ' · ' . htmlspecialchars($company, ENT_QUOTES) : '' ?></title>
<link rel="icon" href="assets/logo.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Sora:wght@600;700&display=swap">
<link rel="stylesheet" href="assets/app.css?v=<?= htmlspecialchars($ver, ENT_QUOTES) ?>">
</head>
<body>
<div id="app"></div>
<div id="modal"></div>
<script>
window.__BOOT__ = {
  csrf: <?= json_encode($csrf) ?>,
  company: <?= json_encode($company, JSON_UNESCAPED_UNICODE) ?>,
  demoMode: <?= $demo ? 'true' : 'false' ?>,
  demoAccounts: <?= json_encode(is_array($demoList) ? $demoList : [], JSON_UNESCAPED_UNICODE) ?>,
  data: <?= $boot ? json_encode($boot, JSON_UNESCAPED_UNICODE) : 'null' ?>
};
</script>
<script src="assets/app.js?v=<?= htmlspecialchars($ver, ENT_QUOTES) ?>"></script>
</body>
</html>
