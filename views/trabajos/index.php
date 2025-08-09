<?php
require_once '../../includes/functions.php';
require_once '../../config/database.php';
require_once '../../models/Trabajo.php';
require_once '../../models/Cliente.php';

requireLogin();
checkSessionTimeout();

$pageTitle = 'Gestión de Trabajos';
include '../../includes/header.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    $trabajoModel = new Trabajo($db);
    
    $searchTerm = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
    
    if (!empty($searchTerm)) {
        $stmt = $trabajoModel->search($searchTerm);
    } else {
        $stmt = $trabajoModel->readAll();
    }
    
} catch (Exception $e) {
    $error = "Error al cargar trabajos: " . $e->getMessage();
}

logActivity($_SESSION['user_id'], "Acceso a gestión de trabajos");
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-briefcase"></i> Gestión de Trabajos
        </h1>
        <a href="create.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nuevo Trabajo
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
            case 'created': echo 'Trabajo creado exitosamente.'; break;
            case 'updated': echo 'Trabajo actualizado exitosamente.'; break;
            case 'deleted': echo 'Trabajo eliminado exitosamente.'; break;
            default: echo 'Operación realizada exitosamente.';
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
                               placeholder="Buscar por tipo de trabajo o cliente..." 
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
                Lista de Trabajos
                <?php if (!empty($searchTerm)): ?>
                    - Resultados para: "<?php echo htmlspecialchars($searchTerm); ?>"
                <?php endif; ?>
            </h6>
        </div>
        <div class="card-body">
            <?php if (isset($stmt) && $stmt->rowCount() > 0): ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="trabajosTable">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Cliente</th>
                            <th>Tipo de Trabajo</th>
                            <th>Precio</th>
                            <th>Fecha Inicio</th>
                            <th>Fecha Final</th>
                            <th class="text-center no-print">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['ID_Trabajo']); ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($row['cliente_nombre'] ?? 'Cliente no encontrado'); ?></strong>
                                <br><small class="text-muted"><?php echo htmlspecialchars($row['Cedula_Cliente']); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($row['Tipo_Trabajo']); ?></td>
                            <td><strong><?php echo formatCurrency($row['Precio']); ?></strong></td>
                            <td><?php echo formatDate($row['Fecha_Inicio']); ?></td>
                            <td><?php echo formatDate($row['Fecha_Final']); ?></td>
                            <td class="text-center no-print">
                                <div class="btn-group" role="group">
                                    <a href="view.php?id=<?php echo $row['ID_Trabajo']; ?>" 
                                       class="btn btn-sm btn-outline-info" title="Ver detalles">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="edit.php?id=<?php echo $row['ID_Trabajo']; ?>" 
                                       class="btn btn-sm btn-outline-warning" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                            onclick="deleteTrabajo('<?php echo $row['ID_Trabajo']; ?>', '<?php echo htmlspecialchars($row['Tipo_Trabajo']); ?>')" 
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
                <i class="fas fa-briefcase fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">
                    <?php echo !empty($searchTerm) ? 'No se encontraron trabajos que coincidan con la búsqueda.' : 'No hay trabajos registrados.'; ?>
                </h5>
                <?php if (empty($searchTerm)): ?>
                <a href="create.php" class="btn btn-primary mt-3">
                    <i class="fas fa-plus"></i> Crear Primer Trabajo
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function deleteTrabajo(id, tipo) {
    if (SistemaKris.confirmDelete(`¿Está seguro de que desea eliminar el trabajo "${tipo}"?\n\nEsta acción no se puede deshacer.`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'delete.php';
        
        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'id_trabajo';
        idInput.value = id;
        
        form.appendChild(idInput);
        document.body.appendChild(form);
        form.submit();
    }
}

function exportTable() {
    Utils.exportTableToCSV('trabajosTable', 'trabajos_' + new Date().toISOString().slice(0,10) + '.csv');
}

document.addEventListener('DOMContentLoaded', function() {
    SistemaKris.initDataTable('trabajosTable', {
        order: [[4, 'desc']],
        columnDefs: [
            { orderable: false, targets: [6] }
        ]
    });
});
</script>

<?php include '../../includes/footer.php'; ?>
