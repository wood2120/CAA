<?php
require_once '../../includes/functions.php';
require_once '../../models/Cliente.php';

requireLogin();
checkSessionTimeout();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['cedula'])) {
    header('Location: index.php?error=invalid_request');
    exit();
}

$cedula = sanitizeInput($_POST['cedula']);

try {
    $database = new Database();
    $db = $database->getConnection();
    $clienteModel = new Cliente($db);
    
    $clienteModel->cedula = $cedula;
    
    if ($clienteModel->delete()) {
        logActivity($_SESSION['user_id'], "Cliente eliminado: Cédula {$cedula}");
        header('Location: index.php?success=deleted');
    } else {
        header('Location: index.php?error=has_trabajos');
    }
} catch (Exception $e) {
    error_log("Error al eliminar cliente: " . $e->getMessage());
    header('Location: index.php?error=system_error');
}
exit();
?>
