<?php
/**
 * Tampilan aplikasi yang diatur sysAdmin di menu Tampilan: warna template, logo,
 * gambar dan latar halaman masuk, serta latar aplikasi setelah masuk.
 *
 * Pilihan disimpan di tabel settings (kunci ui_*). Gambar disimpan sebagai file di folder
 * uploads/ dengan nama buatan aplikasi, bukan di database, supaya tidak terbentur batas
 * ukuran paket MySQL (XAMPP memakai 1 MB).
 */

/** Warna template siap pakai: kunci => [nama, warna dasar]. Kunci pertama adalah bawaan. */
const KPI_UI_THEMES = [
    'navy'   => ['Biru navy', '#2E3D8F'],
    'biru'   => ['Biru cerah', '#1D6FD6'],
    'toska'  => ['Toska', '#0E7C86'],
    'hijau'  => ['Hijau', '#18794E'],
    'merah'  => ['Merah marun', '#A3203A'],
    'ungu'   => ['Ungu', '#6A3FB5'],
    'oranye' => ['Oranye', '#C2560F'],
    'grafit' => ['Abu-abu grafit', '#3F4756'],
];
const KPI_UI_DEFAULT_THEME = 'navy';

/** Tempat gambar: kunci => awalan nama file. */
const KPI_UI_SLOTS = [
    'logo'       => 'logo',
    'login_logo' => 'login-logo',
    'login_art' => 'login-art',
    'login_bg'  => 'login-bg',
    'app_bg'    => 'app-bg',
];

/** Jenis gambar yang diterima: konstanta IMAGETYPE_* => ekstensi file. */
const KPI_UI_TYPES = [
    IMAGETYPE_PNG  => 'png',
    IMAGETYPE_JPEG => 'jpg',
    IMAGETYPE_WEBP => 'webp',
    IMAGETYPE_GIF  => 'gif',
];
const KPI_UI_MAX_MB = 5;

/** Latar aplikasi setelah masuk. */
const KPI_UI_APP_BG_MODES = ['default', 'tint', 'color', 'image'];
/** Warna awal untuk pilihan "Warna sendiri" di latar aplikasi. */
const KPI_UI_APP_BG_COLOR = '#DCE6F5';
/** Kejelasan gambar latar aplikasi: seberapa tebal lapisan warna di atasnya. */
const KPI_UI_VEILS = ['soft' => 0.88, 'medium' => 0.74, 'strong' => 0.55];

const KPI_UI_FILE_RE = '/^[a-z-]+-[0-9a-f]{12}\.(png|jpg|webp|gif)$/';

/* ---------- Warna ---------- */

function kpi_ui_rgb(string $hex): array
{
    $h = ltrim($hex, '#');
    return [hexdec(substr($h, 0, 2)), hexdec(substr($h, 2, 2)), hexdec(substr($h, 4, 2))];
}

function kpi_ui_hex(array $rgb): string
{
    return sprintf('#%02X%02X%02X', ...array_map(static fn($v) => max(0, min(255, (int)round($v))), $rgb));
}

/** Campuran dua warna; $wa = bagian warna $a (0 sampai 1). */
function kpi_ui_mix(string $a, string $b, float $wa): string
{
    $x = kpi_ui_rgb($a);
    $y = kpi_ui_rgb($b);
    return kpi_ui_hex([$x[0] * $wa + $y[0] * (1 - $wa), $x[1] * $wa + $y[1] * (1 - $wa), $x[2] * $wa + $y[2] * (1 - $wa)]);
}

/** [hue 0-360, saturasi 0-1, terang 0-1] */
function kpi_ui_hsl(string $hex): array
{
    [$r, $g, $b] = array_map(static fn($v) => $v / 255, kpi_ui_rgb($hex));
    $max = max($r, $g, $b);
    $min = min($r, $g, $b);
    $l   = ($max + $min) / 2;
    $d   = $max - $min;
    if ($d < 1e-9) {
        return [0.0, 0.0, $l];
    }
    $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);
    if ($max === $r) {
        $h = ($g - $b) / $d + ($g < $b ? 6 : 0);
    } elseif ($max === $g) {
        $h = ($b - $r) / $d + 2;
    } else {
        $h = ($r - $g) / $d + 4;
    }
    return [$h * 60, $s, $l];
}

