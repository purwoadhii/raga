<?php
/**
 * Halaman admin: akun pengelola, sesi terpisah, dan pekerjaan tiap peran.
 *
 *   sysAdmin    mengurus isi aplikasi: anggota, pengaturan, dan akun admin.
 *   infraAdmin  mengurus tempat aplikasi berjalan: status server, cadangan
 *               database, mode pemeliharaan, dan catatan aktivitas.
 *
 * Akun admin disimpan di tabel `admins`, bukan di struktur organisasi,
 * sehingga admin tidak punya KPI dan tidak muncul di aplikasi.
 */

const KPI_ADMIN_ROLES = [
    'sysadmin'   => 'sysAdmin',
    'infraadmin' => 'infraAdmin',
];
const KPI_ADMIN_MIN_PASS = 8;

/** Halaman masuk tiap peran. */
const KPI_ADMIN_LINKS = [
    'sysadmin'   => 'sysadmin.php',
    'infraadmin' => 'infraadmin.php',
];

// sysadmin.php dan infraadmin.php menetapkan peran sebelum memuat admin.php.
if (!defined('KPI_ADMIN_GATE')) {
    define('KPI_ADMIN_GATE', null);
}

/** Halaman yang boleh dibuka tiap peran: kunci => judul menu. */
const KPI_ADMIN_PAGES = [
    'sysadmin' => [
        'ringkasan'  => 'Ringkasan',
        'anggota'    => 'Anggota',
        'pengaturan' => 'Pengaturan aplikasi',
        'admin'      => 'Akun admin',
        'aktivitas'  => 'Catatan aktivitas',
        'akun'       => 'Akun saya',
    ],
    'infraadmin' => [
        'status'       => 'Status server',
        'cadangan'     => 'Cadangan database',
        'pemeliharaan' => 'Mode pemeliharaan',
        'aktivitas'    => 'Catatan aktivitas',
        'akun'         => 'Akun saya',
    ],
];

/** Membuat tabel admins bila belum ada (pemasangan sebelum fitur ini). */
function kpi_admin_ensure_schema(): void
{
    if (kpi_admin_seed_default(db())) {
        kpi_settings(true);
    }
}

/** Akun ini masih memakai password bawaan akun admin. */
function kpi_admin_uses_default_password(array $admin): bool
{
    return $admin['username'] === KPI_DEFAULT_ADMIN['username']
        && password_verify(KPI_DEFAULT_ADMIN['password'], (string)$admin['password_hash']);
}

/* ---------- Sesi admin (terpisah dari sesi aplikasi) ---------- */

function kpi_admin_session_start(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'secure'   => $https,
        'samesite' => 'Strict',
    ]);
    // Tiap link punya sesi sendiri, jadi sysAdmin dan infraAdmin bisa masuk bersamaan di satu browser.
    session_name(KPI_ADMIN_GATE !== null ? 'KPIADM_' . KPI_ADMIN_GATE : 'KPIADMSESS');
    session_start();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
}

function kpi_admin_csrf(): string
{
    kpi_admin_session_start();
    return $_SESSION['csrf'];
}

function kpi_admin_check_csrf(?string $token): bool
{
    kpi_admin_session_start();
    return is_string($token) && $token !== '' && hash_equals($_SESSION['csrf'] ?? '', $token);
}

function kpi_admin_current(): ?array
{
    kpi_admin_session_start();
    if (empty($_SESSION['aid'])) {
        return null;
    }
    // Sesi admin berakhir sendiri setelah 60 menit tanpa kegiatan.
    if (time() - (int)($_SESSION['seen'] ?? 0) > 3600) {
        kpi_admin_logout();
        return null;
    }
    $st = db()->prepare('SELECT * FROM admins WHERE id = ? AND is_active = 1');
    $st->execute([(int)$_SESSION['aid']]);
    $row = $st->fetch();
    if (!$row || !isset(KPI_ADMIN_ROLES[$row['role']]) || (KPI_ADMIN_GATE !== null && $row['role'] !== KPI_ADMIN_GATE)) {
        kpi_admin_logout();
        return null;
    }
    $_SESSION['seen'] = time();
    return $row;
}

