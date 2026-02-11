<?php

require_once __DIR__ . '/../config/config.php';
require_once ROOT_PATH . 'config/session.php';

init_session();

destroy_session();

if (isset($_COOKIE['remember_me'])) {
    setcookie('remember_me', '', time() - 3600, '/', '', !empty($_SERVER['HTTPS']), true);
}

header('Location: ' . BASE_URL . '?page=login&logout=success');
exit;
