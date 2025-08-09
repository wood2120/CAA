<?php
require_once '../../includes/functions.php';
require_once '../../models/Trabajo.php';
require_once '../../models/Cliente.php';

requireLogin();
checkSessionTimeout();

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id = (int)$_GET['id'];
$pageTitle = 'Editar Trabajo';
include '../../includes/header.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    $trabajoModel = new Trabajo($db);
    $clienteModel = new Cliente($db);
    
    // Obtener datos del trabajo
    $trabajo = $trabajoModel->readOne($id);
    
    if (!$trabajo) {
        throw new Exception("Trabajo no encontrado");
    }
    
    // Obtener lista de clientes para el select
    $clientesStmt = $clienteModel->readAll();
    
} catch (Exception $e) {
    header("Location: index.php?error=" . urlencode($e->getMessage()));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $trabajoModel->id_trabajo = $id;
        $trabajoModel->cedula_cliente = !empty($_POST['cedula_cliente']) ? sanitizeInput($_POST['cedula_cliente']) : null;
        $trabajoModel->tipo_trabajo = sanitizeInput($_POST['tipo_trabajo']);
        $trabajoModel->descripcion = sanitizeInput($_POST['descripcion']);
        $trabajoModel->precio_mano_obra = (float)$_POST['precio_mano_obra'];
        $trabajoModel->estado = sanitizeInput($_POST['estado']);
        $trabajoModel->fecha_inicio = !empty($_POST['fecha_inicio']) ? $_POST['fecha_inicio'] : null;
        $trabajoModel->fecha_final = !empty($_POST['fecha_final']) ? $_POST['fecha_final'] : null;
        
        if ($trabajoModel->update()) {
            logActivity($_SESSION['user_id'], "Trabajo editado ID: " . $id . " - " . $trabajoModel->tipo_trabajo);
            header("Location: view.php?id=$id&success=updated");
            exit();
        } else {
            $error = "Error al actualizar el trabajo";
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-edit"></i> Editar Trabajo #<?php echo $trabajo['ID_Trabajo']; ?>
        </h1>
        <div>
            <a href="view.php?id=<?php echo $trabajo['ID_Trabajo']; ?>" class="btn btn-info me-2">
                <i class="fas fa-eye"></i> Ver Detalles
            </a>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
    </div>

    <?php if (isset($error)): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
    </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Información del Trabajo</h6>
                </div>
                <div class="card-body">
                    <form method="POST" id="trabajoForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="cedula_cliente" class="form-label">
                                        <i class="fas fa-user"></i> Cliente
                                    </label>
                                    <select class="form-select" id="cedula_cliente" name="cedula_cliente">
                                        <option value="">Sin cliente asignado</option>
                                        <?php while ($cliente = $clientesStmt->fetch(PDO::FETCH_ASSOC)): ?>
                                        <option value="<?php echo htmlspecialchars($cliente['Cedula']); ?>" 
                                                <?php echo ($cliente['Cedula'] == $trabajo['Cedula_Cliente']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cliente['Nombre']); ?> 
                                            (<?php echo htmlspecialchars($cliente['Cedula']); ?>)
                                        </option>
                                        <?php endwhile; ?>
                                    </select>
                                    <div class="form-text">
                                        <a href="../clientes/create.php" target="_blank">
                                            <i class="fas fa-plus"></i> Crear nuevo cliente
                                        </a>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="tipo_trabajo" class="form-label">
                                        <i class="fas fa-briefcase"></i> Tipo de Trabajo <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="tipo_trabajo" name="tipo_trabajo" 
                                           required maxlength="150" 
                                           value="<?php echo htmlspecialchars($trabajo['Tipo_Trabajo']); ?>">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="mb-3">
                                    <label for="descripcion" class="form-label">
                                        <i class="fas fa-align-left"></i> Descripción del Trabajo
                                    </label>
                                    <textarea class="form-control" id="descripcion" name="descripcion" 
                                              rows="4"><?php echo htmlspecialchars($trabajo['Descripcion']); ?></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="precio_mano_obra" class="form-label">
                                        <i class="fas fa-dollar-sign"></i> Precio Mano de Obra <span class="text-danger">*</span>
                                    </label>
                                    <input type="number" class="form-control" id="precio_mano_obra" name="precio_mano_obra" 
                                           required min="0" step="0.01" 
                                           value="<?php echo $trabajo['Precio_Mano_Obra']; ?>">
                                    <div class="form-text">Costo solo de la mano de obra</div>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="estado" class="form-label">
                                        <i class="fas fa-flag"></i> Estado <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select" id="estado" name="estado" required>
                                        <option value="Pendiente" <?php echo ($trabajo['Estado'] == 'Pendiente') ? 'selected' : ''; ?>>
                                            Pendiente
                                        </option>
                                        <option value="En Proceso" <?php echo ($trabajo['Estado'] == 'En Proceso') ? 'selected' : ''; ?>>
                                            En Proceso
                                        </option>
                                        <option value="Completado" <?php echo ($trabajo['Estado'] == 'Completado') ? 'selected' : ''; ?>>
                                            Completado
                                        </option>
                                        <option value="Cancelado" <?php echo ($trabajo['Estado'] == 'Cancelado') ? 'selected' : ''; ?>>
                                            Cancelado
                                        </option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-calculator"></i> Precio Total Actual
                                    </label>
                                    <input type="text" class="form-control" 
                                           value="<?php echo formatCurrency($trabajo['Precio_Total']); ?>" 
                                           readonly>
                                    <div class="form-text">Se recalcula automáticamente</div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="fecha_inicio" class="form-label">
                                        <i class="fas fa-calendar-alt"></i> Fecha de Inicio
                                    </label>
                                    <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" 
                                           value="<?php echo $trabajo['Fecha_Inicio']; ?>">
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="fecha_final" class="form-label">
                                        <i class="fas fa-calendar-check"></i> Fecha de Finalización
                                    </label>
                                    <input type="date" class="form-control" id="fecha_final" name="fecha_final" 
                                           value="<?php echo $trabajo['Fecha_Final']; ?>">
                                </div>
                            </div>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="view.php?id=<?php echo $trabajo['ID_Trabajo']; ?>" class="btn btn-secondary me-md-2">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Actualizar Trabajo
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Información Actual</h6>
                </div>
                <div class="card-body">
                    <p><strong>ID:</strong> <?php echo $trabajo['ID_Trabajo']; ?></p>
                    <p><strong>Estado:</strong> 
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
                    <p><strong>Creado:</strong> <?php echo formatDate($trabajo['Fecha_Creacion']); ?></p>
                    <p><strong>Precio Total:</strong> <?php echo formatCurrency($trabajo['Precio_Total']); ?></p>
                    
                    <?php
                    // Obtener costo de materiales
                    $queryMateriales = "SELECT SUM(Subtotal) as total_materiales FROM TB_Trabajo_Inventario WHERE ID_Trabajo = :id";
                    $stmtMateriales = $db->prepare($queryMateriales);
                    $stmtMateriales->bindParam(':id', $id);
                    $stmtMateriales->execute();
                    $costoMateriales = $stmtMateriales->fetch(PDO::FETCH_ASSOC)['total_materiales'] ?? 0;
                    ?>
                    
                    <hr>
                    <h6>Desglose de Costos:</h6>
                    <p><strong>Mano de Obra:</strong> <?php echo formatCurrency($trabajo['Precio_Mano_Obra']); ?></p>
                    <p><strong>Materiales:</strong> <?php echo formatCurrency($costoMateriales); ?></p>
                </div>
            </div>

            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Acciones Rápidas</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="agregar_materiales.php?id=<?php echo $trabajo['ID_Trabajo']; ?>" class="btn btn-success">
                            <i class="fas fa-plus"></i> Agregar Materiales
                        </a>
                        
                        <?php if ($trabajo['Estado'] == 'Pendiente'): ?>
                        <button class="btn btn-info" onclick="cambiarEstadoRapido('En Proceso')">
                            <i class="fas fa-play"></i> Marcar En Proceso
                        </button>
                        <?php elseif ($trabajo['Estado'] == 'En Proceso'): ?>
                        <button class="btn btn-success" onclick="cambiarEstadoRapido('Completado')">
                            <i class="fas fa-check"></i> Marcar Completado
                        </button>
                        <?php endif; ?>
                        
                        <button class="btn btn-danger" onclick="eliminarTrabajo()">
                            <i class="fas fa-trash"></i> Eliminar Trabajo
                        </button>
                    </div>
                </div>
            </div>

            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Materiales Usados</h6>
                </div>
                <div class="card-body">
                    <?php
                    // Obtener materiales del trabajo
                    $queryItems = "SELECT COUNT(*) as total_items FROM TB_Trabajo_Inventario WHERE ID_Trabajo = :id";
                    $stmtItems = $db->prepare($queryItems);
                    $stmtItems->bindParam(':id', $id);
                    $stmtItems->execute();
                    $totalItems = $stmtItems->fetch(PDO::FETCH_ASSOC)['total_items'];
                    ?>
                    
                    <p><strong>Items utilizados:</strong> <?php echo number_format($totalItems); ?></p>
                    <p><strong>Costo total materiales:</strong> <?php echo formatCurrency($costoMateriales); ?></p>
                    
                    <?php if ($totalItems > 0): ?>
                    <a href="view.php?id=<?php echo $trabajo['ID_Trabajo']; ?>#materiales" class="btn btn-info btn-sm">
                        <i class="fas fa-list"></i> Ver Detalle
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function cambiarEstadoRapido(nuevoEstado) {
    document.getElementById('estado').value = nuevoEstado;
    
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
            document.getElementById('trabajoForm').submit();
        } else {
            // Revertir el cambio en el select
            location.reload();
        }
    });
}

