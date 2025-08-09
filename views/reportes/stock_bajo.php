<?php
require_once '../../includes/functions.php';
require_once '../../models/Inventario.php';

requireLogin();
checkSessionTimeout();

$pageTitle = 'Reporte de Stock Bajo';
include '../../includes/header.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Obtener items con stock bajo usando la vista
    $query = "SELECT * FROM VW_Stock_Bajo ORDER BY Cantidad_Stock ASC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
} catch (Exception $e) {
    $error = "Error al cargar reporte: " . $e->getMessage();
}

logActivity($_SESSION['user_id'], "Acceso a reporte de stock bajo");
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-exclamation-triangle text-warning"></i> Reporte de Stock Bajo
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

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                Items que Requieren Reabastecimiento
            </h6>
        </div>
        <div class="card-body">
            <?php if (isset($stmt) && $stmt->rowCount() > 0): ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Atención:</strong> Se encontraron <?php echo $stmt->rowCount(); ?> items con stock bajo que requieren reabastecimiento.
            </div>
            
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Nombre del Item</th>
                            <th>Categoría</th>
                            <th>Stock Actual</th>
                            <th>Stock Mínimo</th>
                            <th>Diferencia</th>
                            <th>Estado</th>
                            <th class="no-print">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                        <?php 
                        $diferencia = $row['Stock_Minimo'] - $row['Cantidad_Stock'];
                        $criticidad = '';
                        if ($row['Cantidad_Stock'] == 0) {
                            $criticidad = 'table-danger';
                        } elseif ($row['Cantidad_Stock'] <= $row['Stock_Minimo'] / 2) {
                            $criticidad = 'table-warning';
                        }
                        ?>
                        <tr class="<?php echo $criticidad; ?>">
                            <td><?php echo htmlspecialchars($row['ID_Inventario']); ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($row['Nombre']); ?></strong>
                            </td>
                            <td>
                                <span class="badge bg-info">
                                    <?php echo htmlspecialchars($row['Nombre_Categoria']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge <?php echo $row['Cantidad_Stock'] == 0 ? 'bg-danger' : 'bg-warning'; ?>">
                                    <?php echo number_format($row['Cantidad_Stock']); ?>
                                </span>
                            </td>
                            <td><?php echo number_format($row['Stock_Minimo']); ?></td>
                            <td>
                                <span class="text-danger">
                                    <i class="fas fa-arrow-down"></i> <?php echo number_format($diferencia); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge <?php echo $row['Estado'] == 'Agotado' ? 'bg-danger' : 'bg-warning'; ?>">
                                    <?php echo htmlspecialchars($row['Estado']); ?>
                                </span>
                            </td>
                            <td class="no-print">
                                <div class="btn-group" role="group">
                                    <a href="../inventario/edit.php?id=<?php echo $row['ID_Inventario']; ?>" 
                                       class="btn btn-sm btn-outline-primary" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-outline-success" 
                                            onclick="reabastecer(<?php echo $row['ID_Inventario']; ?>, '<?php echo htmlspecialchars($row['Nombre']); ?>')" 
                                            title="Reabastecer">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="mt-3">
                <h6>Leyenda:</h6>
                <div class="row">
                    <div class="col-md-6">
                        <span class="badge bg-danger me-2"></span> Stock agotado (0 unidades)
                    </div>
                    <div class="col-md-6">
                        <span class="badge bg-warning me-2"></span> Stock crítico (50% o menos del mínimo)
                    </div>
                </div>
            </div>
            
            <?php else: ?>
            <div class="text-center py-4">
                <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                <h5 class="text-success">¡Excelente!</h5>
                <p class="text-muted">Todos los items tienen stock suficiente.</p>
                <a href="../inventario/index.php" class="btn btn-primary">
                    <i class="fas fa-boxes"></i> Ver Inventario Completo
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function reabastecer(id, nombre) {
    Swal.fire({
        title: 'Reabastecer Item',
        text: `¿Desea agregar stock para "${nombre}"?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, reabastecer',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `../inventario/edit.php?id=${id}&action=restock`;
        }
    });
}

// Estilos de impresión
const style = document.createElement('style');
style.textContent = `
    @media print {
        .no-print { display: none !important; }
        .btn { display: none !important; }
        .card { border: none !important; box-shadow: none !important; }
        .card-header { background: white !important; border: none !important; }
    }
`;
document.head.appendChild(style);
</script>

<?php include '../../includes/footer.php'; ?>
