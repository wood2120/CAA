<?php
require_once '../../includes/functions.php';
require_once '../../models/Usuario.php';

requireLogin();
checkSessionTimeout();
requireRole('Administrador');

// ID por POST (form) o GET (enlace viejo)
$id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: index.php?error=not_found");
    exit();
}

// No permitir que el usuario se elimine a sí mismo
if ($id == $_SESSION['user_id']) {
    header("Location: index.php?error=self_delete");
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();
    $usuarioModel = new Usuario($db);
    
    // Verificar que el usuario existe
    $usuario = $usuarioModel->readOne($id);
    if (!$usuario) {
        header("Location: index.php?error=not_found");
        exit();
    }
    
    $usuarioModel->id_usuario = $id;
    
    if ($usuarioModel->delete()) {
        logActivity($_SESSION['user_id'], "Usuario eliminado: " . $usuario['Usuario']);
        header("Location: index.php?success=deleted");
    } else {
        header("Location: index.php?error=delete_failed");
    }
    
} catch (Exception $e) {
    header("Location: index.php?error=delete_failed");
}
exit();
?>
