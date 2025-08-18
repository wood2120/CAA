<?php
require_once '../../includes/functions.php';
require_once '../../models/Bitacora.php';

requireLogin();
checkSessionTimeout();

$pageTitle = 'Bitácora del Sistema';
include '../../includes/header.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    $bitacoraModel = new Bitacora($db);
    
    $limite = isset($_GET['limite']) ? (int)$_GET['limite'] : 100;
    $usuario_filtro = isset($_GET['usuario']) ? (int)$_GET['usuario'] : '';
    $exportar = $_GET['exportar'] ?? '';
    
    if (!empty($usuario_filtro)) {
        $stmt = $bitacoraModel->getByUsuario($usuario_filtro, $limite);
    } else {
        $stmt = $bitacoraModel->readAll($limite);
    }

    // Preparar datos si se exporta
    if ($exportar === 'csv') {
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=bitacora_' . date('Y-m-d_H-i-s') . '.csv');
        $output = fopen('php://output', 'w');
        fwrite($output, "\xEF\xBB\xBF"); // BOM
        fputcsv($output, ['ID_Bitacora','Fecha','Hora','Usuario','Accion']);
        foreach ($rows as $r) {
            fputcsv($output, [
                $r['ID_Bitacora'],
                date('Y-m-d', strtotime($r['Fecha_Hora'])),
                date('H:i:s', strtotime($r['Fecha_Hora'])),
                $r['Usuario'] ?? 'Desconocido',
                preg_replace("/[\r\n]+/", ' ', $r['Accion'])
            ]);
        }
        fclose($output);
        exit;
    }
    
    // Obtener lista de usuarios para el filtro
    $usuariosStmt = $db->query("SELECT ID_Usuario, Usuario FROM TB_Usuarios ORDER BY Usuario");
    // Reiniciar cursor de stmt para mostrar en tabla si no se exportó
    if (!isset($rows)) {
        if (!empty($usuario_filtro)) {
            $stmt = $bitacoraModel->getByUsuario($usuario_filtro, $limite);
        } else {
            $stmt = $bitacoraModel->readAll($limite);
        }
    }
    
} catch (Exception $e) {
    $error = "Error al cargar bitácora: " . $e->getMessage();
}

logActivity($_SESSION['user_id'], "Acceso a bitácora del sistema");
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-history"></i> Bitácora del Sistema
        </h1>
        <div class="btn-group">
            <a href="?exportar=csv<?php echo $usuario_filtro ? '&usuario=' . $usuario_filtro : ''; ?><?php echo $limite ? '&limite=' . $limite : ''; ?>" class="btn btn-success btn-sm">
                <i class="fas fa-file-csv"></i> Exportar CSV
            </a>
            <a href="index.php" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Volver a Reportes
            </a>
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
                <div class="col-md-4">
                    <label for="usuario" class="form-label">Usuario</label>
                    <select class="form-select" id="usuario" name="usuario">
                        <option value="">Todos los usuarios</option>
                        <?php while ($user = $usuariosStmt->fetch(PDO::FETCH_ASSOC)): ?>
                        <option value="<?php echo $user['ID_Usuario']; ?>" 
                                <?php echo ($usuario_filtro == $user['ID_Usuario']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($user['Usuario']); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="limite" class="form-label">Cantidad de registros</label>
                    <select class="form-select" id="limite" name="limite">
                        <option value="50" <?php echo ($limite == 50) ? 'selected' : ''; ?>>50 registros</option>
                        <option value="100" <?php echo ($limite == 100) ? 'selected' : ''; ?>>100 registros</option>
                        <option value="200" <?php echo ($limite == 200) ? 'selected' : ''; ?>>200 registros</option>
                        <option value="500" <?php echo ($limite == 500) ? 'selected' : ''; ?>>500 registros</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-grid gap-2 d-md-flex">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i> Filtrar
                        </button>
                        <a href="bitacora.php" class="btn btn-outline-secondary">
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
                Registro de Actividades
                <?php if (!empty($usuario_filtro)): ?>
                    - Filtrado por usuario
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
                            <th>Fecha y Hora</th>
                            <th>Usuario</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['ID_Bitacora']); ?></td>
                            <td>
                                <strong><?php echo date('d/m/Y', strtotime($row['Fecha_Hora'])); ?></strong>
                                <br>
                                <small class="text-muted"><?php echo date('H:i:s', strtotime($row['Fecha_Hora'])); ?></small>
                            </td>
                            <td>
                                <span class="badge bg-primary">
                                    <?php echo htmlspecialchars($row['Usuario'] ?? 'Usuario desconocido'); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($row['Accion']); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="text-center py-4">
                <i class="fas fa-history fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No hay registros de actividad</h5>
                <p class="text-muted">No se encontraron registros con los filtros aplicados.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Auto-submit el formulario cuando cambian los filtros
const usuarioSel = document.getElementById('usuario');
if (usuarioSel) usuarioSel.addEventListener('change', function() { this.form.submit(); });
const limiteSel = document.getElementById('limite');
if (limiteSel) limiteSel.addEventListener('change', function() { this.form.submit(); });
</script>

<?php include '../../includes/footer.php'; ?>
