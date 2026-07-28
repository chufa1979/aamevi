# AAMEVI - E-Learning Platform

Plataforma de educación en línea moderna, construida con **React + NestJS + PostgreSQL + Docker**.

## 📋 Características

- ✅ **Cursos con Módulos y Clases** - Estructura jerárquica clara
- ✅ **Quiz Interactivos** - Preguntas aleatorias, calificación automática, reintentos
- ✅ **Contenido Multimodal** - Videos, PDFs, textos, tareas, clases en vivo (Google Meet)
- ✅ **Inscripción & Validación** - Formulario de inscripción, aprobación por profesor
- ✅ **Seguimiento de Progreso** - Dashboard para profesores y alumnos
- ✅ **Tareas** - Envío de archivos, calificación por profesor
- ✅ **Certificados** - Generación automática al completar cursos
- ✅ **Autenticación** - Usuario/contraseña + Google OAuth
- ✅ **Notificaciones** - Emails: verificación, recordatorios, certificados
- ✅ **PWA** - Funciona como app móvil en navegador

## 🚀 Tech Stack

### Backend
- **NestJS** - Framework Node.js con estructura modular
- **TypeORM** - ORM para PostgreSQL
- **PostgreSQL** - Base de datos relacional
- **JWT** - Autenticación segura
- **Google OAuth** - Login con Google
- **Google Cloud Storage** - Almacenamiento de archivos

### Frontend
- **React 18** - UI library
- **TypeScript** - Type safety
- **Tailwind CSS** - Utility-first CSS
- **React Router** - Navegación
- **React Query** - Manejo de estado y caché de datos
- **React Hook Form** - Validación de formularios
- **Zod** - Validación de esquemas

### DevOps
- **Docker** - Containerización
- **Docker Compose** - Orquestación local
- **Google Cloud Run** - Deployment backend (prod)
- **Vercel** - Deployment frontend (prod)

---

## 📦 Estructura del Proyecto

```
aamevi/
├── backend/                 # NestJS API
│   ├── src/
│   │   ├── modules/        # Módulos de negocio
│   │   │   ├── auth/       # Autenticación
│   │   │   ├── users/      # Usuarios
│   │   │   ├── courses/    # Cursos, módulos, clases
│   │   │   ├── quiz/       # Preguntas, evaluaciones
│   │   │   ├── tasks/      # Tareas
│   │   │   ├── notifications/
│   │   │   ├── storage/    # Upload/download
│   │   │   ├── certificates/
│   │   │   └── reports/
│   │   ├── common/         # Guards, pipes, decorators
│   │   ├── config/         # Configuración
│   │   └── database/       # Migrations, seeds
│   ├── Dockerfile
│   ├── package.json
│   └── tsconfig.json
├── frontend/                # React + Vite
│   ├── src/
│   │   ├── pages/          # Rutas principales
│   │   ├── components/     # Componentes reutilizables
│   │   ├── hooks/          # Custom hooks
│   │   ├── services/       # API calls
│   │   ├── types/          # TypeScript types
│   │   └── styles/         # CSS + Tailwind
│   ├── Dockerfile
│   ├── package.json
│   └── vite.config.ts
├── database/
│   ├── migrations/         # TypeORM migrations
│   └── seeds/              # Datos iniciales
├── docs/
│   └── PLAN_ARQUITECTONICO.md
├── docker-compose.yml
├── .env.example
├── .gitignore
└── README.md
```

---

## 🛠️ Setup Local

### Prerequisitos
- **Node.js** 18+
- **Docker & Docker Compose**
- **Git**

### Instalación

1. **Clonar el repo**
   ```bash
   git clone https://github.com/chufa1979/aamevi.git
   cd aamevi
   ```

2. **Copiar variables de entorno**
   ```bash
   cp backend/.env.example backend/.env
   cp frontend/.env.example frontend/.env
   ```

3. **Levantar con Docker**
   ```bash
   docker-compose up -d
   ```

4. **Acceder**
   - **Frontend**: http://localhost:3001
   - **Backend API**: http://localhost:3000
   - **API Docs (Swagger)**: http://localhost:3000/api/docs
   - **Base de datos**: postgres://postgres:postgres@localhost:5432/aamevi_db

### Verificar que todo funciona
```bash
# Ver logs
docker-compose logs -f backend
docker-compose logs -f frontend

# Ejecutar migraciones (primero)
docker-compose exec backend npm run migrate:run
```

---

## 📚 Documentación

- [Plan Arquitectónico](./docs/PLAN_ARQUITECTONICO.md) - Decisiones, flujos, timeline
- [API Reference](./docs/API.md) - Endpoints y ejemplos (próximamente)
- [Database Schema](./docs/SCHEMA.md) - Modelo de datos normalizado (próximamente)
- [Deployment Guide](./docs/DEPLOY.md) - Deploy a GCP (próximamente)

---

## 🔄 Flujos Principales

### 1. Inscripción de Alumno
1. Alumno llena formulario (email, contraseña, datos personales)
2. Sistema envía email de verificación con link privado
3. Alumno verifica email → acceso a plataforma
4. Solicita inscripción a curso
5. Profesor aprueba inscripción
6. Email de bienvenida al alumno

### 2. Progresión por Clase
1. Alumno accede a clase (contenido: videos, PDFs, textos)
2. Responde quiz interactivo
   - Sistema genera 3 preguntas aleatorias (distintas por alumno)
   - Calificación automática en tiempo real
3. Si ≥ puntuación mínima: siguiente clase desbloqueada
4. Si < puntuación: puede reintentar (hasta 3 intentos)

### 3. Tareas
1. Profesor sube tarea a clase con fecha de entrega
2. Alumno envía archivo
3. Profesor descarga, califica y deja feedback
4. Alumno recibe email con calificación

### 4. Certificado
1. Alumno completa todas las clases y tareas
2. Sistema genera certificado PDF automáticamente
3. Email con descarga del certificado

---

## 📊 Fases de Desarrollo

| Fase | Descripción | Semanas |
|------|-------------|---------|
| 1 | Setup + Autenticación | 1-2 |
| 2 | Cursos & Módulos | 2-3 |
| 3 | Quiz & Evaluación | 2-3 |
| 4 | Tareas | 1-2 |
| 5 | Notificaciones | 1 |
| 6 | Reportes & Certificados | 1-2 |
| 7 | Deploy | 1 |

**Total**: 10-14 semanas (dependiendo del equipo)

---

## 💰 Costos (Producción)

| Servicio | Costo |
|----------|-------|
| Google Cloud SQL (PostgreSQL) | $15-30/mes |
| Google Cloud Run (Backend) | $5-10/mes |
| Google Cloud Storage | $1-2/mes |
| **Total** | ~$25-50/mes |

---

## 🔐 Seguridad

- ✅ JWT con expiración configurable
- ✅ Contraseñas hasheadas (bcrypt)
- ✅ CORS configurado
- ✅ Validación de entrada (Zod + class-validator)
- ✅ Variables sensibles en `.env`
- ✅ Google OAuth para terceros

---

## 📞 Contacto

Para preguntas o contribuciones, contactar al equipo de desarrollo.

---

**Última actualización**: 2026-07-28
