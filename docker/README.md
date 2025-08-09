# Docker Setup para Sistema de Gestión Empresarial

Este proyecto incluye configuración completa de Docker para desarrollo y producción.

## Requisitos Previos

- Docker
- Docker Compose

## Configuración Rápida

### 1. Construcción e Inicio

```bash
# Construir y levantar todos los servicios
docker-compose up -d --build

# O solo construir la imagen
docker build -t sistema-kris .
```

### 2. Acceso a los Servicios

- **Aplicación Web**: https://caa-i0xf.onrender.com:8080
- **phpMyAdmin**: https://caa-i0xf.onrender.com:8081
- **Base de Datos MySQL**: localhost:3307

### 3. Credenciales por Defecto

**Base de Datos:**
- Usuario: `root`
- Contraseña: `rootpassword`
- Base de Datos: `SistemaGestionEmpresa`

**phpMyAdmin:**
- Usuario: `root`
- Contraseña: `rootpassword`

## Comandos Útiles

### Gestión de Contenedores

```bash
# Ver estado de los contenedores
docker-compose ps

# Ver logs
docker-compose logs

# Ver logs de un servicio específico
docker-compose logs web
docker-compose logs db

# Parar los servicios
docker-compose stop

# Parar y eliminar contenedores
docker-compose down

# Parar y eliminar contenedores + volúmenes
docker-compose down -v
```

### Desarrollo

```bash
# Acceder al contenedor web
docker-compose exec web bash

# Acceder al contenedor de base de datos
docker-compose exec db mysql -u root -p

# Reiniciar solo el servicio web
docker-compose restart web
```

### Base de Datos

```bash
# Hacer backup de la base de datos
docker-compose exec db mysqldump -u root -prootpassword SistemaGestionEmpresa > backup.sql

# Restaurar backup
docker-compose exec -T db mysql -u root -prootpassword SistemaGestionEmpresa < backup.sql

# Ejecutar script SQL
docker-compose exec -T db mysql -u root -prootpassword SistemaGestionEmpresa < script.sql
```

## Estructura de Archivos Docker

```
/
├── Dockerfile                 # Configuración principal del contenedor
├── docker-compose.yml         # Orquestación de servicios
├── .env.docker               # Variables de entorno para Docker
├── docker/
│   └── apache-config.conf     # Configuración personalizada de Apache
└── database/
    ├── schema.sql            # Esquema inicial de la base de datos
    └── migracion.sql         # Datos de migración
```

## Variables de Entorno

El archivo `.env.docker` contiene las configuraciones para el entorno de Docker:

- `DB_HOST`: Host de la base de datos (db)
- `DB_PORT`: Puerto de la base de datos (3306)
- `DB_NAME`: Nombre de la base de datos
- `DB_USER`: Usuario de la base de datos
- `DB_PASS`: Contraseña de la base de datos
- `SITE_URL`: URL del sitio web
- `DEBUG_MODE`: Modo debug (false para producción)

## Configuración de Producción

Para producción, modifica las siguientes configuraciones:

1. **Cambiar contraseñas** en `docker-compose.yml`
2. **Configurar DEBUG_MODE=false** en `.env.docker`
3. **Configurar SESSION_SECURE=true** si usas HTTPS
4. **Usar volúmenes persistentes** para la base de datos

### Ejemplo docker-compose.prod.yml

```yaml
version: '3.8'
services:
  web:
    build: .
    ports:
      - "80:80"
    environment:
      - DEBUG_MODE=false
      - SESSION_SECURE=true
  # ... resto de la configuración
```

## Solución de Problemas

### Puerto en Uso
Si el puerto 8080 está en uso:
```bash
# Cambiar el puerto en docker-compose.yml
ports:
  - "8081:80"  # Cambiar 8080 por otro puerto
```

### Problemas de Permisos
```bash
# Dar permisos a directorios
sudo chown -R www-data:www-data reports/ uploads/
sudo chmod -R 775 reports/ uploads/
```

### Base de Datos No Inicia
```bash
# Ver logs de la base de datos
docker-compose logs db

# Reiniciar solo la base de datos
docker-compose restart db
```

### Limpiar Todo
```bash
# Eliminar todo (contenedores, volúmenes, imágenes)
docker-compose down -v --rmi all
docker system prune -a
```

## Desarrollo con Docker

Para desarrollo activo, puedes montar el código como volumen:

```yaml
services:
  web:
    # ... configuración existente
    volumes:
      - .:/var/www/html
      - ./reports:/var/www/html/reports
      - ./uploads:/var/www/html/uploads
```

Esto permite que los cambios en el código se reflejen inmediatamente sin reconstruir la imagen.
