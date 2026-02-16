---
name: graphql-operation-colocation
description: Implement colocated GraphQL operations with generated typed documents and predictable query/mutation integration. Use when adding or modifying GraphQL queries, mutations, fragments, hooks, or screen data flows in projects that use GraphQL Code Generator.
---

# GraphQL Operation Colocation

Follow this workflow when adding or updating GraphQL-backed UI.

## 1. Place operations with their owner

- Identify the component or screen that owns the data requirement.
- Create or update a nearby `*.graphql` file for that owner.
- Keep operation names clear and scoped to the feature.
- Request only fields required by the current UI behavior.

## 2. Regenerate typed artifacts immediately

- Run GraphQL code generation after changing operations.
- Keep generated artifacts colocated with the operation file.
- Do not hand-edit generated files.
- Resolve any type breakage before continuing.

## 3. Wire typed operations into UI

- Import generated document nodes from codegen output.
- Use typed query/mutation hooks from your GraphQL client integration.
- Keep transport details in shared API client setup, not per-screen code.
- Keep data mapping close to the component that renders it.

## 4. Handle loading and errors consistently

- Use the project-standard async UI pattern (Suspense/loading boundary or equivalent).
- Keep loading UI separate from loaded-content UI.
- Keep error fallback UI explicit and actionable.
- Avoid ad-hoc loading/error branches that conflict with project conventions.

## 5. Validate behavior and safety

- Verify operation naming, variables, and nullability assumptions.
- Verify cache updates/refetch strategy for mutations.
- Verify component type safety with no unchecked casts.
- Verify no stale field usage remains after schema/operation edits.

## Output checklist

- `*.graphql` and generated artifacts are colocated and in sync.
- Component imports typed document/hook artifacts.
- Loading/error behavior matches project conventions.
- GraphQL changes are minimal, intentional, and type-safe.

## File placement

- Colocate operation files with the consuming route/component.
- Use this pair pattern:
  - `Feature.graphql`
  - `Feature.graphql.ts` (generated)
- Base generated schema types live in `src/graphql/types.ts`.

## Commands

- One-off codegen: `npm run codegen`

## Query integration standards

- Use `useSuspenseQuery` for Apollo queries (do not introduce `useQuery` loading branches).
- Wrap data-driven screens with `Suspense` + `ErrorBoundary` and keep fetch logic in a `ScreenNameContent` component.
- Keep loading and error UI in dedicated components (for example `ScreenLoading`, `ScreenErrorBoundary`).

## Shared user/settings data

- Reuse shared hooks for common user/session settings instead of duplicating query logic.
- Example shared user query hook: `src/hooks/useUser.ts`.
