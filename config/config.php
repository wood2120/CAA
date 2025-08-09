<?php
// Cargar el cargador de variables de entorno
require_once __DIR__ . '/env_loader.php';

// Cargar variables de entorno desde .env
try {
    EnvLoader::load();
} catch (Exception $e) {
    // Si no existe .env, usar valores por defecto
    error_log("Warning: " . $e->getMessage());
}

// Configuración de la base de datos
define('DB_HOST', EnvLoader::get('DB_HOST', 'localhost'));
define('DB_PORT', EnvLoader::get('DB_PORT', '3307'));
define('DB_NAME', EnvLoader::get('DB_NAME', 'SistemaGestionEmpresa'));
define('DB_USER', EnvLoader::get('DB_USER', 'root'));
define('DB_PASS', EnvLoader::get('DB_PASS', ''));

// Configuración general del sistema
define('SITE_URL', EnvLoader::get('SITE_URL', 'https://caa-i0xf.onrender.com'));
define('SITE_NAME', EnvLoader::get('SITE_NAME', 'Sistema de Gestión Empresarial'));

// Configuración de sesiones
define('SESSION_TIMEOUT', (int)EnvLoader::get('SESSION_TIMEOUT', 3600)); // en segundos

// Configuración de reportes
define('REPORTS_PATH', dirname(__DIR__) . '/reports/');

// Zona horaria
date_default_timezone_set(EnvLoader::get('TIMEZONE', 'America/Costa_Rica'));

// Configuración de errores
define('DEBUG_MODE', EnvLoader::toBool(EnvLoader::get('DEBUG_MODE', 'true')));

// Configuración de seguridad de sesiones
define('SESSION_SECURE', EnvLoader::toBool(EnvLoader::get('SESSION_SECURE', 'false')));
define('SESSION_HTTPONLY', EnvLoader::toBool(EnvLoader::get('SESSION_HTTPONLY', 'true')));

if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
?>