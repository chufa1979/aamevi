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

### El límite de subida hay que revisarlo

Los valores de PHP que gobiernan las subidas —el PDF de una clase y, sobre todo,
la entrega de un trabajo práctico— **no son los que el sistema declara**. Medidos
en el entorno de desarrollo:

```
upload_max_filesize = 2M      ← el panel ofrece 20 MB para los PDF de clase
post_max_size       = 8M      ← el aula ofrece 10 MB para las entregas
```

**Hay que verificarlos en el servidor y subirlos**, con un `php.ini` propio o
`.user.ini` en el docroot:

```ini
upload_max_filesize = 12M
post_max_size = 16M
```

`post_max_size` tiene que ser mayor que `upload_max_filesize`: además del
archivo viaja el resto del formulario.

Mientras no se haga, un archivo grande **no llega a Laravel**: PHP vacía el
cuerpo de la petición antes, y con él se van los campos y el token CSRF. El
middleware `HandleOversizedUpload` detecta ese caso y muestra «el archivo es
demasiado grande» en lugar del 419 «página expirada» que saldría si no,
pero es un parche: el archivo se sigue perdiendo.

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

### 1. Traer el repo a la carpeta del dominio

El repo tiene su propio `public/`, que pasa a ser el docroot.

**No usar `git clone`**: exige que el destino esté vacío, y la carpeta del
dominio ya trae un `public/` creado por el panel. Además, `git clone <url>` sin
un `.` final crea un subdirectorio `aamevi/` y deja todo un nivel más abajo, con
lo que el docroot queda apuntando a una carpeta vacía.

```bash
cd ~/aamevi.demosdesarrollos.com.ar

git init
git remote add origin https://github.com/chufa1979/aamevi.git
git fetch origin develop
git checkout -t origin/develop -f
```

`init` + `fetch` + `checkout -f` deja el mismo resultado que un clone, pero
tolera que el directorio ya tenga contenido. Verificar que `app/`, `config/`,
`resources/` y `public/` quedaron en la raíz del dominio:

```bash
ls -la
```

Así, `.env`, `vendor/` y el código quedan fuera del docroot: solo `public/` es
alcanzable por web.

> Si en un intento anterior quedó un subdirectorio `aamevi/`, borrarlo con
> `rm -rf aamevi` antes de empezar. No contiene nada propio.

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

CACHE_STORE=file
SESSION_DRIVER=file
```

`APP_DEBUG=false` no es opcional: en `true`, Laravel muestra el stack trace con
variables de entorno ante cualquier error.

⚠️ **`CACHE_STORE`, no `CACHE_DRIVER`.** Laravel 11 renombró esa variable, y la
vieja se ignora en silencio: el store cae al default `database`, que necesita una
tabla `cache` inexistente. El síntoma aparece recién cuando algo usa la caché
—por ejemplo el limitador de intentos del login— y revienta con
`no such table: cache`. Lo mismo con `BROADCAST_DRIVER`, hoy `BROADCAST_CONNECTION`.

Si el `.env` del servidor se creó antes de agosto de 2026, tiene los nombres
viejos y hay que corregirlos a mano.

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

`storage:link` no es opcional: los PDF de clase y las entregas de los alumnos van
al disco `public`, y sin el enlace simbólico el navegador recibe un 404.

Para dejar el servidor con contenido de ejemplo —**sólo si la base está vacía**—:

```bash
php artisan db:seed --force
```

### 6. Cachés de producción

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Tareas programadas

La plataforma no manda correos en el momento: los escribe en `email_queue` y un
comando la vacía. Sin cron eso no sale nunca, así que **el paso es obligatorio**,
no una mejora.

Una sola línea en el crontab del hosting alcanza para todo, porque Laravel
decide adentro qué toca en cada minuto:

```cron
* * * * * cd ~/aamevi.demosdesarrollos.com.ar && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

Hoy están programados dos: `emails:enviar` cada cinco minutos y
`emails:recordatorios` una vez por día a las 7. Se pueden correr a mano para
probar:

```bash
php artisan emails:enviar
php artisan emails:recordatorios
php artisan schedule:list
```

Si el panel de LatinCloud no deja poner `schedule:run` cada minuto, la
alternativa es una línea por tarea con la frecuencia de cada una.

### El correo saliente

Mientras `MAIL_MAILER=log`, los avisos se «mandan» al archivo de log y quedan
marcados como enviados. Para que salgan de verdad hace falta configurar el
proveedor en el `.env` del servidor:

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=smtp.proveedor.com
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS="no-responder@aamevi.ar"
MAIL_FROM_NAME="AAMEVi"
```

La dirección remitente tiene que ser del dominio, y conviene tener SPF y DKIM
puestos: sin eso, buena parte de los avisos va a spam y la cola va a decir
«enviado» igual, porque para el servidor salió.

Lo que no salió se ve en **Sistema → Avisos por email**, con el error y un botón
para reintentar.

## Actualizaciones

```bash
cd ~/aamevi.demosdesarrollos.com.ar
./deploy.sh
```

`deploy.sh` hace el ciclo completo: `git pull`, dependencias, build de assets,
migraciones y regeneración de cachés. Carga `nvm` por su cuenta y aborta con un
mensaje claro si la versión de Node no le sirve a Vite.

A mano es lo mismo:

```bash
source ~/.nvm/nvm.sh
git pull
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan filament:assets
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

**Regenerar las cachés no es opcional.** Con `config:cache` activo Laravel deja
de leer `.env`, y con `route:cache` sigue sirviendo las rutas anteriores. No hay
ningún error que avise: simplemente se despliega una versión que no es la que
subiste. Si algo queda inconsistente, `php artisan optimize:clear` y volver a
generarlas.

### Qué correr según lo que cambió

| Cambió | Alcanza con |
|---|---|
| Vistas Blade, controladores | `git pull` + `view:cache` |
| Rutas | `git pull` + `route:cache` |
| `resources/css` o `resources/js` | `git pull` + `npm run build` |
| `composer.json` / `.lock` | + `composer install --no-dev --optimize-autoloader` + `php artisan filament:assets` |
| `package.json` | + `npm ci` |
| Migración nueva | + `php artisan migrate --force` |
| `.env` o `config/` | + `php artisan config:cache` |

En la duda, `./deploy.sh`: tarda unos segundos más y no deja nada a medias.

**`filament:assets` va aparte del build de Vite.** Filament sirve su CSS y su JS
desde `public/css|js|fonts/filament`, fuera del manifiesto de Vite y sin
versionar, así que `npm run build` no los toca. Si se actualiza Filament y no se
republican, el panel queda con los assets viejos.

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

- **Límite de subida**: verificar `upload_max_filesize` y `post_max_size` en el
  servidor y subirlos (ver arriba). Hasta que se haga, las entregas de más de
  2 MB se pierden.
- **Cron y correo saliente**: sin la línea de `schedule:run` y sin SMTP
  configurado, los avisos se acumulan en la cola sin salir (ver arriba).
- **Clave SSH**: hoy el acceso es por contraseña. `ssh-copy-id -i ~/.ssh/id_ed25519.pub`
  y después deshabilitar la contraseña.
- **HTTPS**: verificar que el certificado del dominio esté activo y que haya
  redirección desde HTTP.
- **PHP 8.4 en el CLI**: si en algún momento el hosting lo ofrece, se puede
  volver a Laravel 13 revirtiendo el commit del downgrade.
