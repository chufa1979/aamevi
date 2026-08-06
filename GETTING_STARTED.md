# Guía de Inicio Rápido - AAMEVI

Esta es una estructura base de un proyecto Laravel con Docker preconfigurado.

## 🚀 Primeros pasos

### 1. Instalar dependencias de PHP (LOCAL)
Si quieres trabajar localmente sin Docker:
```bash
composer install
```

### 2. Generar app key
```bash
# Con Docker
docker-compose exec app php artisan key:generate

# O local
php artisan key:generate
```

### 3. Levantar con Docker
```bash
docker-compose up -d
```

Esto va a:
- Crear un contenedor PostgreSQL
- Crear un contenedor PHP-FPM con la app
- Crear un contenedor Nginx (reverse proxy)
- Ejecutar migraciones automáticamente

### 4. Verificar que funciona
```bash
# Ver logs
docker-compose logs -f app

# Acceder a la app
open http://localhost:8000

# API health check
curl http://localhost:8000/api/health
```

## 📚 Comandos útiles

### Artisan (dentro del contenedor o local)
```bash
# Ver todas las migraciones
php artisan migrate:status

# Crear una nueva migración
php artisan make:migration create_users_table

# Crear un controlador
php artisan make:controller Courses/CourseController

# Crear un modelo con migración
php artisan make:model Course -m

# Ejecutar seeders
php artisan db:seed

# Ver todas las rutas
php artisan route:list

# Acceder a Tinker (REPL)
php artisan tinker
```

### Docker
```bash
# Ver logs en vivo
docker-compose logs -f app

# Ejecutar un comando adentro
docker-compose exec app php artisan migrate

# Entrar a bash en el contenedor
docker-compose exec app bash

# Acceder a la base de datos
docker-compose exec postgres psql -U postgres -d aamevi_db

# Detener containers
docker-compose down

# Reconstruir la imagen
docker-compose up -d --build
```

## 🗄️ Base de datos

Acceder a PostgreSQL:
```bash
docker-compose exec postgres psql -U postgres -d aamevi_db
```

Credenciales:
- Host: postgres
- Puerto: 5432
- Usuario: postgres
- Contraseña: postgres
- Base de datos: aamevi_db

## 📝 Desarrollo

### Estructura de directorios
- `app/` - Código PHP (Controllers, Models, etc.)
- `routes/` - Definición de rutas (web.php, api.php)
- `resources/views/` - Templates Blade
- `database/migrations/` - Cambios de schema
- `tests/` - Tests unitarios y feature
- `config/` - Configuración

### Crear un CRUD completo
1. Crear modelo + migración: `php artisan make:model Post -m`
2. Editar migración en `database/migrations/`
3. Ejecutar: `php artisan migrate`
4. Crear controlador: `php artisan make:controller Posts/PostController`
5. Definir rutas en `routes/web.php`
6. Crear vistas en `resources/views/posts/`

### Testing
```bash
# Correr tests
php artisan test

# Correr un test específico
php artisan test tests/Feature/ExampleTest.php

# Con cobertura
php artisan test --coverage
```

## 🔧 Troubleshooting

### "Connection refused" a PostgreSQL
- Verificar que el contenedor postgres está corriendo: `docker-compose ps`
- Verificar variables de entorno en `.env`
- Revisar logs: `docker-compose logs postgres`

### "Laravel is not installed"
```bash
# Instalar dependencias en el contenedor
docker-compose exec app composer install
```

### Migraciones no se ejecutan
```bash
# Ejecutar manualmente
docker-compose exec app php artisan migrate

# Ver estado
docker-compose exec app php artisan migrate:status
```

### Port 8000 ya está en uso
Cambiar en `docker-compose.yml`:
```yaml
ports:
  - "8001:8000"  # Usar 8001 en lugar de 8000
```

## 📖 Documentación

- [CLAUDE.md](./CLAUDE.md) - Guía para Claude Code
- [README.md](./README.md) - Descripción general del proyecto
- [PLAN_ARQUITECTONICO.md](./docs/PLAN_ARQUITECTONICO.md) - Especificación técnica
- [Laravel docs](https://laravel.com/docs)

## 💡 Tips

- Usar Tinker para explorar: `php artisan tinker`
- Los tests van en `tests/Unit/` (unitarios) y `tests/Feature/` (con BD)
- Migrations se ejecutan cada startup si hay pendientes
- Usar `FormRequest` para validación de requests
- Blade components van en `resources/views/components/`

---

**¿Necesitas ayuda?** Revisa [CLAUDE.md](./CLAUDE.md) para instrucciones detalladas.
