@echo off
echo ========================================
echo Sistema de Gestion Empresarial - Setup
echo ========================================
echo.

echo 1. Verificando estructura de directorios...
if not exist "config\" (
    echo ERROR: Directorio config no encontrado
    echo Por favor, ejecute este script desde la raiz del proyecto
    pause
    exit /b 1
)

echo 2. Verificando archivos principales...
if not exist "index.php" (
    echo ERROR: Archivo index.php no encontrado
    pause
    exit /b 1
)

echo 3. Verificando permisos de escritura...
echo test > "reports\test.txt" 2>nul
if exist "reports\test.txt" (
    del "reports\test.txt" >nul 2>&1
    echo    - Directorio reports: OK
) else (
    echo    - ADVERTENCIA: No se puede escribir en directorio reports
)

echo.
echo ========================================
echo PASOS PARA COMPLETAR LA INSTALACION:
echo ========================================
echo.
echo 1. Configurar base de datos:
echo    - Crear base de datos 'SistemaGestionEmpresa'
echo    - Importar: database\schema.sql
echo.
echo 2. Configurar conexion en config\config.php:
echo    - DB_HOST (por defecto: localhost)
echo    - DB_USER (usuario MySQL)
echo    - DB_PASS (contraseña MySQL)
echo.
echo 3. Ajustar URL del sitio en config\config.php:
echo    - SITE_URL (ej: https://caa-i0xf.onrender.com/SistemaKris)
echo.
echo 4. Primer acceso:
echo    - Usuario: admin
echo    - Contraseña: password
echo    - CAMBIAR contraseña después del primer login
echo.
echo ========================================
echo ESTRUCTURA CREADA EXITOSAMENTE
echo ========================================
echo.
echo Archivos principales:
echo - index.php (Login)
echo - dashboard.php (Panel principal)
echo - config\config.php (Configuracion)
echo - models\ (Clases de datos)
echo - views\ (Interfaces de usuario)
echo - includes\ (Archivos comunes)
echo.
echo Funcionalidades implementadas:
echo - ✓ Sistema de autenticacion
echo - ✓ Dashboard con estadisticas
echo - ✓ Gestion completa de clientes
echo - ✓ Estructura base para trabajos
echo - ✓ Estructura base para inventario
echo - ✓ Sistema de bitacora
echo.
echo Pendiente de completar:
echo - Vistas CRUD para trabajos
echo - Vistas CRUD para inventario
echo - Sistema completo de reportes
echo - Gestion de usuarios
echo.
echo Para mas informacion, consulte README.md
echo.
pause
