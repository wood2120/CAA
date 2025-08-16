<?php
require_once '../../includes/functions.php';
require_once '../../models/Usuario.php';

requireLogin();
checkSessionTimeout();
requireRole('Administrador');

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id = (int)$_GET['id'];
$pageTitle = 'Editar Usuario';
include '../../includes/header.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    $usuarioModel = new Usuario($db);
    
    // Obtener datos del usuario
    $usuarioData = $usuarioModel->readOne($id);
    if (!$usuarioData) {
        throw new Exception("Usuario no encontrado");
    }
    // Asignar propiedades al modelo para usar en el formulario
    $usuarioModel->id_usuario = $usuarioData['ID_Usuario'];
    $usuarioModel->usuario = $usuarioData['Usuario'];
    $usuarioModel->rol = $usuarioData['Rol'];
    
} catch (Exception $e) {
    header("Location: index.php?error=" . urlencode($e->getMessage()));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $usuarioModel->id_usuario = $id;
        $usuarioModel->usuario = sanitizeInput($_POST['usuario']);
        $usuarioModel->rol = sanitizeInput($_POST['rol']);
        $validRoles = ['Administrador','Contador','Trabajador'];
        if (!in_array($usuarioModel->rol, $validRoles)) {
            throw new Exception('Rol inválido');
        }
        
        // Solo actualizar contraseña si se proporcionó una nueva
        if (!empty($_POST['contrasena'])) {
            // Asignar contraseña en texto plano; el modelo se encarga de hashear
            $usuarioModel->contrasena = $_POST['contrasena'];
        }
        
        if ($usuarioModel->update()) {
            logActivity($_SESSION['user_id'], "Usuario editado: " . $usuarioModel->usuario);
            header("Location: index.php?success=updated");
            exit();
        } else {
            $error = "Error al actualizar el usuario";
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-user-edit"></i> Editar Usuario
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
                                           value="<?php echo htmlspecialchars($usuarioModel->usuario); ?>">
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
                                            <option value="<?php echo $value; ?>" <?php echo ($usuarioModel->rol == $value) ? 'selected' : ''; ?>>
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
                                        <i class="fas fa-lock"></i> Nueva Contraseña
                                    </label>
                                    <input type="password" class="form-control" id="contrasena" name="contrasena" 
                                           minlength="6">
                                    <div class="form-text">Dejar vacío para mantener la contraseña actual</div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="confirmar_contrasena" class="form-label">
                                        <i class="fas fa-lock"></i> Confirmar Nueva Contraseña
                                    </label>
                                    <input type="password" class="form-control" id="confirmar_contrasena" name="confirmar_contrasena" 
                                           minlength="6">
                                </div>
                            </div>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="index.php" class="btn btn-secondary me-md-2">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Actualizar Usuario
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
                    <p><strong>ID:</strong> <?php echo $usuarioModel->id_usuario ?? $id; ?></p>
                    <p><strong>Usuario Actual:</strong> <?php echo htmlspecialchars($usuarioModel->usuario); ?></p>
                    <p><strong>Rol Actual:</strong> 
                        <span class="badge <?php echo $usuarioModel->rol == 'Administrador' ? 'bg-primary' : ($usuarioModel->rol == 'Contador' ? 'bg-info' : 'bg-success'); ?>">
                            <?php echo htmlspecialchars($usuarioModel->rol); ?>
                        </span>
                    </p>
                    
                    <hr>
                    
                    <h6><i class="fas fa-shield-alt text-warning"></i> Seguridad</h6>
                    <p class="small text-muted">
                        Solo complete los campos de contraseña si desea cambiarla.
                    </p>
                    
                    <?php if ($id == $_SESSION['user_id']): ?>
                    <div class="alert alert-warning alert-sm">
                        <i class="fas fa-exclamation-triangle"></i> Está editando su propio usuario
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('usuarioForm').addEventListener('submit', function(e) {
    const contrasena = document.getElementById('contrasena').value;
    const confirmarContrasena = document.getElementById('confirmar_contrasena').value;
    
    // Solo validar si se ingresó una contraseña
    if (contrasena && contrasena !== confirmarContrasena) {
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
