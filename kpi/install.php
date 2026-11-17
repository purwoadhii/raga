<?php
/**
 * Halaman pemasangan: membuat tabel database, mengisi data awal,
 * lalu menulis file config.php. Hapus file ini setelah pemasangan selesai.
 */

declare(strict_types=1);

require __DIR__ . '/lib/db.php';
require __DIR__ . '/lib/schema.php';
require __DIR__ . '/lib/demo.php';
require __DIR__ . '/lib/handlers.php';
require __DIR__ . '/lib/installer.php';

$done  = false;
$error = '';
$f = [
    'db_host' => $_POST['db_host'] ?? 'localhost',
    'db_port' => $_POST['db_port'] ?? '3306',
    'db_name' => $_POST['db_name'] ?? '',
    'db_user' => $_POST['db_user'] ?? '',
    'db_pass' => $_POST['db_pass'] ?? '',
    'company' => $_POST['company'] ?? '',
    'year'    => $_POST['year'] ?? date('Y'),
    'mode'    => $_POST['mode'] ?? 'demo',
    'ceo_name'=> $_POST['ceo_name'] ?? '',
    'ceo_user'=> $_POST['ceo_user'] ?? '',
    'ceo_pass'=> $_POST['ceo_pass'] ?? '',
];

$already = kpi_is_installed();

