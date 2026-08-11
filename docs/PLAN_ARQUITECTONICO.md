# Plan Arquitectónico — AAMEVi
## Laravel 12 + Blade + MySQL 8

**Fecha**: 2026-07-28  
**Escala**: 50 alumnos/curso (inicial), extensible a 1000+  
**Tecnología**: Laravel 12 (PHP 8.2+) + Blade + Vite 8 + Tailwind 4 + MySQL 8
**Actualizado**: 2026-08-11

> **Nota de revisión — 2026-08-11**
>
> Este documento nació describiendo una migración a monorepo React + NestJS
> desplegada en GCP, bajo el nombre OSDOP. El proyecto cambió de rumbo: hoy es
> una aplicación Laravel monolítica con vistas Blade, desplegada en hosting
> compartido. Las secciones 1 y 4 a 11 fueron reescritas para reflejarlo.
>
> **Las secciones 2 (modelo de datos) y 3 (flujos) se conservan sin cambios**:
> describen el dominio, no el stack, y siguen siendo el contrato a implementar.

---

## 1. ESTRUCTURA DEL PROYECTO

Aplicación Laravel única. No hay separación backend/frontend: las vistas Blade
se renderizan en el mismo proceso que resuelve la lógica de negocio.

Marcado con ✅ lo que ya existe en el repo; el resto es lo previsto.

Hay **dos superficies** sobre el mismo dominio de datos: el sitio público, en
Blade, y el panel de administración, en Filament (§11). No comparten vistas ni
controladores; sí comparten modelos, sesión y reglas de acceso.

```
aamevi/
├── app/
│   ├── Enums/
│   │   └── UserRole.php     ✅ admin | teacher | student
│   ├── Filament/                  # Panel de administración (§11)
│   │   └── Resources/
│   │       ├── Users/       ✅ CRUD de usuarios + fichas de alumno/profesor
│   │       ├── Courses/     ✅ CRUD de cursos + ModulesRelationManager
│   │       └── CourseModules/ ✅ Pantalla del módulo + ClassesRelationManager
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/        ✅ AuthenticatedSessionController
│   │   │   ├── Courses/        # Catálogo, inscripción y aula (sitio público)
│   │   │   ├── Quiz/           # Intentos y calificación
│   │   │   ├── Tasks/          # Envío de tareas
│   │   │   ├── Certificates/   # Descarga
│   │   │   └── Reports/
│   │   ├── Requests/        ✅ Auth/LoginRequest
│   │   └── Middleware/
│   ├── Models/              ✅ User, Student, Teacher, Course,
│   │                           CourseModule, CourseClass
│   └── Providers/
│       └── Filament/AdminPanelProvider.php ✅
├── bootstrap/app.php        ✅ Esqueleto slim de Laravel 11+
├── config/
│   ├── auth.php             ✅
│   ├── database.php         ✅ mysql + sqlite (esta última solo para tests)
│   └── navigation.php       ✅ Fuente única del menú público
├── database/
│   ├── migrations/          ✅ users, password_reset_tokens, students,
│   │                           teachers, courses, modules, classes
│   ├── factories/           ✅ Una por modelo
│   └── seeders/             ✅ Un usuario por rol
├── public/
│   ├── index.php            ✅ Docroot
│   ├── images/              ✅ aamevi.svg y su variante para modo oscuro
│   ├── build/                  Generado por Vite (no versionado)
│   └── css|js|fonts/filament/  Publicado por Filament (no versionado)
├── resources/
│   ├── css/app.css          ✅ Tokens de marca en @theme (Tailwind 4)
│   ├── js/app.js            ✅ Menú hamburguesa; sin framework JS
│   ├── lang/es/auth.php     ✅
│   └── views/
│       ├── layouts/
│       │   ├── app.blade.php   ✅ Sitio, con navegación
│       │   └── guest.blade.php ✅ Acceso, sin navegación
│       ├── components/      ✅ header, footer, top-bar, page-hero, section,
│       │   └── ui/icon.blade.php ✅ (x-icon lo toma blade-icons)
│       ├── auth/            ✅ login, register
│       ├── home.blade.php   ✅
│       └── placeholder.blade.php ✅
├── routes/web.php           ✅ Grupos `guest` y `auth`
├── tests/Feature/           ✅ Auth/ y Admin/
├── docs/                    ✅ Este plan, SISTEMA_DISENO.md, DEPLOY.md
├── deploy.sh                ✅ Ciclo de actualización en el servidor
├── composer.json            ✅
└── vite.config.js           ✅
```

**Sobre `docs/SISTEMA_DISENO.md`**: describe la paleta, tipografía y elementos
característicos heredados de www.aamevi.ar. Su texto todavía referencia rutas
`frontend/src/...` de la etapa React; los tokens que enumera son correctos y
viven hoy en `resources/css/app.css`.

---

## 2. MODELO DE DATOS NORMALIZADO

