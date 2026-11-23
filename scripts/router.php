<?php
/**
 * Pengatur alamat untuk server bawaan PHP yang dipakai `npm start`.
 * Menggantikan aturan .htaccess: folder lib/ dan file konfigurasi tidak boleh dibuka langsung.
 */

$path = rawurldecode((string)parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));
$path = str_replace('\\', '/', $path);

if (preg_match('#^/+lib(/|$)#i', $path)
    || preg_match('#(^|/)\.#', $path)
    || preg_match('#^/+config(\.sample)?\.php$#i', $path)
    || preg_match('#\.(md|sql|json|lock|log|ini)$#i', $path)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Tidak ditemukan.';
    return true;
}

// Folder uploads hanya berisi gambar dari menu Tampilan sysAdmin.
if (preg_match('#^/+uploads(/|$)#i', $path) && !preg_match('#^/+uploads/[a-z-]+-[0-9a-f]{12}\.(png|jpg|webp|gif)$#', $path)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Tidak ditemukan.';
    return true;
}

// Alamat pendek halaman admin: /sysAdmin dan /infraAdmin (huruf besar kecil bebas, boleh diakhiri garis miring).
$short = ['sysadmin' => 'sysadmin.php', 'infraadmin' => 'infraadmin.php'];
if (preg_match('#^/+(sysadmin|infraadmin)/*$#i', $path, $m)) {
    $file = dirname(__DIR__) . '/kpi/' . $short[strtolower($m[1])];
    $_SERVER['SCRIPT_NAME']     = '/' . $short[strtolower($m[1])];
    $_SERVER['SCRIPT_FILENAME'] = $file;
    $_SERVER['PHP_SELF']        = $_SERVER['SCRIPT_NAME'];
    require $file;
    return true;
}

return false; // layani file apa adanya
