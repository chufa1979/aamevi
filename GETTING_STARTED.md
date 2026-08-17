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
php artisan db:seed
```

`db:seed` es idempotente: se puede volver a correr sin duplicar nada. Deja las
cuentas de prueba —todas con contraseña `password`— y un programa completo de
cinco cursos con avance simulado, para que el panel se vea como se va a ver en
uso. El detalle está en el README, en «Contenido de ejemplo».

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
| Vaciar la cola de correos | `php artisan emails:enviar` |
| Ver lo programado | `php artisan schedule:list` |
| Recurso de Filament | `php artisan make:filament-resource Foo --generate` |
| Assets de Filament | `php artisan filament:assets` |
| Limpiar cachés | `php artisan optimize:clear` |

Los recursos de Filament conviene **generarlos y después revisarlos**: el
generador tiene dos defectos que se repiten —los selects de relación muestran el
UUID crudo, y los campos de contraseña pisan el hash con `null` al editar—.

### Front-end

```bash
npm run dev            # desarrollo, con HMR
npm run build          # build de producción a public/build
```

### Calidad

```bash
php artisan test                              # todos los tests
php artisan test tests/Feature/Admin          # una carpeta
php artisan test --filter TeacherPanelTest    # una clase
php artisan test --coverage
./vendor/bin/pint                             # formatear
./vendor/bin/pint --test                      # verificar sin escribir
```

---

## Estructura

```
app/Console/Commands/   Lo que corre por cron: la cola de correos
app/Events/             Lo que pasó; app/Listeners/, quién reacciona
app/Http/Controllers/   Auth y Classroom (el aula)
app/Http/Requests/      Validación con FormRequest (no reglas inline)
app/Models/             Eloquent, con trait HasUuids
app/Services/           Quiz, Progreso, Inscripciones, Entregas, Certificados,
                        Avisos, Comunicaciones, Consultas y Buscador
app/Policies/           Quién puede qué; los dos paneles las comparten
app/Filament/           Recursos del panel, con Schemas/ y Tables/ aparte
app/Providers/Filament/ Un provider por panel: admin y profesores
config/navigation.php   Fuente única del menú y datos de contacto
database/migrations/    Único lugar donde cambia el esquema
resources/css/app.css   Tokens de marca y semánticos en @theme (Tailwind 4)
resources/views/
  layouts/, classroom/, components/, emails/, certificates/
routes/web.php          Grupo guest (login) y grupo auth (todo lo demás)
routes/console.php      Lo programado: emails:enviar y emails:recordatorios
```

La regla al agregar algo: **decidir primero de qué superficie es**. La
administración del curso es Filament; el catálogo y el aula son Blade. No
comparten vistas ni controladores, sólo modelos y sesión.

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

**Los tests corren sin base de datos**
Es a propósito: `phpunit.xml` fuerza sqlite en memoria. **No apuntarlos a MySQL**
— usan `RefreshDatabase`, que dropea todas las tablas del servidor que figure en
`.env`.

**Cambios en `.env` que no toman efecto**
Si corriste `php artisan config:cache`, Laravel deja de leer el archivo.
`php artisan config:clear`.

**Conexión rechazada a MySQL**
Verificá que el servicio esté arriba (`brew services list`) y que `DB_HOST` sea
`127.0.0.1`. Si en `.env` figura un host remoto, estás apuntando a otra base.

---

## Estado del proyecto

Está en el [README](./README.md#estado), que es donde se mantiene al día: qué
superficies existen, qué solapas tiene el panel y qué falta. Acá no se repite
para que no queden dos versiones distintas de lo mismo.
