<?php
require_once '../../includes/functions.php';
require_once '../../models/Inventario.php';
require_once '../../models/Categoria.php';
require_once '../../models/Proveedor.php';

requireLogin();
checkSessionTimeout();

$pageTitle = 'Gestión de Inventario';
include '../../includes/header.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    $inventarioModel = new Inventario($db);
    $categoriaModel = new Categoria($db);
    
    $searchTerm = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
    $categoriaFilter = isset($_GET['categoria']) ? sanitizeInput($_GET['categoria']) : '';
    $estadoFilter = isset($_GET['estado']) ? sanitizeInput($_GET['estado']) : '';
    
    if (!empty($searchTerm)) {
        $stmt = $inventarioModel->search($searchTerm);
    } elseif (!empty($categoriaFilter)) {
        $stmt = $inventarioModel->getPorCategoria($categoriaFilter);
    } else {
        $stmt = $inventarioModel->readAll();
    }
    
    $categorias = $categoriaModel->readAll();
    
} catch (Exception $e) {
    $error = "Error al cargar inventario: " . $e->getMessage();
}

logActivity($_SESSION['user_id'], "Acceso a gestión de inventario");
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-boxes"></i> Gestión de Inventario
        </h1>
        <div>
            <a href="create.php" class="btn btn-primary me-2">
                <i class="fas fa-plus"></i> Nuevo Item
            </a>
            <a href="movimientos.php" class="btn btn-outline-info">
                <i class="fas fa-exchange-alt"></i> Movimientos
            </a>
        </div>
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
            case 'created': echo 'Item de inventario creado exitosamente.'; break;
            case 'updated': echo 'Item de inventario actualizado exitosamente.'; break;
            case 'deleted': echo 'Item de inventario eliminado exitosamente.'; break;
            case 'movement': echo 'Movimiento de inventario registrado exitosamente.'; break;
            default: echo 'Operación realizada exitosamente.';
        }
        ?>
    </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filtros y Búsqueda</h6>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <div class="input-group">
                        <input type="text" class="form-control" name="search" 
                               placeholder="Buscar por nombre, descripción..." 
                               value="<?php echo htmlspecialchars($searchTerm); ?>">
                        <button class="btn btn-outline-secondary" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-3">
                    <select class="form-control" name="categoria" onchange="this.form.submit()">
                        <option value="">Todas las categorías</option>
                        <?php if (isset($categorias)): ?>
                            <?php while ($categoria = $categorias->fetch(PDO::FETCH_ASSOC)): ?>
                                <option value="<?php echo $categoria['ID_Categoria']; ?>"
                                        <?php echo ($categoriaFilter == $categoria['ID_Categoria']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($categoria['Nombre_Categoria']); ?>
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-control" name="estado" onchange="this.form.submit()">
                        <option value="">Todos los estados</option>
                        <option value="Activo" <?php echo ($estadoFilter == 'Activo') ? 'selected' : ''; ?>>Activo</option>
                        <option value="Agotado" <?php echo ($estadoFilter == 'Agotado') ? 'selected' : ''; ?>>Agotado</option>
                        <option value="Inactivo" <?php echo ($estadoFilter == 'Inactivo') ? 'selected' : ''; ?>>Inactivo</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <div class="d-grid gap-2 d-md-flex">
                        <a href="index.php" class="btn btn-outline-secondary">
                            <i class="fas fa-times"></i> Limpiar
                        </a>
                        <a href="stock-bajo.php" class="btn btn-warning">
                            <i class="fas fa-exclamation-triangle"></i> Stock Bajo
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
                Lista de Inventario
                <?php if (!empty($searchTerm)): ?>
                    - Resultados para: "<?php echo htmlspecialchars($searchTerm); ?>"
                <?php endif; ?>
            </h6>
        </div>
        <div class="card-body">
            <?php if (isset($stmt) && $stmt->rowCount() > 0): ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="inventarioTable">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Categoría</th>
                            <th>Proveedor</th>
                            <th>Stock</th>
                            <th>Precio Unit.</th>
                            <th>Valor Total</th>
                            <th>Estado</th>
                            <th class="text-center no-print">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                        <tr class="<?php echo ($row['Cantidad_Stock'] <= $row['Stock_Minimo']) ? 'table-warning' : ''; ?>">
                            <td><?php echo htmlspecialchars($row['ID_Inventario']); ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($row['Nombre']); ?></strong>
                                <?php if (!empty($row['Descripcion'])): ?>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($row['Descripcion']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo ($row['Tipo_Categoria'] == 'Material') ? 'info' : 'warning'; ?>">
                                    <?php echo htmlspecialchars($row['Nombre_Categoria']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($row['Nombre_Proveedor'] ?? 'Sin proveedor'); ?></td>
                            <td>
                                <strong><?php echo number_format($row['Cantidad_Stock']); ?></strong>
                                <small class="text-muted"><?php echo htmlspecialchars($row['Unidad_Medida']); ?></small>
                                <?php if ($row['Cantidad_Stock'] <= $row['Stock_Minimo']): ?>
                                    <br><small class="text-warning"><i class="fas fa-exclamation-triangle"></i> Stock bajo</small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo formatCurrency($row['Precio_Unitario']); ?></td>
                            <td><strong><?php echo formatCurrency($row['Valor_Total_Stock']); ?></strong></td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo ($row['Estado'] == 'Activo') ? 'success' : 
                                        (($row['Estado'] == 'Agotado') ? 'danger' : 'secondary'); ?>">
                                    <?php echo htmlspecialchars($row['Estado']); ?>
                                </span>
                            </td>
                            <td class="text-center no-print">
                                <div class="btn-group" role="group">
                                   
                                    <a href="edit.php?id=<?php echo $row['ID_Inventario']; ?>" 
                                       class="btn btn-sm btn-outline-warning" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                   
                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                            onclick="deleteItem('<?php echo $row['ID_Inventario']; ?>', '<?php echo htmlspecialchars($row['Nombre']); ?>')" 
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
                <i class="fas fa-boxes fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">
                    <?php echo !empty($searchTerm) ? 'No se encontraron items que coincidan con la búsqueda.' : 'No hay items en el inventario.'; ?>
                </h5>
                <?php if (empty($searchTerm)): ?>
                <a href="create.php" class="btn btn-primary mt-3">
                    <i class="fas fa-plus"></i> Agregar Primer Item
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function deleteItem(id, nombre) {
    if (SistemaKris.confirmDelete(`¿Está seguro de que desea eliminar el item "${nombre}"?\n\nEsta acción no se puede deshacer.`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'delete.php';
        
        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'id_inventario';
        idInput.value = id;
        
        form.appendChild(idInput);
        document.body.appendChild(form);
        form.submit();
    }
}

function exportTable() {
    Utils.exportTableToCSV('inventarioTable', 'inventario_' + new Date().toISOString().slice(0,10) + '.csv');
}

document.addEventListener('DOMContentLoaded', function() {
    SistemaKris.initDataTable('inventarioTable', {
        order: [[1, 'asc']],
        columnDefs: [
            { orderable: false, targets: [8] }
        ]
    });
});
</script>

<?php include '../../includes/footer.php'; ?>
