# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project status

AAMEVI is an e-learning platform (React + NestJS + PostgreSQL + Docker). **The repo is currently a scaffold**, not a working app: `backend/src` contains only `app.module.ts` / `app.controller.ts` / `app.service.ts` (a hello + `/health` endpoint), `frontend/src/App.tsx` is a single placeholder route, and `database/migrations` and `database/seeds` are empty. None of the business modules exist yet.

The authoritative spec for what gets built is `docs/PLAN_ARQUITECTONICO.md` (Spanish, 825 lines) — full normalized SQL schema, per-module component breakdown, and the main user flows. **Read the relevant section of that doc before implementing a feature**; the entity definitions, enum values, and unique constraints there are the contract. `README.md` summarizes the same flows at a higher level.

Project docs, commit messages, and UI copy are in Spanish. Code identifiers are in English.

## Commands

Everything runs through Docker Compose (`postgres` + `backend` + `frontend`):

```bash
docker-compose up -d
docker-compose logs -f backend
docker-compose exec backend npm run migrate:run
```

Ports: frontend http://localhost:3001 (container 3000), backend http://localhost:3000, Swagger http://localhost:3000/api/docs, Postgres 5432 (`postgres:postgres@localhost:5432/aamevi_db`).

Backend (`cd backend`):

| Task | Command |
|---|---|
| Dev server (watch) | `npm run dev` |
| Build | `npm run build` |
| Lint (autofix) | `npm run lint` |
| All unit tests | `npm test` |
| **Single test** | `npm test -- path/to/file.spec.ts` or `npm test -- -t "test name"` |
| E2E | `npm run test:e2e` (needs `test/jest-e2e.json`, not yet created) |
| Coverage | `npm run test:cov` |
| Migration (new, empty) | `npm run migrate:create -- database/migrations/Name` |
| Migration (from entities) | `npm run migrate:generate -- -d <datasource> database/migrations/Name` |
| Apply / revert | `npm run migrate:run` / `npm run migrate:revert` |
| Seed | `npm run seed` (expects `src/database/seeds/seed.ts`) |

Jest `rootDir` is `src` and `testRegex` is `.*\.spec\.ts$` — colocate unit tests next to the code they test.

Frontend (`cd frontend`):

| Task | Command |
|---|---|
| Dev server | `npm run dev` |
| Build (typechecks first) | `npm run build` |
| Typecheck only | `npm run type-check` |
| Lint | `npm run lint` (`--max-warnings 0`) |
| Tests | `npm test` (vitest) |
| **Single test** | `npm test -- src/foo.test.tsx` or `npm test -- -t "name"` |

## Architecture

