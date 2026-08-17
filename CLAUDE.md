# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project status

AAMEVI is an e-learning platform built with Laravel 12 + Blade + MySQL 8. The front-end shell exists — layout, brand components, asset pipeline, placeholder routes — but **none of the business modules do**: `database/migrations/` is empty and there are no domain models or controllers yet.

The authoritative spec for what gets built is `docs/PLAN_ARQUITECTONICO.md` (Spanish) — full normalized SQL schema, per-module component breakdown, and the main user flows. **Read the relevant section of that doc before implementing a feature**; the entity definitions, enum values, and unique constraints there are the contract. `README.md` summarizes the same flows at a higher level, and `docs/DEPLOY.md` covers the target hosting.

Project docs, commit messages, and UI copy are in Spanish. Code identifiers are in English.

## Commands

**No Docker.** The project runs directly on local PHP and MySQL — the target hosting is shared and has no containers, so this mirrors production.

Development needs **two processes**:

```bash
php artisan serve      # http://localhost:8000
npm run dev            # Vite dev server — without it, @vite falls back to public/build
```

| Task | Command |
|---|---|
| Dev server | `php artisan serve` |
| Asset build | `npm run build` |
| Lint (Laravel Pint) | `./vendor/bin/pint` |
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

Tests live in `tests/Unit/` and `tests/Feature/`. Pest is the test runner; colocate feature tests with the features they cover.

## Architecture

**App** — Laravel 12 (PHP 8.2+) + Blade + Eloquent ORM + MySQL 8 (InnoDB, `utf8mb4_unicode_ci`). `composer.json` pins `config.platform.php` to **8.3.11**, the PHP version on the deployment target's CLI — dependencies must resolve against that, not against whatever runs locally. Laravel 13 is not an option: it requires PHP 8.4.1+ via Symfony 8, and the server's CLI tops out at 8.3 (see `docs/DEPLOY.md`). Business logic lives in feature modules under `app/Http/Controllers/` organized by domain (`Auth`, `Users`, `Courses`, `Quiz`, `Tasks`, `Notifications`, `Storage`, `Certificates`, `Reports`), with models in `app/Models/`, form requests for validation in `app/Http/Requests/`, and middleware in `app/Http/Middleware/`. Migrations live in `database/migrations/`, seeders in `database/seeders/`.

Request validation uses `FormRequest` classes (not inline rules) with `authorize()` and `rules()` methods. All request/model validation must be explicit — no relying on defaults.

Eloquent runs with schema changes **only** via migrations in `database/migrations/`. Create migrations early and run them frequently to catch schema issues. Use `artisan make:migration` with descriptive names (`create_courses_table`, `add_status_to_users`, etc.). Model factories live in `database/factories/` for testing and seeding.

Naming conventions: controllers use `CamelCase` and inherit from `Controller`, models inherit from `Model`, requests inherit from `FormRequest`. Use route model binding (implicit binding in routes) to resolve IDs to models automatically.

The site menu is role-aware: `config/navigation.php` marks items with `roles`, and `App\Support\Navigation` filters them and appends the panel link for whoever has one. Most of that menu belongs to the classroom, and the classroom belongs to students — offered to an admin, every one of those items answered 403.

**Views** — Blade templates in `resources/views/` mirrored to the institutional site **https://www.aamevi.ar** (its `css/global.css` and `images/aamevi.svg` are the source of truth). Tailwind 4 is configured **in CSS**, not in a `tailwind.config.js` — the tokens live in the `@theme` block of `resources/css/app.css`: `--color-primary` = `#00b8b3` (institutional teal), `--color-accent` = `#f46707` (orange, used for nav hover and CTAs), `--color-ink` `#333333`, `--color-surface` `#ececec` (page background), plus `--color-pillar-*` taken from the six colors of the logo, `--container-site` and the `--text-title*` sizes. Adding a design token means adding a variable there. Typography is Montserrat, imported at the top of the same file. Reusable Blade components live in `resources/views/components/` — prefer these over re-deriving styling inline. `config/navigation.php` is the single source for menu items and contact data.

Signature elements to preserve when adding pages: the 6px teal bottom border on the header and on page heroes, uppercase nav with orange hover, the translucent orange dropdown submenu, the asymmetric search input (`rounded-br-[15px]`, teal bottom/right borders only), and the dark `#333333` footer. Existing layout components already encapsulate section structure.

Note the parent site sets `body { font-size: 62.5% }` and sizes everything in `em`. This project uses Tailwind's 16px rem base directly — don't port `em` values from `global.css` literally.

