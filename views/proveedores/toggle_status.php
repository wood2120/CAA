<?php
require_once '../../includes/functions.php';
require_once '../../models/Proveedor.php';

requireLogin();
checkSessionTimeout();

$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$action = isset($_GET['action']) ? sanitizeInput($_GET['action']) : '';

if ($id <= 0 || !in_array($action, ['activate','deactivate'])) {
	header('Location: index.php?error=invalid_id');
	exit();
}

try {
	$database = new Database();
	$db = $database->getConnection();
	$proveedorModel = new Proveedor($db);
	$proveedorModel->id_proveedor = $id;

	if (!$proveedorModel->readOne()) {
		header('Location: index.php?error=not_found');
		exit();
	}

	$nuevoEstado = ($action === 'activate') ? 'Activo' : 'Inactivo';
	if (!$proveedorModel->soportaEstado()) {
		header('Location: index.php?error=not_supported');
		exit();
	}
	if ($proveedorModel->toggleEstado($nuevoEstado)) {
		logActivity($_SESSION['user_id'], "Proveedor {$action} ID: $id");
		header('Location: index.php?success=' . ($action === 'activate' ? 'activated' : 'deactivated'));
	} else {
		header('Location: index.php?error=unexpected');
	}
} catch (Exception $e) {
	header('Location: index.php?error=' . urlencode($e->getMessage()));
}
exit();
?>
