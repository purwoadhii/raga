<?php
/**
 * Tampilan halaman admin. Semua keluaran dibuat di server tanpa JavaScript
 * dan tanpa gaya sebaris, supaya aturan Content-Security-Policy bisa ketat.
 */

const ADM_LEVEL_NAMES = ['ceo' => 'CEO', 'div' => 'Division Head', 'unit' => 'Unit Head', 'staff' => 'Staff'];

const ADM_ACTION_NAMES = [
    'login'                => 'Masuk aplikasi',
    'logout'               => 'Keluar aplikasi',
    'save_actuals'         => 'Mengisi realisasi',
    'approve'              => 'Menyetujui realisasi',
    'reject'               => 'Mengembalikan realisasi',
    'approve_all'          => 'Menyetujui semua realisasi',
    'kpi_save'             => 'Menyimpan KPI',
    'kpi_delete'           => 'Menghapus KPI',
    'user_save'            => 'Menyimpan anggota',
    'user_delete'          => 'Menghapus anggota',
    'user_aktif'           => 'Mengaktifkan anggota',
    'user_nonaktif'        => 'Menonaktifkan anggota',
    'buka_kunci'           => 'Membuka kunci login',
    'change_password'      => 'Mengganti password',
    'settings_save'        => 'Mengubah pengaturan',
    'admin_awal'           => 'Membuat admin pertama',
    'admin_login'          => 'Admin masuk',
    'admin_login_gagal'    => 'Admin gagal masuk',
    'admin_logout'         => 'Admin keluar',
    'admin_ganti_password' => 'Admin mengganti password',
    'admin_save'           => 'Menyimpan akun admin',
    'admin_aktif'          => 'Mengaktifkan akun admin',
    'admin_nonaktif'       => 'Menonaktifkan akun admin',
    'admin_delete'         => 'Menghapus akun admin',
    'pemeliharaan_aktif'   => 'Menyalakan mode pemeliharaan',
    'pemeliharaan_mati'    => 'Mematikan mode pemeliharaan',
    'catatan_dibersihkan'  => 'Membersihkan catatan',
    'cadangan_diunduh'     => 'Mengunduh cadangan',
];

function h($s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES);
}

function adm_csrf_field(string $csrf): string
{
    return '<input type="hidden" name="_csrf" value="' . h($csrf) . '">';
}

function adm_flash_html(?array $flash): string
{
    if (!$flash || empty($flash['msg'])) {
        return '';
    }
    $err = ($flash['type'] ?? '') === 'err';
    return '<div class="flash ' . ($err ? 'err' : 'ok') . '" role="' . ($err ? 'alert' : 'status') . '">' . h($flash['msg']) . '</div>';
}

function adm_head(string $title): string
{
    $ver = (string)@filemtime(KPI_ROOT . '/assets/admin.css');
    return '<!doctype html><html lang="id"><head><meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">'
        . '<meta name="robots" content="noindex, nofollow">'
        . '<title>' . h($title) . '</title>'
        . '<link rel="icon" href="assets/logo.png">'
        . '<link rel="preconnect" href="https://fonts.googleapis.com">'
        . '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>'
        . '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Sora:wght@600;700&display=swap">'
        . '<link rel="stylesheet" href="assets/admin.css?v=' . h($ver) . '">'
        . '</head>';
}

/* ---------- Kerangka halaman ---------- */

/** Judul halaman: nama peran pada link khusus, atau Admin Panel pada link umum. */
function adm_title(): string
{
    return KPI_ADMIN_GATE !== null ? KPI_ADMIN_ROLES[KPI_ADMIN_GATE] : 'Admin Panel';
}

/** Tautan ke halaman masuk peran lain, di bawah formulir masuk. */
function adm_other_links(): string
{
    $out = '';
    foreach (KPI_ADMIN_LINKS as $role => $file) {
        if ($role !== KPI_ADMIN_GATE) {
            $out .= '<a href="' . h($file) . '">Masuk sebagai ' . h(KPI_ADMIN_ROLES[$role]) . '</a> · ';
        }
    }
    return $out;
}

function adm_page_guest(string $body, ?array $flash, bool $wide): string
{
    return adm_head(adm_title() . ' · Key Performance Indicator')
        . '<body><main class="guest' . ($wide ? ' wide' : '') . '">'
        . '<div class="guest-brand"><img src="assets/logo.png" alt="" width="52" height="52">'
        . '<div><span class="eyebrow">Key Performance Indicator</span><h1>' . h(adm_title()) . '</h1></div></div>'
        . adm_flash_html($flash) . $body
        . '<p class="guest-foot">' . adm_other_links() . '<a href="index.php">Kembali ke aplikasi KPI</a></p>'
        . '</main></body></html>';
}

