# SaaS Admin Dashboard — Build Plan

**Status:** Not started
**Purpose:** reference doc so any future session (or me) can pick this up without re-deriving context.

## Why this exists

The app currently has exactly one dashboard: `/dashboard` and everything under `dashboard/portfolio/*`, all scoped to `Auth::user()->portfolio` (see [resources/views/pages/⚡dashboard-home.blade.php](../resources/views/pages/⚡dashboard-home.blade.php) and [routes/portfolio-dashboard.php](../routes/portfolio-dashboard.php)). Despite the "portfolio upgrade to dynamic saas" commit, there is no platform-owner view — no way to see all users/portfolios, no role system, no billing/plan model. Every user who signs up gets exactly one portfolio (`portfolios.user_id` is `unique()`, strict 1:1 — see [database/migrations/2026_02_21_000200_create_portfolios_table.php](../database/migrations/2026_02_21_000200_create_portfolios_table.php)).

This doc plans a **platform admin dashboard**: a separate area only the site owner (you) can reach, to see and manage all tenants (users + their portfolios), platform-wide stats, and (later) billing.

## Current state (confirmed by codebase audit, 2026-08-11)

- No `role`/`is_admin` column on `users` table or model ([app/Models/User.php](../app/Models/User.php)).
- No admin middleware in [app/Http/Middleware/](../app/Http/Middleware/) (only `EnforceCanonicalHost.php`).
- No admin route group/prefix anywhere in `routes/`.
- No admin views outside `auth/`, `settings/`, `portfolio/`.
- No subscriptions/plans/roles tables in `database/migrations/`.
- No admin panel package installed (no Filament/Nova/Backpack in `composer.json`).
- Stack in use: Laravel + Livewire (Volt-style single-file components under `resources/views/pages/`, registered via `Route::livewire(...)` with the `pages::` view namespace), Flux UI components (`flux:*`), Tailwind.

## Scope decisions to confirm before building

These are genuine open questions — answer them (or leave defaults) before implementation starts:

1. **Who is "admin"?** Single owner (you) only, or multiple admin users? → Default: start with a simple `is_admin` boolean on `users`, not a full roles/permissions package. Upgrade later only if a second admin is actually needed.
2. **Billing/plans?** Is monetization planned soon, or is this just "let me see all my users for now"? → Default: build the admin dashboard WITHOUT billing first (Phase 1–3 below). Billing is Phase 4, separate effort (Stripe/Cashier), only scope it once actually needed.
3. **Impersonation?** Do you want "log in as this user" for support purposes? → Default: yes, include it (common admin need), but flag it as its own step so it can be skipped.
4. **Multiple portfolios per user, ever?** Current schema is strict 1:1. If SaaS growth means a user could eventually own multiple portfolios/sites, that's a bigger migration (drop the `unique()` on `portfolios.user_id`, adjust every `Auth::user()->portfolio` call site). → Default: NOT in scope here; note it as a future consideration only, don't build for it speculatively.

## Build plan

### Phase 0 — Foundation (roles & access)

1. Migration: add `is_admin` boolean (default `false`) to `users` table.
2. Update `app/Models/User.php`: add `is_admin` to `$fillable`/casts (`'is_admin' => 'boolean'`), add a small `isAdmin(): bool` helper.
3. New middleware `app/Http/Middleware/EnsureUserIsAdmin.php` — aborts 403 if `! Auth::user()->isAdmin()`. Register alias in `bootstrap/app.php` (alongside existing middleware config).
4. Manually flip your own user's `is_admin` to `true` via tinker/seeder — no self-service "become admin" UI, ever.
5. New route file `routes/admin.php`, required from `routes/web.php` (same pattern as `settings.php`/`portfolio-dashboard.php`), wrapped in `Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(...)`.

### Phase 1 — Admin shell + platform stats overview

6. New Livewire page `resources/views/pages/admin/⚡dashboard.blade.php`, route `admin.dashboard` at `/admin`.
   - Reuse the existing `dash-card`/`stat-grid` CSS classes and Flux components already used in the portfolio dashboard, for visual consistency.
   - Stats to show: total users, total portfolios, total published posts (all tenants), total contact messages (all tenants, last 7 days + total), total visits (all tenants, last 7 days + total), new signups (last 7 days).
   - Recent signups list (last 5 users, name/email/created_at).
   - Recent contact messages across ALL portfolios (last 5, name/portfolio/time) — reuse the message-detail-modal pattern already built in the portfolio dashboard ([resources/views/pages/⚡dashboard-home.blade.php](../resources/views/pages/⚡dashboard-home.blade.php)) rather than reinventing it.
7. Add a nav entry point to `/admin` — visible only when `Auth::user()->isAdmin()` — likely in whatever layout/sidebar component the portfolio dashboard uses (check `resources/views/components/layouts/` or wherever the sidebar in the screenshot you showed me lives).

### Phase 2 — Tenant management (users & portfolios)

8. New page `resources/views/pages/admin/⚡users.blade.php`, route `admin.users`.
   - Paginated table of all users: name, email, portfolio slug (if any), signup date, `is_admin` flag, published post count, message count, last activity.
   - Search by name/email.
   - Row action: "View portfolio" (opens the live site), "View as dashboard" (see impersonation below), "Delete user" (soft-delete? — decide once you confirm this is wanted at all; deleting a user's account is high-blast-radius, needs explicit confirm modal same as message delete).
9. New page `resources/views/pages/admin/⚡portfolios.blade.php`, route `admin.portfolios` (could be merged into `users` page if you'd rather have one screen — flag as an option, default to separate for now since portfolios carry their own stats).
   - Table of all portfolios: slug, owner, theme, project/post/testimonial counts, total visits, total messages, created date.
   - Link into each portfolio's live site and into "view as" that tenant's dashboard.

### Phase 3 — Impersonation ("view as user")

10. Add `impersonate()`/`stopImpersonating()` to a small `AdminController` or a trait — store the admin's original user id in session, swap `Auth::login()` to the target user, add a persistent banner ("Viewing as {user} — [Return to admin]") shown in the main layout when impersonating.
11. Guard: never allow impersonating another `is_admin` user (avoids privilege confusion); always log impersonation start/stop (who, whom, when) — even a simple `Log::info()` call is enough at this stage, no need for a dedicated audit table yet.

### Phase 4 — Billing/plans (future, only when actually needed)

Not scoped in detail here — revisit this doc and expand this section once monetization is imminent. At minimum it will need: a `plans` table, a `subscriptions` table (or Laravel Cashier + Stripe), gating logic (e.g. free plan = 1 portfolio, paid = custom domain / more posts / etc.), and an admin view of subscription status per tenant. Don't build ahead of the actual pricing model — the shape of this depends entirely on what plans/limits get decided later.

## Explicitly out of scope (unless re-prioritized later)

- Multi-portfolio-per-user support (schema is 1:1 today, changing that is a separate, larger migration).
- Full roles/permissions package (spatie/laravel-permission etc.) — a boolean `is_admin` is enough until there's a second admin with a different permission set.
- Admin ability to edit a tenant's portfolio content directly (impersonation covers this need without extra UI).
- Audit-log table / detailed activity history — start with simple `Log::info()` calls, upgrade only if actually needed.

## Suggested build order (if picked up fresh)

1. Phase 0 (foundation) — small, everything else depends on it.
2. Phase 1 (admin dashboard shell + stats) — gives immediate visible value, low risk.
3. Phase 2 (tenant tables) — the actual "see all my users" ask.
4. Phase 3 (impersonation) — nice-to-have, do once 1–2 are solid.
5. Phase 4 (billing) — only when there's an actual pricing plan to encode.
