<?php
/**
 * Halaman admin, terpisah dari aplikasi KPI.
 *
 * Alamat: /admin.php. Akun dan sesinya berbeda dari akun anggota,
 * dan isi menunya mengikuti peran: sysAdmin atau infraAdmin.
 * Seluruh halaman dibuat di server tanpa JavaScript.
 */

declare(strict_types=1);

require __DIR__ . '/lib/db.php';
require __DIR__ . '/lib/schema.php';
require __DIR__ . '/lib/handlers.php';
require __DIR__ . '/lib/auth.php';
require __DIR__ . '/lib/admin.php';
require __DIR__ . '/lib/admin_views.php';

/** Halaman yang sedang dipakai: sysadmin.php, infraadmin.php, atau admin.php. */
define('ADM_SELF', KPI_ADMIN_GATE !== null ? KPI_ADMIN_LINKS[KPI_ADMIN_GATE] : 'admin.php');

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

/** Alamat kembali dari formulir: hanya halaman milik peran ini, dengan id angka. */
function adm_back(array $pages, string $fallback): string
{
    $page = (string)($_POST['_back'] ?? '');
    if (!isset($pages[$page])) {
        $page = $fallback;
    }
    $q = 'p=' . $page;
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
    $pages  = $admin ? KPI_ADMIN_PAGES[$admin['role']] : [];
    $home   = $admin ? (string)array_key_first($pages) : '';

    /* ================= Tindakan (POST) ================= */
    if ($method === 'POST') {
        $act = (string)($_POST['_act'] ?? '');
        if (!kpi_admin_check_csrf($_POST['_csrf'] ?? null)) {
            adm_flash('err', 'Sesi sudah berakhir. Silakan ulangi.');
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
                adm_flash('ok', 'Akun admin sudah dibuat. Silakan masuk.');
                adm_go();
            }

            if (!$admin) {
                adm_flash('err', 'Silakan masuk terlebih dahulu.');
                adm_go();
            }
            $aid  = (int)$admin['id'];
            $role = $admin['role'];
            $need = function (string $r) use ($role): void {
                if ($role !== $r) {
                    throw new KpiApiError('Tindakan ini hanya untuk ' . KPI_ADMIN_ROLES[$r] . '.', 403);
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
                    adm_flash('ok', 'Password Anda sudah diganti.');
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
                    ]);
                    kpi_admin_log($admin, 'user_save', "user=$id username=$username");
                    adm_flash('ok', 'Data anggota "' . $username . '" sudah disimpan.');
                    adm_go('p=anggota');
                    break;
                }
                case 'user_toggle': {
                    $need('sysadmin');
                    $on = !empty($_POST['active']);
                    $u  = kpi_user_set_active((int)($_POST['id'] ?? 0), $on);
                    kpi_admin_log($admin, $on ? 'user_aktif' : 'user_nonaktif', 'username=' . $u['username']);
                    adm_flash('ok', 'Akun "' . $u['username'] . '" ' . ($on ? 'diaktifkan kembali.' : 'dinonaktifkan dan tidak bisa masuk.'));
                    adm_go('p=anggota');
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
                    adm_flash('ok', 'Anggota "' . $uname . '" beserta KPI miliknya sudah dihapus.');
                    adm_go('p=anggota');
                    break;
                }
                case 'user_unlock': {
                    $need('sysadmin');
                    $ident = (string)($_POST['ident'] ?? '');
                    kpi_unlock($ident);
                    kpi_admin_log($admin, 'buka_kunci', strtok($ident, '|') ?: '');
                    adm_flash('ok', 'Kunci login sudah dibuka.');
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
                    adm_flash('ok', 'Pengaturan aplikasi sudah disimpan.');
                    adm_go('p=pengaturan');
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
                    adm_flash('ok', 'Akun admin "' . $row['username'] . '" sudah disimpan.');
                    adm_go('p=admin');
                    break;
                }
                case 'admin_toggle': {
                    $need('sysadmin');
                    $on  = !empty($_POST['active']);
                    $row = kpi_admin_set_active((int)($_POST['id'] ?? 0), $on, $aid);
                    kpi_admin_log($admin, $on ? 'admin_aktif' : 'admin_nonaktif', $row['username']);
                    adm_flash('ok', 'Akun admin "' . $row['username'] . '" ' . ($on ? 'diaktifkan kembali.' : 'dinonaktifkan.'));
                    adm_go('p=admin');
                    break;
                }
                case 'admin_delete': {
                    $need('sysadmin');
                    $row = kpi_admin_delete((int)($_POST['id'] ?? 0), $aid);
                    kpi_admin_log($admin, 'admin_delete', $row['username']);
                    adm_flash('ok', 'Akun admin "' . $row['username'] . '" sudah dihapus.');
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
                    adm_flash('ok', $on ? 'Mode pemeliharaan aktif. Aplikasi ditutup sementara untuk semua anggota.'
                                        : 'Mode pemeliharaan dimatikan. Aplikasi bisa dipakai kembali.');
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
                    adm_flash('ok', $n . ' catatan yang lebih lama dari ' . $days . ' hari sudah dihapus.');
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
    $ctx = ['csrf' => $csrf, 'old' => is_array($old) ? $old : [], 'admin' => $admin, 'get' => $_GET];

    switch ($page) {
        case 'ringkasan':    $body = adm_view_overview($ctx); break;
        case 'anggota':      $body = adm_view_users($ctx); break;
        case 'pengaturan':   $body = adm_view_settings($ctx); break;
        case 'admin':        $body = adm_view_admins($ctx); break;
        case 'status':       $body = adm_view_status($ctx); break;
        case 'cadangan':     $body = adm_view_backup($ctx); break;
        case 'pemeliharaan': $body = adm_view_maintenance($ctx); break;
        case 'aktivitas':    $body = adm_view_activity($ctx); break;
        default:             $body = adm_view_account($ctx); break;
    }
    echo adm_page($admin, $pages, $page, $body, $flash, $csrf);
} catch (Throwable $e) {
    error_log('[KPI admin] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
       . '<title>Halaman admin bermasalah</title><link rel="stylesheet" href="assets/admin.css">'
       . '<main class="guest"><div class="card"><h1>Halaman admin tidak dapat dibuka</h1>'
       . '<p class="muted">Aplikasi tidak dapat terhubung ke database, atau terjadi galat di server. '
       . 'Pastikan MySQL menyala, lalu muat ulang halaman ini. Rincian teknis dicatat di log error server.</p></div></main>';
}
