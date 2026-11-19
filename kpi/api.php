<?php
/**
 * Satu-satunya titik masuk API. Semua permintaan dikirim sebagai JSON POST,
 * kecuali unduhan cadangan data (GET ?action=export&download=1).
 */

declare(strict_types=1);

require __DIR__ . '/lib/db.php';
require __DIR__ . '/lib/handlers.php';
require __DIR__ . '/lib/auth.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

function kpi_fail(string $message, int $status): void
{
    http_response_code($status);
    echo json_encode(['ok' => false, 'error' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!kpi_is_installed()) {
    kpi_fail(kpi_t('Aplikasi belum dipasang. Buka install.php terlebih dahulu.'), 503);
}

try {
    $maintenance = kpi_maintenance_message();
    if ($maintenance !== null) {
        kpi_fail($maintenance, 503);
    }

    kpi_session_start();

    $isGet    = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET';
    $download = $isGet && (($_GET['action'] ?? '') === 'export');

    if ($download) {
        $action = 'export';
        $input  = [];
    } else {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            kpi_fail(kpi_t('Metode tidak didukung.'), 405);
        }
        $raw   = file_get_contents('php://input') ?: '';
        $input = json_decode($raw, true);
        if (!is_array($input)) {
            kpi_fail(kpi_t('Permintaan tidak valid.'), 400);
        }
        if (!kpi_check_csrf($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($input['csrf'] ?? null))) {
            kpi_fail(kpi_t('Sesi Anda sudah berakhir. Muat ulang halaman lalu coba lagi.'), 419);
        }
        $action = (string)($input['action'] ?? '');
    }

    $result = kpi_handle($action, $input);

    if ($download) {
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="cadangan-kpi-' . date('Y-m-d') . '.json"');
        echo json_encode($result['data'] ?? [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    echo json_encode(['ok' => true] + $result, JSON_UNESCAPED_UNICODE);
} catch (KpiApiError $e) {
    kpi_fail($e->getMessage(), $e->status);
} catch (Throwable $e) {
    error_log('[KPI] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    kpi_fail(kpi_t('Terjadi kesalahan di server. Silakan coba lagi.'), 500);
}
