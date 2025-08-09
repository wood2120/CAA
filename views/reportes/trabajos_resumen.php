<?php
require_once '../../includes/functions.php';

requireLogin();
checkSessionTimeout();

$pageTitle = 'Resumen de Trabajos';
include '../../includes/header.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Filtros de fecha
    $fecha_desde = isset($_GET['fecha_desde']) ? $_GET['fecha_desde'] : date('Y-m-01'); // Primer día del mes actual
    $fecha_hasta = isset($_GET['fecha_hasta']) ? $_GET['fecha_hasta'] : date('Y-m-t');   // Último día del mes actual
    
    // Estadísticas generales en el período
    $queryStats = "SELECT 
                     COUNT(*) as total_trabajos,
                     COUNT(CASE WHEN Estado = 'Completado' THEN 1 END) as completados,
                     COUNT(CASE WHEN Estado = 'En Proceso' THEN 1 END) as en_proceso,
                     COUNT(CASE WHEN Estado = 'Pendiente' THEN 1 END) as pendientes,
                     COUNT(CASE WHEN Estado = 'Cancelado' THEN 1 END) as cancelados,
                     SUM(CASE WHEN Estado = 'Completado' THEN Precio_Total ELSE 0 END) as ingresos_completados,
                     SUM(Precio_Total) as valor_total,
                     AVG(CASE WHEN Estado = 'Completado' THEN Precio_Total END) as promedio_completados
                   FROM TB_Trabajos 
                   WHERE DATE(Fecha_Creacion) BETWEEN :fecha_desde AND :fecha_hasta";
    
    $stmtStats = $db->prepare($queryStats);
    $stmtStats->bindParam(':fecha_desde', $fecha_desde);
    $stmtStats->bindParam(':fecha_hasta', $fecha_hasta);
    $stmtStats->execute();
    $stats = $stmtStats->fetch(PDO::FETCH_ASSOC);
    
    // Trabajos por tipo
    $queryTipos = "SELECT 
                     Tipo_Trabajo,
                     COUNT(*) as cantidad,
                     SUM(CASE WHEN Estado = 'Completado' THEN Precio_Total ELSE 0 END) as ingresos,
                     AVG(CASE WHEN Estado = 'Completado' THEN Precio_Total END) as promedio
                   FROM TB_Trabajos 
                   WHERE DATE(Fecha_Creacion) BETWEEN :fecha_desde AND :fecha_hasta
                   GROUP BY Tipo_Trabajo
                   ORDER BY cantidad DESC";
    
    $stmtTipos = $db->prepare($queryTipos);
    $stmtTipos->bindParam(':fecha_desde', $fecha_desde);
    $stmtTipos->bindParam(':fecha_hasta', $fecha_hasta);
    $stmtTipos->execute();
    
    // Top clientes
    $queryClientes = "SELECT 
                        cl.Nombre as cliente_nombre,
                        cl.Cedula,
                        COUNT(t.ID_Trabajo) as total_trabajos,
                        SUM(CASE WHEN t.Estado = 'Completado' THEN t.Precio_Total ELSE 0 END) as total_facturado
                      FROM TB_Trabajos t
                      LEFT JOIN TB_Clientes cl ON t.Cedula_Cliente = cl.Cedula
                      WHERE DATE(t.Fecha_Creacion) BETWEEN :fecha_desde AND :fecha_hasta
                      GROUP BY cl.Cedula, cl.Nombre
                      HAVING COUNT(t.ID_Trabajo) > 0
                      ORDER BY total_facturado DESC
                      LIMIT 10";
    
    $stmtClientes = $db->prepare($queryClientes);
    $stmtClientes->bindParam(':fecha_desde', $fecha_desde);
    $stmtClientes->bindParam(':fecha_hasta', $fecha_hasta);
    $stmtClientes->execute();
    
    // Trabajos recientes
    $queryRecientes = "SELECT 
                         t.*,
                         cl.Nombre as cliente_nombre
                       FROM TB_Trabajos t
                       LEFT JOIN TB_Clientes cl ON t.Cedula_Cliente = cl.Cedula
                       WHERE DATE(t.Fecha_Creacion) BETWEEN :fecha_desde AND :fecha_hasta
                       ORDER BY t.Fecha_Creacion DESC
                       LIMIT 10";
    
    $stmtRecientes = $db->prepare($queryRecientes);
    $stmtRecientes->bindParam(':fecha_desde', $fecha_desde);
    $stmtRecientes->bindParam(':fecha_hasta', $fecha_hasta);
    $stmtRecientes->execute();
    
} catch (Exception $e) {
    $error = "Error al cargar reporte: " . $e->getMessage();
}

