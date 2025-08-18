<?php
require_once '../../includes/functions.php';
require_once '../../config/database.php';
require_once '../../models/Cliente.php';
require_once '../../models/Trabajo.php';

requireLogin();
checkSessionTimeout();

$pageTitle = 'Reporte de Clientes';
include '../../includes/header.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $clienteModel = new Cliente($db);
    $trabajoModel = new Trabajo($db);
    
    // Obtener filtros
    $filtro_fecha = $_GET['fecha'] ?? '';
    $filtro_cliente = $_GET['cliente'] ?? '';
    $exportar = $_GET['exportar'] ?? '';
    
    // Consulta base para clientes con estadísticas
    $query = "SELECT 
                c.Cedula,
                c.Nombre,
                c.Contacto,
                c.Empresa,
                COUNT(t.ID_Trabajo) as total_trabajos,
                COALESCE(SUM(CASE WHEN t.Estado = 'Completado' THEN 1 ELSE 0 END), 0) as trabajos_completados,
                COALESCE(SUM(CASE WHEN t.Estado = 'Pendiente' THEN 1 ELSE 0 END), 0) as trabajos_pendientes,
                COALESCE(SUM(CASE WHEN t.Estado = 'En Proceso' THEN 1 ELSE 0 END), 0) as trabajos_proceso,
                COALESCE(SUM(t.Precio_Total), 0) as ingresos_totales,
                MAX(t.Fecha_Creacion) as ultimo_trabajo
              FROM TB_Clientes c
              LEFT JOIN TB_Trabajos t ON c.Cedula = t.Cedula_Cliente";
    
    $conditions = [];
    $params = [];
    
    if (!empty($filtro_fecha)) {
        $conditions[] = "DATE(t.Fecha_Creacion) >= :fecha";
        $params[':fecha'] = $filtro_fecha;
    }
    
    if (!empty($filtro_cliente)) {
        $conditions[] = "(c.Nombre LIKE :cliente OR c.Cedula LIKE :cliente OR c.Empresa LIKE :cliente)";
        $params[':cliente'] = "%{$filtro_cliente}%";
    }
    
    if (!empty($conditions)) {
        $query .= " WHERE " . implode(" AND ", $conditions);
    }
    
    $query .= " GROUP BY c.Cedula, c.Nombre, c.Contacto, c.Empresa
                ORDER BY ingresos_totales DESC, c.Nombre";
    
    $stmt = $db->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Estadísticas generales
    $stmt_stats = $db->query("SELECT 
                                COUNT(*) as total_clientes,
                                SUM(CASE WHEN ultimo_trabajo >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as clientes_activos
                              FROM (
                                SELECT c.Cedula, MAX(t.Fecha_Creacion) as ultimo_trabajo
                                FROM TB_Clientes c
                                LEFT JOIN TB_Trabajos t ON c.Cedula = t.Cedula_Cliente
                                GROUP BY c.Cedula
                              ) subquery");
    $stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);
    
    // Si se solicita exportación a CSV
    if ($exportar === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=reporte_clientes_' . date('Y-m-d') . '.csv');
        
        $output = fopen('php://output', 'w');
        
        // BOM para UTF-8
        fwrite($output, "\xEF\xBB\xBF");
        
        // Encabezados
        fputcsv($output, [
            'Cedula', 'Nombre', 'Contacto', 'Empresa', 'Total_Trabajos', 
            'Completados', 'Pendientes', 'En_Proceso', 'Ingresos_Totales', 'Ultimo_Trabajo'
        ]);
        
        // Datos (sin formatear con separadores de miles para que Excel interprete como número)
        foreach ($clientes as $cliente) {
            $ultimo = $cliente['ultimo_trabajo'] ? date('Y-m-d', strtotime($cliente['ultimo_trabajo'])) : '';
            $row = [
                $cliente['Cedula'],
                preg_replace("/[\r\n]+/", ' ', $cliente['Nombre']),
                preg_replace("/[\r\n]+/", ' ', $cliente['Contacto']),
                preg_replace("/[\r\n]+/", ' ', ($cliente['Empresa'] ?: '')),
                (int)$cliente['total_trabajos'],
                (int)$cliente['trabajos_completados'],
                (int)$cliente['trabajos_pendientes'],
                (int)$cliente['trabajos_proceso'],
                // Usar punto decimal y sin separador de miles
                number_format((float)$cliente['ingresos_totales'], 2, '.', ''),
                $ultimo
            ];
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit;
    }
    
} catch (Exception $e) {
    $error = "Error al generar el reporte: " . $e->getMessage();
}

logActivity($_SESSION['user_id'], "Generó reporte de clientes");
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-users"></i> Reporte de Clientes
        </h1>
        <div class="btn-group">
            <a href="?exportar=csv<?php echo !empty($filtro_fecha) ? '&fecha=' . $filtro_fecha : ''; ?><?php echo !empty($filtro_cliente) ? '&cliente=' . urlencode($filtro_cliente) : ''; ?>" 
               class="btn btn-success btn-sm">
                <i class="fas fa-file-csv"></i> Exportar CSV
            </a>
            <button onclick="window.print()" class="btn btn-info btn-sm">
                <i class="fas fa-print"></i> Imprimir
            </button>
        </div>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <!-- Estadísticas -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Clientes
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($stats['total_clientes'] ?? 0); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
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
                                Clientes Activos (30 días)
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($stats['clientes_activos'] ?? 0); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-check fa-2x text-gray-300"></i>
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
                                Total Ingresos
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                $<?php 
                                $total_ingresos = array_sum(array_column($clientes, 'ingresos_totales'));
                                echo number_format($total_ingresos, 2); 
                                ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
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
                                Promedio por Cliente
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                $<?php 
                                $promedio = $stats['total_clientes'] > 0 ? $total_ingresos / $stats['total_clientes'] : 0;
                                echo number_format($promedio, 2); 
                                ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-filter"></i> Filtros
            </h6>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label for="fecha" class="form-label">Desde fecha:</label>
                    <input type="date" class="form-control" id="fecha" name="fecha" 
                           value="<?php echo htmlspecialchars($filtro_fecha); ?>">
                </div>
                <div class="col-md-4">
                    <label for="cliente" class="form-label">Buscar cliente:</label>
                    <input type="text" class="form-control" id="cliente" name="cliente" 
                           placeholder="Nombre, cédula o empresa..." 
                           value="<?php echo htmlspecialchars($filtro_cliente); ?>">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-search"></i> Filtrar
                    </button>
                    <a href="clientes.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Limpiar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla de resultados -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-table"></i> Reporte Detallado de Clientes
                <span class="badge bg-info text-white ms-2"><?php echo count($clientes); ?> registros</span>
            </h6>
        </div>
        <div class="card-body">
            <?php if (empty($clientes)): ?>
                <div class="text-center">
                    <i class="fas fa-info-circle text-muted fa-3x mb-3"></i>
                    <p class="text-muted">No se encontraron clientes con los criterios especificados.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="dataTable">
                        <thead class="table-dark">
                            <tr>
                                <th>Cédula</th>
                                <th>Nombre</th>
                                <th>Contacto</th>
                                <th>Empresa</th>
                                <th>Total Trabajos</th>
                                <th>Completados</th>
                                <th>Pendientes</th>
                                <th>En Proceso</th>
                                <th>Ingresos Totales</th>
                                <th>Último Trabajo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($clientes as $cliente): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($cliente['Cedula']); ?></td>
                                    <td><strong><?php echo htmlspecialchars($cliente['Nombre']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($cliente['Contacto']); ?></td>
                                    <td><?php echo htmlspecialchars($cliente['Empresa'] ?: 'N/A'); ?></td>
                                    <td>
                                        <span class="badge bg-primary">
                                            <?php echo $cliente['total_trabajos']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-success">
                                            <?php echo $cliente['trabajos_completados']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-warning">
                                            <?php echo $cliente['trabajos_pendientes']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">
                                            <?php echo $cliente['trabajos_proceso']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong class="text-success">
                                            $<?php echo number_format($cliente['ingresos_totales'], 2); ?>
                                        </strong>
                                    </td>
                                    <td>
                                        <?php 
                                        if ($cliente['ultimo_trabajo']) {
                                            echo date('d/m/Y', strtotime($cliente['ultimo_trabajo']));
                                        } else {
                                            echo '<span class="text-muted">Nunca</span>';
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// DataTable initialization
$(document).ready(function() {
    $('#dataTable').DataTable({
        "pageLength": 25,
        "order": [[8, "desc"]], // Ordenar por ingresos totales
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Spanish.json"
        }
    });
});
</script>

<?php include '../../includes/footer.php'; ?>
