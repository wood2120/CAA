<?php
require_once 'includes/functions.php';
// Incluir database.php explícitamente por seguridad
require_once 'config/database.php';
require_once __DIR__ . '/models/Trabajo.php';
require_once __DIR__ . '/models/Cliente.php';
require_once __DIR__ . '/models/Inventario.php';

// Verificar autenticación
requireLogin();
checkSessionTimeout();

$pageTitle = 'Dashboard';
include 'includes/header.php';

// Obtener estadísticas generales
try {
    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        throw new Exception("No se pudo conectar a la base de datos");
    }

    $trabajoModel = new Trabajo($db);
    $inventarioModel = new Inventario($db);
    $clienteModel = new Cliente($db);

    // Obtener conteos básicos con manejo de errores
    try {
        $totalClientes = $clienteModel->readAll()->rowCount();
    } catch (Exception $e) {
        $totalClientes = 0;
        error_log("Error al obtener clientes: " . $e->getMessage());
    }

    try {
        $totalTrabajos = $trabajoModel->readAll()->rowCount();
    } catch (Exception $e) {
        $totalTrabajos = 0;
        error_log("Error al obtener trabajos: " . $e->getMessage());
    }

    try {
        $totalInventario = $inventarioModel->readAll()->rowCount();
    } catch (Exception $e) {
        $totalInventario = 0;
        error_log("Error al obtener inventario: " . $e->getMessage());
    }

    // Obtener estadísticas de trabajos
    try {
        $estadisticasTrabajos = $trabajoModel->getEstadisticas();
    } catch (Exception $e) {
        $estadisticasTrabajos = [
            'total' => 0,
            'pendientes' => 0,
            'en_proceso' => 0,
            'completados' => 0,
            'cancelados' => 0,
            'promedio_costo' => 0,
            'total_ingresos' => 0
        ];
        error_log("Error al obtener estadísticas de trabajos: " . $e->getMessage());
    }
    
    // Obtener estadísticas de inventario
    try {
        $estadisticasInventario = $inventarioModel->getEstadisticas();
    } catch (Exception $e) {
        $estadisticasInventario = [
            'total_items' => 0,
            'valor_total' => 0,
            'items_bajo_stock' => 0
        ];
        error_log("Error al obtener estadísticas de inventario: " . $e->getMessage());
    }

    // Obtener trabajos recientes
    try {
        $trabajosRecientesStmt = $trabajoModel->getTrabajosRecientes(5);
        $trabajosRecientesArray = [];
        while ($row = $trabajosRecientesStmt->fetch(PDO::FETCH_ASSOC)) {
            $trabajosRecientesArray[] = $row;
        }
    } catch (Exception $e) {
        $trabajosRecientesArray = [];
        error_log("Error al obtener trabajos recientes: " . $e->getMessage());
    }

    // Obtener stock bajo
    try {
        $stockBajo = $inventarioModel->getStockBajo(10);
    } catch (Exception $e) {
        $stockBajo = null;
        error_log("Error al obtener stock bajo: " . $e->getMessage());
    }

} catch (Exception $e) {
    $error = "Error al cargar datos del dashboard: " . $e->getMessage();
    // Inicializar variables por defecto
    $totalClientes = 0;
    $totalTrabajos = 0;
    $totalInventario = 0;
    $estadisticasTrabajos = [
        'total' => 0,
        'pendientes' => 0,
        'en_proceso' => 0,
        'completados' => 0,
        'cancelados' => 0,
        'promedio_costo' => 0,
        'total_ingresos' => 0
    ];
    $estadisticasInventario = [
        'total_items' => 0,
        'valor_total' => 0,
        'items_bajo_stock' => 0
    ];
    $trabajosRecientesArray = [];
    $stockBajo = null;
}

// Registrar actividad
logActivity($_SESSION['user_id'], "Acceso al dashboard");
?>

