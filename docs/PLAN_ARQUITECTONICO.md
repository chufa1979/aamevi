# Plan Arquitectónico - Migración OSDOP
## De PHP/MySQL → React + NestJS + MySQL 8 + Docker + GCP

**Fecha**: 2026-07-28  
**Escala**: 50 alumnos/curso (inicial), extensible a 1000+  
**Tecnología**: Monorepo (React + NestJS + Docker)

---

## 1. ESTRUCTURA DEL MONOREPO

```
osdop-platform/
├── docker-compose.yml          # Orquestación local (MySQL + Backend + Frontend)
├── .github/
│   └── workflows/              # CI/CD básico (opcional, deploy manual por ahora)
├── backend/                    # NestJS
│   ├── src/
│   │   ├── modules/
│   │   │   ├── auth/          # Autenticación JWT + OAuth
│   │   │   ├── users/         # Usuarios, roles, perfiles
│   │   │   ├── courses/       # Cursos, módulos, clases
│   │   │   ├── quiz/          # Preguntas, respuestas, calificaciones
│   │   │   ├── tasks/         # Tareas (envío/validación)
│   │   │   ├── notifications/ # Emails, recordatorios
│   │   │   ├── storage/       # Upload/download de archivos
│   │   │   ├── certificates/  # Generación de certificados
│   │   │   └── reports/       # Dashboards y reportes
│   │   ├── common/            # Guards, decorators, pipes
│   │   ├── config/            # Variables de entorno
│   │   ├── database/          # Migrations, seeders
│   │   └── main.ts
│   ├── .env.example
│   ├── Dockerfile
│   └── package.json
├── frontend/                   # React + TypeScript
│   ├── src/
│   │   ├── pages/
│   │   │   ├── auth/          # Login, registro, reset password
│   │   │   ├── dashboard/     # Inicio por rol
│   │   │   ├── courses/       # Listado, detalle, inscripción
│   │   │   ├── classroom/     # Ver clase, contenido, quiz
│   │   │   ├── admin/         # Panel administrador
│   │   │   └── profile/       # Perfil usuario
│   │   ├── components/        # Componentes reutilizables
│   │   ├── hooks/             # Custom hooks (auth, api, etc)
│   │   ├── services/          # API calls
│   │   ├── types/             # TypeScript types
│   │   ├── styles/            # Tailwind/CSS
│   │   └── App.tsx
│   ├── .env.example
│   ├── Dockerfile
│   └── package.json
├── database/
│   ├── migrations/            # SQL migrations (TypeORM)
│   └── seeds/                 # Datos iniciales
└── docs/
    ├── API.md                 # Documentación OpenAPI
    ├── SCHEMA.md              # Esquema de BD
    └── DEPLOY.md              # Guía de deployment
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

### Backend - Módulo Auth
```typescript
// src/modules/auth/auth.service.ts
- register(email, password, firstName, lastName, type: 'student' | 'teacher')
- login(email, password) → JWT token
- verifyEmail(token)
- googleOAuth(accessToken) → crea/loguea usuario
- requestPasswordReset(email)
- resetPassword(token, newPassword)
- validateToken(jwt) → returns user

// src/modules/auth/jwt.strategy.ts
- Guardia para rutas protegidas
- Extrae role del usuario
```

### Backend - Módulo Courses
```typescript
// src/modules/courses/courses.service.ts
- createCourse(title, description, teacherId)
- getCoursesByTeacher(teacherId)
- getCoursesByStudent(studentId)
- enrollStudent(courseId, studentId) → status='pending'
- approveEnrollment(enrollmentId, teacherId)
- createModule(courseId, title, order)
- createClass(moduleId, title, activationDate, meetLink?)

// src/modules/courses/courses.controller.ts
- GET /courses
- POST /courses
- GET /courses/:id
- POST /courses/:id/enroll
- GET /courses/:id/enrollments (solo para profesor)
- PATCH /enrollments/:id/approve (admin/profesor)
```

### Backend - Módulo Quiz
```typescript
// src/modules/quiz/quiz.service.ts
- createQuestion(classId, text, options, correctOption)
- createQuiz(classId, questionsPerStudent, passingScore, maxAttempts)
- getRandomQuestions(quizId, studentId, count) 
  → Selecciona N preguntas aleatorias, las asigna a este alumno
- submitQuiz(attemptId, answers)
  → Califica automáticamente, retorna score + passed
