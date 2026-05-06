# Project Instructions for AI Agents

## Beads Issue Tracker

This project uses **bd (beads)** for issue tracking via MCP.

- Use beads for ALL task tracking — do NOT use TodoWrite, TaskCreate, or markdown TODO lists
- Use `bd remember` for persistent knowledge — do NOT use MEMORY.md files

## Workflow Rules

1. **Tests are mandatory.** Work is not finished until there are tests that cover the changes/new functionality, and those tests pass.
2. **Update markdown docs before moving on.** README.md and ROADMAP.md must reflect the current state before starting the next task.
3. **Do not push.** The developer will push when ready. Do not run `git push` unless explicitly asked.
4. **Commit atomically.** Stage only relevant files, use brief commit messages.
5. **File issues for remaining work.** Use beads to track anything that needs follow-up.

## Build & Test

```bash
composer install
./vendor/bin/pest
```

## Architecture Overview

Laravel RBAC package. Namespace: `Portier\`.

- `src/Models/` — Permission, Role, RolePermission
- `src/Traits/` — HasRoles, HasPermissions, Authorisable
- `src/Middleware/` — RoleMiddleware, PermissionMiddleware
- `src/PortierServiceProvider.php` — Gate::before, middleware aliases, Blade directives
- `config/portier.php` — Package configuration
- `database/migrations/` — 5 tables
- `tests/` — Pest tests (Feature + Unit)

## Conventions

- PHP 8.2+, Laravel 11/12
- Pest for testing, SQLite in-memory
- Orchestra Testbench for package testing
- No build tools — Blade views use Tailwind CDN when needed
