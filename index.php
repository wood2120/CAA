<?php
require_once 'includes/functions.php';

// Si ya está logueado, redirigir al dashboard
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$pageTitle = 'Inicio de Sesión';
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row min-vh-100">
        <!-- Imagen de fondo -->
        <div class="col-md-6 d-none d-md-block p-0">
            <div class="bg-primary d-flex align-items-center justify-content-center min-vh-100">
                <div class="text-center text-white">
                    <i class="fas fa-building fa-5x mb-4"></i>
                    <h2>CAA</h2>
                    <p class="lead">Sistema de gestion empresarial</p>
                </div>
            </div>
        </div>
        
        <!-- Formulario de login -->
        <div class="col-md-6 d-flex align-items-center">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <div class="card shadow">
                            <div class="card-body p-5">
                                <div class="text-center mb-4">
                                    <h3 class="card-title">Iniciar Sesión</h3>
                                    <p class="text-muted">Ingrese sus credenciales para acceder al sistema</p>
                                </div>

                                <?php if (isset($_GET['error'])): ?>
                                    <div class="alert alert-danger">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        <?php 
                                        switch($_GET['error']) {
                                            case 'invalid_credentials': echo 'Usuario o contraseña incorrectos.'; break;
                                            case 'empty_fields': echo 'Por favor, complete todos los campos.'; break;
                                            case 'system_error': echo 'Error del sistema. Inténtelo más tarde.'; break;
                                            default: echo 'Error al iniciar sesión.';
                                        }
                                        ?>
                                    </div>
                                <?php endif; ?>

                                <?php if (isset($_GET['success']) && $_GET['success'] === 'registered'): ?>
                                    <div class="alert alert-success">
                                        <i class="fas fa-check"></i> ¡Cuenta creada exitosamente! Ya puede iniciar sesión con sus credenciales.
                                    </div>
                                <?php endif; ?>

                                <?php if (isset($_GET['timeout'])): ?>
                                    <div class="alert alert-warning">
                                        <i class="fas fa-clock"></i> Su sesión ha expirado. Por favor, inicie sesión nuevamente.
                                    </div>
                                <?php endif; ?>

                                <?php if (isset($_GET['logout'])): ?>
                                    <div class="alert alert-success">
                                        <i class="fas fa-check"></i> Ha cerrado sesión correctamente.
                                    </div>
                                <?php endif; ?>

                                <form id="loginForm" action="controllers/auth.php" method="POST">
                                    <div class="mb-3">
                                        <label for="usuario" class="form-label">Usuario</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="fas fa-user"></i>
                                            </span>
                                            <input type="text" class="form-control" id="usuario" name="usuario" required>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="contrasena" class="form-label">Contraseña</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="fas fa-lock"></i>
                                            </span>
                                            <input type="password" class="form-control" id="contrasena" name="contrasena" required>
                                            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="d-grid gap-2">
                                        <button type="submit" class="btn btn-primary btn-lg">
                                            <i class="fas fa-sign-in-alt"></i> Ingresar
                                        </button>
                                        <button type="button" class="btn btn-secondary" onclick="clearForm()">
                                            <i class="fas fa-times"></i> Cancelar
                                        </button>
                                    </div>
                                </form>

                                
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle password visibility
    document.getElementById('togglePassword').addEventListener('click', function() {
        const password = document.getElementById('contrasena');
        const icon = this.querySelector('i');
        
        if (password.type === 'password') {
            password.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            password.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    });

    // Form validation
    document.getElementById('loginForm').addEventListener('submit', function(e) {
        const usuario = document.getElementById('usuario').value.trim();
        const contrasena = document.getElementById('contrasena').value;

        if (usuario === '' || contrasena === '') {
            e.preventDefault();
            alert('Por favor, complete todos los campos.');
            return false;
        }
    });
});

function clearForm() {
    document.getElementById('usuario').value = '';
    document.getElementById('contrasena').value = '';
    document.getElementById('usuario').focus();
}
</script>

<?php include 'includes/footer.php'; ?>
