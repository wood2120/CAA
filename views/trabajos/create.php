<?php
require_once '../../includes/functions.php';
require_once '../../models/Trabajo.php';
require_once '../../models/Cliente.php';

requireLogin();
checkSessionTimeout();

$pageTitle = 'Crear Trabajo';
include '../../includes/header.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    $trabajoModel = new Trabajo($db);
    $clienteModel = new Cliente($db);
    
    // Obtener lista de clientes para el select
    $clientesStmt = $clienteModel->readAll();
    
} catch (Exception $e) {
    $error = "Error al cargar datos: " . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Usar los nombres de propiedades reales definidos en el modelo (evita dynamic properties en PHP 8.2)
        $trabajoModel->Cedula_Cliente   = (isset($_POST['cedula_cliente']) && $_POST['cedula_cliente'] !== '') ? sanitizeInput($_POST['cedula_cliente']) : null;
        $trabajoModel->Tipo_Trabajo     = sanitizeInput($_POST['tipo_trabajo']);
        $trabajoModel->Descripcion      = isset($_POST['descripcion']) ? sanitizeInput($_POST['descripcion']) : null; // puede ser opcional
        $trabajoModel->Precio_Mano_Obra = (float)$_POST['precio_mano_obra'];
        // Inicialmente el total = mano de obra (los materiales se sumarán luego)
        $trabajoModel->Precio_Total     = $trabajoModel->Precio_Mano_Obra; 
        $trabajoModel->Estado           = sanitizeInput($_POST['estado']);
        $trabajoModel->Fecha_Inicio     = (!empty($_POST['fecha_inicio'])) ? $_POST['fecha_inicio'] : null;
        $trabajoModel->Fecha_Final      = (!empty($_POST['fecha_final'])) ? $_POST['fecha_final'] : null;

        if ($trabajoModel->create()) {
            logActivity($_SESSION['user_id'], "Trabajo creado: " . $trabajoModel->Tipo_Trabajo);
            header("Location: index.php?success=created");
            exit();
        } else {
            $error = "Error al crear el trabajo";
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-plus"></i> Crear Trabajo
        </h1>
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
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
                                        <option value="">Seleccionar cliente (opcional)...</option>
                                        <?php while ($cliente = $clientesStmt->fetch(PDO::FETCH_ASSOC)): ?>
                                        <option value="<?php echo htmlspecialchars($cliente['Cedula']); ?>" 
                                                <?php echo (isset($_POST['cedula_cliente']) && $_POST['cedula_cliente'] == $cliente['Cedula']) ? 'selected' : ''; ?>>
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
                                           value="<?php echo isset($_POST['tipo_trabajo']) ? htmlspecialchars($_POST['tipo_trabajo']) : ''; ?>"
                                           placeholder="Ej: Instalación eléctrica, Plomería, Construcción">
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
                                              rows="4" 
                                              placeholder="Describe detalladamente el trabajo a realizar..."><?php echo isset($_POST['descripcion']) ? htmlspecialchars($_POST['descripcion']) : ''; ?></textarea>
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
                                           value="<?php echo isset($_POST['precio_mano_obra']) ? $_POST['precio_mano_obra'] : ''; ?>"
                                           placeholder="0.00">
                                    <div class="form-text">Costo solo de la mano de obra</div>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="estado" class="form-label">
                                        <i class="fas fa-flag"></i> Estado <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select" id="estado" name="estado" required>
                                        <option value="Pendiente" <?php echo (isset($_POST['estado']) && $_POST['estado'] == 'Pendiente') ? 'selected' : 'selected'; ?>>
                                            Pendiente
                                        </option>
                                        <option value="En Proceso" <?php echo (isset($_POST['estado']) && $_POST['estado'] == 'En Proceso') ? 'selected' : ''; ?>>
                                            En Proceso
                                        </option>
                                        <option value="Completado" <?php echo (isset($_POST['estado']) && $_POST['estado'] == 'Completado') ? 'selected' : ''; ?>>
                                            Completado
                                        </option>
                                        <option value="Cancelado" <?php echo (isset($_POST['estado']) && $_POST['estado'] == 'Cancelado') ? 'selected' : ''; ?>>
                                            Cancelado
                                        </option>
                                    </select>
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
                                           value="<?php echo isset($_POST['fecha_inicio']) ? $_POST['fecha_inicio'] : ''; ?>">
                                    <div class="form-text">Fecha programada para iniciar el trabajo</div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="fecha_final" class="form-label">
                                        <i class="fas fa-calendar-check"></i> Fecha de Finalización
                                    </label>
                                    <input type="date" class="form-control" id="fecha_final" name="fecha_final" 
                                           value="<?php echo isset($_POST['fecha_final']) ? $_POST['fecha_final'] : ''; ?>">
                                    <div class="form-text">Fecha programada para finalizar el trabajo</div>
                                </div>
                            </div>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="index.php" class="btn btn-secondary me-md-2">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Crear Trabajo
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Información</h6>
                </div>
                <div class="card-body">
                    <h6><i class="fas fa-info-circle text-info"></i> Estados del Trabajo</h6>
                    <ul class="list-unstyled small">
                        <li><strong>Pendiente:</strong> Trabajo solicitado, no iniciado</li>
                        <li><strong>En Proceso:</strong> Trabajo en desarrollo</li>
                        <li><strong>Completado:</strong> Trabajo terminado</li>
                        <li><strong>Cancelado:</strong> Trabajo cancelado</li>
                    </ul>
                    
                    <hr>
                    
                    <h6><i class="fas fa-calculator text-success"></i> Cálculo de Costos</h6>
                    <p class="small text-muted">
                        El precio total se calculará automáticamente sumando:
                    </p>
                    <ul class="list-unstyled small">
                        <li>• Mano de obra (especificado aquí)</li>
                        <li>• Materiales y herramientas (agregados después)</li>
                    </ul>
                    
                    <hr>
                    
                    <h6><i class="fas fa-lightbulb text-warning"></i> Consejos</h6>
                    <ul class="list-unstyled small">
                        <li>• Sea específico en la descripción</li>
                        <li>• Puede agregar materiales después</li>
                        <li>• Las fechas son opcionales pero recomendadas</li>
                    </ul>
                </div>
            </div>

            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Tipos de Trabajo Comunes</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-1">
                        <button type="button" class="btn btn-outline-secondary btn-sm" 
                                onclick="setTipoTrabajo('Instalación Eléctrica')">
                            Instalación Eléctrica
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" 
                                onclick="setTipoTrabajo('Plomería')">
                            Plomería
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" 
                                onclick="setTipoTrabajo('Construcción')">
                            Construcción
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" 
                                onclick="setTipoTrabajo('Reparación')">
                            Reparación
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" 
                                onclick="setTipoTrabajo('Mantenimiento')">
                            Mantenimiento
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" 
                                onclick="setTipoTrabajo('Pintura')">
                            Pintura
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function setTipoTrabajo(tipo) {
    document.getElementById('tipo_trabajo').value = tipo;
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
    
    // Establecer fecha mínima para fecha final
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

// Formatear precio en tiempo real
document.getElementById('precio_mano_obra').addEventListener('input', function(e) {
    const valor = parseFloat(e.target.value);
    if (!isNaN(valor) && valor >= 0) {
        // Aquí se podría mostrar el valor formateado
    }
});
</script>

<?php include '../../includes/footer.php'; ?>
