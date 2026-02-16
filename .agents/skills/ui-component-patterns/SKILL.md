---
name: ui-component-patterns
description: Create and refactor reusable UI components with composition-first APIs, strict typing, and consistent style conventions. Use when building new UI primitives, extracting reusable feature components, defining compound component APIs, or standardizing component placement and naming in React or React Native projects.
---

# UI Component Pattern

Follow this workflow when implementing or refactoring UI components.

## 1. Choose the correct component level

- Put framework-level primitives in a dedicated UI primitives area.
- Put business/domain components in feature-level folders.
- Keep one-off route UI local to the route folder.
- Do not extract components until there is a clear reuse or complexity reason.

## 2. Define API and typing first

- Declare explicit prop types before implementing rendering logic.
- Prefer narrow, intentional props over pass-through "catch-all" APIs.
- Keep naming predictable and consistent with nearby components.
- Avoid introducing `any`; model state and variants explicitly.

## 3. Implement composition-first rendering

- Compose larger UI from smaller primitives.
- Use compound components only when the API benefits from flexible composition.
- Keep global state out of component internals.
- Keep reusable pure helpers outside render bodies when they grow beyond trivial size.

## 4. Apply consistent styling patterns

- Colocate styles with the component implementation.
- Keep variant logic close to the styled element.
- Prefer theme tokens over hard-coded values.
- Keep platform-specific styling in explicit platform branches or platform files.

## 5. Validate integration quality

- Confirm accessibility roles/labels for interactive UI.
- Confirm import paths stay direct and explicit.
- Confirm no accidental barrel-export patterns were introduced.

## Output checklist

- Component placement matches reuse level.
- API is typed and minimal.
- Styling follows shared conventions and token usage.
- No new anti-patterns (barrels, mega utils, unclear ownership).

## File placement

- UI primitives: `resources/js/components/ui/`
- Shared feature components: `resources/js/components/`
- Route implementation: `resources/js/routes/<route-name>/`
- Route-local reusable parts: `resources/js/routes/<route-name>/components/`
- Sub Route implementation: `resources/js/routes/<route-name>/routes/<subroute>/`
- SubRoute components: `resources/js/routes/<route-name>/routes/<subroute>/components/`

## Core architecture rules

- Keep GraphQL operations colocated with consumers (`*.graphql` + `*.graphql.ts`).
- Do not use barrel exports (`index.ts` re-export files are disallowed).

## Component implementation rules

- Use arrow-function components (`react/function-component-definition` enforced).
- Use explicit prop typing; avoid `any` unless truly unavoidable.
- Keep route constants/types in route files or `*.route.constants.ts` / `*.route.types.ts`.
- Move large route helpers to colocated `utils/` files; avoid inline helper blocks in `*.route.tsx`.

## Styling rules

- Use `tailwindcss`
