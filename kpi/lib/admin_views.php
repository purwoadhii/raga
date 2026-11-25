<?php
/**
 * Tampilan halaman admin. Semua keluaran dibuat di server tanpa JavaScript
 * dan tanpa gaya sebaris, supaya aturan Content-Security-Policy bisa ketat.
 *
 * Teks bahasa Indonesia sekaligus menjadi kunci terjemahan. Terjemahannya ada di
 * lib/i18n_admin.php; bahasa dipilih lewat menu bahasa (?lang=xx) dan disimpan
 * di cookie yang sama dengan aplikasi KPI.
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
    'tampilan_ubah'        => 'Mengubah tampilan',
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

const ADM_ICON_GLOBE = '<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
    . '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.5 2.6 3.8 5.6 3.8 9s-1.3 6.4-3.8 9c-2.5-2.6-3.8-5.6-3.8-9s1.3-6.4 3.8-9z"/></svg>';
const ADM_ICON_CARET = '<svg class="ico caret" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
    . '<path d="M6 9l6 6 6-6"/></svg>';

function h($s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES);
}

/** Teks terjemahan yang sudah aman untuk HTML. */
function ht(string $text, ...$args): string
{
    return h(kpi_t($text, ...$args));
}

/** Seperti ht(), tetapi {0}, {1}, ... diganti potongan HTML yang sudah aman, misalnya <b>...</b>. */
function hx(string $text, string ...$html): string
{
    return (string)preg_replace_callback('/\{(\d+)\}/', static fn($m) => $html[(int)$m[1]] ?? $m[0], h(kpi_t($text)));
}

/** Nama level dalam bahasa yang sedang dipakai. */
function adm_level(string $level): string
{
    return isset(ADM_LEVEL_NAMES[$level]) ? kpi_t(ADM_LEVEL_NAMES[$level]) : $level;
}

/** Submenu Anggota: daftar anggota menurut status akun (?p=anggota&status=...). */
const ADM_MEMBER_STATUS = ['aktif' => 'Aktif', 'nonaktif' => 'Tidak aktif'];

function adm_member_status($value): string
{
    return is_string($value) && isset(ADM_MEMBER_STATUS[$value]) ? $value : '';
}

/** Alamat daftar anggota, dengan submenu status bila ada. */
function adm_members_url(string $status, string $extra = ''): string
{
    return ADM_SELF . '?p=anggota' . ($status !== '' ? '&amp;status=' . $status : '') . $extra;
}

/** Penjelasan singkat setiap role anggota (lihat KPI_ROLES di auth.php). */
const ADM_ROLE_HINTS = [
    'anggota'   => 'Melihat dan mengisi KPI sendiri serta tim di bawahnya, dan menyetujui realisasi bawahan langsung.',
    'pemantau'  => 'Seperti anggota biasa, ditambah bisa melihat KPI dan skor semua anggota di seluruh perusahaan tanpa bisa mengubahnya. Cocok untuk HR atau auditor.',
    'pengelola' => 'Menetapkan dan mengubah KPI semua anggota di satu divisi atau unit, termasuk kepalanya. Tidak bisa mengubah data anggota atau pengaturan aplikasi.',
    'superuser' => 'Seperti CEO: melihat semua anggota, menetapkan dan mengubah KPI siapa pun di seluruh perusahaan, mengatur struktur organisasi, dan mengubah pengaturan aplikasi. Cocok untuk divisi atau unit yang menyusun KPI perusahaan.',
];

/** Nama wilayah (divisi atau unit) dari id kepalanya, mis. "Divisi Operasional". */
function adm_area_name(?int $headId): string
{
    $users = kpi_all_users();
    if ($headId === null || !isset($users[$headId])) {
        return '';
    }
    $head = $users[$headId];
    return $head['dept'] !== '' ? $head['dept'] : $head['name'];
}

/** Label role di daftar anggota. CEO selalu punya semua hak. */
function adm_role_pill(array $u): string
{
    if ($u['level'] === 'ceo') {
        return adm_pill(kpi_t('Semua hak (CEO)'), 'sys');
    }
    $r    = kpi_role($u);
    $pill = adm_pill(kpi_t(KPI_ROLES[$r]), ['anggota' => '', 'pemantau' => 'infra', 'pengelola' => 'ok', 'superuser' => 'sys'][$r]);
    $area = adm_area_name(kpi_role_area($u));
    return $r === 'pengelola' && $area !== '' ? $pill . '<div class="muted small">' . h($area) . '</div>' : $pill;
}

/** Panah "kembali" yang ikut berbalik arah pada tulisan kanan-ke-kiri. */
function adm_back_link(string $href, string $text): string
{
    return '<p><a class="back" href="' . h($href) . '"><span class="arr" aria-hidden="true">&larr;</span> ' . ht($text) . '</a></p>';
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
    return '<!doctype html><html lang="' . h(kpi_lang_html()) . '" dir="' . h(kpi_lang_dir()) . '"><head><meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">'
        . '<meta name="robots" content="noindex, nofollow">'
        . '<title>' . h($title) . '</title>'
        . '<link rel="icon" href="' . h(kpi_ui_logo()) . '">'
        . '<link rel="preconnect" href="https://fonts.googleapis.com">'
        . '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>'
        . '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Sora:wght@600;700&display=swap">'
        . '<link rel="stylesheet" href="assets/admin.css?v=' . h($ver) . '">'
        . '</head>';
}

/* ---------- Pilihan bahasa (tanpa JavaScript) ---------- */

/** Alamat untuk mengganti bahasa, dengan halaman yang sedang dibuka tetap sama. */
function adm_lang_href(string $code): string
{
    $q = ['lang' => $code];
    foreach (['p', 'edit', 'new', 'q'] as $k) {
        if (isset($_GET[$k]) && is_string($_GET[$k]) && $_GET[$k] !== '') {
            $q[$k] = $_GET[$k];
        }
    }
    return ADM_SELF . '?' . http_build_query($q);
}

/** Menu bahasa: tombol bola dunia yang membuka daftar bahasa per wilayah (Asia, Eropa). */
function adm_lang_menu(string $cls): string
{
    $cur   = kpi_lang();
    $names = KPI_LANG_NAMES[$cur] ?? [];
    $list  = '';
    foreach (KPI_LANG_REGIONS as $region => $codes) {
        $list .= '<p class="lang-grp">' . ht($region) . '</p>';
        foreach ($codes as $code) {
            $other = $code !== $cur && isset($names[$code]) && $names[$code] !== KPI_LANG_ENDONYMS[$code];
            $list .= '<a href="' . h(adm_lang_href($code)) . '" hreflang="' . h(KPI_LANGS[$code][0]) . '"'
                . ($code === $cur ? ' class="on" aria-current="true"' : '') . '>'
                . '<span lang="' . h(KPI_LANGS[$code][0]) . '">' . h(KPI_LANG_ENDONYMS[$code]) . '</span>'
                . ($other ? '<small>' . h($names[$code]) . '</small>' : '') . '</a>';
        }
    }
    return '<details class="lang-menu ' . h($cls) . '">'
        . '<summary class="lang-btn" title="' . ht('Ganti bahasa') . '">' . ADM_ICON_GLOBE
        . '<span class="vh">' . ht('Ganti bahasa') . ': </span>'
        . '<span class="lang-cur">' . h(KPI_LANG_ENDONYMS[$cur]) . '</span>'
        . '<span class="lang-code" aria-hidden="true">' . h(strtoupper($cur)) . '</span>' . ADM_ICON_CARET . '</summary>'
        . '<div class="lang-pop">' . $list . '</div></details>';
}

/* ---------- Kerangka halaman ---------- */

/** Judul halaman: nama peran pada link khusus, atau Admin Panel pada link umum. */
function adm_title(): string
{
    return KPI_ADMIN_GATE !== null ? KPI_ADMIN_ROLES[KPI_ADMIN_GATE] : kpi_t('Admin Panel');
}

/** Tautan ke halaman admin yang lain, di bawah formulir masuk. */
function adm_other_links(): string
{
    $out = '';
    foreach (KPI_ADMIN_LINKS as $role => $file) {
        if ($role !== KPI_ADMIN_GATE) {
            $out .= '<a href="' . h($file) . '">' . ht('Buka halaman {0}', KPI_ADMIN_ROLES[$role]) . '</a> · ';
        }
    }
    return $out;
}

