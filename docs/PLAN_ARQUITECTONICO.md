# Plan Arquitectónico — AAMEVi
## Laravel 12 + Blade + MySQL 8

**Fecha**: 2026-07-28  
**Escala**: 50 alumnos/curso (inicial), extensible a 1000+  
**Tecnología**: Laravel 12 (PHP 8.2+) + Blade + Vite 8 + Tailwind 4 + MySQL 8
**Actualizado**: 2026-08-16

> **Nota de revisión — 2026-08-11**
>
> Este documento nació describiendo una migración a monorepo React + NestJS
> desplegada en GCP, bajo el nombre OSDOP. El proyecto cambió de rumbo: hoy es
> una aplicación Laravel monolítica con vistas Blade, desplegada en hosting
> compartido. Las secciones 1 y 4 a 11 fueron reescritas para reflejarlo.
>
> **Las secciones 2 (modelo de datos) y 3 (flujos) se conservan sin cambios**:
> describen el dominio, no el stack, y siguen siendo el contrato. §3-bis documenta
> las reglas de negocio tal como quedaron implementadas.

---

## 1. ESTRUCTURA DEL PROYECTO

Aplicación Laravel única. No hay separación backend/frontend: las vistas Blade
se renderizan en el mismo proceso que resuelve la lógica de negocio.

Hay **tres superficies** sobre el mismo dominio de datos: el aula, en Blade, y
dos paneles de Filament —`/admin` y `/profesores`, §11— que comparten los mismos
recursos. No comparten vistas ni controladores; sí modelos, sesión y reglas de
acceso.

Marcado con ✅ lo que ya existe en el repo; el resto es lo previsto.

```
aamevi/
├── app/
│   ├── Console/Commands/    ✅ emails:enviar, emails:recordatorios
│   ├── Enums/               ✅ UserRole, EnrollmentStatus, ClassContentType,
│   │                           ClassProgressState, SubmissionStatus, EmailType,
│   │                           EmailStatus
│   ├── Events/              ✅ CourseProgressAdvanced, EnrollmentApproved
│   ├── Exceptions/          ✅ EnrollmentException, QuizException,
│   │                           SubmissionException, CertificateException
│   ├── Filament/                  # Los dos paneles (§11)
│   │   ├── Concerns/        ✅ ScopedToOwnCourses, ListAndCreateNavigation
│   │   ├── Forms/           ✅ RichText, ModuleExam, QuestionOptions
│   │   ├── Tables/          ✅ DragToReorder
│   │   └── Resources/       ✅ Users, Students, Courses, CourseModules,
│   │                           CourseClasses, Questions
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/        ✅ AuthenticatedSessionController,
│   │   │   │                    RegisteredUserController,
│   │   │   │                    EmailVerificationController
│   │   │   └── Classroom/   ✅ Catalog, MyCourses, Course, Classroom, Quiz,
│   │   │                       Submission, Progress
│   │   ├── Requests/        ✅ LoginRequest, RegisterRequest,
│   │   │                       StoreSubmissionRequest
│   │   └── Middleware/      ✅ EnsureUserIsStudent, HandleOversizedUpload
│   ├── Listeners/           ✅ IssueCertificateIfEarned,
│   │                           QueueEnrollmentApprovedEmail
│   ├── Models/              ✅ 16: User, Student, Teacher, Course,
│   │                           CourseModule, CourseClass, ClassContent,
│   │                           CourseEnrollment, Quiz, Question,
│   │                           QuestionOption, QuizAttempt, StudentAnswer,
│   │                           StudentProgress, TaskSubmission, Certificate,
│   │                           QueuedEmail
│   ├── Policies/            ✅ Course, CoursePart (módulos, clases,
│   │                           preguntas) y User
│   ├── Services/            ✅ QuizService, ProgressService,
│   │                           EnrollmentService, SubmissionService,
│   │                           CertificateService, NotificationService,
│   │                           SearchService
│   ├── Support/Html.php     ✅ Saneado del texto enriquecido
│   └── Providers/Filament/  ✅ AdminPanelProvider, TeacherPanelProvider
├── bootstrap/app.php        ✅ Esqueleto slim de Laravel 11+
├── config/
│   ├── auth.php             ✅
│   ├── database.php         ✅ mysql + sqlite (esta última solo para tests)
│   └── navigation.php       ✅ Fuente única del menú del aula
├── database/
│   ├── migrations/          ✅ 18: users, password_reset_tokens, students,
│   │                           teachers, courses, modules, classes,
│   │                           class_content (+ due_date), course_enrollments,
│   │                           questions, question_options, quizzes, los tres
│   │                           de intentos, student_progress, task_submissions
│   │                           certificates y email_queue
│   ├── factories/           ✅ Una por modelo
│   └── seeders/             ✅ DatabaseSeeder (un usuario por rol),
│                               StudentSeeder y CourseSeeder (programa completo)
├── public/
│   ├── index.php            ✅ Docroot
│   ├── images/              ✅ aamevi.svg y su variante para modo oscuro
│   ├── build/                  Generado por Vite (no versionado)
│   └── css|js|fonts/filament/  Publicado por Filament (no versionado)
├── resources/
│   ├── css/
│   │   ├── app.css          ✅ Tokens de marca y semánticos en @theme
│   │   └── filament/        ✅ Estilos del panel, fuera del bundle del sitio
│   ├── js/
│   │   ├── app.js           ✅ Menú hamburguesa; sin framework JS
│   │   └── preferences.js   ✅ Tema y tamaño de letra
│   ├── lang/es/auth.php     ✅
│   └── views/
│       ├── layouts/         ✅ app, guest y classroom
│       ├── partials/        ✅ preferences-head: antes del primer pintado
│       ├── components/      ✅ header, footer, top-bar, page-hero, section,
│       │   ├── classroom/   ✅ nav, progress-bar, state-badge, content-block,
│       │   │                   task-panel
│       │   ├── rich-text.blade.php ✅ Único `{!! !!}` del proyecto
│       │   └── ui/icon.blade.php   ✅ (x-icon lo toma blade-icons)
│       ├── auth/            ✅ login, register, verify-email
│       ├── certificates/    ✅ La plantilla del PDF, escrita para dompdf
│       ├── emails/          ✅ Las plantillas de los avisos, en tablas
│       ├── classroom/       ✅ catálogo, curso, clase, evaluaciones, quiz y
│       │                       su resultado, mis cursos, progreso,
│       │                       certificados, buscador
│       ├── home.blade.php   ✅
│       └── placeholder.blade.php ✅ Sólo ayuda
├── routes/web.php           ✅ Grupos `guest` y `auth`
├── tests/                   ✅ 381: Unit/ y Feature/{Auth,Admin,Teacher,Classroom}
├── docs/                    ✅ Este plan, SISTEMA_DISENO.md, DEPLOY.md
├── deploy.sh                ✅ Ciclo de actualización en el servidor
├── composer.json            ✅
└── vite.config.js           ✅
```

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

