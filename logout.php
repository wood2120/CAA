<?php
require_once 'includes/functions.php';

// Verificar si está logueado
requireLogin();
checkSessionTimeout();

// Registrar actividad
logActivity($_SESSION['user_id'], "Acceso al cerrar sesión");

// Destruir sesión
session_unset();
session_destroy();

// Redirigir al login
header('Location: index.php?logout=1');
exit();
?>