function adm_page(array $admin, array $pages, string $page, string $body, ?array $flash, string $csrf): string
{
    $role = $admin['role'];
    $nav  = '';
    foreach ($pages as $key => $label) {
        $nav .= '<a href="' . ADM_SELF . '?p=' . h($key) . '"' . ($key === $page ? ' class="on" aria-current="page"' : '') . '>' . h($label) . '</a>';
    }
    $maint = kpi_setting('maintenance', '0') === '1'
        ? '<div class="flash warn">Mode pemeliharaan sedang aktif. Aplikasi KPI ditutup sementara untuk semua anggota.</div>'
        : '';
    if (kpi_admin_uses_default_password($admin)) {
        $maint .= '<div class="flash warn">Anda masih memakai password bawaan <b>123456</b>. Siapa pun yang tahu password ini bisa masuk. '
            . '<a href="' . ADM_SELF . '?p=akun">Ganti password sekarang</a>.</div>';
    }

    return adm_head($pages[$page] . ' · ' . adm_title())
        . '<body class="role-' . h($role) . '">'
        . '<header class="top"><a class="brand" href="' . ADM_SELF . '"><img src="assets/logo.png" alt="" width="34" height="34">'
        . '<span><b>' . h(adm_title()) . '</b><small>Key Performance Indicator</small></span></a>'
        . '<div class="who"><span class="role">' . h(KPI_ADMIN_ROLES[$role]) . '</span>'
        . '<span class="who-name">' . h($admin['name']) . '</span>'
        . '<form method="post" action="' . ADM_SELF . '">' . adm_csrf_field($csrf)
        . '<input type="hidden" name="_act" value="logout"><button class="btn ghost" type="submit">Keluar</button></form></div></header>'
        . '<div class="shell"><nav class="nav" aria-label="Menu admin">' . $nav . '</nav>'
        . '<main class="main"><h1>' . h($pages[$page]) . '</h1>' . $maint . adm_flash_html($flash) . $body . '</main></div>'
        . '</body></html>';
}

/* ---------- Masuk dan admin pertama ---------- */

function adm_view_login(string $csrf, array $old): string
{
    return '<form class="card form" method="post" action="' . ADM_SELF . '" autocomplete="off">' . adm_csrf_field($csrf)
        . '<input type="hidden" name="_act" value="login">'
        . '<p class="muted">' . (KPI_ADMIN_GATE !== null
            ? 'Halaman ini khusus untuk akun ' . h(KPI_ADMIN_ROLES[KPI_ADMIN_GATE]) . '. Akun admin berbeda dari akun anggota di aplikasi KPI.'
            : 'Masuk dengan akun sysAdmin atau infraAdmin. Akun ini berbeda dari akun anggota di aplikasi KPI.') . '</p>'
        . adm_input('Username', 'username', $old['username'] ?? '', ['required' => true, 'autofocus' => true, 'autocapitalize' => 'none'])
        . adm_input('Password', 'password', '', ['type' => 'password', 'required' => true])
        . '<button class="btn primary block" type="submit">Masuk</button></form>';
}

function adm_view_first(string $csrf, array $old): string
{
    return '<form class="card form" method="post" action="' . ADM_SELF . '" autocomplete="off">' . adm_csrf_field($csrf)
        . '<input type="hidden" name="_act" value="first_admin">'
        . '<div><h2>Buat akun admin pertama</h2>'
        . '<p class="muted">Belum ada akun admin. Untuk membuktikan bahwa Anda berhak, isi dulu username dan password CEO di aplikasi KPI.</p></div>'

        . '<fieldset><legend>1. Bukti dari CEO</legend><div class="grid2">'
        . adm_input('Username CEO', 'ceo_user', $old['ceo_user'] ?? '', ['required' => true, 'autocapitalize' => 'none'])
        . adm_input('Password CEO', 'ceo_pass', '', ['type' => 'password', 'required' => true])
        . '</div></fieldset>'

        . '<fieldset><legend>2. Akun sysAdmin</legend>'
        . '<p class="muted small">Mengurus isi aplikasi: anggota, pengaturan, dan akun admin lain.</p><div class="grid2">'
        . adm_input('Nama', 'sys_name', $old['sys_name'] ?? 'System Administrator', [])
        . adm_input('Username', 'sys_user', $old['sys_user'] ?? 'sysadmin', ['required' => true, 'autocapitalize' => 'none'])
        . adm_input('Password (minimal ' . KPI_ADMIN_MIN_PASS . ' karakter)', 'sys_pass', '', ['type' => 'password', 'required' => true, 'minlength' => KPI_ADMIN_MIN_PASS])
        . '</div></fieldset>'

        . '<fieldset><legend>3. Akun infraAdmin</legend>'
        . '<p class="muted small">Mengurus server: status, cadangan database, dan mode pemeliharaan. Boleh dikosongkan dan dibuat nanti oleh sysAdmin.</p><div class="grid2">'
        . adm_input('Nama', 'inf_name', $old['inf_name'] ?? '', ['placeholder' => 'Infrastructure Administrator'])
        . adm_input('Username', 'inf_user', $old['inf_user'] ?? '', ['placeholder' => 'infraadmin', 'autocapitalize' => 'none'])
        . adm_input('Password (minimal ' . KPI_ADMIN_MIN_PASS . ' karakter)', 'inf_pass', '', ['type' => 'password', 'minlength' => KPI_ADMIN_MIN_PASS])
        . '</div></fieldset>'

        . '<button class="btn primary" type="submit">Buat akun admin</button></form>';
}

/* ---------- Bagian kecil yang dipakai berulang ---------- */

function adm_input(string $label, string $name, $value, array $o): string
{
    $attr = '';
    foreach (['required', 'autofocus'] as $flag) {
        if (!empty($o[$flag])) {
            $attr .= ' ' . $flag;
        }
    }
    foreach (['placeholder', 'minlength', 'maxlength', 'min', 'max', 'step', 'autocapitalize', 'inputmode'] as $k) {
        if (isset($o[$k])) {
            $attr .= ' ' . $k . '="' . h($o[$k]) . '"';
        }
    }
    $type = $o['type'] ?? 'text';
    if ($type === 'password') {
        $attr .= ' autocomplete="new-password"';
    } else {
        $attr .= ' autocomplete="off" spellcheck="false"';
    }
    $hint = isset($o['hint']) ? '<span class="hint">' . h($o['hint']) . '</span>' : '';
    return '<label class="field"><span>' . h($label) . '</span>'
        . '<input type="' . h($type) . '" name="' . h($name) . '" value="' . h($value) . '"' . $attr . '>' . $hint . '</label>';
}

