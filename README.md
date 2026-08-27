# laravel-portier

A flexible, granular RBAC package for Laravel. Drop-in admin UI, Gate integration via `Gate::before`, wildcard permissions, role inheritance, explicit denials.

## Status

**Phases 1–6 complete.** Next: Phase 7 (API Authorisation & Endpoints).

- 117 tests passing (Pest)
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
- **Schema & sync:** Define permissions in config, run `php artisan portier:sync` to keep DB in sync
- **Caching:** PermissionRegistrar caches permissions/roles, auto-invalidates on write, configurable store/TTL
- **Events:** RoleAssigned, RoleRemoved, PermissionGranted, PermissionRevoked, RoleCreated, RoleDeleted, PermissionsSynced
- **CLI:** `portier:sync`, `portier:cache`, `portier:clear-cache`, `portier:create-role`, `portier:assign`

## Running Tests

```bash
composer install
./vendor/bin/pest
```

## Docs

See the project's issue tracker (`bd ready`) for current work and roadmap.
