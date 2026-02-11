<?php

require_once __DIR__ . '/../config/config.php';
require_once ROOT_PATH . 'config/session.php';

init_session();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ' . BASE_URL . '?page=login');
    exit;
}

$timeout = SESSION_TIMEOUT;
if (!empty($_SESSION['remember_me']) && $_SESSION['remember_me'] === true) {
    $timeout = REMEMBER_ME_DAYS * 86400;
}

if (isset($_SESSION['last_activity'])) {
    $elapsed = time() - (int)$_SESSION['last_activity'];
    if ($elapsed > $timeout) {
        destroy_session();
        header('Location: ' . BASE_URL . '?page=login&timeout=1');
        exit;
    }
}

$_SESSION['last_activity'] = time();

if (!isset($_SESSION['regenerated'])) {
    $_SESSION['regenerated'] = time();
} else {
    if ((time() - (int)$_SESSION['regenerated']) > 1800) {
        session_regenerate_id(true);
        $_SESSION['regenerated'] = time();
    }
}