function adm_select(string $label, string $name, array $options, $value, string $hint = ''): string
{
    $html = '<label class="field"><span>' . h($label) . '</span><select name="' . h($name) . '">';
    foreach ($options as $val => $text) {
        $html .= '<option value="' . h($val) . '"' . ((string)$val === (string)$value ? ' selected' : '') . '>' . h($text) . '</option>';
    }
    return $html . '</select>' . ($hint !== '' ? '<span class="hint">' . h($hint) . '</span>' : '') . '</label>';
}

function adm_pill(string $text, string $kind): string
{
    return '<span class="pill ' . h($kind) . '">' . h($text) . '</span>';
}

function adm_stat(string $label, $value, string $note = '', string $kind = ''): string
{
    return '<div class="stat' . ($kind !== '' ? ' ' . h($kind) : '') . '"><span class="stat-l">' . h($label) . '</span>'
        . '<b class="stat-v">' . h($value) . '</b>' . ($note !== '' ? '<span class="stat-n">' . h($note) . '</span>' : '') . '</div>';
}

function adm_when(?string $ts): string
{
    if (!$ts) {
        return '-';
    }
    $t = strtotime($ts);
    return $t ? date('d/m/Y H:i', $t) : h($ts);
}

/** Tombol yang meminta penegasan lewat <details>, tanpa JavaScript. */
function adm_confirm(string $csrf, string $summary, string $question, string $act, array $fields, string $button, string $kind = 'danger'): string
{
    $hidden = '';
    foreach ($fields as $k => $v) {
        $hidden .= '<input type="hidden" name="' . h($k) . '" value="' . h($v) . '">';
    }
    return '<details class="confirm"><summary class="btn ' . h($kind) . '-soft">' . h($summary) . '</summary>'
        . '<form method="post" action="' . ADM_SELF . '" class="confirm-box">' . adm_csrf_field($csrf)
        . '<input type="hidden" name="_act" value="' . h($act) . '">' . $hidden
        . '<p>' . h($question) . '</p><button class="btn ' . h($kind) . '" type="submit">' . h($button) . '</button></form></details>';
}

/* ---------- sysAdmin ---------- */

function adm_view_overview(array $ctx): string
{
    $o  = kpi_overview();
    $lv = $o['levels'];
    $html = '<p class="lead">Anda masuk sebagai <b>sysAdmin</b>. Dari sini Anda mengurus anggota, pengaturan aplikasi, dan akun admin.</p>'
        . '<div class="stats">'
        . adm_stat('Anggota', $o['users'], $lv['ceo'] . ' CEO, ' . $lv['div'] . ' Division Head, ' . $lv['unit'] . ' Unit Head, ' . $lv['staff'] . ' Staff')
        . adm_stat('KPI', $o['kpis'], $o['no_kpi'] ? $o['no_kpi'] . ' anggota belum punya KPI' : 'Semua anggota sudah punya KPI', $o['no_kpi'] ? 'warn' : '')
        . adm_stat('Menunggu persetujuan', $o['pending'], 'Realisasi yang diajukan dan belum diputuskan atasan')
        . adm_stat('Anggota nonaktif', $o['inactive'], 'Tidak bisa masuk ke aplikasi')
        . adm_stat('Login terkunci', $o['locked'], $o['locked'] ? 'Bisa dibuka di menu Anggota' : 'Tidak ada', $o['locked'] ? 'warn' : '')
        . adm_stat('Admin aktif', $o['admins'], 'sysAdmin dan infraAdmin')
        . '</div>';

    $rows = '';
    foreach (kpi_activity(8) as $a) {
        $rows .= '<tr><td class="nowrap">' . adm_when($a['created_at']) . '</td><td>' . h($a['user_name'] ?? 'Admin atau sistem') . '</td>'
            . '<td>' . h(ADM_ACTION_NAMES[$a['action']] ?? $a['action']) . '</td></tr>';
    }
    $html .= '<section class="card"><div class="card-h"><h2>Kegiatan terakhir</h2><a class="btn" href="' . ADM_SELF . '?p=aktivitas">Lihat semua</a></div>'
        . '<div class="tbl-wrap"><table><thead><tr><th>Waktu</th><th>Pelaku</th><th>Kegiatan</th></tr></thead><tbody>'
        . ($rows ?: '<tr><td colspan="3" class="muted">Belum ada kegiatan.</td></tr>') . '</tbody></table></div></section>';
    return $html;
}

