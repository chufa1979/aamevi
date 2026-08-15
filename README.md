# AAMEVi — Plataforma de E-Learning

Plataforma de formación en medicina del estilo de vida para profesionales de la
salud, de la **Asociación Argentina de Medicina del Estilo de Vida**.

Construida con **Laravel 12 + Blade + MySQL 8**.

## Estado

En desarrollo. Hay dos superficies funcionando sobre el mismo dominio de datos:

**Sitio público** (Blade) — la plataforma es **privada**: sin sesión iniciada no
se ve nada, ni el menú. El acceso es por `/login`, con limitador de intentos y
bloqueo de cuentas desactivadas. La identidad visual de
[www.aamevi.ar](https://www.aamevi.ar) está portada a componentes Blade.

**Panel de administración** (Filament, en `/admin`) — solo para el rol
administrador. El menú tiene cuatro grupos: **Cursos**, **Alumnos**,
**Evaluación** y **Sistema**; y cada curso se abre en solapas:

| Solapa | Qué hace |
|---|---|
| Info general | Título, descripción, docente y cupo |
| Planificación | Cronograma completo del curso, con corrimiento de fechas en lote |
| Contenidos | Módulos → clases → material: video con previsualización, PDF con subida y descarga, texto y consignas |
| Exámenes | Exámenes de módulo, con aviso cuando el banco de preguntas está vacío |
| Alumnos del curso | Inscripciones, con aprobación, rechazo y control de cupo |
| Seguimiento alumnos | Grilla de alumnos por clases: aprobada, en curso, bloqueada o no habilitada |

Las evaluaciones son de dos tipos: la **autoevaluación** de cada clase, que
sortea preguntas de su propio banco, y el **examen de módulo**, que sortea un
porcentaje del banco combinado de todas sus clases y es opcional.

Faltan tres solapas —Calificaciones, Comunicación y Consultas a mesa de ayuda—
diseñadas en §13 del plan arquitectónico.

La lógica del alumno también está implementada y probada —sorteo de preguntas,
corrección automática, reintentos, y el desbloqueo de una clase al aprobar la
anterior— pero **todavía no tiene pantallas**: las secciones del sitio siguen
sirviendo un marcador.

**Pendiente**: el aula pública, la pantalla de rendir un quiz, las
calificaciones, las notificaciones y los certificados. El
[plan arquitectónico](./docs/PLAN_ARQUITECTONICO.md) lleva la cuenta de qué está
hecho; su §3-bis documenta las reglas de negocio implementadas y su §13 el
análisis de un LMS en producción del que salió la organización del panel.

---

## Funcionalidad prevista

| | |
|---|---|
| **Cursos con módulos y clases** | Estructura jerárquica: curso → módulo → clase → contenido |
| **Quiz** | Preguntas aleatorias por alumno, calificación automática, reintentos |
| **Contenido multimodal** | Videos, PDFs, textos, tareas y clases en vivo por Google Meet |
| **Inscripción con aprobación** | El alumno solicita, el docente aprueba |
| **Seguimiento de progreso** | Paneles diferenciados para alumno y docente |
| **Tareas** | Envío de archivos y corrección con nota y devolución |
| **Certificados** | Generación automática de PDF al completar el curso |
| **Autenticación** | Usuario y contraseña, más Google OAuth |
| **Notificaciones** | Emails de verificación, recordatorios y certificados |
| **Panel de administración** | Backoffice en `/admin` y `/profesores` para gestionar el material |

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
| Archivos | Google Cloud Storage — la base guarda solo URLs |

No hay framework de JavaScript en el sitio público: el único JS propio es el
toggle del menú móvil.

---

## Estructura

```
aamevi/
├── app/
│   ├── Http/
│   │   ├── Controllers/     # Por dominio: Auth, Users, Courses, Quiz, Tasks…
│   │   ├── Requests/        # Validación con FormRequest
│   │   └── Middleware/
│   ├── Models/              # Eloquent
│   └── Providers/
├── bootstrap/app.php        # Esqueleto slim de Laravel 11+
├── config/
│   ├── database.php
│   └── navigation.php       # Fuente única del menú y datos de contacto
├── database/
│   ├── migrations/          # Único lugar donde cambia el esquema
│   ├── seeders/
│   └── factories/
├── public/
│   ├── index.php            # Docroot
│   ├── images/aamevi.svg
│   └── build/               # Assets compilados (no versionado)
├── resources/
│   ├── css/app.css          # Tokens de marca en @theme
│   ├── js/app.js
│   ├── lang/es/
│   └── views/
│       ├── layouts/
│       └── components/      # header, footer, page-hero, section, button…
├── routes/
│   ├── web.php
│   ├── api.php
│   └── console.php
├── tests/
│   ├── Unit/
│   └── Feature/
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
| `profesor@aamevi.ar` | profesor | el sitio |
| `alumno@aamevi.ar` | alumno | el sitio |
| `alumno01@aamevi.ar` … `alumno20@aamevi.ar` | alumnos | el sitio |

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

En total **28 módulos, 140 clases y 700 preguntas**, más 20 alumnos repartidos
en 49 inscripciones. Cada clase tiene su autoevaluación con cinco preguntas y
los cuatro tipos de material (video, PDF, texto y tarea); la mayoría de los
módulos tiene examen, y algunos no —el examen es opcional y así se ve la
diferencia—.

**El cronograma va de julio a diciembre de 2026 a propósito.** Con la fecha de
hoy en el medio, cada curso queda partido en clases ya dictadas y clases por
venir, que es lo que hace visible la progresión.

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
1. El alumno completa el formulario de registro
2. Recibe un email de verificación con link privado
3. Verifica el email y accede a la plataforma
4. Solicita inscripción a un curso
5. El docente aprueba la solicitud
6. El alumno recibe el email de bienvenida

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
4. El alumno recibe la nota por email

### Certificado
1. El alumno completa todas las clases y tareas del curso
2. El sistema genera el certificado en PDF
3. Se le envía el link de descarga

---

## Fases de desarrollo

| Fase | Descripción | Semanas |
|---|---|---|
| 0 | Base del proyecto, identidad visual y pipeline | ✅ hecho |
| 1 | Autenticación | 1-2 |
| 2 | Cursos, módulos y clases | 2-3 |
| 3 | Panel de administración | 1-2 |
| 4 | Quiz y evaluación | 2-3 |
| 5 | Tareas | 1-2 |
| 6 | Notificaciones | 1 |
| 7 | Reportes y certificados | 1-2 |

Las estimaciones vienen del plan original, que suponía dos aplicaciones
separadas. Con un monolito Blade cabe esperar algo menos de trabajo.

---

## Seguridad

- Sesión de servidor con las protecciones de Laravel (CSRF, cookies firmadas)
- Contraseñas hasheadas con bcrypt
- Validación explícita mediante `FormRequest`, nunca reglas inline
- Autorización por rol con middleware y Policies
- Credenciales y claves solo en `.env`, fuera del control de versiones y fuera
  del docroot
- `APP_DEBUG=false` en producción

---

**Última actualización**: 2026-08-14