**Backend** — NestJS 10 + TypeORM 0.3 + Postgres. Business logic lives in feature modules under `src/modules/` (`auth`, `users`, `courses`, `quiz`, `tasks`, `notifications`, `storage`, `certificates`, `reports`), with cross-cutting guards/pipes/decorators in `src/common/`, config in `src/config/`, and migrations/seeds in `src/database/`. New modules must be registered in the `imports` array of `app.module.ts` (there's a placeholder comment block marking the spot).

`main.ts` applies a global `ValidationPipe` with `whitelist: true, forbidNonWhitelisted: true, transform: true` — every request body needs a `class-validator`-decorated DTO or the request is rejected. Swagger is generated from decorators, so annotate controllers/DTOs with `@nestjs/swagger`.

TypeORM runs with `synchronize: false`. Schema changes **only** happen via migrations in `database/migrations`. Note the entity/migration globs in `app.module.ts` point at `dist/**/*.entity.js` and `dist/database/migrations/*.js` — compiled output, so a build must run before migrations resolve.

Path aliases (backend `tsconfig.json`): `@/*`, `@modules/*`, `@common/*`, `@config/*`, `@database/*`. Backend TS is strict (`noImplicitAny`, `strictNullChecks`, `noUnusedLocals`, `noUnusedParameters`, `noImplicitReturns`).

**Frontend** — Vite + React 18 + TS, Tailwind, React Router, TanStack Query v5 for server state, Zustand for client state, React Hook Form + Zod (via `@hookform/resolvers`) for forms, axios for HTTP, react-hot-toast for notifications. `@` aliases to `./src`. Intended layout: `pages/`, `components/`, `hooks/`, `services/` (API calls), `types/`, `styles/`.

**Visual design** — the frontend deliberately mirrors the institutional site **https://www.aamevi.ar** (its `css/global.css` and `images/aamevi.svg` are the source of truth). Tokens live in `tailwind.config.js`: `primary` = `#00b8b3` (institutional teal), `accent` = `#f46707` (orange, used for nav hover and CTAs), `ink` `#333333`, `surface` `#ececec` (page background), plus a `pillar.*` ramp taken from the six colors of the logo. Typography is Montserrat, imported in `index.css`. Reusable classes (`.container-site`, `.btn`, `.field`, `.section-title`, `.nav-link`) are declared in `@layer components` — prefer them over re-deriving the styling inline.

Signature elements to preserve when adding pages: the 6px teal bottom border on the header and on page heroes, uppercase nav with orange hover, the translucent orange dropdown submenu, the asymmetric search input (`rounded-br-[15px]`, teal bottom/right borders only), and the dark `#333333` footer. `PageHero` and `Section` (`src/components/ui/`) already encapsulate the section layout.

Note the parent site sets `body { font-size: 62.5% }` and sizes everything in `em`. This project does **not** copy that cascade — it keeps Tailwind's 16px rem base and translates the sizes, so don't port `em` values from `global.css` literally.

**Domain model** (see `docs/PLAN_ARQUITECTONICO.md` §2 for full DDL). All PKs are UUIDs. `users` is the auth base table; `students` and `teachers` are 1:1 extensions keyed on `users.id`. Content hierarchy is `courses → modules → classes → class_content`. Enrollment (`course_enrollments`) is a state machine: `pending → approved/rejected → active → completed`, approved by a teacher.

The quiz subsystem is the most intricate part: a `quizzes` row configures per-class rules (`questions_per_student`, `passing_score`, `max_attempts`, `randomize_options`); each attempt draws a random subset from the class's `questions` bank, and the specific questions a student saw are recorded in `quiz_question_assignment` so attempts stay reproducible. Class progression gates on passing the quiz (`student_progress`), and completing all classes/tasks triggers certificate generation.

Emails are not sent inline — they're written to `email_queue` (typed by `email_type`) with `status`/`retry_count` for a worker to drain.

External services: Google Cloud Storage for uploads (videos, PDFs, submissions, certificate PDFs — DB stores URLs only), Google OAuth for social login, SendGrid/nodemailer for email, Google Meet links for live classes.

## Known scaffold gaps

These will bite on first run — fix them rather than working around them:

- **`docker-compose.yml` runs `npm start` for the frontend**, but `frontend/package.json` has no `start` script (it's `dev`). The frontend container won't boot as configured.
- **DB env var mismatch**: `app.module.ts` reads `DB_HOST`/`DB_PORT`/`DB_USER`/`DB_PASSWORD`/`DB_NAME`, but `.env.example` and `docker-compose.yml` only define `DATABASE_URL`. It currently works purely because the hardcoded fallbacks happen to match Compose.
- **`frontend/.env.example` uses `REACT_APP_*` prefixes** (CRA convention), but this is Vite — only `VITE_*` vars are exposed via `import.meta.env`.
- **No TypeORM DataSource file exists**, so the `migrate:*` scripts have nothing to point `-d` at. One is needed (conventionally `src/config/typeorm.config.ts` or `src/database/data-source.ts`) before migrations can run.
- `.github/workflows/` is empty — no CI.

## Formatting

Root `.prettierrc` applies to both apps: single quotes, semicolons, trailing commas `es5`, `printWidth: 100`, 2-space indent, always-parenthesized arrow params. Backend has `npm run format`; the frontend has no format script — match the same style.
