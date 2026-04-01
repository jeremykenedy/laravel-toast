# CLAUDE.md — Mandatory Instructions for Claude Code
# ============================================================
# THIS FILE IS READ AUTOMATICALLY. FOLLOW EVERY RULE EXACTLY.
# VIOLATION OF THESE RULES WILL BREAK THE APPLICATION.
# ============================================================

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Package Overview

`jeremykenedy/laravel-toast` is a multi-framework toast notification package for Laravel. It supports 3 CSS frameworks (Tailwind v4, Bootstrap 5, Bootstrap 4) x 5 frontend frameworks (Blade, Livewire, Vue, React, Svelte) = 15 rendering combinations. At runtime, exactly one CSS + one frontend framework is active, controlled by `config('ui-kit.css_framework')` and `config('ui-kit.frontend')`.

## UNDERSTAND THE CORE CONCEPT
This app supports 3 CSS frameworks × 5 frontend frameworks = 15 possible
rendering modes. But they are NEVER mixed. At runtime, the app uses exactly
ONE CSS framework and ONE frontend framework, selected by config.

## THE SINGLE MOST IMPORTANT RULE
╔══════════════════════════════════════════════════════════════════════╗
║  NEVER MIX FRAMEWORKS IN THE SAME FILE. EVER. NO EXCEPTIONS.      ║
║                                                                      ║
║  A Tailwind view has ZERO Bootstrap classes.                         ║
║  A Bootstrap 5 view has ZERO Tailwind classes.                       ║
║  A Bootstrap 4 view has ZERO Tailwind classes.                       ║
║  A Blade view has ZERO wire: directives (use Alpine.js instead).     ║
║  A Livewire view has ZERO Alpine x-data (use wire: instead).         ║
║  A Vue file has ZERO Blade {{ }} syntax.                             ║
║  A React file has ZERO Blade {{ }} syntax.                           ║
║  A Svelte file has ZERO Blade {{ }} syntax.                          ║
║                                                                      ║
║  If you catch yourself putting a Bootstrap class in a Tailwind file, ║
║  or a Tailwind class in a Bootstrap file, STOP. You are violating    ║
║  the architecture. Each file lives in a directory that determines     ║
║  its framework. Use ONLY that framework's classes.                   ║
╚══════════════════════════════════════════════════════════════════════╝

## SELF-CHECK BEFORE EVERY FILE WRITE
Before writing ANY view file, ask yourself:
1. What directory is this file in? (tailwind/blade? bootstrap5/blade? livewire?)
2. Am I using ONLY that directory's CSS framework?
3. Am I using ONLY that directory's frontend framework?
4. Do I have dark: variants on every Tailwind color class?
5. Do all x-show elements have x-cloak?

## Commands

```bash
# Install dependencies
composer install

# Run tests
./vendor/bin/pest --ci               # Full suite (191 tests)
./vendor/bin/pest --filter=ToastManager  # Filter by name
./vendor/bin/pest tests/Unit         # Unit tests only
./vendor/bin/pest tests/Feature      # Feature tests only

# Lint / format PHP
./vendor/bin/pint                    # Fix formatting
./vendor/bin/pint --test             # Check only (CI mode)
./vendor/bin/pint --dirty            # Only changed files
```

## Architecture

### Toast Lifecycle

1. **Creation** -- Toasts are created via three entry points:
   - Global helper: `toast('message', 'type', 'title', $duration, $options)`
   - Facade: `Toast::success('message')` (via `Jeremykenedy\LaravelToast\Facades\Toast`)
   - Trait: `$this->toastSuccess('message')` (via `HasToasts` trait on controllers)

2. **Storage** -- `ToastManager` (singleton) stores toasts in the Laravel session via `Session::flash()`. Each toast is an array with 22 properties (id, type, message, title, duration, position, animations, display flags, etc.). Session key is configurable (`toast.session_key`, default `toast_notifications`).

3. **Rendering** -- The `ToastServiceProvider` loads views from the directory matching the active CSS framework:
   - `resources/views/{tailwind|bootstrap5|bootstrap4}/blade/toasts.blade.php`
   - `resources/views/livewire/toast-container.blade.php`
   - `resources/js/{vue|react|svelte}/pages/ToastContainer.{vue|jsx|svelte}`

