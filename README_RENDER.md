# Guía de Despliegue en Render (Hosting)

Esta guía documenta el proceso completo para hospedar este sistema PHP (Apache + MySQL) en Render.com usando el `Dockerfile.render` incluido.

## 1. Requisitos Previos
- Cuenta en Render: https://dashboard.render.com
- Repositorio Git (GitHub / GitLab / Bitbucket) con este proyecto
- Base de Datos MySQL/MariaDB gestionada externamente (PlanetScale, Railway, Aiven, CleverCloud, DigitalOcean, AWS RDS, etc.)
- (Opcional) Dominio propio

Render NO ofrece MySQL administrado nativamente; debes usar un proveedor externo. (Alternativa avanzada: migrar a PostgreSQL.)

## 2. Estructura Relevante para Despliegue
- `Dockerfile.render`: Imagen optimizada para producción (Apache + PHP + extensiones necesarias)
- `docker-compose.yml`: Útil para desarrollo local (no usado directamente por Render)
- `config/config.php`: Carga valores desde variables de entorno (ajústalo si aún hay valores fijos)
- `database/schema.sql` / `schema_mejorado.sql`: Esquema inicial
- `database/*`: Scripts de apoyo (incluyen ENUM actualizado para roles: Administrador, Contador, Trabajador)
- `DEPLOY_RENDER.md`: Versión previa de esta guía (esta README_RENDER es más completa y actualizada)

## 3. Variables de Entorno (Configurar en Render)
Configura todas como Environment → Environment Variables del servicio Web:

