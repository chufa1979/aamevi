# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project status

AAMEVI is an e-learning platform built with Laravel + Blade + MySQL + Docker. **The repo is currently a scaffold**, not a working app: the app structure is initialized but contains only boilerplate controllers, migrations, and views. None of the business modules exist yet.

The authoritative spec for what gets built is `docs/PLAN_ARQUITECTONICO.md` (Spanish, 825 lines) — full normalized SQL schema, per-module component breakdown, and the main user flows. **Read the relevant section of that doc before implementing a feature**; the entity definitions, enum values, and unique constraints there are the contract. `README.md` summarizes the same flows at a higher level.

Project docs, commit messages, and UI copy are in Spanish. Code identifiers are in English.

## Commands

Everything runs through Docker Compose (`mysql` + `app`):

```bash
docker-compose up -d
docker-compose logs -f app
docker-compose exec app php artisan migrate
```

Ports: app http://localhost:8000, MySQL 3306 (`aamevi:aamevi@localhost:3306/aamevi_db`).

App (`cd app` or `docker-compose exec app`):

| Task | Command |
|---|---|
| Dev server | `php artisan serve` |
| Lint (Laravel Pint) | `php artisan pint` |
| All unit tests | `php artisan test` |
| **Single test** | `php artisan test tests/Unit/FooTest.php` or `php artisan test --filter TestName` |
| Feature tests | `php artisan test tests/Feature` |
| Coverage | `php artisan test --coverage` |
| Migration (new, empty) | `php artisan make:migration create_table_name` |
| Apply / revert | `php artisan migrate` / `php artisan migrate:rollback` |
| Seed | `php artisan db:seed` (expects seeders in `database/seeders/`) |
| Make controller | `php artisan make:controller ModuleName/ControllerName` |
| Make model | `php artisan make:model ModelName -m` (with migration) |
| Tinker (REPL) | `php artisan tinker` |

Tests live in `tests/Unit/` and `tests/Feature/`. PHPUnit is the test runner; colocate feature tests with the features they cover.

## Architecture

**App** — Laravel 11 + Blade + Eloquent ORM + MySQL 8 (InnoDB, `utf8mb4_unicode_ci`). Business logic lives in feature modules under `app/Http/Controllers/` organized by domain (`Auth`, `Users`, `Courses`, `Quiz`, `Tasks`, `Notifications`, `Storage`, `Certificates`, `Reports`), with models in `app/Models/`, form requests for validation in `app/Http/Requests/`, and middleware in `app/Http/Middleware/`. Migrations live in `database/migrations/`, seeders in `database/seeders/`.

Request validation uses `FormRequest` classes (not inline rules) with `authorize()` and `rules()` methods. All request/model validation must be explicit — no relying on defaults.

Eloquent runs with schema changes **only** via migrations in `database/migrations/`. Create migrations early and run them frequently to catch schema issues. Use `artisan make:migration` with descriptive names (`create_courses_table`, `add_status_to_users`, etc.). Model factories live in `database/factories/` for testing and seeding.

Naming conventions: controllers use `CamelCase` and inherit from `Controller`, models inherit from `Model`, requests inherit from `FormRequest`. Use route model binding (implicit binding in routes) to resolve IDs to models automatically.

**Views** — Blade templates in `resources/views/` mirrored to the institutional site **https://www.aamevi.ar** (its `css/global.css` and `images/aamevi.svg` are the source of truth). Tailwind is configured in `tailwind.config.js`: `primary` = `#00b8b3` (institutional teal), `accent` = `#f46707` (orange, used for nav hover and CTAs), `ink` `#333333`, `surface` `#ececec` (page background), plus a `pillar.*` ramp taken from the six colors of the logo. Typography is Montserrat, imported in `resources/css/app.css`. Reusable Blade components live in `resources/views/components/` — prefer these over re-deriving styling inline.

Signature elements to preserve when adding pages: the 6px teal bottom border on the header and on page heroes, uppercase nav with orange hover, the translucent orange dropdown submenu, the asymmetric search input (`rounded-br-[15px]`, teal bottom/right borders only), and the dark `#333333` footer. Existing layout components already encapsulate section structure.

Note the parent site sets `body { font-size: 62.5% }` and sizes everything in `em`. This project uses Tailwind's 16px rem base directly — don't port `em` values from `global.css` literally.

**Domain model** (see `docs/PLAN_ARQUITECTONICO.md` §2 for full DDL). All PKs are UUIDs. `users` is the auth base table; `students` and `teachers` are 1:1 extensions keyed on `users.id`. Content hierarchy is `courses → modules → classes → class_content`. Enrollment (`course_enrollments`) is a state machine: `pending → approved/rejected → active → completed`, approved by a teacher.

The quiz subsystem is the most intricate part: a `quizzes` row configures per-class rules (`questions_per_student`, `passing_score`, `max_attempts`, `randomize_options`); each attempt draws a random subset from the class's `questions` bank, and the specific questions a student saw are recorded in `quiz_question_assignment` so attempts stay reproducible. Class progression gates on passing the quiz (`student_progress`), and completing all classes/tasks triggers certificate generation.

Emails are not sent inline — they're written to `email_queue` (typed by `email_type`) with `status`/`retry_count` for a worker to drain.

External services: Google Cloud Storage for uploads (videos, PDFs, submissions, certificate PDFs — DB stores URLs only), Google OAuth for social login, SendGrid/nodemailer for email, Google Meet links for live classes.

## Known scaffold gaps

These will bite on first run — fix them rather than working around them:

- **`.env` file is missing or incomplete**: Copy `.env.example` to `.env` and configure `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` to match the Docker Compose mysql service. Application key needs to be generated (`php artisan key:generate`).
- **Database not initialized**: Run `php artisan migrate` inside the container after docker-compose starts to create tables.
- **No seeders defined**: Create seeders in `database/seeders/` and run `php artisan db:seed` to populate initial data.
- `.github/workflows/` is empty — no CI.

## Formatting

Use Laravel Pint for code style (`php artisan pint`). The project follows PSR-12 standards: 4-space indentation, descriptive naming, no trailing whitespace. IDE should be configured to format on save using Pint rules.