### Key Classes

| Class | Location | Role |
|---|---|---|
| `ToastServiceProvider` | `src/Providers/` | Registers singleton, views, translations, Blade `@toasts` directive, Livewire component |
| `ToastManager` | `src/Services/` | Core service. Adds toasts to session, validates types/positions, converts flash messages |
| `Toast` (Facade) | `src/Facades/` | Static proxy to `ToastManager` |
| `HasToasts` (Trait) | `src/Traits/` | Adds `toast()`, `toastSuccess()`, etc. to controllers |
| `ToastContainer` | `src/Livewire/` | Livewire 3 component with `#[On]` event listeners |
| `InstallCommand` | `src/Console/` | `toast:install` -- interactive CSS/frontend setup |
| `SwitchCommand` | `src/Console/` | `toast:switch --css=X --frontend=Y` |

### View Resolution

`ToastServiceProvider::registerViews()` reads `config('ui-kit.css_framework')` to determine which view directory to load as the `toast` namespace. Falls back to `tailwind` if the directory doesn't exist. Livewire views are registered separately under the `toast-livewire` namespace.

### Configuration

All settings in `config/toast.php` are ENV-overridable with `TOAST_*` prefix. Every config value can also be overridden per-toast via the `$options` array passed to `ToastManager::add()`.

### Translations

40+ language files in `resources/lang/{locale}/toast.php`. Each provides 5 keys: `success`, `error`, `warning`, `info`, `dismiss`.

## Testing Rules

- This is a UI component package with **zero database dependencies**
- Tests use Orchestra Testbench with SQLite `:memory:` only
- `TestCase::setUp()` hard-fails if any non-memory database is detected
- **Never** use `RefreshDatabase`, factories, seeders, models, or database assertions
- Tests cover: component rendering, service class logic, config resolution, view output, Artisan commands
- All tests extend `Jeremykenedy\LaravelToast\Tests\TestCase` (configured in `tests/Pest.php`)

## CI Matrix

GitHub Actions (`.github/workflows/tests.yml`) runs three jobs:
- **Tests**: PHP 8.2/8.3/8.4 x Laravel 12/13 (excludes PHP 8.2 + Laravel 13)
- **Lint**: `./vendor/bin/pint --test` on PHP 8.4
- **JS Check**: Verifies Vue/React/Svelte component files exist with required props

### Livewire Integration

Livewire components are thin wrappers. Each extends `Livewire\Component`, mirrors the Blade component's properties, and renders a view from `resources/views/livewire/` that delegates to the CSS-framework-specific template. Livewire components are only registered if `Livewire::class` exists.

## Critical Rules

### Never mix CSS frameworks in a single view file

Each view file in `resources/views/{framework}/components/` must use ONLY that framework's classes. A Tailwind template must have zero Bootstrap classes and vice versa. All three CSS framework directories must have the same 25 component files (CI validates parity).

### Dark mode on all Tailwind color classes

Every Tailwind color utility must include a `dark:` variant (e.g., `text-gray-900 dark:text-gray-100`). Dark mode is class-based (`@custom-variant dark`), not `prefers-color-scheme`.

### Tests MUST NEVER touch a real database
╔══════════════════════════════════════════════════════════════════════╗
║  THIS IS A UI COMPONENT PACKAGE. IT HAS ZERO DATABASE DEPENDENCIES.║
║  TESTS MUST NEVER TOUCH, CONFIGURE, OR REFERENCE A REAL DATABASE.  ║
╚══════════════════════════════════════════════════════════════════════╝

`TestCase.php` forces SQLite `:memory:`, nullifies all real database
connections (mysql, pgsql, sqlsrv), and hard-fails in `setUp()` if any
non-memory database is detected.

When writing or modifying tests:
- NEVER use `RefreshDatabase`, `DatabaseMigrations`, or `DatabaseTransactions`
- NEVER create migrations, factories, seeders, or models
- NEVER configure or reference mysql, pgsql, or any real database driver
- NEVER add database assertions (`assertDatabaseHas`, `assertDatabaseCount`, etc.)
- NEVER use Eloquent models or query builders in tests
- All tests extend `Jeremykenedy\LaravelUiKit\Tests\TestCase` (via Pest.php)
  which enforces the SQLite `:memory:` safety check automatically
