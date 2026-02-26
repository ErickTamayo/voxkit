---
name: ui-component-patterns
description: Create and refactor reusable UI components with composition-first APIs, strict typing, and consistent style conventions. Use when building new UI primitives, extracting reusable feature components, defining compound component APIs, or standardizing component placement and naming in React or React Native projects.
---

# UI Component Pattern

Follow this workflow when implementing or refactoring UI components.

## 0. Confirm official integration patterns first (3rd-party primitives/libraries)

- Before integrating non-trivial 3rd-party UI libraries (for example Radix, Motion, shadcn composition patterns, gesture libraries, virtualization, animation libraries), check official docs/guides/examples first.
- Prefer sources in this order: official docs -> official examples -> maintainer repo examples -> community posts.
- If an official guide exists (for example Motion + Radix integration), follow that pattern before inventing custom orchestration.
- If you must deviate, state the reason and tradeoffs in the plan/review summary and get approval before coding the deviation.
- In major-step reviews, include the source link(s) and name the pattern you followed.

## 1. Choose component ownership level first

- Put framework-level primitives in `resources/js/components/ui/`.
- Put business/domain reusable components in `resources/js/components/`.
- Keep route-specific UI in `resources/js/routes/<route>/`.
- Keep one-off UI local unless reuse/complexity justifies extraction.
- Before creating a new helper/hook/predicate, run a quick local reuse discovery scan to avoid duplicating logic that is already local but out of context.
- Search by both likely names and behavior/side-effects (for example interaction predicates, `data-*` selectors, DOM attribute sync, safe-area handling), not just the guessed helper name.
- Use this search scope ladder (stop early if you find a good fit): current file/folder -> sibling component family -> `resources/js/components/ui/`, `resources/js/hooks/`, `resources/js/lib/` -> repo-wide targeted `rg`.
- After the scan, choose one path explicitly: reuse existing helper, adapt an existing helper, extract a shared helper, or keep a new helper local.
- In plan/review summaries for non-trivial UI changes, record one line: `Reuse scan: searched ..., chose ... because ...`.
- If creating a new helper, use a searchable name and colocate tests with the owning component family when behavior is deterministic.
- When a UI primitive grows beyond a single file (supporting subcomponents, helpers, local types), move it into its own folder under `resources/js/components/ui/<component>/` and split supporting parts into their own files.
- Even when split across files, preserve a composable compound API exposed via dot notation (for example `Modal.Root`, `Modal.Overlay`, `Modal.Content`, etc.).
- Do not use barrel exports (`index.ts`) to assemble the dot-notation API.

## 2. Define API and typing before implementation

- Declare explicit prop interfaces before implementing rendering logic.
- Prefer narrow, intentional props over pass-through catch-all props.
- Keep naming predictable and consistent with nearby components.
- Avoid introducing `any`; model state and variants explicitly.
- Props must use `interface` declarations, not inline object types.
- Always destructure props in the parameter list.
- Use `const Component: FC<Props> = ({ ... }) => { ... }`.
- Import React APIs/types by name (for example `import { Suspense, type FC } from "react"`).
- Do not use namespace imports (`import * as React from "react"`).
- Prefer named exports for non-route modules.
- Route files may use default export, but only as `const RouteName: FC = ...` then `export default RouteName;` at file end.
- Do not use anonymous default exports.
- Do not start with explicit component return signatures like `(...): React.JSX.Element`.
- Only if the canonical pattern is not feasible (for example complex generics or overloaded signatures), fall back to explicit prop/return typing after attempting the canonical signature first.
- Prefer composable APIs (children + compound subcomponents) over prop-surface customization for UI primitives.
- Do not default to `*ClassName` escape-hatch props for primitive internals (`overlayClassName`, `contentClassName`, etc.) when composition can express the customization.
- Do not add auto-wrapping render adapters (for example helpers that coerce strings into component slots) unless there is a critical need and the deviation is approved.

## 3. Use composition-first rendering

- Compose larger UI from smaller primitives.
- Use compound components only when the API benefits from flexible composition.
- Keep global state out of component internals.
- Move non-trivial pure helpers out of route/component render files into colocated `utils/`.
- For compound primitives, expose intentional subcomponents instead of pushing structure customization into props.
- If a consumer needs a different button/layout/content surface, they should render that structure through composition.

## 4. Apply consistent styling conventions

- Use Tailwind utility classes and existing project conventions.
- Keep styling behavior predictable and colocated with the component.
- Keep variant logic close to the styled element.
- Avoid introducing custom style abstractions unless repeated usage justifies extraction.

### Visual bug triage before patching (especially Motion / animated UI)

