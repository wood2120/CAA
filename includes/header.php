<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' . SITE_NAME : SITE_NAME; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?php echo SITE_URL; ?>/assets/css/style.css" rel="stylesheet">
    
    <?php if (isset($additionalCSS)): ?>
        <?php foreach ($additionalCSS as $css): ?>
            <link href="<?php echo $css; ?>" rel="stylesheet">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
    <?php if (isLoggedIn()): ?>
        <!-- Navigation -->
        <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
            <div class="container-fluid">
                <a class="navbar-brand" href="<?php echo SITE_URL; ?>/dashboard.php">
                    <i class="fas fa-building"></i> <?php echo SITE_NAME; ?>
                </a>
                
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto">
                        <!-- Inicio: visible para todos los roles -->
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo SITE_URL; ?>/dashboard.php">
                                <i class="fas fa-home"></i> Inicio
                            </a>
                        </li>
                        <!-- Clientes: visible para Administrador y Trabajador -->
                        <?php if (hasAnyRole(['Administrador','Trabajador'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo SITE_URL; ?>/views/clientes/index.php">
                                <i class="fas fa-users"></i> Clientes
                            </a>
                        </li>
                        <?php endif; ?>
                        <!-- Trabajos: visible para todos excepto (ninguno) -->
                        <?php if (hasAnyRole(['Administrador','Trabajador','Contador'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo SITE_URL; ?>/views/trabajos/index.php">
                                <i class="fas fa-briefcase"></i> Trabajos
                            </a>
                        </li>
                        <?php endif; ?>
                        <!-- Inventario: visible para Administrador y Trabajador -->
                        <?php if (hasAnyRole(['Administrador','Trabajador'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo SITE_URL; ?>/views/inventario/index.php">
                                <i class="fas fa-boxes"></i> Inventario
                            </a>
                        </li>
                        <?php endif; ?>
                        <!-- Proveedores: solo Administrador -->
                        <?php if (hasRole('Administrador')): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo SITE_URL; ?>/views/proveedores/index.php">
                                <i class="fas fa-truck"></i> Proveedores
                            </a>
                        </li>
                        <?php endif; ?>
                        <!-- Categorias: solo Administrador -->
                        <?php if (hasRole('Administrador')): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo SITE_URL; ?>/views/categorias/index.php">
                                <i class="fas fa-tags"></i> Categorías
                            </a>
                        </li>
                        <?php endif; ?>
                        <!-- Usuarios: solo Administrador -->
                        <?php if (hasRole('Administrador')): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo SITE_URL; ?>/views/usuarios/index.php">
                                <i class="fas fa-user-cog"></i> Usuarios
                            </a>
                        </li>
                        <?php endif; ?>
                        <!-- Reportes: Administrador y Contador -->
                        <?php if (hasAnyRole(['Administrador','Contador'])): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="reportesDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-chart-bar"></i> Reportes
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/views/reportes/clientes.php">Clientes</a></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/views/reportes/trabajos.php">Trabajos</a></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/views/reportes/inventario.php">Inventario</a></li>
                                <?php if (hasRole('Administrador')): ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/views/reportes/bitacora.php">Bitácora</a></li>
                                <?php endif; ?>
                            </ul>
                        </li>
                        <?php endif; ?>
                    </ul>
                    
                    <ul class="navbar-nav">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user"></i> <?php echo $_SESSION['username']; ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/views/perfil.php">Mi Perfil</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/logout.php">Cerrar Sesión</a></li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    <?php endif; ?>
    
    <main class="<?php echo isLoggedIn() ? 'container-fluid mt-4' : ''; ?>">
