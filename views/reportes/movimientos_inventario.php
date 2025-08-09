<?php
require_once '../../includes/functions.php';

requireLogin();
checkSessionTimeout();

$pageTitle = 'Movimientos de Inventario';
include '../../includes/header.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Filtros
    $tipo_movimiento = isset($_GET['tipo']) ? sanitizeInput($_GET['tipo']) : '';
    $fecha_desde = isset($_GET['fecha_desde']) ? $_GET['fecha_desde'] : '';
    $fecha_hasta = isset($_GET['fecha_hasta']) ? $_GET['fecha_hasta'] : '';
    $limite = isset($_GET['limite']) ? (int)$_GET['limite'] : 100;
    
    // Construir consulta con filtros
    $query = "SELECT 
                m.ID_Movimiento,
                m.Tipo_Movimiento,
                m.Cantidad,
                m.Motivo,
                m.Fecha_Movimiento,
                i.Nombre as item_nombre,
                i.Unidad_Medida,
                c.Nombre_Categoria,
                c.Tipo as tipo_categoria,
                u.Usuario,
                t.Tipo_Trabajo
              FROM TB_Movimientos_Inventario m
              LEFT JOIN TB_Inventario i ON m.ID_Inventario = i.ID_Inventario
              LEFT JOIN TB_Categorias c ON i.ID_Categoria = c.ID_Categoria
              LEFT JOIN TB_Usuarios u ON m.ID_Usuario = u.ID_Usuario
              LEFT JOIN TB_Trabajos t ON m.ID_Trabajo = t.ID_Trabajo
              WHERE 1=1";
    
    $params = [];
    
    if (!empty($tipo_movimiento)) {
        $query .= " AND m.Tipo_Movimiento = :tipo_movimiento";
        $params[':tipo_movimiento'] = $tipo_movimiento;
    }
    
    if (!empty($fecha_desde)) {
        $query .= " AND DATE(m.Fecha_Movimiento) >= :fecha_desde";
        $params[':fecha_desde'] = $fecha_desde;
    }
    
    if (!empty($fecha_hasta)) {
        $query .= " AND DATE(m.Fecha_Movimiento) <= :fecha_hasta";
        $params[':fecha_hasta'] = $fecha_hasta;
    }
    
    $query .= " ORDER BY m.Fecha_Movimiento DESC LIMIT :limite";
    
    $stmt = $db->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
    $stmt->execute();
    
} catch (Exception $e) {
    $error = "Error al cargar movimientos: " . $e->getMessage();
}

