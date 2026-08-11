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
administrador. Permite gestionar usuarios con sus fichas de alumno o profesor, y
el contenido académico: cursos, módulos y clases, con el cronograma y
corrimiento de fechas en lote.

**Pendiente**: el contenido dentro de cada clase (videos, PDFs), las
inscripciones, el catálogo público, los quizzes, las tareas y los certificados.
Todo está especificado en el
[plan arquitectónico](./docs/PLAN_ARQUITECTONICO.md), que también lleva la
cuenta de qué está hecho.

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
| Base de datos | MySQL 8 (InnoDB, `utf8mb4_unicode_ci`) |
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

Requiere **PHP ≥ 8.2**, **Composer 2**, **Node ≥ 20.19** y **MySQL 8**.

```bash
git clone https://github.com/chufa1979/aamevi.git
cd aamevi

composer install
npm install

cp .env.example .env
php artisan key:generate
# configurar DB_* en .env

php artisan migrate
```

Desarrollo con **dos procesos**, en dos terminales:

```bash
php artisan serve      # http://localhost:8000
npm run dev            # servidor de Vite con recarga en caliente
```

El detalle, los comandos habituales y los problemas frecuentes están en
[`GETTING_STARTED.md`](./GETTING_STARTED.md).

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

**Última actualización**: 2026-08-11
