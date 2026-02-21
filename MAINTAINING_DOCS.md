# Maintaining Backend Documentation

## Goal
Keep backend docs accurate, minimal, and migration-friendly as guidance moves into skills.

## Scope
This guide is backend-only.
Do not add cross-references to other repositories from this file.

## Current Docs Inventory
- `docs/anti-patterns.md`
- `docs/api-conventions.md`
- `docs/authentication.md`
- `docs/controller-patterns.md`
- `docs/database-patterns.md`
- `docs/database-schema-desicions.md`
- `docs/database-schema.md`
- `docs/development-workflow.md`
- `docs/revenue-calculation-logic.md`
- `DATA_MODEL.md`

## Update Triggers
Update docs whenever you change:
- Public/backend API contracts (GraphQL schema, route contracts, payload shape)
- Database schema, model relationships, or persistence rules
- Authentication behavior or security posture
- Service-layer behavior that affects domain contracts
- Backend development/test workflow commands

## What To Update
- Contract or schema change: update `docs/api-conventions.md`, `docs/database-schema.md`, and related domain docs.
- Auth behavior change: update `docs/authentication.md`.
- Backend workflow changes: update `docs/development-workflow.md`.
- Data model direction changes: update `DATA_MODEL.md` (high-level only).

## Backend-Only Update Workflow
1. Make code changes.
2. Identify affected backend docs.
3. Update docs in the same branch.
4. Verify references and commands.
5. Commit code and docs together.

## Verification Commands
```bash
# Find stale path references inside docs
rg -n --glob '*.md' --glob 'docs/**/*.md' '(\.\./|docs/[a-z0-9_./-]+\.md)'

# Review backend command references in docs
rg -n --glob '*.md' --glob 'docs/**/*.md' 'php artisan [a-z0-9:\-]+'

# Validate command existence
php artisan list

# Optional: run schema and tests before finalizing doc updates
php artisan lighthouse:validate-schema
php artisan test --compact
```

## Keep Docs Lean
When information becomes stable and procedural:
1. Move it into the appropriate skill.
2. Replace long doc sections with a short pointer.
3. Remove duplicated guidance from docs.

## PR Checklist
- [ ] Updated all affected backend docs
- [ ] Removed stale or non-existent file references
- [ ] Removed non-backend references from backend docs
- [ ] Verified command names against `php artisan list`
- [ ] Kept docs concise and non-duplicative
