<?php
require_once '../../includes/functions.php';
require_once '../../models/Proveedor.php';

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
    $proveedorModel = new Proveedor($db);
    
    // Verificar que el proveedor existe
    $proveedor = $proveedorModel->readOne($id);
    if (!$proveedor) {
        throw new Exception("Proveedor no encontrado");
    }
    
    // Verificar si el proveedor tiene items en inventario
    $queryCheck = "SELECT COUNT(*) as total FROM TB_Inventario WHERE ID_Proveedor = :id";
    $stmtCheck = $db->prepare($queryCheck);
    $stmtCheck->bindParam(':id', $id);
    $stmtCheck->execute();
    $tieneItems = $stmtCheck->fetch(PDO::FETCH_ASSOC)['total'] > 0;
    
    if ($tieneItems) {
        // No eliminar si tiene items, redirigir con mensaje
        header("Location: index.php?error=" . urlencode("No se puede eliminar el proveedor porque tiene items asociados en el inventario"));
        exit();
    }
    
    $proveedorModel->id_proveedor = $id;
    
    if ($proveedorModel->delete()) {
        logActivity($_SESSION['user_id'], "Proveedor eliminado: " . $proveedor['Nombre_Proveedor']);
        header("Location: index.php?success=deleted");
    } else {
        header("Location: index.php?error=" . urlencode("Error al eliminar el proveedor"));
    }
    
} catch (Exception $e) {
    header("Location: index.php?error=" . urlencode($e->getMessage()));
}
exit();
?>