function kpi_admin_logout(): void
{
    kpi_admin_session_start();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function kpi_admin_login(string $username, string $password): array
{
    $username = mb_strtolower(trim($username));
    $generic  = 'Username atau password salah.';
    if ($username === '' || $password === '') {
        throw new KpiApiError($generic, 401);
    }
    $key  = 'admin:' . $username;
    $wait = kpi_locked_seconds($key);
    if ($wait > 0) {
        throw new KpiApiError('Terlalu banyak percobaan gagal. Coba lagi dalam ' . ceil($wait / 60) . ' menit.', 429);
    }
    $st = db()->prepare('SELECT * FROM admins WHERE username = ?');
    $st->execute([$username]);
    $row = $st->fetch();
    if (!$row || !$row['is_active'] || !password_verify($password, $row['password_hash'])) {
        kpi_note_failure($key);
        kpi_log(null, 'admin_login_gagal', $username);
        usleep(300000);
        throw new KpiApiError($generic, 401);
    }
    kpi_clear_failures($key);
    if (KPI_ADMIN_GATE !== null && $row['role'] !== KPI_ADMIN_GATE) {
        throw new KpiApiError('Akun ini adalah ' . KPI_ADMIN_ROLES[$row['role']] . '. Silakan masuk lewat link '
            . KPI_ADMIN_LINKS[$row['role']] . '.', 403);
    }
    kpi_admin_session_start();
    session_regenerate_id(true);
    $_SESSION['aid']  = (int)$row['id'];
    $_SESSION['seen'] = time();
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
    db()->prepare('UPDATE admins SET last_login_at = NOW() WHERE id = ?')->execute([$row['id']]);
    kpi_admin_log($row, 'admin_login', '');
    return $row;
}

/** Catatan aktivitas untuk tindakan admin. */
function kpi_admin_log(array $admin, string $action, string $detail): void
{
    kpi_log(null, $action, trim('[' . KPI_ADMIN_ROLES[$admin['role']] . ' ' . $admin['username'] . '] ' . $detail));
}

/* ---------- Akun admin ---------- */

function kpi_admin_all(): array
{
    return db()->query('SELECT * FROM admins ORDER BY role DESC, name')->fetchAll();
}

function kpi_admin_count_sys(?int $exceptId = null): int
{
    $st = db()->prepare("SELECT COUNT(*) FROM admins WHERE role = 'sysadmin' AND is_active = 1 AND id <> ?");
    $st->execute([$exceptId ?: 0]);
    return (int)$st->fetchColumn();
}

function kpi_admin_check_password(string $pass): void
{
    if (mb_strlen($pass) < KPI_ADMIN_MIN_PASS) {
        throw new KpiApiError('Password admin minimal ' . KPI_ADMIN_MIN_PASS . ' karakter.');
    }
}

/** Menambah atau mengubah akun admin. Mengembalikan id. */
function kpi_admin_save(array $in): int
{
    $db   = db();
    $id   = in_id($in, 'id', false);
    $name = in_str($in, 'name', 120);
    $role = in_str($in, 'role', 12);
    $pass = (string)($in['password'] ?? '');
    if ($name === '') {
        throw new KpiApiError('Nama wajib diisi.');
    }
    if (!isset(KPI_ADMIN_ROLES[$role])) {
        throw new KpiApiError('Pilih peran sysAdmin atau infraAdmin.');
    }
    $username = kpi_username_slug(in_str($in, 'username', 64) ?: $name);
    if ($username === '') {
        throw new KpiApiError('Username wajib diisi dengan huruf atau angka.');
    }
    $chk = $db->prepare('SELECT id FROM admins WHERE username = ? AND id <> ?');
    $chk->execute([$username, $id ?: 0]);
    if ($chk->fetchColumn()) {
        throw new KpiApiError('Username "' . $username . '" sudah dipakai admin lain.');
    }

    if ($id) {
        $st = $db->prepare('SELECT * FROM admins WHERE id = ?');
        $st->execute([$id]);
        $cur = $st->fetch();
        if (!$cur) {
            throw new KpiApiError('Akun admin tidak ditemukan.', 404);
        }
        if ($cur['role'] === 'sysadmin' && $role !== 'sysadmin' && $cur['is_active'] && kpi_admin_count_sys($id) === 0) {
            throw new KpiApiError('Harus ada minimal satu sysAdmin yang aktif.');
        }
        if ($pass !== '') {
            kpi_admin_check_password($pass);
            $db->prepare('UPDATE admins SET password_hash = ? WHERE id = ?')
               ->execute([password_hash($pass, PASSWORD_DEFAULT), $id]);
        }
        $db->prepare('UPDATE admins SET name = ?, username = ?, role = ? WHERE id = ?')
           ->execute([$name, $username, $role, $id]);
        return $id;
    }

    kpi_admin_check_password($pass);
    $db->prepare('INSERT INTO admins (name, username, password_hash, role) VALUES (?,?,?,?)')
       ->execute([$name, $username, password_hash($pass, PASSWORD_DEFAULT), $role]);
    return (int)$db->lastInsertId();
}

function kpi_admin_find(int $id): array
{
    $st = db()->prepare('SELECT * FROM admins WHERE id = ?');
    $st->execute([$id]);
    $row = $st->fetch();
    if (!$row) {
        throw new KpiApiError('Akun admin tidak ditemukan.', 404);
    }
    return $row;
}

function kpi_admin_set_active(int $id, bool $active, int $actorId): array
{
    $row = kpi_admin_find($id);
    if (!$active) {
        if ($id === $actorId) {
            throw new KpiApiError('Anda tidak dapat menonaktifkan akun Anda sendiri.');
        }
        if ($row['role'] === 'sysadmin' && kpi_admin_count_sys($id) === 0) {
            throw new KpiApiError('Harus ada minimal satu sysAdmin yang aktif.');
        }
    }
    db()->prepare('UPDATE admins SET is_active = ? WHERE id = ?')->execute([$active ? 1 : 0, $id]);
    return $row;
}

function kpi_admin_delete(int $id, int $actorId): array
{
    $row = kpi_admin_find($id);
    if ($id === $actorId) {
        throw new KpiApiError('Anda tidak dapat menghapus akun Anda sendiri.');
    }
    if ($row['role'] === 'sysadmin' && $row['is_active'] && kpi_admin_count_sys($id) === 0) {
        throw new KpiApiError('Harus ada minimal satu sysAdmin yang aktif.');
    }
    db()->prepare('DELETE FROM admins WHERE id = ?')->execute([$id]);
    return $row;
}

/**
 * Pembuatan admin pertama. Hanya berlaku selama belum ada sysAdmin aktif,
 * dan harus dibuktikan dengan username dan password CEO.
 */
function kpi_admin_bootstrap(array $in): void
{
    if (kpi_admin_count_sys() > 0) {
        throw new KpiApiError('Akun sysAdmin sudah ada. Silakan masuk.', 403);
    }
    $ceoUser = mb_strtolower(trim((string)($in['ceo_user'] ?? '')));
    $ceoPass = (string)($in['ceo_pass'] ?? '');
    $key     = 'admin-setup:' . $ceoUser;
    $wait    = kpi_locked_seconds($key);
    if ($wait > 0) {
        throw new KpiApiError('Terlalu banyak percobaan gagal. Coba lagi dalam ' . ceil($wait / 60) . ' menit.', 429);
    }
    $st = db()->prepare("SELECT * FROM users WHERE username = ? AND level = 'ceo' AND is_active = 1");
    $st->execute([$ceoUser]);
    $ceo = $st->fetch();
    if ($ceoUser === '' || !$ceo || !password_verify($ceoPass, $ceo['password_hash'])) {
        kpi_note_failure($key);
        usleep(300000);
        throw new KpiApiError('Username atau password CEO salah.', 401);
    }
    kpi_clear_failures($key);

    // Periksa semua isian lebih dulu supaya tidak ada akun yang terbuat separuh jalan.
    $sys = ['name' => $in['sys_name'] ?? '', 'username' => $in['sys_user'] ?? '', 'password' => $in['sys_pass'] ?? '', 'role' => 'sysadmin'];
    $inf = ['name' => $in['inf_name'] ?? '', 'username' => $in['inf_user'] ?? '', 'password' => $in['inf_pass'] ?? '', 'role' => 'infraadmin'];
    $withInfra = trim((string)$inf['username']) !== '' || (string)$inf['password'] !== '' || trim((string)$inf['name']) !== '';

    if (trim((string)$sys['name']) === '') {
        $sys['name'] = 'System Administrator';
    }
    kpi_admin_check_password((string)$sys['password']);
    if ($withInfra) {
        if (trim((string)$inf['name']) === '') {
            $inf['name'] = 'Infrastructure Administrator';
        }
        kpi_admin_check_password((string)$inf['password']);
        if (kpi_username_slug((string)($inf['username'] ?: $inf['name'])) === kpi_username_slug((string)($sys['username'] ?: $sys['name']))) {
            throw new KpiApiError('Username sysAdmin dan infraAdmin harus berbeda.');
        }
    }

    $db = db();
    $db->beginTransaction();
    try {
        kpi_admin_save($sys);
        if ($withInfra) {
            kpi_admin_save($inf);
        }
        $db->commit();
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }
    kpi_log((string)$ceo['id'], 'admin_awal', 'Akun admin pertama dibuat' . ($withInfra ? ' (sysAdmin dan infraAdmin)' : ' (sysAdmin)'));
}

/* ---------- sysAdmin: anggota ---------- */

function kpi_user_set_active(int $id, bool $active): array
{
    $users = kpi_all_users(true);
    if (!isset($users[$id])) {
        throw new KpiApiError('Anggota tidak ditemukan.', 404);
    }
    if (!$active && $users[$id]['level'] === 'ceo') {
        $n = 0;
        foreach ($users as $u) {
            if ($u['level'] === 'ceo' && $u['is_active'] && (int)$u['id'] !== $id) {
                $n++;
            }
        }
        if ($n === 0) {
            throw new KpiApiError('Akun CEO satu-satunya tidak dapat dinonaktifkan.');
        }
    }
    db()->prepare('UPDATE users SET is_active = ? WHERE id = ?')->execute([$active ? 1 : 0, $id]);
    kpi_all_users(true);
    return $users[$id];
}

/** Percobaan masuk yang sedang terkunci. */
function kpi_locked_list(): array
{
    return db()->query('SELECT ident, locked_until FROM login_attempts WHERE locked_until IS NOT NULL AND locked_until > NOW() ORDER BY locked_until')->fetchAll();
}

function kpi_unlock(string $ident): void
{
    db()->prepare('DELETE FROM login_attempts WHERE ident = ?')->execute([$ident]);
}

function kpi_overview(): array
{
    $db  = db();
    $one = fn(string $sql) => (int)$db->query($sql)->fetchColumn();
    $lv  = ['ceo' => 0, 'div' => 0, 'unit' => 0, 'staff' => 0];
    foreach ($db->query('SELECT level, COUNT(*) n FROM users GROUP BY level') as $r) {
        $lv[$r['level']] = (int)$r['n'];
    }
    return [
        'levels'    => $lv,
        'users'     => array_sum($lv),
        'inactive'  => $one('SELECT COUNT(*) FROM users WHERE is_active = 0'),
        'kpis'      => $one('SELECT COUNT(*) FROM kpis'),
        'no_kpi'    => $one('SELECT COUNT(*) FROM users u WHERE NOT EXISTS (SELECT 1 FROM kpis k WHERE k.owner_id = u.id)'),
        'pending'   => $one("SELECT COUNT(*) FROM actuals WHERE status = 'submitted'"),
        'admins'    => $one('SELECT COUNT(*) FROM admins WHERE is_active = 1'),
        'locked'    => count(kpi_locked_list()),
    ];
}

/* ---------- Catatan aktivitas ---------- */

function kpi_activity(int $limit = 200): array
{
    $limit = max(1, min(1000, $limit));
    return db()->query('SELECT a.id, a.action, a.detail, a.created_at, u.name AS user_name
        FROM activity a LEFT JOIN users u ON u.id = a.user_id
        ORDER BY a.id DESC LIMIT ' . $limit)->fetchAll();
}

function kpi_activity_purge(int $days): int
{
    $days = max(1, min(3650, $days));
    $st = db()->prepare('DELETE FROM activity WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)');
    $st->execute([$days]);
    return $st->rowCount();
}

/* ---------- infraAdmin: status server ---------- */

function kpi_is_local_request(): bool
{
    $host = strtolower((string)preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? ''));
    return in_array($host, ['localhost', '127.0.0.1', '[::1]'], true);
}

function kpi_bytes(float $n): string
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;
    while ($n >= 1024 && $i < count($units) - 1) {
        $n /= 1024;
        $i++;
    }
    return ($i === 0 ? (string)(int)$n : number_format($n, 1, ',', '.')) . ' ' . $units[$i];
}

