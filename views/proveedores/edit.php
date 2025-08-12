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
$pageTitle = 'Editar Proveedor';
include '../../includes/header.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    $proveedorModel = new Proveedor($db);
    
    // Obtener datos del proveedor
    $proveedorModel->id_proveedor = $id;
    if (!$proveedorModel->readOne()) {
        throw new Exception("Proveedor no encontrado");
    }
    // Construimos array para facilitar el código existente
    // Normalizamos a string vacío para evitar deprecations al usar htmlspecialchars con null
    $proveedor = [
        'ID_Proveedor'     => $id,
        'Nombre_Proveedor' => $proveedorModel->nombre_proveedor ?? '',
        'Contacto'         => $proveedorModel->contacto ?? '',
        'Email'            => $proveedorModel->email ?? '',
        'Direccion'        => $proveedorModel->direccion ?? '',
        'Estado'           => $proveedorModel->estado ?? 'Activo'
    ];
    
} catch (Exception $e) {
    header("Location: index.php?error=" . urlencode($e->getMessage()));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $proveedorModel->id_proveedor = $id;
        $proveedorModel->nombre_proveedor = sanitizeInput($_POST['nombre_proveedor']);
        $proveedorModel->contacto = sanitizeInput($_POST['contacto']);
        $proveedorModel->email = sanitizeInput($_POST['email']);
        $proveedorModel->direccion = sanitizeInput($_POST['direccion']);
        
        if ($proveedorModel->update()) {
            logActivity($_SESSION['user_id'], "Proveedor editado: " . $proveedorModel->nombre_proveedor);
            header("Location: index.php?success=updated");
            exit();
        } else {
            $error = "Error al actualizar el proveedor";
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-edit"></i> Editar Proveedor
        </h1>
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>

    <?php if (isset($error)): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
    </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Información del Proveedor</h6>
                </div>
                <div class="card-body">
                    <form method="POST" id="proveedorForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="nombre_proveedor" class="form-label">
                                        <i class="fas fa-building"></i> Nombre del Proveedor <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="nombre_proveedor" name="nombre_proveedor" 
                                           required maxlength="150" 
                                           value="<?php echo htmlspecialchars($proveedor['Nombre_Proveedor']); ?>">
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="contacto" class="form-label">
                                        <i class="fas fa-phone"></i> Teléfono de Contacto
                                    </label>
                                    <input type="text" class="form-control" id="contacto" name="contacto" 
                                           maxlength="100" 
                                           value="<?php echo htmlspecialchars($proveedor['Contacto'] ?? ''); ?>">
                                    <div class="form-text">Formato: 2222-3333 o 8888-7777</div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">
                                        <i class="fas fa-envelope"></i> Correo Electrónico
                                    </label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           maxlength="100" 
                                           value="<?php echo htmlspecialchars($proveedor['Email'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="direccion" class="form-label">
                                        <i class="fas fa-map-marker-alt"></i> Dirección
                                    </label>
                                    <textarea class="form-control" id="direccion" name="direccion" 
                                              rows="3"><?php echo htmlspecialchars($proveedor['Direccion'] ?? ''); ?></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="index.php" class="btn btn-secondary me-md-2">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Actualizar Proveedor
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Información Actual</h6>
                </div>
                <div class="card-body">
                    <p><strong>ID:</strong> <?php echo $proveedor['ID_Proveedor']; ?></p>
                    <p><strong>Nombre:</strong> <?php echo htmlspecialchars($proveedor['Nombre_Proveedor']); ?></p>
                    <p><strong>Contacto:</strong> <?php echo htmlspecialchars($proveedor['Contacto'] ?? 'No especificado'); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($proveedor['Email'] ?? 'No especificado'); ?></p>
                    <p><strong>Estado:</strong> 
                        <span class="badge badge-<?php echo ($proveedor['Estado'] == 'Activo') ? 'success' : 'secondary'; ?>">
                            <?php echo htmlspecialchars($proveedor['Estado']); ?>
                        </span>
                    </p>
                </div>
            </div>

            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Estadísticas</h6>
                </div>
                <div class="card-body">
                    <?php
                    // Obtener estadísticas del proveedor
                    $queryStats = "SELECT COUNT(*) as total_items FROM TB_Inventario WHERE ID_Proveedor = :id AND Estado = 'Activo'";
                    $stmtStats = $db->prepare($queryStats);
                    $stmtStats->bindParam(':id', $id);
                    $stmtStats->execute();
                    $stats = $stmtStats->fetch(PDO::FETCH_ASSOC);
                    ?>
                    <p><strong>Items en Inventario:</strong> <?php echo number_format($stats['total_items']); ?></p>
                    
                    <hr>
                    
                    <div class="d-grid gap-2">
                        <a href="../inventario/index.php?proveedor=<?php echo $id; ?>" class="btn btn-info btn-sm">
                            <i class="fas fa-boxes"></i> Ver Items del Proveedor
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Validación del formulario
document.getElementById('proveedorForm').addEventListener('submit', function(e) {
    const nombre = document.getElementById('nombre_proveedor').value.trim();
    
    if (nombre.length < 2) {
        e.preventDefault();
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'El nombre del proveedor debe tener al menos 2 caracteres'
        });
        return false;
    }
});

// Formatear teléfono automáticamente
document.getElementById('contacto').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length >= 4) {
        value = value.substring(0, 4) + '-' + value.substring(4, 8);
    }
    e.target.value = value;
});
</script>

<?php include '../../includes/footer.php'; ?>
