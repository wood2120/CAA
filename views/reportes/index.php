<?php
require_once '../../includes/functions.php';
require_once '../../models/Bitacora.php';
require_once '../../models/Inventario.php';
require_once '../../models/Trabajo.php';

requireLogin();
checkSessionTimeout();

$pageTitle = 'Reportes del Sistema';
include '../../includes/header.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Estadísticas generales
    $stats = [];
    
    // Total de trabajos
    $stmt = $db->query("SELECT COUNT(*) as total FROM TB_Trabajos");
    $stats['total_trabajos'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Trabajos del mes actual
    $stmt = $db->query("SELECT COUNT(*) as total FROM TB_Trabajos WHERE MONTH(Fecha_Creacion) = MONTH(CURRENT_DATE) AND YEAR(Fecha_Creacion) = YEAR(CURRENT_DATE)");
    $stats['trabajos_mes'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total inventario
    $stmt = $db->query("SELECT COUNT(*) as total FROM TB_Inventario WHERE Estado = 'Activo'");
    $stats['total_inventario'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Items con stock bajo
    $stmt = $db->query("SELECT COUNT(*) as total FROM TB_Inventario WHERE Cantidad_Stock <= Stock_Minimo");
    $stats['stock_bajo'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Valor total del inventario
    $stmt = $db->query("SELECT SUM(Cantidad_Stock * Precio_Unitario) as total FROM TB_Inventario WHERE Estado = 'Activo'");
    $stats['valor_inventario'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    
} catch (Exception $e) {
    $error = "Error al cargar estadísticas: " . $e->getMessage();
}

logActivity($_SESSION['user_id'], "Acceso a reportes del sistema");
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-chart-bar"></i> Reportes del Sistema
        </h1>
    </div>

    <?php if (isset($error)): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
    </div>
    <?php endif; ?>

    <!-- Estadísticas Generales -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Trabajos
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($stats['total_trabajos']); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-briefcase fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Trabajos Este Mes
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($stats['trabajos_mes']); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Items Inventario
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($stats['total_inventario']); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-boxes fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Stock Bajo
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($stats['stock_bajo']); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Valor Total del Inventario -->
    <div class="row mb-4">
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Valor del Inventario</h6>
                </div>
                <div class="card-body">
                    <div class="text-center">
                        <h2 class="text-success"><?php echo formatCurrency($stats['valor_inventario']); ?></h2>
                        <p class="text-muted">Valor total del inventario activo</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Enlaces a Reportes Específicos -->
    <div class="row">
        <!-- Reportes Principales -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-bar"></i> Reportes Principales
                    </h6>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <a href="clientes.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-users text-primary"></i>
                            <strong>Reporte de Clientes</strong>
                            <p class="mb-1 text-muted">Estadísticas y análisis de clientes</p>
                        </a>
                        <a href="trabajos.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-briefcase text-info"></i>
                            <strong>Reporte de Trabajos</strong>
                            <p class="mb-1 text-muted">Análisis completo de trabajos</p>
                        </a>
                        <a href="inventario.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-boxes text-success"></i>
                            <strong>Reporte de Inventario</strong>
                            <p class="mb-1 text-muted">Estado completo del inventario</p>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-warehouse"></i> Reportes de Inventario
                    </h6>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <a href="stock_bajo.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-exclamation-triangle text-warning"></i>
                            <strong>Stock Bajo</strong>
                            <p class="mb-1 text-muted">Items que necesitan reabastecimiento</p>
                        </a>
                        <a href="movimientos_inventario.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-exchange-alt text-info"></i>
                            <strong>Movimientos de Inventario</strong>
                            <p class="mb-1 text-muted">Entradas y salidas del inventario</p>
                        </a>
                        <a href="valoracion_inventario.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-dollar-sign text-success"></i>
                            <strong>Valoración del Inventario</strong>
                            <p class="mb-1 text-muted">Análisis de costos y valores</p>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-line"></i> Reportes de Actividad
                    </h6>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <a href="trabajos_resumen.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-chart-line text-primary"></i>
                            <strong>Resumen de Trabajos</strong>
                            <p class="mb-1 text-muted">Estadísticas generales de trabajos</p>
                        </a>
                        <a href="bitacora.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-history text-dark"></i>
                            <strong>Bitácora del Sistema</strong>
                            <p class="mb-1 text-muted">Registro de actividades del sistema</p>
                        </a>
                        <div class="list-group-item">
                            <i class="fas fa-tools text-secondary"></i>
                            <strong>Análisis de Materiales</strong>
                            <p class="mb-1 text-muted">
                                <small class="badge bg-warning">Próximamente</small>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Accesos Rápidos -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-tachometer-alt"></i> Accesos Rápidos
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <a href="clientes.php?exportar=csv" class="btn btn-outline-success btn-block">
                                <i class="fas fa-file-csv"></i> Exportar Clientes
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="trabajos.php?exportar=csv" class="btn btn-outline-primary btn-block">
                                <i class="fas fa-file-csv"></i> Exportar Trabajos
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="inventario.php?exportar=csv" class="btn btn-outline-info btn-block">
                                <i class="fas fa-file-csv"></i> Exportar Inventario
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="inventario.php?stock_bajo=1" class="btn btn-outline-warning btn-block">
                                <i class="fas fa-exclamation-triangle"></i> Ver Stock Bajo
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
