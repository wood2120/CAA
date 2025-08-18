<?php
require_once '../../includes/functions.php';
require_once '../../config/database.php';
require_once '../../models/Trabajo.php';
require_once '../../models/Cliente.php';

requireLogin();
checkSessionTimeout();

$pageTitle = 'Reporte de Trabajos';
include '../../includes/header.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $trabajoModel = new Trabajo($db);
    $clienteModel = new Cliente($db);
    
    // Obtener filtros
    $filtro_fecha_inicio = $_GET['fecha_inicio'] ?? '';
    $filtro_fecha_fin = $_GET['fecha_fin'] ?? '';
    $filtro_estado = $_GET['estado'] ?? '';
    $filtro_cliente = $_GET['cliente'] ?? '';
    $exportar = $_GET['exportar'] ?? '';
    
    // Consulta base para trabajos
    $query = "SELECT 
                t.ID_Trabajo,
                t.Descripcion,
                t.Tipo_Trabajo,
                t.Estado,
                t.Fecha_Creacion,
                t.Fecha_Inicio,
                t.Fecha_Final,
                t.Precio_Total,
                t.Precio_Mano_Obra,
                c.Nombre as nombre_cliente,
                c.Cedula as cedula_cliente,
                c.Empresa,
                DATEDIFF(
                    COALESCE(t.Fecha_Final, CURRENT_DATE), 
                    t.Fecha_Creacion
                ) as dias_duracion
              FROM TB_Trabajos t
              LEFT JOIN TB_Clientes c ON t.Cedula_Cliente = c.Cedula";
    
    $conditions = [];
    $params = [];
    
    if (!empty($filtro_fecha_inicio)) {
        $conditions[] = "DATE(t.Fecha_Creacion) >= :fecha_inicio";
        $params[':fecha_inicio'] = $filtro_fecha_inicio;
    }
    
    if (!empty($filtro_fecha_fin)) {
        $conditions[] = "DATE(t.Fecha_Creacion) <= :fecha_fin";
        $params[':fecha_fin'] = $filtro_fecha_fin;
    }
    
    if (!empty($filtro_estado)) {
        $conditions[] = "t.Estado = :estado";
        $params[':estado'] = $filtro_estado;
    }
    
    if (!empty($filtro_cliente)) {
        $conditions[] = "(c.Nombre LIKE :cliente OR c.Cedula LIKE :cliente OR c.Empresa LIKE :cliente)";
        $params[':cliente'] = "%{$filtro_cliente}%";
    }
    
    if (!empty($conditions)) {
        $query .= " WHERE " . implode(" AND ", $conditions);
    }
    
    $query .= " ORDER BY t.Fecha_Creacion DESC";
    
    $stmt = $db->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $trabajos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Estadísticas
    $stats_query = "SELECT 
                      COUNT(*) as total_trabajos,
                      SUM(CASE WHEN Estado = 'Pendiente' THEN 1 ELSE 0 END) as pendientes,
                      SUM(CASE WHEN Estado = 'En Proceso' THEN 1 ELSE 0 END) as en_proceso,
                      SUM(CASE WHEN Estado = 'Completado' THEN 1 ELSE 0 END) as completados,
                      COALESCE(SUM(Precio_Total), 0) as ingresos_totales,
                      COALESCE(AVG(Precio_Total), 0) as precio_promedio,
                      COALESCE(AVG(DATEDIFF(COALESCE(Fecha_Final, CURRENT_DATE), Fecha_Creacion)), 0) as duracion_promedio
                    FROM TB_Trabajos t";
    
    if (!empty($conditions)) {
        $stats_query .= " LEFT JOIN TB_Clientes c ON t.Cedula_Cliente = c.Cedula WHERE " . implode(" AND ", $conditions);
        $stmt_stats = $db->prepare($stats_query);
        foreach ($params as $key => $value) {
            $stmt_stats->bindValue($key, $value);
        }
        $stmt_stats->execute();
    } else {
        $stmt_stats = $db->query($stats_query);
    }
    
    $stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);
    
    // Asegurar que stats tenga valores por defecto si hay error
    if (!$stats) {
        $stats = [
            'total_trabajos' => 0,
            'pendientes' => 0,
            'en_proceso' => 0,
            'completados' => 0,
            'ingresos_totales' => 0,
            'precio_promedio' => 0,
            'duracion_promedio' => 0
        ];
    }
    
    // Si se solicita exportación a CSV
    if ($exportar === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=reporte_trabajos_' . date('Y-m-d') . '.csv');
        
        $output = fopen('php://output', 'w');
        
        // BOM para UTF-8
        fwrite($output, "\xEF\xBB\xBF");
        
        // Encabezados (sin espacios y con guiones bajos para compatibilidad)
        fputcsv($output, [
            'ID_Trabajo', 'Cliente', 'Cedula_Cliente', 'Empresa', 'Tipo_Trabajo', 'Descripcion', 
            'Estado', 'Fecha_Creacion', 'Fecha_Inicio', 'Fecha_Final', 'Dias_Duracion', 'Precio_Mano_Obra', 'Precio_Total'
        ]);
        
        foreach ($trabajos as $trabajo) {
            $row = [
                $trabajo['ID_Trabajo'],
                preg_replace("/[\r\n]+/", ' ', $trabajo['nombre_cliente']),
                $trabajo['cedula_cliente'],
                preg_replace("/[\r\n]+/", ' ', ($trabajo['Empresa'] ?: '')),
                preg_replace("/[\r\n]+/", ' ', $trabajo['Tipo_Trabajo']),
                preg_replace("/[\r\n]+/", ' ', $trabajo['Descripcion']),
                $trabajo['Estado'],
                date('Y-m-d', strtotime($trabajo['Fecha_Creacion'])),
                $trabajo['Fecha_Inicio'] ? date('Y-m-d', strtotime($trabajo['Fecha_Inicio'])) : '',
                $trabajo['Fecha_Final'] ? date('Y-m-d', strtotime($trabajo['Fecha_Final'])) : '',
                (int)$trabajo['dias_duracion'],
                number_format((float)($trabajo['Precio_Mano_Obra'] ?? 0), 2, '.', ''),
                number_format((float)($trabajo['Precio_Total'] ?? 0), 2, '.', '')
            ];
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit;
    }
    
} catch (Exception $e) {
    $error = "Error al generar el reporte: " . $e->getMessage();
    // Inicializar variables por defecto en caso de error
    $trabajos = [];
    $stats = [
        'total_trabajos' => 0,
        'pendientes' => 0,
        'en_proceso' => 0,
        'completados' => 0,
        'ingresos_totales' => 0,
        'precio_promedio' => 0,
        'duracion_promedio' => 0
    ];
}

