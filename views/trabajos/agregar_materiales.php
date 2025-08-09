<?php
require_once '../../includes/functions.php';
require_once '../../models/Trabajo.php';
require_once '../../models/TrabajoInventario.php';
require_once '../../models/Inventario.php';

requireLogin();
checkSessionTimeout();

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id_trabajo = (int)$_GET['id'];
$pageTitle = 'Agregar Materiales al Trabajo';
include '../../includes/header.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    $trabajoModel = new Trabajo($db);
    $trabajoInventarioModel = new TrabajoInventario($db);
    $inventarioModel = new Inventario($db);
    
    // Verificar que el trabajo existe
    $trabajo = $trabajoModel->readOne($id_trabajo);
    if (!$trabajo) {
        throw new Exception("Trabajo no encontrado");
    }
    
    // Obtener inventario disponible
    $inventarioStmt = $inventarioModel->readAllActive();
    
} catch (Exception $e) {
    header("Location: index.php?error=" . urlencode($e->getMessage()));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $id_inventario = (int)$_POST['id_inventario'];
        $cantidad_usada = (int)$_POST['cantidad_usada'];
        $precio_unitario = (float)$_POST['precio_unitario'];
        
        // Verificar stock disponible
        $item = $inventarioModel->readOne($id_inventario);
        if (!$item) {
            throw new Exception("Item de inventario no encontrado");
        }
        
        if ($item['Cantidad_Stock'] < $cantidad_usada) {
            throw new Exception("Stock insuficiente. Disponible: " . $item['Cantidad_Stock']);
        }
        
        $trabajoInventarioModel->id_trabajo = $id_trabajo;
        $trabajoInventarioModel->id_inventario = $id_inventario;
        $trabajoInventarioModel->cantidad_usada = $cantidad_usada;
        $trabajoInventarioModel->precio_unitario_usado = $precio_unitario;
        
        if ($trabajoInventarioModel->create()) {
            logActivity($_SESSION['user_id'], "Material agregado al trabajo ID: $id_trabajo");
            header("Location: view.php?id=$id_trabajo&success=material_added");
            exit();
        } else {
            $error = "Error al agregar el material al trabajo";
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-plus"></i> Agregar Material/Herramienta
        </h1>
        <a href="view.php?id=<?php echo $id_trabajo; ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver al Trabajo
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
                    <h6 class="m-0 font-weight-bold text-primary">
                        Información del Trabajo: <?php echo htmlspecialchars($trabajo['Tipo_Trabajo']); ?>
                    </h6>
                </div>
                <div class="card-body">
                    <p><strong>Cliente:</strong> <?php echo htmlspecialchars($trabajo['cliente_nombre'] ?? 'N/A'); ?></p>
                    <p><strong>Estado:</strong> 
                        <span class="badge <?php 
                            echo match($trabajo['Estado']) {
                                'Pendiente' => 'bg-warning',
                                'En Proceso' => 'bg-info', 
                                'Completado' => 'bg-success',
                                'Cancelado' => 'bg-danger',
                                default => 'bg-secondary'
                            }; ?>">
                            <?php echo htmlspecialchars($trabajo['Estado']); ?>
                        </span>
                    </p>
                </div>
            </div>

            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Agregar Material/Herramienta</h6>
                </div>
                <div class="card-body">
                    <form method="POST" id="materialForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="id_inventario" class="form-label">
                                        <i class="fas fa-boxes"></i> Item del Inventario <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select" id="id_inventario" name="id_inventario" required>
                                        <option value="">Seleccionar item...</option>
                                        <?php while ($item = $inventarioStmt->fetch(PDO::FETCH_ASSOC)): ?>
                                        <option value="<?php echo $item['ID_Inventario']; ?>" 
                                                data-stock="<?php echo $item['Cantidad_Stock']; ?>"
                                                data-precio="<?php echo $item['Precio_Unitario']; ?>"
                                                data-unidad="<?php echo $item['Unidad_Medida']; ?>"
                                                data-categoria="<?php echo $item['categoria_nombre']; ?>">
                                            <?php echo htmlspecialchars($item['Nombre']); ?> 
                                            (Stock: <?php echo $item['Cantidad_Stock']; ?> <?php echo $item['Unidad_Medida']; ?>)
                                            - <?php echo formatCurrency($item['Precio_Unitario']); ?>
                                        </option>
                                        <?php endwhile; ?>
                                    </select>
                                    <div class="form-text">Seleccione el item del inventario a usar</div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="cantidad_usada" class="form-label">
                                        <i class="fas fa-sort-numeric-up"></i> Cantidad a Usar <span class="text-danger">*</span>
                                    </label>
                                    <input type="number" class="form-control" id="cantidad_usada" name="cantidad_usada" 
                                           required min="1" step="1">
                                    <div class="form-text" id="stock_info">Stock disponible: -</div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="precio_unitario" class="form-label">
                                        <i class="fas fa-dollar-sign"></i> Precio Unitario <span class="text-danger">*</span>
                                    </label>
                                    <input type="number" class="form-control" id="precio_unitario" name="precio_unitario" 
                                           required min="0" step="0.01">
                                    <div class="form-text">Precio al momento del uso</div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-calculator"></i> Subtotal Estimado
                                    </label>
                                    <input type="text" class="form-control" id="subtotal_preview" readonly 
                                           placeholder="₡0.00">
                                    <div class="form-text">Cantidad × Precio unitario</div>
                                </div>
                            </div>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="view.php?id=<?php echo $id_trabajo; ?>" class="btn btn-secondary me-md-2">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Agregar Material
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Información del Item</h6>
                </div>
                <div class="card-body">
                    <div id="item_info" style="display: none;">
                        <p><strong>Categoría:</strong> <span id="item_categoria">-</span></p>
                        <p><strong>Stock Disponible:</strong> <span id="item_stock">-</span> <span id="item_unidad">-</span></p>
                        <p><strong>Precio Actual:</strong> <span id="item_precio">-</span></p>
                    </div>
                    <div id="no_item_selected">
                        <p class="text-muted">Seleccione un item para ver su información.</p>
                    </div>
                </div>
            </div>

            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Notas Importantes</h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled small">
                        <li><i class="fas fa-info-circle text-info"></i> El stock se actualizará automáticamente</li>
                        <li><i class="fas fa-dollar-sign text-success"></i> El precio puede ser diferente al actual</li>
                        <li><i class="fas fa-history text-warning"></i> Se registrará el movimiento en la bitácora</li>
                        <li><i class="fas fa-calculator text-primary"></i> El total del trabajo se recalculará</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectItem = document.getElementById('id_inventario');
    const cantidadInput = document.getElementById('cantidad_usada');
    const precioInput = document.getElementById('precio_unitario');
    const subtotalPreview = document.getElementById('subtotal_preview');
    
    selectItem.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        
        if (selectedOption.value) {
            const stock = selectedOption.dataset.stock;
            const precio = selectedOption.dataset.precio;
            const unidad = selectedOption.dataset.unidad;
            const categoria = selectedOption.dataset.categoria;
            
            // Actualizar información del item
            document.getElementById('item_categoria').textContent = categoria;
            document.getElementById('item_stock').textContent = stock;
            document.getElementById('item_unidad').textContent = unidad;
            document.getElementById('item_precio').textContent = '₡' + parseFloat(precio).toLocaleString();
            
            document.getElementById('item_info').style.display = 'block';
            document.getElementById('no_item_selected').style.display = 'none';
            
            // Establecer precio por defecto
            precioInput.value = precio;
            
            // Actualizar información de stock
            document.getElementById('stock_info').textContent = `Stock disponible: ${stock} ${unidad}`;
            
            // Establecer límite máximo de cantidad
            cantidadInput.max = stock;
            
            // Calcular subtotal
            calcularSubtotal();
        } else {
            document.getElementById('item_info').style.display = 'none';
            document.getElementById('no_item_selected').style.display = 'block';
            precioInput.value = '';
            document.getElementById('stock_info').textContent = 'Stock disponible: -';
            cantidadInput.max = '';
            subtotalPreview.value = '';
        }
    });
    
    function calcularSubtotal() {
        const cantidad = parseFloat(cantidadInput.value) || 0;
        const precio = parseFloat(precioInput.value) || 0;
        const subtotal = cantidad * precio;
        
        subtotalPreview.value = subtotal > 0 ? '₡' + subtotal.toLocaleString() : '';
    }
    
    cantidadInput.addEventListener('input', calcularSubtotal);
    precioInput.addEventListener('input', calcularSubtotal);
    
    // Validación del formulario
    document.getElementById('materialForm').addEventListener('submit', function(e) {
        const selectedOption = selectItem.options[selectItem.selectedIndex];
        const stock = parseInt(selectedOption.dataset.stock) || 0;
        const cantidad = parseInt(cantidadInput.value) || 0;
        
        if (cantidad > stock) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Stock Insuficiente',
                text: `La cantidad solicitada (${cantidad}) excede el stock disponible (${stock})`
            });
            return false;
        }
    });
});
</script>

<?php include '../../includes/footer.php'; ?>