> **`pdf_url` no se implementó.** El certificado *es* las otras cuatro columnas;
> el PDF es una forma de mostrarlas y se arma al descargarlo, con dompdf.
> Guardarlo obligaría a regenerar el archivo cada vez que se corrija un apellido
> mal escrito o cambie la plantilla, y a limpiar los viejos. Lo que no se puede
> recalcular —el número emitido y la fecha— sí queda escrito.

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

> Implementada con una columna más, `last_error`: sin ella un aviso fallido no
> dice por qué, y la única salida sería reintentar a ciegas. `verification`
> existe en el enum pero todavía no se usa: depende del registro público.
>
> **Por qué esta tabla y no la cola de Laravel.** El hosting es compartido, no
> hay forma de dejar un `queue:work` corriendo, y lo que sí hay es cron. Además
> esta tabla se puede mirar: la pregunta que llega es «¿le llegó el mail a
> fulano?», y en `jobs` la respuesta está adentro de un payload serializado.

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

## 3-bis. REGLAS IMPLEMENTADAS

Lo que sigue no se deduce del esquema: son decisiones tomadas al construir, cada
una con su test. Modificarlas rompe tests a propósito.

### Inscripciones — `CourseEnrollment`

Las transiciones son métodos (`approve`, `reject`, `activate`, `complete`), no
asignaciones a `status`. Cada una valida el estado de origen y lanza
`EnrollmentException` si no corresponde: asignar a mano permitiría marcar como
finalizada una inscripción rechazada.

| | |
|---|---|
| Cupo | Solo cuentan las **aprobadas, en curso y finalizadas**. Si las pendientes y rechazadas ocuparan lugar, un curso con veinte solicitudes rechazadas quedaría bloqueado teniendo vacantes |
| Duplicados | `unique (course_id, student_id)` en la base, más validación en el formulario |
| Docente eliminado | `approved_by` es `SET NULL`, no cascada: perder inscripciones porque cambió el profesor sería irrecuperable |

### Evaluaciones — `Quiz`, `QuizService`

Un quiz cuelga de **una clase o de un módulo**, nunca de ambos ni de ninguno; lo
valida el modelo en `saving`.

| | |
|---|---|
| Sorteo de clase | `questions_per_student` preguntas del banco de esa clase |
| Sorteo de módulo | `questions_percentage` % del banco combinado de **todas** sus clases, redondeando hacia arriba |
| Tope | Nunca sortea más preguntas que las disponibles: pedir 10 de un banco de 6 calificaría sobre un total distinto al configurado |
| Piso | Nunca sortea cero si hay preguntas — un examen vacío se aprueba solo |
| Inactivas | Las preguntas con `is_active = false` quedan fuera del sorteo |
| Opciones | Exactamente una correcta. Sin correcta no se puede aprobar nunca; con dos, la calificación es ambigua |

Del intento:

- **Reabrir un intento en curso devuelve el mismo**, no abre otro. Recargar la
  página no puede consumir un intento ni cambiar las preguntas a mitad de camino.
- **Las preguntas sin responder se guardan igual**, con la opción en null. Si se
  omitieran, sería indistinguible «no contestó» de «no se le preguntó».
- **Responder algo que no estaba asignado aborta la entrega**: indica un
  formulario manipulado.
