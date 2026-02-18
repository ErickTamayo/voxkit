## Collaboration Protocol

*   **Plan First**: Never jump directly into code changes. Produce a concrete, step-by-step plan and review it with the user.
*   **Clarify**: Ask whenever requirements are ambiguous.
*   **Approval**: Wait for explicit acceptance of the plan before modifying code.

## Global Guardrails

* Follow existing code conventions and check sibling files before implementing changes.
* Use descriptive names for methods and variables.
* Reuse existing components before creating new ones.
* Stick to the current project structure; do not create new base folders without approval.
* Do not change dependencies without approval.
* Do not create documentation files unless explicitly requested.
* If frontend changes do not appear, ask whether `npm run build`, `npm run dev`, or `composer run dev` is running.
* Keep replies concise and focused on useful details.

## Skills Activation

Use the relevant skill for each domain instead of embedding all guidance in this file.

* `laravel-backend-core` — Core PHP/Laravel conventions, Laravel 12 structure, test enforcement, and formatting requirements.
* `boost-mcp-workflow` — Required Laravel Boost MCP tool usage and documentation search workflow.
* `pest-testing` — Pest 4 testing patterns and execution workflow.
* `tailwindcss-development` — Tailwind CSS v4 styling patterns and migration-safe utilities.
* `graphql-operation-colocation` — Colocated GraphQL operation and typed codegen workflow.
* `i18n-patterns` — i18n key strategy, interpolation, and translation boundaries.
* `ui-component-patterns` — Reusable component extraction and composition conventions.
