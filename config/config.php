<?php

// Base URL
define('BASE_URL', 'http://localhost/softsend/');

// Path
define('ROOT_PATH', __DIR__ . '/../');
define('UPLOAD_PATH', ROOT_PATH . 'assets/uploads/');

// App Info
define('APP_NAME', 'SoftSend');
define('APP_VERSION', '2.0.0');

// Database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'softsend_db');

// Session
define('SESSION_TIMEOUT', 7200); // 2 jam
define('REMEMBER_ME_DAYS', 30);

// Upload
define('MAX_UPLOAD_SIZE', 5242880); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png']);

// Timezone
define('TIMEZONE', 'Asia/Jakarta');
date_default_timezone_set(TIMEZONE);