logActivity($_SESSION['user_id'], "Acceso a resumen de trabajos");
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-chart-line text-primary"></i> Resumen de Trabajos
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

    <!-- Filtros de Fecha -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filtros de Fecha</h6>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label for="fecha_desde" class="form-label">Fecha Desde</label>
                    <input type="date" class="form-control" id="fecha_desde" name="fecha_desde" 
                           value="<?php echo htmlspecialchars($fecha_desde); ?>">
                </div>
                <div class="col-md-4">
                    <label for="fecha_hasta" class="form-label">Fecha Hasta</label>
                    <input type="date" class="form-control" id="fecha_hasta" name="fecha_hasta" 
                           value="<?php echo htmlspecialchars($fecha_hasta); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-grid gap-2 d-md-flex">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i> Filtrar
                        </button>
                        <button type="button" class="btn btn-outline-secondary" onclick="setCurrentMonth()">
                            <i class="fas fa-calendar"></i> Mes Actual
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Período Seleccionado -->
    <div class="alert alert-info">
        <i class="fas fa-calendar-alt"></i>
        <strong>Período:</strong> <?php echo formatDate($fecha_desde); ?> - <?php echo formatDate($fecha_hasta); ?>
    </div>

    <!-- Estadísticas Principales -->
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
                                Completados
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($stats['completados']); ?>
                                <small class="text-muted">
                                    (<?php echo $stats['total_trabajos'] > 0 ? number_format(($stats['completados'] / $stats['total_trabajos']) * 100, 1) : 0; ?>%)
                                </small>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
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
                                En Proceso
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($stats['en_proceso']); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-cog fa-2x text-gray-300"></i>
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
                                Ingresos
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo formatCurrency($stats['ingresos_completados']); ?>
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

    <div class="row">
        <!-- Trabajos por Tipo -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Trabajos por Tipo</h6>
                </div>
                <div class="card-body">
                    <?php if ($stmtTipos->rowCount() > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Tipo</th>
                                    <th>Cantidad</th>
                                    <th>Ingresos</th>
                                    <th>Promedio</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($tipo = $stmtTipos->fetch(PDO::FETCH_ASSOC)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($tipo['Tipo_Trabajo']); ?></td>
                                    <td><?php echo number_format($tipo['cantidad']); ?></td>
                                    <td><?php echo formatCurrency($tipo['ingresos']); ?></td>
                                    <td><?php echo formatCurrency($tipo['promedio']); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <p class="text-muted">No hay datos para mostrar</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Top Clientes -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Top Clientes</h6>
                </div>
                <div class="card-body">
                    <?php if ($stmtClientes->rowCount() > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Cliente</th>
                                    <th>Trabajos</th>
                                    <th>Total Facturado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($cliente = $stmtClientes->fetch(PDO::FETCH_ASSOC)): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($cliente['cliente_nombre']); ?></strong>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($cliente['Cedula']); ?></small>
                                    </td>
                                    <td><?php echo number_format($cliente['total_trabajos']); ?></td>
                                    <td><strong><?php echo formatCurrency($cliente['total_facturado']); ?></strong></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <p class="text-muted">No hay datos para mostrar</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Trabajos Recientes -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Trabajos Recientes (Últimos 10)</h6>
        </div>
        <div class="card-body">
            <?php if ($stmtRecientes->rowCount() > 0): ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Fecha</th>
                            <th>Tipo</th>
                            <th>Cliente</th>
                            <th>Estado</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($trabajo = $stmtRecientes->fetch(PDO::FETCH_ASSOC)): ?>
                        <tr>
                            <td><?php echo $trabajo['ID_Trabajo']; ?></td>
                            <td><?php echo formatDate($trabajo['Fecha_Creacion']); ?></td>
                            <td><?php echo htmlspecialchars($trabajo['Tipo_Trabajo']); ?></td>
                            <td><?php echo htmlspecialchars($trabajo['cliente_nombre'] ?? 'N/A'); ?></td>
                            <td>
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
                            </td>
                            <td><strong><?php echo formatCurrency($trabajo['Precio_Total']); ?></strong></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="text-center py-4">
                <i class="fas fa-briefcase fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No hay trabajos en el período seleccionado</h5>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function setCurrentMonth() {
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const firstDay = `${year}-${month}-01`;
    const lastDay = new Date(year, now.getMonth() + 1, 0).getDate();
    const lastDayFormatted = `${year}-${month}-${String(lastDay).padStart(2, '0')}`;
    
    document.getElementById('fecha_desde').value = firstDay;
    document.getElementById('fecha_hasta').value = lastDayFormatted;
}

// Estilos de impresión
const style = document.createElement('style');
style.textContent = `
    @media print {
        .btn { display: none !important; }
        .card { border: 1px solid #ddd !important; box-shadow: none !important; }
        .card-header { background: #f8f9fa !important; border-bottom: 1px solid #ddd !important; }
        .badge { color: #000 !important; border: 1px solid #000 !important; }
    }
`;
document.head.appendChild(style);
</script>

<?php include '../../includes/footer.php'; ?>
