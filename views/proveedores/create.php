<?php
require_once '../../includes/functions.php';
require_once '../../models/Proveedor.php';

requireLogin();
checkSessionTimeout();

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre_proveedor = sanitizeInput($_POST['nombre_proveedor']);
    $rnc = sanitizeInput($_POST['rnc']);
    $direccion = sanitizeInput($_POST['direccion']);
    $telefono = sanitizeInput($_POST['telefono']);
    $email = sanitizeInput($_POST['email']);
    $contacto_principal = sanitizeInput($_POST['contacto_principal']);
    $notas = sanitizeInput($_POST['notas']);

    if (empty($nombre_proveedor)) {
        $errors[] = 'El nombre del proveedor es obligatorio.';
    }

    if (!empty($rnc) && !preg_match('/^\d{9}$/', $rnc)) {
        $errors[] = 'El RNC debe tener exactamente 9 dígitos.';
    }

    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'El formato del correo electrónico no es válido.';
    }

    if (!empty($telefono) && !preg_match('/^[\d\-\(\)\s\+]+$/', $telefono)) {
        $errors[] = 'El formato del teléfono no es válido.';
    }

    if (empty($errors)) {
        try {
            $database = new Database();
            $db = $database->getConnection();
            $proveedorModel = new Proveedor($db);

            $proveedorModel->nombre_proveedor = $nombre_proveedor;
            $proveedorModel->rnc = $rnc;
            $proveedorModel->direccion = $direccion;
            $proveedorModel->telefono = $telefono;
            $proveedorModel->email = $email;
            $proveedorModel->contacto_principal = $contacto_principal;
            $proveedorModel->notas = $notas;

            if ($proveedorModel->exists()) {
                $errors[] = 'Ya existe un proveedor con ese nombre o RNC.';
            } else {
                if ($proveedorModel->create()) {
                    logActivity($_SESSION['user_id'], "Proveedor creado: {$nombre_proveedor}");
                    header('Location: index.php?success=created');
                    exit();
                } else {
                    $errors[] = 'Error al crear el proveedor.';
                }
            }
        } catch (Exception $e) {
            $errors[] = 'Error del sistema: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Nuevo Proveedor';
include '../../includes/header.php';
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-plus-circle"></i> Nuevo Proveedor
        </h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../../dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="index.php">Proveedores</a></li>
                <li class="breadcrumb-item active">Nuevo</li>
            </ol>
        </nav>
    </div>

    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <h6><i class="fas fa-exclamation-triangle"></i> Se encontraron los siguientes errores:</h6>
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-10 mx-auto">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Información del Proveedor</h6>
                </div>
                <div class="card-body">
                    <form method="POST" id="proveedorForm" novalidate>
                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label for="nombre_proveedor" class="form-label">
                                    Nombre del Proveedor <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="nombre_proveedor" name="nombre_proveedor" 
                                       value="<?php echo isset($_POST['nombre_proveedor']) ? htmlspecialchars($_POST['nombre_proveedor']) : ''; ?>" 
                                       required maxlength="150" placeholder="Ej: Distribuidora ABC S.R.L.">
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="rnc" class="form-label">RNC</label>
                                <input type="text" class="form-control" id="rnc" name="rnc" 
                                       value="<?php echo isset($_POST['rnc']) ? htmlspecialchars($_POST['rnc']) : ''; ?>" 
                                       maxlength="9" pattern="\d{9}" placeholder="123456789">
                                <div class="form-text">9 dígitos (opcional)</div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="contacto_principal" class="form-label">Contacto Principal</label>
                                <input type="text" class="form-control" id="contacto_principal" name="contacto_principal" 
                                       value="<?php echo isset($_POST['contacto_principal']) ? htmlspecialchars($_POST['contacto_principal']) : ''; ?>" 
                                       maxlength="100" placeholder="Nombre del contacto principal">
                            </div>

                            <div class="col-md-3 mb-3">
                                <label for="telefono" class="form-label">Teléfono</label>
                                <input type="tel" class="form-control" id="telefono" name="telefono" 
                                       value="<?php echo isset($_POST['telefono']) ? htmlspecialchars($_POST['telefono']) : ''; ?>" 
                                       maxlength="20" placeholder="(809) 123-4567">
                            </div>

                            <div class="col-md-3 mb-3">
                                <label for="email" class="form-label">Correo Electrónico</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                                       maxlength="100" placeholder="contacto@proveedor.com">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12 mb-3">
                                <label for="direccion" class="form-label">Dirección</label>
                                <textarea class="form-control" id="direccion" name="direccion" rows="3" 
                                          placeholder="Dirección completa del proveedor..."><?php echo isset($_POST['direccion']) ? htmlspecialchars($_POST['direccion']) : ''; ?></textarea>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12 mb-3">
                                <label for="notas" class="form-label">Notas Adicionales</label>
                                <textarea class="form-control" id="notas" name="notas" rows="3" 
                                          placeholder="Información adicional sobre el proveedor..."><?php echo isset($_POST['notas']) ? htmlspecialchars($_POST['notas']) : ''; ?></textarea>
                                <div class="form-text">Información sobre términos de pago, horarios de atención, especialidades, etc.</div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="alert alert-info">
                                    <h6><i class="fas fa-info-circle"></i> Información importante:</h6>
                                    <ul class="mb-0">
                                        <li>Solo el <strong>nombre del proveedor</strong> es obligatorio</li>
                                        <li>El RNC debe tener exactamente 9 dígitos numéricos</li>
                                        <li>El proveedor se creará con estado <strong>Activo</strong> por defecto</li>
                                        <li>Puede agregar elementos de inventario después de crear el proveedor</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <hr>
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <a href="index.php" class="btn btn-secondary me-md-2">
                                        <i class="fas fa-arrow-left"></i> Cancelar
                                    </a>
                                    <button type="button" class="btn btn-outline-secondary me-md-2" onclick="clearForm()">
                                        <i class="fas fa-broom"></i> Limpiar
                                    </button>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Guardar Proveedor
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('proveedorForm').addEventListener('submit', function(e) {
        if (!Utils.validateForm('proveedorForm')) {
            e.preventDefault();
            Utils.showAlert('Por favor, complete todos los campos requeridos.', 'warning');
            return false;
        }
    });

    document.getElementById('rnc').addEventListener('input', function(e) {
        this.value = this.value.replace(/\D/g, '');
        if (this.value.length > 9) {
            this.value = this.value.substring(0, 9);
        }
    });

    document.getElementById('telefono').addEventListener('input', function(e) {
        this.value = this.value.replace(/[^\d\-\(\)\s\+]/g, '');
    });

    document.getElementById('nombre_proveedor').focus();
});

function clearForm() {
    if (confirm('¿Está seguro de que desea limpiar todos los campos?')) {
        document.getElementById('proveedorForm').reset();
        Utils.clearFormValidation('proveedorForm');
        document.getElementById('nombre_proveedor').focus();
    }
}
</script>

<?php include '../../includes/footer.php'; ?>