<div class="container-fluid">
    <!-- Encabezado del Dashboard -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">Dashboard</h1>
                    <p class="text-muted">Bienvenido, <?php echo $_SESSION['username']; ?></p>
                </div>
                <div class="text-end">
                    <small class="text-muted">
                        <i class="fas fa-clock"></i> <?php echo date('d/m/Y H:i:s'); ?>
                    </small>
                </div>
            </div>
        </div>
    </div>

    <?php if (isset($error)): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
    </div>
    <?php endif; ?>

    <!-- Tarjetas de estadísticas -->
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
                                <?php echo number_format($totalClientes); ?>
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
                                Total Trabajos
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($totalTrabajos ?? 0); ?>
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
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Ingresos Totales
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo formatCurrency($estadisticasTrabajos['total_ingresos'] ?? 0); ?>
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
                                Items Inventario
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($totalInventario ?? 0); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-boxes fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Trabajos Recientes -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Trabajos Recientes</h6>
                    <a href="views/trabajos/index.php" class="btn btn-sm btn-primary">Ver Todos</a>
                </div>
                <div class="card-body">
                    <?php if (!empty($trabajosRecientesArray)): ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Cliente</th>
                                    <th>Trabajo</th>
                                    <th>Precio</th>
                                    <th>Fecha</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($trabajosRecientesArray as $trabajo): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($trabajo['NombreCliente'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($trabajo['Tipo_Trabajo'] ?? 'N/A'); ?></td>
                                    <td><?php echo formatCurrency($trabajo['Precio_Total'] ?? 0); ?></td>
                                    <td><?php echo formatDate($trabajo['Fecha_Inicio'] ?? ''); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <p class="text-muted text-center">No hay trabajos registrados.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Stock Bajo -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-warning">Inventario - Stock Bajo</h6>
                    <a href="views/inventario/index.php" class="btn btn-sm btn-warning">Ver Inventario</a>
                </div>
                <div class="card-body">
                    <?php 
                    $stockBajoArray = [];
                    if ($stockBajo && is_object($stockBajo)) {
                        try {
                            while ($row = $stockBajo->fetch(PDO::FETCH_ASSOC)) {
                                $stockBajoArray[] = $row;
                            }
                        } catch (Exception $e) {
                            error_log("Error al procesar stock bajo: " . $e->getMessage());
                        }
                    }
                    ?>
                    <?php if (!empty($stockBajoArray)): ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Stock Actual</th>
                                    <th>Stock Mínimo</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($stockBajoArray as $item): ?>
                                <tr class="<?php echo $item['Cantidad_Stock'] <= 2 ? 'table-danger' : 'table-warning'; ?>">
                                    <td><?php echo htmlspecialchars($item['Nombre'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span class="badge <?php echo $item['Cantidad_Stock'] <= 2 ? 'bg-danger' : 'bg-warning'; ?>">
                                            <?php echo number_format($item['Cantidad_Stock'] ?? 0); ?>
                                        </span>
                                    </td>
                                    <td><?php echo number_format($item['Stock_Minimo'] ?? 0); ?></td>
                                    <td>
                                        <span class="badge <?php echo $item['Estado'] == 'Agotado' ? 'bg-danger' : 'bg-warning'; ?>">
                                            <?php echo htmlspecialchars($item['Estado'] ?? 'N/A'); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <p class="text-muted text-center">
                        <i class="fas fa-check text-success"></i> Todos los items tienen stock adecuado.
                    </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Accesos Rápidos -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Accesos Rápidos</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <a href="views/clientes/create.php" class="btn btn-outline-primary btn-block h-100">
                                <i class="fas fa-user-plus fa-2x mb-2"></i><br>
                                Nuevo Cliente
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="views/trabajos/create.php" class="btn btn-outline-success btn-block h-100">
                                <i class="fas fa-plus-circle fa-2x mb-2"></i><br>
                                Nuevo Trabajo
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="views/inventario/create.php" class="btn btn-outline-info btn-block h-100">
                                <i class="fas fa-box fa-2x mb-2"></i><br>
                                Agregar Inventario
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="views/reportes/" class="btn btn-outline-warning btn-block h-100">
                                <i class="fas fa-chart-bar fa-2x mb-2"></i><br>
                                Ver Reportes
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.border-left-primary {
    border-left: 0.25rem solid #4e73df !important;
}
.border-left-success {
    border-left: 0.25rem solid #1cc88a !important;
}
.border-left-info {
    border-left: 0.25rem solid #36b9cc !important;
}
.border-left-warning {
    border-left: 0.25rem solid #f6c23e !important;
}
.btn-block {
    display: block;
    width: 100%;
}
</style>

<?php include 'includes/footer.php'; ?>
