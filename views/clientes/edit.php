<?php
require_once '../../includes/functions.php';
require_once '../../models/Cliente.php';

requireLogin();
checkSessionTimeout();

if (!isset($_GET['cedula'])) {
    header("Location: index.php");
    exit();
}

$cedula = sanitizeInput($_GET['cedula']);
$pageTitle = 'Editar Cliente';
include '../../includes/header.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    $clienteModel = new Cliente($db);
    
    // Obtener datos del cliente
    $cliente = $clienteModel->readOne($cedula);
    
    if (!$cliente) {
        throw new Exception("Cliente no encontrado");
    }
    
} catch (Exception $e) {
    header("Location: index.php?error=" . urlencode($e->getMessage()));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Validar si se está cambiando la cédula
        $nueva_cedula = sanitizeInput($_POST['cedula']);
        
        if ($nueva_cedula !== $cedula) {
            // Verificar que la nueva cédula no exista
            $clienteExistente = $clienteModel->readOne($nueva_cedula);
            if ($clienteExistente) {
                throw new Exception("Ya existe un cliente con la cédula: " . $nueva_cedula);
            }
        }
        
    $clienteModel->Cedula = $nueva_cedula;
    $clienteModel->Nombre = sanitizeInput($_POST['nombre']);
    $clienteModel->Contacto = sanitizeInput($_POST['contacto']);
    $clienteModel->Empresa = sanitizeInput($_POST['empresa']);
        
        if ($clienteModel->update($cedula)) {
            logActivity($_SESSION['user_id'], "Cliente editado: " . $clienteModel->nombre);
            header("Location: index.php?success=updated");
            exit();
        } else {
            $error = "Error al actualizar el cliente";
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-user-edit"></i> Editar Cliente
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
                    <h6 class="m-0 font-weight-bold text-primary">Información del Cliente</h6>
                </div>
                <div class="card-body">
                    <form method="POST" id="clienteForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="cedula" class="form-label">
                                        <i class="fas fa-id-card"></i> Cédula <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="cedula" name="cedula" 
                                           required maxlength="15" 
                                           value="<?php echo htmlspecialchars($cliente['Cedula']); ?>">
                                    <div class="form-text">Formato: 1-1234-5678 o similar</div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="nombre" class="form-label">
                                        <i class="fas fa-user"></i> Nombre Completo <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="nombre" name="nombre" 
                                           required maxlength="150" 
                                           value="<?php echo htmlspecialchars($cliente['Nombre']); ?>">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="contacto" class="form-label">
                                        <i class="fas fa-phone"></i> Teléfono de Contacto
                                    </label>
                                    <input type="text" class="form-control" id="contacto" name="contacto" 
                                           maxlength="100" 
                                           value="<?php echo htmlspecialchars($cliente['Contacto']); ?>">
                                    <div class="form-text">Formato: 2222-3333 o 8888-7777</div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="empresa" class="form-label">
                                        <i class="fas fa-building"></i> Empresa (Opcional)
                                    </label>
                                    <input type="text" class="form-control" id="empresa" name="empresa" 
                                           maxlength="150" 
                                           value="<?php echo htmlspecialchars($cliente['Empresa']); ?>">
                                    <div class="form-text">Nombre de la empresa o negocio</div>
                                </div>
                            </div>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="index.php" class="btn btn-secondary me-md-2">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Actualizar Cliente
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
                    <p><strong>Cédula:</strong> <?php echo htmlspecialchars($cliente['Cedula']); ?></p>
                    <p><strong>Nombre:</strong> <?php echo htmlspecialchars($cliente['Nombre']); ?></p>
                    <p><strong>Contacto:</strong> <?php echo htmlspecialchars($cliente['Contacto'] ?? 'No especificado'); ?></p>
                    <p><strong>Empresa:</strong> <?php echo htmlspecialchars($cliente['Empresa'] ?? 'No especificada'); ?></p>
                </div>
            </div>

            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Historial de Trabajos</h6>
                </div>
                <div class="card-body">
                    <?php
                    // Obtener estadísticas del cliente
                    $queryStats = "SELECT 
                                     COUNT(*) as total_trabajos,
                                     COUNT(CASE WHEN Estado = 'Completado' THEN 1 END) as completados,
                                     SUM(CASE WHEN Estado = 'Completado' THEN Precio_Total ELSE 0 END) as total_facturado
                                   FROM TB_Trabajos 
                                   WHERE Cedula_Cliente = :cedula";
                    $stmtStats = $db->prepare($queryStats);
                    $stmtStats->bindParam(':cedula', $cedula);
                    $stmtStats->execute();
                    $stats = $stmtStats->fetch(PDO::FETCH_ASSOC);
                    ?>
                    <p><strong>Total Trabajos:</strong> <?php echo number_format($stats['total_trabajos']); ?></p>
                    <p><strong>Completados:</strong> <?php echo number_format($stats['completados']); ?></p>
                    <p><strong>Total Facturado:</strong> <?php echo formatCurrency($stats['total_facturado']); ?></p>
                    
                    <hr>
                    
                    <div class="d-grid gap-2">
                        <a href="../trabajos/index.php?cliente=<?php echo urlencode($cedula); ?>" class="btn btn-info btn-sm">
                            <i class="fas fa-briefcase"></i> Ver Trabajos del Cliente
                        </a>
                        <?php if ($stats['total_trabajos'] == 0): ?>
                        <div class="alert alert-info alert-sm">
                            <i class="fas fa-info-circle"></i> Este cliente no tiene trabajos registrados.
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Validación del formulario
document.getElementById('clienteForm').addEventListener('submit', function(e) {
    const cedula = document.getElementById('cedula').value.trim();
    const nombre = document.getElementById('nombre').value.trim();
    
    if (cedula.length < 5) {
        e.preventDefault();
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'La cédula debe tener al menos 5 caracteres'
        });
        return false;
    }
    
    if (nombre.length < 2) {
        e.preventDefault();
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'El nombre debe tener al menos 2 caracteres'
        });
        return false;
    }
});

// Formatear cédula automáticamente
document.getElementById('cedula').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length >= 9) {
        value = value.substring(0, 1) + '-' + value.substring(1, 5) + '-' + value.substring(5, 9);
    }
    e.target.value = value;
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