- **Aprobar una vez alcanza**, aunque un intento posterior desapruebe.

`quiz_question_assignment` registra qué preguntas le tocaron a cada alumno y en
qué orden. Como el sorteo es distinto para cada uno, sin ese registro una nota
reclamada no se puede reconstruir ni recalcular.

### Progresión — `ProgressService`

Una clase se abre con tres condiciones **acumulativas**: inscripción aprobada,
fecha de activación cumplida, y clase anterior aprobada.

Se evalúan por separado para exponer `lockReason()`: «se habilita el 20/09» y
«primero aprobá la clase anterior» son accionables; un «no tenés acceso»
genérico solo genera consultas al soporte.

- **El encadenamiento es por módulo, no por curso entero.** Los módulos se
  habilitan por fecha; encadenar el curso completo dejaría al alumno trabado
  esperando la última clase de un módulo que todavía no le toca cursar.
- **Completar una clase con quiz exige haberlo aprobado.** Sin esa guarda,
  marcarla completa saltearía la evaluación que la progresión protege.
- **Y exige haber entregado sus tareas — entregado, no aprobado.** Pedir la
  corrección dejaría al alumno detenido esperando a otra persona.

### Alta de cuenta — `RegisteredUserController`

- **El formulario público crea siempre un alumno.** Docentes y administradores
  los da de alta la administración; un alta pública que pudiera elegir rol sería
  una puerta abierta.
- **El usuario y su ficha se crean en una transacción.** Un usuario con rol
  Alumno sin fila en `students` no puede entrar al aula, y `EnsureStudent` lo
  rebotaría con un mensaje que no explica nada.
- **Sin verificar, el aula está cerrada** (`verified` sobre el grupo del aula).
  Verificar antes de dejar entrar evita que alguien se anote con la dirección de
  otro y quede cursando a su nombre.
- **Registrarse no da acceso a ningún curso**: la inscripción la sigue aprobando
  una persona. Eso es lo que permite que el alta sea abierta.
- **Las cuentas creadas desde el panel nacen verificadas.** Las da de alta la
  institución, no alguien que dijo ser el dueño de esa casilla.

### Buscador — `SearchService`

Busca **sólo lo que ese alumno puede abrir**: los cursos de su catálogo o los
que ya cursa, y las clases de los cursos en los que está inscripto.

- **El recorte va en la consulta, no en la salida.** Buscar en todo y esconder
  después filtraría igual: la lista de títulos que existen ya es información.
- **Las clases todavía no habilitadas aparecen, pero sin enlace.** Figuran en el
  temario desde el primer día, así que no revelan nada; el resultado dice en qué
  estado están y ofrece el curso, en vez de mandar al alumno a un 403.
- **Las palabras se buscan por separado**: «medicina vida» encuentra «Medicina
  del estilo de vida», que como una sola cadena no aparecería.
- **Es del aula.** El docente y el administrador tienen el buscador global de su
  panel; a ellos ni se les muestra el campo.

### Avisos — `NotificationService`

Nada se manda en el momento: todo se escribe en `email_queue` y sale cuando
`emails:enviar` la vacía, por cron.

- **El asunto y el cuerpo se guardan ya armados.** Lo que figura en la tabla es
  exactamente lo que salió, y cambiar mañana una plantilla no reescribe la
  historia.
- **`scheduled_at` está separado de `created_at`** para poder programar el
  recordatorio de una clase con un día de anticipación.
- **Un envío fallido no lanza: devuelve false.** Un correo que no sale no puede
  cortar la tanda, y acá el error es del mundo exterior, no un programa mal
  escrito. Mientras queden reintentos sigue pendiente, con la próxima salida cada
  vez más lejos; agotados, pasa a `failed` y espera a que alguien lo reintente
  desde el panel.
- **Rechazar una inscripción no avisa.** Una mala noticia por correo automático
  es peor que un llamado.
- **El recordatorio es sólo de las clases en vivo.** Una grabada se ve cuando el
  alumno puede; avisar de cada una sería un correo por clase del cronograma.

### Certificados — `CertificateService`

Se emite con dos condiciones: todas las clases completadas y **ninguna tarea sin
aprobar**. La primera ya arrastra las evaluaciones, porque una clase no se
completa sin haber aprobado la suya.

- **Es más exigente que la progresión, a propósito.** Para pasar de clase alcanza
  con haber entregado; el certificado afirma que el alumno *aprobó* el curso, y
  eso no se puede sostener con una tarea sin corregir.
- **La aprobación tiene que estar publicada.** Emitirlo antes delataría una nota
  que el docente todavía no comunicó.
- **Lo emite un listener, no una llamada suelta.** `CertificateService` necesita
  preguntarle a `ProgressService` si el curso terminó, así que llamarlo desde
  adentro sería un círculo. `CourseProgressAdvanced` se dispara donde el avance
  puede cambiar —completar una clase, publicar una corrección— y
  `IssueCertificateIfEarned` reacciona. Es el enganche que van a usar también las
  notificaciones de la fase 5.
- **`issueIfEarned()` devuelve null, no lanza.** Que a un alumno le falte una
  clase es lo normal, no un error. El que lanza es `issue()`, la emisión manual.
