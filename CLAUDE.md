# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

A gym management web app (member management, class schedules, membership tracking) for a
conventional gym owner going digital. Stack: Laravel 13 (PHP ^8.3), Livewire 4, Tailwind CSS 4,
Spatie Laravel-Permission 8.x, MySQL.

**Current state**: data layer, auth, role middleware, and the Admin-facing feature set are
built. Superadmin-only features (manage Admin accounts, CRUD `tutorials`/`self_workouts`) and
Member-facing features (membership status, booking, attendance, achievements, read-only
tutorials/self-workouts) are **not** built yet — logging in as `member` only reaches a
placeholder page. Treat the "Project requirements" section below as the spec for what's still
missing, not as already-implemented behavior.

## Commands

```bash
# Install PHP deps, copy .env, generate key, migrate, install/build JS — one-shot setup
composer run setup

# Local dev: runs `php artisan serve`, queue listener, `php artisan pail` (logs), and
# `npm run dev` (Vite) concurrently — this is the normal way to run the app locally
composer run dev

# Frontend only
npm run dev      # Vite dev server
npm run build    # production build

# Tests (Pest-style PHPUnit via artisan)
composer test              # clears config cache, then runs the full suite
php artisan test
php artisan test --filter=TestName      # single test
php artisan test tests/Feature/SomeTest.php   # single file

# DB (MySQL, database `fitnessapp`, local Laragon — root, no password)
php artisan migrate
php artisan migrate:fresh --seed --force   # drop, re-run everything, reseed roles+superadmin

# Seed roles (superadmin/admin/member) + default superadmin account
php artisan db:seed --force
# Default superadmin login (overridable via SUPERADMIN_EMAIL/SUPERADMIN_PASSWORD/SUPERADMIN_NAME env vars):
#   superadmin@fitnessapp.test / password

# Scaffolding
php artisan make:migration create_x_table --create=x
php artisan make:model X
```

## Architecture

### Model conventions (Laravel 13 style — follow for any new model)

This codebase uses the newer PHP-attribute/method style rather than the classic protected
properties:

- `#[Fillable([...])]` and `#[Hidden([...])]` class attributes instead of `protected $fillable`
  / `protected $hidden` (see `app/Models/User.php`).
- `protected function casts(): array` method instead of `protected $casts`.

### Domain schema

Custom migrations live under `database/migrations/2026_07_06_06000*` (plus one later
`add_phone_to_users_table`) and were ordered/renamed by hand (not their natural
`make:migration` timestamps) because several tables have FK dependencies on each other — if you
add a migration that references one of these tables, make sure its timestamp sorts after the
table it depends on:

```
membership_plans
members          -> users, membership_plans
schedules
bookings         -> members, schedules
attendances      -> members, bookings (nullable)
achievements     -> members
tutorials        -> users (as dibuat_oleh_superadmin)
self_workouts    -> users (as dibuat_oleh_superadmin)
```

Notes on non-obvious columns:
- `tutorials.dibuat_oleh_superadmin` / `self_workouts.dibuat_oleh_superadmin` are FKs to
  `users.id` (the admin who authored the content), not booleans — modeled via a
  `superadmin()` belongsTo on both `Tutorial` and `SelfWorkout`.
- `attendances.booking_id` is nullable — attendance can happen without a prior booking
  (e.g. walk-in).
- `members.status` and `bookings.status` are DB `enum` columns (see the respective
  migrations for the allowed values), not free-text strings.
- `users.phone` was added later (additive migration) to support the "kontak" field on the
  Admin member CRUD — it didn't exist in the original Laravel scaffold.

Role/permission tables (`roles`, `permissions`, `model_has_roles`, etc.) come from
`spatie/laravel-permission`; `User` uses the `HasRoles` trait. `config/permission.php` is
published and uses the default `web` guard. The `role` middleware alias
(`Spatie\Permission\Middleware\RoleMiddleware`) is registered in `bootstrap/app.php`.

### Auth & role-based routing