function eliminarTrabajo() {
    Swal.fire({
        title: '¿Eliminar trabajo?',
        text: 'Esta acción no se puede deshacer. Se eliminarán también todos los materiales asociados.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `delete.php?id=<?php echo $trabajo['ID_Trabajo']; ?>`;
        }
    });
}

// Validación de fechas
document.getElementById('fecha_inicio').addEventListener('change', function() {
    const fechaInicio = new Date(this.value);
    const fechaFinal = document.getElementById('fecha_final');
    
    if (fechaFinal.value) {
        const fechaFin = new Date(fechaFinal.value);
        if (fechaInicio > fechaFin) {
            Swal.fire({
                icon: 'warning',
                title: 'Fechas Inconsistentes',
                text: 'La fecha de inicio no puede ser posterior a la fecha de finalización'
            });
        }
    }
    
    fechaFinal.min = this.value;
});

document.getElementById('fecha_final').addEventListener('change', function() {
    const fechaFinal = new Date(this.value);
    const fechaInicio = document.getElementById('fecha_inicio');
    
    if (fechaInicio.value) {
        const fechaIni = new Date(fechaInicio.value);
        if (fechaFinal < fechaIni) {
            Swal.fire({
                icon: 'warning',
                title: 'Fechas Inconsistentes',
                text: 'La fecha de finalización no puede ser anterior a la fecha de inicio'
            });
            this.value = '';
        }
    }
});

// Validación del formulario
document.getElementById('trabajoForm').addEventListener('submit', function(e) {
    const tipoTrabajo = document.getElementById('tipo_trabajo').value.trim();
    const precioManoObra = parseFloat(document.getElementById('precio_mano_obra').value);
    
    if (tipoTrabajo.length < 3) {
        e.preventDefault();
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'El tipo de trabajo debe tener al menos 3 caracteres'
        });
        return false;
    }
    
    if (isNaN(precioManoObra) || precioManoObra < 0) {
        e.preventDefault();
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Debe especificar un precio válido para la mano de obra'
        });
        return false;
    }
});
</script>

<?php include '../../includes/footer.php'; ?>