/** Daftar pemeriksaan: [judul, nilai, keadaan (ok|warn|bad|info), saran]. */
function kpi_infra_checks(): array
{
    $db    = db();
    $cfg   = kpi_config();
    $local = kpi_is_local_request();
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    $c = [];

    $c[] = ['Versi PHP', PHP_VERSION, PHP_VERSION_ID >= 80000 ? 'ok' : 'bad', PHP_VERSION_ID >= 80000 ? '' : 'Perlu PHP 8.0 atau lebih baru.'];
    $c[] = ['Server web', (string)($_SERVER['SERVER_SOFTWARE'] ?? PHP_SAPI), 'info', ''];
    foreach (['pdo_mysql', 'mbstring', 'json'] as $ext) {
        $on  = extension_loaded($ext);
        $c[] = ['Ekstensi ' . $ext, $on ? 'Aktif' : 'Tidak aktif', $on ? 'ok' : 'bad', $on ? '' : 'Aktifkan ekstensi ini di php.ini.'];
    }
    $c[] = ['Database', (string)$db->query('SELECT VERSION()')->fetchColumn(), 'ok', ''];
    $c[] = ['Alamat database', ($cfg['db_host'] ?? '') . ':' . ($cfg['db_port'] ?? 3306) . ' / ' . ($cfg['db_name'] ?? ''), 'info', ''];
    $c[] = ['Pengguna database', (string)($cfg['db_user'] ?? ''), ($cfg['db_user'] ?? '') === 'root' && !$local ? 'warn' : 'info',
        ($cfg['db_user'] ?? '') === 'root' && !$local ? 'Di hosting, pakai pengguna database khusus, bukan root.' : ''];
    if (!$local) {
        $c[] = ['Password database', ($cfg['db_pass'] ?? '') === '' ? 'Kosong' : 'Terisi', ($cfg['db_pass'] ?? '') === '' ? 'bad' : 'ok',
            ($cfg['db_pass'] ?? '') === '' ? 'Beri password pada pengguna database.' : ''];
    }
    $c[] = ['HTTPS', $https ? 'Aktif' : 'Tidak aktif', $https ? 'ok' : ($local ? 'info' : 'warn'),
        $https || $local ? '' : 'Tanpa HTTPS, password bisa terbaca di jaringan. Aktifkan SSL di hosting.'];

    $hasInstall = is_file(KPI_ROOT . '/install.php');
    $c[] = ['File install.php', $hasInstall ? 'Masih ada' : 'Sudah dihapus', $hasInstall && !$local ? 'warn' : 'ok',
        $hasInstall && !$local ? 'Hapus install.php dari hosting setelah pemasangan selesai.' : ''];

    if (DIRECTORY_SEPARATOR === '/') {
        $perm  = @fileperms(KPI_CONFIG_FILE);
        $world = $perm !== false && ($perm & 0004);
        $c[] = ['Izin file config.php', $perm !== false ? substr(sprintf('%o', $perm), -3) : '?', $world ? 'warn' : 'ok',
            $world ? 'Ubah izin menjadi 640 supaya tidak terbaca pengguna lain di server.' : ''];
    }

    $demo = kpi_setting('demo_mode', '0') === '1';
    $c[] = ['Mode demo', $demo ? 'Aktif' : 'Mati', $demo && !$local ? 'warn' : 'info',
        $demo ? 'Akun contoh tampil di halaman login. sysAdmin dapat mematikannya di Pengaturan aplikasi.' : ''];

    // Akun contoh yang masih memakai password bawaan.
    $weak = 0;
    $st   = $db->prepare('SELECT password_hash FROM users WHERE username = ? AND is_active = 1');
    foreach (kpi_demo_default_logins() as $user => $pass) {
        $st->execute([$user]);
        $hash = $st->fetchColumn();
        if ($hash && password_verify($pass, (string)$hash)) {
            $weak++;
        }
    }
    $c[] = ['Password contoh bawaan', $weak ? $weak . ' akun masih memakainya' : 'Tidak ada', $weak ? ($local ? 'info' : 'bad') : 'ok',
        $weak ? 'Ganti password akun contoh sebelum aplikasi dipakai sungguhan.' : ''];

    $defaultAdmin = false;
    $st = $db->prepare('SELECT * FROM admins WHERE username = ? AND is_active = 1');
    $st->execute([KPI_DEFAULT_ADMIN['username']]);
    if ($row = $st->fetch()) {
        $defaultAdmin = kpi_admin_uses_default_password($row);
    }
    $c[] = ['Password admin bawaan', $defaultAdmin ? 'Akun "admin" masih memakai 123456' : 'Sudah diganti', $defaultAdmin ? ($local ? 'warn' : 'bad') : 'ok',
        $defaultAdmin ? 'Masuk sebagai admin lalu ganti password di menu Akun saya.' : ''];

    $maint = kpi_setting('maintenance', '0') === '1';
    $c[] = ['Mode pemeliharaan', $maint ? 'Aktif, aplikasi ditutup sementara' : 'Mati', $maint ? 'warn' : 'ok', ''];

    $free = @disk_free_space(KPI_ROOT);
    if ($free !== false) {
        $low = $free < 200 * 1024 * 1024;
        $c[] = ['Ruang disk tersisa', kpi_bytes((float)$free), $low ? 'warn' : 'ok', $low ? 'Ruang disk hampir habis.' : ''];
    }
    $sp = session_save_path() ?: sys_get_temp_dir();
    $sw = @is_writable($sp);
    $c[] = ['Folder sesi', $sw ? 'Bisa ditulis' : 'Tidak bisa ditulis', $sw ? 'ok' : 'bad', $sw ? '' : 'Login tidak akan bertahan. Periksa session.save_path.'];
    $c[] = ['Waktu server', date('Y-m-d H:i') . ' (' . date_default_timezone_get() . ')', 'info', ''];
    $c[] = ['Waktu database', substr((string)$db->query('SELECT NOW()')->fetchColumn(), 0, 16), 'info', ''];
    $c[] = ['Batas memori PHP', (string)ini_get('memory_limit'), 'info', ''];

    return $c;
}

