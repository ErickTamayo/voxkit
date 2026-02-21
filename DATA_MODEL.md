# Voxkit Data Model (Backend)

## Purpose
This file is a lightweight index for backend data modeling.

The source of truth for schema details lives in:
- `docs/database-schema.md`
- `docs/database-schema-desicions.md`
- `database/migrations/`
- `schema.graphql` and `graphql/*.graphql`

## Current Domain Surface
- Authentication and user accounts (`users`, `auth_codes`, Sanctum tokens)
- User configuration and profile (`settings`, `business_profile`)
- Contacts and sourcing (`contacts`, `agents`, `clients`, `platforms`)
- Work lifecycle (`auditions`, `jobs`, `usage_rights`)
- Financial tracking (`invoices`, `invoice_items`, `expenses`, `expense_definitions`)
- Files and notes (`attachments`, `notes`)
- Attention workflow (`activities`)
- Supporting infrastructure (`exchange_rates`, `rewind_versions`, search indexes)

## Maintenance Rules
- Keep this file high-level; do not duplicate full column lists.
- Update `docs/database-schema.md` first when schema changes.
- Keep documentation backend-only; avoid non-backend assumptions here.
- If behavior is stable and reusable, prefer capturing it in skills instead of expanding docs.