- **Emitir es lo único que lleva una inscripción a `completed`.** Pasa por
  `activate()` si hace falta: la máquina de estados no permite saltear, y quien
  terminó el curso estuvo cursándolo aunque nadie lo haya registrado.
- **El número no es correlativo** (`AAMEVI-2026-4F2A9C`). Un correlativo publica
  cuántos certificados emitió la institución y obliga a bloquear la tabla para no
  repetirlo.

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
`resources/css/app.css`, no en un `tailwind.config.js`. El JavaScript propio son
el toggle del menú móvil y los controles de tema y tamaño de letra; el submenú
desplegable se resuelve con `group-hover` y el buscador es un form GET. El panel
sí lleva Livewire, que viene con Filament.

---

## 7. TIMELINE DE IMPLEMENTACIÓN

### Fase 0: Base del proyecto — **completada**
- [x] Esqueleto Laravel 12 con estructura slim (`bootstrap/app.php`)
- [x] Pipeline de assets: Vite 8 + Tailwind 4
- [x] Identidad visual del sitio madre portada a componentes Blade
- [x] `config/navigation.php` como fuente única de navegación
- [x] Rutas placeholder para todas las secciones
- [x] Procedimiento de despliegue documentado (`docs/DEPLOY.md`)

### Fase 1: Setup & Autenticación — **completa salvo OAuth**
- [x] Migraciones de `users`, `students`, `teachers` (§2)
- [x] Modelos Eloquent con `HasUuids` y relaciones 1:1
- [x] Login con sesión de Laravel, limitador de intentos y bloqueo de cuentas
      inactivas. **Todo el sitio está detrás de `auth`**: sin sesión no se ve nada
- [x] Seeders con usuarios de prueba de cada rol
- [ ] Registro público (hoy es un marcador; las cuentas las crea la administración)
- [x] Policies por rol, compartidas por los dos paneles (§11)
- [x] Registro público, siempre con rol Alumno
- [x] Verificación de email vía `email_queue`, con enlace firmado
- [ ] Google OAuth con Socialite

### Fase 2: Core Cursos & Clases — **completa**
- [x] Modelos y migraciones: `courses`, `modules`, `classes`, `class_content`
- [x] Administración de cursos, módulos y clases desde el panel (§11)
- [x] Cronograma: fecha de activación por clase y corrimiento de fechas en lote
- [x] `class_content`: video con previsualización, PDF con subida y descarga,
      texto y consigna con editor enriquecido
- [x] `course_enrollments` con aprobación, rechazo y control de cupo (§3-bis)
- [x] `student_progress`: gateo por inscripción, fecha y aprobación previa
- [x] El aula: catálogo, detalle del curso con inscripción, y pantalla de clase

### Fase 3: Quiz & Evaluación — **completa**
- [x] Modelos y migraciones: `questions`, `question_options`, `quizzes`,
      `student_quiz_attempts`, `student_answers`, `quiz_question_assignment`
- [x] Quiz por clase **y examen de módulo por porcentaje** (extensión de §2)
- [x] Sorteo aleatorio por alumno, con registro de qué le tocó a cada uno
- [x] Calificación automática y control de reintentos
- [x] Carga de preguntas desde el panel, con enunciado enriquecido
- [x] Pantalla de rendir: ver pregunta, responder, ver el resultado
- [x] Revisión de intentos desde el panel, para atender una nota reclamada

### Fase 4: Tareas — **completa**
- [x] `task_submissions` y `class_content.due_date` (el enunciado ya era contenido)
- [x] Subida de archivos al disco `public` — GCS queda para cuando se configure
- [x] Solapa Calificaciones: corregir con nota y devolución, publicar en tanda
- [x] Aula: entregar, ver el estado, y la nota **recién cuando se publique**
- [x] La entrega entra en el gateo: no se completa una clase con la tarea sin entregar

### Fase 5: Notificaciones & Recordatorios — **completa salvo el proveedor**
- [x] Tabla `email_queue` y `NotificationService`
- [x] Worker `emails:enviar`, programado por cron cada cinco minutos
- [x] Recordatorios 24 h antes de las clases en vivo (`emails:recordatorios`)
- [x] Avisos de inscripción aprobada, trabajo corregido y certificado emitido
- [x] La cola a la vista en el panel, con reintento manual
- [x] Verificación de email al registrarse
- [ ] Configurar el SMTP del servidor: hoy `MAIL_MAILER=log`

### Fase 6: Reportes & Certificados — **casi completa**
- [x] `student_progress` y la grilla de seguimiento por curso para el docente
- [x] Barra de avance del alumno
- [x] Modelo `certificates` y emisión automática al terminar el curso
- [x] PDF con dompdf, armado al descargarlo
- [ ] Modelo visual definitivo, con firma escaneada
- [ ] Verificación pública del número de certificado

### Fase 7: Deployment & Polish (1 semana)
- [x] Documentación de deploy (`docs/DEPLOY.md`)
- [x] Primer despliegue en `aamevi.demosdesarrollos.com.ar`
- [x] Variables de entorno de producción (`APP_DEBUG=false`, base)
- [ ] Subir `upload_max_filesize` y `post_max_size` — hoy 2 MB y 8 MB
- [ ] Clave SSH en lugar de contraseña
- [ ] HTTPS y redirección desde HTTP
- [ ] Testing manual completo

