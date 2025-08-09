<?php
require_once '../../includes/functions.php';
require_once '../../models/Trabajo.php';

requireLogin();
checkSessionTimeout();

if (!isset($_GET['id']) || !isset($_GET['estado'])) {
    header("Location: index.php");
    exit();
}

$id = (int)$_GET['id'];
$nuevoEstado = sanitizeInput($_GET['estado']);

// Validar estado
$estadosValidos = ['Pendiente', 'En Proceso', 'Completado', 'Cancelado'];
if (!in_array($nuevoEstado, $estadosValidos)) {
    header("Location: index.php?error=" . urlencode("Estado no válido"));
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();
    $trabajoModel = new Trabajo($db);
    
    // Verificar que el trabajo existe
    $trabajo = $trabajoModel->readOne($id);
    if (!$trabajo) {
        throw new Exception("Trabajo no encontrado");
    }
    
    // Actualizar solo el estado
    $query = "UPDATE TB_Trabajos SET Estado = :estado WHERE ID_Trabajo = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':estado', $nuevoEstado);
    $stmt->bindParam(':id', $id);
    
    if ($stmt->execute()) {
        logActivity($_SESSION['user_id'], "Estado de trabajo cambiado ID: $id - Estado: $nuevoEstado");
        header("Location: view.php?id=$id&success=estado_actualizado&estado=" . urlencode($nuevoEstado));
    } else {
        header("Location: view.php?id=$id&error=" . urlencode("Error al cambiar el estado"));
    }
    
} catch (Exception $e) {
    header("Location: view.php?id=$id&error=" . urlencode($e->getMessage()));
}
exit();
?>
