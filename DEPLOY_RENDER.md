# Guía de Despliegue en Render.com

Esta guía te ayudará a desplegar el Sistema de Gestión Empresarial en Render.com.

## 📋 Requisitos Previos

1. **Cuenta en Render.com**
2. **Repositorio Git** con tu código
3. **Base de datos MySQL** (puedes usar Render PostgreSQL como alternativa)

## 🚀 Pasos para Desplegar

### 1. Preparar el Código

```bash
# Construir imagen local para verificar (opcional)
docker-start.bat render
```

### 2. Configurar en Render.com

#### **Crear Web Service:**

1. Ve a [Render Dashboard](https://dashboard.render.com)
2. Clic en **"New +"** → **"Web Service"**
3. Conecta tu repositorio Git
4. Configuración:
   - **Name**: `sistema-gestion-empresarial`
   - **Environment**: `Docker`
   - **Dockerfile Path**: `Dockerfile.render`
   - **Instance Type**: `Free` o `Starter`

#### **Variables de Entorno:**

Configura estas variables en Render:

```env
# Base de datos (ajustar según tu configuración)
DB_HOST=tu-host-de-base-de-datos
DB_PORT=3306
DB_NAME=SistemaGestionEmpresa
DB_USER=tu-usuario
DB_PASS=tu-contraseña

# Configuración del sitio
SITE_NAME=Sistema de Gestión Empresarial
DEBUG_MODE=false
SESSION_SECURE=true
SESSION_HTTPONLY=true
TIMEZONE=America/Costa_Rica
```

### 3. Configurar Base de Datos

#### **Opción A: MySQL Externo**

Puedes usar servicios como:
- **PlanetScale** (recomendado)
- **Railway**
- **AWS RDS**
- **DigitalOcean Databases**

#### **Opción B: PostgreSQL en Render**

1. Crear **PostgreSQL Database** en Render
2. Modificar el código para usar PostgreSQL en lugar de MySQL
3. Instalar extensión `pdo_pgsql` en el Dockerfile

### 4. Archivos de Configuración

#### **Dockerfile.render** (ya incluido)
- Configuración optimizada para Render
- Módulos Apache condicionales
- Configuración de seguridad para producción

#### **.env.render** (ya incluido)
- Variables de entorno específicas para Render
- Configuración de seguridad HTTPS

### 5. Configuraciones Adicionales

#### **Health Check**
Render verificará automáticamente que tu aplicación responda en el puerto 80.

#### **Persistencia de Archivos**
Para `uploads/` y `reports/`, considera usar:
- **Cloudinary** para imágenes
- **AWS S3** para archivos
- **Render Disks** (próximamente)

#### **Logs**
Los logs se pueden ver en el Render Dashboard o conectar con servicios como **LogTail**.

## 🔧 Comandos de Despliegue

### Despliegue Automático
Render desplegará automáticamente cuando hagas push a tu rama principal.

### Despliegue Manual
```bash
# En Render Dashboard
1. Ve a tu servicio
2. Clic en "Manual Deploy"
3. Selecciona la rama a desplegar
```

### Ver Logs
```bash
# En Render Dashboard
1. Ve a tu servicio
2. Clic en "Logs"
3. Ver logs en tiempo real
```

## 📊 URLs de Acceso

Después del despliegue:
- **Aplicación**: `https://tu-servicio.onrender.com`
- **Base de datos**: Según el proveedor elegido

## 🛠️ Solución de Problemas

### **Error: "Invalid command 'ExpiresActive'"**
✅ **Solucionado**: El `Dockerfile.render` usa configuración simple sin módulos opcionales.

### **Error de Conexión a Base de Datos**
1. Verificar variables de entorno en Render
2. Comprobar que la base de datos esté accesible desde Render
3. Verificar credenciales

### **Error 500 - Internal Server Error**
1. Verificar logs en Render Dashboard
2. Comprobar que `DEBUG_MODE=true` temporalmente para ver errores
3. Verificar permisos de directorios

### **Archivos No Se Guardan**
1. Los archivos en Render son efímeros
2. Usar almacenamiento externo (S3, Cloudinary)
3. O configurar Render Disks

### **Sesiones Se Pierden**
1. Configurar `SESSION_SECURE=true` para HTTPS
2. Verificar configuración de cookies
3. Considerar usar Redis para sesiones

## 🚀 Optimizaciones para Producción

### **Performance**
```dockerfile
# En Dockerfile.render (ya incluido)
- Configuración de cache
- Compresión gzip
- Headers de seguridad
```

### **Seguridad**
```env
# Variables de entorno (ya configuradas)
DEBUG_MODE=false
SESSION_SECURE=true
SESSION_HTTPONLY=true
```

### **Monitoreo**
- Configura **UptimeRobot** para monitoreo
- Usa **Sentry** para tracking de errores
- Configura alertas en Render

## 📝 Checklist de Despliegue

- [ ] Código en repositorio Git
- [ ] Variables de entorno configuradas
- [ ] Base de datos configurada y accesible
- [ ] Dockerfile.render funcional
- [ ] Configuración de archivos estáticos (si se necesita)
- [ ] SSL/HTTPS habilitado (automático en Render)
- [ ] Pruebas de funcionalidad
- [ ] Configuración de dominio personalizado (opcional)

## 🆘 Soporte

Si tienes problemas:

1. **Revisar logs** en Render Dashboard
2. **Verificar configuración** de variables de entorno
3. **Probar localmente** con `docker-start.bat render`
4. **Consultar documentación** de Render.com

---

**¡Tu aplicación estará lista en pocos minutos!** 🎉
