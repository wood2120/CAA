<?php
require_once '../../includes/functions.php';
require_once '../../models/Trabajo.php';

requireLogin();
checkSessionTimeout();

// Aceptar ID por GET (links de confirmación) o POST (botón eliminar desde listado)
$id = (int)($_GET['id'] ?? $_POST['id_trabajo'] ?? 0);
if ($id <= 0) {
    header("Location: index.php");
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();
    $trabajoModel = new Trabajo($db);
    
    // Verificar que el trabajo existe
    $trabajo = $trabajoModel->readOne($id);
    if (!$trabajo) {
        throw new Exception("Trabajo no encontrado");
    }
    
    // Verificar si el trabajo tiene materiales asociados
    $queryCheck = "SELECT COUNT(*) as total FROM TB_Trabajo_Inventario WHERE ID_Trabajo = :id";
    $stmtCheck = $db->prepare($queryCheck);
    $stmtCheck->bindParam(':id', $id);
    $stmtCheck->execute();
    $rowCountMat = (int)$stmtCheck->fetch(PDO::FETCH_ASSOC)['total'];
    $tieneMateriales = $rowCountMat > 0;
    
    // Confirmar eliminación si es necesario
    if ($tieneMateriales && !isset($_GET['confirm'])) {
        // Mostrar página de confirmación
        $pageTitle = 'Confirmar Eliminación';
        include '../../includes/header.php';
        ?>
        
        <div class="container-fluid">
            <div class="d-sm-flex align-items-center justify-content-between mb-4">
                <h1 class="h3 mb-0 text-gray-800">
                    <i class="fas fa-exclamation-triangle text-warning"></i> Confirmar Eliminación
                </h1>
                <a href="view.php?id=<?php echo $id; ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>

            <div class="card shadow">
                <div class="card-header py-3 bg-warning">
                    <h6 class="m-0 font-weight-bold text-dark">
                        <i class="fas fa-exclamation-triangle"></i> Atención: Este trabajo tiene materiales asociados
                    </h6>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <h5><i class="fas fa-exclamation-triangle"></i> Información del Trabajo a Eliminar</h5>
                        <hr>
                        <p><strong>ID:</strong> <?php echo $trabajo['ID_Trabajo']; ?></p>
                        <p><strong>Tipo:</strong> <?php echo htmlspecialchars($trabajo['Tipo_Trabajo']); ?></p>
                        <p><strong>Cliente:</strong> <?php echo htmlspecialchars($trabajo['cliente_nombre'] ?? $trabajo['NombreCliente'] ?? 'Sin cliente'); ?></p>
                        <p><strong>Estado:</strong> <?php echo htmlspecialchars($trabajo['Estado']); ?></p>
                        <p><strong>Precio Total:</strong> <?php echo formatCurrency($trabajo['Precio_Total']); ?></p>
                    </div>
                    
                    <div class="alert alert-danger">
                        <h6><i class="fas fa-warning"></i> Consecuencias de la eliminación:</h6>
                        <ul>
                            <li>Se eliminarán <strong><?php echo $rowCountMat; ?> registros</strong> de materiales/herramientas asociados</li>
                            <li>Se crearán movimientos de inventario para devolver el stock (si aplicable)</li>
                            <li>Esta acción <strong>NO SE PUEDE DESHACER</strong></li>
                        </ul>
                    </div>
                    
                    <?php
                    // Mostrar materiales que se verán afectados
                    $queryMateriales = "SELECT 
                                         ti.*,
                                         i.Nombre as item_nombre,
                                         i.Unidad_Medida
                                       FROM TB_Trabajo_Inventario ti
                                       LEFT JOIN TB_Inventario i ON ti.ID_Inventario = i.ID_Inventario
                                       WHERE ti.ID_Trabajo = :id";
                    $stmtMateriales = $db->prepare($queryMateriales);
                    $stmtMateriales->bindParam(':id', $id);
                    $stmtMateriales->execute();
                    ?>
                    
                    <h6>Materiales/Herramientas que se eliminarán del trabajo:</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Item</th>
                                    <th>Cantidad Usada</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($material = $stmtMateriales->fetch(PDO::FETCH_ASSOC)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($material['item_nombre']); ?></td>
                                    <td><?php echo number_format($material['Cantidad_Usada']); ?> <?php echo $material['Unidad_Medida']; ?></td>
                                    <td><?php echo formatCurrency($material['Subtotal']); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-4">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="d-grid">
                                    <a href="view.php?id=<?php echo $id; ?>" class="btn btn-secondary btn-lg">
                                        <i class="fas fa-times"></i> Cancelar
                                    </a>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-grid">
                                    <a href="delete.php?id=<?php echo $id; ?>&confirm=1" class="btn btn-danger btn-lg">
                                        <i class="fas fa-trash"></i> Confirmar Eliminación
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php
        include '../../includes/footer.php';
        exit();
    }
    
    // Proceder con la eliminación
    // Usar la propiedad correcta definida en el modelo
    $trabajoModel->ID_Trabajo = $id;
    
    if ($trabajoModel->delete()) {
        logActivity($_SESSION['user_id'], "Trabajo eliminado ID: $id - " . $trabajo['Tipo_Trabajo']);
        header("Location: index.php?success=deleted");
    } else {
        header("Location: index.php?error=" . urlencode("Error al eliminar el trabajo"));
    }
    
} catch (Exception $e) {
    header("Location: index.php?error=" . urlencode($e->getMessage()));
}
exit();
?>
