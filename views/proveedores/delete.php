<?php
require_once '../../includes/functions.php';
require_once '../../models/Proveedor.php';

requireLogin();
checkSessionTimeout();

// ID por GET (link) o POST (formulario)
$id = (int)($_GET['id'] ?? $_POST['id_proveedor'] ?? 0);
if ($id <= 0) {
    header("Location: index.php?error=invalid_id");
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();
    $proveedorModel = new Proveedor($db);
    
    // Verificar que el proveedor existe
    $proveedorModel->id_proveedor = $id;
    if (!$proveedorModel->readOne()) {
        throw new Exception("Proveedor no encontrado");
    }
    // Necesitamos datos para log (nombre)
    $proveedor = [
        'Nombre_Proveedor' => $proveedorModel->nombre_proveedor
    ];
    
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
