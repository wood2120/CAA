<?php
require_once '../../includes/functions.php';
require_once '../../models/Proveedor.php';

requireLogin();
checkSessionTimeout();

$database = new Database();
$db = $database->getConnection();
$proveedorModel = new Proveedor($db);

$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$estado = isset($_GET['estado']) ? sanitizeInput($_GET['estado']) : '';

$proveedores = $proveedorModel->read($search, $estado);
$estadisticas = $proveedorModel->getEstadisticas();

$pageTitle = 'Gestión de Proveedores';
include '../../includes/header.php';
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-truck"></i> Gestión de Proveedores
        </h1>
        <a href="create.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nuevo Proveedor
        </a>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle"></i>
            <?php
            switch ($_GET['success']) {
                case 'created':
                    echo 'Proveedor creado exitosamente.';
                    break;
                case 'updated':
                    echo 'Proveedor actualizado exitosamente.';
                    break;
                case 'deleted':
                    echo 'Proveedor eliminado exitosamente.';
                    break;
                case 'activated':
                    echo 'Proveedor activado exitosamente.';
                    break;
                case 'deactivated':
                    echo 'Proveedor desactivado exitosamente.';
                    break;
            }
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-triangle"></i>
            <?php
            switch ($_GET['error']) {
                case 'invalid_id':
                    echo 'ID de proveedor inválido.';
                    break;
                case 'not_found':
                    echo 'Proveedor no encontrado.';
                    break;
                case 'has_items':
                    echo 'No se puede eliminar el proveedor porque tiene elementos de inventario asociados.';
                    break;
                default:
                    echo 'Se produjo un error inesperado.';
            }
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Proveedores</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $estadisticas['total']; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-truck fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Activos</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $estadisticas['activos']; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Inactivos</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $estadisticas['inactivos']; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-times-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Con Elementos</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $estadisticas['con_elementos']; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-boxes fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <div class="row align-items-center">
                <div class="col">
                    <h6 class="m-0 font-weight-bold text-primary">Lista de Proveedores</h6>
                </div>
                <div class="col-auto">
                    <button class="btn btn-outline-secondary btn-sm" onclick="exportarProveedores()">
                        <i class="fas fa-download"></i> Exportar
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body">
            <form method="GET" class="mb-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="search" class="form-label">Buscar:</label>
                        <input type="text" class="form-control" id="search" name="search" 
                               value="<?php echo htmlspecialchars($search); ?>" 
                               placeholder="Nombre, RNC, teléfono o correo...">
                    </div>
                    <div class="col-md-4">
                        <label for="estado" class="form-label">Estado:</label>
                        <select class="form-control" id="estado" name="estado">
                            <option value="">Todos los estados</option>
                            <option value="Activo" <?php echo $estado == 'Activo' ? 'selected' : ''; ?>>Activo</option>
                            <option value="Inactivo" <?php echo $estado == 'Inactivo' ? 'selected' : ''; ?>>Inactivo</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Buscar
                            </button>
                        </div>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered" id="proveedoresTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>RNC</th>
                            <th>Contacto</th>
                            <th>Dirección</th>
                            <th>Estado</th>
                            <th>Elementos</th>
                            <th>Fecha Registro</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($proveedores)): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                <i class="fas fa-search fa-3x mb-3"></i><br>
                                No se encontraron proveedores que coincidan con los criterios de búsqueda.
                                <?php if (!empty($search) || !empty($estado)): ?>
                                <br><a href="index.php" class="btn btn-link">Ver todos los proveedores</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($proveedores as $proveedor): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($proveedor['nombre_proveedor']); ?></strong>
                                <?php if ($proveedor['contacto_principal']): ?>
                                <br><small class="text-muted">
                                    Contacto: <?php echo htmlspecialchars($proveedor['contacto_principal']); ?>
                                </small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($proveedor['rnc']): ?>
                                    <code><?php echo htmlspecialchars($proveedor['rnc']); ?></code>
                                <?php else: ?>
                                    <span class="text-muted">N/A</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($proveedor['telefono']): ?>
                                    <i class="fas fa-phone fa-sm"></i> <?php echo htmlspecialchars($proveedor['telefono']); ?><br>
                                <?php endif; ?>
                                <?php if ($proveedor['email']): ?>
                                    <i class="fas fa-envelope fa-sm"></i> 
                                    <a href="mailto:<?php echo htmlspecialchars($proveedor['email']); ?>">
                                        <?php echo htmlspecialchars($proveedor['email']); ?>
                                    </a>
                                <?php endif; ?>
                                <?php if (!$proveedor['telefono'] && !$proveedor['email']): ?>
                                    <span class="text-muted">Sin contacto</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($proveedor['direccion']): ?>
                                    <small><?php echo htmlspecialchars($proveedor['direccion']); ?></small>
                                <?php else: ?>
                                    <span class="text-muted">N/A</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge badge-<?php echo $proveedor['estado'] == 'Activo' ? 'success' : 'secondary'; ?>">
                                    <?php echo htmlspecialchars($proveedor['estado']); ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge badge-info">
                                    <?php echo $proveedor['total_elementos'] ?? 0; ?>
                                </span>
                            </td>
                            <td>
                                <small><?php echo date('d/m/Y', strtotime($proveedor['fecha_creacion'])); ?></small>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="edit.php?id=<?php echo $proveedor['id_proveedor']; ?>" 
                                       class="btn btn-outline-warning" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if ($proveedor['estado'] == 'Activo'): ?>
                                    <a href="toggle_status.php?id=<?php echo $proveedor['id_proveedor']; ?>&action=deactivate" 
                                       class="btn btn-outline-secondary" title="Desactivar"
                                       onclick="return confirm('¿Desactivar este proveedor?')">
                                        <i class="fas fa-pause"></i>
                                    </a>
                                    <?php else: ?>
                                    <a href="toggle_status.php?id=<?php echo $proveedor['id_proveedor']; ?>&action=activate" 
                                       class="btn btn-outline-success" title="Activar"
                                       onclick="return confirm('¿Activar este proveedor?')">
                                        <i class="fas fa-play"></i>
                                    </a>
                                    <?php endif; ?>
                                    <a href="delete.php?id=<?php echo $proveedor['id_proveedor']; ?>" 
                                       class="btn btn-outline-danger" title="Eliminar">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#proveedoresTable').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json"
        },
        "order": [[6, "desc"]],
        "pageLength": 25,
        "responsive": true,
        "columnDefs": [
            { "orderable": false, "targets": [7] }
        ]
    });
});

function exportarProveedores() {
    const searchParams = new URLSearchParams(window.location.search);
    searchParams.set('export', '1');
    window.open('export.php?' + searchParams.toString(), '_blank');
}
</script>

<?php include '../../includes/footer.php'; ?>