function adm_view_users(array $ctx): string
{
    $users = kpi_all_users(true);
    $csrf  = $ctx['csrf'];
    $get   = $ctx['get'];

    if (!empty($get['new'])) {
        return adm_user_form($ctx, $users, null);
    }
    if (isset($get['edit']) && isset($users[(int)$get['edit']])) {
        return adm_user_form($ctx, $users, $users[(int)$get['edit']]);
    }

    $q    = mb_strtolower(trim((string)($get['q'] ?? '')));
    $rows = '';
    $n    = 0;
    $order = array_flip(array_keys(ADM_LEVEL_NAMES));
    uasort($users, fn($a, $b) => [$order[$a['level']] ?? 9, $a['dept'], $a['name']] <=> [$order[$b['level']] ?? 9, $b['dept'], $b['name']]);
    foreach ($users as $u) {
        if ($q !== '' && mb_strpos(mb_strtolower($u['name'] . ' ' . $u['username'] . ' ' . $u['dept'] . ' ' . $u['title']), $q) === false) {
            continue;
        }
        $n++;
        $boss = $u['parent_id'] !== null && isset($users[(int)$u['parent_id']]) ? $users[(int)$u['parent_id']]['name'] : '-';
        $rows .= '<tr><td><b>' . h($u['name']) . '</b><div class="muted small">' . h($u['title']) . '</div></td>'
            . '<td>' . h(ADM_LEVEL_NAMES[$u['level']] ?? $u['level']) . '</td>'
            . '<td>' . h($u['dept']) . '</td><td>' . h($boss) . '</td>'
            . '<td><code>' . h($u['username']) . '</code></td>'
            . '<td>' . ($u['is_active'] ? adm_pill('Aktif', 'ok') : adm_pill('Nonaktif', 'bad')) . '</td>'
            . '<td class="right"><a class="btn" href="' . ADM_SELF . '?p=anggota&amp;edit=' . (int)$u['id'] . '">Ubah</a></td></tr>';
    }

    $html = '<div class="toolbar"><form method="get" action="' . ADM_SELF . '" class="search"><input type="hidden" name="p" value="anggota">'
        . '<input type="search" name="q" value="' . h($get['q'] ?? '') . '" placeholder="Cari nama, username, atau unit" aria-label="Cari anggota">'
        . '<button class="btn" type="submit">Cari</button></form>'
        . '<a class="btn primary" href="' . ADM_SELF . '?p=anggota&amp;new=1">Tambah anggota</a></div>'
        . '<section class="card"><div class="tbl-wrap"><table><thead><tr><th>Nama</th><th>Level</th><th>Unit</th><th>Atasan</th><th>Username</th><th>Status</th><th></th></tr></thead><tbody>'
        . ($rows ?: '<tr><td colspan="7" class="muted">Tidak ada anggota yang cocok.</td></tr>')
        . '</tbody></table></div><p class="muted small foot">' . $n . ' anggota ditampilkan.</p></section>';

    $locked = kpi_locked_list();
    if ($locked) {
        $lr = '';
        foreach ($locked as $l) {
            [$who, $ip] = array_pad(explode('|', (string)$l['ident'], 2), 2, '');
            $lr .= '<tr><td><code>' . h($who) . '</code></td><td>' . h($ip) . '</td><td class="nowrap">' . adm_when($l['locked_until']) . '</td>'
                . '<td class="right"><form method="post" action="' . ADM_SELF . '">' . adm_csrf_field($csrf)
                . '<input type="hidden" name="_act" value="user_unlock"><input type="hidden" name="ident" value="' . h($l['ident']) . '">'
                . '<button class="btn" type="submit">Buka kunci</button></form></td></tr>';
        }
        $html .= '<section class="card"><div class="card-h"><h2>Login yang terkunci</h2></div>'
            . '<p class="muted">Terkunci 15 menit karena terlalu banyak salah password. Buka kuncinya bila orangnya memang berhak.</p>'
            . '<div class="tbl-wrap"><table><thead><tr><th>Username</th><th>Alamat IP</th><th>Terkunci sampai</th><th></th></tr></thead><tbody>' . $lr . '</tbody></table></div></section>';
    }
    return $html;
}

