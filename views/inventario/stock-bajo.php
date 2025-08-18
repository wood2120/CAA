<?php
require_once '../../includes/functions.php';
require_once '../../config/database.php';
require_once '../../models/Inventario.php';
require_once '../../models/Categoria.php';
require_once '../../models/Proveedor.php';

requireLogin();
checkSessionTimeout();

$pageTitle = 'Inventario - Stock Bajo';

try {
    $database = new Database();
    $db = $database->getConnection();

    // Filtros opcionales (categoría / proveedor / estado) para refinar dentro de stock bajo
    $filtro_categoria = $_GET['categoria'] ?? '';
    $filtro_proveedor = $_GET['proveedor'] ?? '';
    $filtro_estado = $_GET['estado'] ?? '';
    $exportar = $_GET['exportar'] ?? '';

    $query = "SELECT 
                i.ID_Inventario as ID_Item,
                i.Nombre as Nombre_Item,
                i.Descripcion,
                i.Cantidad_Stock,
                i.Stock_Minimo,
                i.Unidad_Medida,
                i.Precio_Unitario,
                (i.Cantidad_Stock * i.Precio_Unitario) as valor_total,
                i.Estado,
                i.Fecha_Ingreso,
                c.Nombre_Categoria as categoria_nombre,
                c.Tipo as tipo_categoria,
                p.Nombre_Proveedor as proveedor_nombre
              FROM TB_Inventario i
              LEFT JOIN TB_Categorias c ON i.ID_Categoria = c.ID_Categoria
              LEFT JOIN TB_Proveedores p ON i.ID_Proveedor = p.ID_Proveedor
              WHERE i.Cantidad_Stock <= i.Stock_Minimo";

    $conditions = [];
    $params = [];
    if (!empty($filtro_categoria)) { $conditions[] = 'i.ID_Categoria = :categoria'; $params[':categoria'] = $filtro_categoria; }
    if (!empty($filtro_proveedor)) { $conditions[] = 'i.ID_Proveedor = :proveedor'; $params[':proveedor'] = $filtro_proveedor; }
    if (!empty($filtro_estado)) { $conditions[] = 'i.Estado = :estado'; $params[':estado'] = $filtro_estado; }
    if ($conditions) { $query .= ' AND ' . implode(' AND ', $conditions); }
    $query .= ' ORDER BY i.Nombre';

    $stmt = $db->prepare($query);
    foreach ($params as $k=>$v) $stmt->bindValue($k,$v);
    $stmt->execute();
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Para combos
    $categorias = $db->query("SELECT ID_Categoria, Nombre_Categoria FROM TB_Categorias ORDER BY Nombre_Categoria")->fetchAll(PDO::FETCH_ASSOC);
    $proveedores = $db->query("SELECT ID_Proveedor, Nombre_Proveedor FROM TB_Proveedores ORDER BY Nombre_Proveedor")->fetchAll(PDO::FETCH_ASSOC);

    // Estadísticas básicas
    $total_items = count($items);
    $valor_total = 0; foreach ($items as $it) { $valor_total += (float)$it['valor_total']; }

    // Exportación CSV antes de cualquier HTML
    if ($exportar === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=stock_bajo_' . date('Y-m-d') . '.csv');
        $out = fopen('php://output','w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['ID_Item','Nombre','Descripcion','Categoria','Proveedor','Stock_Actual','Stock_Minimo','Unidad','Precio_Unitario','Valor_Total','Estado','Fecha_Ingreso']);
        foreach ($items as $r) {
            fputcsv($out, [
                $r['ID_Item'],
                preg_replace('/[\r\n]+/',' ', $r['Nombre_Item']),
                preg_replace('/[\r\n]+/',' ', $r['Descripcion']),
                preg_replace('/[\r\n]+/',' ', ($r['categoria_nombre'] ?? '')),
                preg_replace('/[\r\n]+/',' ', ($r['proveedor_nombre'] ?? '')),
                (int)$r['Cantidad_Stock'],
                (int)$r['Stock_Minimo'],
                $r['Unidad_Medida'],
                number_format((float)$r['Precio_Unitario'],2,'.',''),
                number_format((float)$r['valor_total'],2,'.',''),
                $r['Estado'],
                $r['Fecha_Ingreso'] ? date('Y-m-d', strtotime($r['Fecha_Ingreso'])) : ''
            ]);
        }
        fclose($out);
        exit;
    }

} catch (Exception $e) {
    $error = 'Error al cargar stock bajo: ' . $e->getMessage();
    $items = [];
    $categorias = [];
    $proveedores = [];
    $total_items = 0;
    $valor_total = 0;
}

include '../../includes/header.php';
logActivity($_SESSION['user_id'], 'Acceso a items de stock bajo');
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-exclamation-triangle text-warning"></i> Items con Stock Bajo
        </h1>
        <div class="btn-group">
            <a href="?exportar=csv<?php 
                $qs = [];
                if ($filtro_categoria) $qs[] = 'categoria=' . $filtro_categoria;
                if ($filtro_proveedor) $qs[] = 'proveedor=' . $filtro_proveedor;
                if ($filtro_estado) $qs[] = 'estado=' . urlencode($filtro_estado);
                echo $qs ? '&' . implode('&',$qs) : ''; ?>" class="btn btn-success btn-sm">
                <i class="fas fa-file-csv"></i> Exportar CSV
            </a>
            <a href="index.php" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
    </div>

    <?php if (isset($error)): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
    </div>
    <?php endif; ?>

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Total Items (Stock Bajo)</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_items; ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Valor Total</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">₡<?php echo number_format($valor_total,2); ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filtros</h6>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Categoría</label>
                    <select name="categoria" class="form-select" onchange="this.form.submit()">
                        <option value="">Todas</option>
                        <?php foreach ($categorias as $c): ?>
                        <option value="<?php echo $c['ID_Categoria']; ?>" <?php echo ($filtro_categoria==$c['ID_Categoria'])?'selected':''; ?>>
                            <?php echo htmlspecialchars($c['Nombre_Categoria']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Proveedor</label>
                    <select name="proveedor" class="form-select" onchange="this.form.submit()">
                        <option value="">Todos</option>
                        <?php foreach ($proveedores as $p): ?>
                        <option value="<?php echo $p['ID_Proveedor']; ?>" <?php echo ($filtro_proveedor==$p['ID_Proveedor'])?'selected':''; ?>>
                            <?php echo htmlspecialchars($p['Nombre_Proveedor']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Estado</label>
                    <select name="estado" class="form-select" onchange="this.form.submit()">
                        <option value="">Todos</option>
                        <option value="Activo" <?php echo ($filtro_estado=='Activo')?'selected':''; ?>>Activo</option>
                        <option value="Agotado" <?php echo ($filtro_estado=='Agotado')?'selected':''; ?>>Agotado</option>
                        <option value="Inactivo" <?php echo ($filtro_estado=='Inactivo')?'selected':''; ?>>Inactivo</option>
                    </select>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Items con Stock Igual o Inferior al Mínimo</h6>
        </div>
        <div class="card-body">
            <?php if (!empty($items)): ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="stockBajoTable">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Categoría</th>
                            <th>Proveedor</th>
                            <th>Stock</th>
                            <th>Precio Unit.</th>
                            <th>Valor Total</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $it): ?>
                        <tr class="table-warning">
                            <td><?php echo htmlspecialchars($it['ID_Item']); ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($it['Nombre_Item']); ?></strong>
                                <?php if(!empty($it['Descripcion'])): ?>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($it['Descripcion']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo ($it['tipo_categoria']=='Material')?'info':'warning'; ?>">
                                    <?php echo htmlspecialchars($it['categoria_nombre'] ?? ''); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($it['proveedor_nombre'] ?? 'Sin proveedor'); ?></td>
                            <td>
                                <strong><?php echo (int)$it['Cantidad_Stock']; ?></strong>
                                <small class="text-muted"><?php echo htmlspecialchars($it['Unidad_Medida']); ?></small><br>
                                <small class="text-danger"><i class="fas fa-level-down-alt"></i> Mín: <?php echo (int)$it['Stock_Minimo']; ?></small>
                            </td>
                            <td><?php echo formatCurrency($it['Precio_Unitario']); ?></td>
                            <td><strong><?php echo formatCurrency($it['valor_total']); ?></strong></td>
                            <td>
                                <span class="badge bg-<?php echo ($it['Estado']=='Activo')?'success':(($it['Estado']=='Agotado')?'danger':'secondary'); ?>">
                                    <?php echo htmlspecialchars($it['Estado']); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                <h5 class="text-muted">No hay items bajo el nivel mínimo de stock.</h5>
                <a href="index.php" class="btn btn-primary mt-3"><i class="fas fa-arrow-left"></i> Volver a Inventario</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    SistemaKris.initDataTable('stockBajoTable', { order: [[4,'asc']] });
});
</script>

<?php include '../../includes/footer.php'; ?>