/** Password bawaan akun contoh, untuk memeriksa apakah masih dipakai. */
function kpi_demo_default_logins(): array
{
    $out  = [];
    $list = json_decode((string)kpi_setting('demo_accounts', '[]'), true);
    foreach (is_array($list) ? $list : [] as $a) {
        if (isset($a['username'], $a['password'])) {
            $out[(string)$a['username']] = (string)$a['password'];
        }
    }
    return $out;
}

/** Ukuran dan jumlah baris tiap tabel aplikasi. */
function kpi_table_stats(): array
{
    $db    = db();
    $sizes = [];
    $st    = $db->query('SELECT table_name AS t, COALESCE(data_length, 0) + COALESCE(index_length, 0) AS b
        FROM information_schema.tables WHERE table_schema = DATABASE()');
    foreach ($st as $r) {
        $sizes[strtolower((string)$r['t'])] = (float)$r['b'];
    }
    $out = [];
    foreach (array_reverse(kpi_schema_tables()) as $t) {
        if (!isset($sizes[$t])) {
            continue;
        }
        $out[] = ['table' => $t, 'rows' => (int)$db->query("SELECT COUNT(*) FROM `$t`")->fetchColumn(), 'bytes' => $sizes[$t]];
    }
    return $out;
}

/* ---------- infraAdmin: cadangan database ---------- */

/** Menuliskan seluruh isi database sebagai SQL yang bisa diimpor lewat phpMyAdmin. */
function kpi_sql_dump_output(): void
{
    $db = db();
    echo "-- Cadangan database aplikasi KPI\n";
    echo '-- Dibuat: ' . date('Y-m-d H:i:s') . "\n";
    echo "-- Cara memulihkan: impor file ini ke database kosong lewat phpMyAdmin (menu Import).\n\n";
    echo "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS = 0;\n\n";

    $have = [];
    foreach ($db->query('SHOW TABLES') as $row) {
        $have[] = strtolower((string)array_values($row)[0]);
    }
    foreach (array_reverse(kpi_schema_tables()) as $t) {
        if (!in_array($t, $have, true)) {
            continue;
        }
        $create = $db->query("SHOW CREATE TABLE `$t`")->fetch(PDO::FETCH_NUM);
        echo "DROP TABLE IF EXISTS `$t`;\n" . $create[1] . ";\n\n";

        $rows = $db->query("SELECT * FROM `$t`");
        $n    = 0;
        while ($r = $rows->fetch(PDO::FETCH_ASSOC)) {
            if ($n % 200 === 0) {
                echo ($n ? ";\n" : '') . "INSERT INTO `$t` (`" . implode('`, `', array_keys($r)) . "`) VALUES\n";
            } else {
                echo ",\n";
            }
            $vals = [];
            foreach ($r as $v) {
                $vals[] = $v === null ? 'NULL' : $db->quote((string)$v);
            }
            echo '(' . implode(', ', $vals) . ')';
            $n++;
        }
        echo $n ? ";\n\n" : "\n";
    }
    echo "SET FOREIGN_KEY_CHECKS = 1;\n";
}
