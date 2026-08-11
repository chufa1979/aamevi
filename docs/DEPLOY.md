# Deploy — aamevi.demosdesarrollos.com.ar

Entorno de demo en LatinCloud (CloudSSH). Este documento describe el despliegue
real, con las restricciones concretas de ese hosting.

## Restricciones del servidor

Relevadas por SSH el 2026-08-11:

| | |
|---|---|
| PHP del **web** (FPM) | **8.4.3** |
| PHP del **CLI** (SSH) | **8.3.11** — `/etc/php/` solo lista 5.6, 7.4, 8.2, 8.3 |
| Composer | `/usr/local/bin/composer` |
| Git | `/usr/bin/git` |
| Node / npm | **18.20.4** / 10.7.0 |
| rsync | **no está** |
| Home | `/www/demosdesarrollos` |
| Docroot | `~/aamevi.demosdesarrollos.com.ar/public` (verificado) |

Dos consecuencias que explican decisiones del proyecto:

1. **El CLI es 8.3, no 8.4.** Artisan corre ahí (migraciones, cachés, colas), así
   que el proyecto está en Laravel 12 / Symfony 7 (`php >=8.2`). Laravel 13
   arrastra Symfony 8, que exige `>=8.4.1`, y no se podría administrar.
   `composer.json` fija `config.platform.php = 8.3.11` para que Composer resuelva
   siempre contra la versión del CLI, que es donde corre `composer install`.

2. **Node 18 no alcanza.** Vite 8 declara `node: ^20.19.0 || >=22.12.0`. Hay que
   instalar Node moderno con nvm (paso 2) o compilar los assets localmente y
   subirlos (ver *Alternativa sin nvm*).

## Puesta en marcha (una sola vez)

### 1. Clonar en la carpeta del dominio

El repo tiene su propio `public/`, que pasa a ser el docroot. `git clone` exige
un destino vacío, así que primero se borra el `public/` que crea el panel:

```bash
cd ~/aamevi.demosdesarrollos.com.ar
rmdir public
git clone https://github.com/chufa1979/aamevi.git .
git checkout develop
```

Queda `.env`, `vendor/` y el código fuera del docroot: solo `public/` es
alcanzable por web.

### 2. Node moderno con nvm

```bash
curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.40.1/install.sh | bash
source ~/.nvm/nvm.sh
nvm install 22
node -v          # debe dar v22.x
```

`nvm` queda en el home del usuario; no toca el Node del sistema.

### 3. Dependencias y assets

```bash
cd ~/aamevi.demosdesarrollos.com.ar
composer install --no-dev --optimize-autoloader
npm ci
npm run build
```

`--no-dev` deja afuera Pest, Pint y Sail, que no hacen falta en producción.

### 4. Configuración

```bash
cp .env.example .env
php artisan key:generate
```

Editar `.env`:

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://aamevi.demosdesarrollos.com.ar

DB_CONNECTION=mysql
DB_HOST=<host del panel>
DB_PORT=3306
DB_DATABASE=<base>
DB_USERNAME=<usuario>
DB_PASSWORD=<password>
```

`APP_DEBUG=false` no es opcional: en `true`, Laravel muestra el stack trace con
variables de entorno ante cualquier error.

Los datos de la base salen del panel de LatinCloud. El `DB_HOST` que figura en
el `.env` de desarrollo apunta a un servidor externo y **no** es necesariamente
el que corresponde acá.

### 5. Base de datos y permisos

```bash
php artisan migrate --force
php artisan storage:link
chmod -R ug+rw storage bootstrap/cache
```

`--force` es obligatorio: sin él, Artisan se niega a migrar con `APP_ENV=production`.

> A hoy `database/migrations/` está vacío, así que `migrate` solo crea la tabla
> `migrations`. Las tablas del dominio salen de `docs/PLAN_ARQUITECTONICO.md` y
> todavía no están implementadas.

### 6. Cachés de producción

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Actualizaciones

```bash
cd ~/aamevi.demosdesarrollos.com.ar
git pull
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

Si `nvm` no quedó cargado en la sesión, `source ~/.nvm/nvm.sh` antes de `npm`.

Después de tocar `.env` hay que volver a correr `config:cache`: con la caché
activa, Laravel deja de leer el archivo.

## Alternativa sin nvm

Si no se quiere instalar Node en el servidor, los assets se compilan localmente
y se suben. `public/build` está en `.gitignore`, así que no viaja por git:

```bash
# local
npm run build
scp -P 22 -r public/build \
    aamevidemosdesarrolloscomar@ssh.latincloud.app:~/aamevi.demosdesarrollos.com.ar/public/
```

No hay `rsync` en el servidor, así que `scp` o SFTP. Hay que repetirlo cada vez
que cambie algo de `resources/css` o `resources/js`.

## Pendiente

- **Clave SSH**: hoy el acceso es por contraseña. `ssh-copy-id -i ~/.ssh/id_ed25519.pub`
  y después deshabilitar la contraseña.
- **HTTPS**: verificar que el certificado del dominio esté activo y que haya
  redirección desde HTTP.
- **PHP 8.4 en el CLI**: si en algún momento el hosting lo ofrece, se puede
  volver a Laravel 13 revirtiendo el commit del downgrade.
