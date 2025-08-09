<?php
require_once '../includes/functions.php';
require_once '../models/Usuario.php';
require_once '../models/Bitacora.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = sanitizeInput($_POST['usuario']);
    $contrasena = $_POST['contrasena'];

    if (empty($usuario) || empty($contrasena)) {
        header('Location: ../index.php?error=empty_fields');
        exit();
    }

    try {
        $database = new Database();
        $db = $database->getConnection();

        $userModel = new Usuario($db);
        
        if ($userModel->login($usuario, $contrasena)) {
            $_SESSION['user_id'] = $userModel->id_usuario;
            $_SESSION['username'] = $userModel->usuario;
            $_SESSION['user_role'] = $userModel->rol;
            $_SESSION['last_activity'] = time();
            $_SESSION['login_time'] = time();

            logActivity($userModel->id_usuario, "Inicio de sesión exitoso");

            header('Location: ../dashboard.php');
            exit();
        } else {
            error_log("Intento de login fallido para usuario: " . $usuario);
            header('Location: ../index.php?error=invalid_credentials');
            exit();
        }
    } catch (Exception $e) {
        error_log("Error en autenticación: " . $e->getMessage());
        header('Location: ../index.php?error=system_error');
        exit();
    }
} else {
    header('Location: ../index.php');
    exit();
}
?>