- getStudentAttempts(quizId, studentId) → historial de intentos
```

### Frontend - Páginas Principales
```typescript
// pages/auth/LoginPage.tsx
- Form: email + contraseña
- Botón: "Login con Google"
- Link: "¿Olvidaste tu contraseña?"
- Redirect después de login según role

// pages/dashboard/StudentDashboard.tsx
- Mis Cursos (card con progreso %)
- Clases próximas
- Tareas pendientes
- Certificados

// pages/dashboard/TeacherDashboard.tsx
- Mis Cursos
- Inscripciones pendientes (botón Aprobar/Rechazar)
- Tareas para calificar
- Reportes (quién completó qué)

// pages/courses/CourseDetail.tsx
- Información del curso
- Botón "Inscribirse" (si no estoy inscrito)
- Módulos → Clases (árbol visual)

// pages/classroom/ClassDetail.tsx
- Contenido: videos, PDFs, textos
- Quiz (si existe)
  * Mostra preguntas
  * Maneja reintentos
  * Muestra score al enviar
- Tareas (submit file, ver calificación)
- Botón siguiente clase (si completó)
```

---

## 5. CONFIGURACIÓN LOCAL CON DOCKER

### docker-compose.yml
```yaml
version: '3.8'

services:
  mysql:
    image: mysql:8.0
    command: --character-set-server=utf8mb4 --collation-server=utf8mb4_unicode_ci
    environment:
      MYSQL_DATABASE: osdop
      MYSQL_USER: osdop
      MYSQL_PASSWORD: changeme
      MYSQL_ROOT_PASSWORD: changeme
    ports:
      - "3306:3306"
    volumes:
      - mysql_data:/var/lib/mysql

  backend:
    build: ./backend
    ports:
      - "3000:3000"
    environment:
      DATABASE_URL: "mysql://osdop:changeme@mysql:3306/osdop"
      JWT_SECRET: "your-secret-key"
      GOOGLE_CLIENT_ID: "xxx.apps.googleusercontent.com"
      GOOGLE_CLIENT_SECRET: "xxx"
      SENDGRID_API_KEY: "SG.xxx"
      GCS_BUCKET_NAME: "osdop-files"
    depends_on:
      - mysql
    volumes:
      - ./backend:/app

  frontend:
    build: ./frontend
    ports:
      - "3001:3000"
    environment:
      REACT_APP_API_URL: "http://localhost:3000"
      REACT_APP_GOOGLE_CLIENT_ID: "xxx.apps.googleusercontent.com"
    depends_on:
      - backend
    volumes:
      - ./frontend:/app

volumes:
  mysql_data:
```

### Levantar localmente
```bash
# Clonar repo
git clone <repo> osdop-platform
cd osdop-platform

# Copiar .env
cp backend/.env.example backend/.env
cp frontend/.env.example frontend/.env

# Levantar
docker-compose up -d

# Base de datos (migrations)
docker-compose exec backend npm run migrate

# Acceder
# Frontend: http://localhost:3001
# Backend: http://localhost:3000
# Swagger API: http://localhost:3000/api/docs
```

---

## 6. TECNOLOGÍAS & LIBRERÍAS

### Backend (NestJS)
```json
{
  "dependencies": {
    "@nestjs/core": "^10.0.0",
    "@nestjs/common": "^10.0.0",
    "@nestjs/jwt": "^11.0.0",
    "@nestjs/passport": "^9.0.0",
    "passport-jwt": "^4.0.1",
    "passport-google-oauth20": "^2.0.0",
    "typeorm": "^0.3.0",
    "pg": "^8.0.0",
    "axios": "^1.6.0",
    "@google-cloud/storage": "^6.10.0",
    "nodemailer": "^6.9.0",
    "@nestjs/swagger": "^7.0.0"
  }
}
```

### Frontend (React)
```json
{
  "dependencies": {
    "react": "^18.2.0",
    "react-router-dom": "^6.20.0",
    "axios": "^1.6.0",
    "@tanstack/react-query": "^5.0.0",
    "zustand": "^4.4.0",
    "tailwindcss": "^3.4.0",
    "react-hook-form": "^7.48.0",
    "zod": "^3.22.0"
  }
}
```

---

## 7. TIMELINE DE IMPLEMENTACIÓN

### Fase 1: Setup & Autenticación (1-2 semanas)
- [ ] Crear repo monorepo
- [ ] Configurar Docker local
- [ ] Modelos de BD (users, students, teachers)
- [ ] Implementar Auth (JWT + Google OAuth)
- [ ] Login/Register frontend
- [ ] Email verification

### Fase 2: Core Cursos & Clases (2-3 semanas)
- [ ] Modelos: courses, modules, classes, class_content
- [ ] CRUD de cursos (profesor)
- [ ] Inscripción de alumnos (con aprobación)
- [ ] Frontend: lista de cursos, detalle, inscripción
- [ ] Visualización de contenido (videos, PDFs, textos)

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
- [ ] Configurar GCP (Cloud Run, Cloud Storage)
- [ ] Docker image para prod
- [ ] Variables de entorno
- [ ] Testing manual completo
- [ ] Documentación de deploy

**Total estimado: 10-14 semanas** (depende del equipo)

---

## 8. DEPLOYMENT EN GCP

### Estructura
```
Google Cloud Project
├── Cloud SQL (MySQL 8)
├── Cloud Run (Backend NestJS)
├── Cloud Storage (Archivos: videos, PDFs, certificados)
└── Vercel o Cloud Run (Frontend React)
```

### Pasos (deploy manual)
```bash
# 1. Buildear
docker build -t gcr.io/my-project/osdop-backend:latest ./backend
docker build -t gcr.io/my-project/osdop-frontend:latest ./frontend

