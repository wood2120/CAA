<?php
require_once 'includes/functions.php';

// Si ya está logueado, redirigir al dashboard
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

// Procesar errores y datos del formulario si vienen de vuelta
$errors = [];
$formData = [];

if (isset($_GET['errors'])) {
    $errors = explode('|', $_GET['errors']);
}

// Mantener datos del formulario
$formData = [
    'usuario' => $_GET['usuario'] ?? ''
];

$pageTitle = 'Registro de Usuario';
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row min-vh-100">
        <!-- Imagen de fondo -->
        <div class="col-md-6 d-none d-md-block p-0">
            <div class="bg-success d-flex align-items-center justify-content-center min-vh-100">
                <div class="text-center text-white">
                    <i class="fas fa-user-plus fa-5x mb-4"></i>
                    <h2><?php echo SITE_NAME; ?></h2>
                    <p class="lead">Únete a nuestro sistema de gestión empresarial</p>
                    <div class="mt-4">
                        <h5>Beneficios del registro:</h5>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-check me-2"></i> Gestión completa de clientes</li>
                            <li><i class="fas fa-check me-2"></i> Control de trabajos y proyectos</li>
                            <li><i class="fas fa-check me-2"></i> Administración de inventario</li>
                            <li><i class="fas fa-check me-2"></i> Reportes y estadísticas</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Formulario de registro -->
        <div class="col-md-6 d-flex align-items-center">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-9">
                        <div class="card shadow">
                            <div class="card-body p-5">
                                <div class="text-center mb-4">
                                    <h3 class="card-title">Crear Cuenta</h3>
                                    <p class="text-muted">Complete el formulario para registrarse</p>
                                </div>

                                <!-- Mensajes de estado -->
                                <div id="alertContainer">
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
                                </div>

                                <form id="registerForm" action="controllers/register.php" method="POST">
                                    <div class="mb-3">
                                        <label for="usuario" class="form-label">
                                            Nombre de Usuario <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="fas fa-user"></i>
                                            </span>
                                            <input type="text" class="form-control" id="usuario" name="usuario" 
                                                   required maxlength="50" placeholder="Nombre de usuario único"
                                                   value="<?php echo htmlspecialchars($formData['usuario']); ?>">
                                        </div>
                                        <div class="form-text">
                                            Mínimo 4 caracteres, solo letras, números y guión bajo
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="contrasena" class="form-label">
                                            Contraseña <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="fas fa-lock"></i>
                                            </span>
                                            <input type="password" class="form-control" id="contrasena" name="contrasena" 
                                                   required minlength="6" placeholder="Mínimo 6 caracteres">
                                            <button class="btn btn-outline-secondary" type="button" id="togglePassword1">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                        <div class="form-text">
                                            Mínimo 6 caracteres, incluya letras y números
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="confirmar_contrasena" class="form-label">
                                            Confirmar Contraseña <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="fas fa-lock"></i>
                                            </span>
                                            <input type="password" class="form-control" id="confirmar_contrasena" 
                                                   name="confirmar_contrasena" required minlength="6" 
                                                   placeholder="Repita la contraseña">
                                            <button class="btn btn-outline-secondary" type="button" id="togglePassword2">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="acepto_terminos" 
                                                   name="acepto_terminos" required>
                                            <label class="form-check-label" for="acepto_terminos">
                                                Acepto los <a href="#" data-bs-toggle="modal" data-bs-target="#terminosModal">términos y condiciones</a> <span class="text-danger">*</span>
                                            </label>
                                        </div>
                                    </div>

                                    <div class="d-grid gap-2">
                                        <button type="submit" class="btn btn-success btn-lg" id="btnRegistrar">
                                            <i class="fas fa-user-plus"></i> Crear Cuenta
                                        </button>
                                        <button type="button" class="btn btn-secondary" onclick="clearForm()">
                                            <i class="fas fa-broom"></i> Limpiar Formulario
                                        </button>
                                    </div>
                                </form>

                                <div class="text-center mt-4">
                                    <div class="mb-2">
                                        <a href="index.php" class="text-decoration-none">
                                            <i class="fas fa-arrow-left"></i> ¿Ya tienes cuenta? Inicia sesión
                                        </a>
                                    </div>
                                    <small class="text-muted">
                                        © <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Términos y Condiciones -->
