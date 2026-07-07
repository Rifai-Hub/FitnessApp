# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

A gym management web app (member management, class schedules, membership tracking) for a
conventional gym owner going digital. Stack: Laravel 13 (PHP ^8.3), Livewire 4, Tailwind CSS 4,
Spatie Laravel-Permission 8.x, MySQL.

**Current state**: the data layer (migrations + models) is built and migrated. Nothing above
that layer exists yet — no routes beyond the default welcome page, no controllers, no Livewire
components, no middleware, no seeders beyond the stock `DatabaseSeeder`. Treat the "Project
requirements" section below as the spec to build toward, not as already-implemented behavior.

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
php artisan migrate:fresh --force   # drop + re-run everything, use to sanity-check the schema

# Scaffolding
php artisan make:migration create_x_table --create=x
php artisan make:model X
php artisan make:livewire Admin/X    # Livewire 4 component, see frontend conventions below
```

## Architecture

### Model conventions (Laravel 13 style — follow for any new model)

This codebase uses the newer PHP-attribute/method style rather than the classic protected
properties:

- `#[Fillable([...])]` and `#[Hidden([...])]` class attributes instead of `protected $fillable`
  / `protected $hidden` (see `app/Models/User.php`).
- `protected function casts(): array` method instead of `protected $casts`.

### Domain schema

Custom migrations live under `database/migrations/2026_07_06_06000*` and were ordered/renamed
by hand (not their natural `make:migration` timestamps) because several tables have FK
dependencies on each other — if you add a migration that references one of these tables,
make sure its timestamp sorts after the table it depends on:

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

Role/permission tables (`roles`, `permissions`, `model_has_roles`, etc.) come from
`spatie/laravel-permission`; `User` uses the `HasRoles` trait. `config/permission.php` is
published and uses the default `web` guard.

### Project requirements (product spec — not yet implemented)

**Roles** (3, via Spatie): `superadmin`, `admin`, `member`.
- Superadmin: everything Admin has, plus manage Admin accounts (CRUD + role assignment),
  and CRUD `tutorials`/`self_workouts` content.
- Admin: dashboard (active members, members expiring this week, month's revenue), CRUD
  `members`, `membership_plans`, `schedules`; view revenue reports.
- Member: view own membership status/days remaining, book schedules (capacity-validated),
  view attendance history and achievements, read-only `tutorials`/`self_workouts`.

**Frontend conventions to follow when building this out**:
- All interactive UI as Livewire 4 components, not controller+static-blade — prefer Livewire 4
  single-file components where practical.
- Organize components by role: `app/Livewire/Superadmin`, `app/Livewire/Admin`,
  `app/Livewire/Member`.
- Tailwind, mobile-first (members mainly use phones); sidebar/nav must switch based on the
  logged-in user's role.
- Livewire 4 bundles Alpine.js — do not add `alpinejs` as a separate npm dependency.

**Still to build**:
- Role-checking middleware per route group (superadmin/admin/member).
- Seeder creating the 3 roles + one default superadmin account (first-run setup).
- Scheduled command (`php artisan schedule`) to auto-expire memberships past
  `tanggal_expired`.
- Booking capacity validation (reject bookings once a schedule is full).

**Explicit non-goals for now**: payment gateway integration, WhatsApp notifications. MVP is
basic CRUD + role checks first.