if (!$already && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    try {
        $cfg = [
            'db_host' => trim((string)$f['db_host']),
            'db_port' => (int)$f['db_port'] ?: 3306,
            'db_name' => trim((string)$f['db_name']),
            'db_user' => trim((string)$f['db_user']),
            'db_pass' => (string)$f['db_pass'],
        ];
        if ($cfg['db_name'] === '' || $cfg['db_user'] === '') {
            throw new RuntimeException('Nama database dan nama pengguna database wajib diisi.');
        }

        $pdo = kpi_connect($cfg);

        // Tabel yang sudah ada akan menolak pemasangan, supaya data lama tidak tertimpa.
        kpi_install_data($pdo, $f);

        if (!kpi_write_config($cfg)) {
            throw new RuntimeException('Tidak dapat menulis file config.php. Pastikan folder aplikasi dapat ditulis, '
                . 'atau buat file config.php secara manual dengan isi berikut:' . "\n\n" . kpi_config_php($cfg));
        }
        $done = true;
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

function v(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES);
}
header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="robots" content="noindex, nofollow">
<title>Pemasangan Aplikasi KPI</title>
<link rel="icon" href="assets/logo.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Sora:wght@600;700&display=swap">
<style>
:root{--bg:#F3F4F8;--surface:#fff;--fg:#151A2E;--muted:#5B6280;--line:#DCE0EB;--accent:#2E3D8F;--accent-soft:#E5E8F6;--good:#16845A;--good-soft:#DDF2E8;--bad:#C2392F;--bad-soft:#FBE2DF;color-scheme:light}
@media (prefers-color-scheme:dark){:root{--bg:#0E1120;--surface:#161A2C;--fg:#E7E9F3;--muted:#9BA2BD;--line:#2B3150;--accent:#9AA8F2;--accent-soft:#252C52;--good:#4BD394;--good-soft:#12352A;--bad:#FF877C;--bad-soft:#40201D;color-scheme:dark}}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--fg);font:15px/1.6 "Plus Jakarta Sans",system-ui,sans-serif;padding-block:32px;padding-left:max(16px,env(safe-area-inset-left,0px));padding-right:max(16px,env(safe-area-inset-right,0px))}
.wrap{max-width:660px;margin:0 auto;display:flex;flex-direction:column;gap:18px}
h1{font-family:Sora,sans-serif;font-size:26px;margin:0}
h2{font-family:Sora,sans-serif;font-size:17px;margin:0 0 4px}
p{margin:0;color:var(--muted)}
.card{background:var(--surface);border:1px solid var(--line);border-radius:14px;padding:20px;display:flex;flex-direction:column;gap:14px}
.grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
@media (max-width:560px){.grid{grid-template-columns:1fr}}
label{display:flex;flex-direction:column;gap:5px;font-size:13px;font-weight:600;color:var(--muted)}
input[type=text],input[type=password],input[type=number]{border:1px solid var(--line);background:var(--bg);color:var(--fg);border-radius:9px;padding:10px 12px;font:inherit;font-size:15px;width:100%}
input:focus{outline:2px solid var(--accent);outline-offset:1px}
.radio{display:flex;gap:10px;align-items:flex-start;border:1px solid var(--line);border-radius:10px;padding:12px;cursor:pointer}
.radio input{margin:3px 0 0;accent-color:var(--accent)}
.radio b{display:block;font-size:14px;color:var(--fg)}
.radio span{font-size:13px;color:var(--muted)}
button{background:var(--accent);color:#fff;border:0;border-radius:10px;padding:13px 20px;font:inherit;font-weight:700;cursor:pointer;font-size:15px}
@media (prefers-color-scheme:dark){button{color:#0E1120}}
button:hover{filter:brightness(1.1)}
.msg{border-radius:10px;padding:12px 14px;font-size:14px;white-space:pre-wrap;overflow-wrap:anywhere}
.msg.bad{background:var(--bad-soft);color:var(--bad)}
.msg.good{background:var(--good-soft);color:var(--good)}
.steps{margin:0;padding-left:20px;color:var(--muted);font-size:14px;display:flex;flex-direction:column;gap:6px}
code{background:var(--accent-soft);color:var(--accent);padding:1px 6px;border-radius:5px;font-size:13.5px}
a{color:var(--accent)}
</style>
</head>
<body>
<div class="wrap">
  <div>
    <h1>Pemasangan Aplikasi KPI</h1>
    <p>Isi data koneksi database, lalu tabel dan data awal dibuat otomatis.</p>
  </div>

<?php if ($already): ?>
  <div class="card">
    <div class="msg good">Aplikasi sudah terpasang.</div>
    <p>Untuk memasang ulang, hapus file <code>config.php</code> lebih dulu. Semua data lama tetap berada di database.</p>
    <p><a href="index.php">Buka aplikasi</a></p>
  </div>
<?php elseif ($done): ?>
  <div class="card">
    <div class="msg good">Pemasangan selesai.</div>
    <h2>Dua langkah terakhir</h2>
    <ol class="steps">
      <li>Hapus file <code>install.php</code> dari server, supaya tidak bisa dipakai orang lain.</li>
      <li>Masuk ke aplikasi, lalu ganti password lewat menu <b>Pengaturan</b>.</li>
    </ol>
    <p><a href="index.php">Buka aplikasi sekarang</a></p>
  </div>
<?php else: ?>
  <?php if ($error): ?><div class="msg bad"><?= v($error) ?></div><?php endif; ?>
  <form method="post" class="card" autocomplete="off">
    <div>
      <h2>1. Database MySQL</h2>
      <p>Data ini didapat dari panel hosting Anda, saat membuat database baru.</p>
    </div>
    <div class="grid">
      <label>Host database<input type="text" name="db_host" value="<?= v((string)$f['db_host']) ?>" placeholder="localhost"></label>
      <label>Port<input type="number" name="db_port" value="<?= v((string)$f['db_port']) ?>"></label>
      <label>Nama database<input type="text" name="db_name" value="<?= v((string)$f['db_name']) ?>" required></label>
      <label>Pengguna database<input type="text" name="db_user" value="<?= v((string)$f['db_user']) ?>" required></label>
    </div>
    <label>Password database<input type="password" name="db_pass" value="<?= v((string)$f['db_pass']) ?>"></label>

    <div>
      <h2>2. Perusahaan</h2>
    </div>
    <div class="grid">
      <label>Nama perusahaan<input type="text" name="company" value="<?= v((string)$f['company']) ?>" placeholder="PT Contoh Sejahtera"></label>
      <label>Tahun penilaian<input type="number" name="year" value="<?= v((string)$f['year']) ?>"></label>
    </div>

    <div>
      <h2>3. Data awal</h2>
    </div>
    <label class="radio"><input type="radio" name="mode" value="demo" <?= $f['mode'] !== 'empty' ? 'checked' : '' ?>>
      <span><b>Dengan data contoh</b><span>24 orang, KPI berjenjang, dan realisasi beberapa bulan. Cocok untuk mencoba. Akun contoh ditampilkan di halaman masuk.</span></span></label>
    <label class="radio"><input type="radio" name="mode" value="empty" <?= $f['mode'] === 'empty' ? 'checked' : '' ?>>
      <span><b>Kosong, hanya akun CEO</b><span>Untuk langsung dipakai. Anda mengisi sendiri struktur organisasi dan KPI.</span></span></label>
    <div class="grid">
      <label>Nama CEO<input type="text" name="ceo_name" value="<?= v((string)$f['ceo_name']) ?>" placeholder="Nama lengkap"></label>
      <label>Username CEO<input type="text" name="ceo_user" value="<?= v((string)$f['ceo_user']) ?>" placeholder="nama.belakang"></label>
    </div>
    <label>Password CEO<input type="password" name="ceo_pass" value="" placeholder="minimal 6 karakter"></label>
    <p style="font-size:13px">Tiga isian di atas hanya dipakai bila Anda memilih "Kosong, hanya akun CEO".</p>

    <button type="submit">Pasang sekarang</button>
  </form>
<?php endif; ?>
</div>
</body>
</html>
