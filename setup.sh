#!/bin/bash

echo "========================================"
echo "Sistema de Gestión Empresarial - Setup"
echo "========================================"
echo

echo "1. Verificando estructura de directorios..."
if [ ! -d "config" ]; then
    echo "ERROR: Directorio config no encontrado"
    echo "Por favor, ejecute este script desde la raíz del proyecto"
    exit 1
fi

echo "2. Verificando archivos principales..."
if [ ! -f "index.php" ]; then
    echo "ERROR: Archivo index.php no encontrado"
    exit 1
fi

echo "3. Configurando permisos..."
chmod 755 .
chmod 755 views/
chmod 755 reports/
chmod 644 config/config.php
chmod 644 *.php

echo "   - Permisos configurados correctamente"

echo
echo "========================================"
echo "PASOS PARA COMPLETAR LA INSTALACIÓN:"
echo "========================================"
echo
echo "1. Configurar base de datos:"
echo "   mysql -u root -p -e 'CREATE DATABASE SistemaGestionEmpresa;'"
echo "   mysql -u root -p SistemaGestionEmpresa < database/schema.sql"
echo
echo "2. Configurar conexión en config/config.php:"
echo "   - DB_HOST (por defecto: localhost)"
echo "   - DB_USER (usuario MySQL)"
echo "   - DB_PASS (contraseña MySQL)"
echo
echo "3. Ajustar URL del sitio en config/config.php:"
echo "   - SITE_URL (ej: https://caa-i0xf.onrender.com/SistemaKris)"
echo
echo "4. Configurar servidor web:"
echo "   - Apache: Copiar a /var/www/html/"
echo "   - Nginx: Configurar virtual host"
echo
echo "5. Primer acceso:"
echo "   - Usuario: admin"
echo "   - Contraseña: password"
echo "   - CAMBIAR contraseña después del primer login"
echo
echo "========================================"
echo "ESTRUCTURA CREADA EXITOSAMENTE"
echo "========================================"
echo
echo "Para más información, consulte README.md"
echo
