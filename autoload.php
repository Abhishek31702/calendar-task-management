<?php
/**
 * Simple autoloader for the application
 */

// Define base path
define('BASE_PATH', __DIR__);

// Load database config FIRST (contains Database class)
if (file_exists(BASE_PATH . '/config/database.php')) {
    require_once BASE_PATH . '/config/database.php';
} else {
    die('Database configuration file not found. Please create config/database.php');
}

// Autoload classes
spl_autoload_register(function ($class) {
    $file = BASE_PATH . '/classes/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});
?>