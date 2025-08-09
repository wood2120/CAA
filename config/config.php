<?php
// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'SistemaGestionEmpresa');
define('DB_USER', 'root');
define('DB_PASS', '123456');

// Configuración general del sistema
define('SITE_URL', 'http://localhost/CAA');
define('SITE_NAME', 'CAA');

// Configuración de sesiones
define('SESSION_TIMEOUT', 3600); // 1 hora en segundos

// Configuración de reportes
define('REPORTS_PATH', dirname(__DIR__) . '/reports/');

// Zona horaria
date_default_timezone_set('America/Costa_Rica');

// Configuración de errores (cambiar a false en producción)
define('DEBUG_MODE', true);

if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
?>