# README - Sistema de Gestión Empresarial

## Descripción
Sistema web desarrollado en PHP para la gestión integral de una empresa, incluyendo manejo de clientes, trabajos, inventario y generación de reportes.

## Estructura del Proyecto

```
SistemaKris/
├── config/                 # Configuraciones del sistema
│   ├── config.php         # Configuración general
│   └── database.php       # Configuración de base de datos
├── includes/               # Archivos comunes
│   ├── functions.php      # Funciones útiles
│   ├── header.php         # Encabezado común
│   └── footer.php         # Pie de página común
├── models/                 # Modelos de datos
│   ├── Usuario.php        # Modelo de usuarios
│   ├── Cliente.php        # Modelo de clientes
│   ├── Trabajo.php        # Modelo de trabajos
│   ├── Inventario.php     # Modelo de inventario
│   └── Bitacora.php       # Modelo de bitácora
├── views/                  # Vistas del sistema
│   ├── clientes/          # Gestión de clientes
│   ├── trabajos/          # Gestión de trabajos
│   ├── inventario/        # Gestión de inventario
│   ├── reportes/          # Generación de reportes
│   └── usuarios/          # Gestión de usuarios
├── controllers/            # Controladores
│   ├── auth.php           # Autenticación
│   └── check_session.php  # Verificación de sesión
├── assets/                 # Recursos estáticos
│   ├── css/               # Hojas de estilo
│   ├── js/                # JavaScript
│   └── images/            # Imágenes
├── database/               # Scripts de base de datos
│   └── schema.sql         # Esquema de la base de datos
├── reports/                # Reportes generados
├── index.php               # Página de inicio/login
├── dashboard.php           # Dashboard principal
└── logout.php              # Cerrar sesión
```

## Requisitos del Sistema

### Software Requerido
- **Servidor Web**: Apache 2.4+ o Nginx
- **PHP**: Versión 7.4 o superior
- **Base de Datos**: MySQL 5.7+ o MariaDB 10.3+
- **Extensiones PHP**: PDO, PDO_MySQL, session, json

### Requisitos Recomendados
- **Memoria PHP**: Mínimo 128MB
- **Espacio en Disco**: Mínimo 100MB
- **Navegadores Compatibles**: Chrome 90+, Firefox 88+, Edge 90+, Safari 14+

## Instalación

### 1. Preparación del Entorno

```bash
# En Windows con XAMPP
# Colocar los archivos en: C:\xampp\htdocs\SistemaKris\

# En Linux con Apache
sudo cp -r SistemaKris/ /var/www/html/
sudo chown -R www-data:www-data /var/www/html/SistemaKris/
sudo chmod -R 755 /var/www/html/SistemaKris/
```

### 2. Configuración de la Base de Datos

1. **Crear la base de datos**:
   ```sql
   CREATE DATABASE SistemaGestionEmpresa;
   ```

2. **Importar el esquema**:
   ```bash
   mysql -u root -p SistemaGestionEmpresa < database/schema.sql
   ```

3. **Configurar la conexión** en `config/config.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'SistemaGestionEmpresa');
   define('DB_USER', 'tu_usuario');
   define('DB_PASS', 'tu_contraseña');
   ```

### 3. Configuración del Sistema

1. **Ajustar configuraciones** en `config/config.php`:
   ```php
   define('SITE_URL', 'https://caa-i0xf.onrender.com/SistemaKris');
   define('DEBUG_MODE', false); // Cambiar a false en producción
   ```

2. **Configurar permisos** (Linux):
   ```bash
   chmod 755 reports/
   chmod 644 config/config.php
   ```

### 4. Primer Acceso

- **URL**: `https://caa-i0xf.onrender.com/SistemaKris/`
- **Usuario**: `admin`
- **Contraseña**: `password`

> **Importante**: Cambiar la contraseña del administrador después del primer acceso.

## Funcionalidades Implementadas

### ✅ Módulos Completados

1. **Sistema de Autenticación**
   - Login con validación
   - Gestión de sesiones
   - Control de roles (Administrador/Dueño)
   - Timeout de sesión

2. **Dashboard Principal**
   - Estadísticas generales
   - Accesos rápidos
   - Trabajos recientes
   - Alertas de stock bajo

3. **Gestión de Clientes**
   - CRUD completo (Crear, Leer, Actualizar, Eliminar)
   - Validación de cédula costarricense
   - Búsqueda y filtros
   - Exportación a CSV

4. **Estructura Base para Trabajos**
   - Modelos y controladores preparados
   - Relación con clientes

5. **Estructura Base para Inventario**
   - Modelos preparados
   - Control de stock bajo

6. **Sistema de Bitácora**
   - Registro automático de actividades
   - Seguimiento de usuarios

### 🚧 Módulos en Desarrollo

1. **Gestión de Trabajos**
   - Vistas CRUD (pendiente)
   - Reportes específicos (pendiente)

2. **Gestión de Inventario**
   - Vistas CRUD (pendiente)
   - Control de movimientos (pendiente)

3. **Sistema de Reportes**
   - Reportes por período (pendiente)
   - Exportación PDF (pendiente)

4. **Gestión de Usuarios**
   - Vistas CRUD (pendiente)
   - Cambio de contraseñas (pendiente)

## Configuración de Desarrollo

### Variables de Entorno
```php
// En config/config.php
define('DEBUG_MODE', true);     // Mostrar errores
define('SESSION_TIMEOUT', 3600); // 1 hora
```

### Base de Datos de Prueba
```sql
-- Crear datos de prueba
INSERT INTO TB_Clientes (Cedula, Nombre, Contacto, Empresa) VALUES 
('123456789', 'Juan Pérez', '8888-8888', 'Empresa ABC'),
('987654321', 'María González', '7777-7777', 'Consultores XYZ');
```

## Seguridad

### Medidas Implementadas
- ✅ Validación y sanitización de datos de entrada
- ✅ Protección contra inyección SQL (PDO preparado)
- ✅ Protección XSS (htmlspecialchars)
- ✅ Gestión segura de sesiones
- ✅ Validación de roles y permisos
- ✅ Registro de actividades en bitácora

### Recomendaciones Adicionales
- Usar HTTPS en producción
- Configurar firewall del servidor
- Realizar backups regulares
- Mantener PHP y MySQL actualizados

## Solución de Problemas

### Problemas Comunes

1. **Error de Conexión a Base de Datos**
   ```
   Verificar: config/config.php
   Verificar: Servicio MySQL activo
   Verificar: Credenciales correctas
   ```

2. **Sesión Expira Muy Rápido**
   ```php
   // Aumentar timeout en config/config.php
   define('SESSION_TIMEOUT', 7200); // 2 horas
   ```

3. **Errores de Permisos (Linux)**
   ```bash
   sudo chown -R www-data:www-data /var/www/html/SistemaKris/
   sudo chmod -R 755 /var/www/html/SistemaKris/
   ```

### Logs de Error
```bash
# Ubicación típica de logs
# XAMPP: C:\xampp\apache\logs\error.log
# Linux: /var/log/apache2/error.log
```

## Próximos Pasos

1. **Completar vistas de Trabajos e Inventario**
2. **Implementar sistema de reportes completo**
3. **Agregar funcionalidad de respaldo/restauración**
4. **Implementar notificaciones por email**
5. **Agregar más validaciones y controles**

## Soporte

Para soporte técnico o preguntas sobre el sistema:
- Revisar este README
- Verificar logs de error
- Consultar documentación de PHP y MySQL

---

**Versión**: 1.0  
**Fecha**: Julio 2025  
**Desarrollado para**: Sistema Kris
