<?php
// Конфигурация базы данных
define('DB_HOST', 'localhost');
define('DB_NAME', 'shoe_store');
define('DB_USER', 'root');
define('DB_PASS', '');

// Настройки сайта
define('SITE_URL', 'http://localhost/site');

// Настройка отображения ошибок
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Настройки сессии
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>