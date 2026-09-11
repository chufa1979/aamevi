# Guía de Deployment - AAMEVI

## Deployment Inicial Completado ✅

**Fecha:** 2026-09-11  
**Servidor:** LatinCloud (hosting compartido)  
**URL:** https://aamevicampus.com.ar  
**PHP:** 8.4.3  
**Node.js:** v22.23.2  

## Resumen del Deployment

El sitio AAMEVI está ahora en producción en LatinCloud. Este documento describe qué se hizo, cómo está configurado, y qué pasos seguir para futuros deploys.

---

## 1. Configuración Inicial

### 1.1 Acceso SSH
```bash
ssh aamevi
cd /www/aamevicampus.com.ar/aamevicampus.com.ar
```

### 1.2 Verificación de Estado
```bash
git branch --show-current  # Debe decir: develop
ls -d app config resources routes public composer.json composer.lock  # Todos deben existir
```

### 1.3 Activar Node.js (NVM)
```bash
export NVM_DIR="$HOME/.nvm"
[ -s "$NVM_DIR/nvm.sh" ] && . "$NVM_DIR/nvm.sh"
node -v  # Debe dar: v22.23.2
```

---

## 2. Dependencias

### 2.1 Composer (PHP)
```bash
composer install --no-dev --optimize-autoloader --ignore-platform-req=ext-intl --ignore-platform-req=ext-zip
```

**Nota:** El servidor **no tiene las extensiones `intl` y `zip`** instaladas.
- `intl` → Afecta formatting de números/fechas en Filament
- `zip` → Afecta exportación a Excel (openspout)

**Solución:** Contactar a LatinCloud para instalar `php8.4-intl` y `php8.4-zip`, pero mientras tanto se ignoran.

### 2.2 npm (JavaScript/Vite)
```bash
npm ci
npm run build
```

---

## 3. Configuración Laravel

### 3.1 Variables de Entorno
```bash
cp .env.example .env
php artisan key:generate
```

Luego editar `.env` con:
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://aamevicampus.com.ar

DB_CONNECTION=mysql
DB_HOST=192.168.0.170
DB_PORT=3306
DB_DATABASE=aamevi_db
DB_USERNAME=aamevi_user_db
DB_PASSWORD=FLjqM4vszoO5

CACHE_STORE=file
SESSION_DRIVER=file
```

**Importante:** Usar IP privada `192.168.0.170` para tráfico interno del mismo hosting.

### 3.2 Base de Datos
```bash
php artisan migrate --force
php artisan storage:link
chmod -R ug+rw storage bootstrap/cache
```

### 3.3 Cachés
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 4. Configuración del Servidor Web

### 4.1 Document Root (LatinCloud Panel)
- Verificar que apunte a: `/www/aamevicampus.com.ar/aamevicampus.com.ar/public`
- PHP versión: **8.2 o superior** (recomendado 8.4)

### 4.2 .htaccess
Crear `/www/aamevicampus.com.ar/aamevicampus.com.ar/public/.htaccess`:

```apache
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews
    </IfModule>

    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [QSA,L]
</IfModule>
```

**Nota:** Sin este archivo, solo `/` funciona y el resto da 404.

---

## 5. Adaptación a PHP 8.4

El servidor no tiene PHP 8.3, solo 8.2 y 8.4. Se adaptó el proyecto a **PHP 8.4**:

### 5.1 Cambio en `composer.json`
```json
"config": {
  "platform": {
    "php": "8.4.0"
  }
}
```

Esto fue necesario porque:
- Laravel 12 soporta PHP 8.2+
- Symfony 8 (requerido por Laravel 13) necesita PHP 8.4.1+
- El proyecto usa Laravel 12, así que 8.4 es compatible

---

## 6. Pautas para Futuras Actualizaciones

### 6.1 Flujo de Trabajo (Local → Servidor)

#### Paso 1: En Local (tu máquina)
```bash
# Hacer cambios, commit y push
git add .
git commit -m "feat: descripción del cambio"
git push origin develop
```

#### Paso 2: En el Servidor (SSH)
```bash
# Conectar y cambiar de rama si es necesario
ssh aamevi
cd /www/aamevicampus.com.ar/aamevicampus.com.ar

# Traer cambios del remoto
git pull

# Si cambiaste composer.json, reinstalar dependencias:
composer install --no-dev --optimize-autoloader --ignore-platform-req=ext-intl --ignore-platform-req=ext-zip

