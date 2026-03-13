# 🚀 Deploy PrintingBruno → Hostinger

## Pre-requisitos
- Cuenta Hostinger activa con hosting compartido (Business o superior)
- Dominio apuntando a Hostinger
- Acceso a hPanel

---

## PASO 1 — Editar `api/config.php` (en local, antes de subir)

Abrí el archivo `api/config.php` y reemplazá estas 4 líneas con tus datos reales:

```php
define('DB_NAME', 'u123456789_printingbruno');  // ← el nombre que creaste en hPanel
define('DB_USER', 'u123456789_pbadmin');         // ← usuario MySQL de hPanel
define('DB_PASS', 'TU_PASSWORD_AQUI');           // ← tu contraseña MySQL
define('SITE_URL', 'https://tudominio.com');     // ← tu dominio real con https://
```

> Los nombres de DB y usuario en Hostinger tienen el prefijo `u{número}_`

---

## PASO 2 — Crear la base de datos en hPanel

1. Entrá a **hPanel** → **Bases de datos** → **MySQL Databases**
2. En "Create a new MySQL database":
   - Database name: `printingbruno` → quedará como `u123456789_printingbruno`
3. En "Create a new MySQL user":
   - Username: `pbadmin` → quedará como `u123456789_pbadmin`
   - Password: elegí una contraseña fuerte y **anotala**
4. En "Add user to database": seleccioná el usuario y la DB → **All Privileges**

---

## PASO 3 — Activar SSL (OBLIGATORIO antes de subir)

1. En hPanel → **SSL** → **SSL/TLS**
2. Seleccioná tu dominio → **Install** (certificado gratuito Let's Encrypt)
3. Esperá 5-10 minutos a que propague

> ⚠️ El `.htaccess` redirige HTTP → HTTPS automáticamente.
> Si subís los archivos SIN SSL activo, el sitio queda inaccessible.

---

## PASO 4 — Subir los archivos

### Opción A: File Manager (más fácil)

1. Comprimí la carpeta del proyecto en un `.zip` (excluir: `node_modules`, `.git`, `.claude`)
2. hPanel → **File Manager** → entrá a `public_html/`
3. Hacé click en **Upload** → subí el `.zip`
4. Click derecho sobre el `.zip` → **Extract** → extraé en `public_html/`
5. Verificá que `index.html` quede en la raíz de `public_html/`

### Opción B: FTP con FileZilla

```
Host:     ftp.tudominio.com
Usuario:  tu email de Hostinger
Password: contraseña FTP de hPanel
Puerto:   21
```

Copiá todo el contenido de la carpeta local a `public_html/`

---

## PASO 5 — Importar la base de datos

1. hPanel → **Bases de datos** → **phpMyAdmin**
2. En el panel izquierdo, hacé click en tu DB (`u123456789_printingbruno`)
3. Tab **Importar** → **Seleccionar archivo**
4. Subí el archivo `sql/schema_hostinger.sql` (no el `schema.sql` normal)
5. Click **Importar**

Deberías ver: `3 tablas + productos insertados ✅`

---

## PASO 6 — Cambiar la contraseña admin (OBLIGATORIO)

La contraseña por defecto es `admin123`. **Hay que cambiarla antes de publicar.**

**Método A - Script incluido:**
1. Subí el archivo `sql/change_admin_password.php` al servidor
2. Abrí en el browser: `https://tudominio.com/sql/change_admin_password.php?pass=TuNuevaPass123`
3. Copiá el SQL generado
4. Ejecutalo en phpMyAdmin → pestaña SQL
5. **⚠️ Eliminá el archivo** `sql/change_admin_password.php` del servidor inmediatamente

**Método B - Directo en phpMyAdmin:**
1. phpMyAdmin → seleccioná la DB → tabla `admin_users`
2. Tab **SQL** → ejecutá:
```sql
UPDATE admin_users
SET password_hash = '$2y$12$NUEVO_HASH_AQUI'
WHERE username = 'admin';
```
Para generar el hash, usá: https://bcrypt-generator.com/ (cost = 12)

---

## PASO 7 — Crear directorio de uploads

El sistema de carga de imágenes del admin necesita este directorio:

1. hPanel → **File Manager** → `public_html/`
2. Crear carpeta: `assets/images/products/`
3. Click derecho → **Permissions** → `755`

(Si ya subiste los archivos y el admin sube una imagen, se crea automáticamente)

---

## PASO 8 — Verificar el sitio

Checklist final:

- [ ] `https://tudominio.com` → landing page carga con logo y productos
- [ ] `https://tudominio.com/catalogo.html` → productos del catálogo cargan desde la API
- [ ] `https://tudominio.com/admin.html` → login funciona con la nueva contraseña
- [ ] Panel admin → se pueden ver/crear/editar productos
- [ ] Las imágenes de productos se ven en el catálogo
- [ ] HTTPS activo (candado verde en browser)
- [ ] Redirige `http://` → `https://` automáticamente

---

## PASO 9 — Proteger archivos sensibles (post-deploy)

Podés eliminar del servidor (no necesarios en producción):

```
sql/schema.sql              ← no necesario (ya importado)
sql/schema_hostinger.sql    ← no necesario (ya importado)
sql/change_admin_password.php  ← ELIMINAR si lo usaste
DEPLOY_HOSTINGER.md         ← opcional eliminar
logs/                       ← mantener pero ya tiene .htaccess que lo protege
```

---

## Pendiente (para después del deploy inicial)

- [ ] **MercadoPago**: agregar credenciales reales en `config.php` + `composer install` vía SSH
- [ ] **Dominio personalizado**: configurar DNS si aún no apunta a Hostinger
- [ ] **Backups automáticos**: hPanel → Backups → activar backup diario
- [ ] **Email transaccional**: configurar SMTP para emails de confirmación de pedidos

---

## Solución de problemas comunes

| Error | Causa | Solución |
|-------|-------|----------|
| Pantalla en blanco | Error PHP silencioso | Ver `logs/php_errors.log` o phpMyAdmin logs |
| 500 Internal Server Error | `.htaccess` inválido o módulo mod_rewrite desactivado | Contactar soporte Hostinger |
| API devuelve HTML en vez de JSON | config.php no encontrado / ruta mal | Verificar que `api/config.php` existe en el servidor |
| Login admin falla | Contraseña incorrecta o DB no conecta | Verificar credenciales en `config.php` |
| Imágenes no cargan | Rutas relativas incorrectas | Verificar que `assets/productos/` se subió correctamente |
| Redirect loop (redirige infinito) | SSL no activo pero .htaccess fuerza HTTPS | Activar SSL en hPanel primero |