function adm_user_form(array $ctx, array $users, ?array $u): string
{
    $csrf = $ctx['csrf'];
    $old  = $ctx['old'];
    $val  = fn(string $k, $def = '') => $old[$k] ?? $def;
    $back = '<p><a href="' . ADM_SELF . '?p=anggota">&larr; Kembali ke daftar anggota</a></p>';

    // Pilihan atasan: untuk anggota baru semua yang bukan Staff, untuk yang lama hanya yang selevel dengan atasannya sekarang.
    $parents = [];
    if ($u === null) {
        foreach ($users as $p) {
            if ($p['level'] !== 'staff') {
                $parents[(int)$p['id']] = $p['name'] . ' (' . (ADM_LEVEL_NAMES[$p['level']] ?? $p['level']) . ', ' . $p['dept'] . ')';
            }
        }
    } elseif ($u['parent_id'] !== null && isset($users[(int)$u['parent_id']])) {
        $lvl = $users[(int)$u['parent_id']]['level'];
        foreach ($users as $p) {
            if ($p['level'] === $lvl && (int)$p['id'] !== (int)$u['id']) {
                $parents[(int)$p['id']] = $p['name'] . ' (' . $p['dept'] . ')';
            }
        }
    }

    $form = '<form class="card form" method="post" action="' . ADM_SELF . '" autocomplete="off">' . adm_csrf_field($csrf)
        . '<input type="hidden" name="_act" value="user_save"><input type="hidden" name="_back" value="anggota">'
        . ($u ? '<input type="hidden" name="id" value="' . (int)$u['id'] . '"><input type="hidden" name="_edit" value="' . (int)$u['id'] . '">'
              : '<input type="hidden" name="_new" value="1">')
        . '<h2>' . ($u ? 'Ubah data ' . h($u['name']) : 'Tambah anggota') . '</h2>'
        . ($u ? '<p class="muted">Level: ' . h(ADM_LEVEL_NAMES[$u['level']] ?? $u['level']) . '</p>'
              : '<p class="muted">Level anggota baru mengikuti atasannya: bawahan CEO menjadi Division Head, bawahan Division Head menjadi Unit Head, dan bawahan Unit Head menjadi Staff.</p>')
        . '<div class="grid2">'
        . adm_input('Nama lengkap', 'name', $val('name', $u['name'] ?? ''), ['required' => true, 'maxlength' => 120])
        . adm_input('Jabatan', 'title', $val('title', $u['title'] ?? ''), ['maxlength' => 120])
        . adm_input('Unit atau divisi', 'dept', $val('dept', $u['dept'] ?? ''), ['maxlength' => 120, 'hint' => $u ? '' : 'Bila kosong, mengikuti unit atasannya.'])
        . ($parents ? adm_select('Atasan langsung', 'parentId', $parents, $val('parentId', $u['parent_id'] ?? ''),
                $u ? 'Bila atasan diganti, hubungan KPI ke atasan lama dilepas.' : '') : '')
        . adm_input('Username', 'username', $val('username', $u['username'] ?? ''), ['maxlength' => 64, 'autocapitalize' => 'none', 'hint' => $u ? '' : 'Bila kosong, dibuat dari nama. Contoh: siti.rahma'])
        . adm_input($u ? 'Password baru' : 'Password', 'password', '', ['type' => 'password', 'minlength' => 4, 'required' => !$u,
                'hint' => $u ? 'Kosongkan bila tidak ingin mengganti password.' : 'Minimal 4 karakter.'])
        . '</div><div class="actions"><button class="btn primary" type="submit">Simpan</button><a class="btn" href="' . ADM_SELF . '?p=anggota">Batal</a></div></form>';

    if (!$u) {
        return $back . $form;
    }

    $id   = (int)$u['id'];
    $side = '<section class="card"><h2>Status akun</h2>';
    if ($u['is_active']) {
        $side .= '<p class="muted">Akun ini aktif. Bila dinonaktifkan, orangnya tidak bisa masuk, tetapi data dan KPI-nya tetap tersimpan.</p>'
            . adm_confirm($csrf, 'Nonaktifkan akun', 'Nonaktifkan akun ' . $u['name'] . '?', 'user_toggle', ['id' => $id, 'active' => '0'], 'Ya, nonaktifkan', 'warn');
    } else {
        $side .= '<p class="muted">Akun ini nonaktif dan tidak bisa masuk.</p>'
            . '<form method="post" action="' . ADM_SELF . '">' . adm_csrf_field($csrf)
            . '<input type="hidden" name="_act" value="user_toggle"><input type="hidden" name="id" value="' . $id . '"><input type="hidden" name="active" value="1">'
            . '<button class="btn primary" type="submit">Aktifkan kembali</button></form>';
    }
    $side .= '</section>';

    if ($u['level'] !== 'ceo') {
        $side .= '<section class="card"><h2>Hapus anggota</h2>'
            . '<p class="muted">Menghapus anggota juga menghapus seluruh KPI dan realisasinya, dan tidak bisa dibatalkan. Anggota yang masih punya bawahan tidak bisa dihapus.</p>'
            . adm_confirm($csrf, 'Hapus anggota', 'Hapus ' . $u['name'] . ' beserta seluruh KPI dan realisasinya?', 'user_delete', ['id' => $id, '_back' => 'anggota', '_edit' => $id], 'Ya, hapus permanen')
            . '</section>';
    }
    return $back . $form . $side;
}

function adm_view_settings(array $ctx): string
{
    $old = $ctx['old'];
    $val = fn(string $k, $def) => $old[$k] ?? $def;
    $demo = array_key_exists('company', $old) ? !empty($old['demo_mode']) : kpi_setting('demo_mode', '0') === '1';
    return '<form class="card form" method="post" action="' . ADM_SELF . '">' . adm_csrf_field($ctx['csrf'])
        . '<input type="hidden" name="_act" value="settings_save"><input type="hidden" name="_back" value="pengaturan">'
        . '<div class="grid2">'
        . adm_input('Nama perusahaan', 'company', $val('company', kpi_setting('company', '')), ['required' => true, 'maxlength' => 120])
        . adm_input('Tahun KPI', 'year', $val('year', kpi_setting('year', date('Y'))), ['type' => 'number', 'min' => 2000, 'max' => 2100, 'required' => true,
            'hint' => 'Tahun yang sedang diisi dan dinilai.'])
        . adm_input('Batas capaian per KPI (persen)', 'cap', $val('cap', (float)kpi_setting('cap', 120)), ['type' => 'number', 'min' => 100, 'max' => 200, 'step' => 1, 'required' => true,
            'hint' => 'Capaian di atas angka ini tidak menambah skor. Bawaan 120.'])
        . '</div>'
        . '<label class="check"><input type="checkbox" name="demo_mode" value="1"' . ($demo ? ' checked' : '') . '>'
        . '<span><b>Tampilkan akun contoh di halaman login</b><small>Matikan sebelum aplikasi dipakai sungguhan.</small></span></label>'
        . '<div class="actions"><button class="btn primary" type="submit">Simpan pengaturan</button></div></form>';
}

