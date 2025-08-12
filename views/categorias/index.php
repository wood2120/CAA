<?php
require_once '../../includes/functions.php';
require_once '../../models/Categoria.php';

requireLogin();
checkSessionTimeout();

$pageTitle = 'Gestión de Categorías';
include '../../includes/header.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    $categoriaModel = new Categoria($db);
    
    $searchTerm = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
    $tipoFilter = isset($_GET['tipo']) ? sanitizeInput($_GET['tipo']) : '';
    
    if (!empty($searchTerm)) {
        $stmt = $categoriaModel->search($searchTerm);
    } elseif (!empty($tipoFilter)) {
        $stmt = $categoriaModel->getPorTipo($tipoFilter);
    } else {
        $stmt = $categoriaModel->getConEstadisticas();
    }
    
} catch (Exception $e) {
    $error = "Error al cargar categorías: " . $e->getMessage();
}

logActivity($_SESSION['user_id'], "Acceso a gestión de categorías");
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-tags"></i> Gestión de Categorías
        </h1>
        <a href="create.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nueva Categoría
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
            case 'created': echo 'Categoría creada exitosamente.'; break;
            case 'updated': echo 'Categoría actualizada exitosamente.'; break;
            case 'deleted': echo 'Categoría eliminada exitosamente.'; break;
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
                <div class="col-md-5">
                    <div class="input-group">
                        <input type="text" class="form-control" name="search" 
                               placeholder="Buscar por nombre o descripción..." 
                               value="<?php echo htmlspecialchars($searchTerm); ?>">
                        <button class="btn btn-outline-secondary" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-3">
                    <select class="form-control" name="tipo" onchange="this.form.submit()">
                        <option value="">Todos los tipos</option>
                        <option value="Material" <?php echo ($tipoFilter == 'Material') ? 'selected' : ''; ?>>Material</option>
                        <option value="Herramienta" <?php echo ($tipoFilter == 'Herramienta') ? 'selected' : ''; ?>>Herramienta</option>
                    </select>
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
                Lista de Categorías
                <?php if (!empty($searchTerm)): ?>
                    - Resultados para: "<?php echo htmlspecialchars($searchTerm); ?>"
                <?php endif; ?>
            </h6>
        </div>
        <div class="card-body">
            <?php if (isset($stmt) && $stmt->rowCount() > 0): ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="categoriasTable">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Tipo</th>
                            <th>Descripción</th>
                            <th>Items</th>
                            <th>Stock Total</th>
                            <th>Valor Total</th>
                            <th class="text-center no-print">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['ID_Categoria']); ?></td>
                            <td><strong><?php echo htmlspecialchars($row['Nombre_Categoria']); ?></strong></td>
                            <td>
                                <span class="badge bg-<?php echo ($row['Tipo'] == 'Material') ? 'info' : 'warning'; ?>">
                                    <i class="fas fa-<?php echo ($row['Tipo'] == 'Material') ? 'cubes' : 'tools'; ?>"></i>
                                    <?php echo htmlspecialchars($row['Tipo']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($row['Descripcion'] ?? 'Sin descripción'); ?></td>
                            <td>
                                <span class="badge bg-secondary"><?php echo number_format($row['total_items']); ?> items</span>
                            </td>
                            <td><?php echo number_format($row['total_stock']); ?> unidades</td>
                            <td><strong><?php echo formatCurrency($row['valor_total']); ?></strong></td>
                            <td class="text-center no-print">
                                <div class="btn-group" role="group">
                                    <a href="edit.php?id=<?php echo (int)$row['ID_Categoria']; ?>" 
                                       class="btn btn-sm btn-outline-warning" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if ($row['total_items'] == 0): ?>
                    <button type="button" class="btn btn-sm btn-outline-danger" 
                        onclick="deleteCategoria('<?php echo (int)$row['ID_Categoria']; ?>', '<?php echo htmlspecialchars($row['Nombre_Categoria']); ?>')" 
                                            title="Eliminar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php else: ?>
                                    <button type="button" class="btn btn-sm btn-outline-danger disabled" 
                                            title="No se puede eliminar (tiene items asociados)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-tags fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">
                    <?php echo !empty($searchTerm) ? 'No se encontraron categorías que coincidan con la búsqueda.' : 'No hay categorías registradas.'; ?>
                </h5>
                <?php if (empty($searchTerm)): ?>
                <a href="create.php" class="btn btn-primary mt-3">
                    <i class="fas fa-plus"></i> Crear Primera Categoría
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function deleteCategoria(id, nombre) {
    if (SistemaKris.confirmDelete(`¿Está seguro de que desea eliminar la categoría "${nombre}"?\n\nEsta acción no se puede deshacer.`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'delete.php';
        
        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'id_categoria';
        idInput.value = id;
        
        form.appendChild(idInput);
        document.body.appendChild(form);
        form.submit();
    }
}

function exportTable() {
    Utils.exportTableToCSV('categoriasTable', 'categorias_' + new Date().toISOString().slice(0,10) + '.csv');
}

document.addEventListener('DOMContentLoaded', function() {
    SistemaKris.initDataTable('categoriasTable', {
        order: [[2, 'asc'], [1, 'asc']],
        columnDefs: [
            { orderable: false, targets: [7] }
        ]
    });
});
</script>

<?php include '../../includes/footer.php'; ?>