- Tests should ONLY test component rendering, class logic, and config resolution

# Artisan Commands

- `php artisan toast:install` -- Interactive setup (CSS + frontend selection), publishes config, updates `.env`
- `php artisan toast:switch --css=bootstrap5 --frontend=vue` -- Switch frameworks, updates `.env`, clears caches

## CI Matrix

GitHub Actions tests against PHP 8.2/8.3/8.4 with Laravel 12/13. Three jobs: Pest tests, Pint lint check, and JS component file existence validation (ensures all frameworks have matching component counts).

## Pint Configuration

Uses Laravel preset with relaxed rules for `binary_operator_spaces`, `phpdoc_align`, `concat_space`, `ordered_imports`, and others. See `pint.json`.

## FRAMEWORK MIXING VIOLATIONS — EXAMPLES

### WRONG — Tailwind view with Bootstrap classes:
```html
<!-- THIS IS WRONG. "card" and "card-body" are Bootstrap. -->
<div class="card">
    <div class="card-body">
        <h5 class="text-lg font-bold">Title</h5>  <!-- Tailwind mixed in -->
    </div>
</div>
```

### RIGHT — Pure Tailwind view:
```html
<div class="rounded-lg border border-gray-200 dark:border-gray-700 p-6">
    <h5 class="text-lg font-bold text-gray-900 dark:text-gray-100">Title</h5>
</div>
```

### WRONG — Blade view with Livewire directives:
```html
<!-- THIS IS WRONG. wire:click is Livewire. This is a Blade view. -->
<button wire:click="save" class="btn btn-primary">Save</button>
```

### RIGHT — Blade view with Alpine.js:
```html
<button @click="submitForm()" class="btn btn-primary">Save</button>
```

### WRONG — Mixing both CSS frameworks:
```html
<!-- THIS IS CATASTROPHICALLY WRONG -->
<div class="card mb-4">                          <!-- Bootstrap -->
    <div class="p-6 rounded-lg shadow-sm">       <!-- Tailwind -->
        <button class="btn btn-primary">Click</button>  <!-- Bootstrap -->
    </div>
</div>
```

## <x-ui::*> COMPONENT SYSTEM
All UI uses <x-ui::*> components from laravel-toast. These components
automatically render the correct CSS variant. When writing Blade views:
- USE: <x-ui::button>, <x-ui::card>, <x-ui::input>, etc.
- The component internally loads the right CSS framework template
- You still need framework-specific classes for LAYOUT (grid, spacing, etc.)
- The layout classes MUST match the directory's CSS framework

## CRITICAL GOTCHAS
- Existing package controllers extend Illuminate\Routing\Controller, NOT App\Http\Controllers\Controller
- HasTheme goes through profile->theme_id, NOT users.theme_id
- Dark mode is CLASS-BASED (@custom-variant dark), not prefers-color-scheme
- All x-show elements MUST have x-cloak
- [x-cloak] CSS rule must be in app.css AND inline in both layouts
- db:seed must pass clean after ANY schema change
- LaravelBlockerServiceProvider uses afterResolving('seed.handler')

## OUTPUT RULES
- Full file contents, not diffs/patches
- Minimal comments in code
- No em dashes or regular dashes in text output
- Always test: npm run build, php artisan view:clear, php artisan db:seed

## Skills Activation

This project has domain-specific skills available. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

