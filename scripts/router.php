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

return false; // layani file apa adanya
