<?php
require_once '../../includes/functions.php';
require_once '../../models/Inventario.php';

requireLogin();
checkSessionTimeout();

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id = (int)$_GET['id'];

try {
    $database = new Database();
    $db = $database->getConnection();
    $inventarioModel = new Inventario($db);
    
    // Verificar que el item existe
    $item = $inventarioModel->readOne($id);
    if (!$item) {
        throw new Exception("Item no encontrado");
    }
    
    // Verificar si el item ha sido usado en trabajos
    $queryCheck = "SELECT COUNT(*) as total FROM TB_Trabajo_Inventario WHERE ID_Inventario = :id";
    $stmtCheck = $db->prepare($queryCheck);
    $stmtCheck->bindParam(':id', $id);
    $stmtCheck->execute();
    $usadoEnTrabajos = $stmtCheck->fetch(PDO::FETCH_ASSOC)['total'] > 0;
    
    if ($usadoEnTrabajos) {
        // No eliminar, solo marcar como inactivo
        $inventarioModel->id_inventario = $id;
        $inventarioModel->estado = 'Inactivo';
        
        if ($inventarioModel->updateEstado()) {
            logActivity($_SESSION['user_id'], "Item de inventario marcado como inactivo: " . $item['Nombre']);
            header("Location: index.php?success=deactivated&item=" . urlencode($item['Nombre']));
        } else {
            header("Location: index.php?error=" . urlencode("Error al desactivar el item"));
        }
    } else {
        // Eliminar completamente
        $inventarioModel->id_inventario = $id;
        
        if ($inventarioModel->delete()) {
            logActivity($_SESSION['user_id'], "Item de inventario eliminado: " . $item['Nombre']);
            header("Location: index.php?success=deleted");
        } else {
            header("Location: index.php?error=" . urlencode("Error al eliminar el item"));
        }
    }
    
} catch (Exception $e) {
    header("Location: index.php?error=" . urlencode($e->getMessage()));
}
exit();
?>
