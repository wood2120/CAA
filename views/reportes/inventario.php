<?php
require_once '../../includes/functions.php';
require_once '../../config/database.php';
require_once '../../models/Inventario.php';
require_once '../../models/Categoria.php';
require_once '../../models/Proveedor.php';

requireLogin();
checkSessionTimeout();

$pageTitle = 'Reporte de Inventario';
include '../../includes/header.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $inventarioModel = new Inventario($db);
    
    // Obtener filtros
    $filtro_categoria = $_GET['categoria'] ?? '';
    $filtro_proveedor = $_GET['proveedor'] ?? '';
    $filtro_estado = $_GET['estado'] ?? '';
    $filtro_stock_bajo = $_GET['stock_bajo'] ?? '';
    $exportar = $_GET['exportar'] ?? '';
    
    // Consulta base para inventario
    $query = "SELECT 
                i.ID_Inventario as ID_Item,
                i.Nombre as Nombre_Item,
                i.Descripcion,
                i.Cantidad_Stock,
                i.Stock_Minimo,
                i.Precio_Unitario,
                i.Estado,
                i.Fecha_Ingreso as Fecha_Ultima_Actualizacion,
                c.Nombre_Categoria as categoria_nombre,
                p.Nombre_Proveedor as proveedor_nombre,
                (i.Cantidad_Stock * i.Precio_Unitario) as valor_total,
                CASE 
                    WHEN i.Cantidad_Stock <= i.Stock_Minimo THEN 'Crítico'
                    WHEN i.Cantidad_Stock <= (i.Stock_Minimo * 1.5) THEN 'Bajo'
                    ELSE 'Normal'
                END as nivel_stock
              FROM TB_Inventario i
              LEFT JOIN TB_Categorias c ON i.ID_Categoria = c.ID_Categoria
              LEFT JOIN TB_Proveedores p ON i.ID_Proveedor = p.ID_Proveedor";
    
    $conditions = [];
    $params = [];
    
    if (!empty($filtro_categoria)) {
        $conditions[] = "i.ID_Categoria = :categoria";
        $params[':categoria'] = $filtro_categoria;
    }
    
    if (!empty($filtro_proveedor)) {
        $conditions[] = "i.ID_Proveedor = :proveedor";
        $params[':proveedor'] = $filtro_proveedor;
    }
    
    if (!empty($filtro_estado)) {
        $conditions[] = "i.Estado = :estado";
        $params[':estado'] = $filtro_estado;
    }
    
    if ($filtro_stock_bajo === '1') {
        $conditions[] = "i.Cantidad_Stock <= i.Stock_Minimo";
    }
    
    if (!empty($conditions)) {
        $query .= " WHERE " . implode(" AND ", $conditions);
    }
    
    $query .= " ORDER BY i.Nombre";
    
    $stmt = $db->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Estadísticas generales
    $stats_query = "SELECT 
                      COUNT(*) as total_items,
                      SUM(CASE WHEN Estado = 'Activo' THEN 1 ELSE 0 END) as items_activos,
                      SUM(CASE WHEN Cantidad_Stock <= Stock_Minimo THEN 1 ELSE 0 END) as stock_critico,
                      SUM(CASE WHEN Cantidad_Stock <= Stock_Minimo * 1.5 AND Cantidad_Stock > Stock_Minimo THEN 1 ELSE 0 END) as stock_bajo,
                      SUM(Cantidad_Stock * Precio_Unitario) as valor_total_inventario,
                      AVG(Precio_Unitario) as precio_promedio
                    FROM TB_Inventario i";
    
    if (!empty($conditions)) {
        $stats_query .= " LEFT JOIN TB_Categorias c ON i.ID_Categoria = c.ID_Categoria";
        $stats_query .= " LEFT JOIN TB_Proveedores p ON i.ID_Proveedor = p.ID_Proveedor";
        $stats_query .= " WHERE " . implode(" AND ", $conditions);
        $stmt_stats = $db->prepare($stats_query);
        foreach ($params as $key => $value) {
            $stmt_stats->bindValue($key, $value);
        }
        $stmt_stats->execute();
    } else {
        $stmt_stats = $db->query($stats_query);
    }
    
    $stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);
    
    // Obtener categorías y proveedores para filtros
    $categorias = $db->query("SELECT ID_Categoria, Nombre_Categoria as Nombre FROM TB_Categorias ORDER BY Nombre_Categoria")->fetchAll(PDO::FETCH_ASSOC);
    $proveedores = $db->query("SELECT ID_Proveedor, Nombre_Proveedor as Nombre FROM TB_Proveedores ORDER BY Nombre_Proveedor")->fetchAll(PDO::FETCH_ASSOC);
    
    // Si se solicita exportación a CSV
    if ($exportar === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=reporte_inventario_' . date('Y-m-d') . '.csv');
        
        $output = fopen('php://output', 'w');
        
        // BOM para UTF-8
        fwrite($output, "\xEF\xBB\xBF");
        
        // Encabezados
        fputcsv($output, [
            'ID Item', 'Nombre', 'Descripción', 'Categoría', 'Proveedor', 
            'Stock Actual', 'Stock Mínimo', 'Nivel Stock', 'Precio Unitario', 
            'Valor Total', 'Estado', 'Fecha Ingreso'
        ]);
        
        // Datos
        foreach ($items as $item) {
            fputcsv($output, [
                $item['ID_Item'],
                $item['Nombre_Item'],
                $item['Descripcion'],
                $item['categoria_nombre'],
                $item['proveedor_nombre'],
                $item['Cantidad_Stock'],
                $item['Stock_Minimo'],
                $item['nivel_stock'],
                number_format($item['Precio_Unitario'], 2),
                number_format($item['valor_total'], 2),
                $item['Estado'],
                date('d/m/Y', strtotime($item['Fecha_Ultima_Actualizacion']))
            ]);
        }
        
        fclose($output);
        exit;
    }
    
} catch (Exception $e) {
    $error = "Error al generar el reporte: " . $e->getMessage();
    // Inicializar variables por defecto en caso de error
    $items = [];
    $stats = [
        'total_items' => 0,
        'items_activos' => 0,
        'stock_critico' => 0,
        'stock_bajo' => 0,
        'valor_total_inventario' => 0,
        'precio_promedio' => 0
    ];
    $categorias = [];
    $proveedores = [];
}