function adm_page_guest(string $body, ?array $flash, bool $wide): string
{
    return adm_head(adm_title() . ' · Key Performance Indicator')
        . '<body><main class="guest' . ($wide ? ' wide' : '') . '">'
        . '<div class="guest-tools">' . adm_lang_menu('on-light') . '</div>'
        . '<div class="guest-brand"><img src="' . h(kpi_ui_logo()) . '" alt="" width="52" height="52">'
        . '<div><span class="eyebrow">Key Performance Indicator</span><h1>' . h(adm_title()) . '</h1></div></div>'
        . adm_flash_html($flash) . $body
        . '<p class="guest-foot">' . adm_other_links() . '<a href="index.php">' . ht('Kembali ke aplikasi KPI') . '</a></p>'
        . '</main></body></html>';
}

function adm_page(array $admin, string $panel, array $pages, string $page, string $body, ?array $flash, string $csrf): string
{
    $role   = $admin['role'];
    $title  = kpi_t($pages[$page]);
    $status = $page === 'anggota' ? adm_member_status($_GET['status'] ?? '') : '';
    if ($status !== '') {
        $title = $status === 'aktif' ? kpi_t('Anggota aktif') : kpi_t('Anggota tidak aktif');
    }
    $nav = '';
    foreach ($pages as $key => $label) {
        $on   = $key === $page && $status === '';
        $nav .= '<a href="' . ADM_SELF . '?p=' . h($key) . '"' . ($on ? ' class="on" aria-current="page"' : ($key === $page ? ' class="open"' : '')) . '>' . ht($label) . '</a>';
        if ($key === 'anggota') {
            // Submenu: anggota aktif dan tidak aktif, dengan jumlahnya.
            $count = ['aktif' => 0, 'nonaktif' => 0];
            foreach (kpi_all_users() as $u) {
                $count[$u['is_active'] ? 'aktif' : 'nonaktif']++;
            }
            foreach (ADM_MEMBER_STATUS as $sk => $sl) {
                $sOn  = $page === 'anggota' && $status === $sk;
                $nav .= '<a class="sub' . ($sOn ? ' on' : '') . '" href="' . adm_members_url($sk) . '"' . ($sOn ? ' aria-current="page"' : '') . '>'
                    . '<span>' . ht($sl) . '</span><span class="count num">' . kpi_num($count[$sk]) . '</span></a>';
            }
        }
    }
    $maint = kpi_setting('maintenance', '0') === '1'
        ? '<div class="flash warn">' . ht('Mode pemeliharaan sedang aktif. Aplikasi KPI ditutup sementara untuk semua anggota.') . '</div>'
        : '';
    $defaultPass = kpi_admin_default_password($admin);
    if ($defaultPass !== null) {
        $maint .= '<div class="flash warn">' . hx('Anda masih memakai password bawaan {0}. Siapa pun yang tahu password ini bisa masuk.', '<b>' . h($defaultPass) . '</b>')
            . ' <a href="' . ADM_SELF . '?p=akun">' . ht('Ganti password sekarang') . '</a></div>';
    }

    return adm_head($title . ' · ' . adm_title())
        . '<body class="panel-' . h($panel) . ' role-' . h($role) . '">'
        . '<header class="top"><a class="brand" href="' . ADM_SELF . '"><img src="' . h(kpi_ui_logo()) . '" alt="" width="34" height="34">'
        . '<span><b>' . h(adm_title()) . '</b><small>Key Performance Indicator</small></span></a>'
        . '<div class="who">' . adm_lang_menu('on-dark')
        . '<span class="role" title="' . ht('Peran') . '">' . h(KPI_ADMIN_ROLES[$role]) . '</span>'
        . '<span class="who-name">' . h($admin['name']) . '</span>'
        . '<form method="post" action="' . ADM_SELF . '">' . adm_csrf_field($csrf)
        . '<input type="hidden" name="_act" value="logout"><button class="btn ghost" type="submit">' . ht('Keluar') . '</button></form></div></header>'
        . '<div class="shell"><nav class="nav" aria-label="' . ht('Menu admin') . '">' . $nav . '</nav>'
        . '<main class="main"><h1>' . h($title) . '</h1>' . $maint . adm_flash_html($flash) . $body . '</main></div>'
        . '</body></html>';
}

/* ---------- Masuk dan admin pertama ---------- */

function adm_view_login(string $csrf, array $old): string
{
    return '<form class="card form" method="post" action="' . ADM_SELF . '" autocomplete="off">' . adm_csrf_field($csrf)
        . '<input type="hidden" name="_act" value="login">'
        . '<p class="muted">' . ht('Masuk dengan akun sysAdmin atau infraAdmin. Akun ini berbeda dari akun anggota di aplikasi KPI.') . '</p>'
        . adm_input(kpi_t('Username'), 'username', $old['username'] ?? '', ['required' => true, 'autofocus' => true, 'autocapitalize' => 'none'])
        . adm_input(kpi_t('Password'), 'password', '', ['type' => 'password', 'required' => true])
        . '<button class="btn primary block" type="submit">' . ht('Masuk') . '</button></form>';
}

function adm_view_first(string $csrf, array $old): string
{
    $min = kpi_t('Password (minimal {0} karakter)', KPI_ADMIN_MIN_PASS);
    return '<form class="card form" method="post" action="' . ADM_SELF . '" autocomplete="off">' . adm_csrf_field($csrf)
        . '<input type="hidden" name="_act" value="first_admin">'
        . '<div><h2>' . ht('Buat akun admin pertama') . '</h2>'
        . '<p class="muted">' . ht('Belum ada akun admin. Untuk membuktikan bahwa Anda berhak, isi dulu username dan password CEO di aplikasi KPI.') . '</p></div>'

        . '<fieldset><legend>' . ht('1. Bukti dari CEO') . '</legend><div class="grid2">'
        . adm_input(kpi_t('Username CEO'), 'ceo_user', $old['ceo_user'] ?? '', ['required' => true, 'autocapitalize' => 'none'])
        . adm_input(kpi_t('Password CEO'), 'ceo_pass', '', ['type' => 'password', 'required' => true])
        . '</div></fieldset>'

        . '<fieldset><legend>' . ht('2. Akun sysAdmin') . '</legend>'
        . '<p class="muted small">' . ht('Mengurus isi aplikasi: anggota, pengaturan, dan akun admin lain.') . '</p><div class="grid2">'
        . adm_input(kpi_t('Nama'), 'sys_name', $old['sys_name'] ?? 'System Administrator', [])
        . adm_input(kpi_t('Username'), 'sys_user', $old['sys_user'] ?? 'sysadmin', ['required' => true, 'autocapitalize' => 'none'])
        . adm_input($min, 'sys_pass', '', ['type' => 'password', 'required' => true, 'minlength' => KPI_ADMIN_MIN_PASS])
        . '</div></fieldset>'

        . '<fieldset><legend>' . ht('3. Akun infraAdmin') . '</legend>'
        . '<p class="muted small">' . ht('Mengurus server: status, cadangan database, dan mode pemeliharaan. Boleh dikosongkan dan dibuat nanti oleh sysAdmin.') . '</p><div class="grid2">'
        . adm_input(kpi_t('Nama'), 'inf_name', $old['inf_name'] ?? '', ['placeholder' => 'Infrastructure Administrator'])
        . adm_input(kpi_t('Username'), 'inf_user', $old['inf_user'] ?? '', ['placeholder' => 'infraadmin', 'autocapitalize' => 'none'])
        . adm_input($min, 'inf_pass', '', ['type' => 'password', 'minlength' => KPI_ADMIN_MIN_PASS])
        . '</div></fieldset>'

        . '<button class="btn primary" type="submit">' . ht('Buat akun admin') . '</button></form>';
}

/* ---------- Bagian kecil yang dipakai berulang ---------- */

/** Kotak isian. $label, placeholder, dan hint sudah diterjemahkan oleh pemanggil. */
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
    $hint = isset($o['hint']) && $o['hint'] !== '' ? '<span class="hint">' . h($o['hint']) . '</span>' : '';
    return '<label class="field"><span>' . h($label) . '</span>'
        . '<input type="' . h($type) . '" name="' . h($name) . '" value="' . h($value) . '"' . $attr . '>' . $hint . '</label>';
}

