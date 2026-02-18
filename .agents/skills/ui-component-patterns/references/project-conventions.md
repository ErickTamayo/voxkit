# Project Conventions

Use these conventions for UI code in this repository.

## File placement

- Base UI primitives: `resources/js/components/ui/`
- Shared components: `resources/js/components/`
- Route components: `resources/js/routes/<route>/`
- Route-local helpers: `resources/js/routes/<route>/utils/`

## Anti-pattern reminders

- No barrel exports (`index.ts` re-export hubs).
- No mega utility files with unrelated helpers.
- No large inline helper sections in route component files.
- Keep GraphQL operations colocated and generated artifacts in sync.

## Existing examples

- `resources/js/components/AuthenticatedRoute.tsx`
- `resources/js/components/RouteWithLayout.tsx`
- `resources/js/routes/authentication/`
- `resources/js/routes/home/`