**Domain model** (see `docs/PLAN_ARQUITECTONICO.md` §2 for full DDL). All PKs are UUIDs. `users` is the auth base table; `students` and `teachers` are 1:1 extensions keyed on `users.id`. Content hierarchy is `courses → modules → classes → class_content`. Enrollment (`course_enrollments`) is a state machine: `pending → approved/rejected → active → completed`, approved by a teacher.

The quiz subsystem is the most intricate part: a `quizzes` row configures per-class rules (`questions_per_student`, `passing_score`, `max_attempts`, `randomize_options`); each attempt draws a random subset from the class's `questions` bank, and the specific questions a student saw are recorded in `quiz_question_assignment` so attempts stay reproducible. Class progression gates on passing the quiz (`student_progress`), and completing all classes/tasks triggers certificate generation.

Emails are not sent inline — they're written to `email_queue` (typed by `email_type`) with `status`/`retry_count` for a worker to drain.

External services: Google Cloud Storage for uploads (videos, PDFs, submissions, certificate PDFs — DB stores URLs only), Google OAuth for social login, SendGrid/nodemailer for email, Google Meet links for live classes.

## Two surfaces

The app has a **public site** (Blade, `resources/views/`) and an **admin panel** (Filament, `app/Filament/`). They share models, session and access rules — not views or controllers. When adding a feature, decide which surface it belongs to first: course *administration* is Filament, the course *catalogue and classroom* are Blade.

Filament serves that surface through **two panels over one set of resources**: `/admin` for administrators and `/profesores` for teachers (`TeacherPanelProvider` registers a shorter list of the same resource classes — no user accounts). Never scope anything by panel id: what a teacher may see comes from `Course::scopeVisibleTo(User)`, which the resources apply via `App\Filament\Concerns\ScopedToOwnCourses`, and what they may do comes from the policies in `app/Policies/` (`CoursePolicy`, and `CoursePartPolicy` for everything hanging off a course). Both hold whichever URL the request came in through, and because Filament resolves records through the resource query, a teacher who types another teacher's course URL gets a 404.

Everything is behind `auth`: `routes/web.php` splits into a `guest` group (login only) and an `auth` group (everything else). There is deliberately **no second login form** — the Filament panel does not expose `/admin/login`; admins sign in at `/login` like everyone else, so there is one rate-limited entry point to audit. Where each role lands afterwards comes from `User::homeUrl()` — students to `/mis-cursos`, teachers and admins to their panel. It is used both after signing in and by the `guest` middleware, so opening `/login` with a session already open takes you to your own screen rather than the front page. The URL the auth middleware stashed before asking for credentials still wins — but only if `User::canReach()` says that surface is theirs, because the stash survives in the browser session and belongs to whoever browsed last, not to whoever logged in.

## Admin panel (Filament 5)

Resources live in `app/Filament/Resources/`, one folder each, with the form and table split into `Schemas/` and `Tables/` classes — that layout is what the v5 generator produces, so keep it.

- **Generate, don't hand-write**: `php artisan make:filament-resource Foo --generate`. The v5 API differs substantially from v3 (`Schema` not `Form`, `Filament\Actions` unified, relation managers as separate classes).
- **Always review what it generates.** Two recurring defects: relation selects render raw UUIDs, and password fields overwrite the stored hash with `null` on edit.
- **Navigation is grouped and tabbed**, mirroring the FID admin analysed in §13 of the plan. Four sidebar groups — `Cursos`, `Alumnos`, `Evaluación`, `Sistema` — declared in `AdminPanelProvider::navigationGroups()`, not via per-resource `$navigationSort`. Icons belong on the *group*: Filament refuses to render them on both group and items.
- **A course opens into tabs** via `getRecordSubNavigation()` with `SubNavigationPosition::Top`: Info general · Planificación · Contenidos · Exámenes · Alumnos del curso · Seguimiento alumnos. Modules and classes have their own tabs one level down.
- **Use `ManageRelatedRecords` pages, not relation managers**, for anything that needs to nest further. A relation manager cannot nest another, but a page can carry its own sub-navigation — that is what makes course → module → class navigable. `CourseModuleResource` and `CourseClassResource` stay hidden from navigation and have no create page: their records are created from their parent.
- **Ordering is done by dragging, not by typing a number.** Use `App\Filament\Tables\DragToReorder::apply($table)` — it enables `reorderable()`, disables pagination, and shifts the affected rows out of the target range first. Without that pre-pass, swapping two positions violates `unique(parent_id, order_number)`, because Filament writes every position in a single `UPDATE … CASE`. The form keeps a `Hidden` field defaulting to `max + 1` so new records land last.
- **Screens that cross students with classes must not query per cell.** Ask the service once — see `ProgressService::courseMatrix()`, which resolves the whole grid in three queries and has a test pinning it to `canAccess()`.
- **Labels and colours belong on the enum** (`HasLabel`, `HasColor`), as `UserRole` does — not repeated per resource.
- **`x-icon` is taken** by blade-icons, a Filament dependency, and shadows any component of that name. The project's own is `<x-ui.icon>`.
- **A Blade view rendered inside the panel cannot use the site's Tailwind utilities.** Filament serves its own CSS and never loads the Vite bundle, so `grid`, `h-96` and friends are inert there — silently, which is how it gets missed. Style panel views with `.aamevi-*` classes in `resources/css/filament/admin.css`, using Filament's own CSS variables so dark mode follows.
- Filament's assets sit outside the Vite build; `php artisan filament:assets` republishes them (already in `deploy.sh`).