/** Daftar pilihan. $label, $options, dan $hint sudah diterjemahkan oleh pemanggil. */
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

/** Tanggal dan jam. <bdi> menjaga urutan tanggal lalu jam pada tulisan kanan-ke-kiri. */
function adm_when(?string $ts): string
{
    if (!$ts) {
        return '-';
    }
    $t = strtotime($ts);
    return '<bdi>' . ($t ? date(kpi_date_format(), $t) : h($ts)) . '</bdi>';
}

/** Tombol yang meminta penegasan lewat <details>, tanpa JavaScript. Teksnya sudah diterjemahkan. */
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

function adm_status_pill(bool $active): string
{
    return $active ? adm_pill(kpi_t('Aktif'), 'ok') : adm_pill(kpi_t('Nonaktif'), 'bad');
}

/* ---------- sysAdmin ---------- */

function adm_view_overview(array $ctx): string
{
    $o  = kpi_overview();
    $lv = $o['levels'];
    $html = '<p class="lead">' . hx('Ini halaman {0}. Dari sini Anda mengurus anggota, pengaturan aplikasi, dan akun admin.', '<b>sysAdmin</b>') . '</p>'
        . '<div class="stats">'
        . adm_stat(kpi_t('Anggota'), kpi_num($o['users']), kpi_t('{0} CEO, {1} Division Head, {2} Unit Head, {3} Staff', $lv['ceo'], $lv['div'], $lv['unit'], $lv['staff']))
        . adm_stat(kpi_t('KPI'), kpi_num($o['kpis']), $o['no_kpi'] ? kpi_t('{0} anggota belum punya KPI', $o['no_kpi']) : kpi_t('Semua anggota sudah punya KPI'), $o['no_kpi'] ? 'warn' : '')
        . adm_stat(kpi_t('Menunggu persetujuan'), kpi_num($o['pending']), kpi_t('Realisasi yang diajukan dan belum diputuskan atasan'))
        . adm_stat(kpi_t('Anggota nonaktif'), kpi_num($o['inactive']), kpi_t('Tidak bisa masuk ke aplikasi'))
        . adm_stat(kpi_t('Login terkunci'), kpi_num($o['locked']), $o['locked'] ? kpi_t('Bisa dibuka di menu Anggota') : kpi_t('Tidak ada'), $o['locked'] ? 'warn' : '')
        . adm_stat(kpi_t('Admin aktif'), kpi_num($o['admins']), kpi_t('sysAdmin dan infraAdmin'))
        . '</div>';

    $rows = '';
    foreach (kpi_activity(8) as $a) {
        $rows .= '<tr><td class="nowrap">' . adm_when($a['created_at']) . '</td><td>' . h($a['user_name'] ?? kpi_t('Admin atau sistem')) . '</td>'
            . '<td>' . h(isset(ADM_ACTION_NAMES[$a['action']]) ? kpi_t(ADM_ACTION_NAMES[$a['action']]) : $a['action']) . '</td></tr>';
    }
    $html .= '<section class="card"><div class="card-h"><h2>' . ht('Kegiatan terakhir') . '</h2><a class="btn" href="' . ADM_SELF . '?p=aktivitas">' . ht('Lihat semua') . '</a></div>'
        . '<div class="tbl-wrap"><table><thead><tr><th>' . ht('Waktu') . '</th><th>' . ht('Pelaku') . '</th><th>' . ht('Kegiatan') . '</th></tr></thead><tbody>'
        . ($rows ?: '<tr><td colspan="3" class="muted">' . ht('Belum ada kegiatan.') . '</td></tr>') . '</tbody></table></div></section>';
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

    $q      = mb_strtolower(trim((string)($get['q'] ?? '')));
    $status = adm_member_status($get['status'] ?? '');
    $rows   = '';
    $n      = 0;
    $order = array_flip(array_keys(ADM_LEVEL_NAMES));
    uasort($users, fn($a, $b) => [$order[$a['level']] ?? 9, $a['dept'], $a['name']] <=> [$order[$b['level']] ?? 9, $b['dept'], $b['name']]);
    foreach ($users as $u) {
        $hay = $u['name'] . ' ' . $u['username'] . ' ' . $u['dept'] . ' ' . $u['title'] . ' ' . KPI_ROLES[kpi_role($u)] . ' ' . kpi_t(KPI_ROLES[kpi_role($u)]);
        if ($q !== '' && mb_strpos(mb_strtolower($hay), $q) === false) {
            continue;
        }
        if ($status !== '' && (bool)$u['is_active'] !== ($status === 'aktif')) {
            continue;
        }
        $n++;
        $boss = $u['parent_id'] !== null && isset($users[(int)$u['parent_id']]) ? $users[(int)$u['parent_id']]['name'] : '-';
        $rows .= '<tr><td><b>' . h($u['name']) . '</b><div class="muted small">' . h($u['title']) . '</div></td>'
            . '<td>' . h(adm_level($u['level'])) . '</td>'
            . '<td>' . adm_role_pill($u) . '</td>'
            . '<td>' . h($u['dept']) . '</td><td>' . h($boss) . '</td>'
            . '<td><code>' . h($u['username']) . '</code></td>'
            . '<td>' . adm_status_pill((bool)$u['is_active']) . '</td>'
            . '<td class="right"><a class="btn" href="' . adm_members_url($status, '&amp;edit=' . (int)$u['id']) . '">' . ht('Ubah') . '</a></td></tr>';
    }

    if ($q !== '') {
        $empty = kpi_t('Tidak ada anggota yang cocok.');
    } elseif ($status === 'nonaktif') {
        $empty = kpi_t('Tidak ada anggota yang tidak aktif. Semua akun anggota bisa masuk.');
    } else {
        $empty = kpi_t('Tidak ada anggota yang aktif.');
    }
    $html = ($status === 'nonaktif'
            ? '<p class="muted">' . ht('Anggota tidak aktif tidak bisa masuk ke aplikasi, tetapi data dan KPI-nya tetap tersimpan. Klik Ubah untuk mengaktifkannya kembali.') . '</p>'
            : ($status === 'aktif' ? '<p class="muted">' . ht('Anggota yang bisa masuk ke aplikasi KPI.') . '</p>' : ''))
        . '<div class="toolbar"><form method="get" action="' . ADM_SELF . '" class="search"><input type="hidden" name="p" value="anggota">'
        . ($status !== '' ? '<input type="hidden" name="status" value="' . h($status) . '">' : '')
        . '<input type="search" name="q" value="' . h($get['q'] ?? '') . '" placeholder="' . ht('Cari nama, username, unit, atau role') . '" aria-label="' . ht('Cari anggota') . '">'
        . '<button class="btn" type="submit">' . ht('Cari') . '</button></form>'
        . '<a class="btn primary" href="' . ADM_SELF . '?p=anggota&amp;new=1">' . ht('Tambah anggota') . '</a></div>'
        . '<section class="card"><div class="tbl-wrap"><table><thead><tr><th>' . ht('Nama') . '</th><th>' . ht('Level') . '</th><th>' . ht('Role') . '</th><th>' . ht('Unit') . '</th>'
        . '<th>' . ht('Atasan') . '</th><th>' . ht('Username') . '</th><th>' . ht('Status') . '</th><th></th></tr></thead><tbody>'
        . ($rows ?: '<tr><td colspan="8" class="muted">' . h($empty) . '</td></tr>')
        . '</tbody></table></div><p class="muted small foot">' . ht('{0} anggota ditampilkan.', kpi_num($n)) . '</p></section>';

    // Satu password untuk semua anggota
    $html .= '<section class="card" id="pw-all"><div><h2>' . ht('Ganti password semua anggota') . '</h2>'
        . '<p class="muted">' . ht('Memberi satu password yang sama untuk semua akun anggota, aktif maupun tidak aktif ({0} akun). Akun admin tidak ikut berubah. Setelah itu, beri tahu anggota password barunya.', kpi_num(count($users))) . '</p></div>'
        . '<details class="confirm"><summary class="btn danger-soft">' . ht('Ganti password semua anggota') . '</summary>'
        . '<form method="post" action="' . ADM_SELF . '" class="confirm-box pw-all">' . adm_csrf_field($csrf)
        . '<input type="hidden" name="_act" value="user_password_all"><input type="hidden" name="_back" value="anggota">'
        . ($status !== '' ? '<input type="hidden" name="_status" value="' . h($status) . '">' : '')
        . adm_input(kpi_t('Password baru untuk semua anggota'), 'new_pass', '', ['required' => true, 'minlength' => 4, 'maxlength' => 72,
            'hint' => kpi_t('Minimal 4 karakter. Tulisannya terlihat supaya mudah diperiksa sebelum disimpan.')])
        . '<label class="check"><input type="checkbox" name="confirm" value="1" required>'
        . '<span><b>' . ht('Saya mengerti') . '</b><small>' . ht('Password lama semua anggota tidak bisa dipakai lagi.') . '</small></span></label>'
        . '<div class="actions"><button class="btn danger" type="submit">' . ht('Ganti semua password') . '</button></div></form></details></section>';

    $locked = kpi_locked_list();
    if ($locked) {
        $lr = '';
        foreach ($locked as $l) {
            [$who, $ip] = array_pad(explode('|', (string)$l['ident'], 2), 2, '');
            $lr .= '<tr><td><code>' . h($who) . '</code></td><td>' . h($ip) . '</td><td class="nowrap">' . adm_when($l['locked_until']) . '</td>'
                . '<td class="right"><form method="post" action="' . ADM_SELF . '">' . adm_csrf_field($csrf)
                . '<input type="hidden" name="_act" value="user_unlock"><input type="hidden" name="ident" value="' . h($l['ident']) . '">'
                . '<button class="btn" type="submit">' . ht('Buka kunci') . '</button></form></td></tr>';
        }
        $html .= '<section class="card"><div class="card-h"><h2>' . ht('Login yang terkunci') . '</h2></div>'
            . '<p class="muted">' . ht('Terkunci 15 menit karena terlalu banyak salah password. Buka kuncinya bila orangnya memang berhak.') . '</p>'
            . '<div class="tbl-wrap"><table><thead><tr><th>' . ht('Username') . '</th><th>' . ht('Alamat IP') . '</th><th>' . ht('Terkunci sampai') . '</th><th></th></tr></thead><tbody>' . $lr . '</tbody></table></div></section>';
    }
    return $html;
}

