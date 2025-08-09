@echo off
REM Script de inicio rápido para Sistema de Gestión Empresarial
REM Uso: docker-start.bat [desarrollo|produccion|render]

setlocal enabledelayedexpansion

set ENVIRONMENT=%1
if "%ENVIRONMENT%"=="" set ENVIRONMENT=desarrollo

echo 🚀 Iniciando Sistema de Gestión Empresarial en modo: %ENVIRONMENT%

REM Verificar si Docker está instalado
docker --version >nul 2>&1
if errorlevel 1 (
    echo ❌ Docker no está instalado. Por favor instala Docker primero.
    pause
    exit /b 1
)

REM Verificar si Docker Compose está instalado (solo para desarrollo/producción)
if not "%ENVIRONMENT%"=="render" (
    docker-compose --version >nul 2>&1
    if errorlevel 1 (
        echo ❌ Docker Compose no está instalado. Por favor instala Docker Compose primero.
        pause
        exit /b 1
    )
)

REM Crear directorios necesarios
echo 📁 Creando directorios necesarios...
if not exist "reports" mkdir reports
if not exist "uploads" mkdir uploads
if not exist "logs" mkdir logs

REM Configurar según el entorno
if "%ENVIRONMENT%"=="render" (
    echo 🔧 Configurando para Render.com...
    echo 🏗️  Construyendo imagen para Render...
    docker build -f Dockerfile.render -t sistema-kris:render .
    echo ✅ Imagen construida para Render
    echo 📋 Para desplegar en Render, usa: sistema-kris:render
) else if "%ENVIRONMENT%"=="produccion" (
    echo 🔧 Configurando para producción...
    if not exist ".env.production" (
        copy ".env.docker" ".env.production"
        echo ⚠️  Por favor edita .env.production con tus configuraciones de producción
    )
    docker-compose up -d --build
) else (
    echo 🔧 Configurando para desarrollo...
    docker-compose up -d --build
)

if not "%ENVIRONMENT%"=="render" (
    REM Esperar a que la base de datos esté lista
    echo ⏳ Esperando a que la base de datos esté lista...
    timeout /t 10 /nobreak >nul

    REM Verificar estado de servicios
    echo ✅ Verificando estado de servicios...
    docker-compose ps

    echo.
    echo 🎉 Sistema iniciado exitosamente!
    echo.
    echo 📍 Servicios disponibles:
    echo    • Aplicación Web: http://localhost:8080
    echo    • phpMyAdmin: http://localhost:8081
    echo    • Base de Datos: localhost:3307
    echo.
    echo 🔑 Credenciales por defecto:
    echo    • Usuario DB: root
    echo    • Contraseña DB: rootpassword
    echo.
    echo 📋 Comandos útiles:
    echo    • Ver logs: docker-compose logs
    echo    • Parar servicios: docker-compose stop
    echo    • Acceder al contenedor: docker-compose exec web bash
)

echo.
echo ✨ ¡Listo para usar!
echo.
pause