function adm_view_admins(array $ctx): string
{
    $csrf = $ctx['csrf'];
    $get  = $ctx['get'];
    $me   = (int)$ctx['admin']['id'];
    $all  = kpi_admin_all();

    $edit = null;
    foreach ($all as $a) {
        if (isset($get['edit']) && (int)$a['id'] === (int)$get['edit']) {
            $edit = $a;
        }
    }
    if ($edit || !empty($get['new'])) {
        return adm_admin_form($ctx, $edit);
    }

    $rows = '';
    foreach ($all as $a) {
        $rows .= '<tr><td><b>' . h($a['name']) . '</b>' . ((int)$a['id'] === $me ? ' <span class="muted small">(Anda)</span>' : '') . '</td>'
            . '<td>' . adm_pill(KPI_ADMIN_ROLES[$a['role']] ?? $a['role'], $a['role'] === 'sysadmin' ? 'sys' : 'infra') . '</td>'
            . '<td><code>' . h($a['username']) . '</code></td>'
            . '<td>' . ($a['is_active'] ? adm_pill('Aktif', 'ok') : adm_pill('Nonaktif', 'bad')) . '</td>'
            . '<td class="nowrap">' . adm_when($a['last_login_at']) . '</td>'
            . '<td class="right"><a class="btn" href="' . ADM_SELF . '?p=admin&amp;edit=' . (int)$a['id'] . '">Ubah</a></td></tr>';
    }
    return '<div class="toolbar"><p class="muted">Akun admin terpisah dari akun anggota dan tidak punya KPI.</p>'
        . '<a class="btn primary" href="' . ADM_SELF . '?p=admin&amp;new=1">Tambah admin</a></div>'
        . '<section class="card"><div class="tbl-wrap"><table><thead><tr><th>Nama</th><th>Peran</th><th>Username</th><th>Status</th><th>Terakhir masuk</th><th></th></tr></thead><tbody>'
        . $rows . '</tbody></table></div></section>'
        . '<section class="card"><h2>Perbedaan kedua peran</h2><div class="grid2 roles">'
        . '<div><h3>' . adm_pill('sysAdmin', 'sys') . '</h3><ul><li>Menambah, mengubah, menonaktifkan, dan menghapus anggota</li><li>Mengganti password anggota dan membuka kunci login</li>'
        . '<li>Mengubah pengaturan aplikasi</li><li>Mengelola akun admin</li></ul></div>'
        . '<div><h3>' . adm_pill('infraAdmin', 'infra') . '</h3><ul><li>Memantau status server dan database</li><li>Mengunduh cadangan database</li>'
        . '<li>Menyalakan dan mematikan mode pemeliharaan</li><li>Membersihkan catatan aktivitas lama</li></ul></div></div></section>';
}

function adm_admin_form(array $ctx, ?array $a): string
{
    $csrf = $ctx['csrf'];
    $old  = $ctx['old'];
    $me   = (int)$ctx['admin']['id'];
    $val  = fn(string $k, $def = '') => $old[$k] ?? $def;
    $back = '<p><a href="' . ADM_SELF . '?p=admin">&larr; Kembali ke daftar admin</a></p>';

    $form = '<form class="card form" method="post" action="' . ADM_SELF . '" autocomplete="off">' . adm_csrf_field($csrf)
        . '<input type="hidden" name="_act" value="admin_save"><input type="hidden" name="_back" value="admin">'
        . ($a ? '<input type="hidden" name="id" value="' . (int)$a['id'] . '"><input type="hidden" name="_edit" value="' . (int)$a['id'] . '">'
              : '<input type="hidden" name="_new" value="1">')
        . '<h2>' . ($a ? 'Ubah akun ' . h($a['name']) : 'Tambah admin') . '</h2><div class="grid2">'
        . adm_input('Nama', 'name', $val('name', $a['name'] ?? ''), ['required' => true, 'maxlength' => 120])
        . adm_select('Peran', 'role', KPI_ADMIN_ROLES, $val('role', $a['role'] ?? 'infraadmin'),
            'sysAdmin mengurus isi aplikasi. infraAdmin mengurus server dan cadangan.')
        . adm_input('Username', 'username', $val('username', $a['username'] ?? ''), ['required' => true, 'maxlength' => 64, 'autocapitalize' => 'none'])
        . adm_input($a ? 'Password baru' : 'Password', 'password', '', ['type' => 'password', 'minlength' => KPI_ADMIN_MIN_PASS, 'required' => !$a,
            'hint' => $a ? 'Kosongkan bila tidak ingin mengganti password.' : 'Minimal ' . KPI_ADMIN_MIN_PASS . ' karakter.'])
        . '</div><div class="actions"><button class="btn primary" type="submit">Simpan</button><a class="btn" href="' . ADM_SELF . '?p=admin">Batal</a></div></form>';

    if (!$a || (int)$a['id'] === $me) {
        return $back . $form . ($a ? '<p class="muted small">Akun Anda sendiri tidak bisa dinonaktifkan atau dihapus dari sini.</p>' : '');
    }
    $id   = (int)$a['id'];
    $side = '<section class="card"><h2>Status dan penghapusan</h2><div class="actions">';
    if ($a['is_active']) {
        $side .= adm_confirm($csrf, 'Nonaktifkan', 'Nonaktifkan akun admin ' . $a['username'] . '?', 'admin_toggle', ['id' => $id, 'active' => '0', '_back' => 'admin', '_edit' => $id], 'Ya, nonaktifkan', 'warn');
    } else {
        $side .= '<form method="post" action="' . ADM_SELF . '">' . adm_csrf_field($csrf)
            . '<input type="hidden" name="_act" value="admin_toggle"><input type="hidden" name="id" value="' . $id . '"><input type="hidden" name="active" value="1">'
            . '<button class="btn primary" type="submit">Aktifkan kembali</button></form>';
    }
    $side .= adm_confirm($csrf, 'Hapus akun', 'Hapus akun admin ' . $a['username'] . '? Tindakan ini tidak bisa dibatalkan.', 'admin_delete', ['id' => $id, '_back' => 'admin', '_edit' => $id], 'Ya, hapus')
        . '</div></section>';
    return $back . $form . $side;
}

