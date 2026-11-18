<?php
/**
 * Langkah pemasangan yang dipakai bersama oleh install.php (lewat browser)
 * dan lib/cli_install.php (lewat `npm run setup`).
 */

function kpi_demo_accounts(): array
{
    return [
        ['level' => 'CEO', 'username' => 'budi.santoso', 'password' => '1111'],
        ['level' => 'Division Head', 'username' => 'rina.wijaya', 'password' => '2222'],
        ['level' => 'Unit Head', 'username' => 'agus.prasetyo', 'password' => '3333'],
        ['level' => 'Staff', 'username' => 'dewi.lestari', 'password' => '4444'],
    ];
}

/** Nama tabel aplikasi yang sudah ada di database ini. */
function kpi_existing_tables(PDO $pdo): array
{
    $exist = [];
    foreach ($pdo->query('SHOW TABLES') as $row) {
        $exist[] = strtolower((string)array_values($row)[0]);
    }
    return array_values(array_intersect($exist, kpi_schema_tables()));
}

/**
 * Membuat tabel dan mengisi data awal.
 * $o: company, year, mode ('demo' | 'empty'), ceo_name, ceo_user, ceo_pass
 */
function kpi_install_data(PDO $pdo, array $o): void
{
    $clash = kpi_existing_tables($pdo);
    if ($clash) {
        throw new RuntimeException('Database ini sudah berisi tabel: ' . implode(', ', $clash)
            . '. Gunakan database kosong, atau hapus tabel tersebut lebih dulu.');
    }

    $company = trim((string)($o['company'] ?? '')) ?: 'Perusahaan';
    $year    = (int)($o['year'] ?? 0) ?: (int)date('Y');
    $mode    = ($o['mode'] ?? 'demo') === 'empty' ? 'empty' : 'demo';

    // Periksa isian akun CEO sebelum membuat tabel, supaya tidak ada pemasangan setengah jadi.
    $name = $user = $pass = '';
    if ($mode === 'empty') {
        $name = trim((string)($o['ceo_name'] ?? '')) ?: 'Direktur Utama';
        $user = kpi_username_slug((string)($o['ceo_user'] ?? '') ?: $name);
        $pass = (string)($o['ceo_pass'] ?? '');
        if ($user === '') {
            throw new RuntimeException('Username CEO wajib diisi.');
        }
        if (mb_strlen($pass) < 6) {
            throw new RuntimeException('Password CEO minimal 6 karakter.');
        }
    }

    foreach (kpi_schema_statements() as $sql) {
        $pdo->exec($sql);
    }

    $set = $pdo->prepare('INSERT INTO settings (skey, svalue) VALUES (?, ?)');
    $set->execute(['company', $company]);
    $set->execute(['cap', '120']);
    $set->execute(['year', (string)$year]);
    $set->execute(['demo_mode', $mode === 'demo' ? '1' : '0']);

    if ($mode === 'demo') {
        kpi_seed_demo($pdo, $year);
        $set->execute(['demo_accounts', json_encode(kpi_demo_accounts(), JSON_UNESCAPED_UNICODE)]);
    } else {
        $ins = $pdo->prepare('INSERT INTO users (name, title, level, parent_id, dept, username, password_hash) VALUES (?,?,?,NULL,?,?,?)');
        $ins->execute([$name, 'Chief Executive Officer', 'ceo', 'Direksi', $user, password_hash($pass, PASSWORD_DEFAULT)]);
    }

    kpi_admin_seed_default($pdo);
}

/** Isi file config.php untuk data koneksi yang diberikan. */
function kpi_config_php(array $cfg): string
{
    return "<?php\n// Dibuat otomatis saat pemasangan pada " . date('Y-m-d H:i') . "\n"
         . "// Jangan bagikan file ini: berisi password database.\n"
         . 'return ' . var_export($cfg, true) . ";\n";
}

/** Menulis config.php; mengembalikan false bila folder tidak bisa ditulis. */
function kpi_write_config(array $cfg): bool
{
    if (@file_put_contents(KPI_CONFIG_FILE, kpi_config_php($cfg)) === false) {
        return false;
    }
    @chmod(KPI_CONFIG_FILE, 0640);
    return true;
}