# 2. Push a Container Registry
docker push gcr.io/my-project/osdop-backend:latest
docker push gcr.io/my-project/osdop-frontend:latest

# 3. Deploy a Cloud Run
gcloud run deploy osdop-backend \
  --image gcr.io/my-project/osdop-backend:latest \
  --platform managed \
  --region us-central1 \
  --set-env-vars DATABASE_URL=... JWT_SECRET=...

# 4. Frontend a Vercel (o Cloud Run)
cd frontend
npm run build
vercel --prod
```

Costo estimado:
- **Cloud SQL**: $15-30/mes (pequeña instancia MySQL)
- **Cloud Run**: $5-10/mes (bajo tráfico)
- **Cloud Storage**: $0.020/GB + transferencia (mínimo $1-2/mes)
- **Total**: ~$25-50/mes

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

### ¿Por qué NestJS?
- ✅ Estructura modular clara
- ✅ TypeScript nativo (type-safety)
- ✅ Integración fácil con TypeORM
- ✅ Middleware, guards, pipes listos
- ✅ Swagger automático (documentación)

### ¿Por qué React?
- ✅ Ecosistema más grande
- ✅ Componentes reutilizables
- ✅ Performance excelente
- ✅ TypeScript soporte nativo
- ✅ Muchas librerías (react-query, zustand, etc)

### ¿Por qué Google Cloud Storage (no local)?
- ✅ Escalable (no depende del servidor)
- ✅ CDN integrado (videos cargan rápido)
- ✅ Backup automático
- ✅ CORS fácil de configurar
- ✅ Integración con Google Meet

### Aleatorización de Preguntas
**Problema**: Todos ven las mismas 3 preguntas → facilita copia
**Solución**: Por cada alumno, seleccionar N preguntas aleatorias **en el momento que empieza el intento**
```typescript
// Backend
const questions = await getRandomQuestions(quizId, 3);
// Guardar en quiz_question_assignment para tracking
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
```typescript
// Al enviar quiz, backend retorna:
{
  score: 75,
  passed: true,
  feedback: "¡Pasaste con 75%! Siguiente clase desbloqueada.",
  correctAnswers: [
    { questionId: 1, userAnswer: "A", correct: true },
    { questionId: 2, userAnswer: "C", correct: false, correctOption: "B" }
  ]
}
```

**PWA (Progressive Web App)**:
- `manifest.json` para instalar como app
- Service Worker para funcionar offline
- Responsive design (mobile-first con Tailwind)
- Tailwind breakpoints: `sm`, `md`, `lg`, `xl`
- Camera/file access en mobile (para subir tareas)

---

## 11. PRÓXIMOS PASOS

1. ✅ **Plan arquitectónico**: Completado y guardado
2. **Setup del repo**: Crear estructura monorepo
3. **Configurar Docker local**: `docker-compose.yml`
4. **Modelo de BD**: Migraciones TypeORM
5. **Empezar Fase 1**: Auth (JWT + Google OAuth)

**¿Listo para empezar?**
