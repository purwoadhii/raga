<?php
/**
 * Seluruh aksi API aplikasi KPI.
 */

/**
 * Galat yang ditampilkan ke pengguna. Pesan ditulis dalam bahasa Indonesia dan diterjemahkan
 * ke bahasa pilihan pengguna (lib/i18n.php); {0}, {1}, ... diisi dari $args.
 */
class KpiApiError extends RuntimeException
{
    public int $status;

    public function __construct(string $message, int $status = 400, array $args = [])
    {
        parent::__construct(function_exists('kpi_t') ? kpi_t($message, ...$args) : $message);
        $this->status = $status;
    }
}

/* ---------- Pembaca masukan ---------- */

function in_str(array $in, string $key, int $max = 255, string $default = ''): string
{
    $v = $in[$key] ?? $default;
    if (is_numeric($v)) {
        $v = (string)$v;
    }
    if (!is_string($v)) {
        throw new KpiApiError('Isian "{0}" tidak valid.', 400, [$key]);
    }
    $v = trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $v));
    return mb_substr($v, 0, $max);
}

function in_num(array $in, string $key, ?float $default = null): ?float
{
    $v = $in[$key] ?? null;
    if ($v === null || $v === '') {
        return $default;
    }
    if (is_string($v)) {
        $v = str_replace(',', '.', trim($v));
    }
    if (!is_numeric($v)) {
        throw new KpiApiError('Isian "{0}" harus berupa angka.', 400, [$key]);
    }
    return (float)$v;
}

function in_id(array $in, string $key, bool $required = true): ?int
{
    $v = $in[$key] ?? null;
    if ($v === null || $v === '' || $v === '0') {
        if ($required) {
            throw new KpiApiError('Isian "{0}" wajib diisi.', 400, [$key]);
        }
        return null;
    }
    if (!is_numeric($v)) {
        throw new KpiApiError('Isian "{0}" tidak valid.', 400, [$key]);
    }
    return (int)$v;
}

function kpi_username_slug(string $raw): string
{
    $s = mb_strtolower(trim($raw));
    $s = strtr($s, ['á'=>'a','à'=>'a','ä'=>'a','é'=>'e','è'=>'e','ë'=>'e','í'=>'i','ì'=>'i','ó'=>'o','ò'=>'o','ö'=>'o','ú'=>'u','ù'=>'u','ü'=>'u','ç'=>'c','ñ'=>'n']);
    $s = preg_replace('/[^a-z0-9]+/', '.', $s);
    return trim((string)$s, '.');
}

function kpi_year(): int
{
    return (int)kpi_setting('year', (int)date('Y'));
}

/* ---------- Pengambilan data ---------- */

function kpi_public_user(array $u, bool $withUsername): array
{
    $out = [
        'id'       => (string)$u['id'],
        'name'     => $u['name'],
        'title'    => $u['title'],
        'level'    => $u['level'],
        'role'     => kpi_role($u),
        'parentId' => $u['parent_id'] !== null ? (string)$u['parent_id'] : null,
        'dept'     => $u['dept'],
    ];
    if ($withUsername) {
        $out['username'] = $u['username'];
    }
    return $out;
}

function kpi_public_kpi(array $k, bool $full = true): array
{
    $out = [
        'id'       => (string)$k['id'],
        'ownerId'  => (string)$k['owner_id'],
        'name'     => $k['name'],
        'parentId' => $k['parent_id'] !== null ? (string)$k['parent_id'] : null,
    ];
    if ($full) {
        $out += [
            'persp'    => $k['persp'],
            'unit'     => $k['unit'],
            'target'   => (float)$k['target'],
            'polarity' => $k['polarity'],
            'weight'   => (float)$k['weight'],
            'rollup'   => $k['rollup'],
            'desc'     => (string)($k['descr'] ?? ''),
        ];
    } else {
        $out['stub'] = true;
    }
    return $out;
}

/**
 * Data lengkap untuk satu pengguna: struktur organisasi penuh,
 * tetapi KPI dan realisasi hanya untuk dirinya dan bawahannya.
 */
