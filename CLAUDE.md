# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

A gym management web app (member management, class schedules, membership tracking) for a
conventional gym owner going digital. Stack: Laravel 13 (PHP ^8.3), Livewire 4, Tailwind CSS 4,
Spatie Laravel-Permission 8.x, MySQL.

**Current state**: data layer, auth (including public self-registration, not just seeded/
admin-created accounts), role middleware, and the Admin-facing feature set are built. Superadmin
-only features (manage Admin accounts, CRUD `tutorials`/`self_workouts`) and Member-facing
features (membership status, booking, attendance, achievements, read-only tutorials/
self-workouts) are **not** built yet — logging in as `member` only reaches a placeholder page.
Treat the "Project requirements" section below as the spec for what's still missing, not as
already-implemented behavior.

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

# File uploads (KTP photos) are stored on the `public` disk — needs the symlink once per
# environment (already done on this machine, but required after a fresh clone):
php artisan storage:link

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

Custom migrations live under `database/migrations/2026_07_06_06000*` (plus later additive ones:
`add_phone_to_users_table`, `create_personal_trainer_packages_table`,
`add_personal_trainer_package_id_to_membership_plans_table`) and were ordered/renamed by hand
(not their natural `make:migration` timestamps) because several tables have FK dependencies on
each other — if you add a migration that references one of these tables, make sure its
timestamp sorts after the table it depends on:

```
membership_plans -> personal_trainer_packages (nullable)
members          -> users, membership_plans
schedules
bookings         -> members, schedules
attendances      -> members, bookings (nullable)
achievements     -> members
tutorials        -> users (as dibuat_oleh_superadmin)
self_workouts    -> users (as dibuat_oleh_superadmin)
personal_trainer_packages
```

Notes on non-obvious columns:
- `tutorials.dibuat_oleh_superadmin` / `self_workouts.dibuat_oleh_superadmin` are FKs to
  `users.id` (the admin who authored the content), not booleans — modeled via a
  `superadmin()` belongsTo on both `Tutorial` and `SelfWorkout`.
- `attendances.booking_id` is nullable — attendance can happen without a prior booking
  (e.g. walk-in).
- `members.status` and `bookings.status` are DB `enum` columns (see the respective
  migrations for the allowed values), not free-text strings. `members.status` includes
  `non_member` (default) alongside `active`/`inactive`/`expired` — see "Self-registration" below.
- `users.phone` was added later (additive migration) to support the "kontak" field on the
  Admin member CRUD — it didn't exist in the original Laravel scaffold.
- `members.nik` (16-digit Indonesian ID number, unique, nullable), `members.alamat` (text,
  nullable), `members.ktp_path` (nullable path on the `public` disk) were added later for
  identity/KYC data capture in the Admin member CRUD. All three are optional — a member can be
  created without any of them.
- `membership_plans.personal_trainer_package_id` is a nullable FK to
  `personal_trainer_packages` — a plan may optionally bundle a PT package. There is no expiry
  interaction between the two: the membership's own `tanggal_expired` is still purely
  `tanggal_gabung + durasi_bulan` months; `personal_trainer_packages.masa_berlaku_hari` (nullable
  days) is not currently tracked anywhere on `members` — it's descriptive/catalog-only for now
  (see "Still to build").
- `members.membership_plan_id` and `members.tanggal_expired` are **nullable** (loosened from
  their original NOT NULL via a later migration, dropping/re-adding the FK with raw
  `DB::statement` `ALTER TABLE ... MODIFY` since `doctrine/dbal` isn't installed and
  `Blueprint::change()` needs it) — a self-registered member has neither until an
  Admin/Superadmin assigns a plan. See "Self-registration" below.