function adm_user_form(array $ctx, array $users, ?array $u): string
{
    $csrf = $ctx['csrf'];
    $old  = $ctx['old'];
    $val    = fn(string $k, $def = '') => $old[$k] ?? $def;
    $status = adm_member_status($ctx['get']['status'] ?? '');
    $list   = adm_members_url($status);
    $back   = '<p><a class="back" href="' . $list . '"><span class="arr" aria-hidden="true">&larr;</span> ' . ht('Kembali ke daftar anggota') . '</a></p>';
    $keep   = $status !== '' ? '<input type="hidden" name="_status" value="' . h($status) . '">' : '';

    // Pilihan atasan: untuk anggota baru semua yang bukan Staff, untuk yang lama hanya yang selevel dengan atasannya sekarang.
    $parents = [];
    if ($u === null) {
        foreach ($users as $p) {
            if ($p['level'] !== 'staff') {
                $parents[(int)$p['id']] = $p['name'] . ' (' . adm_level($p['level']) . ', ' . $p['dept'] . ')';
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
        . '<input type="hidden" name="_act" value="user_save"><input type="hidden" name="_back" value="anggota">' . $keep
        . ($u ? '<input type="hidden" name="id" value="' . (int)$u['id'] . '"><input type="hidden" name="_edit" value="' . (int)$u['id'] . '">'
              : '<input type="hidden" name="_new" value="1">')
        . '<h2>' . ($u ? ht('Ubah data {0}', $u['name']) : ht('Tambah anggota')) . '</h2>'
        . ($u ? '<p class="muted">' . ht('Level: {0}', adm_level($u['level'])) . '</p>'
              : '<p class="muted">' . ht('Level anggota baru mengikuti atasannya: bawahan CEO menjadi Division Head, bawahan Division Head menjadi Unit Head, dan bawahan Unit Head menjadi Staff.') . '</p>')
        . '<div class="grid2">'
        . adm_input(kpi_t('Nama lengkap'), 'name', $val('name', $u['name'] ?? ''), ['required' => true, 'maxlength' => 120])
        . adm_input(kpi_t('Jabatan'), 'title', $val('title', $u['title'] ?? ''), ['maxlength' => 120])
        . adm_input(kpi_t('Unit atau divisi'), 'dept', $val('dept', $u['dept'] ?? ''), ['maxlength' => 120, 'hint' => $u ? '' : kpi_t('Bila kosong, mengikuti unit atasannya.')])
        . ($parents ? adm_select(kpi_t('Atasan langsung'), 'parentId', $parents, $val('parentId', $u['parent_id'] ?? ''),
                $u ? kpi_t('Bila atasan diganti, hubungan KPI ke atasan lama dilepas.') : '') : '')
        . adm_input(kpi_t('Username'), 'username', $val('username', $u['username'] ?? ''), ['maxlength' => 64, 'autocapitalize' => 'none',
                'hint' => $u ? '' : kpi_t('Bila kosong, dibuat dari nama. Contoh: siti.rahma')])
        . adm_input($u ? kpi_t('Password baru') : kpi_t('Password'), 'password', '', ['type' => 'password', 'minlength' => 4, 'required' => !$u,
                'hint' => $u ? kpi_t('Kosongkan bila tidak ingin mengganti password.') : kpi_t('Minimal {0} karakter.', 4)])
        . '</div>' . adm_role_field($u, (string)$val('role', $u ? kpi_role($u) : 'anggota'),
                (string)$val('role_scope', $u && $u['role_scope'] !== null && kpi_area_valid((int)$u['role_scope']) ? (string)$u['role_scope'] : ''))
        . '<div class="actions"><button class="btn primary" type="submit">' . ht('Simpan') . '</button>'
        . '<a class="btn" href="' . $list . '">' . ht('Batal') . '</a></div></form>';

    if (!$u) {
        return $back . $form;
    }

    $id   = (int)$u['id'];
    $side = '<section class="card"><h2>' . ht('Status akun') . '</h2>';
    if ($u['is_active']) {
        $side .= '<p class="muted">' . ht('Akun ini aktif. Bila dinonaktifkan, orangnya tidak bisa masuk, tetapi data dan KPI-nya tetap tersimpan.') . '</p>'
            . adm_confirm($csrf, kpi_t('Nonaktifkan akun'), kpi_t('Nonaktifkan akun {0}?', $u['name']), 'user_toggle', ['id' => $id, 'active' => '0', '_status' => $status], kpi_t('Ya, nonaktifkan'), 'warn');
    } else {
        $side .= '<p class="muted">' . ht('Akun ini nonaktif dan tidak bisa masuk.') . '</p>'
            . '<form method="post" action="' . ADM_SELF . '">' . adm_csrf_field($csrf)
            . '<input type="hidden" name="_act" value="user_toggle"><input type="hidden" name="id" value="' . $id . '"><input type="hidden" name="active" value="1">' . $keep
            . '<button class="btn primary" type="submit">' . ht('Aktifkan kembali') . '</button></form>';
    }
    $side .= '</section>';

    if ($u['level'] !== 'ceo') {
        $side .= '<section class="card"><h2>' . ht('Hapus anggota') . '</h2>'
            . '<p class="muted">' . ht('Menghapus anggota juga menghapus seluruh KPI dan realisasinya, dan tidak bisa dibatalkan. Anggota yang masih punya bawahan tidak bisa dihapus.') . '</p>'
            . adm_confirm($csrf, kpi_t('Hapus anggota'), kpi_t('Hapus {0} beserta seluruh KPI dan realisasinya?', $u['name']), 'user_delete',
                ['id' => $id, '_back' => 'anggota', '_edit' => $id, '_status' => $status], kpi_t('Ya, hapus permanen'))
            . '</section>';
    }
    return $back . $form . $side;
}

/** Pilihan role di formulir anggota. CEO tidak bisa dipilihkan role karena sudah punya semua hak. */
function adm_role_field(?array $u, string $cur, string $scope = ''): string
{
    if ($u && $u['level'] === 'ceo') {
        return '<fieldset><legend>' . ht('Role') . '</legend><p class="muted">'
            . ht('CEO selalu bisa melihat dan mengatur semua KPI, anggota, dan pengaturan aplikasi, jadi tidak perlu diberi role.') . '</p></fieldset>';
    }
    if (!isset(KPI_ROLES[$cur])) {
        $cur = 'anggota';
    }
    $html = '<fieldset><legend>' . ht('Role') . '</legend>'
        . '<p class="muted small">' . ht('Role menentukan apa saja yang bisa dilihat dan diubah orang ini di aplikasi, di luar timnya sendiri. Level tetap mengikuti atasannya.') . '</p>';
    foreach (KPI_ROLES as $key => $label) {
        $html .= '<label class="check"><input type="radio" name="role" value="' . h($key) . '"' . ($key === $cur ? ' checked' : '') . '>'
            . '<span><b>' . ht($label) . '</b><small>' . ht(ADM_ROLE_HINTS[$key]) . '</small></span></label>';
        if ($key === 'pengelola') {
            $html .= adm_area_select($u, $scope);
        }
    }
    return $html . '</fieldset>';
}

/** Pilihan divisi atau unit untuk Pengelola KPI. Disembunyikan (CSS) bila role lain yang dipilih. */
function adm_area_select(?array $u, string $scope): string
{
    $users = kpi_all_users();
    $own   = $u ? adm_area_name(kpi_division_of($u)) : '';
    $html  = '<label class="field area-pick"><span>' . ht('Divisi atau unit yang dikelola') . '</span><select name="role_scope">'
        . '<option value="">' . ($own !== '' ? ht('Divisinya sendiri ({0})', $own) : ht('Divisi anggota ini sendiri')) . '</option>';
    foreach (['div' => 'Divisi', 'unit' => 'Unit'] as $lvl => $group) {
        $opts = [];
        foreach ($users as $p) {
            if ($p['level'] === $lvl) {
                $opts[adm_area_name((int)$p['id']) . "\0" . $p['id']] = $p;
            }
        }
        if (!$opts) {
            continue;
        }
        ksort($opts, SORT_NATURAL | SORT_FLAG_CASE);
        $html .= '<optgroup label="' . ht($group) . '">';
        foreach ($opts as $p) {
            $html .= '<option value="' . (int)$p['id'] . '"' . ((string)$p['id'] === $scope ? ' selected' : '') . '>'
                . h(adm_area_name((int)$p['id']) . ' (' . $p['name'] . ')') . '</option>';
        }
        $html .= '</optgroup>';
    }
    return $html . '</select><span class="hint">' . ht('Pengelola KPI bisa mengatur KPI semua anggota di wilayah ini, termasuk kepalanya.') . '</span></label>';
}

function adm_view_settings(array $ctx): string
{
    $old = $ctx['old'];
    $val = fn(string $k, $def) => $old[$k] ?? $def;
    $demo = array_key_exists('company', $old) ? !empty($old['demo_mode']) : kpi_setting('demo_mode', '0') === '1';
    return '<form class="card form" method="post" action="' . ADM_SELF . '">' . adm_csrf_field($ctx['csrf'])
        . '<input type="hidden" name="_act" value="settings_save"><input type="hidden" name="_back" value="pengaturan">'
        . '<div class="grid2">'
        . adm_input(kpi_t('Nama perusahaan'), 'company', $val('company', kpi_setting('company', '')), ['required' => true, 'maxlength' => 120])
        . adm_input(kpi_t('Tahun KPI'), 'year', $val('year', kpi_setting('year', date('Y'))), ['type' => 'number', 'min' => 2000, 'max' => 2100, 'required' => true,
            'hint' => kpi_t('Tahun yang sedang diisi dan dinilai.')])
        . adm_input(kpi_t('Batas capaian per KPI (persen)'), 'cap', $val('cap', (float)kpi_setting('cap', 120)), ['type' => 'number', 'min' => 100, 'max' => 200, 'step' => 1, 'required' => true,
            'hint' => kpi_t('Capaian di atas angka ini tidak menambah skor. Bawaan 120.')])
        . '</div>'
        . '<label class="check"><input type="checkbox" name="demo_mode" value="1"' . ($demo ? ' checked' : '') . '>'
        . '<span><b>' . ht('Tampilkan akun contoh di halaman login') . '</b><small>' . ht('Matikan sebelum aplikasi dipakai sungguhan.') . '</small></span></label>'
        . '<div class="actions"><button class="btn primary" type="submit">' . ht('Simpan pengaturan') . '</button></div></form>';
}

/* ---------- sysAdmin: Tampilan ---------- */

/** Satu tempat gambar: contoh gambar sekarang, tombol unggah, dan tombol kembali ke bawaan. */
function adm_ui_slot(string $csrf, string $slot, string $title, string $desc, string $default, string $kind,
    string $defaultPill = 'Bawaan', string $resetLabel = 'Pakai bawaan lagi'): string
{
    $own  = kpi_ui_image($slot);
    $prev = $own !== null ? '<img src="' . h($own) . '" alt="">' : $default;
    $keep = '<input type="hidden" name="_back" value="tampilan"><input type="hidden" name="slot" value="' . h($slot) . '">';
    return '<div class="media" id="ui-' . h($slot) . '">'
        . '<div class="media-prev ' . h($kind) . '">' . $prev . '</div>'
        . '<div class="media-body"><div class="media-h"><h3>' . ht($title) . '</h3>'
        . ($own !== null ? adm_pill(kpi_t('Gambar sendiri'), 'ok') : adm_pill(kpi_t($defaultPill), '')) . '</div>'
        . '<p class="muted small">' . ht($desc) . '</p>'
        . '<form class="upload" method="post" action="' . ADM_SELF . '" enctype="multipart/form-data">' . adm_csrf_field($csrf)
        . '<input type="hidden" name="_act" value="ui_upload">' . $keep
        . '<input type="file" name="image" accept="image/png,image/jpeg,image/webp,image/gif" required aria-label="' . ht('Pilih gambar') . '">'
        . '<button class="btn primary" type="submit">' . ht('Unggah') . '</button></form>'
        . ($own !== null
            ? '<form method="post" action="' . ADM_SELF . '">' . adm_csrf_field($csrf) . '<input type="hidden" name="_act" value="ui_remove">' . $keep
              . '<button class="btn danger-soft" type="submit">' . ht($resetLabel) . '</button></form>'
            : '')
        . '</div></div>';
}

/** Pilihan berupa kartu bergambar (radio). */
function adm_ui_choice(string $name, string $value, bool $checked, string $img, string $label, string $note = ''): string
{
    return '<label class="theme"><input type="radio" name="' . h($name) . '" value="' . h($value) . '"' . ($checked ? ' checked' : '') . '>'
        . '<img src="' . h($img) . '" alt="" width="160" height="96">'
        . '<span class="theme-name">' . $label . ($note !== '' ? '<small>' . $note . '</small>' : '') . '</span></label>';
}

function adm_view_appearance(array $ctx): string
{
    $csrf   = $ctx['csrf'];
    $key    = kpi_ui_theme_key();
    $custom = (string)kpi_setting('ui_color', '');
    if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $custom)) {
        $custom = kpi_ui_color();
    }
    $custom = strtolower($custom);
    $back   = '<input type="hidden" name="_back" value="tampilan">';

    // Warna template
    $themes = '';
    foreach (KPI_UI_THEMES as $k => [$label, $hex]) {
        $themes .= adm_ui_choice('theme', $k, $k === $key, kpi_ui_preview_svg($hex), ht($label),
            $k === KPI_UI_DEFAULT_THEME ? ht('Bawaan') : '');
    }
    $themes .= '<div class="theme own"><label class="theme-pick"><input type="radio" name="theme" value="custom"' . ($key === 'custom' ? ' checked' : '') . '>'
        . '<img src="' . h(kpi_ui_preview_svg($custom)) . '" alt="" width="160" height="96">'
        . '<span class="theme-name">' . ht('Warna sendiri') . '</span></label>'
        . '<label class="color-field"><span>' . ht('Pilih warna') . '</span><input type="color" name="color" value="' . h($custom) . '"></label>'
        . '<input type="hidden" name="color_was" value="' . h($custom) . '"></div>';

    $html = '<div class="toolbar"><p class="lead">' . ht('Atur tampilan aplikasi KPI untuk semua anggota. Perubahan langsung berlaku saat halaman aplikasi dimuat ulang.') . '</p>'
        . '<a class="btn" href="index.php" target="_blank" rel="noopener">' . ht('Buka aplikasi KPI') . '</a></div>'

        . '<form class="card form" id="ui-theme" method="post" action="' . ADM_SELF . '">' . adm_csrf_field($csrf)
        . '<input type="hidden" name="_act" value="ui_theme_save">' . $back
        . '<div><h2>' . ht('Warna template') . '</h2><p class="muted">' . ht('Warna tombol, menu, tautan, dan panel halaman masuk. Tampilan gelap ikut menyesuaikan.') . '</p></div>'
        . '<div class="themes">' . $themes . '</div>'
        . '<div class="actions"><button class="btn primary" type="submit">' . ht('Simpan warna') . '</button></div></form>'

        . '<section class="card" id="ui-brand"><div><h2>' . ht('Logo') . '</h2>'
        . '<p class="muted">' . ht('Gambar: PNG, JPG, WEBP, atau GIF, paling besar {0} MB.', kpi_ui_limit_mb()) . '</p></div>'
        . adm_ui_slot($csrf, 'logo', 'Logo perusahaan', 'Tampil di menu aplikasi, di halaman admin, di tab browser, dan di halaman masuk bila Logo halaman masuk tidak diisi. Paling bagus gambar persegi dengan latar transparan.',
            '<img src="assets/logo.png" alt="">', 'logo')
        . '</section>';

    // Halaman masuk
    $show    = kpi_ui_show_art();
    $lcMode  = kpi_ui_login_color() !== null ? 'custom' : 'template';
    $lcSaved = kpi_ui_login_color_saved();
    $html .= '<section class="card" id="ui-login"><div><h2>' . ht('Halaman masuk') . '</h2>'
        . '<p class="muted">' . ht('Halaman pertama yang dilihat anggota sebelum masuk.') . '</p></div>'
        . adm_ui_slot($csrf, 'login_logo', 'Logo halaman masuk', 'Logo di atas formulir masuk, di samping tulisan Key Performance Indicator. Bila tidak diisi, memakai Logo perusahaan.',
            '<img src="' . h(kpi_ui_logo()) . '" alt="">', 'logo', 'Sama dengan logo perusahaan', 'Pakai logo perusahaan')
        . adm_ui_slot($csrf, 'login_art', 'Gambar ilustrasi', 'Gambar besar di sisi kiri halaman masuk, menggantikan ilustrasi bawaan. Tidak tampil di layar ponsel.',
            '<span class="ph">' . ht('Ilustrasi bawaan') . '</span>', 'art')
        . adm_ui_slot($csrf, 'login_bg', 'Gambar latar', 'Foto atau gambar di belakang halaman masuk. Sisi kiri diberi lapisan warna latar halaman masuk supaya tulisan tetap terbaca.',
            '<img src="' . h(kpi_ui_swatch_svg('login')) . '" alt="">', 'bg')
        . '<form class="form sub-form" method="post" action="' . ADM_SELF . '">' . adm_csrf_field($csrf)
        . '<input type="hidden" name="_act" value="ui_login_save">' . $back
        . '<fieldset id="ui-login-color"><legend>' . ht('Warna latar halaman masuk') . '</legend>'
        . '<p class="muted small">' . ht('Warna panel kiri dan tepi di sekitar kartu masuk. Tulisan di panel otomatis putih atau gelap supaya tetap terbaca. Bila ada gambar latar, warna ini menjadi lapisan di atas gambar.') . '</p>'
        . '<div class="themes">'
        . adm_ui_choice('login_color_mode', 'template', $lcMode !== 'custom', kpi_ui_swatch_svg('login-template'), ht('Ikuti warna template'), ht('Bawaan'))
        . '<div class="theme own"><label class="theme-pick"><input type="radio" name="login_color_mode" value="custom"' . ($lcMode === 'custom' ? ' checked' : '') . '>'
        . '<img src="' . h(kpi_ui_swatch_svg('login-custom')) . '" alt="" width="160" height="96">'
        . '<span class="theme-name">' . ht('Warna sendiri') . '</span></label>'
        . '<label class="color-field"><span>' . ht('Pilih warna') . '</span><input type="color" name="login_color" value="' . h(strtolower($lcSaved)) . '"></label>'
        . '<input type="hidden" name="login_color_was" value="' . h(strtolower($lcSaved)) . '"></div>'
        . '</div></fieldset>'
        . '<label class="check"><input type="checkbox" name="show_art" value="1"' . ($show ? ' checked' : '') . '>'
        . '<span><b>' . ht('Tampilkan gambar ilustrasi') . '</b><small>' . ht('Hilangkan centang bila Anda hanya ingin menampilkan gambar latar.') . '</small></span></label>'
        . '<div class="actions"><button class="btn primary" type="submit">' . ht('Simpan') . '</button></div></form></section>';

    // Setelah masuk
    $mode   = kpi_ui_app_bg_mode();
    $appImg = kpi_ui_image('app_bg');
    $veils  = ['soft' => kpi_t('Samar (tulisan paling mudah dibaca)'), 'medium' => kpi_t('Sedang'), 'strong' => kpi_t('Jelas')];
    $html .= '<section class="card" id="ui-app"><div><h2>' . ht('Setelah masuk') . '</h2>'
        . '<p class="muted">' . ht('Latar di belakang isi aplikasi setelah anggota masuk. Menu dan kartu tetap berwarna polos supaya mudah dibaca.') . '</p></div>'
        . adm_ui_slot($csrf, 'app_bg', 'Gambar latar aplikasi', 'Setelah diunggah, gambar langsung dipakai sebagai latar. Bisa diganti kembali di pilihan latar di bawah.',
            '<img src="' . h(kpi_ui_swatch_svg('default')) . '" alt="">', 'bg')
        . '<form class="form sub-form" method="post" action="' . ADM_SELF . '">' . adm_csrf_field($csrf)
        . '<input type="hidden" name="_act" value="ui_app_save">' . $back
        . '<fieldset><legend>' . ht('Pilihan latar') . '</legend><div class="themes">'
        . adm_ui_choice('bg_mode', 'default', $mode === 'default', kpi_ui_swatch_svg('default'), ht('Bawaan'), ht('Abu-abu muda polos'))
        . adm_ui_choice('bg_mode', 'tint', $mode === 'tint', kpi_ui_swatch_svg('tint'), ht('Warna template'), ht('Abu-abu muda dengan sentuhan warna template'))
        . '<div class="theme own"><label class="theme-pick"><input type="radio" name="bg_mode" value="color"' . ($mode === 'color' ? ' checked' : '') . '>'
        . '<img src="' . h(kpi_ui_swatch_svg('color')) . '" alt="" width="160" height="96">'
        . '<span class="theme-name">' . ht('Warna sendiri') . '<small>' . ht('Di tampilan gelap warnanya dibuat lebih gelap') . '</small></span></label>'
        . '<label class="color-field"><span>' . ht('Pilih warna') . '</span><input type="color" name="app_color" value="' . h(strtolower(kpi_ui_app_bg_color())) . '"></label>'
        . '<input type="hidden" name="app_color_was" value="' . h(strtolower(kpi_ui_app_bg_color())) . '"></div>'
        . adm_ui_choice('bg_mode', 'image', $mode === 'image', $appImg ?? kpi_ui_swatch_svg('default'), ht('Gambar latar'),
            $appImg !== null ? ht('Gambar yang diunggah di atas') : ht('Unggah gambar dulu'))
        . '</div></fieldset>'
        . '<div class="grid2">' . adm_select(kpi_t('Kejelasan gambar latar'), 'veil', $veils, kpi_ui_veil(),
            kpi_t('Hanya untuk pilihan Gambar latar. Makin jelas gambarnya, makin sulit tulisan di atasnya dibaca.')) . '</div>'
        . '<div class="actions"><button class="btn primary" type="submit">' . ht('Simpan latar') . '</button></div></form></section>';

    return $html;
}

function adm_view_admins(array $ctx): string
{
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
        $rows .= '<tr><td><b>' . h($a['name']) . '</b>' . ((int)$a['id'] === $me ? ' <span class="muted small">' . ht('(Anda)') . '</span>' : '') . '</td>'
            . '<td>' . adm_pill(KPI_ADMIN_ROLES[$a['role']] ?? $a['role'], $a['role'] === 'sysadmin' ? 'sys' : 'infra') . '</td>'
            . '<td><code>' . h($a['username']) . '</code></td>'
            . '<td>' . adm_status_pill((bool)$a['is_active']) . '</td>'
            . '<td class="nowrap">' . adm_when($a['last_login_at']) . '</td>'
            . '<td class="right"><a class="btn" href="' . ADM_SELF . '?p=admin&amp;edit=' . (int)$a['id'] . '">' . ht('Ubah') . '</a></td></tr>';
    }
    return '<div class="toolbar"><p class="muted">' . ht('Akun admin terpisah dari akun anggota dan tidak punya KPI.') . '</p>'
        . '<a class="btn primary" href="' . ADM_SELF . '?p=admin&amp;new=1">' . ht('Tambah admin') . '</a></div>'
        . '<section class="card"><div class="tbl-wrap"><table><thead><tr><th>' . ht('Nama') . '</th><th>' . ht('Peran') . '</th><th>' . ht('Username') . '</th>'
        . '<th>' . ht('Status') . '</th><th>' . ht('Terakhir masuk') . '</th><th></th></tr></thead><tbody>'
        . $rows . '</tbody></table></div></section>'
        . '<section class="card"><h2>' . ht('Isi kedua halaman admin') . '</h2>'
        . '<p class="muted">' . ht('Semua akun admin yang aktif bisa masuk ke kedua halaman. Peran hanya menjadi label akun.') . '</p><div class="grid2 roles">'
        . '<div><h3>' . adm_pill('sysAdmin', 'sys') . '</h3><ul><li>' . ht('Menambah, mengubah, menonaktifkan, dan menghapus anggota') . '</li>'
        . '<li>' . ht('Mengganti password anggota dan membuka kunci login') . '</li>'
        . '<li>' . ht('Mengubah pengaturan aplikasi') . '</li><li>' . ht('Mengelola akun admin') . '</li></ul></div>'
        . '<div><h3>' . adm_pill('infraAdmin', 'infra') . '</h3><ul><li>' . ht('Memantau status server dan database') . '</li>'
        . '<li>' . ht('Mengunduh cadangan database') . '</li>'
        . '<li>' . ht('Menyalakan dan mematikan mode pemeliharaan') . '</li><li>' . ht('Membersihkan catatan aktivitas lama') . '</li></ul></div></div></section>';
}