Las estimaciones originales suponían dos aplicaciones separadas. Con un monolito
Blade el trabajo de las fases 2 a 6 resultó menor: desaparecen la capa de API, el
estado de cliente y la duplicación de validaciones entre back y front.

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
base ni el código. **El aula de §1 no cambia**: son superficies distintas sobre el
mismo dominio de datos.

### Dos paneles

| Ruta | Quién entra | Alcance |
|---|---|---|
| `/admin` | `users.role = 'admin'` | Todo: usuarios, docentes, cursos de cualquier profesor, configuración |
| `/profesores` | `users.role = 'teacher'` | Solo sus propios cursos, su material y sus alumnos |

El enum `users.role` de §2 ya distingue `admin`, `teacher` y `student`, así que
**no hizo falta un paquete de permisos**: alcanzó con `canAccessPanel()`, las
policies de `app/Policies/` y el recorte de consultas de `scopeVisibleTo()`. Si
más adelante aparecen permisos finos (p. ej. un docente que puede editar el curso
de otro), ahí entra `spatie/laravel-permission`.

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

### El panel de `/profesores`

Es un segundo panel de Filament que **reusa los recursos de `/admin`** en lugar
de duplicarlos: un curso se administra igual lo dicte quien lo dicte, y dos
copias del árbol curso → módulo → clase se habrían separado en cuanto se tocara
una. `TeacherPanelProvider` declara los recursos uno por uno —cursos, módulos,
clases y banco de preguntas— para que sumar mañana un recurso de administración
no aparezca solo acá.

Lo que separa a un docente de otro no es el panel sino dos cosas que valen
entren por donde entren:

- **`Course::scopeVisibleTo(User)`** recorta las consultas. Módulos, clases y
  preguntas cuelgan de esa misma regla a través de su curso. Como Filament
  resuelve los registros por la consulta del recurso, escribir a mano la URL del
  curso ajeno devuelve 404, no la pantalla.
- **Las policies** (`CoursePolicy` y `CoursePartPolicy`) deciden qué se puede
  hacer. El alta y la baja de cursos son del administrador: si un docente pudiera
  crearlos tendría que elegir docente, y si pudiera borrarlos se llevaría puestas
  las inscripciones.

En el formulario del curso, «Docente», «Cupo» y «Curso activo» se muestran
deshabilitados para el docente. Deshabilitados y no ocultos porque Filament no
manda al servidor el valor de un campo deshabilitado: esconderlo en la pantalla
no habría alcanzado.

Afuera quedan las cuentas de usuario. Un docente ve a sus alumnos desde la solapa
del curso, que es donde significan algo.

### Cómo está organizado el panel

Los recursos viven en `app/Filament/Resources/`, uno por carpeta, con el
formulario y la tabla en clases aparte (`Schemas/` y `Tables/`). Esa división la
impone el generador de Filament 5; conviene respetarla.

**Menú lateral** — cuatro grupos, en el orden en que se trabaja:

```
Cursos      ▸ Todos · Crear nuevo
Alumnos     ▸ Todos · Crear nuevo
Evaluación  ▸ Banco de preguntas
Sistema     ▸ Usuarios
```

El orden y los iconos de los grupos se fijan con `->navigationGroups()` en
`AdminPanelProvider`, no repartidos en constantes `$navigationSort` por recurso:
así, agregar un recurso en el medio no obliga a renumerar los demás. **Los
iconos van en el grupo y no en los ítems** — Filament rechaza que estén en los
dos a la vez.

El par «Todos / Crear nuevo» sale del trait
`App\Filament\Concerns\ListAndCreateNavigation`, que sobreescribe
`getNavigationItems()` para devolver dos entradas en vez de una.

`StudentResource` trabaja sobre **`User`** y no sobre `Student`, filtrando por
rol: la ficha comparte la clave con el usuario, así que dar de alta un alumno es
siempre crear los dos, y `UserForm` ya resuelve ese par. Se lo reutiliza con el
rol fijo. `UserResource`, bajo Sistema, sigue sirviendo para dar de alta
cualquier rol.

**Navegación del contenido** — el curso abre en solapas:

```
Cursos → [abrir un curso]
   │
   ├── Info general        Planificación      Contenidos
   ├── Exámenes            Alumnos del curso  Seguimiento alumnos
   │
   └── Contenidos → [abrir un módulo] → Datos y examen · Clases
                                            │
                                            └── [abrir una clase] → Autoevaluación · Banco de preguntas
```

Las solapas se registran con `getRecordSubNavigation()` y
`SubNavigationPosition::Top`. La pieza que lo hace posible es
**`ManageRelatedRecords`**, que convierte un relation manager en una página
propia del recurso: un relation manager no puede anidar otro, pero una página sí
puede tener su propia sub-navegación. Eso es lo que permite bajar los tres
niveles sin perder el rastro de dónde está uno.

