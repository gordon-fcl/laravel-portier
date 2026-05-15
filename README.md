# laravel-portier

A flexible, granular RBAC package for Laravel. Drop-in admin UI, Gate integration via `Gate::before`, wildcard permissions, role inheritance, explicit denials.

## Status

**Phases 1–3 complete.** Next: Phase 4 (Schema & sync), Phase 5 (Caching), Phase 6 (Events & CLI).

- 72 tests passing (Pest, 107 assertions)
- Namespace: `Portier\`
- Requires: PHP 8.2+, Laravel 11/12

## What's Built

- **Models:** Permission, Role, RolePermission (custom pivot)
- **Traits:** HasRoles, HasPermissions, Authorisable
- **Resolution:** Role inheritance, wildcards (`posts.*`), explicit denials, `direct_overrides_role` config
- **Super-admin:** Built-in via `isSuperAdmin()` — just assign the configured role
- **Gate integration:** `Gate::before` hook — `$user->can()`, `@can`, `Gate::allows()` all work
- **Middleware:** `role:admin`, `permission:posts.create` with `|` (any) and `&` (all) operators
- **Blade directives:** `@role('admin')/@endrole`, `@permission('posts.create')/@endpermission`

## Running Tests

```bash
composer install
./vendor/bin/pest
```

## Docs

See the project's issue tracker (`bd ready`) for current work and roadmap.