function adm_admin_form(array $ctx, ?array $a): string
{
    $csrf = $ctx['csrf'];
    $old  = $ctx['old'];
    $me   = (int)$ctx['admin']['id'];
    $val  = fn(string $k, $def = '') => $old[$k] ?? $def;
    $back = adm_back_link(ADM_SELF . '?p=admin', 'Kembali ke daftar admin');

    $form = '<form class="card form" method="post" action="' . ADM_SELF . '" autocomplete="off">' . adm_csrf_field($csrf)
        . '<input type="hidden" name="_act" value="admin_save"><input type="hidden" name="_back" value="admin">'
        . ($a ? '<input type="hidden" name="id" value="' . (int)$a['id'] . '"><input type="hidden" name="_edit" value="' . (int)$a['id'] . '">'
              : '<input type="hidden" name="_new" value="1">')
        . '<h2>' . ($a ? ht('Ubah akun {0}', $a['name']) : ht('Tambah admin')) . '</h2><div class="grid2">'
        . adm_input(kpi_t('Nama'), 'name', $val('name', $a['name'] ?? ''), ['required' => true, 'maxlength' => 120])
        . adm_select(kpi_t('Peran'), 'role', KPI_ADMIN_ROLES, $val('role', $a['role'] ?? 'infraadmin'),
            kpi_t('Semua akun admin yang aktif bisa masuk ke kedua halaman. Peran hanya menjadi label akun.'))
        . adm_input(kpi_t('Username'), 'username', $val('username', $a['username'] ?? ''), ['required' => true, 'maxlength' => 64, 'autocapitalize' => 'none'])
        . adm_input($a ? kpi_t('Password baru') : kpi_t('Password'), 'password', '', ['type' => 'password', 'minlength' => KPI_ADMIN_MIN_PASS, 'required' => !$a,
            'hint' => $a ? kpi_t('Kosongkan bila tidak ingin mengganti password.') : kpi_t('Minimal {0} karakter.', KPI_ADMIN_MIN_PASS)])
        . '</div><div class="actions"><button class="btn primary" type="submit">' . ht('Simpan') . '</button>'
        . '<a class="btn" href="' . ADM_SELF . '?p=admin">' . ht('Batal') . '</a></div></form>';

    if (!$a || (int)$a['id'] === $me) {
        return $back . $form . ($a ? '<p class="muted small">' . ht('Akun Anda sendiri tidak bisa dinonaktifkan atau dihapus dari sini.') . '</p>' : '');
    }
    $id   = (int)$a['id'];
    $side = '<section class="card"><h2>' . ht('Status dan penghapusan') . '</h2><div class="actions">';
    if ($a['is_active']) {
        $side .= adm_confirm($csrf, kpi_t('Nonaktifkan'), kpi_t('Nonaktifkan akun admin {0}?', $a['username']), 'admin_toggle',
            ['id' => $id, 'active' => '0', '_back' => 'admin', '_edit' => $id], kpi_t('Ya, nonaktifkan'), 'warn');
    } else {
        $side .= '<form method="post" action="' . ADM_SELF . '">' . adm_csrf_field($csrf)
            . '<input type="hidden" name="_act" value="admin_toggle"><input type="hidden" name="id" value="' . $id . '"><input type="hidden" name="active" value="1">'
            . '<button class="btn primary" type="submit">' . ht('Aktifkan kembali') . '</button></form>';
    }
    $side .= adm_confirm($csrf, kpi_t('Hapus akun'), kpi_t('Hapus akun admin {0}? Tindakan ini tidak bisa dibatalkan.', $a['username']), 'admin_delete',
            ['id' => $id, '_back' => 'admin', '_edit' => $id], kpi_t('Ya, hapus'))
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
            . '<td><bdi>' . h($value) . '</bdi></td><td class="right">' . adm_pill(kpi_t($label[$state]), $state) . '</td></tr>';
    }
    $html = '<p class="lead">' . hx('Ini halaman {0}. Dari sini Anda memantau server, mengambil cadangan, dan mengatur mode pemeliharaan.', '<b>infraAdmin</b>') . '</p>'
        . '<div class="stats">'
        . adm_stat(kpi_t('Baik'), kpi_num($count['ok']), kpi_t('Pemeriksaan yang lolos'))
        . adm_stat(kpi_t('Perlu diperhatikan'), kpi_num($count['warn']), $count['warn'] ? kpi_t('Lihat saran di tabel') : kpi_t('Tidak ada'), $count['warn'] ? 'warn' : '')
        . adm_stat(kpi_t('Bermasalah'), kpi_num($count['bad']), $count['bad'] ? kpi_t('Segera perbaiki') : kpi_t('Tidak ada'), $count['bad'] ? 'bad' : '')
        . '</div>'
        . '<section class="card"><div class="card-h"><h2>' . ht('Pemeriksaan') . '</h2><a class="btn" href="' . ADM_SELF . '?p=status">' . ht('Periksa ulang') . '</a></div>'
        . '<div class="tbl-wrap"><table><thead><tr><th>' . ht('Yang diperiksa') . '</th><th>' . ht('Hasil') . '</th><th class="right">' . ht('Keadaan') . '</th></tr></thead>'
        . '<tbody>' . $rows . '</tbody></table></div></section>';

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
        $rows  .= '<tr><td><b>' . h(isset($names[$t['table']]) ? kpi_t($names[$t['table']]) : $t['table']) . '</b> <code>' . h($t['table']) . '</code></td>'
            . '<td class="right num">' . h(kpi_num($t['rows'])) . '</td><td class="right num"><bdi>' . h(kpi_bytes($t['bytes'])) . '</bdi></td></tr>';
    }
    return '<section class="card"><div class="card-h"><h2>' . ht('Isi database') . '</h2><span class="muted">' . ht('Total {0}', kpi_bytes($total)) . '</span></div>'
        . '<div class="tbl-wrap"><table><thead><tr><th>' . ht('Tabel') . '</th><th class="right">' . ht('Baris') . '</th><th class="right">' . ht('Ukuran') . '</th></tr></thead>'
        . '<tbody>' . $rows . '</tbody></table></div></section>';
}

