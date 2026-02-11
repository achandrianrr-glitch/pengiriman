<?php

if (!defined('SESSION_TIMEOUT')) {
    require_once __DIR__ . '/config.php';
}

function init_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_strict_mode', '1');

    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    $lifetime = 0;

    $cookieParams = [
        'lifetime' => $lifetime,
        'path' => '/',
        'domain' => '',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ];

    session_set_cookie_params($cookieParams);

    $gcMaxLifetime = max(SESSION_TIMEOUT, REMEMBER_ME_DAYS * 86400);
    ini_set('session.gc_maxlifetime', (string)$gcMaxLifetime);

    session_start();

    if (!isset($_SESSION['_initiated'])) {
        session_regenerate_id(true);
        $_SESSION['_initiated'] = true;
        $_SESSION['regenerated_at'] = time();
    }

    $timeout = SESSION_TIMEOUT;
    if (!empty($_SESSION['remember_me']) && $_SESSION['remember_me'] === true) {
        $timeout = REMEMBER_ME_DAYS * 86400;
    }

    if (isset($_SESSION['last_activity'])) {
        $elapsed = time() - (int)$_SESSION['last_activity'];
        if ($elapsed > $timeout) {
            destroy_session();
            session_start();
            $_SESSION['session_timeout'] = 1;
            $_SESSION['_initiated'] = true;
            $_SESSION['regenerated_at'] = time();
        }
    }

    $_SESSION['last_activity'] = time();

    if (isset($_SESSION['regenerated_at'])) {
        if (time() - (int)$_SESSION['regenerated_at'] > 1800) {
            session_regenerate_id(true);
            $_SESSION['regenerated_at'] = time();
        }
    } else {
        $_SESSION['regenerated_at'] = time();
    }
}

function mark_remember_me(bool $remember): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return;
    }
    $_SESSION['remember_me'] = $remember;

    $params = session_get_cookie_params();
    $expires = $remember ? (time() + (REMEMBER_ME_DAYS * 86400)) : 0;

    setcookie(
        session_name(),
        session_id(),
        [
            'expires' => $expires,
            'path' => $params['path'] ?? '/',
            'domain' => $params['domain'] ?? '',
            'secure' => $params['secure'] ?? false,
            'httponly' => $params['httponly'] ?? true,
            'samesite' => 'Lax',
        ]
    );
}

function destroy_session(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return;
    }

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            [
                'expires' => time() - 3600,
                'path' => $params['path'] ?? '/',
                'domain' => $params['domain'] ?? '',
                'secure' => $params['secure'] ?? false,
                'httponly' => $params['httponly'] ?? true,
                'samesite' => 'Lax',
            ]
        );
    }

    session_destroy();
}
