<?php
/**
 * Halaman admin, terpisah dari aplikasi KPI.
 *
 * Alamat: /admin.php. Akun dan sesinya berbeda dari akun anggota,
 * dan isi menunya mengikuti peran: sysAdmin atau infraAdmin.
 * Seluruh halaman dibuat di server tanpa JavaScript.
 */

declare(strict_types=1);

// Terjemahan halaman admin ikut dimuat oleh kpi_t() (lib/i18n.php).
const KPI_I18N_ADMIN = true;

require __DIR__ . '/lib/db.php';
require __DIR__ . '/lib/schema.php';
require __DIR__ . '/lib/handlers.php';
require __DIR__ . '/lib/auth.php';
require __DIR__ . '/lib/admin.php';
require __DIR__ . '/lib/ui.php';
require __DIR__ . '/lib/admin_views.php';

/**
 * Halaman yang sedang dipakai, untuk semua tautan, formulir, dan pengalihan:
 * sysAdmin atau infraAdmin bila dibuka lewat alamat pendek, selain itu nama filenya.
 */
$admPath = (string)parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
if (KPI_ADMIN_GATE !== null && preg_match('#/' . KPI_ADMIN_LINKS[KPI_ADMIN_GATE] . '/+$#i', $admPath)) {
    // /sysAdmin/ dialihkan ke /sysAdmin supaya alamat gambar dan gaya tetap benar.
    $qs = (string)($_SERVER['QUERY_STRING'] ?? '');
    header('Location: ' . rtrim($admPath, '/') . ($qs !== '' ? '?' . $qs : ''), true, 301);
    exit;
}
define('ADM_SELF', KPI_ADMIN_GATE === null ? 'admin.php'
    : (preg_match('#\.php$#i', $admPath) ? KPI_ADMIN_FILES[KPI_ADMIN_GATE] : KPI_ADMIN_LINKS[KPI_ADMIN_GATE]));

if (!kpi_is_installed()) {
    header('Location: install.php');
    exit;
}

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');
header('Cache-Control: no-store');
header("Content-Security-Policy: default-src 'self'; script-src 'none'; style-src 'self' https://fonts.googleapis.com; "
     . "font-src https://fonts.gstatic.com; img-src 'self' data:; form-action 'self'; frame-ancestors 'none'; base-uri 'none'");

function adm_go(string $query = ''): void
{
    header('Location: ' . ADM_SELF . ($query !== '' ? '?' . $query : ''), true, 303);
    exit;
}

