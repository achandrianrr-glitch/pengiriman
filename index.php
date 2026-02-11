<?php

declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once ROOT_PATH . 'config/session.php';
require_once ROOT_PATH . 'config/database.php';
require_once ROOT_PATH . 'includes/functions.php';

init_session();

/**
 * Fallback helper untuk starts_with bila dibutuhkan (robust).
 */
if (!function_exists('softsend_starts_with')) {
    function softsend_starts_with(string $haystack, string $needle): bool
    {
        if ($needle === '') return true;
        return strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}

/**
 * Render halaman error (status code bisa ditentukan).
 */
function render_error_page(string $title, string $message, int $statusCode = 500): void
{
    http_response_code($statusCode);
?>
    <!doctype html>
    <html lang="id">

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></title>
        <link href="https://cdn.jsdelivr.net/npm/tailwindcss@3.4.1/dist/tailwind.min.css" rel="stylesheet">
    </head>

    <body class="bg-gray-50 text-gray-900">
        <div class="min-h-screen flex items-center justify-center p-6">
            <div class="max-w-xl w-full bg-white shadow rounded-2xl p-6 border border-gray-200">
                <h1 class="text-2xl font-bold mb-2"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1>
                <p class="text-gray-600"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
                <div class="mt-5 flex flex-wrap gap-3">
                    <a class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-blue-600 text-white hover:bg-blue-700 transition"
                        href="<?= BASE_URL ?>?page=login">
                        Kembali ke Login
                    </a>
                    <a class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-gray-100 text-gray-800 hover:bg-gray-200 transition"
                        href="<?= BASE_URL ?>">
                        Ke Beranda
                    </a>
                </div>
                <p class="mt-4 text-xs text-gray-400">SoftSend <?= defined('APP_VERSION') ? APP_VERSION : '' ?></p>
            </div>
        </div>
    </body>

    </html>
<?php
    exit;
}

/**
 * Normalize & sanitasi parameter page.
 * - Hilangkan traversal (..)
 * - Rapikan slash
 * - Default: login
 */
function normalize_page($page): string
{
    $page = (string)($page ?? '');
    $page = trim($page);
    $page = str_replace('\\', '/', $page);
    $page = trim($page, '/');

    // Buang karakter aneh, hanya izinkan: a-zA-Z0-9 / _ -
    // (konsisten untuk routing manual)
    $page = preg_replace('/[^a-zA-Z0-9\/\_\-]/', '', $page);

    // Anti directory traversal
    while (strpos($page, '..') !== false) {
        $page = str_replace('..', '', $page);
    }

    // Rapikan multiple slash
    $page = preg_replace('#/{2,}#', '/', $page);

    return $page === '' ? 'login' : $page;
}

/**
 * Resolve route ke path file yang valid.
 * Return full path atau null jika tidak ditemukan.
 */
function resolve_route(string $page): ?string
{
    $routes = [
        'login'     => ROOT_PATH . 'auth/login.php',
        'logout'    => ROOT_PATH . 'auth/logout.php',
        'dashboard' => ROOT_PATH . 'pages/dashboard.php',
        'pengiriman'  => ROOT_PATH . 'pages/pengiriman/create.php',
    ];

    if (isset($routes[$page]) && is_file($routes[$page])) {
        return $routes[$page];
    }

    $candidate_paths = [
        ROOT_PATH . 'pages/' . $page . '.php',
        ROOT_PATH . 'pages/' . $page . '/index.php',
        ROOT_PATH . $page . '.php',
        ROOT_PATH . $page . '/index.php',
        ROOT_PATH . 'auth/' . $page . '.php',
        ROOT_PATH . 'api/' . $page . '.php',
    ];

    foreach ($candidate_paths as $path) {
        if (is_file($path)) {
            return $path;
        }
    }

    return null;
}

/**
 * DB connection test (opsional, tapi kamu sudah buat dan bagus).
 * Pastikan ini tidak menyebabkan crash tanpa pesan jelas.
 */
try {
    $db_test = new Database();
    $conn = $db_test->getConnection();

    $ok = ($conn instanceof mysqli) && @$conn->ping();
    $db_test->close();

    if (!$ok) {
        render_error_page(
            'Koneksi Database Gagal',
            'Pastikan MySQL berjalan dan konfigurasi database benar.',
            500
        );
    }
} catch (Throwable $e) {
    render_error_page(
        'Koneksi Database Error',
        'Terjadi error saat menghubungkan ke database. Silakan cek konfigurasi DB_HOST/DB_USER/DB_PASS/DB_NAME.',
        500
    );
}

// ==============================
// Routing utama
// ==============================
$page = normalize_page($_GET['page'] ?? 'login');
$routeFile = resolve_route($page);

if ($routeFile === null) {
    render_error_page(
        '404 - Halaman tidak ditemukan',
        'Route "' . $page . '" tidak tersedia atau file belum dibuat. Pastikan struktur folder sesuai blueprint.',
        404
    );
}

/**
 * Tentukan protected/public:
 * - Protected: file di /pages atau /api
 * - Public: login, logout, tracking (public tracking tanpa login)
 */
$pagesPrefix = ROOT_PATH . 'pages/';
$apiPrefix   = ROOT_PATH . 'api/';

// Gunakan PHP 8.2 str_starts_with jika ada, fallback jika tidak
$startsWith = function (string $haystack, string $needle): bool {
    if (function_exists('str_starts_with')) {
        return str_starts_with($haystack, $needle);
    }
    return softsend_starts_with($haystack, $needle);
};

$is_in_pages = $startsWith($routeFile, $pagesPrefix);
$is_in_api   = $startsWith($routeFile, $apiPrefix);

$is_protected = ($is_in_pages || $is_in_api);

// whitelist halaman publik
$is_public_page = in_array($page, ['login', 'logout', 'tracking'], true);

// Tracking publik: walaupun di /pages, harus tetap public
$is_tracking_public = ($routeFile === ROOT_PATH . 'pages/tracking/public.php');

if ($is_protected && !$is_public_page && !$is_tracking_public) {
    require_once ROOT_PATH . 'auth/check_auth.php';
}

// Include halaman tujuan
require_once $routeFile;