- Before changing styles, restate the symptom in one line and separate concerns: positioning, clipping, transform state, focus/outline styles, animation timing.
- Re-derive the coordinate system first:
  - which ancestor is the positioning context (`relative`, `absolute`, `fixed`),
  - whether the visual should be clipped (`overflow-*`),
  - whether the animated element already has transforms (`translateX/Y`, scale).
- For Motion elements, do not assume removing a property from `animate` resets it. If a previous version animated `y`, explicitly set `y: 0` (or the intended value) when refactoring to avoid stale transforms.
- Explain the fix model before editing (2-4 lines and, when helpful, a tiny snippet). Example: "indicator is anchored to tablist bottom, animate only `x`/`width`, and explicitly reset `y: 0`."
- Make one visual change at a time, verify in Storybook, then continue tuning.
- If a visual bug persists after two quick patches, stop and re-check the layout/animation model instead of continuing incremental CSS tweaks.

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
import { Suspense, type FC } from "react";
import { ErrorBoundary, type FallbackProps } from "react-error-boundary";
import { useLocation } from "wouter";
import { MeDocument } from "@/graphql/root.graphql";
import { Button } from "@/components/ui/button";

const AccountRouteContent: FC = () => {
    const { data } = useSuspenseQuery(MeDocument);
    return <p>{data.me?.email}</p>;
};

const AccountRouteLoading: FC = () => {
    return <p className="text-sm text-muted-foreground">Loading account...</p>;
};

const AccountRouteErrorBoundary: FC<FallbackProps> = ({
    resetErrorBoundary,
}) => {
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
};

const AccountRoute: FC = () => {
    return (
        <ErrorBoundary FallbackComponent={AccountRouteErrorBoundary}>
            <Suspense fallback={<AccountRouteLoading />}>
                <AccountRouteContent />
            </Suspense>
        </ErrorBoundary>
    );
};

export default AccountRoute;
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
- Confirm import ordering follows project conventions.
- Confirm no accidental barrel-export patterns were introduced.
- Confirm route/component files do not accumulate large inline helper blocks.
- Confirm a local reuse discovery scan was done before introducing new non-trivial helpers/hooks/predicates.
- Confirm 3rd-party primitive integrations follow an official recommended pattern when one exists (or the deviation was explicitly approved).

## 9. State, Hooks, and Performance

- Do not put server data in global state; use Apollo for server state.
- Keep state local first; only promote state when needed.
- Avoid prop drilling past 2 levels; use Zustand at that point.
- Prefer `useReducer` over multiple related `useState` values.
- Custom hooks must:
  - start with `use`,
  - own one concern,
  - live in the appropriate `hooks/` scope,
  - return a consistent object shape.
- Do not define non-trivial custom hooks inside component files.
- Reusable hooks belong in `resources/js/hooks/`.
- Component-scoped hooks may live in a component-local `hooks/` folder only when they are truly local to that component family.
- Prefer business logic in hooks rather than directly in components.
- Do not use `useEffect` for derived state.
- Do not use `useCallback` and `useMemo` (React Compiler project rule).
- Every route-level component must be lazy loaded.
- Keep callback references stable when possible without memoization.
- Virtualize long lists with `@tanstack/virtual`.
- API base URLs and keys must come from `.env`; never hardcode them.

## 10. Styling, TypeScript, Testing, and Safety

- No inline styles except truly dynamic values.
- No global CSS beyond resets and design tokens.
- Use CSS custom properties for design tokens.
- Use `interface` for object shapes; use `type` for unions/intersections.
- No `any`; use `unknown` and narrow or define explicit types.
- Co-locate types with owning features; only move shared types when reused across features.
- Prefer union literals for simple string states over enums.
- Test user-visible behavior and business logic, not implementation details.
- Co-locate tests with components/hooks when possible.
- Remove dead code; do not leave commented-out code.
- Avoid committed `console` logs.
- Use semantic HTML and keyboard-accessible interactions.
- Avoid `dangerouslySetInnerHTML`; sanitize user-generated content.
- Never store sensitive tokens in `localStorage`; use secure backend cookie strategies.

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

- Prefer arrow-function components and `FC<Props>` by default.
- Props should be declared with `interface` and destructured at the parameter.
- Use explicit prop/return signatures only after attempting the canonical signature and confirming an edge case requires it.
- Use explicit prop typing; avoid `any` unless truly unavoidable.
- Keep route constants/types in route files or `*.route.constants.ts` / `*.route.types.ts`.
- Move large route helpers to colocated `utils/` files; avoid inline helper blocks in `*.route.tsx`.

For repository-specific placement and anti-pattern examples, read `references/project-conventions.md`.
