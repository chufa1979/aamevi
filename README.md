# AAMEVI - E-Learning Platform

Plataforma de educación en línea moderna, construida con **Laravel + Blade + PostgreSQL + Docker**.

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

### Backend & Frontend (Full Stack)
- **Laravel 11** - Framework PHP con soporte a Blade templates
- **Eloquent ORM** - ORM para PostgreSQL
- **PostgreSQL** - Base de datos relacional
- **Blade** - Motor de templates
- **Tailwind CSS** - Utility-first CSS
- **Laravel Sanctum** - Autenticación segura
- **Google OAuth** - Login con Google
- **Google Cloud Storage** - Almacenamiento de archivos

### DevOps
- **Docker** - Containerización
- **Docker Compose** - Orquestación local
- **Nginx** - Reverse proxy
- **PHP-FPM** - PHP FastCGI Process Manager

---

## 📦 Estructura del Proyecto

```
aamevi/
├── app/                     # Código Laravel
│   ├── Http/
│   │   ├── Controllers/    # Controladores por dominio
│   │   │   ├── Auth/       # Autenticación
│   │   │   ├── Users/      # Usuarios
│   │   │   ├── Courses/    # Cursos, módulos, clases
│   │   │   ├── Quiz/       # Preguntas, evaluaciones
│   │   │   ├── Tasks/      # Tareas
│   │   │   ├── Notifications/
│   │   │   ├── Storage/    # Upload/download
│   │   │   ├── Certificates/
│   │   │   └── Reports/
│   │   ├── Middleware/     # Middleware personalizado
│   │   └── Requests/       # Form Requests (validación)
│   ├── Models/             # Modelos Eloquent
│   ├── Providers/          # Service Providers
│   └── Console/            # Comandos artisan personalizados
├── bootstrap/
│   └── app.php             # Configuración de la aplicación
├── config/                 # Archivos de configuración
│   ├── app.php
│   ├── database.php
│   ├── filesystems.php
│   └── logging.php
├── database/
│   ├── migrations/         # Migraciones de esquema
│   ├── seeders/            # Seeders para datos iniciales
│   └── factories/          # Model factories para testing
├── resources/
│   ├── views/              # Blade templates
│   │   ├── layouts/        # Layouts base
│   │   ├── components/     # Componentes Blade reutilizables
│   │   └── ...             # Vistas por feature
│   ├── css/                # Estilos (Tailwind)
│   ├── js/                 # JavaScript (Alpine.js, etc.)
│   └── lang/               # Localizaciones (es)
├── routes/
│   ├── web.php             # Rutas web
│   ├── api.php             # Rutas API
│   └── console.php         # Comandos CLI
├── storage/                # Archivos generados
│   ├── app/                # Uploads
│   ├── logs/               # Logs de aplicación
│   └── framework/          # Cache, sessions
├── tests/
│   ├── Unit/               # Tests unitarios
│   └── Feature/            # Tests de características
├── public/
│   ├── index.php           # Punto de entrada
│   ├── css/                # CSS compilado
│   └── js/                 # JS compilado
├── docs/
│   └── PLAN_ARQUITECTONICO.md
├── Dockerfile
├── docker-compose.yml
├── nginx.conf
├── composer.json
├── artisan                 # Laravel CLI
├── .env.example
├── .gitignore
└── README.md
```

---

## 🛠️ Setup Local

### Prerequisitos
- **PHP 8.2+**
- **Composer**
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
   cp .env.example .env
   ```

3. **Levantar con Docker**
   ```bash
   docker-compose up -d
   ```

4. **Generar app key**
   ```bash
   docker-compose exec app php artisan key:generate
   ```

5. **Acceder**
   - **Aplicación**: http://localhost:8000
   - **API Health Check**: http://localhost:8000/api/health
   - **Base de datos**: postgres://postgres:postgres@localhost:5432/aamevi_db

### Verificar que todo funciona
```bash
# Ver logs
docker-compose logs -f app

# Ejecutar migraciones (se hacen automáticamente en startup)
docker-compose exec app php artisan migrate

# Ejecutar seeders
docker-compose exec app php artisan db:seed

# Acceder a Tinker (REPL de Laravel)
docker-compose exec app php artisan tinker
```

---

## 📚 Documentación

- [Plan Arquitectónico](./docs/PLAN_ARQUITECTONICO.md) - Decisiones, flujos, timeline
- [Sistema de Diseño](./docs/SISTEMA_DISENO.md) - Identidad visual derivada de www.aamevi.ar
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

**Última actualización**: 2026-08-06
