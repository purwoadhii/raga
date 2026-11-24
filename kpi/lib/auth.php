<?php
/**
 * Sesi, login, dan pemeriksaan hak akses.
 */

const KPI_LEVELS     = ['ceo', 'div', 'unit', 'staff'];
const KPI_CHILD_LEVEL = ['ceo' => 'div', 'div' => 'unit', 'unit' => 'staff', 'staff' => null];
const KPI_MAX_ATTEMPTS = 8;

/**
 * Role anggota, diatur sysAdmin di menu Anggota. Level (CEO, Division Head, Unit Head, Staff)
 * tetap mengikuti atasan; role menambah hak di luar timnya sendiri.
 *   anggota    melihat dan mengatur dirinya serta tim di bawahnya (seperti sebelumnya)
 *   pemantau   juga bisa melihat KPI dan skor semua anggota, tanpa mengubahnya
 *   pengelola  mengatur KPI semua anggota di satu divisi atau unit (users.role_scope = id
 *              kepalanya; kosong berarti divisinya sendiri)
 *   superuser  seperti CEO: semua KPI di seluruh perusahaan, anggota, dan pengaturan aplikasi
 */
const KPI_ROLES = [
    'anggota'   => 'Anggota biasa',
    'pemantau'  => 'Pemantau',
    'pengelola' => 'Pengelola KPI',
    'superuser' => 'Superuser',
];
const KPI_LOCK_MINUTES = 15;

function kpi_session_start(): void
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
        'samesite' => 'Lax',
    ]);
    session_name('KPISESSID');
    session_start();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
}

function kpi_csrf_token(): string
{
    kpi_session_start();
    return $_SESSION['csrf'];
}

function kpi_check_csrf(?string $token): bool
{
    kpi_session_start();
    return is_string($token) && hash_equals($_SESSION['csrf'] ?? '', $token);
}

/* ---------- Pengguna yang sedang masuk ---------- */

function kpi_current_user(): ?array
{
    kpi_session_start();
    if (empty($_SESSION['uid'])) {
        return null;
    }
    static $user = null;
    if ($user === null) {
        $st = db()->prepare('SELECT * FROM users WHERE id = ? AND is_active = 1');
        $st->execute([(int)$_SESSION['uid']]);
        $user = $st->fetch() ?: false;
        if (!$user) {
            kpi_logout();
            return null;
        }
    }
    return $user ?: null;
}

function kpi_require_user(): array
{
    $u = kpi_current_user();
    if (!$u) {
        throw new KpiApiError('Sesi Anda sudah berakhir. Silakan masuk kembali.', 401);
    }
    return $u;
}

