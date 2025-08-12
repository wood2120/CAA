<?php
require_once '../../includes/functions.php';
require_once '../../models/Categoria.php';

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

// Establecer el ID en el modelo y cargar sus datos
$categoriaModel->id_categoria = $id_categoria;
if (!$categoriaModel->readOne()) {
    header('Location: index.php?error=not_found');
    exit();
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre_categoria = sanitizeInput($_POST['nombre_categoria']);
    $descripcion = sanitizeInput($_POST['descripcion']);
    $tipo = sanitizeInput($_POST['tipo']);

    if (empty($nombre_categoria)) {
        $errors[] = 'El nombre de la categoría es obligatorio.';
    }

    if (empty($tipo)) {
        $tipo = 'Material'; // valor por defecto si el usuario no selecciona
    }

    if (empty($errors)) {
        try {
            $categoriaModel->id_categoria = $id_categoria; // redundante pero explícito
            $categoriaModel->nombre_categoria = $nombre_categoria;
            $categoriaModel->descripcion = $descripcion;
            $categoriaModel->tipo = $tipo;

            // Verificar duplicados usando método exists() existente
            if ($categoriaModel->exists()) {
                $errors[] = 'Ya existe otra categoría con ese nombre.';
            } else {
                if ($categoriaModel->update()) {
                    logActivity($_SESSION['user_id'], "Categoría actualizada: {$nombre_categoria} (ID: {$id_categoria})");
                    header('Location: index.php?success=updated');
                    exit();
                } else {
                    $errors[] = 'Error al actualizar la categoría.';
                }
            }
        } catch (Exception $e) {
            $errors[] = 'Error del sistema: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Editar Categoría';
include '../../includes/header.php';
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-edit"></i> Editar Categoría
        </h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../../dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="index.php">Categorías</a></li>
                <li class="breadcrumb-item active">Editar</li>
            </ol>
        </nav>
    </div>

    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <h6><i class="fas fa-exclamation-triangle"></i> Se encontraron los siguientes errores:</h6>
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Editar Información de la Categoría</h6>
                    <span class="badge badge-<?php echo $categoriaModel->tipo == 'Material' ? 'success' : 'info'; ?>">
                        <?php echo htmlspecialchars($categoriaModel->tipo); ?>
                    </span>
                </div>
                <div class="card-body">
                    <form method="POST" id="categoriaForm" novalidate>
                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label for="nombre_categoria" class="form-label">
                                    Nombre de la Categoría <span class="text-danger">*</span>
                                </label>
                    <input type="text" class="form-control" id="nombre_categoria" name="nombre_categoria" 
                        value="<?php echo isset($_POST['nombre_categoria']) ? htmlspecialchars($_POST['nombre_categoria']) : htmlspecialchars($categoriaModel->nombre_categoria); ?>" 
                                       required maxlength="100" placeholder="Ej: Herramientas Eléctricas">
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="tipo" class="form-label">
                                    Tipo <span class="text-danger">*</span>
                                </label>
                                <select class="form-control" id="tipo" name="tipo">
                                    <option value="">(Auto: Material)</option>
                                    <?php 
                                    $tipo_actual = isset($_POST['tipo']) ? $_POST['tipo'] : $categoriaModel->tipo;
                                    ?>
                                    <option value="Material" <?php echo $tipo_actual == 'Material' ? 'selected' : ''; ?>>
                                        Material
                                    </option>
                                    <option value="Herramienta" <?php echo $tipo_actual == 'Herramienta' ? 'selected' : ''; ?>>
                                        Herramienta
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12 mb-3">
                                <label for="descripcion" class="form-label">Descripción</label>
                                <textarea class="form-control" id="descripcion" name="descripcion" rows="4" 
                                          placeholder="Descripción detallada de la categoría..."><?php echo isset($_POST['descripcion']) ? htmlspecialchars($_POST['descripcion']) : htmlspecialchars($categoriaModel->descripcion); ?></textarea>
                                <div class="form-text">Opcional: Describe qué tipo de elementos incluye esta categoría</div>
                            </div>
                        </div>

                        <!-- Información de fechas eliminada porque no existe en el modelo/tabla actual -->

                        <div class="row">
                            <div class="col-12">
                                <hr>
                                <div class="d-grid gap-2 d-md-flex justify-content-md-between">
                                    <div>
                                        <a href="index.php" class="btn btn-secondary">
                                            <i class="fas fa-arrow-left"></i> Volver al listado
                                        </a>
                                    </div>
                                    <div>
                                        <button type="button" class="btn btn-outline-secondary me-md-2" onclick="resetForm()">
                                            <i class="fas fa-undo"></i> Restaurar
                                        </button>
                                        <button type="submit" class="btn btn-warning">
                                            <i class="fas fa-save"></i> Actualizar Categoría
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const originalData = {
    nombre_categoria: <?php echo json_encode($categoriaModel->nombre_categoria); ?>,
    descripcion: <?php echo json_encode($categoriaModel->descripcion); ?>,
    tipo: <?php echo json_encode($categoriaModel->tipo); ?>
};

document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('categoriaForm').addEventListener('submit', function(e) {
        if (!Utils.validateForm('categoriaForm')) {
            e.preventDefault();
            Utils.showAlert('Por favor, complete todos los campos requeridos.', 'warning');
            return false;
        }
    });

    document.getElementById('nombre_categoria').focus();
});

function resetForm() {
    if (confirm('¿Está seguro de que desea restaurar los valores originales?')) {
        document.getElementById('nombre_categoria').value = originalData.nombre_categoria;
        document.getElementById('descripcion').value = originalData.descripcion;
        document.getElementById('tipo').value = originalData.tipo;
        Utils.clearFormValidation('categoriaForm');
        document.getElementById('nombre_categoria').focus();
    }
}
</script>

<?php include '../../includes/footer.php'; ?>
