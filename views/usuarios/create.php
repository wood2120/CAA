<?php
require_once '../../includes/functions.php';
require_once '../../models/Usuario.php';

requireLogin();
checkSessionTimeout();
requireRole('Administrador');

$pageTitle = 'Crear Usuario';
include '../../includes/header.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $database = new Database();
        $db = $database->getConnection();
        $usuarioModel = new Usuario($db);
        
        $usuarioModel->usuario = sanitizeInput($_POST['usuario']);
        $usuarioModel->contrasena = password_hash($_POST['contrasena'], PASSWORD_DEFAULT);
        $usuarioModel->rol = sanitizeInput($_POST['rol']);
        $validRoles = ['Administrador','Contador','Trabajador'];
        if (!in_array($usuarioModel->rol, $validRoles)) {
            throw new Exception('Rol inválido');
        }
        
        if ($usuarioModel->create()) {
            logActivity($_SESSION['user_id'], "Usuario creado: " . $usuarioModel->usuario);
            header("Location: index.php?success=created");
            exit();
        } else {
            $error = "Error al crear el usuario";
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-user-plus"></i> Crear Usuario
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
                    <h6 class="m-0 font-weight-bold text-primary">Información del Usuario</h6>
                </div>
                <div class="card-body">
                    <form method="POST" id="usuarioForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="usuario" class="form-label">
                                        <i class="fas fa-user"></i> Usuario <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="usuario" name="usuario" 
                                           required maxlength="150" 
                                           value="<?php echo isset($_POST['usuario']) ? htmlspecialchars($_POST['usuario']) : ''; ?>">
                                    <div class="form-text">Nombre de usuario único para el acceso al sistema</div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="rol" class="form-label">
                                        <i class="fas fa-shield-alt"></i> Rol <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select" id="rol" name="rol" required>
                                        <option value="">Seleccionar rol...</option>
                                        <?php $roles = ['Administrador'=>'Administrador','Contador'=>'Contador','Trabajador'=>'Trabajador'];
                                        foreach ($roles as $value=>$label): ?>
                                            <option value="<?php echo $value; ?>" <?php echo (isset($_POST['rol']) && $_POST['rol'] == $value) ? 'selected' : ''; ?>>
                                                <?php echo $label; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="contrasena" class="form-label">
                                        <i class="fas fa-lock"></i> Contraseña <span class="text-danger">*</span>
                                    </label>
                                    <input type="password" class="form-control" id="contrasena" name="contrasena" 
                                           required minlength="6">
                                    <div class="form-text">Mínimo 6 caracteres</div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="confirmar_contrasena" class="form-label">
                                        <i class="fas fa-lock"></i> Confirmar Contraseña <span class="text-danger">*</span>
                                    </label>
                                    <input type="password" class="form-control" id="confirmar_contrasena" name="confirmar_contrasena" 
                                           required minlength="6">
                                </div>
                            </div>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="index.php" class="btn btn-secondary me-md-2">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Guardar Usuario
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
                    <h6><i class="fas fa-info-circle text-info"></i> Roles del Sistema</h6>
                    <ul class="list-unstyled">
                        <li><strong>Administrador:</strong> Acceso completo al sistema</li>
                        <li><strong>Contador:</strong> Acceso a reportes y trabajos</li>
                        <li><strong>Trabajador:</strong> Gestión operativa (clientes, trabajos, inventario)</li>
                    </ul>
                    
                    <hr>
                    
                    <h6><i class="fas fa-shield-alt text-warning"></i> Seguridad</h6>
                    <p class="small text-muted">
                        Las contraseñas se almacenan de forma segura mediante encriptación.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('usuarioForm').addEventListener('submit', function(e) {
    const contrasena = document.getElementById('contrasena').value;
    const confirmarContrasena = document.getElementById('confirmar_contrasena').value;
    
    if (contrasena !== confirmarContrasena) {
        e.preventDefault();
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Las contraseñas no coinciden'
        });
        return false;
    }
});
</script>

<?php include '../../includes/footer.php'; ?>