<div class="modal fade" id="terminosModal" tabindex="-1" aria-labelledby="terminosModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="terminosModalLabel">Términos y Condiciones</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h6>1. Aceptación de los términos</h6>
                <p>Al utilizar este sistema, usted acepta cumplir con estos términos y condiciones.</p>
                
                <h6>2. Uso del sistema</h6>
                <p>El sistema está diseñado para la gestión empresarial y debe ser utilizado de manera responsable.</p>
                
                <h6>3. Privacidad de datos</h6>
                <p>Sus datos personales serán protegidos según las leyes de privacidad vigentes.</p>
                
                <h6>4. Responsabilidades del usuario</h6>
                <p>Usted es responsable de mantener la confidencialidad de su cuenta y contraseña.</p>
                
                <h6>5. Limitaciones</h6>
                <p>El sistema se proporciona "tal como está" sin garantías de ningún tipo.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle password visibility
    document.getElementById('togglePassword1').addEventListener('click', function() {
        togglePasswordVisibility('contrasena', this);
    });
    
    document.getElementById('togglePassword2').addEventListener('click', function() {
        togglePasswordVisibility('confirmar_contrasena', this);
    });

    // Validación en tiempo real del nombre de usuario
    document.getElementById('usuario').addEventListener('input', function() {
        const usuario = this.value;
        const regex = /^[a-zA-Z0-9_]{4,}$/;
        
        if (usuario.length > 0 && !regex.test(usuario)) {
            this.classList.add('is-invalid');
            showFieldError(this, 'Solo letras, números y guión bajo. Mínimo 4 caracteres.');
        } else {
            this.classList.remove('is-invalid');
            clearFieldError(this);
        }
    });

    // Validación de contraseña
    document.getElementById('contrasena').addEventListener('input', function() {
        validatePassword();
    });

    document.getElementById('confirmar_contrasena').addEventListener('input', function() {
        validatePasswordConfirmation();
    });

    // Formatear teléfono
    // document.getElementById('telefono').addEventListener('input', function() {
    //     this.value = Utils.formatPhone(this.value);
    // });

    // Validación del formulario
    document.getElementById('registerForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (validateRegisterForm()) {
            // Deshabilitar botón para evitar doble envío
            const btnRegistrar = document.getElementById('btnRegistrar');
            btnRegistrar.disabled = true;
            btnRegistrar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creando cuenta...';
            
            // Enviar formulario
            this.submit();
        }
    });

    // Auto-focus en el primer campo
    document.getElementById('usuario').focus();
});

function togglePasswordVisibility(fieldId, button) {
    const field = document.getElementById(fieldId);
    const icon = button.querySelector('i');
    
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

function validatePassword() {
    const password = document.getElementById('contrasena');
    const value = password.value;
    
    if (value.length > 0 && value.length < 6) {
        password.classList.add('is-invalid');
        showFieldError(password, 'La contraseña debe tener al menos 6 caracteres.');
        return false;
    } else if (value.length >= 6) {
        password.classList.remove('is-invalid');
        password.classList.add('is-valid');
        clearFieldError(password);
        
        // Validar confirmación si ya tiene valor
        validatePasswordConfirmation();
        return true;
    }
    return false;
}

function validatePasswordConfirmation() {
    const password = document.getElementById('contrasena').value;
    const confirmation = document.getElementById('confirmar_contrasena');
    const confirmValue = confirmation.value;
    
    if (confirmValue.length > 0) {
        if (password !== confirmValue) {
            confirmation.classList.add('is-invalid');
            showFieldError(confirmation, 'Las contraseñas no coinciden.');
            return false;
        } else {
            confirmation.classList.remove('is-invalid');
            confirmation.classList.add('is-valid');
            clearFieldError(confirmation);
            return true;
        }
    }
    return false;
}

function validateRegisterForm() {
    const form = document.getElementById('registerForm');
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;

    // Limpiar alertas previas
    document.getElementById('alertContainer').innerHTML = '';

    // Validar campos requeridos
    requiredFields.forEach(function(field) {
        if (!field.value.trim()) {
            field.classList.add('is-invalid');
            isValid = false;
        } else {
            field.classList.remove('is-invalid');
        }
    });

    // Validaciones específicas
    const usuario = document.getElementById('usuario').value;
    const regex = /^[a-zA-Z0-9_]{4,}$/;
    if (!regex.test(usuario)) {
        document.getElementById('usuario').classList.add('is-invalid');
        isValid = false;
    }

    // Validar email
    // const email = document.getElementById('email').value;
    // const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    // if (!emailRegex.test(email)) {
    //     document.getElementById('email').classList.add('is-invalid');
    //     isValid = false;
    // }

    // Validar contraseñas
    if (!validatePassword() || !validatePasswordConfirmation()) {
        isValid = false;
    }

    // Validar términos
    if (!document.getElementById('acepto_terminos').checked) {
        showAlert('Debe aceptar los términos y condiciones.', 'warning');
        isValid = false;
    }

    if (!isValid) {
        showAlert('Por favor, corrija los errores en el formulario.', 'danger');
    }

    return isValid;
}

function showFieldError(field, message) {
    // Remover error previo
    clearFieldError(field);
    
    // Agregar nuevo error
    const errorDiv = document.createElement('div');
    errorDiv.className = 'invalid-feedback';
    errorDiv.textContent = message;
    field.parentNode.appendChild(errorDiv);
}

function clearFieldError(field) {
    const existingError = field.parentNode.querySelector('.invalid-feedback');
    if (existingError) {
        existingError.remove();
    }
}

function showAlert(message, type) {
    const alertContainer = document.getElementById('alertContainer');
    const alertHTML = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    alertContainer.innerHTML = alertHTML;
}

function clearForm() {
    if (confirm('¿Está seguro de que desea limpiar todos los campos?')) {
        document.getElementById('registerForm').reset();
        
        // Limpiar clases de validación
        const fields = document.querySelectorAll('.is-valid, .is-invalid');
        fields.forEach(field => {
            field.classList.remove('is-valid', 'is-invalid');
        });
        
        // Limpiar errores
        const errors = document.querySelectorAll('.invalid-feedback');
        errors.forEach(error => error.remove());
        
        // Limpiar alertas
        document.getElementById('alertContainer').innerHTML = '';
        
        // Focus en primer campo
        document.getElementById('usuario').focus();
    }
}
</script>

<?php include 'includes/footer.php'; ?>
