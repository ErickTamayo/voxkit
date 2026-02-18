# Project Conventions

Use these conventions when selecting Laravel Boost tools.

## Preferred first calls

- `application-info` to confirm versions and installed package context.
- `search-docs` before implementing Laravel/Pest/Tailwind behavior.

## Routing and URL sharing

- Use `list-routes` for route discovery.
- Use `get-absolute-url` when sharing local project URLs.

## Data and runtime inspection

- Use `database-schema` before schema-dependent changes.
- Use `database-query` for read-only DB checks.
- Use `tinker` only when tests/docs cannot answer the runtime question.

## Frontend diagnostics

- Use `browser-logs` for recent JavaScript/runtime issues.

## Quality workflow alignment

- Keep Boost-assisted investigation output minimal and actionable.
- Convert findings into tests or concrete edits rather than long prose summaries.