`CourseModuleResource` y `CourseClassResource` siguen existiendo como pantallas
de un módulo y de una clase, y siguen **sin aparecer en la navegación**
(`$shouldRegisterNavigation = false`) y **sin página de alta**: se crean desde su
padre, que es donde se sabe a cuál pertenecen.

**El orden se cambia arrastrando.** Módulos y clases se reordenan tirando de la
fila; el `order_number` ya no se escribe a mano y un registro nuevo va al final.

Eso choca de frente con el `unique (padre_id, order_number)`: Filament escribe
todas las posiciones en un solo `UPDATE` con un `CASE`, y al asignarle el 1 a la
fila que estaba segunda, la que estaba primera todavía lo tiene. `DragToReorder`
resuelve el reordenamiento en dos fases —primero corre las filas afectadas por
encima del máximo, después Filament escribe los valores definitivos sobre un
rango libre—, lo que permite **conservar la restricción** en vez de aflojarla.
Requiere `paginated(false)`, porque el reordenamiento solo recibe las filas
visibles y arrastrar entre páginas no significa nada.

El orden no es decorativo: es la cadena que decide qué clase habilita a cuál en
`ProgressService::previousClass()`. Hay un test que lo verifica después de
reordenar.

**El cronograma avanza en el tiempo.** Una clase no puede habilitarse antes que
la que va delante suyo: cargarla con fecha anterior la dejaría disponible fuera
de secuencia. El mismo día sí —dos clases el mismo día son normales—, así que la
comparación es por día y no por hora. `CourseModule::earliestDateFor($orden)`
resuelve el mínimo y el formulario lo aplica con `minDate()`, que da validación
en el navegador y en el servidor. Vale también al editar, mirando la clase
anterior a la que se toca: sin eso alcanzaría con crear la clase con fecha válida
y después moverla hacia atrás.

La acción masiva **«Correr fechas» no valida esto**: correr un subconjunto de
clases hacia atrás puede dejar el módulo desordenado. Es deliberado —la acción
existe para reprogramar en bloque— pero conviene saberlo.

**Las tres solapas que faltan** —Calificaciones, Comunicación y Consultas a mesa
de ayuda— necesitan tablas que todavía no existen. Están diseñadas en §13.

**Convenciones a respetar** al sumar recursos:

| | |
|---|---|
| Generar, no escribir a mano | `php artisan make:filament-resource Foo --generate`. La API de v5 difiere bastante de la de v3 y el generador la acierta |
| Revisar siempre lo generado | Los selects de relación salen mostrando el UUID, y los campos de contraseña se sobreescriben con `null` al editar |
| Etiquetas y colores en el enum | Implementar `HasLabel` y `HasColor` (como `UserRole`) en vez de repetirlos en cada recurso |
| `order_number` único por padre | La unicidad es `(padre_id, order_number)`. El orden se cambia **arrastrando**, no escribiendo el número: ver `App\Filament\Tables\DragToReorder` |
| Acciones masivas para lo repetitivo | Editar treinta clases de a una no es una interfaz; ver «Correr fechas» |
| Solapas, no acciones de fila | Para bajar un nivel, `getRecordSubNavigation()` con páginas `ManageRelatedRecords`. Una acción de fila que abre otra pantalla no deja rastro de dónde está uno |
| Nada de una consulta por celda | Las pantallas que cruzan alumnos con clases piden los datos de una vez al servicio; ver `ProgressService::courseMatrix()` |

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

Hecho hasta el 2026-08-16 — **18 migraciones, 17 modelos, 381 tests**:

1. [x] Plan arquitectónico actualizado a Laravel 12 + Blade
2. [x] Base del proyecto: pipeline de assets, identidad visual, layout
3. [x] Primer despliegue en `aamevi.demosdesarrollos.com.ar`
4. [x] Login, con el sitio entero detrás de sesión
5. [x] Panel de administración con Filament (§11)
6. [x] Dominio académico completo: cursos → módulos → clases → contenido
7. [x] Inscripciones con aprobación y cupo
8. [x] Evaluaciones: banco de preguntas, quiz de clase, examen de módulo por
       porcentaje, intentos y corrección automática
9. [x] Progresión: gateo de clases por inscripción, fecha y aprobación previa
10. [x] Panel reorganizado en solapas por curso, siguiendo el análisis de §13:
        planificación, contenidos, exámenes, alumnos y seguimiento
11. [x] Seeder de programa completo: 5 cursos, 28 módulos, 140 clases, 700
        preguntas y 20 alumnos con avance simulado
12. [x] **El aula**: catálogo con inscripción, pantalla de clase, evaluaciones,
        barra de progreso, modo oscuro y control de tamaño de letra
13. [x] Entrega de trabajos prácticos y solapa de Calificaciones
14. [x] Revisión de intentos: qué preguntas le tocaron a cada alumno y qué
        respondió
15. [x] Panel `/profesores`, acotado a los cursos de cada docente
16. [x] Certificados: emisión automática al terminar el curso, PDF y emisión
        manual desde el panel
17. [x] Avisos por email: cola, worker por cron, recordatorios de clase en vivo
        y la cola a la vista en el panel
18. [x] Registro público con verificación del correo, y el aula detrás de
        `verified`
