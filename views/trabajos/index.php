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
    $exportar = $_GET['exportar'] ?? '';
    
    if (!empty($searchTerm)) {
        $stmt = $trabajoModel->search($searchTerm);
    } else {
        $stmt = $trabajoModel->readAll();
    }
    // Obtener todos los registros para reutilizar (tabla y posible exportación)
    $trabajos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Exportación CSV limpia (evita desalineación por HTML interno)
    if ($exportar === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=trabajos_' . date('Y-m-d') . '.csv');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF"); // BOM UTF-8
        // Encabezados normalizados
        fputcsv($out, ['ID_Trabajo','Cliente','Cedula_Cliente','Tipo_Trabajo','Precio','Fecha_Inicio','Fecha_Final']);
        foreach ($trabajos as $t) {
            // Campos variables según consulta del modelo
            $clienteNombre = $t['cliente_nombre'] ?? ($t['NombreCliente'] ?? '');
            $cedula = $t['Cedula_Cliente'] ?? ($t['Cedula'] ?? '');
            $precioRaw = $t['Precio'] ?? ($t['Precio_Total'] ?? 0);
            fputcsv($out, [
                $t['ID_Trabajo'] ?? '',
                preg_replace("/[\r\n]+/", ' ', $clienteNombre),
                $cedula,
                preg_replace("/[\r\n]+/", ' ', ($t['Tipo_Trabajo'] ?? '')),
                number_format((float)$precioRaw, 2, '.', ''),
                !empty($t['Fecha_Inicio']) ? date('Y-m-d', strtotime($t['Fecha_Inicio'])) : '',
                !empty($t['Fecha_Final']) ? date('Y-m-d', strtotime($t['Fecha_Final'])) : ''
            ]);
        }
        fclose($out);
        exit;
    }
    
} catch (Exception $e) {
    $error = "Error al cargar trabajos: " . $e->getMessage();
    $trabajos = [];
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
                        <a href="?exportar=csv<?php echo $searchTerm ? '&search=' . urlencode($searchTerm) : ''; ?>" class="btn btn-outline-success">
                            <i class="fas fa-file-csv"></i> Exportar CSV
                        </a>
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
            <?php if (!empty($trabajos)): ?>
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
                        <?php foreach ($trabajos as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['ID_Trabajo']); ?></td>
                            <td>
                                <?php 
                                    $nombreCliente = $row['cliente_nombre'] 
                                        ?? $row['NombreCliente'] 
                                        ?? 'Cliente no encontrado';
                                ?>
                                <strong><?php echo htmlspecialchars($nombreCliente); ?></strong>
                                <?php if (!empty($row['Cedula_Cliente'])): ?>
                                <br><small class="text-muted"><?php echo htmlspecialchars($row['Cedula_Cliente']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($row['Tipo_Trabajo']); ?></td>
                            <td><strong><?php 
                                $precio = $row['Precio'] ?? $row['Precio_Total'] ?? 0; 
                                echo formatCurrency($precio); 
                            ?></strong></td>
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
                        <?php endforeach; ?>
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
// Eliminada función exportTable (servidor genera CSV limpio)
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

document.addEventListener('DOMContentLoaded', function() {
    SistemaKris.initDataTable('trabajosTable', {
        order: [[4, 'desc']],
        columnDefs: [ { orderable: false, targets: [6] } ]
    });
});
</script>

<?php include '../../includes/footer.php'; ?>
