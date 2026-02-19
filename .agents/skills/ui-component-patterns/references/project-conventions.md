# Project Conventions

Use these conventions for UI code in this repository.

## File Placement

- Base UI primitives: `resources/js/components/ui/`
- Shared components: `resources/js/components/`
- Route components: `resources/js/routes/<route>/`
- Route-local helpers: `resources/js/routes/<route>/utils/`
- Hooks belong in `hooks/` at the feature scope first, shared scope only when reused.

## Export and Import Standards

- Use named exports for everything by default.
- Route files are the only default-export exception:
  - declare `const RouteName: FC = () => { ... };`
  - end file with `export default RouteName;`
- Never use anonymous default exports.
- Never use `import * as React from "react"`.
- Import React APIs and types by name.
- Use absolute imports via `@/` alias (avoid deep relative imports).
- Import order:

```tsx
// 1) React
import { useState, type FC } from "react";

// 2) Third-party
import { useLocation } from "wouter";

// 3) Internal aliases
import { Button } from "@/components/ui/button";

// 4) Feature-local
import { FormSection } from "./components/FormSection";

// 5) Types
import type { FormValues } from "./types";
```

## Component API Standards

- Type props explicitly using TypeScript `interface` declarations.
- Destructure props in the parameter list.
- Preferred signature: `const Component: FC<Props> = ({ ... }) => { ... }`.
- Do not start with explicit component return signatures like `(...): React.JSX.Element`.
- Use explicit prop/return signatures only if canonical typing is truly not feasible.
- Keep one-off UI local to a route until reuse is proven.
- Extract to shared components when reused by 2+ features or when parent complexity drops materially.
- Avoid unnecessary wrapper `div`s; use fragments when no semantic/styling wrapper is needed.

## Route Composition and Async Boundaries

- Shared wrappers:
  - `resources/js/components/RouteWithLayout.tsx`
  - `resources/js/components/AuthenticatedRoute.tsx`
- Wrapper ownership:
  - `RouteWithLayout` handles layout composition only.
  - `AuthenticatedRoute` handles auth/session gate + redirect behavior.
- Global route loading boundary stays in `resources/js/app.tsx` using top-level `Suspense`.
- For data-heavy routes, use nested route boundaries:
  - `RouteNameContent` calls `useSuspenseQuery`
  - `RouteNameLoading` handles loading UI
  - `RouteNameError` handles errors via `react-error-boundary` `FallbackComponent`
- Keep query hooks in content components, not wrappers/fallbacks.
- Fallbacks must be route/feature-specific and actionable.
- Every fallback includes:
  - retry action wired to `resetErrorBoundary`
  - safe escape action (`Go home`, `Back to sign in`, etc.)
- If domain-specific recovery is unclear, default to retry + safe escape first.

## State and Data Rules

- Do not put server data in global state; use Apollo for server state.
- Keep state local as long as possible.
- Promote state only when necessary.
- Avoid prop drilling past 2 levels; use Zustand when that boundary is exceeded.
- Prefer `useReducer` over multiple related `useState` values in complex local state flows.
- Keep Apollo transport/session behavior centralized in `resources/js/lib/apolloClient.ts`.

## Hook Rules

- Custom hooks must start with `use`.
- One concern per hook.
- Return a consistent object shape (named fields).
- Prefer business logic in hooks instead of route/component render files.
- Do not use `useEffect` for derived state.
- Do not use `useCallback` and `useMemo` (React Compiler project rule).

## Performance Rules

- Every route-level component must be lazy loaded.
- Keep callback references stable when possible without memoization.
- Keep context granular; do not mix high-churn context with app-level static config.
- Virtualize long lists with `@tanstack/virtual`.

## Styling Rules

- No inline styles except truly dynamic runtime values.
- No global CSS besides resets and design tokens.
- Use CSS custom properties for design tokens (colors, spacing, typography).

## TypeScript Standards

- No `any`; use `unknown` + narrowing or explicit interfaces.
- Type parameters and return types when it improves clarity and safety.
- Use `interface` for object shapes.
- Use `type` for unions/intersections.
- Co-locate types with the owning feature; move to shared only when reused.
- Prefer union literals over enums for simple string states.

## Testing Standards

- Unit tests (most): pure functions, hooks, utilities with Vitest + RTL.
- Integration tests (some): component interactions + API mocking with RTL + MSW.
- Test user-visible behavior and business logic.
- Avoid implementation-detail tests.
- Co-locate tests with components/hooks when possible.

## Code Quality Guardrails

- DRY: extract logic reused 2+ times into shared util/hook.
- Component size target: soft cap ~150 lines, hard cap ~250 lines.
- JSX nesting target: max depth 3 in a single return block when possible.
- Comments explain why, not what.
- Remove dead code; do not leave commented-out blocks.
- No committed `console` logs.
- Keep GraphQL operations colocated and generated artifacts in sync.
- No barrel exports (`index.ts` re-export hubs).
- No mega utility files with unrelated helpers.

## Accessibility and Security

- Interactive elements must be keyboard navigable.
- Use semantic HTML (`button`, `nav`, `main`, etc.) instead of clickable `div`s.
- Every image needs `alt`; decorative images must use `alt=""`.
- Form inputs must have associated labels.
- Use ARIA only when semantic HTML cannot express the meaning.
- Never store sensitive tokens in `localStorage`; prefer secure backend cookie flows.
- Sanitize user-generated content before rendering; avoid `dangerouslySetInnerHTML`.
- Keep API URLs/keys in `.env` (`VITE_*`) values only; never hardcode secrets.
- Validate user input on both client and server.
- Keep dependencies updated and run security audits regularly.

## Existing Examples

- `resources/js/components/AuthenticatedRoute.tsx`
- `resources/js/components/RouteWithLayout.tsx`
- `resources/js/routes/authentication/`
- `resources/js/routes/home/`
- `resources/js/hooks/useUser.ts`
- `resources/js/lib/apolloClient.ts`
