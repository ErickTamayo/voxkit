## Collaboration Protocol

*   **Plan First**: Never jump directly into code changes. Produce a concrete, step-by-step plan and review it with the user.
*   **Clarify**: Ask whenever requirements are ambiguous.
*   **Approval**: Wait for explicit acceptance of the plan before modifying code.
*   **Make -> Review (Major Steps)**: For major steps (architecture, build config, shared foundations, multi-file refactors), implement one step at a time, run the smallest relevant check, stop, summarize what changed, and wait for review before continuing.
*   **Review Gate Opt-Out**: Only skip a major-step review stop when the user explicitly says to continue without stopping for review.
*   **Official Pattern First (3rd-party integrations)**: Before implementing non-trivial integrations with external libraries/frameworks (animation, gestures, modal/dialog primitives, routing, build tooling, etc.), check official docs/guides/examples first and follow the recommended pattern when one exists.
*   **Primary Source Order**: Prefer sources in this order: official docs -> official examples -> maintainer repository examples -> community posts.
*   **Deviation Disclosure**: If deviating from an official pattern, explain why, what risk/tradeoff it introduces, and get approval before implementing the deviation.

## Global Guardrails

* Follow existing code conventions and check sibling files before implementing changes.
* Use descriptive names for methods and variables.
* Reuse existing components before creating new ones.
* Stick to the current project structure; do not create new base folders without approval.
* Do not change dependencies without approval.
* Do not create documentation files unless explicitly requested.
* If frontend changes do not appear, ask whether `npm run build`, `npm run dev`, or `composer run dev` is running.
* Keep replies concise and focused on useful details.
* For non-trivial 3rd-party integrations, include the official source link(s) and the specific pattern being followed in the plan/review summary.

## Skills Activation

Use the relevant skill for each domain instead of embedding all guidance in this file.

* `laravel-backend-core` — Core PHP/Laravel conventions, Laravel 12 structure, test enforcement, and formatting requirements.
* `boost-mcp-workflow` — Required Laravel Boost MCP tool usage and documentation search workflow.
* `service-layer-pattern` — Thin transport edges with service-layer orchestration, transactional writes, and exception-first backend behavior.
* `graphql-lighthouse-pattern` — Schema-first Lighthouse conventions with explicit validation, authorization, and resolver boundaries.
* `dto-validation-patterns` — Validation-boundary and payload-mapping patterns for REST/GraphQL inputs.
* `pest-testing` — Pest 4 testing patterns and execution workflow.
* `testing-with-pest-graphql` — Deterministic GraphQL and backend feature-testing patterns.
* `tailwindcss-development` — Tailwind CSS v4 styling patterns and migration-safe utilities.
* `graphql-operation-colocation` — Colocated GraphQL operation and typed codegen workflow.
* `i18n-patterns` — i18n key strategy, interpolation, and translation boundaries.
* `ui-component-patterns` — Reusable component extraction and composition conventions.
* `web-capacitor-foundation` — Shared web/Capacitor frontend foundation rules (target vs viewport, `RootLayout.capacitor`, `.capacitor.*` resolution, modal/sheet policy, safe-area boundaries, and make->review platform changes).
* `testing-with-msw-vitest` — Vitest + MSW frontend testing patterns for API-backed UI behavior.