/* ---------- infraAdmin ---------- */

function adm_view_status(array $ctx): string
{
    $checks = kpi_infra_checks();
    $count  = ['ok' => 0, 'warn' => 0, 'bad' => 0, 'info' => 0];
    $rows   = '';
    $label  = ['ok' => 'Baik', 'warn' => 'Perhatikan', 'bad' => 'Bermasalah', 'info' => 'Info'];
    foreach ($checks as [$name, $value, $state, $hint]) {
        $count[$state]++;
        $rows .= '<tr><td><b>' . h($name) . '</b>' . ($hint !== '' ? '<div class="muted small">' . h($hint) . '</div>' : '') . '</td>'
            . '<td>' . h($value) . '</td><td class="right">' . adm_pill($label[$state], $state) . '</td></tr>';
    }
    $html = '<p class="lead">Anda masuk sebagai <b>infraAdmin</b>. Dari sini Anda memantau server, mengambil cadangan, dan mengatur mode pemeliharaan.</p>'
        . '<div class="stats">'
        . adm_stat('Baik', $count['ok'], 'Pemeriksaan yang lolos')
        . adm_stat('Perlu diperhatikan', $count['warn'], $count['warn'] ? 'Lihat saran di tabel' : 'Tidak ada', $count['warn'] ? 'warn' : '')
        . adm_stat('Bermasalah', $count['bad'], $count['bad'] ? 'Segera perbaiki' : 'Tidak ada', $count['bad'] ? 'bad' : '')
        . '</div>'
        . '<section class="card"><div class="card-h"><h2>Pemeriksaan</h2><a class="btn" href="' . ADM_SELF . '?p=status">Periksa ulang</a></div>'
        . '<div class="tbl-wrap"><table><thead><tr><th>Yang diperiksa</th><th>Hasil</th><th class="right">Keadaan</th></tr></thead><tbody>' . $rows . '</tbody></table></div></section>';

    return $html . adm_tables_card();
}

function adm_tables_card(): string
{
    $names = ['settings' => 'Pengaturan', 'users' => 'Anggota', 'kpis' => 'KPI', 'actuals' => 'Realisasi bulanan',
              'login_attempts' => 'Percobaan login', 'activity' => 'Catatan aktivitas', 'admins' => 'Akun admin'];
    $rows  = '';
    $total = 0.0;
    foreach (kpi_table_stats() as $t) {
        $total += $t['bytes'];
        $rows  .= '<tr><td><b>' . h($names[$t['table']] ?? $t['table']) . '</b> <code>' . h($t['table']) . '</code></td>'
            . '<td class="right num">' . number_format($t['rows'], 0, ',', '.') . '</td><td class="right num">' . h(kpi_bytes($t['bytes'])) . '</td></tr>';
    }
    return '<section class="card"><div class="card-h"><h2>Isi database</h2><span class="muted">Total ' . h(kpi_bytes($total)) . '</span></div>'
        . '<div class="tbl-wrap"><table><thead><tr><th>Tabel</th><th class="right">Baris</th><th class="right">Ukuran</th></tr></thead><tbody>' . $rows . '</tbody></table></div></section>';
}

function adm_view_backup(array $ctx): string
{
    $last = (string)kpi_setting('last_backup_at', '');
    return '<section class="card"><h2>Unduh cadangan</h2>'
        . '<p class="muted">Berkas berisi seluruh isi database dalam bentuk SQL: anggota, KPI, realisasi, pengaturan, dan akun admin. '
        . 'Password tersimpan dalam bentuk acak (hash), tetapi berkas ini tetap rahasia. Simpan di tempat yang aman.</p>'
        . '<p>Cadangan terakhir diunduh: <b>' . ($last !== '' ? h($last) : 'belum pernah') . '</b></p>'
        . '<form method="post" action="' . ADM_SELF . '">' . adm_csrf_field($ctx['csrf'])
        . '<input type="hidden" name="_act" value="backup_sql"><input type="hidden" name="_back" value="cadangan">'
        . '<button class="btn primary" type="submit">Unduh cadangan (.sql)</button></form></section>'
        . '<section class="card"><h2>Cara memulihkan</h2><ol class="steps">'
        . '<li>Nyalakan mode pemeliharaan supaya tidak ada yang mengisi data selama pemulihan.</li>'
        . '<li>Buka phpMyAdmin, pilih database aplikasi, lalu buka menu <b>Import</b>.</li>'
        . '<li>Pilih berkas cadangan <code>.sql</code>, lalu klik <b>Import</b>. Isi database diganti dengan isi cadangan.</li>'
        . '<li>Matikan kembali mode pemeliharaan.</li></ol>'
        . '<p class="muted small">Pemulihan sengaja tidak disediakan sebagai tombol di halaman ini, supaya data tidak tertimpa karena salah klik.</p></section>'
        . adm_tables_card();
}

