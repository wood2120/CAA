<?php
require_once '../../includes/functions.php';
require_once '../../models/Inventario.php';
require_once '../../models/Categoria.php';
require_once '../../models/Proveedor.php';

requireLogin();
checkSessionTimeout();

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = sanitizeInput($_POST['nombre']);
    $descripcion = sanitizeInput($_POST['descripcion']);
    $id_categoria = sanitizeInput($_POST['id_categoria']);
    $id_proveedor = sanitizeInput($_POST['id_proveedor']);
    $cantidad_stock = sanitizeInput($_POST['cantidad_stock']);
    $stock_minimo = sanitizeInput($_POST['stock_minimo']);
    $precio_unitario = sanitizeInput($_POST['precio_unitario']);
    $unidad_medida = sanitizeInput($_POST['unidad_medida']);

    if (empty($nombre)) {
        $errors[] = 'El nombre es obligatorio.';
    }

    if (empty($id_categoria)) {
        $errors[] = 'La categoría es obligatoria.';
    }

    if (empty($precio_unitario) || $precio_unitario <= 0) {
        $errors[] = 'El precio unitario debe ser mayor a 0.';
    }

    if ($cantidad_stock < 0) {
        $errors[] = 'La cantidad en stock no puede ser negativa.';
    }

    if (empty($errors)) {
        try {
            $database = new Database();
            $db = $database->getConnection();
            $inventarioModel = new Inventario($db);

            $inventarioModel->nombre = $nombre;
            $inventarioModel->descripcion = $descripcion;
            $inventarioModel->id_categoria = $id_categoria;
            $inventarioModel->id_proveedor = $id_proveedor ?: null;
            $inventarioModel->cantidad_stock = $cantidad_stock;
            $inventarioModel->stock_minimo = $stock_minimo ?: 5;
            $inventarioModel->precio_unitario = $precio_unitario;
            $inventarioModel->unidad_medida = $unidad_medida ?: 'Unidad';

            if ($inventarioModel->exists()) {
                $errors[] = 'Ya existe un item con ese nombre.';
            } else {
                if ($inventarioModel->create()) {
                    if ($cantidad_stock > 0) {
                        $inventarioModel->registrarMovimiento('Entrada', $cantidad_stock, 'Stock inicial', $_SESSION['user_id']);
                    }
                    
                    logActivity($_SESSION['user_id'], "Item de inventario creado: {$nombre}");
                    header('Location: index.php?success=created');
                    exit();
                } else {
                    $errors[] = 'Error al crear el item de inventario.';
                }
            }
        } catch (Exception $e) {
            $errors[] = 'Error del sistema: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Nuevo Item de Inventario';
include '../../includes/header.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $categoriaModel = new Categoria($db);
    $proveedorModel = new Proveedor($db);
    
    $categorias = $categoriaModel->readAll();
    $proveedores = $proveedorModel->readAll();
} catch (Exception $e) {
    $errors[] = 'Error al cargar datos: ' . $e->getMessage();
}
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-plus-circle"></i> Nuevo Item de Inventario
        </h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../../dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="index.php">Inventario</a></li>
                <li class="breadcrumb-item active">Nuevo</li>
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
        <div class="col-lg-10 mx-auto">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Información del Item</h6>
                </div>
                <div class="card-body">
                    <form method="POST" id="inventarioForm" novalidate>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nombre" class="form-label">
                                    Nombre del Item <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="nombre" name="nombre" 
                                       value="<?php echo isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : ''; ?>" 
                                       required maxlength="150" placeholder="Ej: Cemento Portland 50kg">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="id_categoria" class="form-label">
                                    Categoría <span class="text-danger">*</span>
                                </label>
                                <select class="form-control" id="id_categoria" name="id_categoria" required>
                                    <option value="">Seleccionar categoría...</option>
                                    <?php if (isset($categorias)): ?>
                                        <?php while ($categoria = $categorias->fetch(PDO::FETCH_ASSOC)): ?>
                                            <option value="<?php echo $categoria['ID_Categoria']; ?>"
                                                    <?php echo (isset($_POST['id_categoria']) && $_POST['id_categoria'] == $categoria['ID_Categoria']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($categoria['Nombre_Categoria']) . ' (' . $categoria['Tipo'] . ')'; ?>
                                            </option>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12 mb-3">
                                <label for="descripcion" class="form-label">Descripción</label>
                                <textarea class="form-control" id="descripcion" name="descripcion" rows="3" 
                                          placeholder="Descripción detallada del item..."><?php echo isset($_POST['descripcion']) ? htmlspecialchars($_POST['descripcion']) : ''; ?></textarea>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="id_proveedor" class="form-label">Proveedor</label>
                                <select class="form-control" id="id_proveedor" name="id_proveedor">
                                    <option value="">Sin proveedor específico</option>
                                    <?php if (isset($proveedores)): ?>
                                        <?php while ($proveedor = $proveedores->fetch(PDO::FETCH_ASSOC)): ?>
                                            <option value="<?php echo $proveedor['ID_Proveedor']; ?>"
                                                    <?php echo (isset($_POST['id_proveedor']) && $_POST['id_proveedor'] == $proveedor['ID_Proveedor']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($proveedor['Nombre_Proveedor']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="unidad_medida" class="form-label">Unidad de Medida</label>
                                <select class="form-control" id="unidad_medida" name="unidad_medida">
                                    <option value="Unidad" <?php echo (isset($_POST['unidad_medida']) && $_POST['unidad_medida'] == 'Unidad') ? 'selected' : ''; ?>>Unidad</option>
                                    <option value="Kilogramo" <?php echo (isset($_POST['unidad_medida']) && $_POST['unidad_medida'] == 'Kilogramo') ? 'selected' : ''; ?>>Kilogramo</option>
                                    <option value="Metro" <?php echo (isset($_POST['unidad_medida']) && $_POST['unidad_medida'] == 'Metro') ? 'selected' : ''; ?>>Metro</option>
                                    <option value="Metro cuadrado" <?php echo (isset($_POST['unidad_medida']) && $_POST['unidad_medida'] == 'Metro cuadrado') ? 'selected' : ''; ?>>Metro cuadrado</option>
                                    <option value="Metro cúbico" <?php echo (isset($_POST['unidad_medida']) && $_POST['unidad_medida'] == 'Metro cúbico') ? 'selected' : ''; ?>>Metro cúbico</option>
                                    <option value="Litro" <?php echo (isset($_POST['unidad_medida']) && $_POST['unidad_medida'] == 'Litro') ? 'selected' : ''; ?>>Litro</option>
                                    <option value="Galón" <?php echo (isset($_POST['unidad_medida']) && $_POST['unidad_medida'] == 'Galón') ? 'selected' : ''; ?>>Galón</option>
                                    <option value="Saco" <?php echo (isset($_POST['unidad_medida']) && $_POST['unidad_medida'] == 'Saco') ? 'selected' : ''; ?>>Saco</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="cantidad_stock" class="form-label">Cantidad Inicial</label>
                                <input type="number" class="form-control" id="cantidad_stock" name="cantidad_stock" 
                                       value="<?php echo isset($_POST['cantidad_stock']) ? htmlspecialchars($_POST['cantidad_stock']) : '0'; ?>" 
                                       min="0" step="1">
                                <div class="form-text">Cantidad inicial en inventario</div>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="stock_minimo" class="form-label">Stock Mínimo</label>
                                <input type="number" class="form-control" id="stock_minimo" name="stock_minimo" 
                                       value="<?php echo isset($_POST['stock_minimo']) ? htmlspecialchars($_POST['stock_minimo']) : '5'; ?>" 
                                       min="0" step="1">
                                <div class="form-text">Alerta cuando el stock llegue a este nivel</div>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="precio_unitario" class="form-label">
                                    Precio Unitario <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">₡</span>
                                    <input type="number" class="form-control" id="precio_unitario" name="precio_unitario" 
                                           value="<?php echo isset($_POST['precio_unitario']) ? htmlspecialchars($_POST['precio_unitario']) : ''; ?>" 
                                           min="0" step="0.01" required>
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
                                        <i class="fas fa-save"></i> Guardar Item
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
    document.getElementById('inventarioForm').addEventListener('submit', function(e) {
        if (!Utils.validateForm('inventarioForm')) {
            e.preventDefault();
            Utils.showAlert('Por favor, complete todos los campos requeridos.', 'warning');
            return false;
        }
    });

    document.getElementById('nombre').focus();
});

function clearForm() {
    if (confirm('¿Está seguro de que desea limpiar todos los campos?')) {
        document.getElementById('inventarioForm').reset();
        Utils.clearFormValidation('inventarioForm');
        document.getElementById('nombre').focus();
    }
}
</script>

<?php include '../../includes/footer.php'; ?>