function kpi_ui_from_hsl(float $h, float $s, float $l): string
{
    $h = fmod(fmod($h, 360) + 360, 360) / 360;
    $s = max(0.0, min(1.0, $s));
    $l = max(0.0, min(1.0, $l));
    if ($s == 0.0) {
        return kpi_ui_hex([$l * 255, $l * 255, $l * 255]);
    }
    $q = $l < 0.5 ? $l * (1 + $s) : $l + $s - $l * $s;
    $p = 2 * $l - $q;
    $f = static function (float $t) use ($p, $q): float {
        if ($t < 0) {
            $t += 1;
        }
        if ($t > 1) {
            $t -= 1;
        }
        if ($t < 1 / 6) {
            return $p + ($q - $p) * 6 * $t;
        }
        if ($t < 1 / 2) {
            return $q;
        }
        if ($t < 2 / 3) {
            return $p + ($q - $p) * (2 / 3 - $t) * 6;
        }
        return $p;
    };
    return kpi_ui_hex([$f($h + 1 / 3) * 255, $f($h) * 255, $f($h - 1 / 3) * 255]);
}

function kpi_ui_luminance(string $hex): float
{
    $c = array_map(static function ($v) {
        $v /= 255;
        return $v <= 0.03928 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4;
    }, kpi_ui_rgb($hex));
    return 0.2126 * $c[0] + 0.7152 * $c[1] + 0.0722 * $c[2];
}

function kpi_ui_contrast(string $a, string $b): float
{
    $x = kpi_ui_luminance($a);
    $y = kpi_ui_luminance($b);
    return (max($x, $y) + 0.05) / (min($x, $y) + 0.05);
}

/** Menggelapkan (atau, bila $lighten, menerangkan) warna sampai cukup terbaca di atas $bg. */
function kpi_ui_readable(string $hex, string $bg, float $ratio, bool $lighten = false): string
{
    [$h, $s, $l] = kpi_ui_hsl($hex);
    for ($i = 0; $i < 60 && kpi_ui_contrast($hex, $bg) < $ratio; $i++) {
        $l   = $lighten ? min(1.0, $l + 0.02) : max(0.0, $l - 0.02);
        $hex = kpi_ui_from_hsl($h, $s, $l);
    }
    return $hex;
}

/** Warna dasar template yang sedang dipakai. */
function kpi_ui_color(): string
{
    $key = (string)kpi_setting('ui_theme', KPI_UI_DEFAULT_THEME);
    if ($key === 'custom') {
        $c = (string)kpi_setting('ui_color', '');
        if (preg_match('/^#[0-9A-Fa-f]{6}$/', $c)) {
            return strtoupper($c);
        }
    }
    return KPI_UI_THEMES[$key][1] ?? KPI_UI_THEMES[KPI_UI_DEFAULT_THEME][1];
}

/** Kunci template yang sedang dipakai: salah satu kunci KPI_UI_THEMES atau 'custom'. */
function kpi_ui_theme_key(): string
{
    $key = (string)kpi_setting('ui_theme', KPI_UI_DEFAULT_THEME);
    return $key === 'custom' || isset(KPI_UI_THEMES[$key]) ? $key : KPI_UI_DEFAULT_THEME;
}

/**
 * Semua warna turunan dari satu warna dasar, untuk tampilan terang dan gelap.
 * Template bawaan (navy) memakai warna persis seperti di assets/app.css.
 */
