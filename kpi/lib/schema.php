<?php
/**
 * Definisi tabel database aplikasi KPI.
 * Dipakai oleh install.php dan oleh skrip pengujian.
 */

function kpi_schema_statements(): array
{
    $s = [];

    $s[] = "CREATE TABLE settings (
        skey   VARCHAR(64) NOT NULL PRIMARY KEY,
        svalue TEXT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $s[] = "CREATE TABLE users (
        id            INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        name          VARCHAR(120) NOT NULL,
        title         VARCHAR(120) NOT NULL DEFAULT '',
        level         VARCHAR(10)  NOT NULL,
        parent_id     INT UNSIGNED NULL,
        dept          VARCHAR(120) NOT NULL DEFAULT '',
        username      VARCHAR(64)  NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        is_active     TINYINT NOT NULL DEFAULT 1,
        created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY ux_users_username (username),
        KEY ix_users_parent (parent_id),
        CONSTRAINT fk_users_parent FOREIGN KEY (parent_id) REFERENCES users (id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $s[] = "CREATE TABLE kpis (
        id         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        owner_id   INT UNSIGNED NOT NULL,
        name       VARCHAR(160) NOT NULL,
        persp      VARCHAR(10)  NOT NULL DEFAULT 'fin',
        unit       VARCHAR(40)  NOT NULL DEFAULT '',
        target     DECIMAL(18,4) NOT NULL DEFAULT 0,
        polarity   VARCHAR(4) NOT NULL DEFAULT 'max',
        weight     DECIMAL(6,2) NOT NULL DEFAULT 0,
        parent_id  INT UNSIGNED NULL,
        rollup     VARCHAR(8) NOT NULL DEFAULT 'manual',
        descr      TEXT,
        sort_order INT NOT NULL DEFAULT 0,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY ix_kpis_owner (owner_id),
        KEY ix_kpis_parent (parent_id),
        CONSTRAINT fk_kpis_owner FOREIGN KEY (owner_id) REFERENCES users (id) ON DELETE CASCADE,
        CONSTRAINT fk_kpis_parent FOREIGN KEY (parent_id) REFERENCES kpis (id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $s[] = "CREATE TABLE actuals (
        id           INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        kpi_id       INT UNSIGNED NOT NULL,
        yr           SMALLINT NOT NULL,
        mo           TINYINT NOT NULL,
        val          DECIMAL(18,4) NULL,
        status       VARCHAR(12) NOT NULL DEFAULT 'draft',
        note         VARCHAR(500) NOT NULL DEFAULT '',
        submitted_by INT UNSIGNED NULL,
        approved_by  INT UNSIGNED NULL,
        updated_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY ux_actuals_period (kpi_id, yr, mo),
        CONSTRAINT fk_actuals_kpi FOREIGN KEY (kpi_id) REFERENCES kpis (id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $s[] = "CREATE TABLE login_attempts (
        ident        VARCHAR(190) NOT NULL PRIMARY KEY,
        attempts     INT NOT NULL DEFAULT 0,
        locked_until DATETIME NULL DEFAULT NULL,
        updated_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $s[] = "CREATE TABLE activity (
        id         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        user_id    INT UNSIGNED NULL,
        action     VARCHAR(40) NOT NULL,
        detail     VARCHAR(500) NOT NULL DEFAULT '',
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY ix_activity_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $s[] = kpi_admin_table_sql();

    return $s;
}

/**
 * Akun pengelola (sysAdmin dan infraAdmin), terpisah dari anggota organisasi.
 * Memakai IF NOT EXISTS supaya pemasangan lama ikut mendapat tabel ini.
 */
function kpi_admin_table_sql(): string
{
    return "CREATE TABLE IF NOT EXISTS admins (
        id            INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        name          VARCHAR(120) NOT NULL,
        username      VARCHAR(64)  NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        role          VARCHAR(12)  NOT NULL,
        is_active     TINYINT NOT NULL DEFAULT 1,
        last_login_at DATETIME NULL DEFAULT NULL,
        created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY ux_admins_username (username)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
}

/**
 * Akun admin bawaan, masing-masing dibuat sekali saat pemasangan.
 * Password wajib diganti sebelum aplikasi dipakai sungguhan.
 * `flag` mencatat di tabel settings bahwa akun itu sudah pernah dibuat.
 */
const KPI_DEFAULT_ADMINS = [
    [
        'name'     => 'Administrator',
        'username' => 'admin',
        'password' => '123456',
        'role'     => 'sysadmin',
        'flag'     => 'default_admin_created',
    ],
    [
        'name'     => 'Infrastructure Administrator',
        'username' => 'infraadmin',
        'password' => '12345678',
        'role'     => 'infraadmin',
        'flag'     => 'default_infraadmin_created',
    ],
];

/**
 * Membuat akun admin bawaan yang belum pernah dibuat. Akun yang sudah pernah dibuat
 * lalu dihapus tidak dibuat ulang, dan username yang sudah dipakai tidak ditimpa.
 * Mengembalikan username akun yang baru saja dibuat.
 */
function kpi_admin_seed_default(PDO $pdo): array
{
    $pdo->exec(kpi_admin_table_sql());
    $flag    = $pdo->prepare('SELECT svalue FROM settings WHERE skey = ?');
    $exists  = $pdo->prepare('SELECT COUNT(*) FROM admins WHERE username = ?');
    $insert  = $pdo->prepare('INSERT INTO admins (name, username, password_hash, role) VALUES (?,?,?,?)');
    $mark    = $pdo->prepare('INSERT INTO settings (skey, svalue) VALUES (?, ?) ON DUPLICATE KEY UPDATE svalue = VALUES(svalue)');
    $created = [];
    foreach (KPI_DEFAULT_ADMINS as $d) {
        $flag->execute([$d['flag']]);
        if ($flag->fetchColumn() !== false) {
            continue;
        }
        $exists->execute([$d['username']]);
        if ((int)$exists->fetchColumn() === 0) {
            $insert->execute([$d['name'], $d['username'], password_hash($d['password'], PASSWORD_DEFAULT), $d['role']]);
            $created[] = $d['username'];
        }
        $mark->execute([$d['flag'], date('Y-m-d H:i')]);
    }
    return $created;
}

/** Password bawaan yang masih dipakai akun ini, atau null bila sudah diganti. */
function kpi_admin_default_password(array $admin): ?string
{
    foreach (KPI_DEFAULT_ADMINS as $d) {
        if ($admin['username'] === $d['username'] && password_verify($d['password'], (string)$admin['password_hash'])) {
            return $d['password'];
        }
    }
    return null;
}

/** Akun bawaan yang aktif dan masih memakai password bawaan: username => password. */
function kpi_admin_defaults_in_use(PDO $pdo): array
{
    $out = [];
    $st  = $pdo->prepare('SELECT * FROM admins WHERE username = ? AND is_active = 1');
    foreach (KPI_DEFAULT_ADMINS as $d) {
        $st->execute([$d['username']]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if ($row && kpi_admin_default_password($row) !== null) {
            $out[$d['username']] = $d['password'];
        }
    }
    return $out;
}

/** Urutan hapus tabel (anak lebih dulu). */
function kpi_schema_tables(): array
{
    return ['admins', 'activity', 'login_attempts', 'actuals', 'kpis', 'users', 'settings'];
}