function kpi_bootstrap(array $viewer): array
{
    $db      = db();
    $all     = kpi_all_users(true);
    $scope   = kpi_scope_ids($viewer);
    $year    = kpi_year();
    $manager = kpi_is_manager($viewer);
    // Username hanya untuk tim sendiri, atau semua bila boleh mengatur anggota.
    $own     = array_merge([(int)$viewer['id']], kpi_descendant_ids((int)$viewer['id']));

    $users = [];
    foreach ($all as $id => $u) {
        $users[] = kpi_public_user($u, $manager || in_array((int)$id, $own, true));
    }

    $place = implode(',', array_fill(0, count($scope), '?'));
    $st = $db->prepare("SELECT * FROM kpis WHERE owner_id IN ($place) ORDER BY owner_id, sort_order, id");
    $st->execute($scope);
    $rows = $st->fetchAll();

    $kpis   = [];
    $ids    = [];
    $parents = [];
    foreach ($rows as $r) {
        $kpis[] = kpi_public_kpi($r);
        $ids[(int)$r['id']] = true;
        if ($r['parent_id'] !== null) {
            $parents[(int)$r['parent_id']] = true;
        }
    }
    // KPI atasan yang hanya dipakai sebagai keterangan "mendukung ..."
    $missing = array_diff(array_keys($parents), array_keys($ids));
    if ($missing) {
        $place2 = implode(',', array_fill(0, count($missing), '?'));
        $st2 = $db->prepare("SELECT id, owner_id, name, parent_id FROM kpis WHERE id IN ($place2)");
        $st2->execute(array_values($missing));
        foreach ($st2->fetchAll() as $r) {
            $kpis[] = kpi_public_kpi($r, false);
            $ids[(int)$r['id']] = true;
        }
    }

    // Pengelola KPI: nama KPI atasan kepala wilayahnya, untuk pilihan "diturunkan dari KPI atasan".
    $area = kpi_role_area($viewer);
    $up   = $area !== null && $all[$area]['parent_id'] !== null ? (int)$all[$area]['parent_id'] : null;
    $extra = [];
    if ($up !== null && !in_array($up, $scope, true)) {
        $st4 = $db->prepare('SELECT id, owner_id, name, parent_id FROM kpis WHERE owner_id = ? ORDER BY sort_order, id');
        $st4->execute([$up]);
        foreach ($st4->fetchAll() as $r) {
            if (!isset($ids[(int)$r['id']])) {
                $extra[] = kpi_public_kpi($r, false);
            }
        }
    }

    $actuals = new stdClass();
    if ($ids) {
        $place3 = implode(',', array_fill(0, count($ids), '?'));
        $st3 = $db->prepare("SELECT a.* FROM actuals a WHERE a.yr = ? AND a.kpi_id IN ($place3)");
        $st3->execute(array_merge([$year], array_keys($ids)));
        foreach ($st3->fetchAll() as $r) {
            $kid = (string)$r['kpi_id'];
            if (!isset($actuals->$kid)) {
                $actuals->$kid = new stdClass();
            }
            $mo = (string)(int)$r['mo'];
            $actuals->$kid->$mo = [
                'v'      => $r['val'] === null ? null : (float)$r['val'],
                'status' => $r['status'],
                'note'   => $r['note'],
                'at'     => substr((string)$r['updated_at'], 0, 10),
            ];
        }
    }

    return [
        'meId'      => (string)$viewer['id'],
        // Divisi atau unit yang dikelola Pengelola KPI (id kepalanya), selain itu null.
        'areaId'    => ($area = kpi_role_area($viewer)) !== null ? (string)$area : null,
        'company'   => (string)kpi_setting('company', 'Perusahaan'),
        'cap'       => (float)kpi_setting('cap', 120),
        'year'      => $year,
        'demoMode'  => kpi_setting('demo_mode', '0') === '1',
        'users'     => $users,
        'kpis'      => array_merge($kpis, $extra),
        'actuals'   => $actuals,
    ];
}

/* ---------- Aksi ---------- */

/* ---------- Kelola anggota (dipakai API dan halaman admin) ---------- */

