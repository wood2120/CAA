<?php
require_once '../../includes/functions.php';
require_once '../../models/Proveedor.php';

requireLogin();
checkSessionTimeout();

$pageTitle = 'Gestión de Proveedores'; // usado por header

$database = new Database();
$db = $database->getConnection();
$proveedorModel = new Proveedor($db);

// Filtro de búsqueda simple (solo columnas existentes)
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$exportar = $_GET['exportar'] ?? '';

if ($search !== '') {
    $stmt = $proveedorModel->search($search);
} else {
    $stmt = $proveedorModel->readAll();
}
$proveedores = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $proveedores[] = $row;
}

// Exportación CSV (ANTES de cualquier salida HTML / header include)
if ($exportar === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=proveedores_' . date('Y-m-d') . '.csv');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // BOM UTF-8
    $incluyeEstado = $proveedorModel->soportaEstado();
    $headers = ['ID_Proveedor','Nombre_Proveedor','Contacto','Email','Direccion'];
    if ($incluyeEstado) { $headers[] = 'Estado'; }
    fputcsv($out, $headers);
    foreach ($proveedores as $p) {
        $row = [
            $p['ID_Proveedor'] ?? '',
            preg_replace('/[\r\n]+/',' ', $p['Nombre_Proveedor'] ?? ''),
            preg_replace('/[\r\n]+/',' ', $p['Contacto'] ?? ''),
            preg_replace('/[\r\n]+/',' ', $p['Email'] ?? ''),
            preg_replace('/[\r\n]+/',' ', $p['Direccion'] ?? '')
        ];
        if ($incluyeEstado) {
            $row[] = preg_replace('/[\r\n]+/',' ', $p['Estado'] ?? 'Activo');
        }
        fputcsv($out, $row);
    }
    fclose($out);
    exit; // evitar que se añada HTML al CSV
}

$totalProveedores = count($proveedores);

include '../../includes/header.php'; // HEADER solo después de posible exportación
logActivity($_SESSION['user_id'], 'Acceso a gestión de proveedores');
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
            case 'created': echo 'Proveedor creado exitosamente.'; break;
            case 'updated': echo 'Proveedor actualizado exitosamente.'; break;
            case 'deleted': echo 'Proveedor eliminado exitosamente.'; break;
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
            case 'invalid_id': echo 'ID de proveedor inválido.'; break;
            case 'not_found': echo 'Proveedor no encontrado.'; break;
            case 'has_items': echo 'No se puede eliminar el proveedor porque tiene elementos de inventario asociados.'; break;
            default: echo 'Se produjo un error inesperado.'; break;
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
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalProveedores; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-truck fa-2x text-gray-300"></i>
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
                    <a href="?exportar=csv<?php echo $search ? '&search=' . urlencode($search) : ''; ?>" class="btn btn-outline-success btn-sm">
                        <i class="fas fa-file-csv"></i> Exportar CSV
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <form method="GET" class="mb-4">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label for="search" class="form-label">Buscar:</label>
                        <input type="text" class="form-control" id="search" name="search" 
                               value="<?php echo htmlspecialchars($search); ?>" 
                               placeholder="Nombre, contacto o correo...">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i> Buscar
                        </button>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <a href="index.php" class="btn btn-outline-secondary w-100">
                            <i class="fas fa-undo"></i> Reset
                        </a>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered" id="proveedoresTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Contacto</th>
                            <th>Email</th>
                            <th>Dirección</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($proveedores)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                <i class="fas fa-search fa-3x mb-3"></i><br>
                                No se encontraron proveedores que coincidan con los criterios de búsqueda.
                                <?php if ($search !== ''): ?>
                                <br><a href="index.php" class="btn btn-link">Ver todos los proveedores</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($proveedores as $proveedor): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($proveedor['Nombre_Proveedor']); ?></strong></td>
                            <td><?php echo $proveedor['Contacto'] ? htmlspecialchars($proveedor['Contacto']) : '<span class="text-muted">N/A</span>'; ?></td>
                            <td>
                                <?php if (!empty($proveedor['Email'])): ?>
                                    <a href="mailto:<?php echo htmlspecialchars($proveedor['Email']); ?>"><?php echo htmlspecialchars($proveedor['Email']); ?></a>
                                <?php else: ?>
                                    <span class="text-muted">N/A</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $proveedor['Direccion'] ? '<small>' . htmlspecialchars($proveedor['Direccion']) . '</small>' : '<span class="text-muted">N/A</span>'; ?></td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="edit.php?id=<?php echo $proveedor['ID_Proveedor']; ?>" class="btn btn-outline-warning" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="delete.php?id=<?php echo $proveedor['ID_Proveedor']; ?>" class="btn btn-outline-danger" title="Eliminar">
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
console.log('Listado de proveedores cargado');
</script>

<?php include '../../includes/footer.php'; ?>
