<?php
require_once '../../includes/functions.php';
require_once '../../models/Inventario.php';
require_once '../../models/Categoria.php';
require_once '../../models/Proveedor.php';

requireLogin();
checkSessionTimeout();

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id = (int)$_GET['id'];
$pageTitle = 'Editar Item de Inventario';
include '../../includes/header.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    $inventarioModel = new Inventario($db);
    $categoriaModel = new Categoria($db);
    $proveedorModel = new Proveedor($db);
    
    // Obtener datos del item
    $inventarioModel->id_inventario = $id;
    $itemFound = $inventarioModel->readOne();
    if (!$itemFound) {
        throw new Exception("Item no encontrado");
    }
    // Map properties to $item array for template compatibility
    $item = [
        'ID_Inventario' => $id,
        'Nombre' => $inventarioModel->nombre,
        'Descripcion' => $inventarioModel->descripcion,
        'ID_Categoria' => $inventarioModel->id_categoria,
        'ID_Proveedor' => $inventarioModel->id_proveedor,
        'Cantidad_Stock' => $inventarioModel->cantidad_stock,
        'Stock_Minimo' => $inventarioModel->stock_minimo,
        'Precio_Unitario' => $inventarioModel->precio_unitario,
        'Unidad_Medida' => $inventarioModel->unidad_medida,
        'Fecha_Ingreso' => $inventarioModel->fecha_ingreso,
        'Estado' => $inventarioModel->estado
    ];
    
    // Obtener categorías y proveedores para los selects
    $categorias = $categoriaModel->readAll();
    $proveedores = $proveedorModel->readAll();
    
} catch (Exception $e) {
    header("Location: index.php?error=" . urlencode($e->getMessage()));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $inventarioModel->id_inventario = $id;
        $inventarioModel->nombre = sanitizeInput($_POST['nombre']);
        $inventarioModel->descripcion = sanitizeInput($_POST['descripcion']);
        $inventarioModel->id_categoria = (int)$_POST['id_categoria'];
        $inventarioModel->id_proveedor = !empty($_POST['id_proveedor']) ? (int)$_POST['id_proveedor'] : null;
        $inventarioModel->stock_minimo = (int)$_POST['stock_minimo'];
        $inventarioModel->precio_unitario = (float)$_POST['precio_unitario'];
        $inventarioModel->unidad_medida = sanitizeInput($_POST['unidad_medida']);
        $inventarioModel->estado = sanitizeInput($_POST['estado']);
        
        // Si se especifica nueva cantidad de stock, crear movimiento de ajuste
        if (isset($_POST['nueva_cantidad']) && $_POST['nueva_cantidad'] !== '') {
            $nueva_cantidad = (int)$_POST['nueva_cantidad'];
            $motivo_ajuste = sanitizeInput($_POST['motivo_ajuste']);
            
            if ($nueva_cantidad != $item['Cantidad_Stock']) {
                // Crear movimiento de ajuste
                $queryMovimiento = "INSERT INTO TB_Movimientos_Inventario 
                                   (ID_Inventario, Tipo_Movimiento, Cantidad, Motivo, ID_Usuario) 
                                   VALUES (:id_inventario, 'Ajuste', :cantidad, :motivo, :id_usuario)";
                $stmtMovimiento = $db->prepare($queryMovimiento);
                $stmtMovimiento->bindParam(':id_inventario', $id);
                $stmtMovimiento->bindParam(':cantidad', $nueva_cantidad);
                $stmtMovimiento->bindParam(':motivo', $motivo_ajuste);
                $stmtMovimiento->bindParam(':id_usuario', $_SESSION['user_id']);
                $stmtMovimiento->execute();
            }
        }
        
        if ($inventarioModel->update()) {
            logActivity($_SESSION['user_id'], "Item de inventario editado: " . $inventarioModel->nombre);
            header("Location: index.php?success=updated");
            exit();
        } else {
            $error = "Error al actualizar el item";
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-edit"></i> Editar Item de Inventario
        </h1>
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>

    <?php if (isset($error)): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
    </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Información del Item</h6>
                </div>
                <div class="card-body">
                    <form method="POST" id="inventarioForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="nombre" class="form-label">
                                        <i class="fas fa-tag"></i> Nombre <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="nombre" name="nombre" 
                                           required maxlength="150" 
                                           value="<?php echo htmlspecialchars($item['Nombre']); ?>">
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="id_categoria" class="form-label">
                                        <i class="fas fa-folder"></i> Categoría <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select" id="id_categoria" name="id_categoria" required>
                                        <option value="">Seleccionar categoría...</option>
                                        <?php while ($categoria = $categorias->fetch(PDO::FETCH_ASSOC)): ?>
                                        <option value="<?php echo $categoria['ID_Categoria']; ?>" 
                                                <?php echo ($categoria['ID_Categoria'] == $item['ID_Categoria']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($categoria['Nombre_Categoria']); ?> 
                                            (<?php echo $categoria['Tipo']; ?>)
                                        </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="mb-3">
                                    <label for="descripcion" class="form-label">
                                        <i class="fas fa-align-left"></i> Descripción
                                    </label>
                                    <textarea class="form-control" id="descripcion" name="descripcion" 
                                              rows="3"><?php echo htmlspecialchars($item['Descripcion']); ?></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="id_proveedor" class="form-label">
                                        <i class="fas fa-truck"></i> Proveedor
                                    </label>
                                    <select class="form-select" id="id_proveedor" name="id_proveedor">
                                        <option value="">Sin proveedor específico</option>
                                        <?php while ($proveedor = $proveedores->fetch(PDO::FETCH_ASSOC)): ?>
                                        <option value="<?php echo $proveedor['ID_Proveedor']; ?>" 
                                                <?php echo ($proveedor['ID_Proveedor'] == $item['ID_Proveedor']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($proveedor['Nombre_Proveedor']); ?>
                                        </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="unidad_medida" class="form-label">
                                        <i class="fas fa-ruler"></i> Unidad de Medida <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select" id="unidad_medida" name="unidad_medida" required>
                                        <option value="Unidad" <?php echo ($item['Unidad_Medida'] == 'Unidad') ? 'selected' : ''; ?>>Unidad</option>
                                        <option value="Metro" <?php echo ($item['Unidad_Medida'] == 'Metro') ? 'selected' : ''; ?>>Metro</option>
                                        <option value="Kilogramo" <?php echo ($item['Unidad_Medida'] == 'Kilogramo') ? 'selected' : ''; ?>>Kilogramo</option>
                                        <option value="Litro" <?php echo ($item['Unidad_Medida'] == 'Litro') ? 'selected' : ''; ?>>Litro</option>
                                        <option value="Caja" <?php echo ($item['Unidad_Medida'] == 'Caja') ? 'selected' : ''; ?>>Caja</option>
                                        <option value="Bolsa" <?php echo ($item['Unidad_Medida'] == 'Bolsa') ? 'selected' : ''; ?>>Bolsa</option>
                                        <option value="Galón" <?php echo ($item['Unidad_Medida'] == 'Galón') ? 'selected' : ''; ?>>Galón</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="stock_minimo" class="form-label">
                                        <i class="fas fa-exclamation-triangle"></i> Stock Mínimo <span class="text-danger">*</span>
                                    </label>
                                    <input type="number" class="form-control" id="stock_minimo" name="stock_minimo" 
                                           required min="0" value="<?php echo $item['Stock_Minimo']; ?>">
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="precio_unitario" class="form-label">
                                        <i class="fas fa-dollar-sign"></i> Precio Unitario <span class="text-danger">*</span>
                                    </label>
                                    <input type="number" class="form-control" id="precio_unitario" name="precio_unitario" 
                                           required min="0" step="0.01" value="<?php echo $item['Precio_Unitario']; ?>">
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="estado" class="form-label">
                                        <i class="fas fa-toggle-on"></i> Estado <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select" id="estado" name="estado" required>
                                        <option value="Activo" <?php echo ($item['Estado'] == 'Activo') ? 'selected' : ''; ?>>Activo</option>
                                        <option value="Inactivo" <?php echo ($item['Estado'] == 'Inactivo') ? 'selected' : ''; ?>>Inactivo</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Ajuste de Stock -->
                        <div class="card border-warning mb-3">
                            <div class="card-header bg-warning text-dark">
                                <h6 class="mb-0"><i class="fas fa-adjust"></i> Ajuste de Stock (Opcional)</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="stock_actual" class="form-label">Stock Actual</label>
                                            <input type="text" class="form-control" id="stock_actual" 
                                                   value="<?php echo number_format($item['Cantidad_Stock']); ?> <?php echo $item['Unidad_Medida']; ?>" 
                                                   readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="nueva_cantidad" class="form-label">Nueva Cantidad</label>
                                            <input type="number" class="form-control" id="nueva_cantidad" name="nueva_cantidad" 
                                                   min="0" placeholder="Dejar vacío si no se cambia">
                                            <div class="form-text">Solo completar si necesita ajustar el stock</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-12">
                                        <div class="mb-3">
                                            <label for="motivo_ajuste" class="form-label">Motivo del Ajuste</label>
                                            <input type="text" class="form-control" id="motivo_ajuste" name="motivo_ajuste" 
                                                   placeholder="Ej: Corrección de inventario, pérdida, etc.">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="index.php" class="btn btn-secondary me-md-2">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Actualizar Item
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Información Actual</h6>
                </div>
                <div class="card-body">
                    <p><strong>ID:</strong> <?php echo $item['ID_Inventario']; ?></p>
                    <p><strong>Stock Actual:</strong> 
                        <span class="badge <?php echo $item['Cantidad_Stock'] <= $item['Stock_Minimo'] ? 'bg-warning' : 'bg-success'; ?>">
                            <?php echo number_format($item['Cantidad_Stock']); ?> <?php echo $item['Unidad_Medida']; ?>
                        </span>
                    </p>
                    <p><strong>Valor Total:</strong> 
                        <?php echo formatCurrency($item['Cantidad_Stock'] * $item['Precio_Unitario']); ?>
                    </p>
                    <p><strong>Fecha Ingreso:</strong> <?php echo formatDate($item['Fecha_Ingreso']); ?></p>
                    
                    <?php if ($item['Cantidad_Stock'] <= $item['Stock_Minimo']): ?>
                    <div class="alert alert-warning alert-sm">
                        <i class="fas fa-exclamation-triangle"></i> Stock bajo
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Acciones Rápidas</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-success" onclick="agregarStock()">
                            <i class="fas fa-plus"></i> Agregar Stock
                        </button>
                        <button type="button" class="btn btn-warning" onclick="ajustarStock()">
                            <i class="fas fa-adjust"></i> Ajustar Stock
                        </button>
                        <a href="../reportes/movimientos_inventario.php?item=<?php echo $item['ID_Inventario']; ?>" class="btn btn-info">
                            <i class="fas fa-history"></i> Ver Movimientos
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function agregarStock() {
    Swal.fire({
        title: 'Agregar Stock',
        html: `
            <div class="mb-3">
                <label class="form-label">Cantidad a agregar:</label>
                <input type="number" id="swal-cantidad" class="form-control" min="1" placeholder="Ej: 10">
            </div>
            <div class="mb-3">
                <label class="form-label">Motivo:</label>
                <input type="text" id="swal-motivo" class="form-control" placeholder="Ej: Compra, reabastecimiento">
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Agregar',
        cancelButtonText: 'Cancelar',
        preConfirm: () => {
            const cantidad = document.getElementById('swal-cantidad').value;
            const motivo = document.getElementById('swal-motivo').value;
            
            if (!cantidad || cantidad <= 0) {
                Swal.showValidationMessage('Debe ingresar una cantidad válida');
                return false;
            }
            
            if (!motivo.trim()) {
                Swal.showValidationMessage('Debe ingresar un motivo');
                return false;
            }
            
            return { cantidad: cantidad, motivo: motivo };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // Aquí se podría implementar la funcionalidad para agregar stock directamente
            document.getElementById('nueva_cantidad').value = 
                parseInt(<?php echo $item['Cantidad_Stock']; ?>) + parseInt(result.value.cantidad);
            document.getElementById('motivo_ajuste').value = result.value.motivo;
        }
    });
}

function ajustarStock() {
    Swal.fire({
        title: 'Ajustar Stock',
        html: `
            <div class="mb-3">
                <label class="form-label">Stock actual: <?php echo number_format($item['Cantidad_Stock']); ?></label>
            </div>
            <div class="mb-3">
                <label class="form-label">Nueva cantidad:</label>
                <input type="number" id="swal-nueva-cantidad" class="form-control" min="0" placeholder="Nueva cantidad total">
            </div>
            <div class="mb-3">
                <label class="form-label">Motivo del ajuste:</label>
                <input type="text" id="swal-motivo-ajuste" class="form-control" placeholder="Ej: Corrección de inventario">
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Ajustar',
        cancelButtonText: 'Cancelar',
        preConfirm: () => {
            const cantidad = document.getElementById('swal-nueva-cantidad').value;
            const motivo = document.getElementById('swal-motivo-ajuste').value;
            
            if (cantidad === '') {
                Swal.showValidationMessage('Debe ingresar la nueva cantidad');
                return false;
            }
            
            if (!motivo.trim()) {
                Swal.showValidationMessage('Debe ingresar un motivo');
                return false;
            }
            
            return { cantidad: cantidad, motivo: motivo };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('nueva_cantidad').value = result.value.cantidad;
            document.getElementById('motivo_ajuste').value = result.value.motivo;
        }
    });
}

// Validación del formulario
document.getElementById('inventarioForm').addEventListener('submit', function(e) {
    const nuevaCantidad = document.getElementById('nueva_cantidad').value;
    const motivoAjuste = document.getElementById('motivo_ajuste').value;
    
    if (nuevaCantidad !== '' && !motivoAjuste.trim()) {
        e.preventDefault();
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Debe especificar un motivo para el ajuste de stock'
        });
        return false;
    }
});
</script>

<?php include '../../includes/footer.php'; ?>
