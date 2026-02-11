<?php

require_once __DIR__ . '/../config/config.php';
require_once ROOT_PATH . 'config/session.php';
require_once ROOT_PATH . 'config/database.php';
require_once ROOT_PATH . 'includes/functions.php';

init_session();

if (!empty($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: ' . BASE_URL . '?page=dashboard');
    exit;
}

$admin_email_wajib = 'adminsoft@gmail.com';
$error = '';
$info = '';

if (isset($_GET['logout']) && $_GET['logout'] === 'success') {
    $info = 'Anda berhasil logout.';
}
if (isset($_GET['timeout']) && $_GET['timeout'] == '1') {
    $error = 'Sesi habis. Silakan login kembali.';
}

if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = [
        'count' => 0,
        'first' => 0,
        'block_until' => 0
    ];
}

$attempts = &$_SESSION['login_attempts'];
$now = time();

if (!empty($attempts['block_until']) && $now < (int)$attempts['block_until']) {
    $sisa = (int)$attempts['block_until'] - $now;
    $menit = (int)ceil($sisa / 60);
    $error = 'Terlalu banyak percobaan login. Coba lagi dalam ' . $menit . ' menit.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $error === '') {
    $email = isset($_POST['email']) ? trim((string)$_POST['email']) : '';
    $password = isset($_POST['password']) ? (string)$_POST['password'] : '';
    $remember_me = isset($_POST['remember_me']) && $_POST['remember_me'] === '1';

    if ($email === '' || $password === '') {
        $error = 'Email dan password wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email tidak valid.';
    } elseif (strpos($email, '@gmail.com') === false) {
        $error = 'Email harus @gmail.com';
    } elseif (strtolower($email) !== strtolower($admin_email_wajib)) {
        $error = 'Email tidak terdaftar';
    } elseif (strlen($password) < 8) {
        $error = 'Password minimal 8 karakter';
    }

    if ($error === '') {
        if (empty($attempts['first']) || ($now - (int)$attempts['first']) > 900) {
            $attempts['count'] = 0;
            $attempts['first'] = $now;
            $attempts['block_until'] = 0;
        }

        $db = new Database();
        $rows = $db->select("SELECT * FROM admin WHERE email = ? LIMIT 1", [$email]);
        $db->close();

        if (!$rows || count($rows) === 0) {
            $error = 'Email tidak terdaftar';
        } else {
            $admin = $rows[0];
            $hash = (string)$admin['password'];

            if (!password_verify($password, $hash)) {
                $error = 'Password salah';
            } else {
                $attempts['count'] = 0;
                $attempts['first'] = 0;
                $attempts['block_until'] = 0;

                $_SESSION['admin_id'] = (int)$admin['id'];
                $_SESSION['admin_nama'] = (string)$admin['nama_lengkap'];
                $_SESSION['admin_email'] = (string)$admin['email'];
                $_SESSION['admin_foto'] = !empty($admin['foto_profil']) ? (string)$admin['foto_profil'] : 'default-avatar.png';
                $_SESSION['logged_in'] = true;
                $_SESSION['last_activity'] = time();

                $_SESSION['remember_me'] = $remember_me ? true : false;
                mark_remember_me($_SESSION['remember_me']);

                if ($remember_me) {
                    setcookie('remember_me', '1', time() + (REMEMBER_ME_DAYS * 86400), '/', '', !empty($_SERVER['HTTPS']), true);
                } else {
                    if (isset($_COOKIE['remember_me'])) {
                        setcookie('remember_me', '', time() - 3600, '/', '', !empty($_SERVER['HTTPS']), true);
                    }
                }

                header('Location: ' . BASE_URL . '?page=dashboard');
                exit;
            }
        }

        if ($error !== '') {
            if (empty($attempts['first']) || ($now - (int)$attempts['first']) > 900) {
                $attempts['count'] = 1;
                $attempts['first'] = $now;
            } else {
                $attempts['count'] = (int)$attempts['count'] + 1;
            }

            if ((int)$attempts['count'] >= 5) {
                $attempts['block_until'] = $now + 900;
                $error = 'Terlalu banyak percobaan login. Akun diblokir 15 menit.';
            }
        }
    }
}

$email_value = '';
if (isset($_POST['email'])) {
    $email_value = htmlspecialchars((string)$_POST['email'], ENT_QUOTES, 'UTF-8');
}

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') ?></title>

    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@3.4.1/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/auth.css">
</head>

<body class="auth-body">

    <div class="auth-bg">
        <div class="auth-overlay"></div>
    </div>

    <div class="auth-wrapper">
        <div class="auth-card" role="region" aria-label="Form Login">
            <div class="auth-brand">
                <div class="auth-logo">
                    <span class="auth-logo-dot"></span>
                </div>
                <div>
                    <h1 class="auth-title"><?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') ?></h1>
                    <p class="auth-subtitle">Manajemen Pengiriman</p>
                </div>
            </div>

            <?php if ($info !== ''): ?>
                <div class="auth-alert auth-alert-success" role="alert" aria-live="polite">
                    <i class="bi bi-check-circle"></i>
                    <span><?= htmlspecialchars($info, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
            <?php endif; ?>

            <?php if ($error !== ''): ?>
                <div class="auth-alert auth-alert-error" role="alert" aria-live="polite">
                    <i class="bi bi-exclamation-triangle"></i>
                    <span><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
            <?php endif; ?>

            <form id="loginForm" method="POST" action="<?= BASE_URL ?>?page=login" novalidate>
                <div class="auth-field">
                    <label for="email" class="auth-label">Email</label>
                    <div class="auth-input-wrap">
                        <span class="auth-input-icon" aria-hidden="true"><i class="bi bi-envelope"></i></span>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="auth-input"
                            placeholder="adminsoft@gmail.com"
                            value="<?= $email_value ?>"
                            autocomplete="username"
                            required
                            aria-describedby="error-email">
                    </div>
                    <p id="error-email" class="auth-error-text hidden" role="alert">Email tidak valid</p>
                </div>

                <div class="auth-field">
                    <label for="password" class="auth-label">Password</label>
                    <div class="auth-input-wrap">
                        <span class="auth-input-icon" aria-hidden="true"><i class="bi bi-lock"></i></span>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="auth-input pr-12"
                            placeholder="Minimal 8 karakter"
                            autocomplete="current-password"
                            required
                            minlength="8"
                            aria-describedby="error-password">
                        <button type="button" id="togglePassword" class="auth-toggle" aria-label="Tampilkan password">
                            <i class="bi bi-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                    <p id="error-password" class="auth-error-text hidden" role="alert">Password minimal 8 karakter</p>
                </div>

                <div class="auth-row">
                    <label class="auth-check">
                        <input type="checkbox" name="remember_me" value="1" <?= isset($_POST['remember_me']) ? 'checked' : '' ?>>
                        <span>Ingat Saya (30 hari)</span>
                    </label>
                </div>

                <button type="submit" id="submitBtn" class="auth-btn">
                    <span id="btnText">Login</span>
                    <span id="btnSpinner" class="auth-spinner hidden" aria-hidden="true"></span>
                </button>

                <div class="auth-footnote">
                    <p>Email wajib: <span class="auth-mono"><?= htmlspecialchars($admin_email_wajib, ENT_QUOTES, 'UTF-8') ?></span></p>
                    <p class="auth-muted">Versi <?= htmlspecialchars(APP_VERSION, ENT_QUOTES, 'UTF-8') ?></p>
                </div>
            </form>
        </div>
    </div>

    <script src="assets/js/auth.js"></script>
</body>

</html>