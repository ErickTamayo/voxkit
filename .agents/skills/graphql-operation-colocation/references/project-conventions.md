# Project Conventions

Use these conventions in this repository.

## File placement

- GraphQL operation files live near consuming UI in `resources/js/**`.
- Generated operation artifacts use `*.graphql.ts` alongside `*.graphql`.
- Shared generated schema types: `resources/js/graphql/types.ts`.

## Commands

- Refresh backend schema file: `npm run schema:generate`
- Regenerate typed docs: `npm run codegen`

## Integration standards

- Use `useSuspenseQuery` for authenticated user/session data flows.
- Keep Apollo transport and auth token wiring in `resources/js/lib/apolloClient.ts`.
- Keep operation names stable and feature-scoped to avoid cache and tooling drift.