- `laravel-best-practices` — Apply this skill whenever writing, reviewing, or refactoring Laravel PHP code. This includes creating or modifying controllers, models, migrations, form requests, policies, jobs, scheduled commands, service classes, and Eloquent queries. Triggers for N+1 and query performance issues, caching strategies, authorization and security patterns, validation, error handling, queue and job configuration, route definitions, and architectural decisions. Also use for Laravel code reviews and refactoring existing Laravel code to follow best practices. Covers any task involving Laravel backend PHP code patterns.
- `configuring-horizon` — Use this skill whenever the user mentions Horizon by name in a Laravel context. Covers the full Horizon lifecycle: installing Horizon (horizon:install, Sail setup), configuring config/horizon.php (supervisor blocks, queue assignments, balancing strategies, minProcesses/maxProcesses), fixing the dashboard (authorization via Gate::define viewHorizon, blank metrics, horizon:snapshot scheduling), and troubleshooting production issues (worker crashes, timeout chain ordering, LongWaitDetected notifications, waits config). Also covers job tagging and silencing. Do not use for generic Laravel queues without Horizon, SQS or database drivers, standalone Redis setup, Linux supervisord, Telescope, or job batching.
- `socialite-development` — Manages OAuth social authentication with Laravel Socialite. Activate when adding social login providers; configuring OAuth redirect/callback flows; retrieving authenticated user details; customizing scopes or parameters; setting up community providers; testing with Socialite fakes; or when the user mentions social login, OAuth, Socialite, or third-party authentication.
- `livewire-development` — Use for any task or question involving Livewire. Activate if user mentions Livewire, wire: directives, or Livewire-specific concepts like wire:model, wire:click, invoke this skill. Covers building new components, debugging reactivity issues, real-time form validation, loading states, migrating from Livewire 2 to 3, converting component formats (SFC/MFC/class-based), and performance optimization. Do not use for non-Livewire reactive UI (React, Vue, Alpine-only, Inertia.js) or standard Laravel forms without Livewire.
- `pest-testing` — Use this skill for Pest PHP testing in Laravel projects only. Trigger whenever any test is being written, edited, fixed, or refactored — including fixing tests that broke after a code change, adding assertions, converting PHPUnit to Pest, adding datasets, and TDD workflows. Always activate when the user asks how to write something in Pest, mentions test files or directories (tests/Feature, tests/Unit, tests/Browser), or needs browser testing, smoke testing multiple pages for JS errors, or architecture tests. Covers: it()/expect() syntax, datasets, mocking, browser testing (visit/click/fill), smoke testing, arch(), Livewire component tests, RefreshDatabase, and all Pest 4 features. Do not use for factories, seeders, migrations, controllers, models, or non-test PHP code.
- `echo-development` — Develops real-time broadcasting with Laravel Echo. Activates when setting up broadcasting (Reverb, Pusher, Ably); creating ShouldBroadcast events; defining broadcast channels (public, private, presence, encrypted); authorizing channels; configuring Echo; listening for events; implementing client events (whisper); setting up model broadcasting; broadcasting notifications; or when the user mentions broadcasting, Echo, WebSockets, real-time events, Reverb, or presence channels.
- `tailwindcss-development` — Always invoke when the user's message includes 'tailwind' in any form. Also invoke for: building responsive grid layouts (multi-column card grids, product grids), flex/grid page structures (dashboards with sidebars, fixed topbars, mobile-toggle navs), styling UI components (cards, tables, navbars, pricing sections, forms, inputs, badges), adding dark mode variants, fixing spacing or typography, and Tailwind v3/v4 work. The core use case: writing or fixing Tailwind utility classes in HTML templates (Blade, JSX, Vue). Skip for backend PHP logic, database queries, API routes, JavaScript with no HTML/CSS component, CSS file audits, build tool configuration, and vanilla CSS.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.


## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

# PHP
- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

# PHP Array Rules
- NEVER use `array()` syntax. Always use short array syntax `[]`. No exceptions.
- If you encounter existing `array()` code, convert it to `[]` as part of your change.
- When an array is used as a list of objects, provide a PHPDoc type hint: `/** @var User[] $users */`.
- For associative arrays used as configuration, define their keys in a comment block for clarity.

# Test Enforcement
- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== livewire/core rules ===

# Livewire

- Livewire allow to build dynamic, reactive interfaces in PHP without writing JavaScript.
- You can use Alpine.js for client-side interactions instead of JavaScript frameworks.
- Keep state server-side so the UI reflects it. Validate and authorize in actions as you would in HTTP requests.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

## Pest

- This project uses Pest for testing. Create tests: `php artisan make:test --pest {name}`.
- Run tests: `php artisan test --compact` or filter: `php artisan test --compact --filter=testName`.
- Do NOT delete tests without approval.


