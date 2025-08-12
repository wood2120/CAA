<?php
require_once '../../includes/functions.php';
require_once '../../models/Categoria.php';

requireLogin();
checkSessionTimeout();

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre_categoria = sanitizeInput($_POST['nombre_categoria']);
    $descripcion = sanitizeInput($_POST['descripcion']);
    $tipo = sanitizeInput($_POST['tipo']);

    if (empty($nombre_categoria)) {
        $errors[] = 'El nombre de la categoría es obligatorio.';
    }

    // Tipo ahora opcional: si viene vacío se usará 'Material' por defecto
    if (empty($tipo)) {
        $tipo = 'Material';
    }

    if (empty($errors)) {
        try {
            $database = new Database();
            $db = $database->getConnection();
            $categoriaModel = new Categoria($db);

            $categoriaModel->nombre_categoria = $nombre_categoria;
            $categoriaModel->descripcion = $descripcion;
            $categoriaModel->tipo = $tipo;

            if ($categoriaModel->exists()) {
                $errors[] = 'Ya existe una categoría con ese nombre.';
            } else {
                if ($categoriaModel->create()) {
                    logActivity($_SESSION['user_id'], "Categoría creada: {$nombre_categoria} ({$tipo})");
                    header('Location: index.php?success=created');
                    exit();
                } else {
                    $errors[] = 'Error al crear la categoría.';
                }
            }
        } catch (Exception $e) {
            $errors[] = 'Error del sistema: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Nueva Categoría';
include '../../includes/header.php';
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-plus-circle"></i> Nueva Categoría
        </h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../../dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="index.php">Categorías</a></li>
                <li class="breadcrumb-item active">Nueva</li>
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
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Información de la Categoría</h6>
                </div>
                <div class="card-body">
                    <form method="POST" id="categoriaForm" novalidate>
                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label for="nombre_categoria" class="form-label">
                                    Nombre de la Categoría <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="nombre_categoria" name="nombre_categoria" 
                                       value="<?php echo isset($_POST['nombre_categoria']) ? htmlspecialchars($_POST['nombre_categoria']) : ''; ?>" 
                                       required maxlength="100" placeholder="Ej: Herramientas Eléctricas">
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="tipo" class="form-label">
                                    Tipo <span class="text-danger">*</span>
                                </label>
                                <select class="form-control" id="tipo" name="tipo">
                                    <option value="">(Auto: Material)</option>
                                    <option value="Material" <?php echo (isset($_POST['tipo']) && $_POST['tipo'] == 'Material') ? 'selected' : ''; ?>>
                                        Material
                                    </option>
                                    <option value="Herramienta" <?php echo (isset($_POST['tipo']) && $_POST['tipo'] == 'Herramienta') ? 'selected' : ''; ?>>
                                        Herramienta
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12 mb-3">
                                <label for="descripcion" class="form-label">Descripción</label>
                                <textarea class="form-control" id="descripcion" name="descripcion" rows="4" 
                                          placeholder="Descripción detallada de la categoría..."><?php echo isset($_POST['descripcion']) ? htmlspecialchars($_POST['descripcion']) : ''; ?></textarea>
                                <div class="form-text">Opcional: Describe qué tipo de elementos incluye esta categoría</div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="alert alert-info">
                                    <h6><i class="fas fa-info-circle"></i> Ejemplos de categorías:</h6>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <strong>Materiales:</strong>
                                            <ul class="mb-0">
                                                <li>Cemento y Mortero</li>
                                                <li>Arena y Grava</li>
                                                <li>Hierro y Acero</li>
                                                <li>Pintura y Acabados</li>
                                            </ul>
                                        </div>
                                        <div class="col-md-6">
                                            <strong>Herramientas:</strong>
                                            <ul class="mb-0">
                                                <li>Herramientas Manuales</li>
                                                <li>Herramientas Eléctricas</li>
                                                <li>Equipos de Seguridad</li>
                                                <li>Maquinaria Pesada</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <hr>
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <a href="index.php" class="btn btn-secondary me-md-2">
                                        <i class="fas fa-arrow-left"></i> Cancelar
                                    </a>
                                    <button type="button" class="btn btn-outline-secondary me-md-2" onclick="clearForm()">
                                        <i class="fas fa-broom"></i> Limpiar
                                    </button>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Guardar Categoría
                                    </button>
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

function clearForm() {
    if (confirm('¿Está seguro de que desea limpiar todos los campos?')) {
        document.getElementById('categoriaForm').reset();
        Utils.clearFormValidation('categoriaForm');
        document.getElementById('nombre_categoria').focus();
    }
}
</script>

<?php include '../../includes/footer.php'; ?>