There's no registration flow — accounts are created either by the `SuperadminSeeder` (one
default superadmin) or by an Admin/Superadmin creating a Member through the "Kelola Member" UI
(which creates the `User` + `Member` row together and assigns the `member` role). Login is a
single Livewire page (`auth.login`, route `login`); after authenticating, users are redirected
by role: `admin`/`superadmin` → `admin.dashboard`, everyone else → `member.placeholder` (a
static "coming soon" Blade view, since Member features don't exist yet).

Route groups in `routes/web.php` are middleware-protected by role:
```
guest            -> /login
auth             -> /logout, and:
  role:member          -> /member (placeholder)
  role:admin|superadmin -> /admin/* (superadmin has full Admin access, per spec)
```

### Livewire 4 component conventions (important — differs from the original plan)

Livewire 4's actual component resolution does **not** use an `app/Livewire/{Role}` namespace —
that was the original spec's assumption before Livewire 4 was installed and inspected. The real
convention (see `config/livewire.php` → `component_locations`) is view-based: single-file
components live under `resources/views/components/{name}.blade.php` and are referenced by
dot-notation (e.g. `admin.dashboard` resolves to `resources/views/components/admin/dashboard.blade.php`).
**Follow this actual convention, not the `app/Livewire` one, for any new component.**

- Organize by role via subfolders: `resources/views/components/admin/*`, and (when built)
  `.../superadmin/*`, `.../member/*`.
- Full-page components are wired up with `Route::livewire('/uri', 'admin.dashboard')` (a macro
  Livewire registers), not a controller.
- Every admin page component sets `#[Layout('layouts::admin')]` (the `layouts` component
  namespace maps to `resources/views/layouts/`). `layouts::admin` (sidebar shell) wraps
  `layouts::app` (bare HTML shell with `@livewireStyles`/`@livewireScripts`, published via
  `php artisan livewire:layout`) — don't add a third layout layer.
- Alpine.js is bundled with Livewire 4 — don't add `alpinejs` as an npm dependency. The mobile
  sidebar toggle in `layouts/admin.blade.php` uses a plain `x-data="{ sidebarOpen: false }"`.
- Each Admin CRUD component (`members`, `membership-plans`, `schedules`) follows the same
  "list + modal form" pattern in one file: `public bool $showModal`, `with()` returns the
  paginated collection, `create()`/`edit($id)` open the modal, `save()` does
  `validate()` + `updateOrCreate()`, `delete($id)` uses `wire:confirm` client-side confirmation
  (no separate confirmation modal). Follow this pattern for Superadmin/Member CRUD components
  too, for consistency.

### Admin feature notes

- **Member creation** (`admin.members`) creates a `User` (name/email/phone) + `Member` row
  together, assigns the `member` role, and generates a random password
  (`Str::password(10)`) shown **once** in a dismissible banner after creation — there's no
  email/invite system, so the admin must relay it manually.
- **`tanggal_expired`** is always derived as `tanggal_gabung + membership_plan.durasi_bulan`
  months (recomputed on every save) — it's never a free-typed field in the UI.
- **Revenue** (dashboard + `admin.revenue-report`) is calculated as the sum of
  `membership_plans.harga` for members whose `tanggal_gabung` falls in the target month. There
  is no separate "setup fee" concept in the schema — the original spec mentioned "setup fee +
  subscription" but only plan price exists, so that's the whole calculation for now.

## Project requirements (product spec — remaining work)

**Roles** (3, via Spatie): `superadmin`, `admin`, `member` — seeded, middleware in place.
- Superadmin: everything Admin has (done), **plus** (not built): manage Admin accounts (CRUD +
  role assignment), CRUD `tutorials`/`self_workouts` content.
- Admin: dashboard, CRUD `members`/`membership_plans`/`schedules`, revenue report — **done**.
- Member (not built): view own membership status/days remaining, book schedules
  (capacity-validated), view attendance history and achievements, read-only
  `tutorials`/`self_workouts`.

**Still to build**:
- Superadmin Livewire components (`resources/views/components/superadmin/*`) + route group
  (`role:superadmin`) + sidebar section.
- Member Livewire components (`resources/views/components/member/*`), replacing the
  `member.placeholder` route, + sidebar section.
- Scheduled command (`php artisan schedule`) to auto-expire memberships past
  `tanggal_expired`.
- Booking capacity validation (reject bookings once a schedule's `kapasitas` is reached) — this
  lives in the Member booking flow, not yet built.

**Explicit non-goals for now**: payment gateway integration, WhatsApp notifications.
