<?php
require_once '../../includes/functions.php';
require_once '../../models/Cliente.php';

requireLogin();
checkSessionTimeout();

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cedula = sanitizeInput($_POST['cedula']);
    $nombre = sanitizeInput($_POST['nombre']);
    $contacto = sanitizeInput($_POST['contacto']);
    $empresa = sanitizeInput($_POST['empresa']);

    if (empty($cedula)) {
        $errors[] = 'La cédula es obligatoria.';
    } elseif (!validateCedula($cedula)) {
        $errors[] = 'La cédula debe tener exactamente 9 dígitos.';
    }

    if (empty($nombre)) {
        $errors[] = 'El nombre es obligatorio.';
    }

    if (!empty($contacto) && !preg_match('/^[0-9-+\s()]+$/', $contacto)) {
        $errors[] = 'El formato del contacto no es válido.';
    }

    if (empty($errors)) {
        try {
            $database = new Database();
            $db = $database->getConnection();
            $clienteModel = new Cliente($db);

            $clienteModel->cedula = $cedula;
            $clienteModel->nombre = $nombre;
            $clienteModel->contacto = $contacto;
            $clienteModel->empresa = $empresa;

            if ($clienteModel->exists()) {
                $errors[] = 'Ya existe un cliente con esa cédula.';
            } else {
                if ($clienteModel->create()) {
                    logActivity($_SESSION['user_id'], "Cliente creado: {$nombre} (Cédula: {$cedula})");
                    
                    header('Location: index.php?success=created');
                    exit();
                } else {
                    $errors[] = 'Error al crear el cliente.';
                }
            }
        } catch (Exception $e) {
            $errors[] = 'Error del sistema: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Nuevo Cliente';
include '../../includes/header.php';
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-user-plus"></i> Nuevo Cliente
        </h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../../dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="index.php">Clientes</a></li>
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
        <div class="col-lg-8 mx-auto">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Información del Cliente</h6>
                </div>
                <div class="card-body">
                    <form method="POST" id="clienteForm" novalidate>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="cedula" class="form-label">
                                    Cédula <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="cedula" name="cedula" 
                                       value="<?php echo isset($_POST['cedula']) ? htmlspecialchars($_POST['cedula']) : ''; ?>" 
                                       required maxlength="15" placeholder="Ej: 123456789">
                                <div class="form-text">
                                    Exactamente 9 dígitos sin espacios ni guiones
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="nombre" class="form-label">
                                    Nombre Completo <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="nombre" name="nombre" 
                                       value="<?php echo isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : ''; ?>" 
                                       required maxlength="150" placeholder="Nombre completo del cliente">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="contacto" class="form-label">Teléfono de Contacto</label>
                                <input type="tel" class="form-control" id="contacto" name="contacto" 
                                       value="<?php echo isset($_POST['contacto']) ? htmlspecialchars($_POST['contacto']) : ''; ?>" 
                                       maxlength="20" placeholder="Ej: 8888-8888">
                                <div class="form-text">
                                    Número de teléfono principal del cliente
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="empresa" class="form-label">Empresa</label>
                                <input type="text" class="form-control" id="empresa" name="empresa" 
                                       value="<?php echo isset($_POST['empresa']) ? htmlspecialchars($_POST['empresa']) : ''; ?>" 
                                       maxlength="150" placeholder="Nombre de la empresa (opcional)">
                                <div class="form-text">
                                    Empresa donde trabaja el cliente (opcional)
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
                                        <i class="fas fa-save"></i> Guardar Cliente
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
    // Configurar validación de cédula
    SistemaKris.setupCedulaValidation('cedula');

    document.getElementById('cedula').addEventListener('input', function() {
        let value = this.value.replace(/\D/g, ''); // Solo números
        if (value.length > 9) {
            value = value.substring(0, 9);
        }
        this.value = value;
    });

    document.getElementById('contacto').addEventListener('input', function() {
        this.value = Utils.formatPhone(this.value);
    });

    document.getElementById('clienteForm').addEventListener('submit', function(e) {
        if (!Utils.validateForm('clienteForm')) {
            e.preventDefault();
            Utils.showAlert('Por favor, complete todos los campos requeridos.', 'warning');
            return false;
        }

        const cedula = document.getElementById('cedula').value;
        if (!Utils.validateCedula(cedula)) {
            e.preventDefault();
            document.getElementById('cedula').classList.add('is-invalid');
            Utils.showAlert('La cédula debe tener exactamente 9 dígitos.', 'danger');
            return false;
        }
    });

    document.getElementById('cedula').focus();
});

function clearForm() {
    if (confirm('¿Está seguro de que desea limpiar todos los campos?')) {
        document.getElementById('clienteForm').reset();
        Utils.clearFormValidation('clienteForm');
        document.getElementById('cedula').focus();
    }
}
</script>

<?php include '../../includes/footer.php'; ?>