function kpi_ui_palette(string $base): array
{
    if (strcasecmp($base, KPI_UI_THEMES[KPI_UI_DEFAULT_THEME][1]) === 0) {
        return [
            'light' => ['accent' => '#2E3D8F', 'accent-soft' => '#E5E8F6', 'lg-a' => '#1F4790', 'lg-b' => '#152E63', 'lg-c' => '#0E1C40',
                        'lg-muted' => '#C3CEE8', 'lg-btn' => '#12244F', 'lg-link' => '#1D5BBF'],
            'dark'  => ['accent' => '#9AA8F2', 'accent-soft' => '#252C52', 'lg-btn' => '#9AA8F2', 'lg-link' => '#8FB4FF'],
            'hue'   => 222.0, 'sat' => 0.6,
        ];
    }
    [$h, $s, $l] = kpi_ui_hsl($base);
    $s7 = min($s, 0.70);
    // Panel halaman masuk mengikuti terang warna dasar, tetapi tetap cukup gelap untuk tulisan putih.
    $la = max(0.30, min($l, 0.44));
    $accent = kpi_ui_readable($base, '#FFFFFF', 5.0);
    $dark   = kpi_ui_readable(kpi_ui_from_hsl($h, min($s, 0.75), 0.74), '#161A2C', 6.0, true);
    return [
        'light' => [
            'accent'      => $accent,
            'accent-soft' => kpi_ui_mix($accent, '#FFFFFF', 0.12),
            'lg-a'        => kpi_ui_from_hsl($h, $s7, $la),
            'lg-b'        => kpi_ui_from_hsl($h, $s7, $la - 0.10),
            'lg-c'        => kpi_ui_from_hsl($h, $s7, $la - 0.18),
            'lg-muted'    => kpi_ui_from_hsl($h, min($s, 0.5), 0.86),
            'lg-btn'      => kpi_ui_from_hsl($h, min($s, 0.65), 0.19),
            'lg-link'     => kpi_ui_readable(kpi_ui_from_hsl($h, min($s, 0.75), 0.45), '#F3F4F8', 4.8),
        ],
        'dark' => [
            'accent'      => $dark,
            'accent-soft' => kpi_ui_mix($base, '#161A2C', 0.35),
            'lg-btn'      => $dark,
            'lg-link'     => kpi_ui_readable(kpi_ui_from_hsl($h, min($s, 0.9), 0.76), '#0E1120', 6.0, true),
        ],
        'hue' => $h, 'sat' => $s,
    ];
}

/* ---------- Gambar ---------- */

function kpi_ui_dir(): string
{
    return KPI_ROOT . '/uploads';
}

/** Alamat gambar sendiri untuk sebuah tempat (relatif terhadap folder aplikasi), atau null bila memakai bawaan. */
function kpi_ui_image(string $slot): ?string
{
    $file = (string)kpi_setting('ui_img_' . $slot, '');
    if ($file === '' || !preg_match(KPI_UI_FILE_RE, $file) || !is_file(kpi_ui_dir() . '/' . $file)) {
        return null;
    }
    return 'uploads/' . $file;
}

function kpi_ui_logo(): string
{
    return kpi_ui_image('logo') ?? 'assets/logo.png';
}

/** Logo di halaman masuk aplikasi: logo khusus halaman masuk, atau logo perusahaan bila kosong. */
function kpi_ui_login_logo(): string
{
    return kpi_ui_image('login_logo') ?? kpi_ui_logo();
}

/** Batas ukuran unggahan dalam byte: 5 MB, atau lebih kecil bila php.ini membatasi. */
function kpi_ui_upload_limit(): int
{
    $limit = KPI_UI_MAX_MB * 1024 * 1024;
    foreach (['upload_max_filesize', 'post_max_size'] as $k) {
        $v = kpi_ui_ini_bytes((string)ini_get($k));
        if ($v > 0) {
            $limit = min($limit, $v);
        }
    }
    return $limit;
}

function kpi_ui_ini_bytes(string $v): int
{
    $v = trim($v);
    if ($v === '') {
        return 0;
    }
    $n = (float)$v;
    switch (strtolower(substr($v, -1))) {
        case 'g': $n *= 1024;
        // no break
        case 'm': $n *= 1024;
        // no break
        case 'k': $n *= 1024;
    }
    return (int)$n;
}

