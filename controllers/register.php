<?php
require_once '../includes/functions.php';
require_once '../models/Usuario.php';
require_once '../models/Bitacora.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = sanitizeInput($_POST['usuario']);
    $contrasena = $_POST['contrasena'];
    $confirmar_contrasena = $_POST['confirmar_contrasena'];
    $acepto_terminos = isset($_POST['acepto_terminos']);

    $errors = [];

    if (empty($usuario)) {
        $errors[] = 'El nombre de usuario es obligatorio.';
    } elseif (strlen($usuario) < 4) {
        $errors[] = 'El nombre de usuario debe tener al menos 4 caracteres.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $usuario)) {
        $errors[] = 'El nombre de usuario solo puede contener letras, números y guión bajo.';
    }

    if (empty($contrasena)) {
        $errors[] = 'La contraseña es obligatoria.';
    } elseif (strlen($contrasena) < 6) {
        $errors[] = 'La contraseña debe tener al menos 6 caracteres.';
    }

    if ($contrasena !== $confirmar_contrasena) {
        $errors[] = 'Las contraseñas no coinciden.';
    }

    if (!$acepto_terminos) {
        $errors[] = 'Debe aceptar los términos y condiciones.';
    }

    if (empty($errors)) {
        try {
            $database = new Database();
            $db = $database->getConnection();
            $userModel = new Usuario($db);

            $userModel->usuario = $usuario;
            $userModel->contrasena = $contrasena;
            $userModel->rol = 'Dueño'; 
            $userModel->id_usuario = 0; 
            if ($userModel->exists()) {
                $errors[] = 'El nombre de usuario ya está en uso.';
            }

            if (empty($errors)) {
                if ($userModel->create()) {
                    $userModel->usuario = $usuario;
                    $stmt = $db->prepare("SELECT ID_Usuario FROM TB_Usuarios WHERE Usuario = :usuario");
                    $stmt->bindParam(':usuario', $usuario);
                    $stmt->execute();
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($row) {
                        logActivity($row['ID_Usuario'], "Nuevo usuario registrado: {$usuario}");
                    }
                    
                    header('Location: ../index.php?success=registered');
                    exit();
                } else {
                    $errors[] = 'Error al crear la cuenta. Por favor, inténtelo nuevamente.';
                }
            }
        } catch (Exception $e) {
            error_log("Error en registro de usuario: " . $e->getMessage());
            $errors[] = 'Error del sistema. Por favor, inténtelo más tarde.';
        }
    }

    if (!empty($errors)) {
        $errorMsg = implode('|', $errors);
        $queryParams = http_build_query([
            'errors' => $errorMsg,
            'usuario' => $usuario
        ]);
        header('Location: ../register.php?' . $queryParams);
        exit();
    }
} else {
    header('Location: ../register.php');
    exit();
}
?>