logActivity($_SESSION['user_id'], "Acceso a movimientos de inventario");
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-exchange-alt"></i> Movimientos de Inventario
        </h1>
        <div>
            <a href="index.php" class="btn btn-secondary me-2">
                <i class="fas fa-arrow-left"></i> Volver a Reportes
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
            <h6 class="m-0 font-weight-bold text-primary">Filtros</h6>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="tipo" class="form-label">Tipo de Movimiento</label>
                    <select class="form-select" id="tipo" name="tipo">
                        <option value="">Todos los tipos</option>
                        <option value="Entrada" <?php echo ($tipo_movimiento == 'Entrada') ? 'selected' : ''; ?>>Entrada</option>
                        <option value="Salida" <?php echo ($tipo_movimiento == 'Salida') ? 'selected' : ''; ?>>Salida</option>
                        <option value="Ajuste" <?php echo ($tipo_movimiento == 'Ajuste') ? 'selected' : ''; ?>>Ajuste</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="fecha_desde" class="form-label">Fecha Desde</label>
                    <input type="date" class="form-control" id="fecha_desde" name="fecha_desde" 
                           value="<?php echo htmlspecialchars($fecha_desde); ?>">
                </div>
                <div class="col-md-3">
                    <label for="fecha_hasta" class="form-label">Fecha Hasta</label>
                    <input type="date" class="form-control" id="fecha_hasta" name="fecha_hasta" 
                           value="<?php echo htmlspecialchars($fecha_hasta); ?>">
                </div>
                <div class="col-md-3">
                    <label for="limite" class="form-label">Registros</label>
                    <select class="form-select" id="limite" name="limite">
                        <option value="50" <?php echo ($limite == 50) ? 'selected' : ''; ?>>50</option>
                        <option value="100" <?php echo ($limite == 100) ? 'selected' : ''; ?>>100</option>
                        <option value="200" <?php echo ($limite == 200) ? 'selected' : ''; ?>>200</option>
                        <option value="500" <?php echo ($limite == 500) ? 'selected' : ''; ?>>500</option>
                    </select>
                </div>
                <div class="col-12">
                    <div class="d-grid gap-2 d-md-flex">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i> Filtrar
                        </button>
                        <a href="movimientos_inventario.php" class="btn btn-outline-secondary">
                            <i class="fas fa-times"></i> Limpiar
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                Historial de Movimientos
                <?php if (!empty($tipo_movimiento) || !empty($fecha_desde) || !empty($fecha_hasta)): ?>
                    - Con filtros aplicados
                <?php endif; ?>
                (Últimos <?php echo $limite; ?> registros)
            </h6>
        </div>
        <div class="card-body">
            <?php if (isset($stmt) && $stmt->rowCount() > 0): ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Fecha/Hora</th>
                            <th>Tipo</th>
                            <th>Item</th>
                            <th>Categoría</th>
                            <th>Cantidad</th>
                            <th>Motivo</th>
                            <th>Usuario</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['ID_Movimiento']); ?></td>
                            <td>
                                <strong><?php echo date('d/m/Y', strtotime($row['Fecha_Movimiento'])); ?></strong>
                                <br>
                                <small class="text-muted"><?php echo date('H:i:s', strtotime($row['Fecha_Movimiento'])); ?></small>
                            </td>
                            <td>
                                <span class="badge <?php 
                                    echo match($row['Tipo_Movimiento']) {
                                        'Entrada' => 'bg-success',
                                        'Salida' => 'bg-danger',
                                        'Ajuste' => 'bg-warning',
                                        default => 'bg-secondary'
                                    }; ?>">
                                    <?php echo htmlspecialchars($row['Tipo_Movimiento']); ?>
                                </span>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($row['item_nombre']); ?></strong>
                                <br>
                                <small class="text-muted"><?php echo htmlspecialchars($row['Unidad_Medida']); ?></small>
                            </td>
                            <td>
                                <span class="badge <?php echo $row['tipo_categoria'] == 'Material' ? 'bg-info' : 'bg-warning'; ?>">
                                    <?php echo htmlspecialchars($row['Nombre_Categoria']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="fw-bold <?php echo $row['Tipo_Movimiento'] == 'Entrada' ? 'text-success' : 'text-danger'; ?>">
                                    <?php echo $row['Tipo_Movimiento'] == 'Entrada' ? '+' : '-'; ?>
                                    <?php echo number_format($row['Cantidad']); ?>
                                </span>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($row['Motivo']); ?>
                                <?php if (!empty($row['Tipo_Trabajo'])): ?>
                                    <br><small class="text-muted">Trabajo: <?php echo htmlspecialchars($row['Tipo_Trabajo']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-secondary">
                                    <?php echo htmlspecialchars($row['Usuario'] ?? 'Sistema'); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="mt-3">
                <small class="text-muted">
                    <i class="fas fa-info-circle"></i> 
                    Mostrando los últimos <?php echo $limite; ?> movimientos de inventario.
                </small>
            </div>
            
            <?php else: ?>
            <div class="text-center py-4">
                <i class="fas fa-exchange-alt fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No hay movimientos registrados</h5>
                <p class="text-muted">No se encontraron movimientos con los filtros aplicados.</p>
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
    }
`;
document.head.appendChild(style);

// Auto-submit en algunos cambios de filtro
document.getElementById('tipo').addEventListener('change', function() {
    if (this.value !== '') {
        this.form.submit();
    }
});
</script>

<?php include '../../includes/footer.php'; ?>
