<?php
require_once '../../includes/functions.php';
require_once '../../models/Usuario.php';

requireLogin();
checkSessionTimeout();
requireRole('Administrador');

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id = (int)$_GET['id'];

// No permitir que el usuario se elimine a sí mismo
if ($id == $_SESSION['user_id']) {
    header("Location: index.php?error=" . urlencode("No puedes eliminarte a ti mismo"));
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();
    $usuarioModel = new Usuario($db);
    
    // Verificar que el usuario existe
    $usuario = $usuarioModel->readOne($id);
    if (!$usuario) {
        throw new Exception("Usuario no encontrado");
    }
    
    $usuarioModel->id_usuario = $id;
    
    if ($usuarioModel->delete()) {
        logActivity($_SESSION['user_id'], "Usuario eliminado: " . $usuario['Usuario']);
        header("Location: index.php?success=deleted");
    } else {
        header("Location: index.php?error=" . urlencode("Error al eliminar el usuario"));
    }
    
} catch (Exception $e) {
    header("Location: index.php?error=" . urlencode($e->getMessage()));
}
exit();
?>