function adm_flash(string $type, string $msg): void
{
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

/**
 * Ganti bahasa lewat tautan ?lang=xx dari menu bahasa. Pilihan disimpan di cookie kpi_lang
 * dengan path folder aplikasi, sama seperti yang dibuat assets/app.js, jadi aplikasi KPI
 * dan halaman admin selalu memakai bahasa yang sama.
 */
function adm_set_lang(string $code, string $path): void
{
    if (!isset(KPI_LANGS[$code])) {
        return;
    }
    $dir = substr($path, 0, (int)strrpos($path, '/') + 1);
    if (!preg_match('#^/[A-Za-z0-9._~/%-]*$#', $dir)) {
        $dir = '/';
    }
    setcookie(KPI_LANG_COOKIE, $code, ['expires' => time() + 31536000, 'path' => $dir, 'samesite' => 'Lax']);
    $_COOKIE[KPI_LANG_COOKIE] = $code;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' && isset($_GET['lang']) && is_string($_GET['lang'])) {
    adm_set_lang($_GET['lang'], $admPath);
    $keep = [];
    foreach (['p', 'edit', 'new', 'q', 'status'] as $k) {
        if (isset($_GET[$k]) && is_string($_GET[$k]) && $_GET[$k] !== '') {
            $keep[$k] = $_GET[$k];
        }
    }
    adm_go(http_build_query($keep));
}

/** Submenu anggota (aktif atau tidak aktif) yang sedang dibuka saat formulir dikirim. */
function adm_status_q(): string
{
    $s = adm_member_status($_POST['_status'] ?? '');
    return $s !== '' ? '&status=' . $s : '';
}

/** Alamat kembali dari formulir: hanya halaman milik peran ini, dengan id angka. */
function adm_back(array $pages, string $fallback): string
{
    $page = (string)($_POST['_back'] ?? '');
    if (!isset($pages[$page])) {
        $page = $fallback;
    }
    $q = 'p=' . $page . adm_status_q();
    if (isset($_POST['_edit']) && ctype_digit((string)$_POST['_edit'])) {
        $q .= '&edit=' . $_POST['_edit'];
    } elseif (!empty($_POST['_new'])) {
        $q .= '&new=1';
    }
    return $q;
}

try {
    kpi_admin_ensure_schema();
    kpi_admin_session_start();

    $admin  = kpi_admin_current();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $panel  = $admin ? kpi_admin_panel($admin) : '';
    $pages  = $admin ? KPI_ADMIN_PAGES[$panel] : [];
    $home   = $admin ? (string)array_key_first($pages) : '';

    /* ================= Tindakan (POST) ================= */
    if ($method === 'POST') {
        $act = (string)($_POST['_act'] ?? '');
        // Unggahan yang melebihi post_max_size sampai tanpa isi sama sekali (juga tanpa token).
        if ($_POST === [] && $_FILES === [] && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
            adm_flash('err', kpi_t('Gambar terlalu besar. Ukuran paling besar {0} MB.', kpi_ui_limit_mb()));
            adm_go($admin && $panel === 'sysadmin' ? 'p=tampilan' : '');
        }
        if (!kpi_admin_check_csrf($_POST['_csrf'] ?? null)) {
            adm_flash('err', kpi_t('Sesi sudah berakhir. Silakan ulangi.'));
            adm_go();
        }

        try {
            /* --- tanpa login --- */
            if ($act === 'login') {
                kpi_admin_login((string)($_POST['username'] ?? ''), (string)($_POST['password'] ?? ''));
                adm_go();
            }
            if ($act === 'first_admin') {
                kpi_admin_bootstrap($_POST);
                adm_flash('ok', kpi_t('Akun admin sudah dibuat. Silakan masuk.'));
                adm_go();
            }

            if (!$admin) {
                adm_flash('err', kpi_t('Silakan masuk terlebih dahulu.'));
                adm_go();
            }
            $aid  = (int)$admin['id'];
            // Tindakan mengikuti halaman yang sedang dibuka, bukan peran akun.
            $need = function (string $r) use ($panel): void {
                if ($panel !== $r) {
                    throw new KpiApiError('Tindakan ini hanya bisa dilakukan di halaman {0}.', 403, [KPI_ADMIN_ROLES[$r]]);
                }
            };

            switch ($act) {
                case 'logout':
                    kpi_admin_log($admin, 'admin_logout', '');
                    kpi_admin_logout();
                    adm_go();
                    break;

                case 'password_change': {
                    $cur = (string)($_POST['current'] ?? '');
                    $new = (string)($_POST['password'] ?? '');
                    if (!password_verify($cur, $admin['password_hash'])) {
                        usleep(300000);
                        throw new KpiApiError('Password saat ini salah.');
                    }
                    kpi_admin_check_password($new);
                    if ($new !== (string)($_POST['password2'] ?? '')) {
                        throw new KpiApiError('Ulangi password baru dengan isi yang sama.');
                    }
                    db()->prepare('UPDATE admins SET password_hash = ? WHERE id = ?')
                        ->execute([password_hash($new, PASSWORD_DEFAULT), $aid]);
                    session_regenerate_id(true);
                    kpi_admin_log($admin, 'admin_ganti_password', '');
                    adm_flash('ok', kpi_t('Password Anda sudah diganti.'));
                    adm_go('p=akun');
                    break;
                }

                /* --- sysAdmin --- */
                case 'user_save': {
                    $need('sysadmin');
                    [$id, $username] = kpi_user_save([
                        'id'       => $_POST['id'] ?? '',
                        'name'     => $_POST['name'] ?? '',
                        'title'    => $_POST['title'] ?? '',
                        'dept'     => $_POST['dept'] ?? '',
                        'username' => $_POST['username'] ?? '',
                        'password' => $_POST['password'] ?? '',
                        'parentId' => $_POST['parentId'] ?? '',
                        'role'     => isset($_POST['role']) ? (string)$_POST['role'] : null,
                    ], true);
                    $role = kpi_role(kpi_all_users()[$id] ?? []);
                    kpi_admin_log($admin, 'user_save', "user=$id username=$username role=$role");
                    adm_flash('ok', kpi_t('Data anggota "{0}" sudah disimpan.', $username));
                    adm_go('p=anggota' . adm_status_q());
                    break;
                }
                case 'user_toggle': {
                    $need('sysadmin');
                    $on = !empty($_POST['active']);
                    $u  = kpi_user_set_active((int)($_POST['id'] ?? 0), $on);
                    kpi_admin_log($admin, $on ? 'user_aktif' : 'user_nonaktif', 'username=' . $u['username']);
                    adm_flash('ok', $on ? kpi_t('Akun "{0}" diaktifkan kembali.', $u['username'])
                                        : kpi_t('Akun "{0}" dinonaktifkan dan tidak bisa masuk.', $u['username']));
                    adm_go('p=anggota' . adm_status_q());
                    break;
                }
                case 'user_delete': {
                    $need('sysadmin');
                    $id    = (int)($_POST['id'] ?? 0);
                    $users = kpi_all_users();
                    if (isset($users[$id]) && $users[$id]['level'] === 'ceo') {
                        throw new KpiApiError('Akun CEO tidak dapat dihapus.');
                    }
                    $uname = $users[$id]['username'] ?? '';
                    kpi_user_delete($id);
                    kpi_admin_log($admin, 'user_delete', "user=$id username=$uname");
                    adm_flash('ok', kpi_t('Anggota "{0}" beserta KPI miliknya sudah dihapus.', $uname));
                    adm_go('p=anggota' . adm_status_q());
                    break;
                }
                case 'user_unlock': {
                    $need('sysadmin');
                    $ident = (string)($_POST['ident'] ?? '');
                    kpi_unlock($ident);
                    kpi_admin_log($admin, 'buka_kunci', strtok($ident, '|') ?: '');
                    adm_flash('ok', kpi_t('Kunci login sudah dibuka.'));
                    adm_go('p=anggota');
                    break;
                }
                case 'settings_save': {
                    $need('sysadmin');
                    $company = in_str($_POST, 'company', 120);
                    $cap     = in_num($_POST, 'cap', 120.0);
                    $year    = (int)in_num($_POST, 'year', (float)kpi_year());
                    if ($company === '') {
                        throw new KpiApiError('Nama perusahaan wajib diisi.');
                    }
                    if ($cap < 100 || $cap > 200) {
                        throw new KpiApiError('Batas capaian harus antara 100 dan 200 persen.');
                    }
                    if ($year < 2000 || $year > 2100) {
                        throw new KpiApiError('Tahun tidak valid.');
                    }
                    kpi_set_setting('company', $company);
                    kpi_set_setting('cap', $cap);
                    kpi_set_setting('year', $year);
                    kpi_set_setting('demo_mode', !empty($_POST['demo_mode']) ? '1' : '0');
                    kpi_admin_log($admin, 'settings_save', '');
                    adm_flash('ok', kpi_t('Pengaturan aplikasi sudah disimpan.'));
                    adm_go('p=pengaturan');
                    break;
                }
                case 'ui_theme_save': {
                    $need('sysadmin');
                    $theme = (string)($_POST['theme'] ?? '');
                    $color = strtoupper(trim((string)($_POST['color'] ?? '')));
                    if (!preg_match('/^#[0-9A-F]{6}$/', $color)) {
                        throw new KpiApiError('Warna tidak valid.');
                    }
                    // Warna sendiri yang diubah tanpa memilih "Warna sendiri" tetap dianggap pilihan.
                    if ($theme !== 'custom' && $color !== strtoupper((string)($_POST['color_was'] ?? ''))) {
                        $theme = 'custom';
                    }
                    if ($theme !== 'custom' && !isset(KPI_UI_THEMES[$theme])) {
                        throw new KpiApiError('Pilih salah satu warna template.');
                    }
                    kpi_set_setting('ui_theme', $theme);
                    if ($theme === 'custom') {
                        kpi_set_setting('ui_color', $color);
                    }
                    kpi_admin_log($admin, 'tampilan_ubah', 'warna template: ' . ($theme === 'custom' ? $color : $theme));
                    adm_flash('ok', kpi_t('Warna template sudah disimpan. Muat ulang aplikasi KPI untuk melihat hasilnya.'));
                    adm_go('p=tampilan');
                    break;
                }
                case 'ui_upload': {
                    $need('sysadmin');
                    $slot = (string)($_POST['slot'] ?? '');
                    kpi_ui_upload($slot, $_FILES['image'] ?? null);
                    if ($slot === 'app_bg') {
                        kpi_set_setting('ui_app_bg_mode', 'image'); // gambar latar langsung dipakai
                    }
                    kpi_admin_log($admin, 'tampilan_ubah', 'gambar diunggah: ' . $slot);
                    adm_flash('ok', kpi_t('Gambar sudah dipasang. Muat ulang aplikasi KPI untuk melihat hasilnya.'));
                    adm_go('p=tampilan#ui-' . $slot);
                    break;
                }
                case 'ui_remove': {
                    $need('sysadmin');
                    $slot = (string)($_POST['slot'] ?? '');
                    kpi_ui_remove($slot);
                    if ($slot === 'app_bg' && kpi_setting('ui_app_bg_mode', 'default') === 'image') {
                        kpi_set_setting('ui_app_bg_mode', 'default');
                    }
                    kpi_admin_log($admin, 'tampilan_ubah', 'gambar bawaan dipakai lagi: ' . $slot);
                    adm_flash('ok', kpi_t('Gambar bawaan dipakai kembali.'));
                    adm_go('p=tampilan#ui-' . $slot);
                    break;
                }
                case 'ui_login_save': {
                    $need('sysadmin');
                    $show = !empty($_POST['show_art']);
                    kpi_set_setting('ui_login_art_show', $show ? '1' : '0');
                    kpi_admin_log($admin, 'tampilan_ubah', 'ilustrasi halaman masuk: ' . ($show ? 'tampil' : 'disembunyikan'));
                    adm_flash('ok', kpi_t('Pengaturan halaman masuk sudah disimpan.'));
                    adm_go('p=tampilan#ui-login');
                    break;
                }
                case 'ui_app_save': {
                    $need('sysadmin');
                    $mode = (string)($_POST['bg_mode'] ?? '');
                    $veil = (string)($_POST['veil'] ?? 'soft');
                    if (!in_array($mode, KPI_UI_APP_BG_MODES, true) || !isset(KPI_UI_VEILS[$veil])) {
                        throw new KpiApiError('Pilih salah satu latar.');
                    }
                    if ($mode === 'image' && kpi_ui_image('app_bg') === null) {
                        throw new KpiApiError('Unggah gambar latar aplikasi terlebih dahulu.');
                    }
                    kpi_set_setting('ui_app_bg_mode', $mode);
                    kpi_set_setting('ui_app_bg_veil', $veil);
                    kpi_admin_log($admin, 'tampilan_ubah', 'latar aplikasi: ' . $mode . ($mode === 'image' ? ', ' . $veil : ''));
                    adm_flash('ok', kpi_t('Latar aplikasi sudah disimpan.'));
                    adm_go('p=tampilan#ui-app');
                    break;
                }
                case 'admin_save': {
                    $need('sysadmin');
                    $id = kpi_admin_save([
                        'id'       => $_POST['id'] ?? '',
                        'name'     => $_POST['name'] ?? '',
                        'username' => $_POST['username'] ?? '',
                        'role'     => $_POST['role'] ?? '',
                        'password' => $_POST['password'] ?? '',
                    ]);
                    $row = kpi_admin_find($id);
                    kpi_admin_log($admin, 'admin_save', $row['username'] . ' sebagai ' . KPI_ADMIN_ROLES[$row['role']]);
                    adm_flash('ok', kpi_t('Akun admin "{0}" sudah disimpan.', $row['username']));
                    adm_go('p=admin');
                    break;
                }
                case 'admin_toggle': {
                    $need('sysadmin');
                    $on  = !empty($_POST['active']);
                    $row = kpi_admin_set_active((int)($_POST['id'] ?? 0), $on, $aid);
                    kpi_admin_log($admin, $on ? 'admin_aktif' : 'admin_nonaktif', $row['username']);
                    adm_flash('ok', $on ? kpi_t('Akun admin "{0}" diaktifkan kembali.', $row['username'])
                                        : kpi_t('Akun admin "{0}" dinonaktifkan.', $row['username']));
                    adm_go('p=admin');
                    break;
                }
                case 'admin_delete': {
                    $need('sysadmin');
                    $row = kpi_admin_delete((int)($_POST['id'] ?? 0), $aid);
                    kpi_admin_log($admin, 'admin_delete', $row['username']);
                    adm_flash('ok', kpi_t('Akun admin "{0}" sudah dihapus.', $row['username']));
                    adm_go('p=admin');
                    break;
                }

                /* --- infraAdmin --- */
                case 'maintenance_save': {
                    $need('infraadmin');
                    $on = !empty($_POST['maintenance']);
                    kpi_set_setting('maintenance', $on ? '1' : '0');
                    kpi_set_setting('maintenance_msg', in_str($_POST, 'message', 300));
                    kpi_admin_log($admin, $on ? 'pemeliharaan_aktif' : 'pemeliharaan_mati', '');
                    adm_flash('ok', $on ? kpi_t('Mode pemeliharaan aktif. Aplikasi ditutup sementara untuk semua anggota.')
                                        : kpi_t('Mode pemeliharaan dimatikan. Aplikasi bisa dipakai kembali.'));
                    adm_go('p=pemeliharaan');
                    break;
                }
                case 'log_purge': {
                    $need('infraadmin');
                    $days = (int)($_POST['days'] ?? 0);
                    if (!in_array($days, [30, 90, 180, 365], true)) {
                        throw new KpiApiError('Pilih batas umur catatan.');
                    }
                    $n = kpi_activity_purge($days);
                    kpi_admin_log($admin, 'catatan_dibersihkan', "$n baris, lebih lama dari $days hari");
                    adm_flash('ok', kpi_t('{0} catatan yang lebih lama dari {1} hari sudah dihapus.', kpi_num($n), $days));
                    adm_go('p=aktivitas');
                    break;
                }
                case 'backup_sql': {
                    $need('infraadmin');
                    kpi_set_setting('last_backup_at', date('Y-m-d H:i'));
                    kpi_admin_log($admin, 'cadangan_diunduh', '');
                    header('Content-Type: application/sql; charset=utf-8');
                    header('Content-Disposition: attachment; filename="cadangan-kpi-' . date('Y-m-d-Hi') . '.sql"');
                    kpi_sql_dump_output();
                    exit;
                }

                default:
                    throw new KpiApiError('Tindakan tidak dikenal.', 404);
            }
        } catch (KpiApiError $e) {
            adm_flash('err', $e->getMessage());
            $keep = $_POST;
            foreach (array_keys($keep) as $k) {
                if (stripos((string)$k, 'pass') !== false || $k === 'current' || str_starts_with((string)$k, '_')) {
                    unset($keep[$k]);
                }
            }
            $_SESSION['old'] = $keep;
            adm_go($admin ? adm_back($pages, $home) : '');
        }
        exit;
    }

    /* ================= Tampilan (GET) ================= */
    $flash = $_SESSION['flash'] ?? null;
    $old   = $_SESSION['old'] ?? [];
    unset($_SESSION['flash'], $_SESSION['old']);
    $csrf  = kpi_admin_csrf();

    header('Content-Type: text/html; charset=utf-8');

    if (!$admin) {
        $first = kpi_admin_count_sys() === 0;
        echo adm_page_guest($first ? adm_view_first($csrf, $old) : adm_view_login($csrf, $old), $flash, $first);
        exit;
    }

    $page = (string)($_GET['p'] ?? $home);
    if (!isset($pages[$page])) {
        $page = $home;
    }
    $ctx = ['csrf' => $csrf, 'old' => is_array($old) ? $old : [], 'admin' => $admin, 'panel' => $panel, 'get' => $_GET];

    switch ($page) {
        case 'ringkasan':    $body = adm_view_overview($ctx); break;
        case 'anggota':      $body = adm_view_users($ctx); break;
        case 'pengaturan':   $body = adm_view_settings($ctx); break;
        case 'tampilan':     $body = adm_view_appearance($ctx); break;
        case 'admin':        $body = adm_view_admins($ctx); break;
        case 'status':       $body = adm_view_status($ctx); break;
        case 'cadangan':     $body = adm_view_backup($ctx); break;
        case 'pemeliharaan': $body = adm_view_maintenance($ctx); break;
        case 'aktivitas':    $body = adm_view_activity($ctx); break;
        default:             $body = adm_view_account($ctx); break;
    }
    echo adm_page($admin, $panel, $pages, $page, $body, $flash, $csrf);
} catch (Throwable $e) {
    error_log('[KPI admin] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    header('Content-Type: text/html; charset=utf-8');
    $tr = static fn(string $s): string => htmlspecialchars(kpi_t($s), ENT_QUOTES);
    echo '<!doctype html><html lang="' . kpi_lang_html() . '" dir="' . kpi_lang_dir() . '"><meta charset="utf-8">'
       . '<meta name="viewport" content="width=device-width, initial-scale=1">'
       . '<title>' . $tr('Halaman admin bermasalah') . '</title><link rel="stylesheet" href="assets/admin.css">'
       . '<main class="guest"><div class="card"><h1>' . $tr('Halaman admin tidak dapat dibuka') . '</h1>'
       . '<p class="muted">' . $tr('Aplikasi tidak dapat terhubung ke database, atau terjadi galat di server. Pastikan MySQL menyala, lalu muat ulang halaman ini. Rincian teknis dicatat di log error server.') . '</p></div></main>';
}
