<?php
/**
 * Koneksi database dan pengaturan aplikasi.
 */

define('KPI_ROOT', dirname(__DIR__));
define('KPI_CONFIG_FILE', KPI_ROOT . '/config.php');

function kpi_is_installed(): bool
{
    return is_file(KPI_CONFIG_FILE);
}

function kpi_config(): array
{
    static $cfg = null;
    if ($cfg === null) {
        if (!kpi_is_installed()) {
            throw new RuntimeException('Aplikasi belum dipasang. Buka install.php terlebih dahulu.');
        }
        $cfg = require KPI_CONFIG_FILE;
        if (!is_array($cfg)) {
            throw new RuntimeException('File config.php tidak valid.');
        }
    }
    return $cfg;
}

function kpi_dsn(array $cfg): string
{
    $host = $cfg['db_host'] ?? 'localhost';
    $port = (int)($cfg['db_port'] ?? 3306);
    $name = $cfg['db_name'] ?? '';
    return sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $host, $port, $name);
}

function kpi_connect(array $cfg): PDO
{
    $pdo = new PDO(kpi_dsn($cfg), $cfg['db_user'] ?? '', $cfg['db_pass'] ?? '', [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
    $pdo->exec("SET SESSION sql_mode = 'STRICT_ALL_TABLES'");
    return $pdo;
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $pdo = kpi_connect(kpi_config());
    }
    return $pdo;
}

/* ---------- Pengaturan aplikasi ---------- */

function kpi_settings(bool $fresh = false): array
{
    static $cache = null;
    if ($cache === null || $fresh) {
        $cache = [];
        foreach (db()->query('SELECT skey, svalue FROM settings') as $row) {
            $cache[$row['skey']] = $row['svalue'];
        }
    }
    return $cache;
}

function kpi_setting(string $key, $default = null)
{
    $all = kpi_settings();
    return array_key_exists($key, $all) ? $all[$key] : $default;
}

function kpi_set_setting(string $key, $value): void
{
    $st = db()->prepare('INSERT INTO settings (skey, svalue) VALUES (?, ?) ON DUPLICATE KEY UPDATE svalue = VALUES(svalue)');
    $st->execute([$key, (string)$value]);
    kpi_settings(true);
}

/** Pesan pemeliharaan bila aplikasi sedang ditutup sementara, selain itu null. */
function kpi_maintenance_message(): ?string
{
    if (kpi_setting('maintenance', '0') !== '1') {
        return null;
    }
    $msg = trim((string)kpi_setting('maintenance_msg', ''));
    return $msg !== '' ? $msg : 'Aplikasi sedang dalam pemeliharaan. Silakan coba lagi beberapa saat lagi.';
}

function kpi_log(?string $userId, string $action, string $detail = ''): void
{
    try {
        $st = db()->prepare('INSERT INTO activity (user_id, action, detail) VALUES (?, ?, ?)');
        $st->execute([$userId !== null ? (int)$userId : null, $action, mb_substr($detail, 0, 500)]);
    } catch (Throwable $e) {
        // Pencatatan aktivitas tidak boleh menggagalkan aksi utama.
    }
}
