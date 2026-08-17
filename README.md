# AAMEVi — Plataforma de E-Learning

Plataforma de formación en medicina del estilo de vida para profesionales de la
salud, de la **Asociación Argentina de Medicina del Estilo de Vida**.

Construida con **Laravel 12 + Blade + MySQL 8**.

## Estado

En desarrollo. Hay **tres superficies** sobre el mismo dominio de datos, con una
sola puerta de entrada: `/login`, con limitador de intentos y bloqueo de cuentas
desactivadas. Ningún panel expone su propio formulario, así que hay un solo lugar
que auditar. Cada rol cae después en lo suyo —el alumno en sus cursos, el docente
y el administrador en su panel—, salvo que viniera de una URL concreta, en cuyo
caso vuelve ahí.

### El aula (Blade)

La plataforma es **privada**: sin sesión iniciada no se ve nada, ni el menú. La
identidad visual de [www.aamevi.ar](https://www.aamevi.ar) está portada a
componentes Blade.

El alumno tiene catálogo con solicitud de inscripción, sus cursos, la pantalla de
clase con su material, las evaluaciones, la entrega de trabajos prácticos, una
barra de progreso, sus certificados, un buscador, el tablón de comunicaciones de
cada curso y sus consultas a mesa de ayuda. Puede elegir **tema claro u oscuro** y **tres tamaños de
letra**; las preferencias se aplican antes del primer pintado, sin parpadeo.

Una clase se abre cuando llegó su fecha y se aprobó la anterior; `ProgressService`
decide eso en un solo lugar y sabe explicar por qué una clase está cerrada.

### Panel de administración (Filament, en `/admin`)

Solo para el rol administrador. El menú tiene cuatro grupos —**Cursos**,
**Alumnos**, **Evaluación** y **Sistema**— y cada curso se abre en solapas:

| Solapa | Qué hace |
|---|---|
| Info general | Título, descripción, docente y cupo |
| Planificación | Cronograma completo del curso, con corrimiento de fechas en lote |
| Contenidos | Módulos → clases → material: video con previsualización, PDF con subida y descarga, texto y consignas |
| Exámenes | Exámenes de módulo, con aviso cuando el banco de preguntas está vacío |
| Intentos | Qué rindió cada alumno, con qué preguntas le tocaron y qué respondió |
| Calificaciones | Bandeja de entregas: corregir con nota y devolución, y publicar en tanda |
| Alumnos del curso | Inscripciones, con aprobación, rechazo y control de cupo |
| Seguimiento alumnos | Grilla de alumnos por clases: aprobada, en curso, bloqueada o no habilitada |
| Comunicación | Tablón del curso: para todos o para un alumno, con aviso por email opcional |
| Consultas | Las preguntas de los alumnos del curso, con su hilo y su estado |

En **Sistema → Avisos por email** está la cola de correos: qué se le mandó a
quién, si salió, y el error de los que fallaron con un botón para reintentar.

Los módulos y las clases se reordenan **arrastrando**, no escribiendo un número.

Las evaluaciones son de dos tipos: la **autoevaluación** de cada clase, que
sortea preguntas de su propio banco, y el **examen de módulo**, que sortea un
porcentaje del banco combinado de todas sus clases y es opcional.

### Panel de profesores (`/profesores`)

Las mismas pantallas del curso, acotadas a los cursos que dicta cada docente. No
duplica los recursos: lo que separa a un docente de otro es
`Course::scopeVisibleTo()`, que recorta las consultas, y las policies de
`app/Policies/`, que deciden qué se puede hacer. Como Filament resuelve los
registros por la consulta del recurso, escribir a mano la URL del curso ajeno
devuelve 404.

**Pendiente**: `/ayuda`, Google OAuth y Google Cloud Storage.
El [plan arquitectónico](./docs/PLAN_ARQUITECTONICO.md) lleva la cuenta de qué
está hecho; su §3-bis documenta las reglas de negocio implementadas y su §13 el
análisis de un LMS en producción del que salió la organización del panel.

---

## Funcionalidad

| | | |
|---|---|---|
| **Cursos con módulos y clases** | Estructura jerárquica: curso → módulo → clase → contenido | ✅ |
| **Quiz** | Preguntas aleatorias por alumno, calificación automática, reintentos | ✅ |
| **Contenido multimodal** | Videos, PDFs, textos y consignas | ✅ |
| **Inscripción con aprobación** | El alumno solicita, el docente aprueba | ✅ |
| **Seguimiento de progreso** | Grilla de curso para el docente, barra de avance para el alumno | ✅ |
| **Tareas** | Envío de archivos y corrección con nota y devolución | ✅ |
| **Panel de administración** | Backoffice en `/admin` y `/profesores` para gestionar el material | ✅ |
| **Autenticación** | Usuario y contraseña | ✅ |
| **Clases en vivo** | La clase marcada en vivo muestra su enlace de Google Meet | ✅ |
| **Certificados** | PDF de finalización, emitido solo al completar el curso | ✅ |
| **Registro público** | Alta de cuenta con verificación por email | ✅ |
| **Google OAuth** | Inicio de sesión con cuenta de Google | ⏳ |
| **Notificaciones** | Avisos por email de inscripción, corrección, certificado, clase en vivo, comunicación y respuesta a una consulta | ✅ |
| **Comunicaciones** | Tablón del curso, con aviso por email opcional | ✅ |
| **Mesa de ayuda** | Consultas por curso, con hilo y estado | ✅ |

---

## Stack

| Capa | Tecnología |
|---|---|
| Framework | Laravel 12 (PHP 8.2+) |
| Vistas | Blade, renderizado en servidor |
| ORM | Eloquent, con UUIDs como clave primaria |
| Base de datos | MySQL 8 / MariaDB (InnoDB, `utf8mb4_unicode_ci`) |
| Estilos | Tailwind 4, configurado en CSS con `@theme` |
| Build | Vite 8 (requiere Node ≥ 20.19) |
| Autenticación | Sesión de Laravel; Sanctum disponible para una futura API |
| Archivos | Disco `public` de Laravel. Google Cloud Storage está previsto, todavía no configurado |

No hay framework de JavaScript en el aula: el JS propio son el toggle del menú
móvil y los controles de tema y tamaño de letra. El panel sí lleva Livewire, que
viene con Filament.

---

## Estructura

```
aamevi/
├── app/
│   ├── Console/Commands/    # Lo que corre por cron: la cola de correos
│   ├── Enums/               # Estados y roles, con su etiqueta y su color
│   ├── Exceptions/          # Excepciones de negocio: fallan fuerte, no devuelven false
│   ├── Filament/            # El panel: recursos, formularios, tablas, acciones
│   │   └── Resources/       # Uno por carpeta, con Schemas/ y Tables/ aparte
│   ├── Http/
│   │   ├── Controllers/     # Auth y Classroom (el aula)
│   │   ├── Requests/        # Validación con FormRequest
│   │   └── Middleware/
│   ├── Models/              # Eloquent
│   ├── Policies/            # Quién puede qué; los dos paneles las comparten
│   ├── Providers/Filament/  # Un provider por panel: admin y profesores
│   ├── Events/              # Lo que pasó, para que reaccione quien quiera
│   ├── Listeners/           # Quién reacciona
│   ├── Services/            # Quiz, Progreso, Inscripciones, Entregas,
│   │                        #   Certificados y Avisos
│   └── Support/Html.php     # Saneado del texto enriquecido
├── bootstrap/app.php        # Esqueleto slim de Laravel 11+
├── config/
│   ├── database.php
│   └── navigation.php       # Fuente única del menú y datos de contacto
├── database/
│   ├── migrations/          # Único lugar donde cambia el esquema
│   ├── seeders/             # DatabaseSeeder, StudentSeeder, CourseSeeder
│   └── factories/
├── public/
│   ├── index.php            # Docroot
│   ├── images/aamevi.svg    # Y aamevi-dark.svg, para el modo oscuro
│   └── build/               # Assets compilados (no versionado)
├── resources/
│   ├── css/
│   │   ├── app.css          # Tokens de marca y semánticos en @theme
│   │   └── filament/        # Estilos del panel: no comparte bundle con el sitio
│   ├── js/
│   │   ├── app.js
│   │   └── preferences.js   # Tema y tamaño de letra
│   ├── lang/es/
│   └── views/
│       ├── layouts/
│       ├── partials/        # preferences-head: se aplica antes del primer pintado
│       ├── classroom/       # El aula
│       └── components/      # header, footer, button, rich-text, classroom/…
├── routes/
│   ├── web.php
│   ├── api.php
│   └── console.php
├── tests/
│   ├── Unit/
│   └── Feature/             # Admin/, Teacher/, Classroom/, Auth/
├── docs/
│   ├── PLAN_ARQUITECTONICO.md
│   ├── SISTEMA_DISENO.md
│   └── DEPLOY.md
├── composer.json
├── package.json
└── vite.config.js
```

---

## Puesta en marcha

Requiere **PHP ≥ 8.2**, **Composer 2**, **Node ≥ 20.19** y **MySQL 8 o MariaDB**.

```bash
git clone https://github.com/chufa1979/aamevi.git
cd aamevi

composer install
npm install

cp .env.example .env
php artisan key:generate
```

Antes de migrar hay que tener la base corriendo — ver la sección siguiente.

```bash
php artisan migrate
php artisan db:seed
```

Desarrollo con **dos procesos**, en dos terminales:

```bash
php artisan serve      # http://localhost:8000
npm run dev            # servidor de Vite con recarga en caliente
```

El detalle, los comandos habituales y los problemas frecuentes están en
[`GETTING_STARTED.md`](./GETTING_STARTED.md).

---

## Base de datos local

El servidor de producción corre **MariaDB**, que es compatible con MySQL 8 para
todo lo que usa el proyecto. En macOS conviene instalarla por Homebrew.

> **En Mac con chip Apple**: instalá desde el Homebrew nativo
> (`/opt/homebrew`), no desde el de Intel (`/usr/local`). Si `brew --prefix`
> devuelve `/opt/homebrew` pero `which mysql` apunta a `/usr/local/bin`, tenés
> la versión x86 corriendo bajo Rosetta.

### Instalar y arrancar

```bash
brew install mariadb
brew services start mariadb      # arranca ahora y en cada login
```

Sin servicio en segundo plano, a mano:

```bash
/opt/homebrew/opt/mariadb/bin/mariadbd-safe --datadir=/opt/homebrew/var/mysql
```

Comandos del servicio:

| | |
|---|---|
| Estado | `brew services list \| grep mariadb` |
| Arrancar | `brew services start mariadb` |
| Parar | `brew services stop mariadb` |
| Reiniciar | `brew services restart mariadb` |
| ¿Está escuchando? | `mariadb-admin ping` |

### Crear la base y el usuario

Una instalación nueva de Homebrew **no le pone contraseña a tu usuario del
sistema**: entrás con `mariadb` a secas, por socket. `root`, en cambio, suele
estar restringido y devuelve `ERROR 1698`.

```bash
mariadb -e "
CREATE DATABASE IF NOT EXISTS aamevi_db
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'aamevi'@'127.0.0.1' IDENTIFIED BY 'aamevi';
CREATE USER IF NOT EXISTS 'aamevi'@'localhost' IDENTIFIED BY 'aamevi';
GRANT ALL PRIVILEGES ON aamevi_db.* TO 'aamevi'@'127.0.0.1';
GRANT ALL PRIVILEGES ON aamevi_db.* TO 'aamevi'@'localhost';
FLUSH PRIVILEGES;"
```

Se crea el usuario **dos veces a propósito**: MySQL trata `localhost` (conexión
por socket) y `127.0.0.1` (por TCP) como hosts distintos a la hora de dar
permisos. Laravel se conecta por TCP, pero el cliente de consola usa el socket.

### Configurar el `.env`

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=aamevi_db
DB_USERNAME=aamevi
DB_PASSWORD=aamevi
```

> ⚠️ **Revisá que `DB_HOST` no sea la IP del servidor.** Si apunta a producción,
> `php artisan migrate:fresh` o `db:wipe` borran la base real. El `.env` de
> desarrollo tiene que decir `127.0.0.1`.

### Verificar

```bash
php artisan config:clear     # el .env no se relee si la config está cacheada
php artisan db:show          # tiene que decir Host 127.0.0.1
php artisan migrate:status   # ninguna pendiente
```

### Usuarios de prueba

`php artisan db:seed` deja tres cuentas, todas con contraseña `password`:

| Email | Rol | Entra a |
|---|---|---|
| `admin@aamevi.ar` | administrador | `/admin` y el sitio |
| `profesor@aamevi.ar` | profesor | `/profesores` — dicta 3 de los 5 cursos |
| `profesora@aamevi.ar` | profesora | `/profesores` — dicta los otros 2 |
| `alumno@aamevi.ar` | alumno | el aula |
| `alumno01@aamevi.ar` … `alumno20@aamevi.ar` | alumnos | el aula, con avance simulado |

Los dos docentes están repartidos a propósito: con uno solo no se vería que cada
panel muestra únicamente sus cursos.

### Contenido de ejemplo

`db:seed` carga un programa completo, para que el panel se vea como se va a ver
en uso y no con dos cursos de juguete:

| Curso | Módulos | Clases |
|---|---|---|
| Fundamentos de la Medicina del Estilo de Vida | 5 | 25 |
| Nutrición basada en plantas y prescripción alimentaria | 8 | 40 |
| Actividad física y prescripción del ejercicio | 6 | 30 |
| Sueño, estrés y salud mental | 4 | 20 |
| Vínculos, comunidad y cambio de comportamiento | 5 | 25 |
| Introducción a la Medicina del Estilo de Vida (edición 2025) | 2 | 10 |

En total **30 módulos, 150 clases y 750 preguntas**, más 20 alumnos repartidos
en 49 inscripciones. Cada clase tiene su autoevaluación con cinco preguntas y
los cuatro tipos de material (video, PDF, texto y tarea); la mayoría de los
módulos tiene examen, y algunos no —el examen es opcional y así se ve la
diferencia—.

**El cronograma va de julio a diciembre de 2026 a propósito.** Con la fecha de
hoy en el medio, cada curso queda partido en clases ya dictadas y clases por
venir, que es lo que hace visible la progresión.

La sexta, **la edición 2025, es la excepción: ya terminó**. Existe porque
ninguno de los otros cinco cierra antes de diciembre, así que sin ella no habría
un solo alumno recibido y las pantallas de certificados quedarían vacías. De ahí
salen las inscripciones finalizadas y los tres certificados emitidos. Queda
**inactiva**, o sea fuera del catálogo: una edición terminada no se puede cursar,
pero los que la hicieron conservan su acceso y su certificado.

Los alumnos avanzan a **ritmos distintos** —al día, atrasados, recién empezando
o sin entrar nunca—, porque una grilla de seguimiento donde todos van igual no
muestra nada. El avance se simula con `QuizService` y `ProgressService`, no
escribiendo las tablas a mano: los intentos quedan con sus preguntas sorteadas y
sus respuestas, y nadie figura aprobado sin haber rendido.

Las inscripciones cubren los tres estados —aprobada, pendiente y rechazada— para
que la solapa *Alumnos del curso* tenga qué mostrar.

Los videos apuntan a cortos de la Blender Foundation y los PDF a un archivo de
prueba del W3C: son marcadores de posición, pero cargan de verdad, así que la
previsualización se ve funcionando. **El texto de las preguntas es de relleno**
—se arma combinando el título de la clase con cinco plantillas— y hay que
reemplazarlo por material real.

Los tres seeders son idempotentes: se pueden volver a correr sin duplicar nada.

### Los tests no usan MySQL

`phpunit.xml` fuerza **sqlite en memoria**, así que `php artisan test` corre sin
levantar la base. Es a propósito y **no hay que cambiarlo**: los tests usan
`RefreshDatabase`, que dropea todas las tablas: si la conexión saliera del
`.env`, un test correría contra el servidor que ahí figure.

### Problemas frecuentes

| Síntoma | Causa |
|---|---|
| `Table 'aamevi_db.users' doesn't exist` con un `Host` que es una IP | El `.env` apunta a producción — corregí `DB_HOST` |
| `Connection refused` en `127.0.0.1:3306` | El servicio no está arrancado |
| `Access denied for user 'aamevi'@'127.0.0.1'` | Falta el `GRANT` para ese host — ver arriba, son dos usuarios |
| `ERROR 1698 (28000)` al hacer `mariadb -u root` | `root` usa autenticación por socket: entrá con `mariadb` sin `-u` |
| Cambiás el `.env` y no toma efecto | Config cacheada: `php artisan config:clear` |
| `Can't connect through socket '/tmp/mysql.sock'` | Servidor apagado, o quedó instalado el binario de otra arquitectura |

---

## Documentación

| Documento | Contenido |
|---|---|
| [Plan arquitectónico](./docs/PLAN_ARQUITECTONICO.md) | Modelo de datos, flujos, decisiones y timeline |
| [Sistema de diseño](./docs/SISTEMA_DISENO.md) | Paleta, tipografía y elementos heredados de www.aamevi.ar |
| [Guía de deploy](./docs/DEPLOY.md) | Despliegue en el hosting, con sus restricciones |
| [Guía de inicio](./GETTING_STARTED.md) | Entorno local, comandos y convenciones |
| [CLAUDE.md](./CLAUDE.md) | Convenciones para trabajar en el repo |

---

## Flujos principales

### Inscripción de alumno
1. El alumno completa el formulario de registro y queda con la cuenta creada
   pero sin verificar: lo único que puede hacer es verificar
2. Recibe un correo con un enlace firmado y con vencimiento
3. Lo abre y entra al catálogo
4. Solicita inscripción a un curso
5. El docente la aprueba desde el panel
6. Le llega el aviso de que ya puede empezar

Registrarse no da acceso a ningún curso: la inscripción la sigue aprobando una
persona. Por eso el alta puede ser abierta.

### Progresión por clase
1. Accede al contenido de la clase: videos, PDFs, textos
2. Responde el quiz
   - El sistema sortea N preguntas del banco de esa clase, distintas por alumno
   - Se registra qué preguntas le tocaron, para que el intento sea auditable
   - Calificación automática, con resultado inmediato
3. Si alcanza la nota mínima, se desbloquea la clase siguiente
4. Si no, puede reintentar hasta el máximo configurado

### Tareas
1. El docente publica la consigna con fecha de entrega
2. El alumno envía su archivo
3. El docente lo descarga, califica y deja devolución
4. Al publicar la corrección, el alumno recibe la nota por email

### Certificado
1. El alumno completa todas las clases y le aprueban todas las tareas
2. El sistema emite el certificado con su número, y la inscripción queda
   finalizada
3. El alumno lo descarga en PDF desde su sección de certificados

El docente puede emitirlo a mano desde el panel para los casos que la regla no
contempla.

---

## Fases de desarrollo

| Fase | Descripción | Estado |
|---|---|---|
| 0 | Base del proyecto, identidad visual y pipeline | ✅ |
| 1 | Autenticación | ✅ |
| 2 | Cursos, módulos y clases | ✅ |
| 3 | Panel de administración | ✅ |
| 4 | Quiz y evaluación | ✅ |
| 5 | Tareas | ✅ |
| 6 | Notificaciones | ✅ |
| 7 | Reportes y certificados | ⏳ falta el modelo visual y los reportes |

Fuera del plan original quedaron dos cosas que sí se hicieron: el **aula del
alumno** —que el plan daba por sentada— y el **panel de profesores**.

---

## Seguridad

- Sesión de servidor con las protecciones de Laravel (CSRF, cookies firmadas)
- Contraseñas hasheadas con bcrypt
- Validación explícita mediante `FormRequest`, nunca reglas inline
- Autorización por rol con middleware y Policies; los recursos del panel recortan
  además sus consultas, así que un docente no puede abrir el curso de otro ni
  escribiendo la URL
- El texto enriquecido cargado desde el panel se sanea al mostrarlo
  (`App\Support\Html`); el único `{!! !!}` del proyecto vive dentro de `<x-rich-text>`
- Credenciales y claves solo en `.env`, fuera del control de versiones y fuera
  del docroot
- `APP_DEBUG=false` en producción

---

**Última actualización**: 2026-08-16
