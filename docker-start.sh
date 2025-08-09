#!/bin/bash

# Script de inicio rápido para Sistema de Gestión Empresarial
# Uso: ./docker-start.sh [desarrollo|produccion]

set -e

ENVIRONMENT=${1:-desarrollo}

echo "🚀 Iniciando Sistema de Gestión Empresarial en modo: $ENVIRONMENT"

# Verificar si Docker está instalado
if ! command -v docker &> /dev/null; then
    echo "❌ Docker no está instalado. Por favor instala Docker primero."
    exit 1
fi

# Verificar si Docker Compose está instalado
if ! command -v docker-compose &> /dev/null; then
    echo "❌ Docker Compose no está instalado. Por favor instala Docker Compose primero."
    exit 1
fi

# Crear directorios necesarios
echo "📁 Creando directorios necesarios..."
mkdir -p reports uploads

# Configurar archivo de entorno
if [ "$ENVIRONMENT" = "produccion" ]; then
    echo "🔧 Configurando para producción..."
    if [ ! -f .env.production ]; then
        cp .env.docker .env.production
        sed -i 's/DEBUG_MODE=false/DEBUG_MODE=false/g' .env.production
        sed -i 's/SESSION_SECURE=false/SESSION_SECURE=true/g' .env.production
        echo "⚠️  Por favor edita .env.production con tus configuraciones de producción"
    fi
    export COMPOSE_FILE=docker-compose.yml:docker-compose.prod.yml
else
    echo "🔧 Configurando para desarrollo..."
    export COMPOSE_FILE=docker-compose.yml
fi

# Construir y levantar servicios
echo "🏗️  Construyendo y levantando servicios..."
docker-compose up -d --build

# Esperar a que la base de datos esté lista
echo "⏳ Esperando a que la base de datos esté lista..."
sleep 10

# Verificar estado de servicios
echo "✅ Verificando estado de servicios..."
docker-compose ps

echo ""
echo "🎉 Sistema iniciado exitosamente!"
echo ""
echo "📍 Servicios disponibles:"
echo "   • Aplicación Web: http://localhost:8080"
echo "   • phpMyAdmin: http://localhost:8081"
echo "   • Base de Datos: localhost:3307"
echo ""
echo "🔑 Credenciales por defecto:"
echo "   • Usuario DB: root"
echo "   • Contraseña DB: rootpassword"
echo ""
echo "📋 Comandos útiles:"
echo "   • Ver logs: docker-compose logs"
echo "   • Parar servicios: docker-compose stop"
echo "   • Acceder al contenedor: docker-compose exec web bash"
echo ""
echo "✨ ¡Listo para usar!"
