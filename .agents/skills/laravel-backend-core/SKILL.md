---
name: laravel-backend-core
description: Core Laravel 12 and PHP 8.3 backend rules for architecture, validation, Eloquent usage, testing, and formatting in this repository.
---

# Laravel Backend Core

Apply this skill when working on backend PHP/Laravel code, migrations, validation, routing, or API behavior.

## Mandatory Documentation Lookup

- Always use `search-docs` for version-specific guidance before implementing Laravel, Pest, or Tailwind related behavior.
- Use broad topic queries and refine only as needed.

## PHP Rules

- Always use curly braces for control structures.
- Use explicit parameter and return types.
- Use constructor property promotion when appropriate.
- Avoid empty public constructors with no parameters.
- Prefer PHPDoc for meaningful type/context notes (including useful array shapes).

## Laravel Implementation Rules

- Use `php artisan make:*` generators for framework artifacts.
- Pass `--no-interaction` to Artisan generation commands.
- Prefer Eloquent models and relationships over raw SQL.
- Avoid `DB::` unless complexity clearly requires query builder-level access.
- Eager load relationships to prevent N+1 behavior.
- Use Form Request classes for validation, including custom messages.
- Prefer named routes and `route()` for URL generation.
- Use built-in auth/authorization features (policies, gates, Sanctum).
- Use queued jobs (`ShouldQueue`) for time-consuming tasks.
- Never use `env()` outside config files; use `config()` in app code.

## Laravel 12 Structure

- Middleware is configured in `bootstrap/app.php`, not `app/Http/Kernel.php`.
- `bootstrap/providers.php` contains app-specific providers.
- `app/Console/Kernel.php` is not used in this structure.
- Console commands in `app/Console/Commands/` are auto-registered.
- When changing existing columns in migrations, preserve full column attributes.

## Testing and Verification

- Every functional change must be verified programmatically with focused tests.
- Prefer minimal targeted runs: `php artisan test --compact <file-or-filter>`.
- Use factories and existing factory states for test data setup.
- Do not remove tests without explicit approval.
- Do not add ad-hoc verification scripts when tests can prove behavior.

## Formatting

- Run `vendor/bin/pint --dirty --format agent` before finalizing PHP changes.