function adm_view_maintenance(array $ctx): string
{
    $on  = kpi_setting('maintenance', '0') === '1';
    $msg = (string)kpi_setting('maintenance_msg', '');
    return '<section class="card"><h2>Keadaan sekarang: ' . ($on ? adm_pill('Aktif', 'warn') : adm_pill('Mati', 'ok')) . '</h2>'
        . '<p class="muted">Saat mode pemeliharaan aktif, aplikasi KPI menampilkan halaman pemberitahuan dan tidak ada yang bisa masuk atau mengisi data. '
        . 'Halaman admin ini tetap bisa dibuka. Pakai saat memulihkan cadangan atau memperbarui aplikasi.</p>'
        . '<form class="form" method="post" action="' . ADM_SELF . '">' . adm_csrf_field($ctx['csrf'])
        . '<input type="hidden" name="_act" value="maintenance_save"><input type="hidden" name="_back" value="pemeliharaan">'
        . '<label class="check"><input type="checkbox" name="maintenance" value="1"' . ($on ? ' checked' : '') . '>'
        . '<span><b>Tutup aplikasi sementara</b><small>Hilangkan centang untuk membuka aplikasi kembali.</small></span></label>'
        . '<label class="field"><span>Pesan untuk anggota</span><textarea name="message" rows="3" maxlength="300" '
        . 'placeholder="Aplikasi sedang dalam pemeliharaan. Silakan coba lagi beberapa saat lagi.">' . h($msg) . '</textarea>'
        . '<span class="hint">Contoh: Aplikasi sedang diperbarui dan bisa dipakai lagi pukul 13.00.</span></label>'
        . '<div class="actions"><button class="btn primary" type="submit">Simpan</button>'
        . '<a class="btn" href="index.php" target="_blank" rel="noopener">Lihat tampilan aplikasi</a></div></form></section>';
}

/* ---------- Untuk kedua peran ---------- */

function adm_view_activity(array $ctx): string
{
    $rows = '';
    $list = kpi_activity(200);
    foreach ($list as $a) {
        $rows .= '<tr><td class="nowrap">' . adm_when($a['created_at']) . '</td><td>' . h($a['user_name'] ?? 'Admin atau sistem') . '</td>'
            . '<td>' . h(ADM_ACTION_NAMES[$a['action']] ?? $a['action']) . '</td><td class="muted">' . h($a['detail']) . '</td></tr>';
    }
    $html = '<section class="card"><div class="card-h"><h2>200 kegiatan terakhir</h2><span class="muted">' . count($list) . ' baris</span></div>'
        . '<div class="tbl-wrap"><table><thead><tr><th>Waktu</th><th>Pelaku</th><th>Kegiatan</th><th>Rincian</th></tr></thead><tbody>'
        . ($rows ?: '<tr><td colspan="4" class="muted">Belum ada kegiatan.</td></tr>') . '</tbody></table></div></section>';

    if ($ctx['admin']['role'] === 'infraadmin') {
        $html .= '<section class="card"><h2>Bersihkan catatan lama</h2>'
            . '<p class="muted">Catatan aktivitas terus bertambah. Hapus yang sudah lama supaya database tetap ringan. Data KPI tidak terpengaruh.</p>'
            . '<form class="inline" method="post" action="' . ADM_SELF . '">' . adm_csrf_field($ctx['csrf'])
            . '<input type="hidden" name="_act" value="log_purge"><input type="hidden" name="_back" value="aktivitas">'
            . adm_select('Hapus catatan yang lebih lama dari', 'days', [365 => '1 tahun', 180 => '6 bulan', 90 => '3 bulan', 30 => '30 hari'], 365)
            . '<button class="btn danger-soft" type="submit">Hapus catatan lama</button></form></section>';
    }
    return $html;
}

function adm_view_account(array $ctx): string
{
    $a = $ctx['admin'];
    return '<section class="card"><dl class="facts">'
        . '<div><dt>Nama</dt><dd>' . h($a['name']) . '</dd></div>'
        . '<div><dt>Username</dt><dd><code>' . h($a['username']) . '</code></dd></div>'
        . '<div><dt>Peran</dt><dd>' . adm_pill(KPI_ADMIN_ROLES[$a['role']], $a['role'] === 'sysadmin' ? 'sys' : 'infra') . '</dd></div>'
        . '<div><dt>Terakhir masuk</dt><dd>' . adm_when($a['last_login_at']) . '</dd></div></dl></section>'
        . '<form class="card form" method="post" action="' . ADM_SELF . '" autocomplete="off">' . adm_csrf_field($ctx['csrf'])
        . '<input type="hidden" name="_act" value="password_change"><input type="hidden" name="_back" value="akun">'
        . '<h2>Ganti password</h2><div class="grid2">'
        . adm_input('Password saat ini', 'current', '', ['type' => 'password', 'required' => true])
        . '<span></span>'
        . adm_input('Password baru', 'password', '', ['type' => 'password', 'required' => true, 'minlength' => KPI_ADMIN_MIN_PASS, 'hint' => 'Minimal ' . KPI_ADMIN_MIN_PASS . ' karakter.'])
        . adm_input('Ulangi password baru', 'password2', '', ['type' => 'password', 'required' => true, 'minlength' => KPI_ADMIN_MIN_PASS])
        . '</div><div class="actions"><button class="btn primary" type="submit">Ganti password</button></div></form>';
}
