# Project Conventions

Use these conventions for UI code in this repository.

## File placement

- Base UI primitives: `resources/js/components/ui/`
- Shared components: `resources/js/components/`
- Route components: `resources/js/routes/<route>/`
- Route-local helpers: `resources/js/routes/<route>/utils/`

## Route composition patterns

- Shared route wrappers:
  - `resources/js/components/RouteWithLayout.tsx`
  - `resources/js/components/AuthenticatedRoute.tsx`
- Keep wrapper responsibilities narrow:
  - Layout composition only in `RouteWithLayout`
  - Auth session gate and redirect behavior in `AuthenticatedRoute`

## Async UI boundary patterns

- Global route loading boundary currently lives in `resources/js/app.tsx` via top-level `Suspense`.
- For data-heavy routes, prefer route-local nested boundaries:
  - `RouteNameContent` calls `useSuspenseQuery`
  - `RouteNameLoading` owns fallback
  - `RouteNameError` owns error fallback (via `react-error-boundary` `FallbackComponent`)
- Keep query hooks in content components, not in wrapper or fallback components.
- Use `react-error-boundary` for route/feature boundaries unless there is a strong reason not to.
- Keep fallback copy route/feature-specific and actionable.
- Every fallback must include:
  - One retry action wired to `resetErrorBoundary` (for example `Try again`).
  - One safe escape action (for example `Go home`, `Back to sign in`).
- If domain-specific recovery is unclear, default to retry + safe escape first, then refine with product-specific actions.
- Avoid generic copy like `Something went wrong` with no recovery path.

## Data-fetching usage norms

- Prefer `useSuspenseQuery` for Apollo route data.
- Avoid duplicating loading/error branch logic inside many leaf components when route boundaries can own it.
- Keep Apollo transport/session behavior centralized in `resources/js/lib/apolloClient.ts`.

## Component API and extraction rules

- Prefer explicit typed props and narrow component APIs.
- Keep one-off UI local to its route until reuse is proven.
- Extract to shared components when used by 2+ routes/features or when parent complexity is reduced materially.

## Form-adapter pattern reminders

- Keep primitive UI components in `resources/js/components/ui/`.
- Keep form-specific adapters (current or future) in a dedicated feature/shared form layer.
- Keep field normalization/mapping helpers centralized when repeated.

## Anti-pattern reminders

- No barrel exports (`index.ts` re-export hubs).
- No mega utility files with unrelated helpers.
- No large inline helper sections in route component files.
- Keep GraphQL operations colocated and generated artifacts in sync.
- No ad-hoc async state handling copies across routes when boundary components can be reused.

## Existing examples

- `resources/js/components/AuthenticatedRoute.tsx`
- `resources/js/components/RouteWithLayout.tsx`
- `resources/js/routes/authentication/`
- `resources/js/routes/home/`
- `resources/js/hooks/useUser.ts`
- `resources/js/lib/apolloClient.ts`
