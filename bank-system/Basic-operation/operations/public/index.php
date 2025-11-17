<?php

define('ROOT_PATH', realpath(__DIR__ . '/../'));
// Dynamically construct URLROOT based on current host to work with any IP or domain
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
define('URLROOT', $protocol . '://' . $host . '/bank-system/Basic-operation/operations/public');

$envFile = ROOT_PATH . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}

require_once ROOT_PATH . '/core/App.php';
require_once ROOT_PATH . '/core/Controller.php';
require_once ROOT_PATH . '/core/Database.php';

spl_autoload_register(function ($className) {
    // Check for Controllers
    if (strpos($className, 'Controller') !== false) {
        $file = ROOT_PATH . '/app/controllers/' . $className . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
    // Check for Models
    $file = ROOT_PATH . '/app/models/' . $className . '.php';
    if (file_exists($file)) {
        require_once $file;
        return;
    }
});

$app = new App();