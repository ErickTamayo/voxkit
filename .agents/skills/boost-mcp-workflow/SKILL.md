---
name: boost-mcp-workflow
description: Workflow rules for Laravel Boost MCP tools, including required tool selection and documentation lookup patterns.
---

# Boost MCP Workflow

Apply this skill when using Laravel Boost tools, inspecting Laravel package docs, debugging app state, or sharing project URLs.

## Core Tool Usage

- Use Laravel Boost tools whenever available for application-specific work.
- Use `list-artisan-commands` to verify command names/options before running unfamiliar Artisan commands.
- Use `get-absolute-url` whenever sharing app URLs.
- Use `tinker` for quick Laravel runtime checks when tests are not a better fit.
- Use `database-query` for read-only SQL checks.
- Use `database-schema` before migration/model work that depends on column/index details.
- Use `browser-logs` for recent frontend JavaScript errors and browser exceptions.

## Documentation Search Requirements

- Use `search-docs` before implementing Laravel ecosystem behavior.
- Keep queries broad and topic-based first, then refine.
- Do not include package names inside query text.
- Run multiple queries when terminology might vary.

## Search Patterns

- `authentication`
- `rate limit`
- `middleware "rate limit"`
- `["authentication", "middleware"]`

## Practical Guardrails

- Prefer Boost tooling over ad-hoc shell/database probing when equivalent tooling exists.
- Keep logs and query checks focused on the smallest scope needed.
- Treat stale browser logs as low-value unless they are reproducible and recent.