| Variable | Descripción |
|----------|-------------|
| DB_HOST | Host del MySQL externo (ej: aws… or psdb.cloud) |
| DB_PORT | Puerto (3306 normalmente) |
| DB_NAME | Nombre de la base de datos |
| DB_USER | Usuario de la BD |
| DB_PASS | Contraseña de la BD |
| SITE_NAME | Nombre a mostrar en la app |
| SITE_URL | URL pública (Render asigna algo como https://mi-app.onrender.com) |
| DEBUG_MODE | true/false (usar false en producción) |
| SESSION_SECURE | true (Render usa HTTPS) |
| SESSION_HTTPONLY | true |
| TIMEZONE | America/Costa_Rica (o tu zona) |
| SESSION_TIMEOUT | 3600 (segundos) |

(Opcional) Para futuras integraciones agrega MAIL_* o STORAGE_*.

## 4. Preparar la Base de Datos
Ejecuta en tu proveedor MySQL antes del primer despliegue:

1. Crear base de datos (si no existe):
```sql
CREATE DATABASE IF NOT EXISTS SistemaGestionEmpresa CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```
2. Importar esquema (elige el más reciente, por ejemplo `schema_mejorado.sql`):
```bash
mysql -h <DB_HOST> -u <DB_USER> -p SistemaGestionEmpresa < database/schema_mejorado.sql
```
3. (Si vienes de versión previa con ENUM antiguo 'Administrador','Dueño') Migrar roles:
```sql
UPDATE TB_Usuarios SET Rol='Trabajador' WHERE Rol='Dueño';
ALTER TABLE TB_Usuarios MODIFY Rol ENUM('Administrador','Contador','Trabajador') NOT NULL;
```
4. Crear usuario administrador inicial si no existe:
```sql
INSERT INTO TB_Usuarios (Usuario, Contrasena, Rol)
SELECT 'admin', '$2y$10$abcdefghijklmnopqrstuvABCDEuVGD9y7wJmP2gSPFcbKnGZkQdVepyi', 'Administrador'
WHERE NOT EXISTS (SELECT 1 FROM TB_Usuarios WHERE Usuario='admin');
-- La contraseña hash anterior es de un ejemplo; genera la tuya con password_hash en PHP.
```

## 5. Despliegue en Render (Web Service Docker)
1. En Render: New + → Web Service
2. Conecta el repositorio.
3. Name: `sistema-gestion` (o el que prefieras)
4. Environment: Docker
5. Dockerfile Path: `Dockerfile.render`
6. Root Directory: (deja vacío si el Dockerfile está en la raíz del repo)
7. Region: la más cercana a tus usuarios.
8. Plan: Free (para pruebas) / Starter (recomendado en producción).
9. Add Environment Variables (tabla anterior).
10. Crear Servicio.

Render construirá la imagen automáticamente (fase Build) y luego levantará el contenedor (fase Deploy). La app escuchará en el puerto 80 dentro del contenedor (Apache).

## 6. Flujo de Actualizaciones
- Cada `git push` a la rama configurada (por defecto `main`/`Main`) dispara un nuevo build.
- Para rollback: Deploys → selecciona un deploy anterior → Rollback.
- Para despliegue manual: botón Manual Deploy → Deploy latest commit.

## 7. Pruebas Locales Simulando Render
En Windows (PowerShell):
```powershell
# Construir usando el Dockerfile.render
docker build -f Dockerfile.render -t sistema-gestion:render .
# Ejecutar contenedor
docker run -p 8080:80 --env-file .env.local sistema-gestion:render
```
Luego abrir http://localhost:8080

Crea un `.env.local` (no subir a Git) con las mismas variables de Render.

## 8. Persistencia y Archivos
El sistema actualmente trabaja principalmente con datos en la BD. Si en el futuro manejas uploads:
- Evita almacenar en el filesystem del contenedor (se pierde en cada deploy).
- Usa S3 / Backblaze / Cloudinary.
- Alternativa: Render Disks (solo en planes de pago) montados en un path (ej: `/var/www/html/storage`).

## 9. Seguridad y Buenas Prácticas
- Establece `DEBUG_MODE=false` en producción.
- Fuerza HTTPS (Render lo aplica automáticamente y gestiona certificados SSL gratuitos).
- Revisa cabeceras de seguridad adicionales (ya puedes ampliar el Apache conf dentro de la imagen si requieres HSTS o CSP avanzada).
- Cambia contraseñas creadas por defecto inmediatamente.
- Implementa rotación de contraseñas y logs de acceso críticos (la bitácora ya registra acciones básicas).
- Considera WAF externo (Cloudflare) si esperas tráfico público amplio.

## 10. Monitoreo y Logs
- Logs: pestaña Logs del servicio.
- Exportación: integra un agente (p.ej. Vector) o usa Render Log Streams (si disponible en tu plan).
- Uptime: configura UptimeRobot para tu URL.
- Errores PHP: visibles en logs si `display_errors` está off pero `error_log` configurado (ya en Apache).

## 11. Escalado
- Horizontal: Para apps PHP/Apache en Render Free/Starter no hay autoscale automático; escala manual creando un plan superior o replicando servicios detrás de un reverse proxy (avanzado).
- Vertical: Cambia el Instance Type en Settings.

## 12. Jobs Programados (Opcional)
Si en el futuro necesitas tareas periódicas (limpieza, reportes):
- Usa Render Cron Jobs apuntando a un script CLI PHP (ej: `php artisan` style o `php scripts/limpieza.php`).

## 13. Actualización de Roles (Resumen Rápido)
Si se detectan usuarios con rol obsoleto:
```sql
UPDATE TB_Usuarios SET Rol='Trabajador' WHERE Rol NOT IN ('Administrador','Contador','Trabajador');
ALTER TABLE TB_Usuarios MODIFY Rol ENUM('Administrador','Contador','Trabajador') NOT NULL;
```

## 14. Checklist Post-Deploy
- [ ] Página carga sin errores 500
- [ ] Conexión a BD exitosa (revisa logs si falla)
- [ ] Usuario admin puede iniciar sesión
- [ ] Roles funcionan (menús adaptados)
- [ ] `DEBUG_MODE=false`
- [ ] URL pública configurada en `SITE_URL`
- [ ] Certificado SSL activo
- [ ] Logs sin errores repetitivos

## 15. Solución de Problemas Rápida
| Problema | Causa Común | Acción |
|----------|-------------|--------|
| Error 500 inicial | Variables de entorno faltan | Revisar Settings → Env Vars |
| No conecta a DB | Firewall o host mal | Probar conexión directa desde local, revisar DB_HOST |
| Cambios no aparecen | Cache navegador / build fallido | Forzar refresh, revisar pestaña Events |
| Sesiones se pierden | Cambio de dominio / cookie | Verifica SITE_URL y SESSION_SECURE |
| Roles no actualizados | ENUM viejo | Ejecutar ALTER TABLE |

## 16. Migrar a PostgreSQL (Opcional Futuro)
1. Crear DB PostgreSQL en Render
2. Ajustar `database.php` para PDO PGSQL
3. Cambiar tipos ENUM por CHECK o tabla relacional `roles`
4. Exportar datos de MySQL → convertir → importar a PG
