# Guía de Inicio Rápido — AAMEVi

Plataforma de e-learning en **Laravel 12 + Blade + MySQL 8**.

Documentos relacionados:

- [`docs/PLAN_ARQUITECTONICO.md`](./docs/PLAN_ARQUITECTONICO.md) — modelo de datos, flujos y decisiones
- [`docs/SISTEMA_DISENO.md`](./docs/SISTEMA_DISENO.md) — paleta, tipografía y componentes
- [`docs/DEPLOY.md`](./docs/DEPLOY.md) — despliegue en el hosting
- [`CLAUDE.md`](./CLAUDE.md) — convenciones para trabajar en el repo

---

## Requisitos

| | Versión | Por qué |
|---|---|---|
| PHP | **≥ 8.2** | Laravel 12. Extensiones: `pdo_mysql`, `mbstring`, `intl`, `gd`, `zip`, `bcmath` |
| Composer | 2.x | |
| Node | **≥ 20.19** | Vite 8 lo exige (`^20.19.0 \|\| >=22.12.0`) |
| MySQL | 8.x | También sirve MariaDB |

> **Nota sobre versiones de PHP**: `composer.json` fija
> `config.platform.php = 8.3.11`, que es el PHP del servidor de producción.
> Podés desarrollar con una versión mayor —Composer resuelve igual contra
> 8.3.11— y así evitás instalar paquetes que después no corran en el hosting.

---

## Puesta en marcha

### 1. Dependencias

```bash
composer install
npm install
```

### 2. Entorno

```bash
cp .env.example .env
php artisan key:generate
```

Editar `.env` con los datos de tu base local:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=aamevi_db
DB_USERNAME=aamevi
DB_PASSWORD=aamevi
```

Crear la base, si no existe:

```bash
mysql -u root -e "
  CREATE DATABASE IF NOT EXISTS aamevi_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
  CREATE USER IF NOT EXISTS 'aamevi'@'localhost' IDENTIFIED BY 'aamevi';
  GRANT ALL PRIVILEGES ON aamevi_db.* TO 'aamevi'@'localhost';
  FLUSH PRIVILEGES;"
```

### 3. Migrar

```bash
php artisan migrate
```

> A hoy `database/migrations/` está **vacío**: esto solo crea la tabla
> `migrations`. El esquema del dominio está especificado en
> `docs/PLAN_ARQUITECTONICO.md` §2 y todavía no está implementado.

### 4. Levantar

Hacen falta **dos procesos**, en dos terminales:

```bash
php artisan serve      # http://localhost:8000
npm run dev            # servidor de Vite, con recarga en caliente
```

`npm run dev` no es opcional en desarrollo: la directiva `@vite` del layout
busca el servidor de Vite y, si no está, cae al manifiesto de `public/build`
—que solo existe después de un `npm run build`—.

---

## Comandos

### Artisan

| Tarea | Comando |
|---|---|
| Servidor | `php artisan serve` |
| Rutas registradas | `php artisan route:list` |
| REPL | `php artisan tinker` |
| Estado del entorno | `php artisan about` |
| Migración nueva | `php artisan make:migration create_courses_table` |
| Migrar / revertir | `php artisan migrate` / `php artisan migrate:rollback` |
| Modelo + migración | `php artisan make:model Course -m` |
| Controlador | `php artisan make:controller Courses/CourseController` |
| FormRequest | `php artisan make:request StoreCourseRequest` |
| Seeders | `php artisan db:seed` |
| Limpiar cachés | `php artisan optimize:clear` |

### Front-end

```bash
npm run dev            # desarrollo, con HMR
npm run build          # build de producción a public/build
```

### Calidad

```bash
php artisan test                              # todos los tests
php artisan test tests/Feature/CourseTest.php # uno solo
php artisan test --filter TestName
php artisan test --coverage
./vendor/bin/pint                             # formatear
./vendor/bin/pint --test                      # verificar sin escribir
```

---

## Estructura

```
app/Http/Controllers/   Por dominio: Auth, Users, Courses, Quiz, Tasks…
app/Http/Requests/      Validación con FormRequest (no reglas inline)
app/Models/             Eloquent, con trait HasUuids
config/navigation.php   Fuente única del menú y datos de contacto
database/migrations/    Único lugar donde cambia el esquema
resources/css/app.css   Tokens de marca en @theme (Tailwind 4)
resources/views/
  layouts/app.blade.php
  components/           header, footer, top-bar, page-hero, section, button, icon
routes/web.php          Rutas públicas
```

### Convenciones

- **Validación siempre en `FormRequest`**, con `authorize()` y `rules()` explícitos
- **El esquema cambia solo por migraciones**, nunca a mano en la base
- **Route model binding** en lugar de resolver IDs en el controlador
- **Componentes Blade antes que estilos inline**: los de `resources/views/components/`
  ya encapsulan la identidad visual
- Código en inglés; documentación, commits y textos de interfaz en español

### Sobre los estilos

Tailwind 4 se configura **en CSS**, no en `tailwind.config.js`. Los tokens de
marca viven en el bloque `@theme` de `resources/css/app.css`:

```css
@theme {
  --color-primary: #00b8b3;   /* institucional */
  --color-accent:  #f46707;   /* hover de nav y CTAs */
  --color-ink:     #333333;
  --color-surface: #ececec;
  --container-site: 1315px;
}
```

Agregar un color o un tamaño es agregar una variable ahí. Ver
`docs/SISTEMA_DISENO.md` para el detalle y el origen de cada valor.

---

## Docker (opcional)

`docker-compose.yml` levanta `mysql` + `app` + `nginx`:

```bash
docker-compose up -d
docker-compose exec app php artisan migrate
docker-compose logs -f app
```

**No es el camino recomendado.** El entorno de destino es hosting compartido sin
contenedores, así que trabajar sin Docker se parece más a producción. Además el
compose no contempla el build de assets.

---

## Problemas frecuentes

**`zsh: command not found: php`**
El shell no tiene Homebrew en el `PATH`. Recargá la configuración con `exec zsh`,
o forzalo en la sesión con `eval "$(/opt/homebrew/bin/brew shellenv)"`.

**`Vite manifest not found`**
Falta el build. En desarrollo, `npm run dev`; si querés servir estático,
`npm run build`.

**Cambios de CSS que no aparecen**
Si `npm run dev` está corriendo, revisá que el archivo esté dentro de lo que
Tailwind escanea (`@source '../views'` en `app.css`). Si no, hace falta rebuild.

**`php artisan migrate` no crea tablas**
Es lo esperado: no hay migraciones todavía. Ver §2 del plan arquitectónico.

**Cambios en `.env` que no toman efecto**
Si corriste `php artisan config:cache`, Laravel deja de leer el archivo.
`php artisan config:clear`.

**Conexión rechazada a MySQL**
Verificá que el servicio esté arriba (`brew services list`) y que `DB_HOST` sea
`127.0.0.1` y no `mysql`, que es el nombre del contenedor y solo resuelve dentro
de Docker.

---

## Estado del proyecto

**Hecho**: esqueleto Laravel 12, pipeline Vite + Tailwind 4, identidad visual del
sitio madre portada a componentes Blade, home, rutas placeholder de todas las
secciones, y procedimiento de despliegue.

**Pendiente**: todo el dominio — migraciones, modelos, autenticación, cursos,
quizzes, tareas, certificados y el panel de administración (§11 del plan).