function adm_view_backup(array $ctx): string
{
    $last = (string)kpi_setting('last_backup_at', '');
    return '<section class="card"><h2>' . ht('Unduh cadangan') . '</h2>'
        . '<p class="muted">' . ht('Berkas berisi seluruh isi database dalam bentuk SQL: anggota, KPI, realisasi, pengaturan, dan akun admin. Password tersimpan dalam bentuk acak (hash), tetapi berkas ini tetap rahasia. Simpan di tempat yang aman.') . '</p>'
        . '<p>' . hx('Cadangan terakhir diunduh: {0}', '<b>' . ($last !== '' ? adm_when($last) : ht('belum pernah')) . '</b>') . '</p>'
        . '<form method="post" action="' . ADM_SELF . '">' . adm_csrf_field($ctx['csrf'])
        . '<input type="hidden" name="_act" value="backup_sql"><input type="hidden" name="_back" value="cadangan">'
        . '<button class="btn primary" type="submit">' . ht('Unduh cadangan (.sql)') . '</button></form></section>'
        . '<section class="card"><h2>' . ht('Cara memulihkan') . '</h2><ol class="steps">'
        . '<li>' . ht('Nyalakan mode pemeliharaan supaya tidak ada yang mengisi data selama pemulihan.') . '</li>'
        . '<li>' . hx('Buka phpMyAdmin, pilih database aplikasi, lalu buka menu {0}.', '<b>Import</b>') . '</li>'
        . '<li>' . hx('Pilih berkas cadangan {0}, lalu klik {1}. Isi database diganti dengan isi cadangan.', '<code>.sql</code>', '<b>Import</b>') . '</li>'
        . '<li>' . ht('Matikan kembali mode pemeliharaan.') . '</li></ol>'
        . '<p class="muted small">' . ht('Pemulihan sengaja tidak disediakan sebagai tombol di halaman ini, supaya data tidak tertimpa karena salah klik.') . '</p></section>'
        . adm_tables_card();
}