/**
 * Satu password yang sama untuk semua akun anggota (dari sysAdmin > Anggota).
 * Akun contoh di halaman masuk ikut memakai password baru. Mengembalikan jumlah akun.
 */
function kpi_user_password_all(string $pass): int
{
    $db = db();
    $db->prepare('UPDATE users SET password_hash = ?')->execute([password_hash($pass, PASSWORD_DEFAULT)]);
    $list = json_decode((string)kpi_setting('demo_accounts', '[]'), true);
    if (is_array($list) && $list) {
        foreach ($list as &$a) {
            if (is_array($a) && isset($a['password'])) {
                $a['password'] = $pass;
            }
        }
        unset($a);
        kpi_set_setting('demo_accounts', json_encode($list, JSON_UNESCAPED_UNICODE));
    }
    return (int)$db->query('SELECT COUNT(*) FROM users')->fetchColumn();
}

/**
 * Menambah atau mengubah anggota. Mengembalikan [id, username].
 * $withRole: role ikut disimpan (hanya dari halaman sysAdmin); bila isian role tidak
 * dikirim, role yang lama tetap. Role CEO tidak diubah karena CEO sudah punya semua hak.
 */
function kpi_user_save(array $in, bool $withRole = false): array
{
    $db    = db();
    $id    = in_id($in, 'id', false);
    $name  = in_str($in, 'name', 120);
    $title = in_str($in, 'title', 120);
    $dept  = in_str($in, 'dept', 120);
    $pass  = (string)($in['password'] ?? '');
    if ($name === '') {
        throw new KpiApiError('Nama wajib diisi.');
    }
    $username = kpi_username_slug(in_str($in, 'username', 64) ?: $name);
    if ($username === '') {
        throw new KpiApiError('Username wajib diisi dengan huruf atau angka.');
    }
    $chk = $db->prepare('SELECT id FROM users WHERE username = ? AND id <> ?');
    $chk->execute([$username, $id ?: 0]);
    if ($chk->fetchColumn()) {
        throw new KpiApiError('Username "{0}" sudah dipakai. Gunakan username lain.', 400, [$username]);
    }
    $users = kpi_all_users();
    $role  = null;
    $scope = null;
    if ($withRole && array_key_exists('role', $in) && $in['role'] !== null) {
        $role = (string)$in['role'];
        if (!isset(KPI_ROLES[$role])) {
            throw new KpiApiError('Role tidak dikenal.');
        }
        // Pengelola KPI: divisi atau unit yang dikelola. Kosong berarti divisinya sendiri.
        $raw = trim((string)($in['roleScope'] ?? ''));
        if ($role === 'pengelola' && $raw !== '') {
            $scope = ctype_digit($raw) ? (int)$raw : 0;
            if (!kpi_area_valid($scope)) {
                throw new KpiApiError('Divisi atau unit yang dikelola tidak ditemukan.');
            }
        }
    }

    if ($id) {
        if (!isset($users[$id])) {
            throw new KpiApiError('Anggota tidak ditemukan.', 404);
        }
        $cur      = $users[$id];
        $parentId = in_id($in, 'parentId', false);
        if ($parentId !== null && $cur['parent_id'] !== null && $parentId !== (int)$cur['parent_id']) {
            if (!isset($users[$parentId])) {
                throw new KpiApiError('Atasan tidak ditemukan.', 404);
            }
            if ($users[$parentId]['level'] !== $users[(int)$cur['parent_id']]['level']) {
                throw new KpiApiError('Atasan baru harus berada di level yang sama dengan atasan sebelumnya.');
            }
            if ($parentId === $id || kpi_is_ancestor($id, $parentId)) {
                throw new KpiApiError('Atasan tidak boleh berasal dari bawahannya sendiri.');
            }
            // KPI yang terhubung ke atasan lama dilepas.
            $db->prepare('UPDATE kpis SET parent_id = NULL WHERE owner_id = ?')->execute([$id]);
            $db->prepare('UPDATE users SET parent_id = ? WHERE id = ?')->execute([$parentId, $id]);
        }
        $up = $db->prepare('UPDATE users SET name=?, title=?, dept=?, username=? WHERE id=?');
        $up->execute([$name, $title, $dept, $username, $id]);
        if ($role !== null && $cur['level'] !== 'ceo') {
            $db->prepare('UPDATE users SET role = ?, role_scope = ? WHERE id = ?')->execute([$role, $scope, $id]);
        }
        if ($pass !== '') {
            if (mb_strlen($pass) < 4) {
                throw new KpiApiError('Password minimal 4 karakter.');
            }
            $db->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
               ->execute([password_hash($pass, PASSWORD_DEFAULT), $id]);
        }
    } else {
        $parentId = in_id($in, 'parentId');
        if (!isset($users[$parentId])) {
            throw new KpiApiError('Pilih atasan terlebih dahulu.');
        }
        $level = KPI_CHILD_LEVEL[$users[$parentId]['level']] ?? null;
        if ($level === null) {
            throw new KpiApiError('Staff tidak dapat memiliki bawahan.');
        }
        if (mb_strlen($pass) < 4) {
            throw new KpiApiError('Password minimal 4 karakter.');
        }
        $up = $db->prepare('INSERT INTO users (name, title, level, role, role_scope, parent_id, dept, username, password_hash) VALUES (?,?,?,?,?,?,?,?,?)');
        $up->execute([
            $name,
            $title ?: ucfirst($level),
            $level,
            $role ?? 'anggota',
            $scope,
            $parentId,
            $dept ?: $users[$parentId]['dept'],
            $username,
            password_hash($pass, PASSWORD_DEFAULT),
        ]);
        $id = (int)$db->lastInsertId();
    }
    kpi_all_users(true);
    return [$id, $username];
}