19. [x] Buscador del aula, y la navegación del sitio acotada a cada rol

### Lo que falta

El ciclo de enseñanza está cerrado: se puede cargar un curso, dictarlo, evaluarlo
y corregirlo. Lo que queda son las piezas de alrededor.

1. [ ] **Poner a andar el correo en el servidor** — el circuito está entero, pero
       falta la línea de cron y el SMTP. Hasta que se haga, la cola se llena y no
       sale nada (ver `docs/DEPLOY.md`)
2. [ ] **Comunicaciones y consultas a mesa de ayuda** — diseñadas en §13, últimas
       en la cola: en FID casi no se usaron
3. [ ] **Google OAuth y Google Cloud Storage** — previstos en el plan, sin
       configurar. Los archivos van hoy al disco `public`
4. [ ] **`/ayuda`** — sigue sirviendo el marcador

### Deuda pendiente

| | |
|---|---|
| ~~**Sanitizar el HTML**~~ | Resuelto: `App\Support\Html::sanitize()` limpia al mostrar, y el único `{!! !!}` del proyecto vive dentro de `<x-rich-text>`. Dejó de ser teórico al abrir `/profesores`: ya no cargan contenido sólo administradores |
| `intl` en el servidor | La extensión no está instalada; hace falta para formatear números y fechas. Pedido a soporte |
| `CACHE_STORE` en producción | El `.env` del servidor puede tener el nombre viejo `CACHE_DRIVER`, que Laravel 11 ignora; rompe el limitador de intentos del login (ver `docs/DEPLOY.md`) |
| Google Cloud Storage | Los PDF van al disco público local. `ClassContent::url()` ya distingue enlace externo de ruta relativa, así que migrar no tocará las vistas |
| Límite de subida del servidor | `upload_max_filesize` está en 2 MB y `post_max_size` en 8 MB; el panel ofrece 20 MB para los PDF. PHP corta antes y la validación de Laravel ni siquiera llega a correr (ver `docs/DEPLOY.md`) |

---

## 13. LO APRENDIDO DE UN LMS EN PRODUCCIÓN

En agosto de 2026 se revisó el código y la base de **FID**
(`cursoselearning.com.ar/fid`), un LMS en PHP procedural que lleva años
operando cursos de yoga. No es un sistema a imitar —no tiene framework, ni
claves foráneas, ni tests—, pero **es la única fuente disponible sobre qué usan
de verdad los docentes**, y de ahí salió la organización en solapas de §11.

### Qué resolvió mejor cada uno

Sobre clases y evaluación, que es el corazón del sistema, este proyecto ya está
por delante:

| | FID | AAMEVi |
|---|---|---|
| Estructura | `aula_planificacion`: una tabla plana con filas `modulo`, `clase` y `examen` mezcladas | `courses → modules → classes`, con FK reales |
| Vínculo contenido↔clase | `(curso, numero)`, enteros sin FK; borrar una clase **renumera** las demás | `class_id` con `ON DELETE CASCADE` |
| Banco de preguntas | 30 columnas fijas (`pregunta1..5` × `respuesta1a..1e`): tope duro de 5 por clase | tablas `questions` y `question_options`, sin tope |
| Sorteo del examen | `ORDER BY RAND() DESC LIMIT 1,10`. El `OFFSET 1` es un bug: con una sola clase seleccionada, el examen sale vacío | `questionsToDraw()`, porcentaje configurable y acotado al banco |
| Trazabilidad | guarda el **texto** de la pregunta y la respuesta, no el id | `quiz_question_assignment` registra qué preguntas le tocaron a cada alumno |
| Aprobación | el docente marca aprobado/desaprobado a mano | automática contra `passing_score`, con `max_attempts` |
| Reintentos | existen solo si el docente marca «desaprobado», que además **borra las respuestas** | contador real de intentos |
| Progresión | ninguna: la autoevaluación no bloquea nada | `ProgressService` gatea por inscripción, fecha y clase anterior |

Lo que FID sí tiene y acá faltaba **no es modelo de datos: es organización**. Sus
nueve solapas cubren el ciclo docente completo; este panel tenía dos pantallas.

Un detalle de terminología que conviene no arrastrar: la pantalla de exámenes de
FID dice *«preguntas tomadas de los módulos seleccionados»*, pero en la base
selecciona **clases**. La nomenclatura de acá —módulo compuesto por clases, cada
clase con su autoevaluación, y el examen de módulo sorteando del banco
combinado— es la correcta.

### Qué se usa de verdad

Los volcados de la base, sobre dos cursos y varios años de operación:

| Tabla | Filas |
|---|---|
| `aula_calificaciones_examen_alumno` | 1752 |
| `seguimiento` (bitácora de accesos) | 1181 |
| `aula_autoevaluacion_alumno` | 276 |
| `aula_actividad_adicional` (material) | 238 |
| `aula_calificaciones_actividad` | 138 |
| `aula_calificaciones_examen` | 100 |
| `aula_consulta_mesa` | **3** |
| `aula_comunicaciones` | **1** |

