<?php
require_once '../../includes/functions.php';
require_once '../../config/database.php';
require_once '../../models/Cliente.php';

requireLogin();
checkSessionTimeout();

$pageTitle = 'Gestión de Clientes';
include '../../includes/header.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    $clienteModel = new Cliente($db);
    
    $searchTerm = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
    
    if (!empty($searchTerm)) {
        $stmt = $clienteModel->search($searchTerm);
    } else {
        $stmt = $clienteModel->readAll();
    }
    
} catch (Exception $e) {
    $error = "Error al cargar clientes: " . $e->getMessage();
}

logActivity($_SESSION['user_id'], "Acceso a gestión de clientes");
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-users"></i> Gestión de Clientes
        </h1>
        <a href="create.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nuevo Cliente
        </a>
    </div>

    <?php if (isset($error)): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
    </div>
    <?php endif; ?>

    <?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">
        <i class="fas fa-check"></i>
        <?php 
        switch($_GET['success']) {
            case 'created': echo 'Cliente creado exitosamente.'; break;
            case 'updated': echo 'Cliente actualizado exitosamente.'; break;
            case 'deleted': echo 'Cliente eliminado exitosamente.'; break;
            default: echo 'Operación realizada exitosamente.';
        }
        ?>
    </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-triangle"></i>
        <?php 
        switch($_GET['error']) {
            case 'not_found': echo 'Cliente no encontrado.'; break;
            case 'has_trabajos': echo 'No se puede eliminar el cliente porque tiene trabajos asociados.'; break;
            case 'cedula_exists': echo 'Ya existe un cliente con esa cédula.'; break;
            case 'invalid_cedula': echo 'La cédula ingresada no es válida.'; break;
            default: echo 'Ocurrió un error al procesar la solicitud.';
        }
        ?>
    </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filtros</h6>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-8">
                    <div class="input-group">
                        <input type="text" class="form-control" name="search" 
                               placeholder="Buscar por nombre, cédula o empresa..." 
                               value="<?php echo htmlspecialchars($searchTerm); ?>">
                        <button class="btn btn-outline-secondary" type="submit">
                            <i class="fas fa-search"></i> Buscar
                        </button>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="d-grid gap-2 d-md-flex">
                        <a href="index.php" class="btn btn-outline-secondary">
                            <i class="fas fa-times"></i> Limpiar
                        </a>
                        <button type="button" class="btn btn-outline-success" onclick="exportTable()">
                            <i class="fas fa-download"></i> Exportar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                Lista de Clientes
                <?php if (!empty($searchTerm)): ?>
                    - Resultados para: "<?php echo htmlspecialchars($searchTerm); ?>"
                <?php endif; ?>
            </h6>
        </div>
        <div class="card-body">
            <?php if (isset($stmt) && $stmt->rowCount() > 0): ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="clientesTable">
                    <thead class="table-light">
                        <tr>
                            <th>Cédula</th>
                            <th>Nombre</th>
                            <th>Contacto</th>
                            <th>Empresa</th>
                            <th class="text-center no-print">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['Cedula']); ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($row['Nombre']); ?></strong>
                            </td>
                            <td>
                                <?php if (!empty($row['Contacto'])): ?>
                                    <a href="tel:<?php echo htmlspecialchars($row['Contacto']); ?>" class="text-decoration-none">
                                        <i class="fas fa-phone"></i> <?php echo htmlspecialchars($row['Contacto']); ?>
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">No disponible</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo !empty($row['Empresa']) ? htmlspecialchars($row['Empresa']) : '<span class="text-muted">No especificada</span>'; ?>
                            </td>
                            <td class="text-center no-print">
                                <div class="btn-group" role="group">
                                    <a href="view.php?cedula=<?php echo urlencode($row['Cedula']); ?>" 
                                       class="btn btn-sm btn-outline-info" title="Ver detalles">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="edit.php?cedula=<?php echo urlencode($row['Cedula']); ?>" 
                                       class="btn btn-sm btn-outline-warning" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                            onclick="deleteCliente('<?php echo htmlspecialchars($row['Cedula']); ?>', '<?php echo htmlspecialchars($row['Nombre']); ?>')" 
                                            title="Eliminar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">
                    <?php echo !empty($searchTerm) ? 'No se encontraron clientes que coincidan con la búsqueda.' : 'No hay clientes registrados.'; ?>
                </h5>
                <?php if (empty($searchTerm)): ?>
                <a href="create.php" class="btn btn-primary mt-3">
                    <i class="fas fa-plus"></i> Crear Primer Cliente
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function deleteCliente(cedula, nombre) {
    if (SistemaKris.confirmDelete(`¿Está seguro de que desea eliminar al cliente "${nombre}"?\n\nEsta acción no se puede deshacer.`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'delete.php';
        
        const cedulaInput = document.createElement('input');
        cedulaInput.type = 'hidden';
        cedulaInput.name = 'cedula';
        cedulaInput.value = cedula;
        
        form.appendChild(cedulaInput);
        document.body.appendChild(form);
        form.submit();
    }
}

function exportTable() {
    Utils.exportTableToCSV('clientesTable', 'clientes_' + new Date().toISOString().slice(0,10) + '.csv');
}

document.addEventListener('DOMContentLoaded', function() {
    SistemaKris.initDataTable('clientesTable', {
        order: [[1, 'asc']], 
        columnDefs: [
            { orderable: false, targets: [4] } 
        ]
    });
});
</script>

<?php include '../../includes/footer.php'; ?>