# Si cambiaste assets (JS/CSS), recompilar:
export NVM_DIR="$HOME/.nvm"
[ -s "$NVM_DIR/nvm.sh" ] && . "$NVM_DIR/nvm.sh"
npm ci
npm run build

# Limpiar cachés (SIEMPRE después de cambios)
php artisan config:clear
php artisan cache:clear
php artisan view:clear
rm -rf bootstrap/cache/*

# Si hay migraciones nuevas:
php artisan migrate --force
```

### 6.2 Cambios Comunes

#### A. Cambios en Rutas o Controladores
```bash
php artisan route:cache
php artisan config:clear
```

#### B. Cambios en Vistas Blade
```bash
php artisan view:clear
```

#### C. Cambios en Assets (CSS/JS)
```bash
npm run build
php artisan config:clear
```

#### D. Cambios en Migraciones o Modelos
```bash
php artisan migrate --force
php artisan config:clear
php artisan cache:clear
```

#### E. Cambios en Configuración (.env o config/)
```bash
php artisan config:clear
php artisan cache:clear
php artisan config:cache
```

### 6.3 Checklist de Deployment

Antes de hacer push a producción:

- [ ] Testear localmente: `php artisan serve` + `npm run dev`
- [ ] Ejecutar tests: `php artisan test`
- [ ] Linter: `./vendor/bin/pint`
- [ ] Actualizar `CLAUDE.md` si cambió la arquitectura
- [ ] Commit con mensaje descriptivo

En el servidor después de pull:

- [ ] Ejecutar `git pull`
- [ ] Reinstalar dependencias si es necesario: `composer install --no-dev ...`
- [ ] Recompilar assets: `npm run build`
- [ ] Limpiar cachés: `php artisan config:clear && php artisan cache:clear`
- [ ] Ejecutar migraciones si las hay: `php artisan migrate --force`
- [ ] Verificar en navegador que todo funciona

---

## 7. Información Importante

### 7.1 Limitaciones Actuales
- **Extensiones PHP faltantes:** `intl` y `zip` (solicitadas a LatinCloud)
- **Sin SMTP configurado:** Emails se guardan en `email_queue` pero no se envían (necesita cron job)
- **Sin CI/CD:** Los deploys son manuales vía SSH

### 7.2 Credenciales
- **DB Host:** `192.168.0.170` (IP privada interna)
- **DB User:** `aamevi_user_db`
- **App URL:** `https://aamevicampus.com.ar`

### 7.3 Estructura de Directorios
```
/www/aamevicampus.com.ar/
└── aamevicampus.com.ar/          # Raíz del proyecto
    ├── app/                       # Código Laravel
    ├── resources/                 # Vistas y assets
    ├── public/                    # Document Root (apunta aquí Apache)
    │   ├── build/                 # Assets compilados por Vite
    │   └── .htaccess              # Reescritura de URLs
    ├── bootstrap/cache/           # Cachés de Laravel
    ├── storage/                   # Logs y archivos
    ├── .env                       # Variables de entorno (NO subir a git)
    └── composer.json
```

---

## 8. Troubleshooting

### Error: "Unable to locate file in Vite manifest"
```bash
npm run build
php artisan config:clear
```

### Error: "No application encryption key has been specified"
```bash
php artisan key:generate
php artisan config:clear
```

### Error: 404 en todas las rutas excepto /
- Verificar que `.htaccess` existe en `public/`
- Verificar que `mod_rewrite` está habilitado en Apache
- Contactar a LatinCloud si no está habilitado

### Error: 500 Internal Server Error
```bash
tail -50 /www/aamevicampus.com.ar/aamevicampus.com.ar/storage/logs/laravel.log
```

---

## 9. Próximos Pasos

1. **Instalar extensiones PHP:** Contactar a LatinCloud para `php8.4-intl` y `php8.4-zip`
2. **Configurar SMTP:** Para que los emails se envíen realmente (SendGrid o nodemailer)
3. **Configurar cron:** Para que `emails:enviar` corra periódicamente y drene la cola
4. **CI/CD opcional:** GitHub Actions para test automáticos y deployment

---

## Referencias

- Documentación del proyecto: `docs/PLAN_ARQUITECTONICO.md`
- README: `README.md`
- Instrucciones para Claude Code: `CLAUDE.md`