## Domain rules live in code, not in the schema

Business rules are enforced in models and services, each covered by a test. **§3-bis of the plan documents all of them** — read it before touching enrollment, quiz or progression logic. The short version:

- **State transitions are methods**, not `status` assignments. `CourseEnrollment::approve()/reject()/activate()/complete()` each validate the source state and throw `EnrollmentException`. Same idea for quizzes.
- **Business exceptions throw, they don't return false.** Approving an already-rejected enrollment is a programming error and must fail loudly; the panel catches and turns it into a notification.
- **Two services own the intricate parts**: `QuizService` (draw, grade, attempt limits) and `ProgressService` (who can open which class, and why not). Controllers and Filament actions call them — don't reimplement.
- **`quiz_question_assignment` records which questions each student got.** The draw is per-student, so without it a disputed grade cannot be reconstructed. Questions are `RESTRICT` on delete for the same reason.
- **Emails are never sent inline.** `NotificationService` renders subject and body and writes them to `email_queue`; `emails:enviar` drains it from cron. The hosting cannot keep a `queue:work` running, and tying a screen's response time to someone else's SMTP is a bad trade. What is stored is exactly what went out, so changing a template does not rewrite history.
- **Certificates are issued by a listener, not by a service call.** `CertificateService` has to ask `ProgressService` whether the course is finished, so calling it from inside `ProgressService` would be a cycle. `CourseProgressAdvanced` is dispatched wherever progress can change — completing a class, publishing a correction — and `IssueCertificateIfEarned` reacts. Add a new way to advance and you dispatch the event, not another call to the certificate code.
- **A support ticket hangs off the course, and its state comes from who wrote last.** The teacher who gives the course answers it — a doubt about a class has no reason to travel through people who did not teach it. Student writes → `open`; teacher answers → `answered`. Closed takes no more messages: reopening with a follow-up buries the last answer, and opening a new one reads better for whoever handles it.
- **Publishing an announcement and emailing it are two things.** The board always keeps it; the mail goes out only if the teacher ticks the box, and `notified_at` makes that once-only, so fixing a typo does not fill anyone's inbox again.
- **Issuing a certificate is stricter than passing a class.** To move on, submitting a task is enough — waiting for a correction would leave the student blocked by someone else. The certificate asserts the course was *approved*, so it also requires every task graded, approved **and published**.

## Known gaps

- **`/ayuda` still renders `placeholder.blade.php`.** The rest of the classroom is built: catalogue, enrolment, class screen, quiz, task submission, progress, certificates and search.
- **Nothing has been sent for real yet.** `MAIL_MAILER=log` locally, and the server has no SMTP configured and no cron line, so the queue fills and never drains — see `docs/DEPLOY.md`.
- **Registration is open but gated.** Anyone can create an account at `/registro`; it is always a student, always unverified, and the classroom sits behind `verified`. Enrolment still needs a teacher's approval, which is what makes an open sign-up safe. Accounts created from the panel default to verified — the institution is vouching for them.
- **Tests use sqlite in memory** (`phpunit.xml`). Never point them at mysql: `DB_HOST` would come from `.env`, and `RefreshDatabase` would drop tables on whatever server that names.
- **No CI**: `.github/` is gitignored.

## Formatting

Use Laravel Pint for code style (`php artisan pint`). The project follows PSR-12 standards: 4-space indentation, descriptive naming, no trailing whitespace. IDE should be configured to format on save using Pint rules.