### Usuarios & Autenticación
```sql
-- Tabla base (todos los usuarios)
CREATE TABLE users (
  id CHAR(36) PRIMARY KEY,
  email VARCHAR(255) UNIQUE NOT NULL,
  password_hash VARCHAR(255),
  first_name VARCHAR(100),
  last_name VARCHAR(100),
  role ENUM ('admin', 'teacher', 'student'), -- Roles
  is_active BOOLEAN DEFAULT true,
  email_verified BOOLEAN DEFAULT false,
  email_verification_token VARCHAR(255),
  oauth_provider VARCHAR(50), -- 'google', null
  oauth_id VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Información estudiante (extensión de users)
CREATE TABLE students (
  id CHAR(36) PRIMARY KEY REFERENCES users(id) ON DELETE CASCADE,
  dni VARCHAR(20) UNIQUE,
  date_of_birth DATE,
  phone VARCHAR(20),
  cell_phone VARCHAR(20),
  sub_delegation VARCHAR(100),
  delegation VARCHAR(100),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Información profesor
CREATE TABLE teachers (
  id CHAR(36) PRIMARY KEY REFERENCES users(id) ON DELETE CASCADE,
  bio TEXT,
  specialization VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

> **Desviación implementada (2026-08-11)** — Las migraciones de `users` renombran
> tres columnas respecto de este DDL, para poder usar lo que Laravel ya trae en
> lugar de reimplementarlo:
>
> | Acá | Implementado | Qué habilita |
> |---|---|---|
> | `password_hash` | `password` | `Auth::attempt()` |
> | `email_verified` (bool) | `email_verified_at` (timestamp) | La interfaz `MustVerifyEmail` |
> | `email_verification_token` | *(eliminada)* | Verificación por URL firmada |
> | *(no existía)* | `remember_token` | El "recordarme" del login |
>
> La semántica del modelo no cambia. Se agrega además la tabla
> `password_reset_tokens`, que el broker de contraseñas necesita y que este plan
> no contemplaba porque resolvía la autenticación con JWT.
>
> `students` y `teachers` llevan `updated_at` además de `created_at`.

### Cursos, Módulos & Clases
```sql
CREATE TABLE courses (
  id CHAR(36) PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  description TEXT,
  teacher_id CHAR(36) NOT NULL REFERENCES teachers(id) ON DELETE CASCADE,
  max_students INT DEFAULT 50,
  is_active BOOLEAN DEFAULT true,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Inscripción a cursos (con estado)
CREATE TABLE course_enrollments (
  id CHAR(36) PRIMARY KEY,
  course_id CHAR(36) NOT NULL REFERENCES courses(id) ON DELETE CASCADE,
  student_id CHAR(36) NOT NULL REFERENCES students(id) ON DELETE CASCADE,
  enrollment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  status ENUM ('pending', 'approved', 'rejected', 'active', 'completed'),
  approval_date TIMESTAMP,
  approved_by CHAR(36) REFERENCES teachers(id),
  UNIQUE(course_id, student_id)
);

-- Módulos (planificación)
CREATE TABLE modules (
  id CHAR(36) PRIMARY KEY,
  course_id CHAR(36) NOT NULL REFERENCES courses(id) ON DELETE CASCADE,
  title VARCHAR(255) NOT NULL,
  description TEXT,
  order_number INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Clases
CREATE TABLE classes (
  id CHAR(36) PRIMARY KEY,
  module_id CHAR(36) NOT NULL REFERENCES modules(id) ON DELETE CASCADE,
  title VARCHAR(255) NOT NULL,
  description TEXT,
  order_number INT,
  activation_date TIMESTAMP NOT NULL, -- Cuándo está disponible
  is_live_session BOOLEAN DEFAULT false,
  meet_link VARCHAR(500), -- Link a Google Meet (si es en vivo)
  is_live_recording_available BOOLEAN DEFAULT false,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Contenido de Clase
```sql
-- Contenido multimodal (videos, PDFs, textos, tareas)
CREATE TABLE class_content (
  id CHAR(36) PRIMARY KEY,
  class_id CHAR(36) NOT NULL REFERENCES classes(id) ON DELETE CASCADE,
  type ENUM ('video', 'pdf', 'text', 'task'),
  title VARCHAR(255),
  description TEXT,
  content_url VARCHAR(500), -- URL en Google Cloud Storage
  order_number INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tareas (assignments)
CREATE TABLE tasks (
  id CHAR(36) PRIMARY KEY,
  class_id CHAR(36) NOT NULL REFERENCES classes(id) ON DELETE CASCADE,
  title VARCHAR(255) NOT NULL,
  description TEXT,
  due_date TIMESTAMP,
  max_score INT DEFAULT 100,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE task_submissions (
  id CHAR(36) PRIMARY KEY,
  task_id CHAR(36) NOT NULL REFERENCES tasks(id) ON DELETE CASCADE,
  student_id CHAR(36) NOT NULL REFERENCES students(id) ON DELETE CASCADE,
  submission_url VARCHAR(500), -- URL del archivo en Cloud Storage
  submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  score INT,
  feedback TEXT,
  graded_at TIMESTAMP,
  graded_by CHAR(36) REFERENCES teachers(id),
  UNIQUE(task_id, student_id)
);
```

### Preguntas & Quiz
```sql
-- Banco de preguntas (principal)
CREATE TABLE questions (
  id CHAR(36) PRIMARY KEY,
  class_id CHAR(36) NOT NULL REFERENCES classes(id) ON DELETE CASCADE,
  text TEXT NOT NULL,
  question_type ENUM ('multiple_choice') DEFAULT 'multiple_choice',
  is_active BOOLEAN DEFAULT true,
  created_by CHAR(36) NOT NULL REFERENCES teachers(id),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Opciones de respuesta
CREATE TABLE question_options (
  id CHAR(36) PRIMARY KEY,
  question_id CHAR(36) NOT NULL REFERENCES questions(id) ON DELETE CASCADE,
  option_text TEXT NOT NULL,
  is_correct BOOLEAN DEFAULT false,
  order_number INT
);

-- Quiz configuración por clase
CREATE TABLE quizzes (
  id CHAR(36) PRIMARY KEY,
  class_id CHAR(36) NOT NULL REFERENCES classes(id) ON DELETE CASCADE,
  title VARCHAR(255),
  questions_per_student INT DEFAULT 3, -- Cuántas preguntas ve cada alumno
  passing_score INT DEFAULT 70, -- % mínimo
  max_attempts INT DEFAULT 3, -- Reintentos permitidos
  show_correct_answers BOOLEAN DEFAULT true,
  randomize_options BOOLEAN DEFAULT true,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Respuestas de estudiante (lo que responde)
CREATE TABLE student_quiz_attempts (
  id CHAR(36) PRIMARY KEY,
  quiz_id CHAR(36) NOT NULL REFERENCES quizzes(id) ON DELETE CASCADE,
  student_id CHAR(36) NOT NULL REFERENCES students(id) ON DELETE CASCADE,
  attempt_number INT DEFAULT 1,
  started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  submitted_at TIMESTAMP,
  score INT,
  passed BOOLEAN,
  is_passed_threshold BOOLEAN, -- ¿Pasó el mínimo?
  UNIQUE(quiz_id, student_id, attempt_number)
);

CREATE TABLE student_answers (
  id CHAR(36) PRIMARY KEY,
  attempt_id CHAR(36) NOT NULL REFERENCES student_quiz_attempts(id) ON DELETE CASCADE,
  question_id CHAR(36) NOT NULL REFERENCES questions(id) ON DELETE CASCADE,
  selected_option_id CHAR(36) NOT NULL REFERENCES question_options(id),
  is_correct BOOLEAN,
  answered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Randomización per alumno (para saber qué preguntas vio cada uno)
CREATE TABLE quiz_question_assignment (
  id CHAR(36) PRIMARY KEY,
  attempt_id CHAR(36) NOT NULL REFERENCES student_quiz_attempts(id),
  question_id CHAR(36) NOT NULL REFERENCES questions(id),
  assigned_order INT
);
```

### Progreso & Certificados
```sql
-- Progreso del estudiante
CREATE TABLE student_progress (
  id CHAR(36) PRIMARY KEY,
  enrollment_id CHAR(36) NOT NULL REFERENCES course_enrollments(id) ON DELETE CASCADE,
  class_id CHAR(36) NOT NULL REFERENCES classes(id) ON DELETE CASCADE,
  completed_at TIMESTAMP,
  quiz_passed BOOLEAN,
  tasks_completed INT DEFAULT 0,
  last_accessed TIMESTAMP,
  UNIQUE(enrollment_id, class_id)
);

-- Certificados
CREATE TABLE certificates (
  id CHAR(36) PRIMARY KEY,
  enrollment_id CHAR(36) NOT NULL REFERENCES course_enrollments(id) ON DELETE CASCADE,
  certificate_number VARCHAR(50) UNIQUE,
  issued_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  pdf_url VARCHAR(500), -- PDF generado
  UNIQUE(enrollment_id)
);
```

### Notificaciones
```sql
CREATE TABLE email_queue (
  id CHAR(36) PRIMARY KEY,
  recipient_id CHAR(36) NOT NULL REFERENCES users(id),
  email_type ENUM ('verification', 'enrollment_approved', 'class_reminder', 'certificate', 'task_graded'),
  subject VARCHAR(255),
  body TEXT,
  scheduled_at TIMESTAMP,
  sent_at TIMESTAMP,
  status ENUM ('pending', 'sent', 'failed') DEFAULT 'pending',
  retry_count INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

---

## 3. FLUJOS PRINCIPALES

### A) Inscripción de Estudiante
```
1. Formulario de inscripción en frontend
   → Email + contraseña + datos personales (DNI, teléfono, etc)

2. Backend:
   - Validar email único
   - Hash de contraseña
   - Crear user + student
   - Generar email_verification_token
   - Encolar email de verificación

3. Email enviado a alumno:
   "Haz clic aquí para verificar tu email: 
    https://platform.com/verify?token=xyz"

4. Alumno hace clic → email_verified = true

5. Inscripción al curso:
   - Crear course_enrollment con status='pending'
   - Notificar al profesor

6. Profesor aprueba inscripción (admin panel):
   - status='approved'
   - approval_date = NOW
   - Enviar email al alumno: "¡Bienvenido! Ya puedes acceder"

7. Alumno accede → status='active'
```

### B) Progresión por Clase
```
1. Profesor crea clase en módulo
   - Sube contenido: videos, PDFs, texto
   - Carga X preguntas en el banco
   - Configura quiz: 
     * "Mostrar 3 preguntas aleatorias de las 10 cargadas"
     * "Mínimo 70% para pasar"
     * "Máx 3 reintentos"

2. Alumno accede a clase:
   - Ve contenido (videos, PDFs, textos)
   - Completa quiz
   → Backend: genera 3 preguntas aleatorias DISTINTAS para CADA alumno

3. Alumno responde quiz (feedback en TIEMPO REAL):
   - Al enviar: backend califica automáticamente
   - **Pantalla de resultado inmediato**:
     * Puntuación: 75/100
     * Respuestas correctas ✅ / incorrectas ❌ (con la opción correcta)
     * Si ≥70%: "¡Pasaste! Siguiente clase ya disponible"
     * Si <70%: "No pasaste (65%). Tienes 2 reintentos restantes"
   - Alumno puede ver detalles de lo que respondió mal

4. Reintentos:
   - Cada reintento = nuevas 3 preguntas aleatorias (distintas)
   - Máximo 3 intentos (configurable por clase)
   - Historial de intentos visible en perfil

5. Si completa clase (≥ puntuación mínima):
   - Crear student_progress (completed_at = NOW)
   - Desbloquear siguiente clase automáticamente
   - Actualizar dashboard del profesor (alumno X completó clase Y)
   - Recordatorio 24h antes de siguiente clase en vivo (si existe)
```

### C) Clase en Vivo (Meet)
```
1. Profesor configura clase:
   - is_live_session = true
   - activation_date = fecha/hora de clase
   - meet_link = "https://meet.google.com/abc-def-ghi"

2. 24 horas antes:
   - Backend encola email a todos los alumnos inscritos
   - "Tu clase 'Intro a Python' es mañana a las 10:00 AM
      Link: [meet_link]"

3. Profesor inicia clase
   - Enseña en vivo por Meet
   - Graba (Google Meet graba automáticamente)

4. Después de clase:
   - Profesor sube grabación a Google Cloud Storage
   - Backend actualiza: is_live_recording_available = true
   - Alumnos pueden ver grabación

5. Después, quiz de clase puede usarse:
   - Para medir comprensión
   - O ser ignorado si es informal
```

### D) Tareas (Assignments)
```
1. Profesor adjunta tarea a clase
   - Título, descripción
   - Fecha de entrega (due_date)
   - Puntuación máxima (100 puntos)

2. Alumno:
   - Sube archivo (PDF, imagen, code, etc)
   - Backend: guarda en Google Cloud Storage
   - Crea task_submission record

3. Profesor:
   - Dashboard muestra entregas pendientes
   - Descarga archivo
   - Califica: score + feedback
   - Alumno recibe email: "Tu tarea fue calificada: 85/100"

4. Progreso de alumno:
   - student_progress.tasks_completed += 1
```

### E) Certificado
```
1. Al completar TODO el curso:
   - Profesor o sistema verifica:
     * Todas las clases completadas (todos los quiz pasados)
     * Todas las tareas calificadas
     * O si Profesor da aprobación manual

2. Backend:
   - Genera PDF dinámico
     * Nombre del alumno
     * Nombre del curso
     * Fecha de emisión
     * Número único de certificado
   - Guarda PDF en Cloud Storage
   - Envía email con link al PDF

3. Estudiante:
   - Puede descargar desde su perfil
   - Ver en lista de certificados
```

---

## 4. COMPONENTES PRINCIPALES POR MÓDULO

La sesión es la de Laravel (cookie + `web` middleware), no JWT: al renderizar
en el servidor no hay cliente separado que necesite un token. Sanctum queda
disponible por si más adelante hace falta una API para mobile.

### Módulo Auth

```php
// app/Http/Controllers/Auth/
RegisteredUserController::store(RegisterRequest)   // type: student|teacher
AuthenticatedSessionController::store(LoginRequest)
AuthenticatedSessionController::destroy()
EmailVerificationController::verify(id, hash)
GoogleOAuthController::redirect() / callback()     // Laravel Socialite
PasswordResetLinkController::store(email)
NewPasswordController::store(token, password)
```

Autorización por rol con middleware y Policies (`CoursePolicy`,
`EnrollmentPolicy`), no con chequeos sueltos en los controladores.

### Módulo Courses

> **La administración de cursos ya no vive acá.** Crear y editar cursos, módulos
> y clases se hace desde el panel de Filament (§11). Estos controladores son los
> del **sitio público**: lo que ve y hace un alumno.

```php
// app/Http/Controllers/Courses/
CourseController::index()      // GET  /cursos                catálogo
CourseController::show(Course) // GET  /cursos/{course}       (route model binding)
EnrollmentController::store(Course)  // POST /cursos/{course}/inscripcion → pending
ClassroomController::show(CourseClass) // GET /clases/{class}  aula: contenido y quiz
```

La aprobación de inscripciones (§3-A) es una acción del panel, no de este
controlador.

La lógica que excede un CRUD (transiciones de `course_enrollments`, cálculo de
progreso) va en clases de servicio bajo `app/Services/`, no en el controlador.

### Módulo Quiz

Es la parte más intrincada del dominio; ver §3-B y las tablas de §2.

```php
// app/Services/Quiz/
QuizService::createQuestion(CourseClass, text, options, correctOption)
QuizService::configure(CourseClass, questionsPerStudent, passingScore, maxAttempts)
QuizService::startAttempt(Quiz, Student): QuizAttempt
    // Sortea N preguntas del banco de la clase y las graba en
    // quiz_question_assignment, para que el intento sea reproducible
QuizService::submit(QuizAttempt, array $answers): QuizResult
    // Califica automáticamente y devuelve score + passed
QuizService::attemptsFor(Quiz, Student): Collection
```

### Vistas Blade

Sustituyen a las páginas React del plan original. Cada una extiende
`layouts.app` y reutiliza los componentes de `resources/views/components/`.

```
auth/login, auth/register          Form + botón de Google + olvido de contraseña
dashboard/student                  Mis cursos (con % de progreso), próximas
                                   clases, tareas pendientes, certificados
dashboard/teacher                  Mis cursos, inscripciones pendientes con
                                   aprobar/rechazar, tareas por calificar
courses/index, courses/show        Catálogo; detalle con árbol módulos → clases
                                   y botón de inscripción
classroom/class                    Contenido (video, PDF, texto), quiz con
                                   reintentos y score, tareas, siguiente clase
certificates/index                 Listado y descarga
```

El quiz es la única pantalla con interacción no trivial. Se resuelve con envío
de formulario por paso; si más adelante se quiere sin recargas, la vía natural
es Livewire, que fue considerado y pospuesto (§9).

---

## 5. ENTORNO LOCAL

El procedimiento paso a paso está en `GETTING_STARTED.md`. Resumen:

```bash
composer install
npm install
cp .env.example .env && php artisan key:generate
# configurar DB_* en .env
php artisan migrate
npm run dev          # Vite en watch
php artisan serve    # http://localhost:8000
```

Hacen falta **dos procesos** en desarrollo: `php artisan serve` sirve la
aplicación y `npm run dev` mantiene el servidor de Vite, que es el que resuelve
la directiva `@vite` de `layouts/app.blade.php` con recarga en caliente. Sin él,
Blade busca el manifiesto de `public/build` y hay que haber corrido `npm run build`.

**Requisitos**: PHP ≥ 8.2 con `pdo_mysql`, `mbstring`, `intl`, `gd` y `zip`;
Composer 2; Node ≥ 20.19 (Vite 8 lo exige); MySQL 8 o MariaDB.

**Sin contenedores.** El proyecto se instala directamente sobre PHP y MySQL
locales. El entorno de destino es hosting compartido, donde tampoco hay Docker,
así que desarrollar así se parece más a producción.

---

## 6. TECNOLOGÍAS & LIBRERÍAS

### PHP — instalado

```json
{
  "require": {
    "php": "^8.2",
    "laravel/framework": "^12.0",
    "laravel/tinker": "^2.10",
    "laravel/sanctum": "^4.0"
  },
  "require-dev": {
    "pestphp/pest": "^4.0",
    "pestphp/pest-plugin-laravel": "^4.0",
    "laravel/pint": "^1.30",
    "fakerphp/faker": "^1.24",
    "mockery/mockery": "^1.6"
  },
  "config": {
    "platform": { "php": "8.3.11" }
  }
}
```

`config.platform.php` fija la resolución de dependencias a **8.3.11**, que es el
PHP del CLI en el servidor de destino. Ver §9 y `docs/DEPLOY.md`.

### PHP — pendiente de incorporar

| Paquete | Para qué |
|---|---|
| `laravel/socialite` | Login con Google (§3-A) |
| `google/cloud-storage` | Videos, PDFs, entregas y certificados |
| `barryvdh/laravel-dompdf` | PDF de certificados (§3-E) |
| `laravel/breeze` *(opcional)* | Andamiaje de auth con Blade, si conviene no escribirlo a mano |

### Front-end — instalado

```json
{
  "devDependencies": {
    "vite": "^8.2.1",
    "laravel-vite-plugin": "^3.2.0",
    "tailwindcss": "^4.3.3",
    "@tailwindcss/vite": "^4.3.3"
  }
}
```

Sin framework de JS. Tailwind 4 se configura **en CSS** con `@theme` dentro de
`resources/css/app.css`, no en un `tailwind.config.js`. El único JavaScript
propio es el toggle del menú móvil; el submenú desplegable se resuelve con
`group-hover` y el buscador es un form GET.

---

## 7. TIMELINE DE IMPLEMENTACIÓN

### Fase 0: Base del proyecto — **completada**
- [x] Esqueleto Laravel 12 con estructura slim (`bootstrap/app.php`)
- [x] Pipeline de assets: Vite 8 + Tailwind 4
- [x] Identidad visual del sitio madre portada a componentes Blade
- [x] `config/navigation.php` como fuente única de navegación
- [x] Rutas placeholder para todas las secciones
- [x] Procedimiento de despliegue documentado (`docs/DEPLOY.md`)

### Fase 1: Setup & Autenticación — **casi completa**
- [x] Migraciones de `users`, `students`, `teachers` (§2)
- [x] Modelos Eloquent con `HasUuids` y relaciones 1:1
- [x] Login con sesión de Laravel, limitador de intentos y bloqueo de cuentas
      inactivas. **Todo el sitio está detrás de `auth`**: sin sesión no se ve nada
- [x] Seeders con usuarios de prueba de cada rol
- [ ] Registro público (hoy es un marcador; las cuentas las crea la administración)
- [ ] Verificación de email vía `email_queue`
- [ ] Google OAuth con Socialite
- [ ] Policies por rol para el sitio público

### Fase 2: Core Cursos & Clases — **en curso**
- [x] Modelos y migraciones: `courses`, `modules`, `classes`
- [x] Administración de cursos, módulos y clases desde el panel (§11)
- [x] Cronograma: fecha de activación por clase y corrimiento de fechas en lote
- [ ] `class_content`: videos, PDFs y textos dentro de cada clase
- [ ] `course_enrollments` e inscripción con aprobación
- [ ] Sitio público: catálogo, detalle del curso y aula

### Fase 3: Quiz & Evaluación (2-3 semanas)
- [ ] Modelos: questions, quizzes, student_quiz_attempts, student_answers
- [ ] Lógica de randomización de preguntas
- [ ] Calificación automática
- [ ] Frontend: interfaz quiz (ver pregunta, responder, ver score)
- [ ] Reintentos

### Fase 4: Tareas (1-2 semanas)
- [ ] Modelos: tasks, task_submissions
- [ ] Upload de archivos a Google Cloud Storage
- [ ] Dashboard de tareas para profesor (calificar)
- [ ] Frontend: submit tarea, ver calificación

### Fase 5: Notificaciones & Recordatorios (1 semana)
- [ ] Modelos: email_queue
- [ ] Integración SendGrid/Resend
- [ ] Verificación de email
- [ ] Recordatorios 24h antes de clases en vivo
- [ ] Notificaciones de cambios de estado

### Fase 6: Reportes & Certificados (1-2 semanas)
- [ ] Modelos: student_progress, certificates
- [ ] Dashboard del profesor (progreso de alumnos)
- [ ] Generación de PDF de certificados
- [ ] Modelo de certificado visual

### Fase 7: Deployment & Polish (1 semana)
- [x] Documentación de deploy (`docs/DEPLOY.md`)
- [ ] Primer despliegue en `aamevi.demosdesarrollos.com.ar`
- [ ] Variables de entorno de producción (`APP_DEBUG=false`, base, GCS)
- [ ] Clave SSH en lugar de contraseña
- [ ] HTTPS y redirección desde HTTP
- [ ] Testing manual completo

**Total estimado: 10-14 semanas** (depende del equipo)

Las estimaciones vienen del plan original, que suponía dos aplicaciones
separadas. Con un monolito Blade cabe esperar menos trabajo en las fases 2 a 6,
porque desaparecen la capa de API, el estado de cliente y la duplicación de
validaciones entre back y front.

---

## 8. DEPLOYMENT

El procedimiento completo está en **`docs/DEPLOY.md`**. Acá va lo que condiciona
el diseño.

### Entorno de demo

`aamevi.demosdesarrollos.com.ar`, hosting compartido en LatinCloud (CloudSSH).
Se clona el repo en la carpeta del dominio, de modo que el `public/` del proyecto
**es** el docroot y `.env` con `vendor/` quedan fuera del alcance web.

```
~/aamevi.demosdesarrollos.com.ar/
├── app/  config/  resources/  vendor/  .env      ← fuera del docroot
└── public/                                       ← docroot verificado
```

### Restricciones relevadas (2026-08-11)

| | |
|---|---|
| PHP del web (FPM) | 8.4.3 |
| PHP del CLI (SSH) | **8.3.11** — `/etc/php/` no lista 8.4 |
| Composer / Git | disponibles |
| Node / npm | **18.20.4** / 10.7.0 |
| rsync | no está |

Dos consecuencias de diseño:

1. **Artisan corre en el CLI**, o sea en 8.3. Eso descartó Laravel 13, que vía
   Symfony 8 exige `php >=8.4.1`: el sitio habría cargado, pero migraciones,
   cachés y colas quedaban inutilizables.
2. **Node 18 no alcanza para Vite 8** (`^20.19.0 || >=22.12.0`). Se instala Node
   moderno con `nvm` en el home del usuario, o se compilan los assets localmente
   y se suben con `scp` (no hay `rsync`).

### Actualizaciones

```bash
git pull
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

### Producción definitiva

Sin definir. Las opciones razonables son un VPS con PHP 8.4 —que permitiría
volver a Laravel 13— o mantener hosting compartido. Google Cloud Storage sigue
siendo la elección para archivos (§9), y es independiente de dónde corra la app.

---

## 9. DECISIONES ARQUITECTÓNICAS

### ¿Por qué MySQL?
- ✅ Stack de origen ya es MySQL (migración de datos directa, sin conversión de tipos)
- ✅ Soporte de primera clase en Eloquent/Laravel (driver por defecto)
- ✅ InnoDB: transacciones ACID e integridad referencial (FK con ON DELETE CASCADE)
- ✅ `utf8mb4` para acentos y emojis sin sorpresas
- ✅ JSON nativo desde MySQL 5.7 (suficiente para los campos semiestructurados del modelo)
- ✅ Hosting más barato y ubicuo; Cloud SQL for MySQL en GCP

**Nota sobre CHAR(36)s**: MySQL no tiene tipo `CHAR(36)` nativo. Las PKs se declaran
`CHAR(36)` y en migraciones Laravel se usa `$table->uuid('id')->primary()`, que
genera exactamente eso. Los modelos usan el trait `HasUuids`.

### ¿Por qué Laravel (y no NestJS)?
- ✅ Eloquent cubre el modelo de §2 sin capa extra de ORM
- ✅ Migraciones, validación (`FormRequest`), Policies, colas y scheduler ya vienen
- ✅ Blade permite render en servidor: una sola aplicación en lugar de dos
- ✅ Corre en hosting compartido barato, que es el entorno real de destino
- ✅ Un solo lenguaje en todo el proyecto

### ¿Por qué Blade (y no el SPA React)?

Existía una rama, `feat/identidad-visual-aamevi`, con un front React completo y
ya branded. Se decidió portarlo a Blade en vez de conservarlo:

- ✅ Un artefacto para desplegar, no dos, en un hosting sin Node moderno
- ✅ Sin API intermedia, sin CORS, sin duplicar validaciones entre back y front
- ✅ Sesión de servidor en lugar de manejo de tokens en el cliente
- ✅ La identidad visual se conserva: los tokens y componentes se tradujeron uno
  a uno (§1 y `docs/SISTEMA_DISENO.md`)
- ⚠️ Costo: las pantallas muy interactivas —el quiz sobre todo— requieren más
  trabajo o incorporar Livewire

### ¿Por qué Laravel 12 y no 13?

Laravel 13 arrastra Symfony 8, que exige `php >=8.4.1`. El servidor de destino
sirve el sitio con PHP 8.4.3 pero **solo ofrece 8.3.11 por SSH**, y Artisan corre
ahí: migraciones, `config:cache`, scheduler y workers habrían quedado
inutilizables. Laravel 12 usa Symfony 7 (`php >=8.2`) y funciona en ambos.

Es reversible: si el hosting llega a ofrecer PHP 8.4 en el CLI, volver a 13 es
revertir un commit. `config.platform.php = 8.3.11` evita mientras tanto que
`composer update` incorpore paquetes que el CLI no pueda ejecutar.

### ¿Por qué Google Cloud Storage (no local)?
- ✅ Escalable (no depende del servidor)
- ✅ CDN integrado (videos cargan rápido)
- ✅ Backup automático
- ✅ CORS fácil de configurar
- ✅ Integración con Google Meet

### Aleatorización de Preguntas
**Problema**: Todos ven las mismas 3 preguntas → facilita copia
**Solución**: Por cada alumno, seleccionar N preguntas aleatorias **en el momento que empieza el intento**
```php
// app/Services/Quiz/QuizService.php
$questions = $quiz->courseClass->questions()
    ->inRandomOrder()
    ->limit($quiz->questions_per_student)
    ->get();

// Se graban en quiz_question_assignment para que el intento sea reproducible:
// sin esto no se puede auditar ni recalcular una nota reclamada.
$attempt->assignedQuestions()->attach($questions->pluck('id'));
```

---

## 10. DECISIONES FINALES CONFIRMADAS

✅ **Calificación**: Numérica (0-100), sin letter-based (A, B, C, D)  
✅ **Feedback en tiempo real**: Sí - alumno ve su puntuación **inmediatamente** tras enviar quiz  
✅ **Google Classroom**: No integrar  
✅ **Idioma**: Español (sin soporte multiidioma inicial)  
✅ **Mobile**: PWA responsive (web-mobile, no nativa)

### Implicancias Técnicas

**Quiz - Feedback Inmediato**:
```php
// QuizService::submit() devuelve el resultado que la vista Blade renderiza
// en la misma respuesta, sin round-trip adicional:
[
    'score' => 75,
    'passed' => true,
    'feedback' => '¡Pasaste con 75%! Siguiente clase desbloqueada.',
    'answers' => [
        ['question_id' => '...', 'given' => 'A', 'correct' => true],
        ['question_id' => '...', 'given' => 'C', 'correct' => false, 'expected' => 'B'],
    ],
]
```

**PWA (Progressive Web App)**:
- `manifest.json` para instalar como app
- Service Worker para funcionar offline
- Responsive design (mobile-first con Tailwind)
- Tailwind breakpoints: `sm`, `md`, `lg`, `xl`
- Camera/file access en mobile (para subir tareas)

---

## 11. PANEL DE ADMINISTRACIÓN (CMS)

Backoffice para que administración y docentes gestionen el material sin tocar la
base ni el código. **El sitio público de §1 no cambia**: son dos superficies
distintas sobre el mismo dominio de datos.

### Dos paneles

| Ruta | Quién entra | Alcance |
|---|---|---|
| `/admin` | `users.role = 'admin'` | Todo: usuarios, docentes, cursos de cualquier profesor, configuración |
| `/profesores` | `users.role = 'teacher'` | Solo sus propios cursos, su material y sus alumnos |

El enum `users.role` de §2 ya distingue `admin`, `teacher` y `student`, así que
**no hace falta un paquete de permisos**: alcanza con middleware por rol y
Policies. Si más adelante aparecen permisos finos (p. ej. un docente que puede
editar el curso de otro), ahí entra `spatie/laravel-permission`.

### Alcance funcional

**Contenido académico**
- Cursos: alta, edición, activar/desactivar, asignar docente, cupo
- Módulos: creación y reordenamiento dentro del curso
- Clases: orden, `activation_date`, marcar en vivo con `meet_link`, grabación
- Contenido de clase: videos, PDFs y texto — el archivo va a GCS, la base guarda la URL
- **Cronograma**: vista por curso ordenada por `activation_date`, con edición de
  fechas en lote (correr todo un módulo N días es la operación real de un docente)

**Evaluación**
- Banco de preguntas por clase, con opciones y marca de correcta
- Configuración del quiz: `questions_per_student`, `passing_score`, `max_attempts`, `randomize_options`
- Tareas: consigna, fecha límite, y corrección con nota y devolución
- Intentos de cada alumno, incluyendo **qué preguntas le tocaron** (vía `quiz_question_assignment`)

**Personas**
- CRUD de alumnos, docentes y administradores
- Alta de docente = fila en `users` con `role='teacher'` + fila en `teachers`
- Inscripciones: aprobar y rechazar (§3-A)
- Activar/desactivar usuarios en lugar de borrarlos

**Operación**
- Cola de emails (`email_queue`): estado, reintentos, errores
- Certificados emitidos
- Reportes de progreso por curso

### Opciones evaluadas

| Opción | A favor | En contra |
|---|---|---|
| **Filament** (MIT, gratuito) | Constructor de paneles sobre Livewire. Trae CRUD, tablas con filtros y búsqueda, formularios, subida de archivos y *relation managers*, que calzan exactamente con la jerarquía cursos → módulos → clases → contenido. Soporta múltiples paneles, uno para `/admin` y otro para `/profesores` | Incorpora Livewire y Alpine; estética propia, ajena a la identidad AAMEVi; tiene curva de aprendizaje |
| **Blade a mano** | Control total, identidad institucional coherente, cero dependencias nuevas | Hay que escribir tablas, filtros, paginación, subida de archivos y validación para ~12 entidades. Es el grueso del trabajo del proyecto |
| **Laravel Nova** | Oficial y pulido | Licencia paga por sitio |
| **Backpack** | Maduro | Varias piezas son pagas |

### Recomendación

**Filament para el backoffice, Blade plano para el sitio público.**

El panel concentra casi todo el volumen de CRUD, y es justo donde la identidad
visual importa poco: no lo ve nadie de afuera. Escribirlo a mano son semanas
reproduciendo lo que un constructor de paneles ya resuelve.

Esto no contradice la decisión de §9 de posponer Livewire: entraría **solo** en
`/admin` y `/profesores`. Las vistas públicas siguen siendo Blade sin framework
de JavaScript.

### Estado: adoptado (2026-08-11)

**Filament 5.7.6 instalado.** Resuelve limpio contra Laravel 12 con
`platform.php = 8.3.11`, arrastrando Livewire 4.4. El panel vive en `/admin`.

Decisiones de integración:

- **Sin login propio de Filament.** El panel no expone `/admin/login`: los
  administradores entran por el `/login` del sitio, que ya tiene limitador de
  intentos y control de cuentas desactivadas. Una sola sesión, un solo
  formulario que auditar.
- **`User` implementa `FilamentUser`**: `canAccessPanel()` exige rol `admin` y
  cuenta activa. Sin eso Filament dejaría entrar a cualquier autenticado.
- **`User` implementa `HasName`**, porque Filament espera un atributo `name` y
  este modelo tiene el nombre partido en `first_name` y `last_name`.
- **`UserRole` implementa `HasLabel` y `HasColor`**, para que las etiquetas en
  español y los colores de los badges salgan del enum y no se repitan en cada
  recurso.
- **Color primario** `#00b8b3`, el institucional.

Dos consecuencias operativas:

- Filament sirve **sus propios assets** desde `public/css|js|fonts/filament`,
  fuera del build de Vite. No están versionados: los republica
  `php artisan filament:assets`, que ya está en `deploy.sh`.
- `blade-icons`, que viene con Filament, registra un componente global `x-icon`
  que le ganaba al nuestro. El componente propio pasó a ser `<x-ui.icon>`.

El panel de `/profesores` queda pendiente: es un segundo panel de Filament con
los recursos acotados a los cursos del docente.

### Cómo está organizado el panel

Los recursos viven en `app/Filament/Resources/`, uno por carpeta, con el
formulario y la tabla en clases aparte (`Schemas/` y `Tables/`). Esa división la
impone el generador de Filament 5; conviene respetarla.

**Navegación del contenido** — se baja un nivel por pantalla:

```
Cursos  →  [abrir un curso]  →  pestaña Módulos
                                     │
                                     └── acción «Clases»  →  pantalla del módulo
                                                                  │
                                                                  └── pestaña Clases
```

El salto del medio es lo menos evidente del diseño y tiene un motivo:
**un relation manager de Filament no puede anidar otro**. Como las clases cuelgan
del módulo y el módulo del curso, hacen falta dos pantallas. Por eso existe
`CourseModuleResource`, que:

- **no aparece en la navegación** (`$shouldRegisterNavigation = false`)
- **no tiene página de alta**: los módulos se crean desde el curso, que es donde
  se conoce a cuál pertenecen
- solo sirve para abrir un módulo y trabajar sus clases

**Convenciones a respetar** al sumar recursos:

| | |
|---|---|
| Generar, no escribir a mano | `php artisan make:filament-resource Foo --generate`. La API de v5 difiere bastante de la de v3 y el generador la acierta |
| Revisar siempre lo generado | Los selects de relación salen mostrando el UUID, y los campos de contraseña se sobreescriben con `null` al editar |
| Etiquetas y colores en el enum | Implementar `HasLabel` y `HasColor` (como `UserRole`) en vez de repetirlos en cada recurso |
| `order_number` único por padre | La unicidad es `(padre_id, order_number)`; el formulario sugiere la posición siguiente |
| Acciones masivas para lo repetitivo | Editar treinta clases de a una no es una interfaz; ver «Correr fechas» |

**Cuidado con `x-icon`**: `blade-icons`, dependencia de Filament, lo registra
globalmente y le gana a cualquier componente propio con ese nombre. El del
proyecto es `<x-ui.icon>`.

### Impacto en el resto del plan

- **§2**: sin cambios. El enum de roles ya contempla `admin`
- **§4**: los controladores de `Courses` quedan para el sitio público —catálogo,
  inscripción, aula—. La administración pasa a ser recursos del panel
- **§7**: hace falta una fase nueva para el panel, entre la 2 y la 3, porque
  cargar contenido de prueba a mano deja de ser viable apenas exista el modelo

---

## 12. PRÓXIMOS PASOS

Hecho hasta el 2026-08-11:

1. [x] Plan arquitectónico actualizado a Laravel 12 + Blade
2. [x] Base del proyecto: pipeline de assets, identidad visual, layout
3. [x] Primer despliegue en `aamevi.demosdesarrollos.com.ar`
4. [x] Esquema base: `users`, `students`, `teachers`
5. [x] Login, con el sitio entero detrás de sesión
6. [x] Panel de administración con Filament: usuarios, cursos, módulos y clases

Lo que sigue, en orden de dependencia:

1. [ ] **`class_content`** — videos, PDFs y textos dentro de cada clase. Sin
       esto un curso es una estructura vacía, así que es lo primero
2. [ ] **`course_enrollments`** — inscripción con aprobación (§3-A). Habilita el
       catálogo público y los paneles de progreso
3. [ ] **Sitio público de cursos** — catálogo, detalle y aula, reemplazando las
       rutas placeholder
4. [ ] **Panel `/profesores`** — segundo panel, acotado a los cursos del docente
5. [ ] **Fase 3: quiz** — la parte más intrincada del dominio (§9)

### Deuda pendiente

| | |
|---|---|
| `intl` en el servidor | La extensión no está instalada; hace falta para formatear números y fechas. Pedido a soporte |
| Registro público | Hoy es un marcador: las cuentas las crea la administración |
| Verificación de email | `User` implementa `MustVerifyEmail` pero no hay flujo ni `email_queue` |
| Google Cloud Storage | Los archivos todavía no se suben a ningún lado |
| `docs/SISTEMA_DISENO.md` | Sus rutas siguen apuntando a `frontend/src/...` de la etapa React; los tokens que enumera sí son correctos |