function kpi_logout(): void
{
    kpi_session_start();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/* ---------- Percobaan login ---------- */

function kpi_attempt_key(string $username): string
{
    return mb_substr($username, 0, 100) . '|' . ($_SERVER['REMOTE_ADDR'] ?? 'cli');
}

function kpi_locked_seconds(string $username): int
{
    $st = db()->prepare('SELECT locked_until FROM login_attempts WHERE ident = ?');
    $st->execute([kpi_attempt_key($username)]);
    $row = $st->fetch();
    if (!$row || !$row['locked_until']) {
        return 0;
    }
    $left = strtotime($row['locked_until']) - time();
    return $left > 0 ? $left : 0;
}

function kpi_note_failure(string $username): void
{
    $key = kpi_attempt_key($username);
    $db  = db();
    $st  = $db->prepare('SELECT attempts FROM login_attempts WHERE ident = ?');
    $st->execute([$key]);
    $n = (int)($st->fetchColumn() ?: 0) + 1;
    $lock = $n >= KPI_MAX_ATTEMPTS ? date('Y-m-d H:i:s', time() + KPI_LOCK_MINUTES * 60) : null;
    $up = $db->prepare('INSERT INTO login_attempts (ident, attempts, locked_until) VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE attempts = VALUES(attempts), locked_until = VALUES(locked_until)');
    $up->execute([$key, $n >= KPI_MAX_ATTEMPTS ? 0 : $n, $lock]);
}

function kpi_clear_failures(string $username): void
{
    $st = db()->prepare('DELETE FROM login_attempts WHERE ident = ?');
    $st->execute([kpi_attempt_key($username)]);
}

function kpi_login(string $username, string $password): array
{
    $username = mb_strtolower(trim($username));
    $generic  = 'Username atau password salah. Silakan coba lagi.'; // diterjemahkan oleh KpiApiError

    if ($username === '' || $password === '') {
        throw new KpiApiError($generic, 401);
    }
    $wait = kpi_locked_seconds($username);
    if ($wait > 0) {
        throw new KpiApiError('Terlalu banyak percobaan gagal. Coba lagi dalam {0} menit.', 429, [(int)ceil($wait / 60)]);
    }

    $st = db()->prepare('SELECT * FROM users WHERE username = ?');
    $st->execute([$username]);
    $user = $st->fetch();

    if (!$user || !$user['is_active'] || !password_verify($password, $user['password_hash'])) {
        $adminPage = kpi_admin_page_for($username, $password);
        if ($adminPage !== null) {
            throw new KpiApiError('Ini akun admin, bukan akun anggota. Masuk lewat halaman admin: {0}',
                401, [kpi_app_url($adminPage)]);
        }
        kpi_note_failure($username);
        usleep(300000);
        throw new KpiApiError($generic, 401);
    }

    if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
        $up = db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $up->execute([password_hash($password, PASSWORD_DEFAULT), $user['id']]);
    }

    kpi_clear_failures($username);
    kpi_session_start();
    session_regenerate_id(true);
    $_SESSION['uid']  = (int)$user['id'];
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
    kpi_log((string)$user['id'], 'login', $user['username']);
    return $user;
}

/**
 * Bila username dan password ini milik akun admin, kembalikan halaman masuknya
 * (sysAdmin atau infraAdmin), supaya orang yang salah halaman diberi petunjuk.
 */
function kpi_admin_page_for(string $username, string $password): ?string
{
    try {
        $st = db()->prepare('SELECT role, password_hash FROM admins WHERE username = ? AND is_active = 1');
        $st->execute([$username]);
        $row = $st->fetch();
    } catch (Throwable $e) {
        return null; // tabel admins belum ada
    }
    if (!$row || !password_verify($password, (string)$row['password_hash'])) {
        return null;
    }
    return $row['role'] === 'infraadmin' ? 'infraAdmin' : 'sysAdmin';
}

/** Alamat lengkap sebuah halaman aplikasi, dari alamat permintaan yang sedang berjalan. */
function kpi_app_url(string $page): string
{
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    $host = (string)($_SERVER['HTTP_HOST'] ?? '');
    if ($host === '' || !preg_match('/^[A-Za-z0-9.\-:\[\]]+$/', $host)) {
        return $page;
    }
    $dir = rtrim(str_replace('\\', '/', dirname((string)($_SERVER['SCRIPT_NAME'] ?? '/'))), '/');
    return ($https ? 'https' : 'http') . '://' . $host . $dir . '/' . $page;
}

/* ---------- Struktur organisasi dan hak akses ---------- */

/** Semua pengguna sebagai id => baris. */
function kpi_all_users(bool $fresh = false): array
{
    static $cache = null;
    if ($cache === null || $fresh) {
        $cache = [];
        foreach (db()->query('SELECT * FROM users ORDER BY id') as $row) {
            $cache[(int)$row['id']] = $row;
        }
    }
    return $cache;
}

/** Id semua bawahan (langsung dan tidak langsung). */
function kpi_descendant_ids(int $rootId): array
{
    $byParent = [];
    foreach (kpi_all_users() as $u) {
        $byParent[(int)($u['parent_id'] ?? 0)][] = (int)$u['id'];
    }
    $out   = [];
    $queue = $byParent[$rootId] ?? [];
    $guard = 0;
    while ($queue && $guard++ < 10000) {
        $id = array_shift($queue);
        if (isset($out[$id])) {
            continue;
        }
        $out[$id] = true;
        foreach ($byParent[$id] ?? [] as $child) {
            $queue[] = $child;
        }
    }
    return array_keys($out);
}