function adm_view_maintenance(array $ctx): string
{
    $on  = kpi_setting('maintenance', '0') === '1';
    $msg = (string)kpi_setting('maintenance_msg', '');
    return '<section class="card"><h2>' . hx('Keadaan sekarang: {0}', $on ? adm_pill(kpi_t('Aktif'), 'warn') : adm_pill(kpi_t('Mati'), 'ok')) . '</h2>'
        . '<p class="muted">' . ht('Saat mode pemeliharaan aktif, aplikasi KPI menampilkan halaman pemberitahuan dan tidak ada yang bisa masuk atau mengisi data. Halaman admin ini tetap bisa dibuka. Pakai saat memulihkan cadangan atau memperbarui aplikasi.') . '</p>'
        . '<form class="form" method="post" action="' . ADM_SELF . '">' . adm_csrf_field($ctx['csrf'])
        . '<input type="hidden" name="_act" value="maintenance_save"><input type="hidden" name="_back" value="pemeliharaan">'
        . '<label class="check"><input type="checkbox" name="maintenance" value="1"' . ($on ? ' checked' : '') . '>'
        . '<span><b>' . ht('Tutup aplikasi sementara') . '</b><small>' . ht('Hilangkan centang untuk membuka aplikasi kembali.') . '</small></span></label>'
        . '<label class="field"><span>' . ht('Pesan untuk anggota') . '</span><textarea name="message" rows="3" maxlength="300" '
        . 'placeholder="' . ht('Aplikasi sedang dalam pemeliharaan. Silakan coba lagi beberapa saat lagi.') . '">' . h($msg) . '</textarea>'
        . '<span class="hint">' . ht('Contoh: Aplikasi sedang diperbarui dan bisa dipakai lagi pukul 13.00.') . '</span>'
        . '<span class="hint">' . ht('Bila dikosongkan, anggota melihat pesan bawaan dalam bahasa yang mereka pilih.') . '</span></label>'
        . '<div class="actions"><button class="btn primary" type="submit">' . ht('Simpan') . '</button>'
        . '<a class="btn" href="index.php" target="_blank" rel="noopener">' . ht('Lihat tampilan aplikasi') . '</a></div></form></section>';
}