logActivity($_SESSION['user_id'], "Generó reporte de inventario");
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-boxes"></i> Reporte de Inventario
        </h1>
        <div class="btn-group">
            <a href="?exportar=csv<?php 
                $params = [];
                if (!empty($filtro_categoria)) $params[] = 'categoria=' . $filtro_categoria;
                if (!empty($filtro_proveedor)) $params[] = 'proveedor=' . $filtro_proveedor;
                if (!empty($filtro_estado)) $params[] = 'estado=' . urlencode($filtro_estado);
                if (!empty($filtro_stock_bajo)) $params[] = 'stock_bajo=' . $filtro_stock_bajo;
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
                                Total Items
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($stats['total_items']); ?>
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
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                Stock Crítico
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($stats['stock_critico']); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
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
                                Valor Total Inventario
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                $<?php echo number_format($stats['valor_total_inventario'], 2); ?>
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
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Items Activos
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($stats['items_activos']); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
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
                    <label for="categoria" class="form-label">Categoría:</label>
                    <select class="form-control" id="categoria" name="categoria">
                        <option value="">Todas las categorías</option>
                        <?php foreach ($categorias as $categoria): ?>
                            <option value="<?php echo $categoria['ID_Categoria']; ?>" 
                                    <?php echo $filtro_categoria == $categoria['ID_Categoria'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($categoria['Nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="proveedor" class="form-label">Proveedor:</label>
                    <select class="form-control" id="proveedor" name="proveedor">
                        <option value="">Todos los proveedores</option>
                        <?php foreach ($proveedores as $proveedor): ?>
                            <option value="<?php echo $proveedor['ID_Proveedor']; ?>" 
                                    <?php echo $filtro_proveedor == $proveedor['ID_Proveedor'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($proveedor['Nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="estado" class="form-label">Estado:</label>
                    <select class="form-control" id="estado" name="estado">
                        <option value="">Todos</option>
                        <option value="Activo" <?php echo $filtro_estado === 'Activo' ? 'selected' : ''; ?>>Activo</option>
                        <option value="Inactivo" <?php echo $filtro_estado === 'Inactivo' ? 'selected' : ''; ?>>Inactivo</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="stock_bajo" class="form-label">Solo stock bajo:</label>
                    <select class="form-control" id="stock_bajo" name="stock_bajo">
                        <option value="">No</option>
                        <option value="1" <?php echo $filtro_stock_bajo === '1' ? 'selected' : ''; ?>>Sí</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-search"></i> Filtrar
                    </button>
                    <a href="inventario.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Limpiar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Alerta de stock bajo -->
    <?php if ($stats['stock_critico'] > 0): ?>
        <div class="alert alert-warning">
            <h4><i class="fas fa-exclamation-triangle"></i> Alerta de Stock</h4>
            <p>Hay <strong><?php echo $stats['stock_critico']; ?></strong> item(s) con stock crítico que requieren atención inmediata.</p>
            <a href="?stock_bajo=1" class="btn btn-warning btn-sm">
                <i class="fas fa-eye"></i> Ver items con stock bajo
            </a>
        </div>
    <?php endif; ?>

    <!-- Gráficos -->
    <div class="row mb-4">
        <div class="col-lg-6">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-pie"></i> Distribución por Nivel de Stock
                    </h6>
                </div>
                <div class="card-body">
                    <canvas id="stockChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-bar"></i> Top 10 Items por Valor
                    </h6>
                </div>
                <div class="card-body">
                    <canvas id="valorChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de resultados -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-table"></i> Reporte Detallado de Inventario
                <span class="badge bg-info text-white ms-2"><?php echo count($items); ?> registros</span>
            </h6>
        </div>
        <div class="card-body">
            <?php if (empty($items)): ?>
                <div class="text-center">
                    <i class="fas fa-info-circle text-muted fa-3x mb-3"></i>
                    <p class="text-muted">No se encontraron items con los criterios especificados.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="dataTable">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Categoría</th>
                                <th>Proveedor</th>
                                <th>Stock Actual</th>
                                <th>Stock Mínimo</th>
                                <th>Nivel</th>
                                <th>Precio Unit.</th>
                                <th>Valor Total</th>
                                <th>Estado</th>
                                <th>Actualizado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td><strong>#<?php echo $item['ID_Item']; ?></strong></td>
                                    <td>
                                        <div>
                                            <strong><?php echo htmlspecialchars($item['Nombre_Item']); ?></strong>
                                            <?php if ($item['Descripcion']): ?>
                                                <br><small class="text-muted"><?php echo htmlspecialchars(substr($item['Descripcion'], 0, 50)); ?>...</small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($item['categoria_nombre'] ?: 'Sin categoría'); ?></td>
                                    <td><?php echo htmlspecialchars($item['proveedor_nombre'] ?: 'Sin proveedor'); ?></td>
                                    <td>
                                        <strong><?php echo number_format($item['Cantidad_Stock']); ?></strong>
                                    </td>
                                    <td><?php echo number_format($item['Stock_Minimo']); ?></td>
                                    <td>
                                        <?php
                                        $nivel_class = 'bg-success';
                                        if ($item['nivel_stock'] === 'Crítico') $nivel_class = 'bg-danger';
                                        elseif ($item['nivel_stock'] === 'Bajo') $nivel_class = 'bg-warning';
                                        ?>
                                        <span class="badge <?php echo $nivel_class; ?>">
                                            <?php echo $item['nivel_stock']; ?>
                                        </span>
                                    </td>
                                    <td>$<?php echo number_format($item['Precio_Unitario'], 2); ?></td>
                                    <td>
                                        <strong class="text-success">
                                            $<?php echo number_format($item['valor_total'], 2); ?>
                                        </strong>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $item['Estado'] === 'Activo' ? 'bg-success' : 'bg-secondary'; ?>">
                                            <?php echo $item['Estado']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($item['Fecha_Ultima_Actualizacion'])); ?></td>
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
        "order": [[8, "desc"]], // Ordenar por valor total
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Spanish.json"
        }
    });
    
    // Contar niveles de stock
    const stockNormal = <?php echo count(array_filter($items, function($item) { return $item['nivel_stock'] === 'Normal'; })); ?>;
    const stockBajo = <?php echo count(array_filter($items, function($item) { return $item['nivel_stock'] === 'Bajo'; })); ?>;
    const stockCritico = <?php echo count(array_filter($items, function($item) { return $item['nivel_stock'] === 'Crítico'; })); ?>;
    
    // Gráfico de niveles de stock
    const ctxStock = document.getElementById('stockChart').getContext('2d');
    const stockChart = new Chart(ctxStock, {
        type: 'doughnut',
        data: {
            labels: ['Normal', 'Bajo', 'Crítico'],
            datasets: [{
                data: [stockNormal, stockBajo, stockCritico],
                backgroundColor: ['#28a745', '#ffc107', '#dc3545'],
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
    
    // Top 10 items por valor - preparar datos
    const topItems = <?php 
        $top10 = array_slice($items, 0, 10);
        echo json_encode(array_map(function($item) {
            return [
                'nombre' => substr($item['Nombre_Item'], 0, 20) . (strlen($item['Nombre_Item']) > 20 ? '...' : ''),
                'valor' => floatval($item['valor_total'])
            ];
        }, $top10));
    ?>;
    
    // Gráfico de valor
    const ctxValor = document.getElementById('valorChart').getContext('2d');
    const valorChart = new Chart(ctxValor, {
        type: 'bar',
        data: {
            labels: topItems.map(item => item.nombre),
            datasets: [{
                label: 'Valor Total ($)',
                data: topItems.map(item => item.valor),
                backgroundColor: '#007bff',
                borderColor: '#0056b3',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '$' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });
});
</script>

<?php include '../../includes/footer.php'; ?>
