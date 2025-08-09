<?php
require_once '../../includes/functions.php';
require_once '../../models/Categoria.php';
require_once '../../models/Inventario.php';

requireLogin();
checkSessionTimeout();

$id_categoria = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_categoria <= 0) {
    header('Location: index.php?error=invalid_id');
    exit();
}

$database = new Database();
$db = $database->getConnection();
$categoriaModel = new Categoria($db);
$inventarioModel = new Inventario($db);

$categoria = $categoriaModel->readOne($id_categoria);

if (!$categoria) {
    header('Location: index.php?error=not_found');
    exit();
}

$items_count = $inventarioModel->countByCategoria($id_categoria);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    if ($items_count > 0) {
        header('Location: index.php?error=has_items');
        exit();
    }

    try {
        if ($categoriaModel->delete($id_categoria)) {
            logActivity($_SESSION['user_id'], "Categoría eliminada: {$categoria['nombre_categoria']} (ID: {$id_categoria})");
            header('Location: index.php?success=deleted');
            exit();
        } else {
            $error = 'Error al eliminar la categoría.';
        }
    } catch (Exception $e) {
        $error = 'Error del sistema: ' . $e->getMessage();
    }
}

$pageTitle = 'Eliminar Categoría';
include '../../includes/header.php';
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-trash-alt text-danger"></i> Eliminar Categoría
        </h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../../dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="index.php">Categorías</a></li>
                <li class="breadcrumb-item active">Eliminar</li>
            </ol>
        </nav>
    </div>

    <?php if (isset($error)): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
    </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card shadow border-left-danger">
                <div class="card-header py-3 bg-danger">
                    <h6 class="m-0 font-weight-bold text-white">
                        <i class="fas fa-exclamation-triangle"></i> Confirmar Eliminación
                    </h6>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <h6><i class="fas fa-exclamation-circle"></i> ¡Advertencia!</h6>
                        Esta acción no se puede deshacer. Una vez eliminada, la categoría no podrá ser recuperada.
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6 class="text-muted">Información de la categoría a eliminar:</h6>
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>Nombre:</strong></td>
                                    <td><?php echo htmlspecialchars($categoria['nombre_categoria']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Tipo:</strong></td>
                                    <td>
                                        <span class="badge badge-<?php echo $categoria['tipo'] == 'Material' ? 'success' : 'info'; ?>">
                                            <?php echo htmlspecialchars($categoria['tipo']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Descripción:</strong></td>
                                    <td><?php echo $categoria['descripcion'] ? htmlspecialchars($categoria['descripcion']) : '<em>Sin descripción</em>'; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Fecha de creación:</strong></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($categoria['fecha_creacion'])); ?></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">Elementos asociados:</h6>
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h2 class="<?php echo $items_count > 0 ? 'text-danger' : 'text-success'; ?>">
                                        <?php echo $items_count; ?>
                                    </h2>
                                    <p class="mb-0">
                                        <?php if ($items_count > 0): ?>
                                            <i class="fas fa-times-circle text-danger"></i>
                                            Elementos de inventario<br>
                                            <small class="text-danger">No se puede eliminar</small>
                                        <?php else: ?>
                                            <i class="fas fa-check-circle text-success"></i>
                                            Sin elementos asociados<br>
                                            <small class="text-success">Seguro para eliminar</small>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if ($items_count > 0): ?>
                    <div class="alert alert-danger">
                        <h6><i class="fas fa-ban"></i> No se puede eliminar esta categoría</h6>
                        <p class="mb-0">
                            Esta categoría tiene <strong><?php echo $items_count; ?></strong> elemento(s) de inventario asociados. 
                            Para eliminar esta categoría, primero debe:
                        </p>
                        <ol class="mt-2 mb-0">
                            <li>Reasignar todos los elementos a otra categoría, o</li>
                            <li>Eliminar todos los elementos de inventario de esta categoría</li>
                        </ol>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Volver al listado
                        </a>
                        <a href="../inventario/index.php?categoria=<?php echo $id_categoria; ?>" class="btn btn-info">
                            <i class="fas fa-boxes"></i> Ver elementos asociados
                        </a>
                    </div>

                    <?php else: ?>
                    <form method="POST" id="deleteForm">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="confirmCheck" required>
                            <label class="form-check-label" for="confirmCheck">
                                Confirmo que deseo eliminar permanentemente esta categoría
                            </label>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                            <a href="index.php" class="btn btn-secondary me-md-2">
                                <i class="fas fa-arrow-left"></i> Cancelar
                            </a>
                            <button type="submit" name="confirm_delete" class="btn btn-danger" disabled id="deleteBtn">
                                <i class="fas fa-trash-alt"></i> Confirmar Eliminación
                            </button>
                        </div>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const confirmCheck = document.getElementById('confirmCheck');
    const deleteBtn = document.getElementById('deleteBtn');
    
    if (confirmCheck && deleteBtn) {
        confirmCheck.addEventListener('change', function() {
            deleteBtn.disabled = !this.checked;
        });

        document.getElementById('deleteForm').addEventListener('submit', function(e) {
            if (!confirmCheck.checked) {
                e.preventDefault();
                Utils.showAlert('Debe confirmar la eliminación antes de continuar.', 'warning');
                return false;
            }

            if (!confirm('¿Está absolutamente seguro de que desea eliminar esta categoría? Esta acción no se puede deshacer.')) {
                e.preventDefault();
                return false;
            }
        });
    }
});
</script>

<?php include '../../includes/footer.php'; ?>
