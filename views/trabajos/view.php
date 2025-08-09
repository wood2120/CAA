<?php
require_once '../../includes/functions.php';
require_once '../../models/Trabajo.php';
require_once '../../models/Cliente.php';
require_once '../../models/TrabajoInventario.php';

requireLogin();
checkSessionTimeout();

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id = (int)$_GET['id'];
$pageTitle = 'Detalles del Trabajo';
include '../../includes/header.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    $trabajoModel = new Trabajo($db);
    $trabajoInventarioModel = new TrabajoInventario($db);
    
    // Obtener datos del trabajo
    $trabajo = $trabajoModel->readOne($id);
    
    if (!$trabajo) {
        throw new Exception("Trabajo no encontrado");
    }
    
    // Obtener materiales/herramientas usados
    $materialesStmt = $trabajoInventarioModel->getItemsPorTrabajo($id);
    
} catch (Exception $e) {
    header("Location: index.php?error=" . urlencode($e->getMessage()));
    exit();
}

logActivity($_SESSION['user_id'], "Vista de detalles trabajo ID: " . $id);
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-eye"></i> Detalles del Trabajo #<?php echo $trabajo['ID_Trabajo']; ?>
        </h1>
        <div>
            <a href="index.php" class="btn btn-secondary me-2">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
            <a href="edit.php?id=<?php echo $trabajo['ID_Trabajo']; ?>" class="btn btn-warning me-2">
                <i class="fas fa-edit"></i> Editar
            </a>
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fas fa-print"></i> Imprimir
            </button>
        </div>
    </div>

    <div class="row">
        <!-- Información del Trabajo -->
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Información General</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6><i class="fas fa-briefcase text-primary"></i> Tipo de Trabajo</h6>
                            <p class="mb-3"><?php echo htmlspecialchars($trabajo['Tipo_Trabajo']); ?></p>
                            
                            <h6><i class="fas fa-user text-info"></i> Cliente</h6>
                            <p class="mb-3">
                                <strong><?php echo htmlspecialchars($trabajo['cliente_nombre'] ?? 'N/A'); ?></strong><br>
                                <small class="text-muted">Cédula: <?php echo htmlspecialchars($trabajo['Cedula_Cliente']); ?></small>
                            </p>
                            
                            <h6><i class="fas fa-info-circle text-secondary"></i> Estado</h6>
                            <p class="mb-3">
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
                        <div class="col-md-6">
                            <h6><i class="fas fa-calendar text-success"></i> Fechas</h6>
                            <p class="mb-3">
                                <strong>Creación:</strong> <?php echo formatDate($trabajo['Fecha_Creacion']); ?><br>
                                <strong>Inicio:</strong> <?php echo formatDate($trabajo['Fecha_Inicio']); ?><br>
                                <strong>Final:</strong> <?php echo formatDate($trabajo['Fecha_Final']); ?>
                            </p>
                            
                            <h6><i class="fas fa-dollar-sign text-success"></i> Costos</h6>
                            <p class="mb-3">
                                <strong>Mano de Obra:</strong> <?php echo formatCurrency($trabajo['Precio_Mano_Obra']); ?><br>
                                <strong>Total:</strong> <span class="text-success fw-bold"><?php echo formatCurrency($trabajo['Precio_Total']); ?></span>
                            </p>
                        </div>
                    </div>
                    
                    <?php if (!empty($trabajo['Descripcion'])): ?>
                    <div class="row">
                        <div class="col-12">
                            <h6><i class="fas fa-align-left text-dark"></i> Descripción</h6>
                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($trabajo['Descripcion'])); ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Materiales y Herramientas Utilizados -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-tools"></i> Materiales y Herramientas Utilizados
                    </h6>
                </div>
                <div class="card-body">
                    <?php if ($materialesStmt->rowCount() > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Item</th>
                                    <th>Categoría</th>
                                    <th>Cantidad</th>
                                    <th>Precio Unit.</th>
                                    <th>Subtotal</th>
                                    <th>Fecha Uso</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $totalMateriales = 0;
                                while ($material = $materialesStmt->fetch(PDO::FETCH_ASSOC)): 
                                    $totalMateriales += $material['Subtotal'];
                                ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($material['item_nombre']); ?></strong>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($material['Unidad_Medida']); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $material['tipo_categoria'] == 'Material' ? 'bg-info' : 'bg-warning'; ?>">
                                            <?php echo htmlspecialchars($material['Nombre_Categoria']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo number_format($material['Cantidad_Usada']); ?></td>
                                    <td><?php echo formatCurrency($material['Precio_Unitario_Usado']); ?></td>
                                    <td><strong><?php echo formatCurrency($material['Subtotal']); ?></strong></td>
                                    <td><?php echo formatDate($material['Fecha_Uso']); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="4" class="text-end">Total Materiales:</th>
                                    <th><?php echo formatCurrency($totalMateriales); ?></th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-tools fa-3x text-muted mb-3"></i>
                        <h6 class="text-muted">No se han registrado materiales o herramientas para este trabajo</h6>
                        <a href="agregar_materiales.php?id=<?php echo $trabajo['ID_Trabajo']; ?>" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Agregar Materiales
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Panel Lateral -->
        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Acciones Rápidas</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="agregar_materiales.php?id=<?php echo $trabajo['ID_Trabajo']; ?>" class="btn btn-success">
                            <i class="fas fa-plus"></i> Agregar Materiales
                        </a>
                        <a href="edit.php?id=<?php echo $trabajo['ID_Trabajo']; ?>" class="btn btn-warning">
                            <i class="fas fa-edit"></i> Editar Trabajo
                        </a>
                        <?php if ($trabajo['Estado'] == 'Pendiente'): ?>
                        <button class="btn btn-info" onclick="cambiarEstado('En Proceso')">
                            <i class="fas fa-play"></i> Marcar En Proceso
                        </button>
                        <?php elseif ($trabajo['Estado'] == 'En Proceso'): ?>
                        <button class="btn btn-success" onclick="cambiarEstado('Completado')">
                            <i class="fas fa-check"></i> Marcar Completado
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Resumen de Costos -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Resumen de Costos</h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-12 mb-3">
                            <h6 class="text-muted">Mano de Obra</h6>
                            <h4 class="text-info"><?php echo formatCurrency($trabajo['Precio_Mano_Obra']); ?></h4>
                        </div>
                        <?php if (isset($totalMateriales) && $totalMateriales > 0): ?>
                        <div class="col-12 mb-3">
                            <h6 class="text-muted">Materiales</h6>
                            <h4 class="text-warning"><?php echo formatCurrency($totalMateriales); ?></h4>
                        </div>
                        <?php endif; ?>
                        <div class="col-12">
                            <hr>
                            <h6 class="text-muted">Total</h6>
                            <h3 class="text-success"><?php echo formatCurrency($trabajo['Precio_Total']); ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function cambiarEstado(nuevoEstado) {
    Swal.fire({
        title: '¿Cambiar estado?',
        text: `¿Desea cambiar el estado del trabajo a "${nuevoEstado}"?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, cambiar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            // Aquí se podría implementar la funcionalidad para cambiar el estado
            window.location.href = `cambiar_estado.php?id=<?php echo $trabajo['ID_Trabajo']; ?>&estado=${nuevoEstado}`;
        }
    });
}

// Estilos de impresión
const style = document.createElement('style');
style.textContent = `
    @media print {
        .btn { display: none !important; }
        .card { border: 1px solid #ddd !important; box-shadow: none !important; }
        .card-header { background: #f8f9fa !important; border-bottom: 1px solid #ddd !important; }
    }
`;
document.head.appendChild(style);
</script>

<?php include '../../includes/footer.php'; ?>