/** Batas unggahan untuk ditampilkan, misalnya "5" atau "1,5" (MB). */
function kpi_ui_limit_mb(): string
{
    $mb = kpi_ui_upload_limit() / 1048576;
    return kpi_num($mb, fmod($mb, 1.0) < 0.05 ? 0 : 1);
}

/** Menyimpan gambar unggahan untuk sebuah tempat, mengganti gambar lama. */
function kpi_ui_upload(string $slot, $file): string
{
    if (!isset(KPI_UI_SLOTS[$slot])) {
        throw new KpiApiError('Tempat gambar tidak dikenal.');
    }
    if (!is_array($file) || !isset($file['error']) || is_array($file['error'])) {
        throw new KpiApiError('Pilih gambar terlebih dahulu.');
    }
    $tooBig = ['Gambar terlalu besar. Ukuran paling besar {0} MB.', 400, [kpi_ui_limit_mb()]];
    switch ((int)$file['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_NO_FILE:
            throw new KpiApiError('Pilih gambar terlebih dahulu.');
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            throw new KpiApiError(...$tooBig);
        default:
            throw new KpiApiError('Gambar gagal diunggah. Silakan coba lagi.');
    }
    $tmp = (string)$file['tmp_name'];
    if (!is_uploaded_file($tmp)) {
        throw new KpiApiError('Gambar gagal diunggah. Silakan coba lagi.');
    }
    if ((int)$file['size'] > kpi_ui_upload_limit()) {
        throw new KpiApiError(...$tooBig);
    }
    $info = @getimagesize($tmp);
    if (!$info || !isset(KPI_UI_TYPES[$info[2]]) || $info[0] < 1 || $info[1] < 1) {
        throw new KpiApiError('File ini bukan gambar PNG, JPG, WEBP, atau GIF.');
    }

    $dir = kpi_ui_dir();
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    $name = KPI_UI_SLOTS[$slot] . '-' . bin2hex(random_bytes(6)) . '.' . KPI_UI_TYPES[$info[2]];
    if (!is_dir($dir) || !is_writable($dir) || !@move_uploaded_file($tmp, $dir . '/' . $name)) {
        throw new KpiApiError('Gambar tidak bisa disimpan. Pastikan folder {0} bisa ditulis oleh server.', 500, ['uploads']);
    }
    @chmod($dir . '/' . $name, 0644);

    kpi_ui_delete_file((string)kpi_setting('ui_img_' . $slot, ''));
    kpi_set_setting('ui_img_' . $slot, $name);
    return $name;
}

/** Kembali memakai gambar bawaan untuk sebuah tempat. */
function kpi_ui_remove(string $slot): void
{
    if (!isset(KPI_UI_SLOTS[$slot])) {
        throw new KpiApiError('Tempat gambar tidak dikenal.');
    }
    kpi_ui_delete_file((string)kpi_setting('ui_img_' . $slot, ''));
    kpi_set_setting('ui_img_' . $slot, '');
}

function kpi_ui_delete_file(string $file): void
{
    if ($file !== '' && preg_match(KPI_UI_FILE_RE, $file) && is_file(kpi_ui_dir() . '/' . $file)) {
        @unlink(kpi_ui_dir() . '/' . $file);
    }
}

/* ---------- Pilihan lain ---------- */

function kpi_ui_show_art(): bool
{
    return kpi_setting('ui_login_art_show', '1') !== '0';
}

/** Latar aplikasi setelah masuk: default, tint (warna template lembut), atau image. */
function kpi_ui_app_bg_mode(): string
{
    $m = (string)kpi_setting('ui_app_bg_mode', 'default');
    if (!in_array($m, KPI_UI_APP_BG_MODES, true) || ($m === 'image' && kpi_ui_image('app_bg') === null)) {
        return 'default';
    }
    return $m;
}

/** Warna sendiri untuk latar aplikasi (pilihan 'color'). */
function kpi_ui_app_bg_color(): string
{
    $c = (string)kpi_setting('ui_app_bg_color', '');
    return preg_match('/^#[0-9A-Fa-f]{6}$/', $c) ? strtoupper($c) : KPI_UI_APP_BG_COLOR;
}

/** Warna latar halaman masuk pilihan sendiri, atau null bila mengikuti warna template. */
function kpi_ui_login_color(): ?string
{
    if (kpi_setting('ui_login_color_mode', 'template') !== 'custom') {
        return null;
    }
    $c = (string)kpi_setting('ui_login_color', '');
    return preg_match('/^#[0-9A-Fa-f]{6}$/', $c) ? strtoupper($c) : null;
}

/** Warna yang terakhir dipilih untuk latar halaman masuk (juga saat sedang mengikuti template). */
function kpi_ui_login_color_saved(): string
{
    $c = (string)kpi_setting('ui_login_color', '');
    return preg_match('/^#[0-9A-Fa-f]{6}$/', $c) ? strtoupper($c) : kpi_ui_palette(kpi_ui_color())['light']['lg-b'];
}

/**
 * Warna panel halaman masuk untuk warna latar sendiri. Tulisan dibuat putih atau gelap,
 * mana yang lebih terbaca di atas warna itu.
 */
function kpi_ui_login_vars(string $c): array
{
    [$h, $s, $l] = kpi_ui_hsl($c);
    $white = kpi_ui_contrast('#FFFFFF', $c) >= kpi_ui_contrast('#151A2E', $c);
    $fg    = $white ? '#FFFFFF' : '#151A2E';
    return [
        'lg-a'     => kpi_ui_from_hsl($h, $s, min(1.0, $l + 0.06)),
        'lg-b'     => $c,
        'lg-c'     => kpi_ui_from_hsl($h, $s, max(0.0, $l - 0.06)),
        'lg-fg'    => $fg,
        'lg-muted' => kpi_ui_readable(kpi_ui_mix($fg, $c, 0.78), $c, 4.5, $white),
        'lg-grid'  => $white ? 'rgba(255,255,255,.035)' : 'rgba(21,26,46,.05)',
    ];
}

function kpi_ui_veil(): string
{
    $v = (string)kpi_setting('ui_app_bg_veil', 'soft');
    return isset(KPI_UI_VEILS[$v]) ? $v : 'soft';
}

/* ---------- Keluaran untuk aplikasi ---------- */

/** Data tampilan untuk assets/app.js (window.__BOOT__.ui). */
function kpi_ui_boot(): array
{
    return [
        'logo'         => kpi_ui_logo(),
        'logoOwn'      => kpi_ui_image('logo') !== null,
        'loginLogo'    => kpi_ui_login_logo(),
        'loginLogoOwn' => kpi_ui_image('login_logo') !== null || kpi_ui_image('logo') !== null,
        'art'     => kpi_ui_image('login_art'),
        'showArt' => kpi_ui_show_art(),
    ];
}

/** CSS tambahan untuk aplikasi KPI sesuai pilihan di menu Tampilan. Kosong bila semuanya bawaan. */
function kpi_ui_css(): string
{
    $base  = kpi_ui_color();
    $isDef = strcasecmp($base, KPI_UI_THEMES[KPI_UI_DEFAULT_THEME][1]) === 0;
    $p     = kpi_ui_palette($base);
    $vars  = static function (array $m): string {
        $out = '';
        foreach ($m as $k => $v) {
            $out .= '--' . $k . ':' . $v . ';';
        }
        return $out;
    };
    $css = '';

    if (!$isDef) {
        $dark = $vars($p['dark']);
        $css .= ':root{' . $vars($p['light']) . '}'
              . '@media (prefers-color-scheme: dark){:root:not([data-theme="light"]){' . $dark . '}}'
              . ':root[data-theme="dark"]{' . $dark . '}';
        // Ilustrasi bawaan berwarna biru: geser warnanya mengikuti template.
        $rot = (int)round($p['hue'] - 222);
        $sat = $p['sat'] < 0.15 ? 0.15 : 1;
        $css .= '.lg-hero svg{filter:hue-rotate(' . $rot . 'deg)' . ($sat < 1 ? ' saturate(' . $sat . ')' : '') . '}';
    }

    // Warna latar halaman masuk pilihan sendiri (panel kiri dan tepi di sekitar kartu masuk).
    $loginColor = kpi_ui_login_color();
    $lgC        = $p['light']['lg-c'];
    if ($loginColor !== null) {
        $lv  = kpi_ui_login_vars($loginColor);
        $lgC = $lv['lg-c'];
        $css .= '.login{' . $vars($lv) . 'background:' . $loginColor . '}';
    }

    $loginBg = kpi_ui_image('login_bg');
    if ($loginBg !== null) {
        [$r, $g, $b] = kpi_ui_rgb($lgC);
        $css .= '.login{background:var(--lg-c) url("' . $loginBg . '") center/cover no-repeat}'
              . '.lg-hero{background:linear-gradient(180deg,rgba(' . "$r,$g,$b" . ',.28),rgba(' . "$r,$g,$b" . ',.78))}';
    }

    $mode = kpi_ui_app_bg_mode();
    if ($mode === 'tint') {
        $css .= '.app{background:' . kpi_ui_mix($base, '#F3F4F8', 0.10) . '}'
              . '@media (prefers-color-scheme: dark){:root:not([data-theme="light"]) .app{background:' . kpi_ui_mix($base, '#0E1120', 0.16) . '}}'
              . ':root[data-theme="dark"] .app{background:' . kpi_ui_mix($base, '#0E1120', 0.16) . '}';
    } elseif ($mode === 'color') {
        // Warna sendiri. Di tampilan gelap warnanya dicampur gelap supaya tulisan tetap terbaca.
        $c    = kpi_ui_app_bg_color();
        $dark = kpi_ui_mix($c, '#0E1120', 0.18);
        $css .= '.app{background:' . $c . '}'
              . '@media (prefers-color-scheme: dark){:root:not([data-theme="light"]) .app{background:' . $dark . '}}'
              . ':root[data-theme="dark"] .app{background:' . $dark . '}';
        if (kpi_ui_contrast('#151A2E', $c) < 4.5) {
            // Latar gelap: judul halaman (di luar kartu) dibuat terang, hanya di tampilan terang.
            $muted = kpi_ui_readable(kpi_ui_mix('#FFFFFF', $c, 0.80), $c, 4.5, true);
            $css .= '.app{--top-fg:#FFFFFF;--top-muted:' . $muted . '}'
                  . '@media (prefers-color-scheme: dark){:root:not([data-theme="light"]) .app{--top-fg:var(--fg);--top-muted:var(--muted)}}'
                  . ':root[data-theme="dark"] .app{--top-fg:var(--fg);--top-muted:var(--muted)}'
                  . '.top h1{color:var(--top-fg)}.top .eyebrow,.top .period{color:var(--top-muted)}.top select.inline{color:var(--fg)}';
        }
    } elseif ($mode === 'image') {
        $a = KPI_UI_VEILS[kpi_ui_veil()];
        $css .= ':root{--ui-veil:rgba(243,244,248,' . $a . ')}'
              . '@media (prefers-color-scheme: dark){:root:not([data-theme="light"]){--ui-veil:rgba(14,17,32,' . $a . ')}}'
              . ':root[data-theme="dark"]{--ui-veil:rgba(14,17,32,' . $a . ')}'
              . '.app{isolation:isolate}'
              . '.app::before{content:"";position:fixed;inset:0;z-index:-1;pointer-events:none;'
              . 'background:linear-gradient(var(--ui-veil),var(--ui-veil)),url("' . kpi_ui_image('app_bg') . '") center/cover no-repeat}';
    }
    return $css;
}

/* ---------- Contoh warna untuk menu Tampilan (gambar SVG kecil, tanpa gaya sebaris) ---------- */

/** Contoh kecil sebuah template: panel halaman masuk di kiri, aplikasi di kanan. */
function kpi_ui_preview_svg(string $base): string
{
    $p = kpi_ui_palette($base)['light'];
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 160 96">'
         . '<defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="' . $p['lg-a'] . '"/>'
         . '<stop offset=".55" stop-color="' . $p['lg-b'] . '"/><stop offset="1" stop-color="' . $p['lg-c'] . '"/></linearGradient></defs>'
         . '<rect width="160" height="96" fill="#F3F4F8"/>'
         . '<rect width="64" height="96" fill="url(#g)"/>'
         . '<rect x="14" y="30" width="36" height="5" rx="2.5" fill="#fff" opacity=".9"/>'
         . '<rect x="18" y="41" width="28" height="4" rx="2" fill="' . $p['lg-muted'] . '" opacity=".8"/>'
         . '<rect x="76" y="12" width="72" height="72" rx="8" fill="#fff" stroke="#DCE0EB"/>'
         . '<rect x="86" y="24" width="38" height="5" rx="2.5" fill="#151A2E" opacity=".75"/>'
         . '<rect x="86" y="36" width="52" height="8" rx="3" fill="#fff" stroke="#DCE0EB"/>'
         . '<rect x="86" y="50" width="52" height="8" rx="3" fill="#fff" stroke="#DCE0EB"/>'
         . '<rect x="86" y="65" width="52" height="10" rx="3.5" fill="' . $p['lg-btn'] . '"/>'
         . '<rect x="128" y="22" width="12" height="9" rx="4.5" fill="' . $p['accent-soft'] . '"/>'
         . '<circle cx="134" cy="26.5" r="2.5" fill="' . $p['accent'] . '"/>'
         . '</svg>';
    return 'data:image/svg+xml;base64,' . base64_encode($svg);
}

/** Contoh latar: gradasi halaman masuk atau warna latar aplikasi. */
function kpi_ui_swatch_svg(string $kind): string
{
    $base = kpi_ui_color();
    $p    = kpi_ui_palette($base)['light'];
    if ($kind === 'login' || $kind === 'login-template' || $kind === 'login-custom') {
        $lc = $kind === 'login' ? kpi_ui_login_color() : ($kind === 'login-custom' ? kpi_ui_login_color_saved() : null);
        if ($lc !== null) {
            $p = kpi_ui_login_vars($lc) + $p;
        }
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 160 96"><defs><radialGradient id="g" cx="0" cy="0" r="1.3">'
             . '<stop offset="0" stop-color="' . $p['lg-a'] . '"/><stop offset=".55" stop-color="' . $p['lg-b'] . '"/>'
             . '<stop offset="1" stop-color="' . $p['lg-c'] . '"/></radialGradient></defs><rect width="160" height="96" fill="url(#g)"/></svg>';
    } else {
        $bg  = $kind === 'tint' ? kpi_ui_mix($base, '#F3F4F8', 0.10) : ($kind === 'color' ? kpi_ui_app_bg_color() : '#F3F4F8');
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 160 96"><rect width="160" height="96" fill="' . $bg . '"/>'
             . '<rect width="40" height="96" fill="#fff"/><rect x="8" y="22" width="24" height="7" rx="3" fill="' . $p['accent-soft'] . '"/>'
             . '<rect x="52" y="14" width="96" height="30" rx="5" fill="#fff" stroke="#DCE0EB"/>'
             . '<rect x="52" y="52" width="46" height="30" rx="5" fill="#fff" stroke="#DCE0EB"/>'
             . '<rect x="102" y="52" width="46" height="30" rx="5" fill="#fff" stroke="#DCE0EB"/></svg>';
    }
    return 'data:image/svg+xml;base64,' . base64_encode($svg);
}