/** Menghapus anggota yang tidak punya bawahan. */
function kpi_user_delete(int $id): void
{
    $users = kpi_all_users();
    if (!isset($users[$id])) {
        throw new KpiApiError('Anggota tidak ditemukan.', 404);
    }
    foreach ($users as $u) {
        if ((int)($u['parent_id'] ?? 0) === $id) {
            throw new KpiApiError('Pindahkan dulu bawahannya ke atasan lain sebelum menghapus.');
        }
    }
    db()->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
    // Pengelola KPI yang mengelola wilayah orang ini kembali ke divisinya sendiri.
    db()->prepare('UPDATE users SET role_scope = NULL WHERE role_scope = ?')->execute([$id]);
    kpi_all_users(true);
}

function kpi_handle(string $action, array $in): array
{
    switch ($action) {

        case 'login': {
            $user = kpi_login(in_str($in, 'username', 64), (string)($in['password'] ?? ''));
            return ['data' => kpi_bootstrap($user), 'csrf' => kpi_csrf_token()];
        }

        case 'logout': {
            $u = kpi_current_user();
            if ($u) {
                kpi_log((string)$u['id'], 'logout', $u['username']);
            }
            kpi_logout();
            return ['ok' => true];
        }

        case 'bootstrap': {
            return ['data' => kpi_bootstrap(kpi_require_user())];
        }

        case 'save_actuals': {
            $me    = kpi_require_user();
            $year  = (int)(in_num($in, 'year', kpi_year()));
            $month = (int)(in_num($in, 'month', 0));
            $mode  = in_str($in, 'mode', 12);
            if ($year !== kpi_year()) {
                throw new KpiApiError('Periode tahun tidak sesuai dengan pengaturan.');
            }
            if ($month < 0 || $month > 11) {
                throw new KpiApiError('Bulan tidak valid.');
            }
            if (!in_array($mode, ['draft', 'submitted'], true)) {
                throw new KpiApiError('Mode penyimpanan tidak valid.');
            }
            $items = $in['items'] ?? [];
            if (!is_array($items)) {
                throw new KpiApiError('Daftar realisasi tidak valid.');
            }
            $isCeo    = $me['level'] === 'ceo';
            $status   = $isCeo ? 'approved' : $mode;
            $db       = db();
            $saved    = 0;

            $db->beginTransaction();
            try {
                foreach ($items as $item) {
                    if (!is_array($item)) {
                        continue;
                    }
                    $kid = in_id($item, 'kpiId');
                    $st  = $db->prepare('SELECT * FROM kpis WHERE id = ?');
                    $st->execute([$kid]);
                    $kpi = $st->fetch();
                    if (!$kpi || (int)$kpi['owner_id'] !== (int)$me['id']) {
                        throw new KpiApiError('Anda hanya dapat mengisi realisasi KPI milik sendiri.', 403);
                    }
                    if ($kpi['rollup'] !== 'manual') {
                        continue; // dihitung otomatis dari KPI turunan
                    }
                    $cur = $db->prepare('SELECT * FROM actuals WHERE kpi_id = ? AND yr = ? AND mo = ?');
                    $cur->execute([$kid, $year, $month]);
                    $exist = $cur->fetch();
                    if ($exist && $exist['status'] === 'approved' && !$isCeo) {
                        continue; // sudah disetujui, tidak bisa diubah sendiri
                    }
                    $val = array_key_exists('value', $item) ? in_num($item, 'value', null) : null;
                    if ($val === null) {
                        if ($exist) {
                            $del = $db->prepare('DELETE FROM actuals WHERE id = ?');
                            $del->execute([$exist['id']]);
                        }
                        continue;
                    }
                    if ($val < 0) {
                        throw new KpiApiError('Realisasi tidak boleh bernilai negatif.');
                    }
                    $up = $db->prepare('INSERT INTO actuals (kpi_id, yr, mo, val, status, note, submitted_by, approved_by)
                        VALUES (?, ?, ?, ?, ?, "", ?, ?)
                        ON DUPLICATE KEY UPDATE val = VALUES(val), status = VALUES(status), note = "",
                            submitted_by = VALUES(submitted_by), approved_by = VALUES(approved_by)');
                    $up->execute([$kid, $year, $month, $val, $status, (int)$me['id'], $isCeo ? (int)$me['id'] : null]);
                    $saved++;
                }
                $db->commit();
            } catch (Throwable $e) {
                $db->rollBack();
                throw $e;
            }
            kpi_log((string)$me['id'], 'save_actuals', "bulan=$month status=$status jumlah=$saved");
            return ['data' => kpi_bootstrap($me), 'saved' => $saved];
        }

        case 'approve':
        case 'reject': {
            $me    = kpi_require_user();
            $kid   = in_id($in, 'kpiId');
            $month = (int)(in_num($in, 'month', -1));
            $year  = (int)(in_num($in, 'year', kpi_year()));
            $st = db()->prepare('SELECT * FROM kpis WHERE id = ?');
            $st->execute([$kid]);
            $kpi = $st->fetch();
            if (!$kpi) {
                throw new KpiApiError('KPI tidak ditemukan.', 404);
            }
            if (!kpi_can_approve($me, (int)$kpi['owner_id'])) {
                throw new KpiApiError('Anda hanya dapat menyetujui realisasi bawahan langsung Anda.', 403);
            }
            $cur = db()->prepare('SELECT * FROM actuals WHERE kpi_id = ? AND yr = ? AND mo = ?');
            $cur->execute([$kid, $year, $month]);
            $row = $cur->fetch();
            if (!$row || $row['status'] !== 'submitted') {
                throw new KpiApiError('Realisasi ini tidak sedang menunggu persetujuan.');
            }
            if ($action === 'approve') {
                $up = db()->prepare('UPDATE actuals SET status = "approved", note = "", approved_by = ? WHERE id = ?');
                $up->execute([(int)$me['id'], $row['id']]);
            } else {
                $note = in_str($in, 'note', 500) ?: kpi_t('Mohon diperiksa kembali.');
                $up = db()->prepare('UPDATE actuals SET status = "rejected", note = ?, approved_by = ? WHERE id = ?');
                $up->execute([$note, (int)$me['id'], $row['id']]);
            }
            kpi_log((string)$me['id'], $action, "kpi=$kid bulan=$month");
            return ['data' => kpi_bootstrap($me)];
        }

        case 'approve_all': {
            $me  = kpi_require_user();
            $uid = in_id($in, 'userId');
            if (!kpi_can_approve($me, $uid)) {
                throw new KpiApiError('Anda hanya dapat menyetujui realisasi bawahan langsung Anda.', 403);
            }
            $up = db()->prepare('UPDATE actuals a JOIN kpis k ON k.id = a.kpi_id
                SET a.status = "approved", a.note = "", a.approved_by = ?
                WHERE k.owner_id = ? AND a.yr = ? AND a.status = "submitted"');
            $up->execute([(int)$me['id'], $uid, kpi_year()]);
            kpi_log((string)$me['id'], 'approve_all', "user=$uid jumlah=" . $up->rowCount());
            return ['data' => kpi_bootstrap($me), 'saved' => $up->rowCount()];
        }

        case 'kpi_save': {
            $me  = kpi_require_user();
            $id  = in_id($in, 'id', false);
            $db  = db();
            if ($id) {
                $st = $db->prepare('SELECT * FROM kpis WHERE id = ?');
                $st->execute([$id]);
                $cur = $st->fetch();
                if (!$cur) {
                    throw new KpiApiError('KPI tidak ditemukan.', 404);
                }
                $ownerId = (int)$cur['owner_id'];
            } else {
                $ownerId = in_id($in, 'ownerId');
            }
            if (!kpi_can_define($me, $ownerId)) {
                throw new KpiApiError('Anda tidak berhak mengubah KPI pengguna ini.', 403);
            }
            $users = kpi_all_users();
            if (!isset($users[$ownerId])) {
                throw new KpiApiError('Pemilik KPI tidak ditemukan.', 404);
            }

            $name   = in_str($in, 'name', 160);
            $target = in_num($in, 'target');
            $weight = in_num($in, 'weight', 0);
            $rollup = in_str($in, 'rollup', 8) ?: 'manual';
            $persp  = in_str($in, 'persp', 10) ?: 'fin';
            $pol    = in_str($in, 'polarity', 4) ?: 'max';
            if ($name === '') {
                throw new KpiApiError('Nama KPI wajib diisi.');
            }
            if ($target === null || $target <= 0) {
                throw new KpiApiError('Target harus angka lebih dari 0.');
            }
            if ($weight === null || $weight < 0 || $weight > 100) {
                throw new KpiApiError('Bobot harus antara 0 dan 100.');
            }
            if (!in_array($rollup, ['manual', 'sum', 'avg'], true)) {
                throw new KpiApiError('Cara pengisian realisasi tidak valid.');
            }
            if (!in_array($pol, ['max', 'min'], true)) {
                throw new KpiApiError('Arah penilaian tidak valid.');
            }
            if (!array_key_exists($persp, ['fin' => 1, 'cus' => 1, 'ops' => 1, 'lrn' => 1])) {
                throw new KpiApiError('Perspektif tidak valid.');
            }

            // KPI induk harus milik atasan langsung pemilik KPI ini.
            $parentId = in_id($in, 'parentId', false);
            if ($parentId !== null) {
                $supId = (int)($users[$ownerId]['parent_id'] ?? 0);
                $st = $db->prepare('SELECT owner_id FROM kpis WHERE id = ?');
                $st->execute([$parentId]);
                $powner = $st->fetchColumn();
                if ($powner === false || (int)$powner !== $supId) {
                    throw new KpiApiError('KPI induk harus milik atasan langsung pemilik KPI ini.');
                }
                if ($id && $parentId === $id) {
                    throw new KpiApiError('KPI tidak boleh menjadi induk dirinya sendiri.');
                }
            }

            $unit = in_str($in, 'unit', 40) ?: '-';
            $desc = in_str($in, 'desc', 2000);

            if ($id) {
                $up = $db->prepare('UPDATE kpis SET name=?, persp=?, unit=?, target=?, polarity=?, weight=?, parent_id=?, rollup=?, descr=? WHERE id=?');
                $up->execute([$name, $persp, $unit, $target, $pol, $weight, $parentId, $rollup, $desc, $id]);
            } else {
                $ord = (int)$db->query('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM kpis WHERE owner_id = ' . (int)$ownerId)->fetchColumn();
                $up = $db->prepare('INSERT INTO kpis (owner_id, name, persp, unit, target, polarity, weight, parent_id, rollup, descr, sort_order)
                    VALUES (?,?,?,?,?,?,?,?,?,?,?)');
                $up->execute([$ownerId, $name, $persp, $unit, $target, $pol, $weight, $parentId, $rollup, $desc, $ord]);
                $id = (int)$db->lastInsertId();
            }
            kpi_log((string)$me['id'], 'kpi_save', "kpi=$id owner=$ownerId");
            return ['data' => kpi_bootstrap($me)];
        }

        case 'kpi_delete': {
            $me = kpi_require_user();
            $id = in_id($in, 'id');
            $st = db()->prepare('SELECT * FROM kpis WHERE id = ?');
            $st->execute([$id]);
            $kpi = $st->fetch();
            if (!$kpi) {
                throw new KpiApiError('KPI tidak ditemukan.', 404);
            }
            if (!kpi_can_define($me, (int)$kpi['owner_id'])) {
                throw new KpiApiError('Anda tidak berhak menghapus KPI ini.', 403);
            }
            $del = db()->prepare('DELETE FROM kpis WHERE id = ?');
            $del->execute([$id]);
            kpi_log((string)$me['id'], 'kpi_delete', "kpi=$id");
            return ['data' => kpi_bootstrap($me)];
        }

        case 'user_save': {
            $me = kpi_require_user();
            kpi_require_manager($me);
            $target = in_id($in, 'id', false);
            $users  = kpi_all_users();
            if ($target && isset($users[$target]) && $users[$target]['level'] === 'ceo' && $me['level'] !== 'ceo') {
                throw new KpiApiError('Data CEO hanya bisa diubah oleh CEO sendiri.', 403);
            }
            [$id, $username] = kpi_user_save($in);
            kpi_log((string)$me['id'], 'user_save', "user=$id username=$username");
            return ['data' => kpi_bootstrap($me)];
        }

        case 'user_delete': {
            $me = kpi_require_user();
            kpi_require_manager($me);
            $id = in_id($in, 'id');
            if ($id === (int)$me['id']) {
                throw new KpiApiError('Anda tidak dapat menghapus akun Anda sendiri.');
            }
            kpi_user_delete($id);
            kpi_log((string)$me['id'], 'user_delete', "user=$id");
            return ['data' => kpi_bootstrap($me)];
        }

        case 'change_password': {
            $me  = kpi_require_user();
            $old = (string)($in['current'] ?? '');
            $new = (string)($in['password'] ?? '');
            if (!password_verify($old, $me['password_hash'])) {
                throw new KpiApiError('Password lama tidak cocok.');
            }
            if (mb_strlen($new) < 4) {
                throw new KpiApiError('Password baru minimal 4 karakter.');
            }
            db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
                ->execute([password_hash($new, PASSWORD_DEFAULT), (int)$me['id']]);
            kpi_log((string)$me['id'], 'change_password', '');
            return ['ok' => true];
        }

        case 'settings_save': {
            $me = kpi_require_user();
            kpi_require_manager($me);
            $company = in_str($in, 'company', 120);
            $cap     = in_num($in, 'cap', 120);
            $year    = (int)in_num($in, 'year', kpi_year());
            if ($company !== '') {
                kpi_set_setting('company', $company);
            }
            if ($cap !== null && $cap >= 100 && $cap <= 200) {
                kpi_set_setting('cap', $cap);
            }
            if ($year >= 2000 && $year <= 2100) {
                kpi_set_setting('year', $year);
            }
            if (array_key_exists('demoMode', $in)) {
                kpi_set_setting('demo_mode', !empty($in['demoMode']) ? '1' : '0');
            }
            kpi_log((string)$me['id'], 'settings_save', '');
            return ['data' => kpi_bootstrap($me)];
        }

        case 'export': {
            $me = kpi_require_user();
            return ['data' => kpi_bootstrap($me), 'exportedAt' => date('c')];
        }
    }

    throw new KpiApiError('Aksi tidak dikenal.', 404);
}