/* ---------- Untuk kedua peran ---------- */

function adm_view_activity(array $ctx): string
{
    $rows = '';
    $list = kpi_activity(200);
    foreach ($list as $a) {
        $rows .= '<tr><td class="nowrap">' . adm_when($a['created_at']) . '</td><td>' . h($a['user_name'] ?? kpi_t('Admin atau sistem')) . '</td>'
            . '<td>' . h(isset(ADM_ACTION_NAMES[$a['action']]) ? kpi_t(ADM_ACTION_NAMES[$a['action']]) : $a['action']) . '</td>'
            . '<td class="muted"><bdi>' . h($a['detail']) . '</bdi></td></tr>';
    }
    $html = '<section class="card"><div class="card-h"><h2>' . ht('{0} kegiatan terakhir', kpi_num(200)) . '</h2>'
        . '<span class="muted">' . ht('{0} baris', kpi_num(count($list))) . '</span></div>'
        . '<div class="tbl-wrap"><table><thead><tr><th>' . ht('Waktu') . '</th><th>' . ht('Pelaku') . '</th><th>' . ht('Kegiatan') . '</th><th>' . ht('Rincian') . '</th></tr></thead><tbody>'
        . ($rows ?: '<tr><td colspan="4" class="muted">' . ht('Belum ada kegiatan.') . '</td></tr>') . '</tbody></table></div></section>';

    if ($ctx['panel'] === 'infraadmin') {
        $html .= '<section class="card"><h2>' . ht('Bersihkan catatan lama') . '</h2>'
            . '<p class="muted">' . ht('Catatan aktivitas terus bertambah. Hapus yang sudah lama supaya database tetap ringan. Data KPI tidak terpengaruh.') . '</p>'
            . '<form class="inline" method="post" action="' . ADM_SELF . '">' . adm_csrf_field($ctx['csrf'])
            . '<input type="hidden" name="_act" value="log_purge"><input type="hidden" name="_back" value="aktivitas">'
            . adm_select(kpi_t('Hapus catatan yang lebih lama dari'), 'days',
                [365 => kpi_t('1 tahun'), 180 => kpi_t('6 bulan'), 90 => kpi_t('3 bulan'), 30 => kpi_t('30 hari')], 365)
            . '<button class="btn danger-soft" type="submit">' . ht('Hapus catatan lama') . '</button></form></section>';
    }
    return $html;
}

function adm_view_account(array $ctx): string
{
    $a = $ctx['admin'];
    return '<section class="card"><dl class="facts">'
        . '<div><dt>' . ht('Nama') . '</dt><dd>' . h($a['name']) . '</dd></div>'
        . '<div><dt>' . ht('Username') . '</dt><dd><code>' . h($a['username']) . '</code></dd></div>'
        . '<div><dt>' . ht('Peran') . '</dt><dd>' . adm_pill(KPI_ADMIN_ROLES[$a['role']], $a['role'] === 'sysadmin' ? 'sys' : 'infra') . '</dd></div>'
        . '<div><dt>' . ht('Terakhir masuk') . '</dt><dd>' . adm_when($a['last_login_at']) . '</dd></div></dl></section>'
        . '<form class="card form" method="post" action="' . ADM_SELF . '" autocomplete="off">' . adm_csrf_field($ctx['csrf'])
        . '<input type="hidden" name="_act" value="password_change"><input type="hidden" name="_back" value="akun">'
        . '<h2>' . ht('Ganti password') . '</h2><div class="grid2">'
        . adm_input(kpi_t('Password saat ini'), 'current', '', ['type' => 'password', 'required' => true])
        . '<span></span>'
        . adm_input(kpi_t('Password baru'), 'password', '', ['type' => 'password', 'required' => true, 'minlength' => KPI_ADMIN_MIN_PASS,
            'hint' => kpi_t('Minimal {0} karakter.', KPI_ADMIN_MIN_PASS)])
        . adm_input(kpi_t('Ulangi password baru'), 'password2', '', ['type' => 'password', 'required' => true, 'minlength' => KPI_ADMIN_MIN_PASS])
        . '</div><div class="actions"><button class="btn primary" type="submit">' . ht('Ganti password') . '</button></div></form>';
}
