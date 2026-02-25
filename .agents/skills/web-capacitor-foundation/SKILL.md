---
name: web-capacitor-foundation
description: "Define and maintain the shared frontend foundation for web and Capacitor targets in voxkit. Use when changing vite.config.ts or vite.capacitor.config.ts, adding .capacitor.* file resolution, creating/updating RootLayout.tsx or RootLayout.capacitor.tsx, implementing safe-area handling, modal/sheet presentation behavior, Tailwind breakpoint-driven UI behavior, centralized Capacitor back handling, or other platform-specific UI foundation work."
---

# Web + Capacitor Foundation

Apply this skill for platform foundation work in `voxkit`.

## Official Pattern First (3rd-party + tooling integrations)

- Before implementing non-trivial integrations with external libraries/frameworks/tooling used by the platform foundation (for example Capacitor APIs, Radix primitives, Motion animation/presence patterns, Vite plugin hooks/resolvers), check official docs/guides/examples first.
- Prefer sources in this order: official docs -> official examples -> maintainer repository examples -> community posts.
- If an official integration guide exists (for example Motion + Radix), follow that pattern before inventing custom orchestration.
- If you intentionally deviate, state why, list the risk/tradeoff, and get approval before implementing the deviation.
- In `make -> review` summaries for these steps, include the source link(s) and the exact pattern being followed.

## Decision Baseline (Authoritative)

- Two axes only:
  - `target`: `web | capacitor`
  - `breakpoint`: Tailwind responsive breakpoints (`base`, `sm`, `md`, `lg`, `xl`, `2xl`) expressed in `className` utilities for layout/presentation changes
- Mobile web is not a third product mode.
- Presentation should be very similar between mobile-sized web and Capacitor unless a target-specific difference is intentional.
- Default to Tailwind responsive utilities in `className` for breakpoint behavior. Do not introduce JS breakpoint maps/hooks unless CSS-only implementation is clearly insufficient for the requirement.
- One modal API with policy-driven presentation:
  - larger breakpoints => dialog
  - smaller breakpoints => sheet
  - swipe-to-dismiss may be enabled for mobile-sized web and Capacitor; tune behavior by target
- Safe areas are handled by root layouts, overlays, and sticky action bars only (not feature route content).
- Keep route parity between web and Capacitor.
- Keep two Vite configs (`vite.config.ts`, `vite.capacitor.config.ts`).
- No direct platform branching in feature routes.

## File and Naming Rules

- Use target root layout variants:
  - `resources/js/layouts/RootLayout.tsx`
  - `resources/js/layouts/RootLayout.capacitor.tsx`
- Prefer extensionless imports when a target-specific variant should be resolved.
- Prefer `.capacitor.tsx/.ts` variants for target-specific implementation differences over global flags.
- If a target flag is introduced, use it sparingly and only at foundation boundaries.

## Build and Resolver Rules

- Capacitor-only file suffix resolution belongs in `vite.capacitor.config.ts`.
- Do not change `vite.config.ts` to prefer `.capacitor.*`.
- The Capacitor resolver should behave like Metro platform suffix precedence for `.capacitor.*` files only.
- Keep shared Vite behavior (React, Tailwind, aliases) aligned unless a target-specific need is proven.

## Platform Branching Boundaries

- Centralize target/capability checks in shared platform utilities/hooks (for example under `resources/js/lib/` or a dedicated platform module).
- Reusable viewport/media-query hooks belong in `resources/js/hooks/` (not inside UI primitive component files).
- If a JS media query is required to complement Tailwind styling (for example animation behavior), align it to Tailwind defaults and add a code comment noting the matching Tailwind breakpoint token (for example `md = 48rem`) and update requirement if breakpoints are customized.
- Feature routes and route-local components should consume shared policies/hooks instead of calling `Capacitor.isNativePlatform()` directly.
- Target-specific navigation container behavior belongs in root layout variants, not feature routes.

## Navigation and UI Policy

- `web` target:
  - no persistent bottom tab bar on mobile web
- `capacitor` target:
  - may render bottom tab bar and app-specific navigation surfaces
- Breakpoint changes (layout density, stacking, spacing, action placement) should use Tailwind breakpoints and remain breakpoint-driven, not target-driven, unless there is a clear product reason.
- For UI primitives with structural variation (for example modal overlays/headers/actions), prefer composable compound APIs with dot notation (`Modal.Root`, `Modal.Overlay`, etc.) over prop-driven style escape hatches.
- If a primitive is split across multiple files, keep the public compound API intact without using barrel exports.

## Safe Area Policy

- Root layouts own global safe-area padding rules.
- Overlays/sheets/dialogs must be safe-area aware when fixed to viewport edges.
- Sticky action bars/footers must account for safe-area bottom insets.
- Do not add ad hoc safe-area padding in feature route content unless a concrete exception is documented in code comments.

## Back Handling (Capacitor)

- Capacitor back behavior is centralized in `RootLayout.capacitor.tsx` (or a hook used by it).
- Feature routes should remain unaware of native back integration.
- Start with minimal, regular-app behavior and iterate after device testing.

## Testing Expectations (Platform Foundation)

- Add or update focused tests when foundation behavior is deterministic and can be mocked (for example target utilities, resolver helper logic, or Capacitor back-handler registration/cleanup behavior).
- For native-only runtime behavior (safe areas, hardware back integration), keep automated tests focused on app logic and listener wiring; do not rely on jsdom to prove native WebView behavior.
- Pair native foundation changes with manual simulator/device verification when applicable (iOS safe areas, Android hardware back).
- Build config and resolver changes must still be verified with real target builds (`npm run build`, `npm run cap:build`) even if unit tests are added.

## Make -> Review Workflow (Required for Major Steps)

- Follow `make -> review` for architecture, build config, shared foundations, and multi-file platform changes.
- Implement one step at a time.
- Run the smallest relevant check for that step.
- Stop and summarize changes/tradeoffs before continuing.
- Continue without a stop only if the user explicitly waives the review gate.

## Step Checklist (Platform Foundation Changes)

1. Restate the specific step being changed.
2. Confirm whether the change is `target`, Tailwind breakpoint behavior, or both.
3. Keep changes inside foundation boundaries (Vite config, root layouts, shared primitives, platform utilities).
4. Avoid introducing feature-route platform branches.
5. Decide test coverage for the step (focused unit test and/or simulator/device manual check).
6. Run a targeted verification (build, typecheck, or focused test).
7. Stop for review unless explicitly waived.
8. If a 3rd-party integration is involved, cite the official guide/example and confirm whether the implementation follows it or an approved deviation.
9. If a shared UI primitive API is involved, confirm the API remains composition-first (dot notation compound components when applicable) and does not drift into prop-surface customization.

## Validation Checklist

- Web target still resolves default files (no `.capacitor.*` leakage).
- Capacitor target resolves `.capacitor.*` variants when present.
- Root layout variants are selected correctly.
- Modal API remains one API while presentation changes by policy.
- Safe-area logic is limited to allowed layers.
- No direct platform checks were added to feature routes.
- Relevant automated tests were added/updated when feasible for changed foundation logic.
- Native-only behavior was manually validated on simulator/device when applicable.
- 3rd-party integrations follow official guidance when available (or the deviation was explicitly approved and documented in the review summary).
- Shared UI primitives remain composable (compound API) and supporting hooks/helpers are extracted to appropriate files/scopes.