Evaluación y calificaciones concentran todo el uso. **Comunicaciones y consultas
están prácticamente sin usar**, así que van al final de la cola aunque estén
diseñadas.

### Diseño de las tres solapas que faltan

**Calificaciones y entrega de tareas — implementado el 2026-08-15.**
`ClassContentType::Task` era solo un enunciado; ahora el alumno entrega un
archivo y el docente lo corrige.

Cuatro decisiones fijan el comportamiento:

| | |
|---|---|
| La entrega **hace falta** para completar la clase | Pero no que esté aprobada: exigir la corrección dejaría al alumno detenido esperando a otra persona |
| Nota de **1 a 10** más aprobado/desaprobado | La nota informa, el resultado decide |
| **Publicar es un paso aparte de corregir** | El docente corrige a lo largo de la semana y suelta la tanda cuando terminó. Es lo mejor que tenía FID y acá no existía |
| **Reentrega sólo si la desaprueban** | Evita que el docente corrija tres versiones del mismo trabajo |

Lo que **no** se adoptó de FID es el aprobado/desaprobado manual en las
evaluaciones: el quiz y el examen se corrigen y aprueban solos contra
`passing_score`. La corrección manual queda sólo donde no hay alternativa, que
son las entregas de archivo.

**Modelo**: `class_content.due_date` —opcional; sin fecha se entrega siempre— y
`task_submissions`, **una fila por entrega**. La reentrega no pisa la anterior:
si el docente desaprueba y el alumno vuelve a entregar, la corrección original
tiene que seguir existiendo, igual que con `student_quiz_attempts`.

Las reglas viven en `SubmissionService`, con las transiciones como métodos que
validan y lanzan `SubmissionException`. `ProgressService::complete()` consulta
las tareas pendientes, y `completionBlocker()` dice cuál falta en vez de un «no
se puede» a secas.

**El límite de subida es el punto flojo.** El sistema declara 10 MB pero PHP
puede cortar mucho antes —medido en desarrollo: `upload_max_filesize` en 2 MB—, y
cuando eso pasa el archivo no llega a Laravel: se pierde el cuerpo entero de la
petición, incluido el token CSRF. `HandleOversizedUpload` traduce ese caso a un
mensaje entendible en lugar de un 419, pero es un parche: hay que subir el
límite en el servidor. Ver `docs/DEPLOY.md`.

**Comunicaciones.** Tablón de anuncios por curso: título, texto enriquecido,
destinatario (todo el curso o un alumno) y visibilidad. En FID **no manda
emails**, pese al nombre del módulo; acá se engancha a `email_queue`, que ya está
en §2 pero todavía sin migración.

**Consultas a mesa de ayuda.** Ticket con estado y respuesta. Tres cosas de FID
que se corrigen en el diseño: el hilo es de una sola respuesta, el listado **no
filtra por curso** aunque viva dentro del menú del curso, y la notificación va a
una casilla personal quemada en el código.

### Entrega de tareas y calificaciones — implementado

`ClassContentType::Task` era sólo un enunciado. Ahora el alumno entrega un
archivo y el docente lo corrige.

**Cuatro decisiones que fijan el comportamiento:**

| | |
|---|---|
| La entrega **hace falta** para completar la clase | Pero no que esté aprobada: exigir la corrección dejaría al alumno detenido esperando a otra persona |
| Nota de **1 a 10** más aprobado/desaprobado | La nota informa, el resultado decide |
| **Publicar es un paso aparte de corregir** | El docente corrige a lo largo de la semana y suelta la tanda cuando terminó. Es lo mejor que tenía FID |
| **Reentrega sólo si la desaprueban** | Evita que el docente corrija tres versiones del mismo trabajo |

**Modelo**: `class_content.due_date` —opcional, sin fecha se entrega siempre— y
`task_submissions`, **una fila por entrega**. La reentrega no pisa la anterior:
si el docente desaprueba y el alumno vuelve a entregar, la corrección original
tiene que seguir existiendo, igual que con `student_quiz_attempts`.

**Las reglas están en `SubmissionService`**, con las transiciones como métodos
que validan y lanzan `SubmissionException`. `ProgressService::complete()` ahora
consulta las tareas pendientes, y `completionBlocker()` dice cuál falta en lugar
de un «no se puede» a secas.

**El límite de subida es el punto flojo.** El sistema declara 10 MB pero PHP en
el servidor puede cortar mucho antes —medido en desarrollo: `upload_max_filesize`
en 2 MB—, y cuando eso pasa el archivo no llega a Laravel: se pierde el cuerpo
entero de la petición, incluido el token CSRF. `HandleOversizedUpload` traduce
ese caso a un mensaje entendible en vez de un 419, pero es un parche: hay que
subir el límite en el servidor. Ver `docs/DEPLOY.md`.

### Dos apuntes más

- **Bitácora de accesos.** FID registra quién entró, cuándo y desde qué IP
  (`seguimiento`, la tabla más poblada después de las respuestas de examen). Acá
  no existe y es barato de agregar.
- **Usuario del panel acotado a un curso.** `aula_usuarios.curso` limita a un
  administrador a un curso. Es exactamente el mecanismo que va a necesitar el
  panel `/profesores` pendiente de §11.
