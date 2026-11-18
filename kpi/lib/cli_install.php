<?php
/**
 * Pemasangan lewat baris perintah, dipanggil oleh `npm run setup`.
 * Membuat database bila belum ada, membuat tabel, mengisi data awal,
 * lalu menulis config.php. Isian dikirim sebagai JSON di variabel KPI_SETUP.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__ . '/db.php';
require __DIR__ . '/schema.php';
require __DIR__ . '/demo.php';
require __DIR__ . '/handlers.php';
require __DIR__ . '/installer.php';

function cli_fail(string $msg, int $code = 1): void
{
    fwrite(STDERR, $msg . "\n");
    exit($code);
}

if (!extension_loaded('pdo_mysql')) {
    cli_fail('PHP di komputer ini belum mengaktifkan ekstensi pdo_mysql. '
        . 'Aktifkan "extension=pdo_mysql" di php.ini, atau pakai PHP bawaan XAMPP.');
}

$o = json_decode((string)getenv('KPI_SETUP'), true);
if (!is_array($o)) {
    cli_fail('Isian pemasangan tidak ditemukan. Jalankan lewat "npm run setup".');
}

$cfg = [
    'db_host' => trim((string)($o['db_host'] ?? '127.0.0.1')) ?: '127.0.0.1',
    'db_port' => (int)($o['db_port'] ?? 3306) ?: 3306,
    'db_name' => trim((string)($o['db_name'] ?? 'kpi')) ?: 'kpi',
    'db_user' => trim((string)($o['db_user'] ?? 'root')) ?: 'root',
    'db_pass' => (string)($o['db_pass'] ?? ''),
];
if (!preg_match('/^[A-Za-z0-9_]{1,64}$/', $cfg['db_name'])) {
    cli_fail('Nama database hanya boleh berisi huruf, angka, dan garis bawah.');
}

try {
    // Buat database bila belum ada. Di hosting bersama langkah ini bisa ditolak;
    // itu tidak masalah selama databasenya sudah dibuat lewat panel.
    try {
        $server = new PDO(
            sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $cfg['db_host'], $cfg['db_port']),
            $cfg['db_user'],
            $cfg['db_pass'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        $server->exec('CREATE DATABASE IF NOT EXISTS `' . $cfg['db_name'] . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    } catch (PDOException $e) {
        $code = (int)($e->errorInfo[1] ?? 0);
        if ($code !== 1044 && $code !== 1142) {
            throw $e;
        }
    }

    $pdo   = kpi_connect($cfg);
    $have  = kpi_existing_tables($pdo);
    $reuse = false;

    if (in_array('users', $have, true) && in_array('settings', $have, true)
        && (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() > 0) {
        // Database sudah berisi data aplikasi ini: pakai apa adanya.
        $reuse = true;
        kpi_admin_seed_default($pdo);
    } else {
        kpi_install_data($pdo, $o);
    }

    if (!kpi_write_config($cfg)) {
        cli_fail('Tidak dapat menulis file kpi/config.php. Periksa izin tulis folder kpi.');
    }

    echo json_encode([
        'reused' => $reuse,
        'mode'   => ($o['mode'] ?? 'demo') === 'empty' ? 'empty' : 'demo',
        'users'  => (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
        'kpis'   => (int)$pdo->query('SELECT COUNT(*) FROM kpis')->fetchColumn(),
        'demo'   => kpi_setting_raw($pdo, 'demo_mode') === '1',
        'admin'  => kpi_default_admin_active($pdo),
    ]) . "\n";
} catch (PDOException $e) {
    $code = (int)($e->errorInfo[1] ?? 0);
    if ($code === 2002 || $code === 2003 || $code === 2006) {
        cli_fail('Tidak bisa terhubung ke MySQL di ' . $cfg['db_host'] . ':' . $cfg['db_port']
            . '. Nyalakan MySQL lebih dulu (di XAMPP: klik Start pada MySQL), lalu ulangi.', 2);
    }
    if ($code === 1045 || $code === 1698) {
        cli_fail('MySQL menolak pengguna "' . $cfg['db_user'] . '". Periksa nama pengguna dan password database.', 3);
    }
    if ($code === 1044 || $code === 1049) {
        cli_fail('Database "' . $cfg['db_name'] . '" tidak ada dan pengguna ini tidak boleh membuatnya. '
            . 'Buat databasenya lebih dulu, lalu ulangi.', 4);
    }
    cli_fail('Galat database: ' . $e->getMessage(), 5);
} catch (Throwable $e) {
    cli_fail($e->getMessage(), 6);
}

/** Akun admin bawaan masih ada dan masih memakai password bawaan. */
function kpi_default_admin_active(PDO $pdo): bool
{
    $st = $pdo->prepare('SELECT password_hash FROM admins WHERE username = ? AND is_active = 1');
    $st->execute([KPI_DEFAULT_ADMIN['username']]);
    $hash = $st->fetchColumn();
    return $hash !== false && password_verify(KPI_DEFAULT_ADMIN['password'], (string)$hash);
}

function kpi_setting_raw(PDO $pdo, string $key): ?string
{
    $st = $pdo->prepare('SELECT svalue FROM settings WHERE skey = ?');
    $st->execute([$key]);
    $v = $st->fetchColumn();
    return $v === false ? null : (string)$v;
}
