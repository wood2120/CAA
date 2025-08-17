<?php
require_once '../../includes/functions.php';
require_once '../../models/Usuario.php';

requireLogin();
checkSessionTimeout();
requireRole('Administrador');

// Solo aceptar POST (patrón uniforme con otras vistas)
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id'])) {
    header('Location: index.php?error=not_found');
    exit();
}

$id = (int)$_POST['id'];
if ($id <= 0) {
    header('Location: index.php?error=not_found');
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
    
    // Verificar referencias (bitácora, movimientos inventario)
    $refs = [
        'bitacora' => 0,
        'movimientos' => 0
    ];
    $stmtRef = $db->prepare("SELECT COUNT(*) c FROM TB_Bitacora WHERE ID_Usuario = :id");
    $stmtRef->execute([':id' => $id]);
    $refs['bitacora'] = (int)$stmtRef->fetchColumn();

    $stmtRef = $db->prepare("SELECT COUNT(*) c FROM TB_Movimientos_Inventario WHERE ID_Usuario = :id");
    $stmtRef->execute([':id' => $id]);
    $refs['movimientos'] = (int)$stmtRef->fetchColumn();

    if ($refs['bitacora'] > 0 || $refs['movimientos'] > 0) {
        header('Location: index.php?error=has_refs');
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
