---
name: ui-component-patterns
description: Create and refactor reusable UI components with composition-first APIs, strict typing, and consistent style conventions. Use when building new UI primitives, extracting reusable feature components, defining compound component APIs, or standardizing component placement and naming in React or React Native projects.
---

# UI Component Pattern

Follow this workflow when implementing or refactoring UI components.

## 1. Choose component ownership level first

- Put framework-level primitives in `resources/js/components/ui/`.
- Put business/domain reusable components in `resources/js/components/`.
- Keep route-specific UI in `resources/js/routes/<route>/`.
- Keep one-off UI local unless reuse/complexity justifies extraction.

## 2. Define API and typing before implementation

- Declare explicit prop types before implementing rendering logic.
- Prefer narrow, intentional props over pass-through catch-all props.
- Keep naming predictable and consistent with nearby components.
- Avoid introducing `any`; model state and variants explicitly.
- Prefer arrow-function components and explicit React return types.

## 3. Use composition-first rendering

- Compose larger UI from smaller primitives.
- Use compound components only when the API benefits from flexible composition.
- Keep global state out of component internals.
- Move non-trivial pure helpers out of route/component render files into colocated `utils/`.

## 4. Apply consistent styling conventions

- Use Tailwind utility classes and existing project conventions.
- Keep styling behavior predictable and colocated with the component.
- Keep variant logic close to the styled element.
- Avoid introducing custom style abstractions unless repeated usage justifies extraction.

## 5. Apply async UI boundaries for data-driven routes

- Prefer `useSuspenseQuery` for Apollo route/component data.
- Keep loaded-content rendering in a dedicated `RouteNameContent` component.
- Keep loading fallback UI separate from loaded content.
- Use `react-error-boundary` for route/feature error boundaries unless there is a strong reason not to.
- Keep error fallback UI explicit and actionable.
- Fallbacks must be route/feature-specific (no generic `Something went wrong` copy).
- Fallbacks must include:
  - one retry action (`Try again`) wired to `resetErrorBoundary`,
  - one safe escape action (for example `Go home` or `Back to sign in`).
- If domain-specific recovery is unclear, default to retry + safe escape first, then refine with product-specific actions.
- Avoid ad-hoc `isLoading` and `error` branch sprawl when Suspense boundaries can own that behavior.

Example:

```tsx
import { useSuspenseQuery } from "@apollo/client/react";
import { Suspense } from "react";
import { ErrorBoundary, type FallbackProps } from "react-error-boundary";
import { useLocation } from "wouter";
import { MeDocument } from "@/graphql/root.graphql";
import { Button } from "@/components/ui/button";

function AccountRouteContent(): React.JSX.Element {
    const { data } = useSuspenseQuery(MeDocument);
    return <p>{data.me?.email}</p>;
}

function AccountRouteLoading(): React.JSX.Element {
    return <p className="text-sm text-muted-foreground">Loading account...</p>;
}

function AccountRouteErrorBoundary({ resetErrorBoundary }: FallbackProps): React.JSX.Element {
    const [, setLocation] = useLocation();

    return (
        <div className="space-y-3 rounded-md border border-destructive/30 bg-destructive/10 p-4">
            <p className="text-sm text-destructive">Could not load account details.</p>
            <div className="flex gap-2">
                <Button type="button" onClick={() => resetErrorBoundary()}>
                    Try again
                </Button>
                <Button type="button" variant="outline" onClick={() => setLocation("/")}>
                    Go home
                </Button>
            </div>
        </div>
    );
}

export default function AccountRoute(): React.JSX.Element {
    return (
        <ErrorBoundary FallbackComponent={AccountRouteErrorBoundary}>
            <Suspense fallback={<AccountRouteLoading />}>
                <AccountRouteContent />
            </Suspense>
        </ErrorBoundary>
    );
}
```

## 6. Follow route composition patterns

- Use shared wrappers like `RouteWithLayout` and `AuthenticatedRoute` for cross-route behaviors.
- Keep wrappers generic and reusable (layout composition, auth gating, redirect behavior).
- Keep route files focused on route behavior, not reusable wrapper logic.

## 7. Enforce anti-pattern boundaries

- No barrel exports (`index.ts` re-export hubs).
- No mega utility files with unrelated helpers.
- No large inline helper blocks in route component files.
- No duplicated loading/error UI logic across many route files when a reusable boundary component can be used.
- No generic non-contextual error fallback copy for route boundaries.

## 8. Validate integration quality

- Confirm accessibility roles/labels for interactive UI.
- Confirm import paths stay direct and explicit.
- Confirm no accidental barrel-export patterns were introduced.
- Confirm route/component files do not accumulate large inline helper blocks.

## Output checklist

- Component placement matches reuse level.
- API is typed and minimal.
- Styling follows shared conventions.
- Loading, error, and loaded states have clear ownership.
- No new anti-patterns (barrels, mega utils, unclear ownership, duplicated async branches).

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

- Prefer arrow-function components unless there is a strong reason to use a named function declaration.
- Use explicit prop typing; avoid `any` unless truly unavoidable.
- Keep route constants/types in route files or `*.route.constants.ts` / `*.route.types.ts`.
- Move large route helpers to colocated `utils/` files; avoid inline helper blocks in `*.route.tsx`.

For repository-specific placement and anti-pattern examples, read `references/project-conventions.md`.
