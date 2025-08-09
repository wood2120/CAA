<?php
require_once '../../includes/functions.php';

requireLogin();
checkSessionTimeout();

$pageTitle = 'Valoración del Inventario';
include '../../includes/header.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Obtener valoración completa usando la vista
    $query = "SELECT * FROM VW_Inventario_Completo WHERE Estado = 'Activo' ORDER BY Valor_Total_Stock DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    // Obtener totales por categoría
    $queryTotales = "SELECT 
                       c.Nombre_Categoria,
                       c.Tipo,
                       COUNT(i.ID_Inventario) as total_items,
                       SUM(i.Cantidad_Stock) as total_cantidad,
                       SUM(i.Cantidad_Stock * i.Precio_Unitario) as valor_total
                     FROM TB_Categorias c
                     LEFT JOIN TB_Inventario i ON c.ID_Categoria = i.ID_Categoria AND i.Estado = 'Activo'
                     GROUP BY c.ID_Categoria, c.Nombre_Categoria, c.Tipo
                     ORDER BY valor_total DESC";
    $stmtTotales = $db->prepare($queryTotales);
    $stmtTotales->execute();
    
    // Total general
    $queryTotal = "SELECT 
                     COUNT(*) as total_items,
                     SUM(Cantidad_Stock) as total_cantidad,
                     SUM(Cantidad_Stock * Precio_Unitario) as valor_total
                   FROM TB_Inventario 
                   WHERE Estado = 'Activo'";
    $stmtTotal = $db->prepare($queryTotal);
    $stmtTotal->execute();
    $totales = $stmtTotal->fetch(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error = "Error al cargar reporte: " . $e->getMessage();
}

logActivity($_SESSION['user_id'], "Acceso a valoración del inventario");
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-dollar-sign text-success"></i> Valoración del Inventario
        </h1>
        <div>
            <a href="index.php" class="btn btn-secondary me-2">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fas fa-print"></i> Imprimir
            </button>
        </div>
    </div>

    <?php if (isset($error)): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
    </div>
    <?php endif; ?>

    <!-- Resumen General -->
    <div class="row mb-4">
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Items
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($totales['total_items']); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-boxes fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Cantidad Total
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($totales['total_cantidad']); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-cubes fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Valor Total
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo formatCurrency($totales['valor_total']); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Valoración por Categoría -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Valoración por Categoría</h6>
        </div>
        <div class="card-body">
            <?php if ($stmtTotales->rowCount() > 0): ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Categoría</th>
                            <th>Tipo</th>
                            <th>Items</th>
                            <th>Cantidad Total</th>
                            <th>Valor Total</th>
                            <th>% del Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($categoria = $stmtTotales->fetch(PDO::FETCH_ASSOC)): ?>
                        <?php 
                        $porcentaje = $totales['valor_total'] > 0 ? 
                                     ($categoria['valor_total'] / $totales['valor_total']) * 100 : 0;
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($categoria['Nombre_Categoria']); ?></strong>
                            </td>
                            <td>
                                <span class="badge <?php echo $categoria['Tipo'] == 'Material' ? 'bg-info' : 'bg-warning'; ?>">
                                    <?php echo htmlspecialchars($categoria['Tipo']); ?>
                                </span>
                            </td>
                            <td><?php echo number_format($categoria['total_items']); ?></td>
                            <td><?php echo number_format($categoria['total_cantidad']); ?></td>
                            <td><strong><?php echo formatCurrency($categoria['valor_total']); ?></strong></td>
                            <td>
                                <div class="progress" style="height: 20px;">
                                    <div class="progress-bar" role="progressbar" 
                                         style="width: <?php echo $porcentaje; ?>%"
                                         aria-valuenow="<?php echo $porcentaje; ?>" 
                                         aria-valuemin="0" aria-valuemax="100">
                                        <?php echo number_format($porcentaje, 1); ?>%
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Detalle por Item -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Detalle por Item</h6>
        </div>
        <div class="card-body">
            <?php if ($stmt->rowCount() > 0): ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Item</th>
                            <th>Categoría</th>
                            <th>Proveedor</th>
                            <th>Stock</th>
                            <th>Precio Unit.</th>
                            <th>Valor Total</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($item = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($item['Nombre']); ?></strong>
                                <?php if (!empty($item['Descripcion'])): ?>
                                <br><small class="text-muted"><?php echo htmlspecialchars($item['Descripcion']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge <?php echo $item['Tipo_Categoria'] == 'Material' ? 'bg-info' : 'bg-warning'; ?>">
                                    <?php echo htmlspecialchars($item['Nombre_Categoria']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($item['Nombre_Proveedor'] ?? 'N/A'); ?></td>
                            <td>
                                <?php echo number_format($item['Cantidad_Stock']); ?> <?php echo $item['Unidad_Medida']; ?>
                                <?php if ($item['Cantidad_Stock'] <= $item['Stock_Minimo']): ?>
                                    <i class="fas fa-exclamation-triangle text-warning" title="Stock bajo"></i>
                                <?php endif; ?>
                            </td>
                            <td><?php echo formatCurrency($item['Precio_Unitario']); ?></td>
                            <td><strong><?php echo formatCurrency($item['Valor_Total_Stock']); ?></strong></td>
                            <td>
                                <span class="badge <?php echo $item['Estado'] == 'Activo' ? 'bg-success' : 'bg-secondary'; ?>">
                                    <?php echo htmlspecialchars($item['Estado']); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="text-center py-4">
                <i class="fas fa-boxes fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No hay items en el inventario</h5>
                <a href="../inventario/create.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Agregar Primer Item
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Estilos de impresión
const style = document.createElement('style');
style.textContent = `
    @media print {
        .btn { display: none !important; }
        .card { border: 1px solid #ddd !important; box-shadow: none !important; }
        .card-header { background: #f8f9fa !important; border-bottom: 1px solid #ddd !important; }
        .badge { color: #000 !important; border: 1px solid #000 !important; }
        .progress { border: 1px solid #ddd !important; }
        .progress-bar { background: #6c757d !important; }
    }
`;
document.head.appendChild(style);
</script>

<?php include '../../includes/footer.php'; ?>