**`APP_URL` must match the actual host:port you browse the app on** (currently
`http://127.0.0.1:8000`, matching `php artisan serve`'s default). Some URL generation (e.g.
`Storage::disk('public')->url()` for KTP photos, see `config/filesystems.php`'s `public.url`)
is built from `config('app.url')` rather than the current request, so a mismatched `APP_URL`
silently produces broken links (missing port, wrong host) even though page-to-page navigation
still works fine (that part uses the request's actual host). If you change the port Laravel
runs on, update `APP_URL` too.

Role/permission tables (`roles`, `permissions`, `model_has_roles`, etc.) come from
`spatie/laravel-permission`; `User` uses the `HasRoles` trait. `config/permission.php` is
published and uses the default `web` guard. The `role` middleware alias
(`Spatie\Permission\Middleware\RoleMiddleware`) is registered in `bootstrap/app.php`.

### Auth & role-based routing

Accounts are created three ways: the `SuperadminSeeder` (one default superadmin), an
Admin/Superadmin creating a Member directly through "Kelola Member" (`admin.members` — still
supported, e.g. for enrolling someone in person with a plan on the spot), or **self-registration**
(`auth.register`, public route `/register` — a person signs themselves up, no plan yet). Login is
a single Livewire page (`auth.login`, route `login`); after authenticating, users are redirected
by role: `admin`/`superadmin` → `admin.dashboard`, everyone else → `member.placeholder` (a
static "coming soon" Blade view, since Member features don't exist yet).

Route groups in `routes/web.php` are middleware-protected by role:
```
guest            -> /login, /register
auth             -> /logout, and:
  role:member          -> /member (placeholder)
  role:admin|superadmin -> /admin/* (superadmin has full Admin access, per spec)
```

**Self-registration** (`auth.register`): captures the same identity fields as the Admin member
form (nama, email, phone, NIK, alamat, KTP — all but nama/email/phone stay optional) plus a
password the user sets themselves (the Admin-created flow instead auto-generates one, since
there's no email/invite system). On submit it creates the `User` + `Member` (role `member`,
`membership_plan_id` and `tanggal_expired` both null, `status = 'non_member'`), logs the user in,
and redirects to `member.placeholder`. There is **no email verification or approval gate** — the
account is immediately usable (for whatever little Member area exists), just without an active
plan.

### Livewire 4 component conventions (important — differs from the original plan)

Livewire 4's actual component resolution does **not** use an `app/Livewire/{Role}` namespace —
that was the original spec's assumption before Livewire 4 was installed and inspected. The real
convention (see `config/livewire.php` → `component_locations`) is view-based: single-file
components live under `resources/views/components/{name}.blade.php` and are referenced by
dot-notation (e.g. `admin.dashboard` resolves to `resources/views/components/admin/dashboard.blade.php`).
**Follow this actual convention, not the `app/Livewire` one, for any new component.**

- Organize by role via subfolders: `resources/views/components/admin/*`, `.../auth/*` (login,
  register — public/guest pages), and (when built) `.../superadmin/*`, `.../member/*`.
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
- **`tanggal_expired`** is derived as `tanggal_gabung + membership_plan.durasi_bulan` months
  (recomputed on every save) when a plan is selected — it's never a free-typed field in the UI.
  With no plan selected it's `null` (see "non-member" below).
- **Assigning a plan to a `non_member`** (`admin.members`): the "Paket Membership" `<select>`
  uses `wire:model.live` (not the usual deferred `wire:model`) specifically so
  `updatedMembershipPlanId()` can fire immediately and flip `status` from `non_member` to
  `active` the moment a plan is picked — without `.live` this would only happen on the next
  request (i.e. on save, one step later than the user sees it happen). The `status` `<select>`
  stays manually overridable after. Self-registered members land in the members list with a
  "Non-Member" badge and no plan/expiry columns (rendered as `—`); the dashboard's "Belum Ambil
  Paket" tile counts them so Admin/Superadmin can spot new signups needing a plan.
- **Revenue** (dashboard + `admin.revenue-report`) is calculated as the sum of
  `membership_plans.harga` for members whose `tanggal_gabung` falls in the target month. There
  is no separate "setup fee" concept in the schema — the original spec mentioned "setup fee +
  subscription" but only plan price exists, so that's the whole calculation for now.
- **Membership plan "quick templates"** (`admin.membership-plans`): a `$template` select
  (`monthly`, `combo_3_pt8`, `combo_6_pt12`, `custom`) auto-fills `nama`/`durasi_bulan`/
  `personal_trainer_package_id` via `updatedTemplate()` — it's a one-time convenience fill, not a
  locked/linked state; every field stays manually editable after applying a template. The combo
  templates call `PersonalTrainerPackage::firstOrCreate()` so the referenced PT package always
  exists (e.g. picking "3 Bulan + PT 8 Sesi" ensures an "8 sesi / 40 hari" PT package row exists,
  reusing it if already created). Duration uses the same preset-dropdown-plus-custom-reveal
  pattern (1–24 months, or "custom" reveals a free `durasi_bulan` number input) as the PT
  package's `sesiPreset` field in `admin.personal-trainer-packages` — replicate this pattern for
  any other preset-with-custom-escape-hatch field.
- **Personal Trainer packages** (`admin.personal-trainer-packages`) are a standalone catalog
  (session count, optional validity in days, price), independently manageable, and only
  *optionally* attached to a membership plan — deleting a plan does not delete its PT package
  (and vice versa isn't possible: `membership_plans.personal_trainer_package_id` is
  `nullOnDelete`).
- **KTP upload** (`admin.members`) uses Livewire's `WithFileUploads` trait (`wire:model` on a
  plain `<input type="file">`, no `enctype` needed — Livewire uploads it via its own AJAX
  endpoint before the form even submits). Submitting while the upload is still in flight would
  silently save the member without a KTP, so the Simpan button is disabled via
  `wire:loading.attr="disabled" wire:target="ktp"` while `$ktp` is uploading. Files are stored
  on the `public` disk under `ktp/`; `Member::ktpUrl()` builds the public URL (added as a plain
  method, not a Blade-facade call, because a Livewire SFC's leading `use` imports aren't
  guaranteed to be in scope in the compiled Blade-template half of the same file — resolve
  Storage paths in PHP/model code and pass plain strings/URLs into the template, don't call
  `Storage::` directly from the Blade markup section of an SFC). Old files are deleted from disk
  when replaced or when the member is deleted (see `save()`/`delete()`/`removeKtp()`).

### UI conventions (established visual style — match this for new pages)

- Page header: `<h1 class="text-2xl font-semibold tracking-tight text-gray-900">` + a
  `<p class="mt-1 text-sm text-gray-500">` subtitle, in a `flex flex-wrap items-center
  justify-between gap-4` row with the primary action button.
- Cards/table containers: `rounded-2xl bg-white shadow-sm ring-1 ring-gray-100` (not
  `rounded-xl` — that was the pre-polish style, now only `rounded-lg` for inputs/small buttons).
- Tables: `bg-gray-50/80` header with `text-xs font-semibold uppercase tracking-wide
  text-gray-500` labels, `divide-y divide-gray-100` rows with `hover:bg-gray-50/60
  transition-colors`, `px-5 py-3.5` cell padding (not `px-4 py-3`).
- Buttons: primary = `rounded-lg bg-indigo-600 ... shadow-sm transition-colors
  hover:bg-indigo-500` (hover goes to `-500`, not `-700`); secondary/cancel =
  `text-gray-600 hover:bg-gray-100`; destructive = `text-red-600 hover:text-red-700`.
- Modals: `bg-gray-900/50 backdrop-blur-sm` overlay with `wire:click.self="$set('showModal',
  false)"` (click-outside-to-close), panel is `animate-modal-in rounded-2xl ... ring-1
  ring-black/5` — the `animate-modal-in` keyframe class lives in `resources/css/app.css`.
- Sidebar active nav item: solid `bg-indigo-600 text-white`, not the earlier plain
  `bg-gray-800`.
- Font is **Plus Jakarta Sans** (`--font-sans` in `resources/css/app.css`, loaded via the
  Bunny Fonts helper in `vite.config.js` — `bunny('Plus Jakarta Sans', { weights: [...] })`).
  Changing the font requires editing both places (the `@theme` CSS var and the Vite font
  loader) plus a Vite dev-server restart — `vite.config.js` changes aren't hot-reloaded.
- Wide/scrollable tables (`resources/views/components/admin/*`) all wrap the `<table>` in
  **two** nested divs: an outer one with `.table-scroll-wrap` (fades the right edge via a
  `::after` gradient in `app.css`, and hides the native scrollbar — the fade is the only
  scroll affordance now, a visible OS scrollbar there previously looked like a rendering
  glitch) and an inner `overflow-x-auto` (the actual scroll container). Both divs are
  required — a table missing the inner one won't scroll at all on narrow screens (this
  happened twice already when new table components were added; double check it).
- `layouts/admin.blade.php`'s `<aside>` switches from `fixed` (mobile, an overlay) to
  `sm:static` (desktop, in normal flow) — on desktop this means it no longer stretches to
  the viewport via `inset-y-0` (that only applies to positioned elements), so its parent
  flex row has `sm:min-h-screen` to force full-height and let `align-items: stretch`
  (flex default) stretch `<aside>` to match. Without that class the sidebar's dark
  background falls short of the page height on any route where the main content is shorter
  than the nav+profile block, leaving a visibly "cut off" sidebar.

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
- Personal Trainer session tracking against `masa_berlaku_hari` (e.g. a per-member "PT sessions
  expire on X date" derived field) isn't wired up anywhere yet — the PT package is currently
  catalog/descriptive only.

**Explicit non-goals for now**: payment gateway integration, WhatsApp notifications.