function kpi_role(array $u): string
{
    $r = (string)($u['role'] ?? 'anggota');
    return isset(KPI_ROLES[$r]) ? $r : 'anggota';
}

/** Boleh melihat KPI semua anggota: CEO, Pemantau, dan Superuser. */
function kpi_sees_all(array $u): bool
{
    return $u['level'] === 'ceo' || in_array(kpi_role($u), ['pemantau', 'superuser'], true);
}

/** Boleh mengatur KPI siapa pun, struktur organisasi, dan pengaturan aplikasi: CEO dan Superuser. */
function kpi_is_manager(array $u): bool
{
    return $u['level'] === 'ceo' || kpi_role($u) === 'superuser';
}

/** Divisi tempat anggota ini berada: id Division Head-nya (dirinya sendiri bila ia Division Head). */
function kpi_division_of(array $u): ?int
{
    $users = kpi_all_users();
    $seen  = [];
    while ($u && !isset($seen[(int)$u['id']])) {
        if ($u['level'] === 'div') {
            return (int)$u['id'];
        }
        $seen[(int)$u['id']] = true;
        $u = $u['parent_id'] !== null ? ($users[(int)$u['parent_id']] ?? null) : null;
    }
    return null;
}

/** Kepala divisi atau unit yang boleh dipilih sebagai wilayah Pengelola KPI. */
function kpi_area_valid(?int $headId): bool
{
    $users = kpi_all_users();
    return $headId !== null && isset($users[$headId]) && in_array($users[$headId]['level'], ['div', 'unit'], true);
}

/**
 * Wilayah Pengelola KPI: id kepala divisi atau unit yang dikelola, atau null bila bukan Pengelola.
 * Bila belum dipilih (atau kepalanya sudah tidak ada), wilayahnya divisi anggota itu sendiri.
 */
function kpi_role_area(array $u): ?int
{
    if (kpi_role($u) !== 'pengelola') {
        return null;
    }
    $scope = isset($u['role_scope']) && $u['role_scope'] !== null ? (int)$u['role_scope'] : null;
    return kpi_area_valid($scope) ? $scope : kpi_division_of($u);
}

/** Anggota di wilayah Pengelola KPI: kepala divisi atau unit beserta seluruh bawahannya. */
function kpi_area_ids(array $u): array
{
    $head = kpi_role_area($u);
    return $head === null ? [] : array_merge([$head], kpi_descendant_ids($head));
}

/** Pengguna yang boleh dilihat: diri sendiri, seluruh bawahan, dan wilayah kelolanya, atau semua orang bila role-nya mengizinkan. */
function kpi_scope_ids(array $viewer): array
{
    if (kpi_sees_all($viewer)) {
        return array_keys(kpi_all_users());
    }
    $own = array_merge([(int)$viewer['id']], kpi_descendant_ids((int)$viewer['id']));
    return array_values(array_unique(array_merge($own, kpi_area_ids($viewer))));
}

function kpi_in_scope(array $viewer, int $targetId): bool
{
    return in_array($targetId, kpi_scope_ids($viewer), true);
}

function kpi_is_ancestor(int $maybeAncestor, int $userId): bool
{
    return in_array($userId, kpi_descendant_ids($maybeAncestor), true);
}

/** Boleh menetapkan atau mengubah KPI milik pengguna ini. */
function kpi_can_define(array $viewer, int $ownerId): bool
{
    return kpi_is_manager($viewer) || kpi_is_ancestor((int)$viewer['id'], $ownerId)
        || in_array($ownerId, kpi_area_ids($viewer), true);
}

/** Boleh menyetujui realisasi pengguna ini (hanya atasan langsung). */
function kpi_can_approve(array $viewer, int $ownerId): bool
{
    $users = kpi_all_users();
    return isset($users[$ownerId]) && (int)($users[$ownerId]['parent_id'] ?? 0) === (int)$viewer['id'];
}

/** Struktur organisasi dan pengaturan aplikasi: hanya CEO dan Superuser. */
function kpi_require_manager(array $viewer): void
{
    if (!kpi_is_manager($viewer)) {
        throw new KpiApiError('Hanya CEO atau Superuser yang dapat melakukan tindakan ini.', 403);
    }
}