logActivity($_SESSION['user_id'], "Generó reporte de trabajos");
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-briefcase"></i> Reporte de Trabajos
        </h1>
        <div class="btn-group">
            <a href="?exportar=csv<?php 
                $params = [];
                if (!empty($filtro_fecha_inicio)) $params[] = 'fecha_inicio=' . $filtro_fecha_inicio;
                if (!empty($filtro_fecha_fin)) $params[] = 'fecha_fin=' . $filtro_fecha_fin;
                if (!empty($filtro_estado)) $params[] = 'estado=' . urlencode($filtro_estado);
                if (!empty($filtro_cliente)) $params[] = 'cliente=' . urlencode($filtro_cliente);
                echo !empty($params) ? '&' . implode('&', $params) : '';
            ?>" class="btn btn-success btn-sm">
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
                                Trabajos Completados
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($stats['completados'] ?? 0); ?>
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
                                Ingresos Totales
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                $<?php echo number_format($stats['ingresos_totales'], 2); ?>
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
                                Duración Promedio
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($stats['duracion_promedio'], 1); ?> días
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
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
                <div class="col-md-3">
                    <label for="fecha_inicio" class="form-label">Fecha inicio:</label>
                    <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" 
                           value="<?php echo htmlspecialchars($filtro_fecha_inicio); ?>">
                </div>
                <div class="col-md-3">
                    <label for="fecha_fin" class="form-label">Fecha fin:</label>
                    <input type="date" class="form-control" id="fecha_fin" name="fecha_fin" 
                           value="<?php echo htmlspecialchars($filtro_fecha_fin); ?>">
                </div>
                <div class="col-md-2">
                    <label for="estado" class="form-label">Estado:</label>
                    <select class="form-control" id="estado" name="estado">
                        <option value="">Todos</option>
                        <option value="Pendiente" <?php echo $filtro_estado === 'Pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                        <option value="En Proceso" <?php echo $filtro_estado === 'En Proceso' ? 'selected' : ''; ?>>En Proceso</option>
                        <option value="Completado" <?php echo $filtro_estado === 'Completado' ? 'selected' : ''; ?>>Completado</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="cliente" class="form-label">Cliente:</label>
                    <input type="text" class="form-control" id="cliente" name="cliente" 
                           placeholder="Buscar..." 
                           value="<?php echo htmlspecialchars($filtro_cliente); ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-search"></i> Filtrar
                    </button>
                    <a href="trabajos.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Limpiar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Gráfico de estado de trabajos -->
    <div class="row mb-4">
        <div class="col-lg-6">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-pie"></i> Distribución por Estado
                    </h6>
                </div>
                <div class="card-body">
                    <canvas id="estadoChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-bar"></i> Resumen de Estados
                    </h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <span class="badge bg-warning me-2">Pendientes</span>
                        <strong><?php echo $stats['pendientes']; ?></strong>
                        <span class="text-muted">
                            (<?php echo $stats['total_trabajos'] > 0 ? number_format(($stats['pendientes'] / $stats['total_trabajos']) * 100, 1) : 0; ?>%)
                        </span>
                    </div>
                    <div class="mb-3">
                        <span class="badge bg-info me-2">En Proceso</span>
                        <strong><?php echo $stats['en_proceso']; ?></strong>
                        <span class="text-muted">
                            (<?php echo $stats['total_trabajos'] > 0 ? number_format(($stats['en_proceso'] / $stats['total_trabajos']) * 100, 1) : 0; ?>%)
                        </span>
                    </div>
                    <div class="mb-3">
                        <span class="badge bg-success me-2">Completados</span>
                        <strong><?php echo $stats['completados']; ?></strong>
                        <span class="text-muted">
                            (<?php echo $stats['total_trabajos'] > 0 ? number_format(($stats['completados'] / $stats['total_trabajos']) * 100, 1) : 0; ?>%)
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de resultados -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-table"></i> Reporte Detallado de Trabajos
                <span class="badge bg-info text-white ms-2"><?php echo count($trabajos); ?> registros</span>
            </h6>
        </div>
        <div class="card-body">
            <?php if (empty($trabajos)): ?>
                <div class="text-center">
                    <i class="fas fa-info-circle text-muted fa-3x mb-3"></i>
                    <p class="text-muted">No se encontraron trabajos con los criterios especificados.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="dataTable">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Cliente</th>
                                <th>Tipo Trabajo</th>
                                <th>Descripción</th>
                                <th>Estado</th>
                                <th>Fecha Creación</th>
                                <th>Fecha Inicio</th>
                                <th>Fecha Final</th>
                                <th>Duración</th>
                                <th>Precio</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($trabajos as $trabajo): ?>
                                <tr>
                                    <td><strong>#<?php echo $trabajo['ID_Trabajo']; ?></strong></td>
                                    <td>
                                        <div>
                                            <strong><?php echo htmlspecialchars($trabajo['nombre_cliente']); ?></strong>
                                            <br>
                                            <small class="text-muted"><?php echo htmlspecialchars($trabajo['cedula_cliente']); ?></small>
                                        </div>
                                    </td>
                                    <td><strong><?php echo htmlspecialchars($trabajo['Tipo_Trabajo']); ?></strong></td>
                                    <td>
                                        <div style="max-width: 200px;">
                                            <?php echo htmlspecialchars(substr($trabajo['Descripcion'], 0, 100)); ?>
                                            <?php if (strlen($trabajo['Descripcion']) > 100): ?>...<?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php
                                        $badge_class = 'bg-secondary';
                                        switch ($trabajo['Estado']) {
                                            case 'Pendiente': $badge_class = 'bg-warning'; break;
                                            case 'En Proceso': $badge_class = 'bg-info'; break;
                                            case 'Completado': $badge_class = 'bg-success'; break;
                                        }
                                        ?>
                                        <span class="badge <?php echo $badge_class; ?>">
                                            <?php echo $trabajo['Estado']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($trabajo['Fecha_Creacion'])); ?></td>
                                    <td>
                                        <?php 
                                        if ($trabajo['Fecha_Inicio']) {
                                            echo date('d/m/Y', strtotime($trabajo['Fecha_Inicio']));
                                        } else {
                                            echo '<span class="text-muted">No definida</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                        if ($trabajo['Fecha_Final']) {
                                            echo date('d/m/Y', strtotime($trabajo['Fecha_Final']));
                                        } else {
                                            echo '<span class="text-muted">No finalizado</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark">
                                            <?php echo $trabajo['dias_duracion']; ?> días
                                        </span>
                                    </td>
                                    <td>
                                        <strong class="text-success">
                                            $<?php echo number_format($trabajo['Precio_Total'] ?? 0, 2); ?>
                                        </strong>
                                    </td>
                                    <td>
                                        <a href="../trabajos/view.php?id=<?php echo $trabajo['ID_Trabajo']; ?>" 
                                           class="btn btn-sm btn-outline-primary" title="Ver detalles">
                                            <i class="fas fa-eye"></i>
                                        </a>
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

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// DataTable initialization
$(document).ready(function() {
    $('#dataTable').DataTable({
        "pageLength": 25,
        "order": [[4, "desc"]], // Ordenar por fecha de creación
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Spanish.json"
        }
    });
    
    // Gráfico de estados
    const ctx = document.getElementById('estadoChart').getContext('2d');
    const estadoChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Pendientes', 'En Proceso', 'Completados'],
            datasets: [{
                data: [
                    <?php echo $stats['pendientes']; ?>,
                    <?php echo $stats['en_proceso']; ?>,
                    <?php echo $stats['completados']; ?>
                ],
                backgroundColor: [
                    '#ffc107',
                    '#17a2b8',
                    '#28a745'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
});
</script>

<?php include '../../includes/footer.php'; ?>
