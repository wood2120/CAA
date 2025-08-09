<?php
// Iniciar buffer de salida para evitar problemas con headers
ob_start();
session_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// Función para verificar si el usuario está logueado
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Función para verificar el rol del usuario
function hasRole($role) {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
}

// Función para redirigir si no está logueado
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . SITE_URL . '/login.php');
        exit();
    }
}

// Función para verificar timeout de sesión
function checkSessionTimeout() {
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
        session_unset();
        session_destroy();
        header('Location: ' . SITE_URL . '/login.php?timeout=1');
        exit();
    }
    $_SESSION['last_activity'] = time();
}

// Función para limpiar datos de entrada
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Función para formatear fechas
function formatDate($date, $format = 'd/m/Y') {
    if (empty($date) || $date === '0000-00-00') {
        return '';
    }
    return date($format, strtotime($date));
}

// Función para formatear moneda
function formatCurrency($amount) {
    return '₡' . number_format($amount, 2, '.', ',');
}

// Función para registrar en bitácora
function logActivity($userId, $action) {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "INSERT INTO TB_Bitacora (ID_Usuario, Accion) VALUES (:user_id, :action)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':action', $action);
        $stmt->execute();
        
        $database->closeConnection();
    } catch (Exception $e) {
        error_log("Error al registrar actividad: " . $e->getMessage());
    }
}

// Función para mostrar alertas
function showAlert($message, $type = 'info') {
    echo "<div class='alert alert-{$type} alert-dismissible fade show' role='alert'>
            {$message}
            <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
          </div>";
}

// Función para validar cédula (solo longitud)
function validateCedula($cedula) {
    // Remover espacios y guiones
    $cedula = str_replace([' ', '-'], '', $cedula);
    
    // Verificar que tenga exactamente 9 dígitos
    return preg_match('/^\d{9}$/', $cedula);
}
?>
