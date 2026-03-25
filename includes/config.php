<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('APP_NAME', 'TimeTrack Pro PHP');
define('APP_TIMEZONE', 'America/Denver'); // Utah

define('BASE_PATH', dirname(__DIR__));
define('DATA_PATH', BASE_PATH . '/data');
define('UPLOAD_PATH', BASE_PATH . '/uploads');
define('DB_PATH', DATA_PATH . '/attendance.sqlite');

if (!is_dir(DATA_PATH)) {
    mkdir(DATA_PATH, 0775, true);
}

if (!is_dir(UPLOAD_PATH)) {
    mkdir(UPLOAD_PATH, 0775, true);
}

date_default_timezone_set(APP_TIMEZONE);
